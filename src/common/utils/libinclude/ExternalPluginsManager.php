<?php

/**
 * Класс для подключения внешних плагинов.
 * @author azazello
 */
class ExternalPluginsManager {

    /**
     * Метод проверит - относится ли файл к файлам внешних плагинов
     */
    public static function isExternalFile($fileAbsPath) {
        return starts_with(normalize_path($fileAbsPath), normalize_path(PATH_BASE_DIR . 'plugins/'));
    }

}

?>