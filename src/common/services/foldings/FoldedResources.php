<?php

/**
 * Базовый класс для всех фолдингов
 */
abstract class FoldedResources extends AbstractSingleton {

    const RTYPE_JS = 'js';
    const RTYPE_CSS = 'css';
    const RTYPE_PCSS = 'print_css';
    const RTYPE_PHP = 'php';
    const RTYPE_TPL = 'tpl';
    const RTYPE_TXT = 'txt';
    //Директория с информациооными шаблонами
    const INFO_PATTERNS = 'tpl';
    //Название шаблона
    const PATTERN_NAME = '!PATTERN';

    //Уникальный идентификатор фолдинга. "Собирается" из типа и подтипа фолдинга.
    private $UNIQUE;

    /** Класс */
    protected $CLASS;

    /** @var PsLoggerInterface */
    protected $LOGGER;

    /** @var PsProfilerInterface */
    protected $PROFILER;

    /** Идентификаторы */
    private $IDENTS;

    /** Название таблицы, хранящей сущности фолдинга */
    private $TABLE;

    /** Название вьюхи, хранящей видимые сущности фолдинга */
    private $TABLE_VIEW;

    /** Название столбца в таблице, хранящей идентификатор фолдинга */
    private $TABLE_COLUMN_IDENT;

    /** Название столбца в таблице, хранящей подтип фолдинга. Считаем, что все фолдинги одного типа хранятся в одной таблице. */
    private $TABLE_COLUMN_STYPE;

    /** Префикс для Smarty-функций, чтобы можно было мгновенно находить фолдинг. */
    private $SMARTY_PREFIX;

    /** Префикс для классов, например IP_. Если забора с типом ресурсов - PHP не ведётся, то - null */
    private $CLASS_PREFIX;

    /** текстовое описание фолдинга */
    private $TO_STRING;

    /** Ремап типа на расширение */
    private static $TYPE2EXT = array(self::RTYPE_PCSS => 'print.css');

    /** Подключаемые типы ресурсов */
    private $RESOURCE_TYPES_LINKED = array(self::RTYPE_JS, self::RTYPE_CSS, self::RTYPE_PCSS);

    /** Типы ресурсов, по которым будет происходить проверка - изменилась ли сущность */
    private $RESOURCE_TYPES_CHECK_CHANGE = array(self::RTYPE_TPL, self::RTYPE_PHP, self::RTYPE_TXT);

    /** Допустимые типы ресурсов */
    protected $RESOURCE_TYPES_ALLOWED = array(self::RTYPE_JS, self::RTYPE_CSS, self::RTYPE_PCSS, self::RTYPE_PHP, self::RTYPE_TPL);

    //Тип foldings (pp, pl, post...)
    public abstract function getFoldingType();

    //Подтип foldings (null, is, tr...)
    public abstract function getFoldingSubType();

    //Название сущности
    public abstract function getEntityName();

    //Метод возвращает идентификатор для вновь создаваемой записи фолдинга. Используется для первичного наполнения форм и т.д.
    public function getNextEntityIdent() {
        $name = $this->getFoldingType() . trim($this->getFoldingSubType()) . 'new';
        $ident = $name;
        for ($idx = 0; $this->existsEntity($ident); $idx++) {
            $ident = $name . $idx;
        }
        return $ident;
    }

    /**
     * Функция возвращает префикс для смарти-функций, соответствующий данному фолдингу.
     * Например функция trpostimg - сразу указывает на фолдинг post-tr.
     * Данный префикс являет собой просто слияние подтипа + типа фолдинга.
     */
    public final function getSmartyPrefix() {
        return $this->SMARTY_PREFIX;
    }

    /**
     * Функция возвращает префикс для классов данного фолдинга, например IP_
     */
    public function getClassPrefix() {
        return $this->CLASS_PREFIX;
    }

    //Название класса по его идентификатору (calculator->PP_calculator)
    protected function ident2className($ident) {
        return $this->CLASS_PREFIX . $ident;
    }

    //Название идентификатор по названию класса (PP_calculator -> calculator)
    private function className2ident($className) {
        return cut_string_start($className, $this->CLASS_PREFIX);
    }

    //Метод проверяет, может ли переданная последовательность служить префиксом класса.
    //Она должна сосотоять из больших букв и заканчиваться подчёркиванием, например: PL_
    public static function isValidClassPrefix($prefix) {
        return $prefix && preg_match('/^[A-Z]+\_/', $prefix, $matches) == 1 && $matches[0] === $prefix;
    }

    //Метод извлекат префикс из имени класса
    public static function extractPrefixFromClass($className) {
        $tokens = explode('_', trim($className), 3);
        $prefix = count($tokens) == 2 ? $tokens[0] . '_' : null;
        return self::isValidClassPrefix($prefix) ? $prefix : null;
    }

    //Метод извлекат идентификатор сущности из имени класса. Конечно мы проверим, валиден ли префикс класса
    public static function extractIdentFormClass($className) {
        $tokens = explode('_', trim($className), 3);
        $prefix = count($tokens) == 2 ? $tokens[0] . '_' : null;
        return self::isValidClassPrefix($prefix) && !!$tokens[1] ? $tokens[1] : null;
    }

    //Метод переводит тип ресурса в расширение файла для ресурса
    public static function resourceTypeToExt($type) {
        return array_get_value($type, self::$TYPE2EXT, $type);
    }

    //Предпросмотр сущности фолдинга при редактировании
    public function getFoldedEntityPreview($ident) {
        return array('info' => '', 'content' => '');
    }

    /**
     * Методы различных проверок
     */
    public function isIt($type, $subtype = null) {
        return $this->isItByType($type) && (!$this->getFoldingSubType() || $this->getFoldingSubType() == $subtype);
    }

    //Проверяет, относится ли фолдинг к данному типу
    public function isItByType($type) {
        return $type === $this->getFoldingType();
    }

    //Работает ли фолдинг с подтипами фолдингов
    public function hasSubType() {
        return !!$this->getFoldingSubType();
    }

    //Уникальный идентификатор фолдинга, либо сущности внутри фолдинга (если передана)
    public static function unique($type, $subtype = null, $ident = null) {
        return $type . ($subtype ? '-' . $subtype : '') . ($ident ? '-' . $ident : '');
    }

    //Уникальный идентификатор фолдинга, либо сущности внутри фолдинга (если передана)
    public function getUnique($ident = null) {
        return $ident ? $this->UNIQUE . '-' . $ident : $this->UNIQUE;
    }

    private static function smartyPrefix($type, $subtype = null) {
        return trim($subtype) . $type;
    }

    //Текстовое описание фолдинга или сущности фолдинга
    public final function getTextDescr($ident = null) {
        return $this->CLASS . "[{$this->getUnique($ident)}] ({$this->getEntityName()})";
    }

    /*
     * **************************
     *       ПРОФИЛИРОВАНИЕ
     * **************************
     */

    protected final function profilerStart($__FUNCTION__) {
        PsProfiler::inst('Folding')->start($this->CLASS . "[$this->UNIQUE]->" . $__FUNCTION__);
    }

    protected final function profilerStop($save = true) {
        PsProfiler::inst('Folding')->stop($save);
    }

    /*
     * **************************
     *            КЕШИ
     * **************************
     */

    //Константы - префиксы служебных кешей

