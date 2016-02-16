<?php

/**
 * Класс регистрирует действия и фильтры
 *
 * @author azazello
 */
class PsWpPlugin {

    /**
     * Метод добавляет действия
     * TODO - вынести на конфиги
     */
    public static function addActions() {
        $wpActions = new PsWpActions();

        $methods = PsUtil::getClassMethods($wpActions, true, false, true, false);

        $LOGGER = PsLogger::inst(__CLASS__);

        $LOGGER->info();
        $LOGGER->info(__METHOD__);

        foreach ($methods as $action) {
            $LOGGER->info(' + {}', $action);
            add_action($action, array($wpActions, $action));
        }
    }

    /**
     * Метод добавляет шорткоды
     * TODO - вынести на конфиги
     */
    public static function addShortcodes() {
        $wpShortcodes = new PsWpShortCodes();

        $methods = PsUtil::getClassMethods($wpShortcodes, true, false, true, false);

        $LOGGER = PsLogger::inst(__CLASS__);

        $LOGGER->info();
        $LOGGER->info(__METHOD__);

        foreach ($methods as $action) {
            $LOGGER->info(' + {}', $action);
            add_shortcode($action, array($wpShortcodes, $action));
        }
    }

}

?>
