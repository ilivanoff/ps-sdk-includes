<?php

class IpBanAction extends AbstractAdminAjaxAction {

    protected function getRequiredParamKeys() {
        return array('action', 'ip');
    }

    protected function executeImpl(ArrayAdapter $params) {
        $ip = PsCheck::ip($params->str('ip'));
        $action = $params->str('action');
        $res = array();
        switch ($action) {
            case 'ban':
                $res['done'] = PsIp::ban($ip);
                break;
            case 'unban':
                $res['done'] = PsIp::unban($ip);
                break;
            default:
                raise_error("Unknown action: $action");
        }

        return new AjaxSuccess($res);
    }

}

?>
