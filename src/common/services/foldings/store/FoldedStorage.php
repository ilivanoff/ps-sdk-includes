<?php

/**
 * Хранилище информации о фолдингах, сущностях фолдингов и т.д.
 *
 * @author azazello
 */
final class FoldedStorage extends AbstractSingleton {

    /**
     * @var PsLoggerInterface 
     */
    private $LOGGER;

    /**
     * @var PsProfilerInterface 
     */
    private $PROFILER;

    /**
     * Уникальные идентификаторы фолдингов
     * 
     * @var array
     */
    private $FOLDINGS = array();

    /**
     * Карта:
     * тип_фолдинга => array('сущность' => 'абсолютный_путь_к_директории_сущности')
     * 
     * @var array
     */
    private $FOLDING_2_ENTITY_2_ENTABSPATH = array();

    /**
     * Карта:
     * тип_фолдинга => array('сущность' => 'относительный_путь_к_директории_сущности')
     * 
     * @var array
     */
    private $FOLDING_2_ENTITY_2_ENTRELPATH = array();

    /**
     * Карта:
     * префикс_ресурсов => тип_фолдинга
     * 
     * @var array
     */
    private $SOURCE_2_FOLDING = array();

    /**
     * Карта:
     * тип фолдинга => массив подтипов фолдинга
     * 
     * @var array
     */
    private $TYPE_2_STYPE = array();

    /**
     * Карта:
     * префикс_классов => тип_фолдинга
     * 
     * @var array
     */
    private $CLASSPREFIX_2_FOLDING = array();

    /** @return FoldedStorage */
    protected static function inst() {
        return parent::inst();
    }

    /**
     * Метод предзагружает фолдинги
     */
    public static function init() {
        self::inst();
    }

    /**
     * В конструкторе пробежимся по всем хранилищам и соберём все фолдинги
     */
    protected function __construct() {
        $this->LOGGER = PsLogger::inst(__CLASS__);
        $this->PROFILER = PsProfiler::inst(__CLASS__);

        $this->PROFILER->start('Loading folding entities');

        /*
         * Пробегаемся по всему, настроенному в foldings.ini
         */
        foreach (FoldingsIni::foldingsRel() as $foldedUnique => $dirRelPathes) {
            $this->FOLDING_2_ENTITY_2_ENTABSPATH[$foldedUnique] = array();
            $this->FOLDING_2_ENTITY_2_ENTRELPATH[$foldedUnique] = array();

            /*
             * Загрузим карту сущностей
             */
            foreach (array_unique($dirRelPathes) as $dirRelPath) {
                $dm = DirManager::inst($dirRelPath);
                foreach ($dm->getSubDirNames() as $entity) {
                    //Не будем проверять наличие этой сущности, более поздние смогут её переопределить
                    //array_key_exists($entity, $this->FOLDING_2_ENTITY_2_ENTABSPATH[$foldedUnique])
                    $this->FOLDING_2_ENTITY_2_ENTABSPATH[$foldedUnique][$entity] = $dm->absDirPath($entity);
                    $this->FOLDING_2_ENTITY_2_ENTRELPATH[$foldedUnique][$entity] = $dm->relDirPath($entity);
                }
            }
            ksort($this->FOLDING_2_ENTITY_2_ENTABSPATH[$foldedUnique]);
            ksort($this->FOLDING_2_ENTITY_2_ENTRELPATH[$foldedUnique]);

            /*
             * Построим карты сущностей к типам фолдингов, чтобы мы могли через них выйти на фолдинг
             */
            self::extractFoldedTypeAndSubtype($foldedUnique, $ftype, $fsubtype);

            /*
             * Построим карту отношения идентификатора фолдинга к коду ресурса
             * slib => lib-s
             */
            $this->SOURCE_2_FOLDING[$fsubtype . $ftype] = $foldedUnique;

            /*
             * Построим карту отношения идентификатора фолдинга к префиксу класса
             * SLIB_ => lib-s
             */
            $this->CLASSPREFIX_2_FOLDING[strtoupper($fsubtype . $ftype) . '_'] = $foldedUnique;

            /*
             * Построим карту отношения типа фолдинга к массиву подтипов фолдингов
             * lib => array('s', 'p')
             * pl = > null
             */
            if (array_key_exists($ftype, $this->TYPE_2_STYPE)) {
                //Если мы второй раз попали в этот блок для типа фолдинга, то он должен иметь подтип [lib=>array('s')].
                check_condition(is_array($this->TYPE_2_STYPE[$ftype]), "Уже зарегистрирован фолдинг с типом [$ftype] без подтипов");
                $this->TYPE_2_STYPE[$ftype][] = check_condition($fsubtype, "Уже зарегистрирован фолдинг с типом [$ftype] и с подтипами");
            } else {
                if ($fsubtype) {
                    //Новый тип фолдинга с подтипом.
                    $this->TYPE_2_STYPE[$ftype] = array($fsubtype);
                } else {
                    //Новый тип фолдинга без подтипа.
                    $this->TYPE_2_STYPE[$ftype] = null;
                }
            }
        }

        //Отсортируем по уникальным кодам фолдингов
        ksort($this->FOLDING_2_ENTITY_2_ENTABSPATH);
        ksort($this->FOLDING_2_ENTITY_2_ENTRELPATH);
        ksort($this->TYPE_2_STYPE);

        //Установим идентификаторы фолдингов
        $this->FOLDINGS = array_keys($this->FOLDING_2_ENTITY_2_ENTRELPATH);

        $sec = $this->PROFILER->stop();

        if ($this->LOGGER->isEnabled()) {
            $this->LOGGER->info('FOLDINGS: {}', print_r($this->FOLDINGS, true));
            $this->LOGGER->info('FOLDING_2_ENTITY_2_ENTABSPATH: {}', print_r($this->FOLDING_2_ENTITY_2_ENTABSPATH, true));
            $this->LOGGER->info('FOLDING_2_ENTITY_2_ENTRELPATH: {}', print_r($this->FOLDING_2_ENTITY_2_ENTRELPATH, true));
            $this->LOGGER->info('TYPE_2_STYPE: {}', print_r($this->TYPE_2_STYPE, true));
            $this->LOGGER->info('CLASSPREFIX_2_FOLDING: {}', print_r($this->CLASSPREFIX_2_FOLDING, true));
            $this->LOGGER->info('SOURCE_2_FOLDING: {}', print_r($this->SOURCE_2_FOLDING, true));
            $this->LOGGER->info('BUILDING_TIME: {} sec', $sec->getTotalTime());
        }
    }

