<?php

class TestAction extends AbstractAdminAjaxAction {

    protected function getRequiredParamKeys() {
        return array('type', 'class', 'method');
    }

    protected function executeImpl(ArrayAdapter $params) {
        PsAdminAccessClasses::execute($params->str('type'), $params->str('class'), $params->str('method'), $params->arr('params'));
        return new AjaxSuccess();
    }

}

?>