    const CACHE_CHANGE_PROCESS = 'CHANGE_PROCESSED';
    const CACHE_INFO_TPL = 'TPL_';
    const CACHE_TXT_PARAMS = 'TXTPARAMS';
    const CACHE_DEPEND_ENTS = 'DEPEND_ENTS';

    /**
     * В качестве подписи кешей сущности фолдинга используется время самого последнего 
     * изменённого файла из тех ресурсов, которые включены в RESOURCE_TYPES_CHECK_CHANGE.
     */
    private $OLDEST = array();

    private function getOldestResourceFile($ident) {
        if (array_key_exists($ident, $this->OLDEST)) {
            return $this->OLDEST[$ident];
        }

        $this->assertExistsEntity($ident);
        $this->LOGGER->info("Loading oldest resource file for entity [$ident].");

        $this->PROFILER->start(__FUNCTION__);

        //Строим полный список сущностей, которые будут проверены на дату последнего изменения
        $items[] = array();
        foreach ($this->RESOURCE_TYPES_CHECK_CHANGE as $type) {
            $items[] = $this->getResourceDi($ident, $type);
        }
        //Включим в список преверяемых сущностей все информационные шаблоны
        $items[] = $this->getInfoDiList($ident);
        //Если мы работаем с обложками - проверим и их
        if ($this->isImagesFactoryEnabled()) {
            $items[] = $this->getCoverOriginal($ident);
        }

        $oldest = null;
        foreach ($items as $item) {
            /** @var $di DirItem */
            foreach (to_array($item) as $di) {
                $time = $di->getModificationTime();
                if ($time && (!$oldest || $time > $oldest)) {
                    $oldest = $time;
                    $this->LOGGER->info("Resource file [{$di->getRelPath()}] mtime: $time, oldest: $oldest.");
                } else {
                    $this->LOGGER->info("Resource file [{$di->getRelPath()}] mtime: $time.");
                }
            }
        }

        $this->PROFILER->stop();

        return $this->OLDEST[$ident] = $oldest;
    }

    /** Кеш группы фолдинга. Всегда одинаков для сущности, чтобы мы могли зачистить все кеши по данному фолдингу */
    private function cacheGroup($ident) {
        return 'FOLDING-' . $this->getUnique($ident);
    }

    /**
     * Метод проверяет, можно ли использовать кеш.
     * 
     * @param string $ident - идентификатор сущности. Если она не видна, то кеш использовать нельзя.
     * @param string $cacheId - ключ кеширования. Если он начинается на префикс, зарегистрированный в данном классе, то кеш можно использовать (служебный кеш).
     * @return bool
     */
    private function isCanUseCache($ident = null, $cacheId = null) {
        if ($ident && !$this->existsEntity($ident)) {
            //Сущность не видна
            return false;
        }
        if ($this instanceof StorableFolding) {
            return true;
        }
        if (!!$cacheId && starts_with($cacheId, PsUtil::getClassConsts(__CLASS__, 'CACHE_'))) {
            return true;
        }
        return false;
    }

    /**
     * Метод проверяет, может ли данный класс в принципе использовать кеши.
     */
    private function assertClassCanUseCache($cacheId = null) {
        check_condition($this->isCanUseCache(null, $cacheId), "Фолдинг $this не может использовать кеши. Информация по ключу [$cacheId] не будет сохранена.");
    }

    /**
     * Загрузка из кеша
     */
    private function getFromFoldedCache($ident, $cacheId, $checkKeys = null) {
        $this->assertExistsEntity($ident);
        $this->assertClassCanUseCache($cacheId);

        if (!$this->isCanUseCache($ident, $cacheId)) {
            return null; //Для данной сущности нельзя использовать кеши
        }

        /*
         * Хоть мы и подписываем все кеши датой модификации самого старого файла, тем не менее 
         * будем выполнять checkEntityChanged, так как в случае изменения сущности нужно обновить 
         * и всё остальное - сбросить спрайты и т.д.
         */
        $sign = $this->getOldestResourceFile($ident);
        $groupId = $this->cacheGroup($ident);
        return PSCache::inst()->getFromCache($cacheId, $groupId, $checkKeys, $sign);
    }

    /**
     * Сохранение в кеш
     */
    private function saveToFoldedCache($object, $ident, $cacheId) {
        $this->assertExistsEntity($ident);
        $this->assertClassCanUseCache($cacheId);

        if (!$this->isCanUseCache($ident, $cacheId)) {
            return $object; //Для данной сущности нельзя использовать кеши
        }

        $sign = $this->getOldestResourceFile($ident);
        $groupId = $this->cacheGroup($ident);
        return PSCache::inst()->saveToCache($object, $cacheId, $groupId, $sign);
    }

    /**
     * Очистка кеша
     */
    private function cleanFoldedCache($ident) {
        if ($this->isCanUseCache($ident)) {
            PSCache::inst()->cleanCache($this->cacheGroup($ident));
        }
    }

    /*
     * == ПАРАМЕТРЫ ЦЕПОЧЕК ЗАВИСИМОСТЕЙ ==
     */

    /**
     * Проверим, не был ли какой-нибудь из файлов ресурсов изменён.
     * Если был, то нужно выполнить действия после изменения сущности.
     */
    private $CHANGED_ENTITYS = array();

    //Метод вызывается, как только обнаруживается, что сущность изменилась
    public function onEntityChanged($ident) {
        if (!$this->existsEntity($ident)) {
            return; //Сущность пока не видна пользователям, для неё не обрабатываем событие изменения
        }

        if (in_array($ident, $this->CHANGED_ENTITYS)) {
            return; //---
        }
        $this->CHANGED_ENTITYS[] = $ident;

        $this->LOGGER->info("Entity [$ident] is changed");
        FoldedResourcesManager::onEntityAction(FoldedResourcesManager::ACTION_ENTITY_CHANGED, $this, $ident);

        $this->cleanFoldedCache($ident);
        $this->getAutogenDm($ident)->clearDir();
        $this->rebuildSprite($ident);

        unset($this->OLDEST[$ident]);
        unset($this->FETCH_RETURNS[$ident]);

        $this->onEntityChangedImpl($ident);

        //Именно здесь ставим маркер обработанного изменения, так как до этого мы почистили кэши
        //$this->saveToFoldedCache(true, $ident, self::CACHE_CHANGE_PROCESS);
    }

    protected abstract function onEntityChangedImpl($ident);

    /**
     * Возвращает полный список сущностей, не проверяя права доступа
     */
    public final function getAllIdents($includePattern = false) {
        if (!is_array($this->IDENTS)) {
            $all = array_keys(FoldedStorage::getEntities($this->UNIQUE));
            $this->IDENTS['full'] = $all;
            $this->IDENTS['short'] = array_values(array_remove_value($all, self::PATTERN_NAME));
        }
        return $this->IDENTS[$includePattern ? 'full' : 'short'];
    }

    /**
     * Метод проверяет существование директории для сущности фолдинга.
     */
    public function existsEntity($ident) {
        return FoldedStorage::existsEntity($this->UNIQUE, $ident);
    }

    //Сущность существует, но не обязательно видима
    public function assertExistsEntity($ident) {
        check_condition($this->existsEntity($ident), "Элемент {$this->getTextDescr($ident)} не существует.");
        return $ident;
    }

    //Проверяет, что сущность не существует
    public function assertNotExistsEntity($ident) {
        check_condition(!$this->existsEntity($ident), "Элемент {$this->getTextDescr($ident)} уже существует.");
    }

