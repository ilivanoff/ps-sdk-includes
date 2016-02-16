<?php

/**
 * Класс для получения общей информации о всех фолдингах, а также для вывода финального лога по всем фолдингам.
 */
final class FoldedResourcesManager extends AbstractSingleton implements Destructable {

    /** @var SimpleDataCache */
    private $CACHE;

    /** @var PsLoggerInterface */
    private $LOGGER;

    /**
     * Метод возвращает сущность фолдинга по заданному коду
     * 
     * @return FoldedEntity Сущность, соответствующая заданному коду
     */
    public function getFoldedEntityByDbCode($code) {
        return $this->CACHE->has($code) ? $this->CACHE->get($code) : $this->CACHE->set($code, FoldedStorageInsts::getFoldedEntityByUnique(FoldingBean::inst()->getUniqueByCode($code)));
    }

    /**
     * DirManager директории, в которой находится основная функциональность для работы с фолдингами
     * @return DirManager
     */
    public function getFoldedDir() {
        return DirManager::inst(__DIR__);
    }

    /**
     * Функция для записи общих логов от имени разных фолдингов, чтобы увидеть в одном месте,
     * какие функции вызываются.
     * 
     * @param FoldedResources $folded - фолдинг, от имени которого пишется лог
     * @param type $msg - сообщение
     */
    public static function info(FoldedResources $folded, $msg) {
        if (self::inst()->LOGGER->isEnabled()) {
            self::inst()->LOGGER->info('[' . $folded->getUnique() . '] ' . $msg);
        }
    }

    /**
     * Действия, которые вконце будут отлогированы
     */
    //Действия над фолдингами

    const ACTION_FOLDING_ALL_CHECKED = 'Фолдинги, для которых все сущности проверены на изменеие';
    const ACTION_FOLDING_ONCE_CHENGED = 'Фолдинги, для которых был вызван onFoldingChanged';
    //Действия над сущностями
    const ACTION_ENTITY_CHECK_CHANGED = 'Сущности, проверенные на изменение';
    const ACTION_ENTITY_CHANGED_DB = 'Список изменённых в БД';
    const ACTION_ENTITY_CHANGED = 'Список изменённых сущностей';
    const ACTION_ENTITY_INST_CREATED = 'Список созданных экземпляров классов';
    const ACTION_ENTITY_FETCH_REQUESTD = 'Сущности, для которых запрошен фетчинг шаблона';
    const ACTION_ENTITY_FETCH_DONE = 'Сущности, для которых фактически выполнен фетчинг шаблона';

    private $ACTIONS = array();

    public static function onEntityAction($action, FoldedResources $folding, $ident = null, $msg = null) {
        if (self::inst()->LOGGER->isEnabled()) {
            self::inst()->ACTIONS[$action][] = array($folding->getUnique($ident), $msg);
        }
    }

    /**
     * В процессе закрытия данного класса мы напишем полный список изменённых сущностей
     */
    public function onDestruct() {
        foreach (array('ACTION_FOLDING_' => 'Фолдинги', 'ACTION_ENTITY_' => 'Сущности') as $CONST_PREFIX => $name) {
            $this->LOGGER->infoBox($name);
            foreach (PsUtil::getClassConsts($this, $CONST_PREFIX) as $action) {
                $idents = array_get_value($action, $this->ACTIONS, array());
                $count = count($idents);

                $this->LOGGER->info();
                $this->LOGGER->info($action . ':');

                if ($count > 0) {
                    for ($i = 0; $i < $count; $i++) {
                        $this->LOGGER->info("\t" . (1 + $i) . '. ' . $idents[$i][0] . ($idents[$i][1] ? ' [' . $idents[$i][1] . ']' : ''));
                    }
                } else {
                    $this->LOGGER->info("\t -- Нет --");
                }
            }
        }
    }

    /** @return FoldedResourcesManager */
    public static function inst() {
        return parent::inst();
    }

    protected function __construct() {
        $this->CACHE = new SimpleDataCache();
        $this->LOGGER = PsLogger::inst(__CLASS__);
        if ($this->LOGGER->isEnabled()) {
            PsShotdownSdk::registerDestructable($this, PsShotdownSdk::FoldedResourcesManager);
        }
    }

}

?>