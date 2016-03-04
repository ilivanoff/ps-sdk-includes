<?php

/**
 * Аудит подсистемы бана IP адресов
 *
 * @author azaz
 */
class IpBanAudit extends BaseAudit {

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
        $this->doAudit(self::ACTION_BANNED, null, PsCheck::ip($ip));
    }

    /**
     * Был забанен IP адрес
     */
    public function onUnbanned($ip) {
        $this->doAudit(self::ACTION_UNBANNED, null, PsCheck::ip($ip));
    }

    /**
     * Был забанен IP адрес
     */
    public function onUnbannedAll() {
        $this->doAudit(self::ACTION_UNBANNED_ALL);
    }

    /** @return IpBanAudit */
    public static function inst() {
        return parent::inst();
    }

}

?>