    /**
     * Метод проверяет, имеет ли текущий авторизованный пользователь доступ к сущности фолдинга.
     * Админ может иметь доступ к существующим невидимым сущностям, например если он её только создал, но не доабвил запись в базу.
     */
    public function hasAccess($ident, $checkClassInstAccess = false) {
        if (!$this->existsEntity($ident)) {
            return false;
        }
        if ($checkClassInstAccess) {
            return $this->isAllowedResourceType(self::RTYPE_PHP) && $this->getEntityClassInst($ident)->isUserHasAccess();
        }
        return true;
    }

    /**
     * Метод возвращает сущность фолдинга
     * 
     * @return FoldedEntity
     */
    public function getFoldedEntity($ident, $assert = false) {
        return $this->hasAccess($ident) ? FoldedEntity::inst($this, $ident) : ($assert ? PsUtil::raise("Сущность фолдинга [{}] не существует.", $this->getUnique($ident)) : null);
    }

    /**
     * Метод возвращает все сущности фолдинга
     * @return array
     */
    public function getFoldedEntitys($includePattern = false) {
        $result = array();
        foreach ($this->getAllIdents($includePattern) as $ident) {
            $result[$ident] = $this->getFoldedEntity($ident);
        }
        return $result;
    }

    public final function getAllowedResourceTypes() {
        return $this->RESOURCE_TYPES_ALLOWED;
    }

    public final function isAllowedResourceType($type) {
        return in_array($type, $this->RESOURCE_TYPES_ALLOWED);
    }

    public function assertAllowedResourceType($type) {
        check_condition($this->isAllowedResourceType($type), "Тип ресурса [$type] не может быть запрошен для сущностей типа {$this->getTextDescr()}");
        return $type; //---
    }

    /**
     * Создание экземпляра класса для сущности фолдинга.
     * Если по каким-либо причинам экземпляр не может быть создан - выбрасываем ошибку.
     * 
     * @return FoldedClass
     */
    public final function getEntityClassInst($ident, $cache = true) {
        /* @var $CACHE SimpleDataCache */
        $CACHE = $cache ? SimpleDataCache::inst($this->unique('CLASSES-CACHE')) : null; //---

        if ($CACHE && $CACHE->has($ident)) {
            return $CACHE->get($ident);
        }

        $classPath = $this->getClassPath($ident);

        //Подключим класс, не будем заставлять трудиться класслоадер
        require_once $classPath;

        //Построим название класса на основе идентификатора сущности
        $className = $this->ident2className($ident);
        if (!PsUtil::isInstanceOf($className, FoldedClass::getCalledClass())) {
            return PsUtil::raise('Класс для сущности {} не является наследником {}', $this->getTextDescr($ident), FoldedClass::getCalledClass());
        }

        //Получим FoldedEntity, так как её потом нужно будет передать в конструктор
        $foldedEntity = $this->getFoldedEntity($ident);

        //Создаём экземпляр
        $inst = new $className($foldedEntity);

        //Отлогируем
        $this->LOGGER->info('Instance of {} created.', $className);
        FoldedResourcesManager::onEntityAction(FoldedResourcesManager::ACTION_ENTITY_INST_CREATED, $this, $ident);

        return $CACHE ? $CACHE->set($ident, $inst) : $CACHE;
    }

    /**
     * Метод загружает все экземпляры классов
     * 
     * @param array $idents - ограниченный список сущностей, если нет - ищем среди всех
     * @param bool $checkClassAccess - нужно ли при загрузке проверить доступ к сущности
     * @param bool $cacheInst - кешировать ли созданные экземпляры
     * @return array - карта идентификатор->экземпляр
     */
    public final function getEntityClassInsts(array $idents = null, $checkClassAccess = true, $skipNotExisted = true, $cacheInst = true) {
        $idents = is_array($idents) ? array_unique($idents) : $this->getAllIdents();
        $insts = array();
        foreach ($idents as $ident) {
            if ($this->existsEntity($ident)) {
                $inst = $this->getEntityClassInst($ident, $cacheInst);
                if (!$checkClassAccess || $inst->isUserHasAccess()) {
                    $insts[$ident] = $inst;
                }
            } else {
                check_condition($skipNotExisted, "Элемент {$this->getTextDescr($ident)} не существует.");
            }
        }
        return $insts;
    }

    /** @return DirManager */
    public function getResourcesDm($ident, $subDir = null) {
        return DirManager::inst(FoldedStorage::getEntityChild($this->UNIQUE, $ident, $subDir));
    }

    /** @return DirItem */
    public function getResourceDi($ident, $type) {
        return $this->getResourcesDm($ident)->getDirItem(null, $ident, self::resourceTypeToExt($this->assertAllowedResourceType($type)));
    }

    /** @return DirItem */
    public function getTplDi($ident) {
        return $this->getResourceDi($ident, self::RTYPE_TPL);
    }

    /** @return DirManager */
    public function getAutogenDm($ident, $subDir = null) {
        return DirManager::autogen(array('folded', $this->getUnique($this->assertExistsEntity($ident)), $subDir));
    }

    /** @return DirItem */
    public function getAutogenDi($ident, $makeDirs = null, $notMakeDirs = null, $file = null, $ext = null) {
        return $this->getAutogenDm($ident, $makeDirs)->getDirItem($notMakeDirs, $file, $ext);
    }

    /**
     * Метод возвращает менеджера информационной директории
     * @return DirManager
     */
    private function getInfoDm($ident) {
        return $this->getResourcesDm($ident, self::INFO_PATTERNS);
    }

    /**
     * Метод возвращает шаблон из информационной директории
     * @return DirItem
     */
    private function getInfoDi($ident, $tplPath) {
        return $this->getInfoDm($ident)->getDirItem(null, $tplPath, 'tpl');
    }

    /**
     * Метод возвращает список всех информационных шаблонов
     */
    public function getInfoDiList($ident) {
        return $this->getInfoDm($ident)->getDirContentFull(null, PsConst::EXT_TPL);
    }

    /** @return Smarty_Internal_Template */
    private function getTpl($ident, $smartyParams = null) {
        return PSSmarty::template($this->getResourceDi($ident, self::RTYPE_TPL), $smartyParams);
    }

    /*
     * ИНФОРМАЦИЯ О ФОЛДИНГЕ, ХРАНИМАЯ В ШАБЛОНАХ
     * 
     * Информационные шаблоны хранятся в папке tpl, рядом с ресурсами фолдинга.
     */

    /** @return FoldedInfoTpl */
    public function getInfoTpl($ident, $tplPath) {
        return FoldedInfoTpl::inst($this->getFoldedEntity($ident, true), $this->getInfoDi($ident, $tplPath));
    }

    private $INFO_TEMPLATES_LISTS = array();

    public function getInfoTpls($ident, $tplDir = null) {
        $key = unique_from_path($ident, $tplDir);
        if (!array_key_exists($key, $this->INFO_TEMPLATES_LISTS)) {
            $this->INFO_TEMPLATES_LISTS[$key] = array();
            $entity = $this->getFoldedEntity($ident, true);
            foreach ($this->getInfoDm($ident)->getDirContent($tplDir, PsConst::EXT_TPL) as $tplDi) {
                $this->INFO_TEMPLATES_LISTS[$key][] = FoldedInfoTpl::inst($entity, $tplDi);
            }
        }
        return $this->INFO_TEMPLATES_LISTS[$key];
    }

