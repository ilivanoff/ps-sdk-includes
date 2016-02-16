<?php

/**
 * Description of UtilsBean
 *
 * @author Admin
 */
class UtilsBean extends BaseBean {
    /*
     * =====================================
     * ===== АВТОРИЗАЦИИ ПОЛЬЗОВАТЕЛЯ ======
     * =====================================
     */

    public function saveAudit($parentId, $userId, $userIdAuthed, $processId, $action, $data, $encoded) {
        return $this->insert(
                        'INSERT INTO ps_audit (id_rec_parent, id_user, id_user_authed, id_process, dt_event, n_action, v_data, b_encoded) VALUES (?, ?, ?, ?, unix_timestamp(), ?, ?, ?)', //
                        array($parentId, $userId, $userIdAuthed, $processId, $action, $data, $encoded));
    }

    /*
     * =====================================
     * ============= ОПЕЧАТКИ ==============
     * =====================================
     */

    public function saveMisprint($url, $text, $note = null, $user_id = null) {
        $ident = md5("text: $text, note: $note");

        $cnt = $this->getCnt('select count(1) as cnt from ps_misprint where url=? and ident=?', array($url, $ident));
        if ($cnt > 0) {
            //Такая запись уже есть
            return false;
        }

        $updated = $this->update('INSERT INTO ps_misprint(url, text, note, id_user, ident) VALUES (?, ?, ?, ?, ?)', array(
            $url, $text, $note, $user_id, $ident));

        return $updated > 0;
    }

    public function getMissprints() {
        return $this->getArray('select * from ps_misprint where b_deleted=0 order by id_missprint limit 50');
    }

    public function removeMissprint($id) {
        $this->update('update ps_misprint set b_deleted=1 where id_missprint=?', array($id));
    }

    /**
     * Получение now() из базы (2014-04-07 16:44:33)
     */
    public function getDbNow() {
        return $this->getValue('select now()');
    }

    /**
     * Получение unix_timestamp() из базы (1396874703)
     */
    public function getDbUnixTimeStamp() {
        return $this->getInt('select unix_timestamp()');
    }

    /** @return UtilsBean */
    public static function inst() {
        return parent::inst();
    }

}

?>