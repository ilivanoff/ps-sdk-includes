<?php

/**
 * Базовый класс для классов аудита
 *
 * @author azazello
 */
abstract class PsAuditAbstract {

    /**
     * Метод создаёт новое действие
     * 
     * @param int $action - код действия
     * @return PsAuditRec
     */
    protected static function newRec($action) {
        return PsAuditRec::inst(get_called_class(), $action);
    }

}

?>