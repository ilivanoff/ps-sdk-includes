<?php

/**
 * Класс для работы с IP адресами - баны, различные проверки и т.д.
 *
 * @author azaz
 */
class PsIp {

    /**
     * Проверяет, забанен ли IP адрес
     */
    public static function isBanned($ip) {
        return IpBanBean::inst()->isBanned($ip);
    }

    /**
     * Добавляет IP адрес в список забаненных
     */
    public static function ban($ip) {
        $done = IpBanBean::inst()->banIp($ip);
        if ($done) {
            IpBanAudit::onBanned($ip);
        }
        return $done;
    }

    /**
     * Удаляет IP адрес из списка забаненных
     */
    public static function unban($ip) {
        $done = IpBanBean::inst()->unbanIp($ip);
        if ($done) {
            IpBanAudit::onUnbanned($ip);
        }
        return $done;
    }

    /**
     * Удаляет все IP адрес из списка забаненных
     * 
     * @return int - кол-во разблокированных ip адресов
     */
    public static function unbanAll() {
        $done = IpBanBean::inst()->unbanAll();
        if ($done) {
            IpBanAudit::onUnbannedAll();
        }
        return $done;
    }

    /**
     * Возвращает список забаненных IP адресов
     * 
     * @return array - список заблокированных ip адресов
     */
    public static function listBanned() {
        return IpBanBean::inst()->listBanned();
    }

    /**
     * Метод определяет удалённый IP адрес в $_SERVER
     * 
     * @return bool
     */
    public static function remoteAddr() {
        foreach (ConfigIni::realIpHeaderNames() as $name) {
            $ip = ServerArrayAdapter::inst()->str($name);
            if (PsCheck::isIp($ip)) {
                return $ip; //---
            }
        }
        return ServerArrayAdapter::REMOTE_ADDR();
    }

    /**
     * Метод проверяет, забанен ли IP адрес в $_SERVER
     * 
     * @return bool
     */
    public static function isRemoteAddrBanned() {
        return self::isBanned(self::remoteAddr());
    }

}