    private $ALL_INFO_TEMPLATES_LISTS;

    public function getAllInfoTpls($ident) {
        if (!is_array($this->ALL_INFO_TEMPLATES_LISTS)) {
            $this->ALL_INFO_TEMPLATES_LISTS = array();
            $entity = $this->getFoldedEntity($ident, true);
            /* @var $tplDi DirItem */
            foreach ($this->getInfoDm($ident)->getDirContentFull(null, PsConst::EXT_TPL) as $tplDi) {
                $this->ALL_INFO_TEMPLATES_LISTS[] = FoldedInfoTpl::inst($entity, $tplDi);
            }
        }
        return $this->ALL_INFO_TEMPLATES_LISTS;
    }

    /**
     * Метод возвращат путь относительно директории информационных шаблонов данного фолдинга:
     * /resources/folded/stocks/mosaic/tpl/stock1.tpl -> /stock1.tpl
     * 
     * Пример вызова:
     * StockManager::inst()->getInfoTplRelPath('/resources/folded/stocks/mosaic/tpl/stock1.tpl');
     */
    public function getInfoTplRelPath($infoTpl) {
        $infoTpl = $infoTpl instanceof DirItem ? $infoTpl->getRelPath() : $infoTpl;
        $infoTpl = $infoTpl instanceof FoldedInfoTpl ? $infoTpl->getDirItem()->getRelPath() : $infoTpl;

        $rel2foldDm = cut_string_start($infoTpl, $this->getResourcesDm()->relDirPath());

        check_condition($rel2foldDm != $infoTpl, "Путь [$infoTpl] не принадлежит фолдингу $this.");

        $ident = array_get_value(0, explode(DIR_SEPARATOR, $rel2foldDm));

        check_condition($this->existsEntity($ident), "Не удалось определить сущность фолдинга для информационного шаблона [$infoTpl].");

        $rel2infoDm = cut_string_start($infoTpl, $this->getInfoDm($ident)->relDirPath());

        check_condition($rel2infoDm != $infoTpl, "Путь [$infoTpl] не является путём к информационному шаблону.");

        return ensure_dir_startswith_dir_separator($rel2infoDm);
    }

    /**
     * Метод возвращает информацию из информационного шаблона, производя его фетчинг
     * с переданными параметрами Smarty.
     */
    public function getInfo($ident, $tpl, array $smartyParams = array()) {
        //Информационный шаблон
        $tpl = $tpl instanceof DirItem ? $tpl : $this->getInfoDi($ident, $tpl);
        //Если шаблон не сущетвует - просто пропускаем его, и не будем ругаться
        if (!$tpl->isFile()) {
            return null; //---
        }
        //Ключом являются параметры Смарти, с которыми мы фетчим информационный шаблон
        $cacheKey = simple_hash($smartyParams);
        //Идентификатором кеша является путь к файлу шаблона
        $cacheId = self::CACHE_INFO_TPL . md5($tpl->getRelPath());
        //Пробуем загрузить закешированный 
        $cached = to_array($this->getFromFoldedCache($ident, $cacheId, array()));

        if (!array_key_exists($cacheKey, $cached)) {
            $cached[$cacheKey] = $this->getInfoTplCtt($ident, $tpl, $smartyParams);
            $this->saveToFoldedCache($cached, $ident, $cacheId);
        }

        return $cached[$cacheKey];
    }

    /**
     * Фетчинг информационного шаблона без его кеширования
     */
    public function getInfoTplCtt($ident, $tpl, array $smartyParams = array()) {
        $tpl = $tpl instanceof DirItem ? $tpl : $this->getInfoDi($ident, $tpl);
        FoldedInfoTplContext::getInstance()->setContextWithFoldedEntity($this->getFoldedEntity($ident, true));
        $content = trim(ContentHelper::getContent(PSSmarty::template($tpl, $smartyParams)));
        FoldedInfoTplContext::getInstance()->dropContext();
        return $content;
    }

    /**
     * Различные "временные" данные для сущности
     */

    /**
     * Метод подключает ресурсы к сущности фолдинга.
     * Всегда подключаем все ресурсы, ненужные будут выкинуты в процессе финализации страницы.
     */
    public function getResourcesLinks($ident, $content = null) {
        $this->LOGGER->info("Getting resource links for entity [$ident].");

        $tokens = array();
        foreach ($this->RESOURCE_TYPES_LINKED as $type) {
            $di = $this->getResourceDi($ident, $type);
            if ($di->isFile()) {
                switch ($type) {
                    case self::RTYPE_JS:
                        $tokens[] = PsHtml::linkJs($di);
                        break;
                    case self::RTYPE_CSS:
                        $tokens[] = PsHtml::linkCss($di);
                        break;
                    case self::RTYPE_PCSS:
                        $tokens[] = PsHtml::linkCss($di, 'print');
                        break;
                }
            }
        }
        //Приаттачим спрайты
        $sprite = $this->getSprite($ident);
        $tokens[] = $sprite ? PsHtml::linkCss($sprite->getCssDi()) : '';

        //Контент - после ресурсов
        $tokens[] = $content;
        return concat($tokens);
    }

    /**
     * Фетчинг шаблона и добавление к нему ресурсов
     */
    public function fetchTplWithResources($ident, array $smParams = null, $returnType = self::FETCH_RETURN_CONTENT) {
        return $this->fetchTplImpl($ident, $smParams, $returnType, true);
    }

    const FETCH_RETURN_FULL = 'full';
    const FETCH_RETURN_CONTENT = 'content';
    const FETCH_RETURN_PARAMS = 'params';
    const FETCH_RETURN_FULL_OB = 'full_ob';
    const FETCH_RETURN_PARAMS_OB = 'params_ob';

    private $FETCH_RETURNS = array();
    private static $FETCH_REQUEST_CNT = 0;

