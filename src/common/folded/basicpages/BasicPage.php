<?php

/**
 * Базовая страница
 */
abstract class BasicPage extends FoldedClass {

    protected function _construct() {
        //do nothing...
    }

    public function getAuthType() {
        return AuthManager::AUTH_TYPE_NO_MATTER;
    }

    //Функция вызывается до построения контента страницы, чтобы иметь возможность выполнить ряд действий
    public abstract function doProcess(RequestArrayAdapter $params);

    public abstract function getTitle();

    public abstract function getSmartyParams4Resources();

    public abstract function getJsParams();

    public abstract function buildContent();
}

?>