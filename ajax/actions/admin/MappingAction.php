<?php

class MappingAction extends AbstractAdminAjaxAction {

    protected function getRequiredParamKeys() {
        return array('action');
    }

    protected function executeImpl(ArrayAdapter $params) {
        $action = $params->str('action');
        $mhash = $params->str('mhash');
        $lident = $params->str('lident');
        $ridents = $params->arr('ridents');

        $result = 'OK';
        switch ($action) {
            case 'load_left':
                $mapping = AdminMappings::getMapping($mhash);
                $result = array();
                $result['lsrc'] = $mapping->getDescriptionLsrc();
                $result['rsrc'] = $mapping->getDescriptionRsrc();
                $result['lidents'] = array();
                foreach ($mapping->getIdentsLeft() as $lident) {
                    $result['lidents'][] = array($lident, count($mapping->getMappedEntitysUnsafe($lident)));
                }
                break;

            case 'load_right':
                $mapping = AdminMappings::getMapping($mhash);
                $ridents = $mapping->getIdentsRight($lident);
                $selected = $mapping->getMappedEntitysUnsafe($lident);
                $notSelected = array_diff($ridents, $selected);

                $result = array();
                foreach ($selected as $ident) {
                    $result[] = array($ident, 1, in_array($ident, $ridents));
                }
                foreach ($notSelected as $ident) {
                    $result[] = array($ident, 0, true);
                }

                break;

            case 'save':
                AdminMappings::saveMapping($mhash, $lident, $ridents);
                break;

            case 'clean':
                AdminMappings::cleanMapping($mhash);
                break;

            default:
                return 'Неизвестное действие: ' . $action;
        }

        return new AjaxSuccess($result);
    }

}

?>
