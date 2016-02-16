<?php

/*
 * Ресурсы об учёных
 */

abstract class ScientistsResources extends LibResources {

    public function getEntityName() {
        return 'Учёные';
    }

    function defaultDim() {
        return '210x';
    }

    protected function onEntityChangedImpl($ident) {
        
    }

    public function getFoldingSubType() {
        return 's';
    }

}

?>