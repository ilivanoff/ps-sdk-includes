<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of AbstractConfigIni
 *
 * @author azazello
 */
abstract class AbstractIni {

    /**
     * Обработанные настройки.
     * config.ini => scope => settings_array
     */
    private static $INI = array();

    /**
     * Название класса
     */
    public static final function getClass() {
        return get_called_class();
    }

    /**
     * Название файла конфига: 'config.ini'
     */
    private static function getConfigName($scope) {
        $pieces[] = cut_string_end(strtolower(self::getClass()), 'ini');
        switch ($scope) {
            case ENTITY_SCOPE_PROJ_EXT:
                $pieces[] = 'ext';
                break;
        }
        $pieces[] = PsConst::EXT_INI;
        return implode('.', $pieces);
    }

    /**
     * Получает ссылку на файл с конфигом .ini
     */
    private static function getIniDi($scope) {
        $iniFileName = self::getConfigName($scope);
        switch ($scope) {
            case ENTITY_SCOPE_SDK:
                return DirManager::inst(PS_DIR_INCLUDES)->getDirItem(DirManager::DIR_CONFIG, $iniFileName);
            case ENTITY_SCOPE_PROJ:
                return DirManager::inst(PS_DIR_ADDON)->getDirItem(DirManager::DIR_CONFIG, $iniFileName);
            case ENTITY_SCOPE_PROJ_EXT:
                return DirManager::inst(PS_DIR_ADDON)->getDirItem(DirManager::DIR_CONFIG, $iniFileName);
        }
        return PsUtil::raise('Invalid scope [{}] for method {}::{}', $scope, __CLASS__, __FUNCTION__);
    }

    /**
     * Проверка существования самого ini афйла
     */
    public static function existsIni($scope) {
        return self::getIniDi($scope)->isFile();
    }

    /**
     * Проверка существования ini файла в sdk
     */
    public static function existsSdk() {
        return self::existsIni(ENTITY_SCOPE_SDK);
    }

    /**
     * Проверка существования проектного ini
     */
    public static function existsProj() {
        return self::existsIni(ENTITY_SCOPE_PROJ);
    }

    /**
     * Получение содержимого ini афйла
     */
    public static function getIniContent($scope) {
        return self::getIniDi($scope)->getFileContents(false);
    }

    /**
     * Получение содержимого ini файла
     */
    public static function saveIniContent($scope, $content) {
        self::getIniDi($scope)->putToFile($content);
        unset(self::$INI[self::getConfigName($scope)]);
    }

    /**
     * Метод загружает все группы настроек
     */
    public static function getIni($scope = ENTITY_SCOPE_ALL) {
        $config = self::getConfigName($scope);

        if (!array_key_exists($config, self::$INI)) {

            /*
             * Конфиги SDK есть всегда
             */
            self::$INI[$config][ENTITY_SCOPE_SDK] = self::getIniDi(ENTITY_SCOPE_SDK)->parseAsIni(true);

            /*
             * Если мы работаем в режиме SDK или не существует проектный файл настроек - выполняем быструю инициализацию.
             * Отдельно к контексту ENTITY_SCOPE_PROJ_EXT обращаться нельзя
             */
            $projDi = self::getIniDi(ENTITY_SCOPE_PROJ);

            if (self::isSdk() || !$projDi->isFile()) {
                self::$INI[$config][ENTITY_SCOPE_PROJ] = array();
                self::$INI[$config][ENTITY_SCOPE_ALL] = self::$INI[$config][ENTITY_SCOPE_SDK];
            } else {
                self::$INI[$config][ENTITY_SCOPE_PROJ] = to_array(PsUtil::mergeIniFiles($projDi->parseAsIni(true, false), self::getIniDi(ENTITY_SCOPE_PROJ_EXT)->parseAsIni(true, false)));
                self::$INI[$config][ENTITY_SCOPE_ALL] = PsUtil::mergeIniFiles(self::$INI[$config][ENTITY_SCOPE_SDK], self::$INI[$config][ENTITY_SCOPE_PROJ]);
            }

            //Экземпляр логгера должен быть создан именно здесь - после того, как был наполнен массив параметров
            //TODO - нельзя здесь использовать логгер. Можно вывести в FileLogWriter.
            /*
              $LOGGER = PsLogger::inst(__CLASS__);

              if ($LOGGER->isEnabled()) {
              foreach (self::$INI[$config] as $iniScope => $iniProps) {
              $LOGGER->info('{} [{}]:', $config, $iniScope);
              $LOGGER->info(print_r($iniProps, true));
              $LOGGER->info();
              }
              }
             */
        }

        PsUtil::assert(array_key_exists($scope, self::$INI[$config]), "Unknown scope [{}]", $scope);

        return self::$INI[$config][$scope];
    }

