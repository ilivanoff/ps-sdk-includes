<?php

class TestAction extends AbstractAdminAjaxAction {

    protected function getRequiredParamKeys() {
        return array('class', 'method');
    }

    protected function executeImpl(ArrayAdapter $params) {
        PsDevClasses::execute($params->str('class'), $params->str('method'), $params->arr('params'));
        return new AjaxSuccess();
    }

}

?>