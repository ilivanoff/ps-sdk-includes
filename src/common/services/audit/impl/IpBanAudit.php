<?php

/**
 * Аудит подсистемы бана IP адресов
 *
 * @author azaz
 */
final class IpBanAudit extends PsAuditAbstract {
    /**
     * Действия
     */

    const ACTION_BANNED = 1;
    const ACTION_UNBANNED = 2;
    const ACTION_UNBANNED_ALL = 3;

    public function getProcessCode() {
        return self::CODE_IPBANS;
    }

    public function getDescription() {
        return 'Бан IP адресов';
    }

    /**
     * Был забанен IP адрес
     */
    public function onBanned($ip) {
        parent::doAudit(self::ACTION_BANNED, PsCheck::ip($ip));
    }

    /**
     * Был забанен IP адрес
     */
    public function onUnbanned($ip) {
        parent::doAudit(self::ACTION_UNBANNED, PsCheck::ip($ip));
    }

    /**
     * Был забанен IP адрес
     */
    public function onUnbannedAll() {
        parent::doAudit(self::ACTION_UNBANNED_ALL);
    }

}

?>