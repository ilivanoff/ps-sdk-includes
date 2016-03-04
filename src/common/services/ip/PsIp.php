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
        return IpBanBean::inst()->banIp($ip);
    }

    /**
     * Удаляет IP адрес из списка забаненных
     */
    public static function unban($ip) {
        return IpBanBean::inst()->unbanIp($ip);
    }

    /**
     * Удаляет все IP адрес из списка забаненных
     * 
     * @return int - кол-во разблокированных ip адресов
     */
    public static function unbanAll() {
        return IpBanBean::inst()->unbanAll();
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
     * Метод проверяет, забанен ли IP адрес в $_SERVER
     * 
     * @return bool
     */
    public static function isRemoteAddrBanned() {
        return self::isBanned(ServerArrayAdapter::REMOTE_ADDR());
    }

}
