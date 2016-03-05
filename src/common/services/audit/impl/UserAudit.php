<?php

/**
 * Класс для аудита авторизаций пользователя
 *
 * @author azazello
 */
final class UserAudit extends PsAuditAbstract {
    /**
     * Действия
     */

    const ACTION_REGISTER = 1;
    const ACTION_LOGIN = 2;
    const ACTION_LOGOUT = 3;
    const ACTION_UPDATE = 4;

    public function getDescription() {
        return 'Действия пользователя';
    }

    /**
     * Аудит регистрации пользователя
     */
    public static function afterRegistered($userId, array $params) {
        parent::newRec(self::ACTION_REGISTER)->setUserId($userId)->setData($params)->submit();
    }

    /**
     * Аудит входа пользователя в систему
     */
    public static function afterLogin($userId) {
        parent::newRec(self::ACTION_LOGIN)->setUserId($userId)->submit();
    }

    /**
     * Аудит изменения параметров пользователя
     */
    public static function onUpdate($userId, array $DIFF) {
        parent::newRec(self::ACTION_UPDATE)->setUserId($userId)->setData($DIFF)->submit();
    }

    /**
     * Аудит выхода пользователя из системы
     */
    public static function beforeLogout($userId) {
        parent::newRec(self::ACTION_LOGOUT)->setUserId($userId)->submit();
    }

}

?>