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
        parent::doAudit(self::ACTION_REGISTER, $params, $userId);
    }

    /**
     * Аудит входа пользователя в систему
     */
    public static function afterLogin($userId) {
        $data['ip'] = ServerArrayAdapter::REMOTE_ADDR();
        $data['agent'] = ServerArrayAdapter::HTTP_USER_AGENT();
        parent::doAudit(self::ACTION_LOGIN, $data, $userId);
    }

    /**
     * Аудит изменения параметров пользователя
     */
    public static function onUpdate($userId, array $DIFF) {
        parent::doAudit(self::ACTION_UPDATE, $DIFF, $userId);
    }

    /**
     * Аудит выхода пользователя из системы
     */
    public static function beforeLogout($userId) {
        $this->doAudit(self::ACTION_LOGOUT, null, $userId);
    }

}

?>