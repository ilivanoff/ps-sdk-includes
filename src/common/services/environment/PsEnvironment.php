<?php

/**
 * Класс отвечает за окружение, в котором выполняется ps-sdk
 *
 * @author azaz
 */
class PsEnvironment {

    /**
     * Признак того, что рабочее окружение инициализировалось (был вызван метод #init())
     */
    private static $inited = false;

    /**
     * Признак того, что рабочее окружение было подключено:
     * 1. был вызван метод #init()
     * 2. в config.ini задано рабочее окружение
     * 3. рабочее окружение подключено
     */
    private static $included = false;

    /**
     * Метод возвращает идентификатор работчего окружения
     */
    public static function env() {
        return ConfigIni::environment();
    }

    /**
     * Метод проверяет, соответствует ли окружение переданному
     */
    public static function isEnv($env) {
        return self::env() == $env;
    }

    /**
     * Метод проверяет, нужно ли подключать окружение.
     */
    private static function isSkipInclude() {
        return PsContext::isCmd();
    }

    /**
     * Метод вызывается для инициализации окружения:
     * 1. Директория ресурсов окружения будет подключена в Autoload
     * 2. Файл, включающий окружение, будет выполнен
     */
    public static function init() {
        if (self::$inited) {
            return; //---
        }

        self::$inited = true; //---

        /*
         * Проверим, нужно ли подключать окружение
         */
        if (self::isSkipInclude()) {
            return; //---
        }

        $env = self::env();

        if (!$env) {
            return; //---
        }

        $envDir = array_get_value($env, ConfigIni::environments());

        if (!$envDir) {
            return PsUtil::raise('Environment [{}] not found', $env);
        }

        if (!is_dir($envDir)) {
            return PsUtil::raise('Environment dir for [{}] not found', $env);
        }

        $envSrcDir = next_level_dir($envDir, DirManager::DIR_SRC);
        $envIncFile = file_path($envDir, $env, PsConst::EXT_PHP);

        if (!is_file($envIncFile)) {
            return PsUtil::raise('Environment include file for [{}] not found', $env);
        }

        $LOGGER = PsLogger::inst(__CLASS__);
        if ($LOGGER->isEnabled()) {
            $LOGGER->info('Including \'{}\' environment for context \'{}\'', $env, PsContext::describe());
            $LOGGER->info('Env dir:  {}', $envDir);
            $LOGGER->info('Src dir:  {}', $envSrcDir);
            $LOGGER->info('Inc file: {}', $envIncFile);
        }

        //Проинициализировано окружение
        self::$included = true;

        //Регистрируем директорию с классами, специфичными только для данного окружения
        Autoload::inst()->registerBaseDir($envSrcDir, false);

        //Выполним необходимое действие
        $PROFILER = PsProfiler::inst(__CLASS__);
        try {
            $LOGGER->info('{');

            $PROFILER->start($env);
            self::initImpl($LOGGER, $envIncFile);
            $secundomer = $PROFILER->stop();

            $LOGGER->info('}');
            $LOGGER->info('Inc file included for {} sec', $secundomer->getTime());
        } catch (Exception $ex) {
            $PROFILER->stop(false);
            $LOGGER->info('Inc file execution error: [{}]', $ex->getMessage());
            throw $ex; //---
        }
    }

    /**
     * include выполняем в отдельном методе, так как вызываемый файл получит в контекст все наши переменные.
     * 
     * @param PsLoggerInterface $LOGGER - логгер для записи логов
     * @param string $_INC_FILE_ - абсолютный путь к файлу, оторый выполнит включение окружения.
     */
    private static function initImpl($LOGGER, $_INC_FILE_) {
        require_once $_INC_FILE_;
    }

    /**
     * Метод возвращает признак - была ли подключена рабочая среда.
     * Удостоверяемся, что этот метод был вызван после инициализации рабочего пространства.
     */
    public static function isIncluded() {
        return check_condition(self::$inited, __CLASS__ . ' is not inited yet, cannot call ' . __METHOD__) && self::$included;
    }

}
