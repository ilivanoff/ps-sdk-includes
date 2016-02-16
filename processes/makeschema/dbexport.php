<?php

/**
 * Процесс строит скрипты разворачивания БД
 * 
 * @param array $argv
 */
function executeProcess(array $argv) {
    /*
     * СОЗДАЁМ SQL
     * 
     * Нам нужны настройки таблиц, которые неоткуда взять, кроме как из базы, поэтому для экспорта данных нужна БД.
     */
    $DB = DirManager::inst(__DIR__ . '/temp');
    $SDK = DirManager::inst(__DIR__ . '/temp/ps-sdk');

    //Почистим файлы, которые нам не интересны
    $cleanExt = array(PsConst::EXT_TXT, PsConst::EXT_SQL);
    dolog('Clearing not {} files from temp dir', array_to_string($cleanExt));
    /* @var $item DirItem */
    foreach ($DB->getDirContentFull(null, DirItemFilter::FILES) as $item) {
        if (!$item->checkExtension($cleanExt)) {
            dolog('[-] {}', $item->remove()->getRelPath());
        }
    }

    //Пробежимся по скоупам и выполним обработку
    /* @var $DIR DirManager */
    foreach (array(ENTITY_SCOPE_SDK => $SDK, ENTITY_SCOPE_PROJ => $DB) as $scope => $DM) {
        dolog();
        dolog('***************************** SCOPE [{}] *****************************', $scope);
        dolog('Working directory: [{}]', $DM->absDirPath());

        $SCHEMA = $DM->getDirItem(null, 'schema', PsConst::EXT_SQL);
        if (!$SCHEMA->isFile()) {
            dolog('schema.sql is not exists, skipping');
            continue; //---
        }

        //Директория с системными объектами
        $DM_SYSOBJECTS = DirManager::inst($DM->absDirPath(), 'sysobjects');

        //Директория, в которой будет содержимое для автосгенерированных файлов
        $DM_BUILD = DirManager::inst($DM->absDirPath(), 'build')->clearDir();

        //Создадим ссылку на файл с объектами
        $DM_BUILD_ALL_SQL = $DM_BUILD->getDirItem(null, 'all', PsConst::EXT_SQL)->getSqlFileBuilder();

        //Строим objects.sql
        dolog('Processing all.sql');

        /*
         * Получаем строки с включениями в objects.sql
         */
        $ALL_LINES = $DM_SYSOBJECTS->getDirItem(null, 'all', PsConst::EXT_TXT)->getFileLines(false);
        if (empty($ALL_LINES)) {
            dolog('No includes');
        } else {
            dolog('Adding {} includes from all.txt', count($ALL_LINES));
            foreach ($ALL_LINES as $include) {
                dolog('+ {}', $include);
                $DM_BUILD_ALL_SQL->appendFile($DM_SYSOBJECTS->getDirItem($include));
            }
        }

        // << Сохраняем objects.sql
        $DM_BUILD_ALL_SQL->save();

        /*
         * Создаём скрипты инициализации для схем
         */
        dolog('Processing default connection names: {}', array_to_string(array_values(PsConnectionParams::getDefaultConnectionNames())));

        foreach (PsConnectionParams::getDefaultConnectionNames() as $connection) {
            //На момент обработки скоупа мы не должны быть подключены никуда
            PsConnectionPool::assertDisconnectied();

            //Для данного скоупа не задан коннект? Пропускаем...
            if (PsConnectionParams::CONN_ROOT == $connection) {
                dolog('Skip {}', $connection);
                continue; //---
            }

            if (!PsConnectionParams::has($connection, $scope)) {
                dolog('No connection properties for {}', $connection);
                continue; //---
            }

            //Поработаем с настройками
            $props = PsConnectionParams::get($connection, $scope);
            $database = $props->database();

            if (empty($database)) {
                continue; //Не задана БД - пропускаем (для root)
            }

            dolog('Making schema script for {}', $props);

            $SCHEMA_DI = $DM_BUILD->getDirItem('schemas', $database, PsConst::EXT_SQL)->makePath();
            check_condition(!$SCHEMA_DI->isFile(), 'Schema file for database "{}" is already exists. Dublicate database names?', $database);
            $SCHEMA_SQL = $SCHEMA_DI->getSqlFileBuilder();

            //DROP+USE
            $SCHEMA_SQL->clean();
            $SCHEMA_SQL->appendLine("DROP DATABASE IF EXISTS $database;");
            $SCHEMA_SQL->appendLine("CREATE DATABASE $database CHARACTER SET utf8 COLLATE utf8_general_ci;");
            $SCHEMA_SQL->appendLine("USE $database;");

            //CREATE USER
            $grant = "grant all on {}.* to '{}'@'{}' identified by '{}';";
            $SCHEMA_SQL->appendMlComment('Create user with grants');
            $SCHEMA_SQL->appendLine(PsStrings::replaceWithBraced($grant, $database, $props->user(), $props->host(), $props->password()));

            if ($scope == ENTITY_SCOPE_PROJ) {
                dolog('+ SDK PART');

                //Добавим секцию в лог
                $SCHEMA_SQL->appendMlComment('>>> SDK');

                //CREATE CHEMA SCRIPT
                $SCHEMA_SQL->appendFile($SDK->getDirItem(null, 'schema', PsConst::EXT_SQL));

                //OBJECTS SCRIPT
                $SCHEMA_SQL->appendFile($SDK->getDirItem('build', 'all', PsConst::EXT_SQL));

                //Добавим секцию в лог
                $SCHEMA_SQL->appendMlComment('<<< SDK');
            }

            //CREATE CHEMA SCRIPT
            $SCHEMA_SQL->appendFile($SCHEMA);

            //OBJECTS SCRIPT
            $SCHEMA_SQL->appendFile($DM_BUILD_ALL_SQL->getDi());

            /*
             * Мы должны создать тестовую схему, чтобы убедиться, что всё хорошо и сконфигурировать db.ini
             */
            if ($connection != PsConnectionParams::CONN_TEST) {
                //Всё, сохраняем скрипт, работа закончена
                $SCHEMA_SQL->save();

                continue; //---
            }

            /*
             * На тестовой схеме прогоняем скрипт
             */
            dolog('Making physical schema {}', $props);

            $rootProps = PsConnectionParams::get(PsConnectionParams::CONN_ROOT);
            dolog('Root connection props: {}', $rootProps);

            $rootProps->execureShell($SCHEMA_SQL->getDi());

            dolog('Connecting to [{}]', $props);
            PsConnectionPool::configure($props);

            $tables = PsTable::all();

            /*
             * Нам нужно определить новый список таблиц SDK, чтобы по ним 
             * провести валидацию новых db.ini.
             * 
             * Если мы обрабатываем проект, то SDK-шный db.ini уже готов и 
             * можем положиться на него. Если мы подготавливаем SDK-шный db.ini,
             * но новый список таблиц возмём из развёрнутой тестовой БД.
             */
            $sdkTableNames = $scope == ENTITY_SCOPE_SDK ? array_keys($tables) : $SDK->getDirItem('build', 'tables', PsConst::EXT_TXT)->getFileLines();

            if ($scope == ENTITY_SCOPE_PROJ) {
                //Уберём из всех таблиц - SDK`шные
                array_remove_keys($tables, $sdkTableNames);
            }

            $scopeTableNames = array_keys($tables);
            sort($scopeTableNames);

            /*
             * Составим список таблиц.
             * Он нам особенно не нужен, но всёже будем его формировать для наглядности - какие таблицы добавились.
             */
            $TABLES_DI = $DM_BUILD->getDirItem(null, 'tables', PsConst::EXT_TXT)->touch()->putToFile(implode("\n", $scopeTableNames));
            dolog('Tables saved to {}: {}', $TABLES_DI->getRelPath(), print_r($scopeTableNames, true));

            /*
             * Выгрузим данные из таблиц в файл, чтобы убедиться, что всё корректно вставилось.
             */
            if ($scopeTableNames) {
                dolog("Exporting '{}' schema tables data to file", $database);

                $DATA_DI_SQL = $DM_BUILD->getDirItem(null, 'data', PsConst::EXT_SQL)->getSqlFileBuilder();

                $DATA_DI_SQL->clean();

                //Пробегаемся по таблицам
                foreach ($scopeTableNames as $tableName) {
                    $fileData = PsTable::inst($tableName)->exportAsSqlString();
                    if ($fileData) {
                        dolog(' + {} [not empty]', $tableName);
                        $DATA_DI_SQL->appendMlComment('+ table ' . $tableName);
                        $DATA_DI_SQL->appendLine($fileData);
                    } else {
                        dolog(' - {} [empty]', $tableName);
                    }
                }

                $DATA_DI_SQL->save();
            }

            /*
             * Теперь ещё создадим тестовые объекты.
             * Для каждого скоупа свои тестовые данные, так что таблицы можно называть одинаково.
             */
            dolog('Add test part');
            $SCHEMA_SQL->appendMlComment('Test part');

            /*
              if ($scope == ENTITY_SCOPE_PROJ) {
              dolog('+ SDK TEST PART');

              //Добавим секцию в лог
              $SCHEMA_SQL->appendMlComment('>>> SDK TEST PART');

              //CREATE CHEMA SCRIPT
              $SCHEMA_SQL->appendFile($SDK->getDirItem('sysobjects/test', 'schema', PsConst::EXT_SQL));

              //ADD TEST DATA
              $SCHEMA_SQL->appendFile($SDK->getDirItem('sysobjects/test', 'data', PsConst::EXT_SQL));

              //Добавим секцию в лог
              $SCHEMA_SQL->appendMlComment('<<< SDK TEST PART');
              }
             */
            $SCHEMA_SQL->appendFile($DM_SYSOBJECTS->getDirItem('test', 'schema', PsConst::EXT_SQL), false);
            $SCHEMA_SQL->appendFile($DM_SYSOBJECTS->getDirItem('test', 'data', PsConst::EXT_SQL), false);
            $SCHEMA_SQL->save();
            #end conn== TEST

            /*
             * Всё, сохраняем финальный скрипт
             */
            //SAVE .sql
            $SCHEMA_SQL->save();

            //Переразвернём тестовую схему с тестовыми таблицами
            dolog("Rebuilding checma '{}'", $database);
            $rootProps->execureShell($SCHEMA_SQL->getDi());

            //Отключимся от схемы
            PsConnectionPool::disconnect();
        }
    }

    dolog('Database schemas successfully exported');
}

//Отключаем автоматический коннект на базу, чтоыб наш генератор ничего ненабедокурил на продуктиве
$CALLED_FILE = __FILE__;
$LOGGERS_LIST[] = 'PsConnectionParams';
require_once dirname(__DIR__) . '/ProcessStarter.php';
?>