<?php

/**
 * Ресурсы классов, занимающихся построением страниц
 *
 * @author azazello
 */
class PageBuilderResources extends FoldedResources {

    /** Допустимые типы ресурсов */
    protected $RESOURCE_TYPES_ALLOWED = array(self::RTYPE_PHP, self::RTYPE_TPL, self::RTYPE_CSS);

    public function getEntityName() {
        return 'Построитель страниц';
    }

    public function getFoldingType() {
        return 'pb';
    }

    public function getFoldingSubType() {
        
    }

    protected function onEntityChangedImpl($ident) {
        
    }

    protected function getFoldedContext() {
        return PageBuilderContext::getInstance();
    }

}

?>
