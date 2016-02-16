<?php

/**
 * Хранилище экземпляров фолдингов, с которыми можно работать.
 * Можно переопределить в config.ini и использовать проектный.
 *
 * @author azazello
 */
class FoldedStorageInsts {

    /**
     * @var PsLoggerInterface 
     */
    private $LOGGER;

    /**
     * @var PsProfilerInterface 
     */
    private $PROFILER;

    /** @var arr Список доступных фолдингов */
    private $FOLDINGS = array();

    /**
     * Список всех фолдингов
     */
    public static function listFoldings() {
        return self::inst()->FOLDINGS;
    }

    /**
     * Проверка существования фолдинга
     * @param string $foldedUnique - код фолдинга [lib-p]
     */
    public static function existsFolding($foldedUnique) {
        return array_key_exists($foldedUnique, self::inst()->FOLDINGS);
    }

    /**
     * Метод возвращает уникальные коды фолдингов: [pl, lib-p, ...]
     */
    public static function listFoldingUniques() {
        return array_keys(self::listFoldings());
    }

    /**
     * Метод получает фолдинг по его уникальному идентификатору [ps, lib-p ...]
     * 
     * @param string $unique - уникальный код фолдинга
     * @param bool $assert - проверять ли наличие фолдинга или возвращать null
     * @return FoldedResources
     */
    public static function byUnique($unique, $assert = true) {
        $folding = $unique ? array_get_value($unique, self::inst()->FOLDINGS) : null;
        check_condition(!$assert || $folding, "Экземпляр фолдинга [$unique] не зарегистрирован.");
        return $folding;
    }

    /**
     * Метод получает фолдинг по типу и подтипу фолдинга [ps, lib + p ...]
     * 
     * @param string $type - тип фолдинга
     * @param string $subtype - подтип фолдинга
     * @param bool $assert - проверять ли наличие фолдинга или возвращать null
     * @return FoldedResources
     */
    public static function byTypeStype($type, $subtype = null, $assert = true) {
        return self::byUnique(FoldedResources::unique($type, $subtype), $assert);
    }

    /**
     * Функция возвращает префикс для классов данного фолдинга, например IP_
     *
     * @param string $classPrefix - префикс классов
     * @param bool $assert - признак существования
     */
    public static function byClassPrefix($classPrefix, $assert = true) {
        return self::byUnique(FoldedStorage::getFoldingByClassPrefix($classPrefix, $assert), $assert);
    }

    /**
     * Проверка существования префикса класса
     * @param string $classPrefix - префикс класса фолдингов [PLIB_]
     */
    public static function existsClassPrefix($classPrefix) {
        return FoldedStorage::existsClassPrefix($classPrefix) && self::existsFolding(FoldedStorage::getFoldingByClassPrefix($classPrefix));
    }

    /**
     * Функция возвращает экземпляр фолдинга по префиксу его ресурсов, например ip, plib
     *
     * @param string $sourcePrefix - префикс ресурсов
     * @param bool $assert - признак существования
     */
    public static function bySourcePrefix($sourcePrefix, $assert = true) {
        return self::byUnique(FoldedStorage::getFoldingBySourcePrefix($sourcePrefix, $assert), $assert);
    }

    /**
     * Метод получает сущность фолдинга по её идентификатору: [lib-p-pushkin]
     * 
     * @param string $unique - уникальный код сущности фолдинга
     * @param bool $assert - проверить существование сущности
     * @return FoldedEntity
     */
    public static function getFoldedEntityByUnique($unique, $assert = true) {
        $parts = explode('-', trim($unique));
        $count = count($parts);
        if ($count < 2) {
            check_condition(!$assert, "Некорректный идентификатор сущности фолдинга: [$unique].");
            return null; //---
        }

        $type = $parts[0];
        $hasSubType = FoldedStorage::isFoldingHasSubtype($type, false);
        if ($hasSubType === null) {
            //Фолдинга с таким типом вообще не существует
            check_condition(!$assert, "Сущность фолдинга [$unique] не существует.");
            return null; //---
        }

        if ($hasSubType && ($count == 2)) {
            check_condition(!$assert, "Некорректный идентификатор сущности фолдинга: [$unique].");
            return null; //---
        }

        $subtype = $hasSubType ? $parts[1] : null;
        $folding = self::byTypeStype($type, $subtype, $assert);

        if (!$folding) {
            return null; //---
        }

        array_shift($parts);
        if ($hasSubType) {
            array_shift($parts);
        }

        //TODO '-' вынести на константы
        $ident = implode('-', $parts);

        return $folding->getFoldedEntity($ident, $assert);
    }