    /**
     * Метод возвращает уникальные коды фолдингов: [pl, lib-p, ...]
     */
    public static function listFoldingUniques() {
        return self::inst()->FOLDINGS;
    }

    /**
     * Метод возвращает все сущности фолдингов и абсолютные пути к ним
     */
    public static function listEntitiesAbs() {
        return self::inst()->FOLDING_2_ENTITY_2_ENTABSPATH;
    }

    /**
     * Метод возвращает все сущности фолдингов и относительные пути к ним
     */
    public static function listEntitiesRel() {
        return self::inst()->FOLDING_2_ENTITY_2_ENTRELPATH;
    }

    /**
     * Проверка существования фолдинга
     * @param string $foldedUnique - код фолдинга [lib-p]
     */
    public static function existsFolding($foldedUnique) {
        return array_key_exists($foldedUnique, self::inst()->FOLDING_2_ENTITY_2_ENTABSPATH);
    }

    /**
     * Метод утверждает, что фолдинг существует
     * @param string $foldedUnique - код фолдинга [lib-p]
     */
    public static function assertExistsFolding($foldedUnique) {
        return check_condition(self::existsFolding($foldedUnique), "Фолдинг с идентификатором [$foldedUnique] не существует");
    }

    /**
     * Проверка существования префикса класса
     * @param string $classPrefix - префикс класса фолдингов [PLIB_]
     */
    public static function existsClassPrefix($classPrefix) {
        return array_key_exists($classPrefix, self::inst()->CLASSPREFIX_2_FOLDING);
    }

    /**
     * Метод утверждает, что префикс классов существует
     * @param string $classPrefix - префикс класса фолдингов [PLIB_]
     */
    public static function assertExistsClassPrefix($classPrefix) {
        return check_condition(self::existsClassPrefix($classPrefix), "Фолдинг с префиксом классов [$classPrefix] не существует");
    }

