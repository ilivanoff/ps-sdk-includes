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
        return PSCacheGroup::inst(__CLASS__, __FUNCTION__);
    }

    /**
     * Кеш для временных шкал.
     * 
     * @return PSCacheGroup
     */
    public static final function TIMELINES() {
        return PSCacheGroup::inst(__CLASS__, __FUNCTION__);
    }

    /**
     * Кеш для картинок-мозаек.
     * 
     * @return PSCacheGroup
     */
    public static final function MOSAIC() {
        return PSCacheGroup::inst(__CLASS__, __FUNCTION__);
    }

}

?>