    public function fetchTplImpl($ident, array $smParams = null, $returnType = self::FETCH_RETURN_CONTENT, $addResources = false, $cacheId = null) {
        $logMsg = null;

        if ($this->LOGGER->isEnabled()) {
            $rqNum = ++self::$FETCH_REQUEST_CNT;
            $logMsg = "#$rqNum Smarty params count: " . count(to_array($smParams)) . ", type: $returnType, resources: " . var_export($addResources, true) . ", " . ($cacheId ? "cache id: [$cacheId]" : 'nocache');
            $this->LOGGER->info("Tpl fetching requested for entity [$ident]. $logMsg");
            FoldedResourcesManager::onEntityAction(FoldedResourcesManager::ACTION_ENTITY_FETCH_REQUESTD, $this, $ident, $logMsg);
        }

        $entity = $this->getFoldedEntity($ident);

        $CTXT = $this->getFoldedContext();

        $PCLASS = $CTXT->tplFetchParamsClass();
        $PCLASS_BASE = FoldedTplFetchPrams::getClassName();

        check_condition(PsUtil::isInstanceOf($PCLASS, $PCLASS_BASE), "Класс [$PCLASS] для хранения данных контекста $CTXT должен быть подклассом $PCLASS_BASE");

        //Если мы не возвращаем содержимое, то в любом случае ресурсы добавлять не к чему
        $addResources = $addResources && !in_array($returnType, array(self::FETCH_RETURN_PARAMS, self::FETCH_RETURN_PARAMS_OB));

        $keysRequired = PsUtil::getClassConsts($PCLASS, 'PARAM_');
        $keysRequiredParams = array_diff($keysRequired, array(FoldedTplFetchPrams::PARAM_CONTENT));

        $PARAMS = null;
        $PARAMS_KEY = null;

        $CONTENT = null;
        $CONTENT_KEY = null;

        $RETURN_KEY = null;

        if ($cacheId) {
            $cacheId = ensure_wrapped_with($cacheId, '[', ']') . '[' . PsDefines::getReplaceFormulesType() . ']';
            $RETURN_KEY = $cacheId . '-' . $returnType;

            if (array_key_exists($ident, $this->FETCH_RETURNS)) {
                if (array_key_exists($RETURN_KEY, $this->FETCH_RETURNS[$ident])) {
                    return $this->FETCH_RETURNS[$ident][$RETURN_KEY];
                }
            } else {
                $this->FETCH_RETURNS[$ident] = array();
            }

            $PARAMS_KEY = empty($keysRequiredParams) ? null : $cacheId . '-params';
            $CONTENT_KEY = $cacheId . '-content';

            switch ($returnType) {
                case self::FETCH_RETURN_FULL:
                case self::FETCH_RETURN_FULL_OB:
                    $CONTENT = $this->getFromFoldedCache($ident, $CONTENT_KEY);
                    $PARAMS = $PARAMS_KEY ? $this->getFromFoldedCache($ident, $PARAMS_KEY, $keysRequiredParams) : array();
                    if ($CONTENT && is_array($PARAMS)) {
                        $CONTENT = $addResources ? $this->getResourcesLinks($ident, $CONTENT) : $CONTENT;
                        $PARAMS[FoldedTplFetchPrams::PARAM_CONTENT] = $CONTENT;
                        switch ($returnType) {
                            case self::FETCH_RETURN_FULL:
                                return $this->FETCH_RETURNS[$ident][$RETURN_KEY] = $PARAMS;
                            case self::FETCH_RETURN_FULL_OB:
                                return $this->FETCH_RETURNS[$ident][$RETURN_KEY] = new $PCLASS($PARAMS);
                            default:
                                raise_error("Unprocessed fetch return type [$returnType].");
                        }
                    }
                    break;

                case self::FETCH_RETURN_CONTENT:
                    $CONTENT = $this->getFromFoldedCache($ident, $CONTENT_KEY);
                    if ($CONTENT) {
                        $CONTENT = $addResources ? $this->getResourcesLinks($ident, $CONTENT) : $CONTENT;
                        return $this->FETCH_RETURNS[$ident][$RETURN_KEY] = $CONTENT;
                    }
                    break;

                case self::FETCH_RETURN_PARAMS:
                case self::FETCH_RETURN_PARAMS_OB:
                    $PARAMS = $PARAMS_KEY ? $this->getFromFoldedCache($ident, $PARAMS_KEY, $keysRequiredParams) : array();
                    if (is_array($PARAMS)) {
                        switch ($returnType) {
                            case self::FETCH_RETURN_PARAMS:
                                return $this->FETCH_RETURNS[$ident][$RETURN_KEY] = $PARAMS;
                            case self::FETCH_RETURN_PARAMS_OB:
                                return $this->FETCH_RETURNS[$ident][$RETURN_KEY] = new $PCLASS($PARAMS);
                            default:
                                raise_error("Unprocessed fetch return type [$returnType].");
                        }
                    }
                    break;
            }
        }

        $settedNow = false;
        if (!$entity->equalTo(FoldedContextWatcher::getInstance()->getFoldedEntity())) {
            $CTXT->setContextWithFoldedEntity($entity);
            $settedNow = true;
        }

        try {
            $CONTENT = $this->getTpl($ident, $smParams)->fetch();

            $entityNow = FoldedContextWatcher::getInstance()->getFoldedEntity();
            check_condition($entity->equalTo($entityNow), "After tpl fetching folded entity [$entity] chenged to [$entityNow]");

            $PARAMS_FULL = $CTXT->finalizeTplContent($CONTENT);

            check_condition(is_array($PARAMS_FULL), "After [$entity] tpl finalisation not array is returned");
            $keysReturned = array_keys($PARAMS_FULL);

            if (count(array_diff($keysReturned, $keysRequired)) || count(array_diff($keysRequired, $keysReturned))) {
                raise_error("After [$entity] tpl finalisation required keys: " . array_to_string($keysRequired) . '], returned keys: [' . array_to_string($keysReturned) . ']');
            }

            if ($this->LOGGER->isEnabled()) {
                $this->LOGGER->info("Tpl fetching actually done for entity [$ident]. $logMsg");
                FoldedResourcesManager::onEntityAction(FoldedResourcesManager::ACTION_ENTITY_FETCH_DONE, $this, $ident, $logMsg);
            }
        } catch (Exception $e) {
            /*
             * Произошла ошибка!
             * 
             * Если мы устанавливали контенст и он не поменялся после завершения фетчинга (если поменялся, это ошибка), то нужно его обязательно завершить.
             * Если контекст был установлен во внешнем блоке, то этот блок должен позаботиться о сбросе контекста.
             * 
             * Далее от нас требуется только пробросить ошибку наверх.
             */
            if ($settedNow && ($entity->equalTo(FoldedContextWatcher::getInstance()->getFoldedEntity()))) {
                $CTXT->dropContext();
            }

            throw $e; //---
        }

        $CONTENT = $PARAMS_FULL[FoldedTplFetchPrams::PARAM_CONTENT];

        $PARAMS = $PARAMS_FULL;
        unset($PARAMS[FoldedTplFetchPrams::PARAM_CONTENT]);

        if ($PARAMS_KEY) {
            $this->saveToFoldedCache($PARAMS, $ident, $PARAMS_KEY);
        }

        if ($CONTENT_KEY) {
            $this->saveToFoldedCache($CONTENT, $ident, $CONTENT_KEY);
        }

        if ($settedNow) {
            $CTXT->dropContext();
        }

        if ($addResources) {
            $CONTENT = $this->getResourcesLinks($ident, $CONTENT);
            $PARAMS_FULL[FoldedTplFetchPrams::PARAM_CONTENT] = $CONTENT;
        }

        switch ($returnType) {
            case self::FETCH_RETURN_FULL:
                return $RETURN_KEY ? $this->FETCH_RETURNS[$ident][$RETURN_KEY] = $PARAMS_FULL : $PARAMS_FULL;
            case self::FETCH_RETURN_FULL_OB:
                return $RETURN_KEY ? $this->FETCH_RETURNS[$ident][$RETURN_KEY] = new $PCLASS($PARAMS_FULL) : new $PCLASS($PARAMS_FULL);
            case self::FETCH_RETURN_CONTENT:
                return $RETURN_KEY ? $this->FETCH_RETURNS[$ident][$RETURN_KEY] = $CONTENT : $CONTENT;
            case self::FETCH_RETURN_PARAMS:
                return $RETURN_KEY ? $this->FETCH_RETURNS[$ident][$RETURN_KEY] = $PARAMS : $PARAMS;
            case self::FETCH_RETURN_PARAMS_OB:
                return $RETURN_KEY ? $this->FETCH_RETURNS[$ident][$RETURN_KEY] = new $PCLASS($PARAMS) : new $PCLASS($PARAMS);
        }

        raise_error("Unknown fetch return type [$returnType].");
    }

