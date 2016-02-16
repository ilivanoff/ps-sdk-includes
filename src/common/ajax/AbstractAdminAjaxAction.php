<?php

abstract class AbstractAdminAjaxAction extends AbstractAjaxAction {

    public final function getAuthType() {
        return AuthManager::AUTH_TYPE_AUTHORIZED_AS_ADMIN;
    }

    protected function isCheckActivity() {
        return false;
    }

}

?>