    /**
     * Регистрация страниц SDK
     */
    private function registerSdkFoldings() {
        $this->register(PopupPagesManager::inst());
        $this->register(PluginsManager::inst());
        $this->register(TimeLineManager::inst());
        $this->register(UserPointsManager::inst());
        $this->register(StockManager::inst());
        $this->register(HelpManager::inst());
        $this->register(EmailManager::inst());
        $this->register(PSForm::inst());
        $this->register(DialogManager::inst());
        $this->register(PageBuilder::inst());
        //Библиотеки
        $this->register(PoetsManager::inst());
        $this->register(ScientistsManager::inst());
        //Админские страницы
        $this->register(APagesResources::inst());
    }

    /**
     * Регистрация проектных фолдингов
     */
    protected function registerProjectFoldings() {
        
    }

    /**
     * Метод регистрации экземпляров фолдингов
     * 
     * @param FoldedResources $inst - экземпляр
     */
    protected final function register(FoldedResources $inst) {
        $unique = $inst->getUnique();
        if (array_key_exists($unique, $this->FOLDINGS)) {
            PsUtil::raise('Folding \'{}\' is already registered. Cannot register \'{}\' with same unique.', $this->FOLDINGS[$unique], $inst);
        } else {
            $this->FOLDINGS[$unique] = $inst;

            if ($this->LOGGER->isEnabled()) {
                $this->LOGGER->info('+{}. {}, count: {}.', pad_left(count($this->FOLDINGS), 3, ' '), $inst, FoldedStorage::getEntitiesCount($unique));
            }
        }
    }

    /** @var FoldedStorageInsts */
    private static $inst;

    /**
     * Метод возвращает экземпляр класса-хранилища экземпляров фолдинов.
     * Для переопределения этого класса, на уровне проектного config.ini
     * должен быть задан другой класс.
     * 
     * @return FoldedStorageInsts
     */
    protected static final function inst() {
        if (isset(self::$inst)) {
            return self::$inst; //----
        }

        /*
         * Получим название класса
         */
        $class = FoldingsIni::foldingsStore();

        /*
         * Класс совпадает с базовым
         */
        if (__CLASS__ == $class) {
            return self::$inst = new FoldedStorageInsts();
        }

        /*
         * Нам передан класс, который отличается от SDK
         */
        $classPath = Autoload::inst()->getClassPath($class);
        if (!PsCheck::isNotEmptyString($classPath)) {
            return PsUtil::raise('Не удалось найти класс регистрации экземпляров фолдингов [{}]', $class);
        }

        /*
         * Указанный класс должен быть наследником данного
         */
        if (!PsUtil::isInstanceOf($class, __CLASS__)) {
            return PsUtil::raise('Указанный класс регистрации экземпляров фолдингов [{}] не является наследником класса [{}]', $class, __CLASS__);
        }

        return self::$inst = new $class();
    }

    /**
     * В конструкторе зарегистрируем все страницы
     */
    protected final function __construct() {
        //Инициализируем хранилище, чтобы честно замерять время создания регистрации самих экземпляров
        FoldedStorage::init();

        $class = get_called_class();
        $basic = __CLASS__ == $class;

        //Логгер
        $this->LOGGER = PsLogger::inst(__CLASS__);
        $this->LOGGER->info('USING {} STORAGE: {}', $basic ? 'SDK' : 'CUSTOM', $class);

        //Стартуем профайлер
        $this->PROFILER = PsProfiler::inst(__CLASS__);
        $this->PROFILER->start('Loading folding insts');

        //Регистрируем фолдинги SDK
        $this->LOGGER->info();
        $this->LOGGER->info('FOLDINGS SDK:');
        $this->registerSdkFoldings();

        //Если используем не SDK провайдер, вызываем регистратор
        if (!$basic) {
            $this->LOGGER->info();
            $this->LOGGER->info('FOLDINGS PROJECT:');
            $this->registerProjectFoldings();
        }

        //Отсортируем фолдинги по идентификаторам
        ksort($this->FOLDINGS);

        //Останавливаем профайлер
        $sec = $this->PROFILER->stop();

        //Логируем
        $this->LOGGER->info();
        $this->LOGGER->info('COLLECTING TIME: {} sec', $sec->getTotalTime());
    }

}

?>