<?php

/**
 * Ресурсы менеджера поэтов
 *
 * @author azazello
 */
abstract class PoetsResources extends LibResources {

    public function getEntityName() {
        return 'Поэты';
    }

    function defaultDim() {
        return '210x';
    }

    //.tpl файлы со стихами поэта
    protected function getPoetVersesTpls($ident) {
        return $this->getResourcesDm($ident)->getDirContent('verses', PsConst::EXT_TPL);
    }

    protected function onEntityChangedImpl($ident) {
        
    }

    public function getFoldingSubType() {
        return 'p';
    }

}

?>