<?php

/**
 * Хранилище всех WebPage, которые только есть в системе.
 * Данный класс является статической надстройкой над WebPagesStorage.
 */
class WebPages {

    /**
     * Возвращает текущую страницу.
     * 
     * @return WebPage
     */
    public static function getCurPage() {
        return WebPagesStorage::inst()->getCurPage();
    }

    /**
     * Проверияет, установлена ли текущая страницаы
     * 
     * @return type
     */
    public final static function hasCurrentPage() {
        return WebPagesStorage::inst()->hasCurPage();
    }

    /**
     * Проверяет, является ли переданная страница - текущей
     * 
     * @param type $page
     */
    public static function isCurPage($page) {
        return WebPagesStorage::inst()->isCurPage($page);
    }

    /**
     * Метод перезагружает текущую страницу
     */
    public static function reloadCurPage() {
        self::getCurPage()->redirectHere();
    }

    /**
     * Метод перезагружает текущую страницу
     */
    public static function redirectToIndex() {
        self::getPage(BASE_PAGE_INDEX)->redirectHere();
    }

    /**
     * Метод определяет и строит текущую Web страницу.
     * Если у пользователя нет к ней доступа, то он будет перенаправлен.
     */
    public static function buildCurrent() {
        if (self::hasCurrentPage()) {
            self::getCurPage()->buildPage();
        } else {
            self::getPage(BASE_PAGE_INDEX)->redirectHere();
        }
    }

    /**
     * Метод получения зарегистрированной страницы
     * 
     * @return WebPage
     */
    public final static function getPage($page, $ensure = true) {
        return WebPagesStorage::inst()->getPage($page, $ensure);
    }

}

?>