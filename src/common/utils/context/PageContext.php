<?php

/**
 * Контекст выполнения страницы
 */
class PageContext extends AbstractSingleton {

    /** @return WebPage */
    public function getPage() {
        return WebPages::getCurPage();
    }

    public function getPageCode() {
        return $this->getPage()->getCode();
    }

    public function getPageType() {
        return $this->getPage()->getBuilderType();
    }

    public function isIt($page) {
        return $this->getPage()->isIt($page);
    }

    public function isBasicPage() {
        return $this->getPage()->isType(PB_basic::getIdent());
    }

    public function isAdminPage() {
        return $this->getPage()->isType(PB_admin::getIdent());
    }

    public function isPopupPage() {
        return $this->getPage()->isType(PB_popup::getIdent());
    }

    public function isTestPage() {
        return $this->getPage()->isType(PB_test::getIdent());
    }

    public function isEnvPage() {
        return $this->getPage()->isIt(PAGE_ENV);
    }

    public function getRequestUrl() {
        return PsUrl::current();
    }

    /** @return PageContext */
    public static function inst() {
        return parent::inst();
    }

}

?>