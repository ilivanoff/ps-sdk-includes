<?php

/**
 * Специальный класс-обёртка для работы над группой кешей для удобства.
 * Просто объявляете в данном классе метод

  public static final function MY_CACHE_GROUP() {
  return PSCacheInst::inst(__FUNCTION__);
  }

 *
 * @author azazello
 */
class PSCacheGroups {

    /**
     * Кеш для popup-страниц. Будет сброшен при изменении кол-ва видимых плагинов,
     * которое происходит при изменении поста или кол-ва видимых постов.
     * 
     * @return PSCacheGroup
     */
    public static final function POPUPS() {
        return self::inst(__FUNCTION__);
    }

    /**
     * Кеш для временных шкал.
     * 
     * @return PSCacheGroup
     */
    public static final function TIMELINES() {
        return self::inst(__FUNCTION__);
    }

    /**
     * Кеш для картинок-мозаек.
     * 
     * @return PSCacheGroup
     */
    public static final function MOSAIC() {
        return self::inst(__FUNCTION__);
    }

    /**
     * Выданные экземпляры
     */
    private static $insts = array();

    /**
     * Основной метод, возвращающий экземпляры оболочек над группами кешей
     * @return PSCacheGroup
     */
    protected static final function inst($group) {
        return array_key_exists($group, self::$insts) ? self::$insts[$group] : self::$insts[$group] = new PSCacheGroup($group);
    }

}

?>