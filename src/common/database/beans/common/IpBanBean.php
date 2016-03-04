<?php

/**
 * Бин для работы с забаненными IP адресами
 *
 * @author azaz
 */
class IpBanBean extends BaseBean {

    /**
     * Проверяет, забанен ли IP адрес
     */
    public function isBanned($ip) {
        return PsCheck::isIp($ip) && $this->hasRec('ps_banned_ip', array('v_ip' => $ip));
    }

    /**
     * Добавляет IP адрес в список забаненных
     */
    public function banIp($ip) {
        if ($this->isBanned($ip)) {
            return false; //---
        } else {
            return PsCheck::isIp($ip) ? $this->update(Query::insert('ps_banned_ip', array('v_ip' => $ip))) > 0 : false;
        }
    }

    /**
     * Удаляет IP адрес из списка забаненных
     */
    public function unbanIp($ip) {
        if ($this->isBanned($ip)) {
            return PsCheck::isIp($ip) ? $this->update(Query::delete('ps_banned_ip', array('v_ip' => $ip))) > 0 : false;
        } else {
            return false; //---
        }
    }

    /**
     * Удаляет все IP адрес из списка забаненных
     * 
     * @return int - кол-во разблокированных ip адресов
     */
    public function unbanAll() {
        return $this->update(Query::delete('ps_banned_ip'));
    }

    /**
     * Возвращает список забаненных IP адресов
     * 
     * @return array - список заблокированных ip адресов
     */
    public function listBanned() {
        return $this->getValues('select v_ip as value from ps_banned_ip');
    }

    /** @return IpBanBean */
    public static function inst() {
        return parent::inst();
    }

}
