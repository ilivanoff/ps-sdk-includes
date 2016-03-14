<?php

class AdminAuthManager {

    /**
     * Авторизация администратора
     */
    public static function login() {
        if (FORM_AdminLoginForm::getInstance()->isValid4Process()) {
            $data = FORM_AdminLoginForm::getInstance()->getData();

            $login = $data->getLogin();
            $passwd = $data->getPassword();

            AuthManager::loginAdmin($login, $passwd);
        }
        return AuthManager::isAuthorized();
    }

}

?>