    /** @return FoldedContext */
    protected function getFoldedContext() {
        return FoldedContext::getInstance();
    }

    /**
     * Метод возвращает путь к классу для сущности фолдинга
     * Если сущности не существует или мы не поддерживаем работу с классами - выдаём ошибку.
     */
    public function getClassPath($ident) {
        if (self::PATTERN_NAME === $ident) {
            return PsUtil::raise('Нельзя загрузить путь к классу для сущности {}', self::PATTERN_NAME);
        }
        $this->assertAllowedResourceType(self::RTYPE_PHP);

        return check_condition(FoldedStorage::tryGetEntityClassPath($this->CLASS_PREFIX . $ident), 'Не найден класс реализации для сущности ' . $this->getTextDescr($ident));
    }

    /*
     * COVERS
     */

    /**
     * Метод возвращает признак - работает ли данный фолдинг с картинками
     */
    public final function isImagesFactoryEnabled() {
        return $this instanceof ImagedFolding;
    }

    /**
     * Метод утверждает, что фолдинг работает с картинками
     */
    public final function assertImagesFactoryEnabled() {
        check_condition($this->isImagesFactoryEnabled(), "Фолдинг [$this] не работает с картинками");
    }

    /**
     * Метод возвращает путь к обложке для сущности
     * 
     * @return DirItem
     */
    private function getCoverOriginal($ident) {
        $this->assertImagesFactoryEnabled();
        return $this->getResourcesDm($ident)->getDirItem(null, $ident, SYSTEM_IMG_TYPE);
    }

    /**
     * Метод возвращает обложку сущности, приводя её размер к $dim.
     * 
     * @param type $ident - идентификатор сущности. Если передан null, то будет возвращена дефолтная обложка.
     * @param type $dim - размер в виде 3x4
     * @return DirItem
     */
    public function getCover($ident = null, $dim = null) {
        $this->assertImagesFactoryEnabled();

        //Определим размер
        $dim = $dim ? $dim : $this->defaultDim();

        //Мы ожидаем, что все обложки для фолдингов должны иметь расширение SYSTEM_IMG_TYPE
        $scrDi = $ident && $this->hasAccess($ident) ? $this->getCoverOriginal($ident) : null;

        //Передать сущность нужно именно в качестве первого параметра метода getDirItem,
        //чтобы не проверять доступ, так как дефолтная картинка должна быть доступна всегда
        $dfltDi = $this->getResourcesDm(self::PATTERN_NAME)->getDirItem(null, self::PATTERN_NAME, SYSTEM_IMG_TYPE);

        //Выполняем resize
        return $scrDi ? PsImgEditor::resize($scrDi, $dim, $dfltDi) : PsImgEditor::resize($dfltDi, $dim);
    }

    /*
     * SPRITES
     */

    /**
     * Признак - производится ли построение спрайтов для сущностей данного фолдинга.
     * Это происходит только в том случае, когда контекст данного волдинга наследует {@see SpritableContext}
     */
    public function isSpritable() {
        return $this->getFoldedContext() instanceof SpritableContext;
    }

    /**
     * Метод утверждает, что данный класс работает со спрайтами
     */
    protected function assertSpritable($ident) {
        check_condition($this->isSpritable(), "Работа со спрайтами для сущности {$this->getTextDescr($ident)} запрещена.");
        return $ident; //---
    }

    /** @return CssSprite */
    public function getSprite($ident) {
        return $this->isSpritable() ? CssSprite::inst($this->getFoldedEntity($ident)) : null;
    }

    private function rebuildSprite($ident) {
        if ($this->isSpritable()) {
            $this->getSprite($ident)->rebuild();
        }
    }

    /**
     * Название файла со спрайтами
     */
    public function getSpriteName($ident) {
        return $this->getUnique($this->assertSpritable($ident));
    }

    /**
     * Метод должен вернуть картинки для построения спрайта
     */
    public function getSpriteImages($ident) {
        return $this->getTplFormules($this->assertSpritable($ident));
    }

    /**
     * Метод извлекает все формулы из smarty-шаблона
     */
    public function getTplFormules($ident) {
        return TexImager::inst()->extractTexImages($this->getTplDi($ident)->getFileContents());
    }

    /*
     * МЕТОДЫ ДЛЯ РАБОТЫ С БАЗОЙ
     */

    /**
     * Метод возвращает название таблицы, в которой хранятся сущности фолдинга
     */
    public function getTableName() {
        return $this->TABLE;
    }

    /**
     * Метод возвращает название вьюхи, в которой хранятся видимые сущности фолдинга
     */
    public function getTableView() {
        return $this->TABLE_VIEW;
    }

    /**
     * Метод возвращает название столбца в таблице, хранящего идентификатор фолдинга
     */
    public function getTableColumnIdent() {
        return $this->TABLE_COLUMN_IDENT;
    }

    /**
     * Метод возвращает название столбца в таблице, хранящего подтип фолдинга
     */
    public function getTableColumnStype() {
        return $this->TABLE_COLUMN_STYPE;
    }

    /**
     * Метод возвращает признак - работает ли данный фолдинг с базой
     */
    public function isWorkWithTable() {
        return $this instanceof DatabasedFolding;
    }

    /**
     * Метод утверждает, что фолдинг работает с базой
     */
    public function assertWorkWithTable() {
        check_condition($this->isWorkWithTable(), "Фолдинг $this не работает с базой");
    }

    /**
     * Метод возвращает идентификаторы сущностей фолдинга из таблицы.
     * Для админа вернёт всё (из таблицы), для обычного пользователя - только видимые (из вью).
     */
    public function getAccessibleDbIdents() {
        return FoldingBean::inst()->getIdents($this, AuthManager::isAuthorizedAsAdmin());
    }

    /**
     * Метод возвращает идентификаторы сущностей фолдинга из таблицы.
     * Для админа вернёт всё (из таблицы), для обычного пользователя - только видимые (из вью).
     */
    public function getVisibleDbObjects($objectName) {
        return FoldingBean::inst()->getVisibleObjects($this, $objectName, $this->getAllIdents());
    }

    /**
     * Метод возвращает из базы код для сущности.
     * На некоторые сущности фолдингов можно ссылаться по коду, как, например, на шаблонные сообщения или причину выдачи очков.
     * Мы можем не обязывать классы следить за тем, чтобы они имели сквозную нумерацию, просто будем, при 
     * необходимости, генерировать этот код.
     */
    public function getEntiltyDbCode($ident) {
        return $this->getFoldedEntity($ident, true)->getDbCode();
    }

    /**
     * Метод получает сущность фолдинга по её коду и убеждается, что она принадлежит данному фолдингу
     * 
     * @return FoldedEntity
     */
    protected function getFoldedEntityByDbCode($code) {
        $entity = FoldedResourcesManager::inst()->getFoldedEntityByDbCode($code);
        check_condition($entity->getFolding() === $this, "Сущность $entity с кодом [$code] не принадлежит фолдингу $this");
        return $entity;
    }

    /**
     * Метод возвращает "сырую" запись БД для сущности фолдинга, которая может быть использована для:
     * 1. Понимания, есть ли запись в БД для данного фолдинга
     * 2. Наполнения формы создания записи в БД (для данной сущности фолдинга)
     * 
     * Иными словами, метод возвращает то, какой ДОЛЖНА БЫТЬ запись в базе для данного фолдинга.
     * По этим данным её можно попытаться извлечь или наполнить форму создания.
     */
    public function getDbRec4Entity($ident) {
        if (!$this->isWorkWithTable()) {
            return null;
        }
        $row = $this->dbRec4Entity($ident);
        return is_array($row) ? $row : null;
    }

