<?php

/**
 * Класс для доступа к цитатам
 *
 * @author azazello
 */
class PsCitates {

    /** @var PsArrayRandomAccess Цитаты */
    private static $citates = null;

    /**
     * Метод возвращает массив цитат, каждая строка имеет вид:
     * автор | цитата
     * 
     * @return PsArrayRandomAccess - цитаты
     */
    public static function citates() {
        return self::$citates ? self::$citates : self::$citates = PsArrayRandomAccess::inst(DirItem::inst(PS_DIR_CONTENT . '/docs/citates', 'citates', PsConst::EXT_TXT)->getFileLines());
    }

    /**
     * Метод возвращает произвольную строку из файла цитат
     */
    private static function randomCitataLine() {
        return self::citates()->getValue();
    }

    /**
     * Метод возвращает произвольную строку из файла цитат
     */
    private static function parseLine($line, &$auth, &$text) {
        $tokens = explode(' | ', $line, 2);
        $auth = $tokens[0];
        return $text = $tokens[1];
    }

    /**
     * Метод возвращает произвольную цитату
     */
    public static function citata(&$auth = '', &$text = '') {
        return self::parseLine(self::randomCitataLine(), $auth, $text);
    }

    /**
     * Метод возвращает статистику цитат по длине имён авторов и по длине самих цитат
     */
    public static function getStatistic() {
        $result = array();

        foreach (self::citates()->getArray() as $line) {
            self::parseLine($line, $auth, $text);
            $result['auth'][ps_strlen($auth)][] = $line;
            $result['text'][ps_strlen($text)][] = $line;
        }

        ksort($result['auth']);
        ksort($result['text']);

        return $result; //---
    }

}

?>
