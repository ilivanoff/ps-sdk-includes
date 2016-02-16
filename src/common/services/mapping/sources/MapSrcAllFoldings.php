<?php

/**
 * Все фолдинги системы
 *
 * @author azazello
 */
class MapSrcAllFoldings extends MappingSource {

    protected function init($mident, array $params) {
        
    }

    protected function preload($mident, array $params) {
        
    }

    protected function loadDescription($mident, array $params) {
        return 'Все фолдинги системы';
    }

    protected function loadIdentsLeft($mident, array $params) {
        return FoldedStorageInsts::listFoldingUniques();
    }

    protected function loadIdentsRight($mident, array $params, \MappingSource $srcLeft, $lident) {
        return FoldedStorageInsts::listFoldingUniques();
    }

}

?>