    /**
     * Данный метод возвращает идентификатор фолдинга для записи из таблицы
     * 
     * @param array $rec - запись из БД
     * @param type $checkEntityExists - проверить, существует ли эта сущность фолдинга
     */
    public function getEntityIdent4DbRec(array $rec, $checkEntityExists) {
        if (!$this->isWorkWithTable()) {
            return null;
        }
        if ($this->TABLE_COLUMN_STYPE && array_key_exists($this->TABLE_COLUMN_STYPE, $rec) && ($rec[$this->TABLE_COLUMN_STYPE] !== $this->getFoldingSubType())) {
            return null;
        }
        $ident = array_get_value($this->TABLE_COLUMN_IDENT, $rec);
        return $ident && (!$checkEntityExists || $this->existsEntity($ident)) ? $ident : null;
    }

    /*
     * =====================
     * = РАБОТА С ПАНЕЛЯМИ =
     * =====================
     */

    /**
     * Метод включает панель данного фолдинга на страницу.
     * Для того, чтобы фолдинг мог добавлять панели на страницу, он должен
     * наследовать интерфейс PanelFolding.
     * 
     * При построении панели может вернуться и null, тогда панель не будет добавлена.
     * 
     * @return type
     */
    private $includedPanels = array();

    public final function includePanel($panelName) {
        if (array_key_exists($panelName, $this->includedPanels)) {
            return $this->includedPanels[$panelName];
        }

        check_condition($this instanceof PanelFolding, "Фолдинг $this не может работать с панелями");
        check_condition(in_array($panelName, PsUtil::getClassConsts($this, 'PANEL_')), "Панель [$panelName] не может быть предоставлена фолдингом $this");

        //Сразу отметим, что панель была запрошена, так как может возникнуть ошибка
        $this->includedPanels[$panelName] = '';

        /*
         * Уникальный код панели - тот самый, через который потом можно будет 
         * достучаться до параметров панели из javascript.
         */
        $panelUnique = $this->getUnique($panelName);

        //Стартуем профайлер
        $this->profilerStart(__FUNCTION__ . "($panelName)");

        /** @var PluggablePanel */
        $panel = $this->buildPanel($panelName);

        //Мог вернуться и null, тогда ничего не подключаем
        if ($panel == null) {
            //Останавливаем профайлер без сохранения
            $this->profilerStop(false);
            return '';
        }

        //Останавливаем профайлер
        $this->profilerStop();

        check_condition($panel instanceof PluggablePanel, "Возвращена некорректная панель $panelUnique. Ожидался обект типа PluggablePanel, получен: " . PsUtil::getClassName($panel));

        //Html content
        $this->includedPanels[$panelName] = trim($panel->getHtml());

        //Js params
        $jsParams = $panel->getJsParams();
        if (!isTotallyEmpty($jsParams)) {
            PageBuilderContext::getInstance()->setJsParamsGroup(PsConstJs::PAGE_JS_GROUP_PANELS, $panelUnique, $jsParams);
        }

        //Smarty resources params
        $smartyParams4Resources = $panel->getSmartyParams4Resources();
        if (is_array($smartyParams4Resources) && !empty($smartyParams4Resources)) {
            PageBuilderContext::getInstance()->setSmartyParams4Resources($smartyParams4Resources);
        }

        return $this->includedPanels[$panelName];
    }

    /*
     * ====================================
     * = МЕТОДЫ, ДОСТУПНЫЕ ТОЛЬКО АДМИНАМ =
     * ====================================
     */

    private function assertAdminCanDo($__FUNCTION__, $ident) {
        $this->LOGGER->info('{} вызвана для {}', $__FUNCTION__, $ident);
        AuthManager::checkAdminAccess();
    }

    /**
     * Метод возвращает сущность фолдинга не проверяя, существует она или нет
     * 
     * @return FoldedEntity
     */
    public function getFoldedEntityAnyway($ident) {
        $this->assertAdminCanDo(__FUNCTION__, $ident);
        return FoldedEntity::inst($this, $ident, false);
    }

    /**
     * Возвращает массив сущностей, которые могут входить в список.
     * Метод нужен для наполнения текущего состояния списка для отображения в панели администратора.
     */
    public final function getPossibleListIdents($list) {
        $this->assertAdminCanDo(__FUNCTION__, $list);

        $now = $this->getListContent($list);
        $result = array();
        foreach ($now as $ident => $marked) {
            $result[$ident] = array(
                'i' => true, //included
                'm' => $marked //marked
            );
        }
        foreach ($this->getAllIdents() as $ident) {
            if (!array_key_exists($ident, $result)) {
                $result[$ident] = array(
                    'i' => false, //included
                    'm' => false  //marked
                );
            }
        }
        return $result;
    }

    /**
     * Обновляет обложку для сущности
     */
    public function updateEntityCover($ident, DirItem $cover = null) {
        if (!($cover instanceof DirItem) || !$this->isImagesFactoryEnabled() || !$cover->isImg()) {
            return; //---
        }

        $this->assertAdminCanDo(__FUNCTION__, $ident);

        $this->LOGGER->info('Обновляем обложку сущности');
        PsImgEditor::copy($cover, $this->getCoverOriginal($ident));
    }

    /**
     * Редактирование сущности фолдинга
     */
    public function editEntity($ident, ArrayAdapter $params) {
        $this->assertAdminCanDo(__FUNCTION__, $ident);

        foreach ($this->RESOURCE_TYPES_ALLOWED as $type) {
            if ($params->has($type)) {
                $this->getResourceDi($ident, $type)->writeToFile($params->str($type), true);
            }
        }

        //Сущность могла стать видна из-за редактирования записи в базе
        $this->LOGGER->info('Очищаем кеш доступных сущностей');
        $this->IDENTS = null;

        $this->onEntityChanged($ident);
    }

    /**
     * Удаление сущности фолдинга
     */
    public function deleteEntity($ident) {
        $this->assertAdminCanDo(__FUNCTION__, $ident);

        if (!$this->existsEntity($ident)) {
            /*
             * Если сущности нет - не будем ругаться
             */
            return;
        }

        $this->getResourcesDm()->clearDir($ident, true);
    }

    /**
     * Создание сущности фолдинга
     */
    public function createEntity($ident) {
        $this->assertAdminCanDo(__FUNCTION__, $ident);

        if ($this->existsEntity($ident)) {
            /*
             * Просто выходим, если сущность уже создана.
             * Это нам упростит жизнь при создании сущностей ещё и в базе (битблиотек, например).
             */
            return;
        }

        //$this->assertNotExistsEntity($ident);
        $this->getResourcesDm()->makePath($ident);
        //Зачистим кеш созданных сущноестей
        $this->IDENTS = null;
        foreach ($this->RESOURCE_TYPES_ALLOWED as $type) {
            $src = $this->getResourceDi(self::PATTERN_NAME, $type);
            $dst = $this->getResourceDi($ident, $type)->touch();

            /*
             * Теперь возмём содержимое файлов из шаблона и заменим в них pattern на идентификатор сущности.
             * Стоит учитывать, что в шаблоне может и не быть файла с таким расширением.
             */
            $content = $src->getFileContents(false, '');
            $content = str_replace('pattern', $ident, $content);
            $content = str_replace('Pattern', ucfirst($ident), $content);
            $content = str_replace('funique', $this->getUnique(), $content);
            $content = str_replace('eunique', $this->getUnique($ident), $content);
            $content = str_replace('eident', $ident, $content);
            $content = str_replace('eclassname', $this->ident2className($ident), $content);
            $dst->writeToFile($content, true);
        }

        /*
         * Создадим директории - такие-же, как у шаблона, перенеся всё их содержимое.
         */
        $dirs = $this->getResourcesDm()->getDirContent(self::PATTERN_NAME, DirItemFilter::DIRS);
        foreach ($dirs as $dir) {
            $this->getResourcesDm(self::PATTERN_NAME)->copyDirContent2Dir($dir->getName(), $this->getResourcesDm($ident)->getDirItem(), true);
        }
    }

