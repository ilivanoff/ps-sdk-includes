<?php

/**
 * Провайдер безопасности в случае работы в контексте WordPress
 *
 * @author azaz
 */
class PsSecurityProviderWp implements PsSecurityProvider {

    private $userId;
    private $loggedIn;
    private $loggedInAsAdmin;

    public function getUserId() {
        return $this->userId;
    }

    public function isAuthorized() {
        return $this->loggedIn;
    }

    public function isAuthorizedAsAdmin() {
        return $this->loggedInAsAdmin;
    }

    public function reset() {
        $this->loggedIn = is_user_logged_in();
        $this->userId = $this->loggedIn ? PsCheck::positiveInt(get_current_user_id()) : null;
        $this->loggedInAsAdmin = $this->loggedIn ? is_super_admin($this->userId) : false;
    }

}