    /**
     * Проверка существования группы
     */
    public static function hasGroup($group, $scope = ENTITY_SCOPE_ALL) {
        return array_key_exists($group, self::getIni($scope));
    }

    /**
     * Возвращает все группы заданного scope
     */
    public static function getGroups($scope = ENTITY_SCOPE_ALL) {
        return array_keys(self::getIni($scope));
    }

    /**
     * Загрузка настроек конкретной группы
     */
    public static function getGroup($group, $mandatory = true, $scope = ENTITY_SCOPE_ALL) {
        if (self::hasGroup($group, $scope)) {
            return self::getIni($scope)[$group];
        }
        if ($mandatory) {
            PsUtil::raise('Required config group [{}] not found in {} [{}]', $group, static::getConfigName($scope), $scope);
        }
        return null; //--
    }

    /**
     * Загрузка настроек конкретной группы или null
     */
    public static function getGroupOrNull($group, $scope = ENTITY_SCOPE_ALL) {
        return self::getGroup($group, false, $scope);
    }

    /**
     * Проверка существования свойства
     */
    public static function hasProp($group, $prop, $scope = ENTITY_SCOPE_ALL) {
        return self::hasGroup($group, $scope) && array_key_exists($prop, self::getGroup($group, true, $scope));
    }

    /**
     * Загрузка конкретной настройки
     */
    public static function getProp($group, $prop, $mandatory = true, $scope = ENTITY_SCOPE_ALL) {
        if (self::hasProp($group, $prop, $scope)) {
            return self::getGroup($group, true, $scope)[$prop];
        }
        if ($mandatory) {
            PsUtil::raise('Required config property [{}/{}] not found in {} [{}]', $group, $prop, static::getConfigName($scope), $scope);
        }
        return null; //--
    }

    /**
     * Загрузка конкретной настройки или null
     */
    public static function getPropOrNull($group, $prop, $scope = ENTITY_SCOPE_ALL) {
        return self::getProp($group, $prop, false, $scope);
    }

    /**
     * Загрузка конкретной настройки с проверкой её типа
     */
    public static function getPropCheckType($group, $prop, array $allowedTypes = null, $scope = ENTITY_SCOPE_ALL) {
        return PsCheck::phpVarType(self::getPropOrNull($group, $prop, $scope), $allowedTypes);
    }

    /**
     * Метод возвращает признак - работаем ли мы в контексте проекта.
     * Для этого должен существовать конфиг проекнтных настроек: 
     * /ps-addon/config/config.ini
     * 
     * @return bool
     */
    public static final function isProject() {
        return DirManager::inst(PS_DIR_ADDON)->getDirItem(DirManager::DIR_CONFIG, DirManager::DIR_CONFIG, PsConst::EXT_INI)->isFile();
    }

    /**
     * Метод возвращает признак - работаем ли мы в контексте только SDK.
     * 
     * При парсинге проектные конфиги не будут учитываться, даже если они будут 
     * существовать.
     * 
     * @return bool
     */
    public static final function isSdk() {
        return !self::isProject();
    }

}

?>