    /*
     * ZIP EXPORT/IMPORT
     */

    /** @return DirManager */
    private $ZIP_SECRET = '42e39f9e6a0383c2d533cf6b30a86ab3';

    private function addZipContents(ZipArchive $zip, array $items) {
        foreach ($items as $item) {
            if (is_array($item)) {
                $this->addZipContents($zip, $item);
            } else if ($item instanceof DirItem) {
                $added = true;
                if ($item->isDir()) {
                    $added = $zip->addEmptyDir($item->getRelPathNoDs());
                } else if ($item->isFile()) {
                    $added = $zip->addFile($item->getAbsPath(), $item->getRelPathNoDs());
                } else {
                    //У нас не директория и не файл, просто пропускаем
                }
                check_condition($added, "Error adding file {$item->getAbsPath()} to zip");
            }
        }
    }

    /** @return DirItem */
    public function export2zip($ident) {
        $this->assertExistsEntity($ident);
        $this->assertAdminCanDo(__FUNCTION__, $ident);

        $ftype = $this->getFoldingType();
        $fsubtype = $this->getFoldingSubType();
        $name = "$ftype-$fsubtype-$ident";

        $zipDi = $this->getAutogenDi($ident, null, null, $name, 'zip')->remove();

        $zip = $zipDi->startZip();

        /*
         * Экспортировать будем всё содержимое + извлечём формулы из .tpl
         */
        $ITEMS = $this->getResourcesDm($ident)->getDirContentFull();
        /*
          if ($this->isAllowedResourceType(self::RTYPE_TPL)) {
          $ITEMS[] = TexImager::inst()->extractTexImages($this->getResourceDi($ident, self::RTYPE_TPL)->getFileContents(false), false, true);
          }
         */

        $this->addZipContents($zip, $ITEMS);

        $secret = $this->ZIP_SECRET;
        $sign = md5("$name-$secret");
        $comment = "$name;$sign";

        $zip->setArchiveComment($comment);
        $zip->close();

        return $zipDi;
    }

    /**
     * Импортирует фолдинг из zip-архива
     * 
     * @param DirItem $zip - пут к архиву
     * @param type $clear - очищать ли директорию перед загрузкой архива
     * @return FoldedEntity
     */
    public function imporFromZip(DirItem $zip, $clear = false) {
        $zip = $zip->loadZip();
        $comment = $zip->getArchiveComment();

        $comment = explode(';', $comment);
        check_condition(count($comment) === 2, 'Bad zip archive sign');

        $name = explode('-', $comment[0]);
        $sign = $comment[1];

        check_condition(count($name) === 3, 'Bad zip name');

        $ftype = $name[0];
        $fsubtype = $name[1];
        $ident = $name[2];

        $this->assertAdminCanDo(__FUNCTION__, $ident);

        /*
         * Сейчас мы загружаем zip-архивы из формы, в которой содержатся тип и подтип фолдинга, 
         * так что будем ругаться, если нам передадут не наш архив.
         * В противном случае можно будет просто проверить $this->isIt($ftype, $fsubtype)
         */
        check_condition($this->isIt($ftype, $fsubtype), "Folding [$ftype]/[$fsubtype] cannot extract this zip");

        $secret = $this->ZIP_SECRET;
        $validSign = md5("$ftype-$fsubtype-$ident-$secret");
        check_condition($sign === $validSign, 'Folding archive sign is invalid');

        //Проверим, будет ли архив развёрнут в надлежащую директорию
        $dm = $this->getResourcesDm($ident);
        if ($clear) {
            $dm->clearDir();
        }

        $exportToDirs[] = $dm->getDirItem()->getRelPathNoDs();
        //$exportToDirs[] = DirManager::formules()->getDirItem()->getRelPathNoDs();

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $path = $zip->getNameIndex($i);
            $valid = contains_substring($path, $exportToDirs);
            check_condition($valid, "Cant export folded to dir: [$path]");
        }

        //Разворачиваем
        $zip->extractTo(PATH_BASE_DIR);
        $zip->close();

        //Очистка старых коверов
        $this->clearGenerated($ident);

        //Оповестим об изменении сущности
        $this->onEntityChanged($ident);

        return $this->getFoldedEntity($ident);
    }

    /**
     * КОНСТРУКТОР
     */
    protected function __construct() {
        $this->CLASS = get_called_class();
        $this->UNIQUE = self::unique($this->getFoldingType(), $this->getFoldingSubType());

        $this->LOGGER = PsLogger::inst(__CLASS__ . '-' . $this->UNIQUE);
        $this->PROFILER = PsProfiler::inst(__CLASS__);

        $this->CLASS_PREFIX = FoldedStorage::getFoldingClassPrefix($this->UNIQUE);
        $this->SMARTY_PREFIX = FoldedStorage::getFoldingSourcePrefix($this->UNIQUE);

        $this->RESOURCE_TYPES_LINKED = array_intersect($this->RESOURCE_TYPES_ALLOWED, $this->RESOURCE_TYPES_LINKED);
        $this->RESOURCE_TYPES_CHECK_CHANGE = array_intersect($this->RESOURCE_TYPES_ALLOWED, $this->RESOURCE_TYPES_CHECK_CHANGE);

        //Получим текстовое описание
        $this->TO_STRING = $this->getTextDescr();

        /*
         * Проверим, что заданы размеры обложки по умолчанию, если мы работаем с картинками
         */
        if ($this->isImagesFactoryEnabled() && !$this->defaultDim()) {
            raise_error("Не заданы размеры обложки по умолчанию для фолдинга $this");
        }

        //Разберём настройки хранения фолдингов в базе
        if ($this->isWorkWithTable()) {
            $dbs = explode('.', trim($this->foldingTable()));
            $this->TABLE_VIEW = array_get_value(0, $dbs);
            $this->TABLE = cut_string_start($this->TABLE_VIEW, 'v_');
            $this->TABLE_COLUMN_IDENT = array_get_value(1, $dbs);
            $this->TABLE_COLUMN_STYPE = array_get_value(2, $dbs);

            check_condition(!!$this->TABLE && !!$this->TABLE_COLUMN_IDENT, "Некорректные настройки работы с базой для фолдинга $this");

            if ($this->TABLE_COLUMN_STYPE) {
                check_condition($this->hasSubType(), "Некорректные настройки работы с базой. Фолдинг $this не имеет подтипа.");
            }
        }
    }

    public function __toString() {
        return $this->TO_STRING;
    }

}

?>