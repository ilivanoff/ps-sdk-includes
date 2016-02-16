<?php

/**
 * {'panelName'|trpostpanel}
 */
class SmartyPanelsInclider extends AbstractSmartyPlugin {

    const MODIFIER_SUFFIX = 'panel';

    protected function do_modifier($tagName, $panelName) {
        $smartyPrefix = cut_string_end($tagName, self::MODIFIER_SUFFIX);
        echo FoldedStorageInsts::bySourcePrefix($smartyPrefix)->includePanel($panelName);
    }

    protected function registerPluginsImpl() {
        /* @var $manager FoldedResources */
        foreach (Handlers::getInstance()->getPanelProviders() as $manager) {
            $this->registerModifier($manager->getSmartyPrefix() . self::MODIFIER_SUFFIX);
        }
    }

}

?>