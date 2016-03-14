<?php

/**
 * Базовый провайдер безопасности, который устанавливается в случае работы из командной строки
 *
 * @author azaz
 */
class PsSecurityProviderCmd implements PsSecurityProvider {

    public function getUserId() {
        return null;
    }

    public function isAuthorized() {
        return false;
    }

    public function isAuthorizedAsAdmin() {
        return false;
    }

    public function reset() {
        
    }

}

?>