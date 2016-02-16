<?php

class StockResources extends FoldedResources implements ImagedFolding {

    protected $RESOURCE_TYPES_ALLOWED = array(self::RTYPE_JS, self::RTYPE_CSS, self::RTYPE_PHP, self::RTYPE_TPL);

    function defaultDim() {
        return '128x128';
    }

    protected function onEntityChangedImpl($ident) {
        
    }

    public function getEntityName() {
        return 'Акция';
    }

    public function getFoldingType() {
        return 'st';
    }

    public function getFoldingSubType() {
        
    }

}

?>