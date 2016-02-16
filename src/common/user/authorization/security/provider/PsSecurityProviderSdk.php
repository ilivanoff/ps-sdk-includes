<?php

/**
 * Базовый провайдер безопасности SDK на основе нашей таблицы users
 *
 * @author azaz
 */
class PsSecurityProviderSdk implements PsSecurityProvider {

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

    public function __construct() {
        $this->loggedIn = SessionArrayHelper::hasInt(SESSION_USER_PARAM);
        $this->userId = $this->loggedIn ? SessionArrayHelper::getInt(SESSION_USER_PARAM) : null;
        $this->loggedInAsAdmin = $this->loggedIn ? UserBean::inst()->isAdmin($this->userId) : false;
    }

}

?>