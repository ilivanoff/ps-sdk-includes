<?php

/*
 * В этот класс для удобства вынесено всё, что "знает" информацию о плагинах
 */

class PopupPagesResources extends FoldedResources implements ImagedFolding {

    function defaultDim() {
        return '36x36';
    }

    public function getEntityName() {
        return 'Popup page';
    }

    public function getFoldingType() {
        return 'pp';
    }

    public function getFoldingSubType() {
        return null;
    }

    protected function onEntityChangedImpl($ident) {
        
    }

}

?>