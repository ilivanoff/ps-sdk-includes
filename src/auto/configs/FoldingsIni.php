<?php

/**
 * Класс обрабатывает foldings.ini
 *
 * @author azazello
 */
final class FoldingsIni extends AbstractIni {

    const GROUP_FOLDINGS = 'foldings';
    const GROUP_SETTINGS = 'settings';

    /*
     * SETTINGS
     */

    public static function foldingsStore() {
        return self::getPropCheckType(self::GROUP_SETTINGS, 'storage', array(PsConst::PHP_TYPE_STRING));
    }

    /*
     * FOLDINGS
     */

    private static $rel;

    public static function foldingsRel() {
        return isset(self::$rel) ? self::$rel : self::$rel = PsCheck::arr(self::getGroup(self::GROUP_FOLDINGS));
    }

    private static $abs;

    public static function foldingsAbs() {
        return isset(self::$abs) ? self::$abs : self::$abs = DirManager::relToAbs(self::foldingsRel());
    }

}

?>