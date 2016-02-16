<?php

/**
 * CssSpritesManager::getDirSprite(CssSpritesManager::DIR_ICO, 'print', true)
 */
class CssSpritesManager {

    /**
     * Метод возвращает спрайты для всех зарегистрированных директорий
     * TODO - выкинуть
     */
    public static function getAllDirsSptites() {
        return array();
    }

    /** @return CssSprite */
    public static function getSprite(Spritable $item) {
        return CssSprite::inst($item);
    }

    /**
     * Спрайт для формулы
     */
    public static function getFormulaSprite(Spritable $item, $formula, $classes = null) {
        $itemName = TexTools::formulaHash($formula);
        $atts = array();
        $atts['data']['tex'] = $itemName;
        $atts['class'][] = $classes;
        return self::getSprite($item)->getSpriteSpan($itemName, $atts);
    }

    /**
     * Спрайт для картинки из директории
     */
    public static function getDirSprite(DieItem $dir, $itemName, $withGray = false) {
        return self::getSprite($dir)->getSpriteSpan($itemName, array(), $withGray);
    }

}

?>