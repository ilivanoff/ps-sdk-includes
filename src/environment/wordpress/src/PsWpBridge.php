<?php

/**
 * Класс строит мост между wordpress и ps sdk, включая в работу классы sdk
 *
 * @author azazello
 */
class PsWpBridge extends AbstractSingleton {

    public function init() {
        PsLogger::inst(__CLASS__)->info(__FUNCTION__);
        PsWpPlugin::addActions();
        PsWpPlugin::addShortcodes();
    }

    public function pluginActivation() {
        PsLogger::inst(__CLASS__)->info(__FUNCTION__);
    }

    public function pluginDeactivation() {
        PsLogger::inst(__CLASS__)->info(__FUNCTION__);
    }

    /** @return PsWpBridge */
    public static function inst() {
        return parent::inst();
    }

}

?>