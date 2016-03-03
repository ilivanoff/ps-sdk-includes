<?php

/**
 * Наш класс, занимающийся обработкой ошибок, выбрасываемых через trigger_error.
 */
final class ExceptionHandler {

    /**
     * Метод форматирует вывод Exception в html
     */
    public static function getHtml(Exception $exception) {
        //Вычитываем [exception.html] и производим замены
        try {
            return str_replace('{STACK}', ExceptionHelper::formatStackHtml($exception), file_get_contents(file_path(__DIR__, 'exception.html')));
        } catch (Exception $ex) {
            //Если в методе форматирования эксепшена ошибка - прекращаем выполнение.
            die("Exception [{$exception->getMessage()}] stack format error: [{$ex->getMessage()}]");
        }
    }

    public static function register4errors() {
        set_error_handler(array(__CLASS__, 'processError'), error_reporting());
    }

    public static function register() {
        restore_exception_handler();
        set_exception_handler(array(__CLASS__, 'processException'));
    }

    public static function registerPretty() {
        restore_exception_handler();
        set_exception_handler(array(__CLASS__, 'processExceptionPretty'));
    }

    /**
     * Функция, вызывающая обычную обработку неотловленного Exception
     */
    public static function processException(Exception $exception) {
        die($exception->getMessage());
    }

    /**
     * Функция, вызывающая красивую обработку неотловленного Exception
     */
    public static function processExceptionPretty(Exception $exception) {
        die(self::getHtml($exception));
    }

    /**
     * Функция, выполняющая обработку php ошибок, выбрасываемых через trigger_error
     */
    public static function processError($errorLevel, $message, $file, $line) {
        if (!error_reporting()) {
            //Вывод ошибок для данного метода - @отключен
            return; //---
        }

        if (ExternalPluginsManager::isExternalFile($file)) {
            //Данный файл относится к файлам внешних плагинов
            //Возвращаем управление встроенному обработчику
            return false; //---
        }

        throw new PsErrorException($message, $errorLevel, $file, $line);
    }

    /**
     * Метод собирает информацию об ошибке для дампа
     * 
     * @param Exception $exception
     * @param type $additionalInfo
     */
    public static function collectDumpInfo(Exception $exception, $additionalInfo = '') {
        $INFO[] = 'SERVER: ' . (isset($_SERVER) ? print_r($_SERVER, true) : '');
        $INFO[] = 'REQUEST: ' . (isset($_REQUEST) ? print_r($_REQUEST, true) : '');
        $INFO[] = 'SESSION: ' . (isset($_SESSION) ? print_r($_SESSION, true) : '');
        $INFO[] = 'FILES: ' . (isset($_FILES) ? print_r($_FILES, true) : '');

        $additionalInfo = trim("$additionalInfo");

        if ($additionalInfo) {
            $INFO[] = "ADDITIONAL:\n$additionalInfo\n";
        }

        $INFO[] = 'STACK:';
        $INFO[] = ExceptionHelper::formatStackFile($exception);

        return implode("\n", $INFO);
    }

    /**
     * Метод сохраняет ошибку выполнения в файл
     * 
     * @param Exception $exception - исключение
     * @param mixed $additionalInfo - дополнительная информация
     * @return string - информация с исключением для дампа
     */
    public static function dumpError(Exception $exception, $additionalInfo = '') {
        if (ConfigIni::exceptionsMaxDumpCount() <= 0) {
            return; //---
        }

        try {
            $DumpInfo = self::collectDumpInfo($exception, $additionalInfo);

            //Поставим защиту от двойного дампинга ошибки
            $SafePropName = 'ps_ex_dumped';
            if (property_exists($exception, $SafePropName)) {
                return $DumpInfo; //---
            }
            $exception->$SafePropName = true;


            $original = ExceptionHelper::extractOriginal($exception);
            $fname = get_file_name($original->getFile());
            $fline = $original->getLine();

            $DM = DirManager::autogen('exceptions');
            if ($DM->getDirContentCnt() >= ConfigIni::exceptionsMaxDumpCount()) {
                $DM->clearDir();
            }
            $DM->getDirItem(null, PsUtil::fileUniqueTime() . " [$fname $fline]", PsConst::EXT_ERR)->putToFile($DumpInfo);

            return $DumpInfo; //---
        } catch (Exception $ex) {
            //Если в методе дампа эксепшена ошибка - прекращаем выполнение.
            die("Exception [{$exception->getMessage()}] dump error: [{$ex->getMessage()}]");
        }
    }

}

?>