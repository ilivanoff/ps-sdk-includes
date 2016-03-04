<?php

/**
 * Description of PsAuditAbstract
 *
 * @author azazello
 */
abstract class PsAuditAbstract {

    protected static function doAudit($action, $data = null, $userId = null) {
        PsAuditController::inst(get_called_class())->doAudit($action, $data, $userId);
    }

}

?>