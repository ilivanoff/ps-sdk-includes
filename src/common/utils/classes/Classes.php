<?php

class Classes {

    /**
     * Метод получает экземпляр класса и, если нужно, кеширует его.
     */
    public static function getClassInstance($__DIR__, $subDir, $className, $parent, $caching = true) {
        if (!is_valid_file_name($className)) {
            return null; //---
        }

        $className = get_file_name($className);

        if ($className == $parent) {
            //Абстрактный класс/интерфейс лежит рядом с классами реализации - пропустим его
            return null;
        }

        //Абсолютный путь к классу
        $classPath = file_path(array($__DIR__, $subDir), $className, PsConst::EXT_PHP);

        //Ключ кеширования
        $cacheKey = md5($classPath);

        $CACHE = $caching ? SimpleDataCache::inst(__CLASS__, __FUNCTION__) : null;

        if ($CACHE && $CACHE->has($cacheKey)) {
            return $CACHE->get($cacheKey);
        }

        $INST = null;

        if (is_file($classPath)) {

            //Подключим данный класс
            require_once $classPath;

            //Проверим, существует ли класс
            $rc = PsUtil::newReflectionClass($className, false);
            $INST = $rc && $rc->isSubclassOf($parent) ? $rc->newInstance() : null;
        }

        if ($CACHE && $INST) {
            $CACHE->set($cacheKey, $INST);
        }

        return $INST;
    }

    /**
     * Возвращает экземпляры всех классов в директории
     */
    public static function getDirClasses($__DIR__, $subDir, $parent, $caching = true) {
        $classes = array();
        foreach (DirManager::inst($__DIR__)->getDirContent($subDir, PsConst::EXT_PHP, DirManager::DC_NAMES_NO_EXT) as $name) {
            $inst = self::getClassInstance($__DIR__, $subDir, $name, $parent, $caching);
            if ($inst) {
                $classes[] = $inst;
            }
        }
        return $classes;
    }

    /**
     * Возвращает названия всех классов в директории
     */
    public static function getDirClassNames($__DIR__, $subDir, $parent) {
        $classes = array();
        foreach (DirManager::inst($__DIR__)->getDirContent($subDir, PsConst::EXT_PHP, DirManager::DC_NAMES_NO_EXT) as $name) {
            if (PsUtil::isInstanceOf($name, $parent)) {
                $classes[] = $name;
            }
        }
        return $classes;
    }

}

?>