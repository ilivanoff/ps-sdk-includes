<?php

/**
 * Класс для доступа к цитатам
 *
 * @author azazello
 */
class PsCitates {

    /** @var array Цитаты */
    private static $citates = null;

    /**
     * Метод возвращает массив цитат, каждая строка имеет вид:
     * автор | цитата
     * 
     * @return array - цитаты
     */
    public static function citates() {
        return is_array(self::$citates) ? self::$citates : self::$citates = DirItem::inst(PS_DIR_CONTENT . '/docs/citates', 'citates', PsConst::EXT_TXT)->getFileLines();
    }

    /**
     * Метод возвращает произвольную строку из файла цитат
     */
    private static function randomCitataLine() {
        return self::citates()[rand(0, count(self::citates()) - 1)];
    }

    /**
     * Метод возвращает произвольную цитату
     */
    public static function citata() {
        $tokens = explode(' | ', self::randomCitataLine(), 2);
        return array($tokens[0], $tokens[1]);
    }

}

?>
