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

}

?>