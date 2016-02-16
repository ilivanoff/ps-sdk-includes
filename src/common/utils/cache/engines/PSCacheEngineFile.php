<?php

/**
 * Класс - движок для хранения данных в постоянном хранилище на основе файла
 *
 * @author azazello
 */
class PSCacheEngineFile implements PSCacheEngine {

    /** @var Cache_Lite */
    private $IMPL;

    public function __construct() {
        /**
         * Подключаем cache lite
         */
        PsLibs::inst()->CacheLite();

        /*
         * Конфигурируем
         */
        $liteOptions = array(
            'readControl' => true,
            'writeControl' => true,
            'readControlType' => 'md5',
            'automaticSerialization' => true, //Чтобы можно было писать объекты и массивы
            //
            'cacheDir' => DirManager::autogen('cache-lite')->absDirPath(),
            'lifeTime' => ConfigIni::cacheFileLifetime() * 60,
            'caching' => true //Кеширование включено всегда
        );

        if (PsLogger::isEnabled()) {
            PsLogger::inst(__CLASS__)->info('Lite options: {}', print_r($liteOptions, true));
        }

        $this->IMPL = new Cache_Lite($liteOptions);
    }

    public function getFromCache($id, $group) {
        return $this->IMPL->get($id, $group);
    }

    public function saveToCache($object, $id, $group) {
        $this->IMPL->save($object, $id, $group);
    }

    public function removeFromCache($id, $group) {
        $this->IMPL->remove($id, $group);
    }

    public function cleanCache($group = null) {
        $this->IMPL->clean($group);
    }

}

?>
