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
        parent::newRec(self::ACTION_BANNED)->setData(PsCheck::ip($ip))->submit();
    }

    /**
     * Был забанен IP адрес
     */
    public function onUnbanned($ip) {
        parent::newRec(self::ACTION_UNBANNED)->setData(PsCheck::ip($ip))->submit();
    }

    /**
     * Был забанен IP адрес
     */
    public function onUnbannedAll() {
        parent::newRec(self::ACTION_UNBANNED_ALL)->submit();
    }

}

?>