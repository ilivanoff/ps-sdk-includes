<?php

class ConfigFilesSave extends AbstractAdminAjaxAction {

    protected function getRequiredParamKeys() {
        return array('action');
    }

    protected function executeImpl(ArrayAdapter $params) {
        $action = $params->str('action');

        switch ($action) {
            case 'saveIni':
                ConfigIni::saveIniContent($params->str('scope'), $params->str('content'));
                break;
            default:
                raise_error("Неизвестный тип действия: [$action]");
        }


        return new AjaxSuccess();
    }

}

?>