    /**
     * Проверка существования префикса класса
     * @param string $sourcePrefix - префикс ресурсов фолдинга [plib, pp]
     */
    public static function existsSourcePrefix($sourcePrefix) {
        return array_key_exists($sourcePrefix, self::inst()->SOURCE_2_FOLDING);
    }

    /**
     * Метод утверждает, что префикс ресурсов существует
     * @param string $sourcePrefix - префикс ресурсов фолдинга [plib, pp]
     */
    public static function assertExistsSourcePrefix($sourcePrefix) {
        return check_condition(self::existsSourcePrefix($sourcePrefix), "Фолдинг с префиксом ресурсов [$sourcePrefix] не существует");
    }

    /**
     * Проверка существования сущности фолдинга
     * 
     * @param string $foldedUnique - код фолдинга [lib-p]
     * @param string $entity - код сущности
     */
    public static function existsEntity($foldedUnique, $entity) {
        return isset(self::inst()->FOLDING_2_ENTITY_2_ENTABSPATH[$foldedUnique][$entity]);
    }

    /**
     * Метод утверждает, что сущность фолдинга существует
     * @param string $foldedUnique - код фолдинга [lib-p]
     * @param string $entity - код сущности
     */
    public static function assertExistsEntity($foldedUnique, $entity) {
        return check_condition(self::existsEntity($foldedUnique, $entity), "Сущность фолдинга [$foldedUnique-$entity] не существует");
    }

    /**
     * Метод возвращает сущности для указанного типа фолдинга
     * @param string $foldedUnique - код фолдинга [lib-p]
     */
    public static function getEntities($foldedUnique) {
        return self::assertExistsFolding($foldedUnique) ? self::inst()->FOLDING_2_ENTITY_2_ENTABSPATH[$foldedUnique] : null;
    }

    /**
     * Метод возвращает кол-во сущностей для указанного типа фолдинга
     * @param string $foldedUnique - код фолдинга [lib-p]
     */
    public static function getEntitiesCount($foldedUnique) {
        return self::assertExistsFolding($foldedUnique) ? count(self::inst()->FOLDING_2_ENTITY_2_ENTABSPATH[$foldedUnique]) : null;
    }

    /**
     * Метод возвращает сущность указанного типа фолдинга
     * @param string $foldedUnique - код фолдинга [lib-p]
     * @param string $entity - код сущности
     */
    public static function getEntity($foldedUnique, $entity) {
        return self::assertExistsEntity($foldedUnique, $entity) ? self::inst()->FOLDING_2_ENTITY_2_ENTABSPATH[$foldedUnique][$entity] : null;
    }

    /**
     * Метод возвращает элемент в директории указанной сущности
     * 
     * @param string $foldedUnique - код фолдинга [lib-p]
     * @param string $entity - код сущности
     * @param mixed $dirs - поддиректории
     * @param string $name - название файла
     * @param string $ext - расширение файла
     */
    public static function getEntityChild($foldedUnique, $entity, $dirs, $name = null, $ext = null) {
        return file_path(array(self::getEntity($foldedUnique, $entity), $dirs), $name, $ext);
    }

    /**
     * Получение префикса класса для фолдинга: lib-p => PLIB_
     * @param string $foldedUnique - код фолдинга [lib-p]
     */
    public static function getFoldingClassPrefix($foldedUnique) {
        return self::assertExistsFolding($foldedUnique) ? array_search($foldedUnique, self::inst()->CLASSPREFIX_2_FOLDING) : null;
    }

    /**
     * Получение фолдинга по префиксу класса: PLIB_ => lib-p
     * @param string $classPrefix - префикс класса [PLIB_]
     * @param bool $assert - признак проверки существования
     */
    public static function getFoldingByClassPrefix($classPrefix, $assert = true) {
        return self::existsClassPrefix($classPrefix) ? self::inst()->CLASSPREFIX_2_FOLDING[$classPrefix] : ($assert ? self::assertExistsClassPrefix($classPrefix) : null);
    }

