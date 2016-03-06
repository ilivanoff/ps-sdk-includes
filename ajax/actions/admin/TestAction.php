<?php

class TestAction extends AbstractAdminAjaxAction {

    protected function getRequiredParamKeys() {
        return array('method');
    }

    protected function executeImpl(ArrayAdapter $params) {
        $method = $params->str('method');
        $params = $params->arr('params');
        TestManagerCaller::execute($method, $params);
        return new AjaxSuccess();
    }

}

?>