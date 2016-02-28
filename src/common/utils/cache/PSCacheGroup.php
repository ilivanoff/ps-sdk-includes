<?php

/**
 * Класс-обёртка над PSCache, для работы с инкапсуляцией группы
 */
class PSCacheGroup {

    private $group;

    public function __construct($group) {
        $this->group = PsCheck::notEmptyString($group);
    }

    public function getFromCache($id, array $REQUIRED_KEYS = null, $sign = null) {
        return PSCache::inst()->getFromCache($id, $this->group, $REQUIRED_KEYS, $sign);
    }

    public function saveToCache($object, $id, $sign = null) {
        return PSCache::inst()->saveToCache($object, $id, $this->group, $sign);
    }

    public function removeFromCache($id) {
        PSCache::inst()->removeFromCache($id, $this->group);
    }

    public function clean() {
        PSCache::inst()->cleanCache($this->group);
    }

    /**
     * Выданные экземпляры
     */
    private static $insts = array();

    /**
     * Основной метод, возвращающий экземпляры оболочек над группами кешей
     * @return PSCacheGroup
     */
    public static final function inst($__CLASS__, $__FUNCTION__) {
        $group = PsCheck::notEmptyString($__CLASS__) . ':' . PsCheck::notEmptyString($__FUNCTION__);
        return array_key_exists($group, self::$insts) ? self::$insts[$group] : self::$insts[$group] = new PSCacheGroup($group);
    }

}

?>