    /**
     * Получение префикса ресурсов для фолдинга: lib-p => plib
     * @param string $foldedUnique - код фолдинга [lib-p]
     */
    public static function getFoldingSourcePrefix($foldedUnique) {
        return self::assertExistsFolding($foldedUnique) ? array_search($foldedUnique, self::inst()->SOURCE_2_FOLDING) : null;
    }

    /**
     * Получение фолдинга по префиксу ресурса: plib => lib-p
     * @param string $sourcePrefix - префикс ресурса [plib]
     * @param bool $assert - признак проверки существования
     */
    public static function getFoldingBySourcePrefix($sourcePrefix, $assert = true) {
        return self::existsSourcePrefix($sourcePrefix) ? self::inst()->SOURCE_2_FOLDING[$sourcePrefix] : ($assert ? self::assertExistsSourcePrefix($sourcePrefix) : null);
    }

    /**
     * Метод проверяет, имеет ли фолдинг с данным типом - подтип.
     * Например, все фолдинги библиотек объединены в фолдинг с типом lib и разными подтипами [s, p, ...].
     * 
     * @param string $type - тип фолдинга
     * @param bool $assertExists - ругаться ли, если фолдинг не найден
     * @return true, false, null если фолдинг не найден
     */
    public static function isFoldingHasSubtype($type, $assertExists = true) {
        if (array_key_exists($type, self::inst()->TYPE_2_STYPE)) {
            return is_array(self::inst()->TYPE_2_STYPE[$type]);
        } else {
            check_condition(!$assertExists, "Не удалось найти folding с типом [$type]");
            return null; //---
        }
    }

    /**
     * Метод возвращает список типоф волдингов: [pp, pb, lib, pl, ...]
     */
    public static function listFoldedTypes() {
        return array_keys(self::inst()->TYPE_2_STYPE);
    }

    /**
     * Метод патыется получить путь к сущности фолдинга по названию класса.
     * Все классы для сущностей фолдинга начинаются на префикс с подчёркиванием,
     * например PL_, на этом и основан способ подключени класса.
     * 
     * Метод должен быть статическим, так как если мы попытаемся получить путь к
     * классу фолидна, создаваемому Handlers, то никогда его не загрузим.
     */
    public static function tryGetEntityClassPath($className) {
        if (!self::extractInfoFromClassName($className, $classPrefix, $entity)) {
            return null; //---
        }
        $foldedUnique = self::inst()->CLASSPREFIX_2_FOLDING[$classPrefix];
        if (!$foldedUnique || !self::existsEntity($foldedUnique, $entity)) {
            return null; //---
        }
        $classPath = self::getEntityChild($foldedUnique, $entity, null, $entity, PsConst::EXT_PHP);
        return is_file($classPath) ? $classPath : null;
    }

    /**
     * Извлекает информацию из названия класса. Пример:
     * PL_advgraph
     * Будет извлечено PL_ и advgraph.
     * 
     * @param type $className
     * @return null
     */
    public static function extractInfoFromClassName($className, &$classPrefix, &$entity) {
        if (1 !== preg_match('/^[A-Z]+\_/', $className, $matches)) {
            return false; //---
        }
        $ident = cut_string_start($className, $matches[0]);
        if (1 !== preg_match('/^[A-Za-z0-9]+$/', $ident, $imatches)) {
            return false; //---
        }
        $classPrefix = $matches[0];
        $entity = $imatches[0];
        return true; //---
    }

    /**
     * Извлекает тип и подтип фолдинга из его идентификатора:
     * [lib-p] => [lib, p]
     */
    public static function extractFoldedTypeAndSubtype($foldedUnique, &$type, &$subtype) {
        $tokens = explode('-', PsCheck::notEmptyString($foldedUnique), 3);
        $tokensCnt = count($tokens);
        switch ($tokensCnt) {
            case 1:
                $type = PsCheck::notEmptyString($tokens[0]);
                $subtype = '';
                break;
            case 2:
                $type = PsCheck::notEmptyString($tokens[0]);
                $subtype = PsCheck::notEmptyString($tokens[1]);
                break;
            default:
                PsUtil::raise('Invalid folded resource ident: [{}]', $foldedUnique);
        }
    }

}

?>