<?php

/**
 * Класс плагинов для Smarty.
 * Может быть переопределён в config.ini
 */
class PSSmartyPlugin {

    /**
     * @var PsLoggerInterface 
     */
    private $LOGGER;

    /**
     * @var PsProfilerInterface 
     */
    private $PROFILER;

    /**
     * @var array
     */
    private $PLUGINS = array();

    /**
     * Метод вызывается для привязки фильтра к Smarty
     * 
     * @param Smarty $smarty
     */
    public final function bind(Smarty $smarty) {
        $this->LOGGER->info();
        $this->LOGGER->info('BINDING Smarty PLUGINS:');

        $this->PROFILER->start('Binding plugins');

        $i = 0;
        /* @var $plugin AbstractSmartyPlugin */
        foreach ($this->PLUGINS as $class => $plugin) {
            $this->LOGGER->info('{}. {}', pad_left(++$i, 1, ' '), $class);
            $plugin->bind($smarty, $this->LOGGER);
        }

        //Останавливаем профайлер
        $sec = $this->PROFILER->stop();

        //Логируем
        $this->LOGGER->info();
        $this->LOGGER->info('BINDING TIME: {} sec', $sec->getTotalTime());
    }

    /**
     * Регистрация SDK плагинов
     */
    private function registerSdkSmartyPlugins() {
        //$this->register(new SmartyBlocks());
        $this->register(new SmartyFunctions());
        //$this->register(new SmartyModifiers());
        $this->register(new SmartyBubblesIncluder());
        $this->register(new SmartyImgIncluder());
        $this->register(new SmartyPanelsInclider());
    }

    /**
     * Регистрация проектных плагинов
     */
    protected function registerProjectSmartyPlugins() {
        
    }

    /**
     * Метод выполняет фактическую регистрацию плагина
     * 
     * @param AbstractSmartyPlugin $plugin
     */
    protected final function register(AbstractSmartyPlugin $plugin) {
        $class = get_class($plugin);
        if (array_key_exists($class, $this->PLUGINS)) {
            PsUtil::raise('Smarty plugin \'{}\' is already registered.', $class);
        } else {
            $this->PLUGINS[$class] = $plugin;
            $this->LOGGER->info('{}. {}', pad_left(count($this->PLUGINS), 1, ' '), $class);
        }
    }

    /** @var PSSmartyPlugin */
    private static $inst;

    /**
     * Метод возвращает экземпляр класса-плагина Smarty.
     * Для переопределения этого класса, на уровне проектного config.ini
     * должен быть задан другой класс.
     * 
     * Это позволит использовать стандартизованный метод подключения плагинов
     */
    public static final function inst() {
        if (isset(self::$inst)) {
            return self::$inst; //----
        }

        /*
         * Получим название класса
         */
        $class = ConfigIni::smartyPlugin();

        /*
         * Класс подключения библиотек совпадает с базовым
         */
        if (__CLASS__ == $class) {
            return self::$inst = new PSSmartyPlugin();
        }

        /*
         * Нам передан класс, который отличается от SDK
         */
        $classPath = Autoload::inst()->getClassPath($class);
        if (!PsCheck::isNotEmptyString($classPath)) {
            return PsUtil::raise('Не удалось найти класс плагинов Smarty [{}]', $class);
        }

        /*
         * Указанный класс должен быть наследником данного
         */
        if (!PsUtil::isInstanceOf($class, __CLASS__)) {
            return PsUtil::raise('Указанный плагин Smarty [{}] не является наследником класса [{}]', $class, __CLASS__);
        }

        return self::$inst = new $class();
    }

    /**
     * В конструкторе зарегистрируем все страницы
     */
    protected final function __construct() {
        $class = get_called_class();
        $basic = __CLASS__ == $class;

        //Логгер
        $this->LOGGER = PsLogger::inst(__CLASS__);
        $this->LOGGER->info('USING {} PLUGIN PROVIDER: {}', $basic ? 'SDK' : 'CUSTOM', $class);

        //Стартуем профайлер
        $this->PROFILER = PsProfiler::inst(__CLASS__);
        $this->PROFILER->start('Loading plugins');

        //Регистрируем фолдинги SDK
        $this->LOGGER->info();
        $this->LOGGER->info('PLUGINS SDK:');
        $this->registerSdkSmartyPlugins();

        //Если используем не SDK провайдер, вызываем регистратор
        if (!$basic) {
            $this->LOGGER->info();
            $this->LOGGER->info('PLUGINS PROJECT:');
            $this->registerProjectSmartyPlugins();
        }

        //Останавливаем профайлер
        $sec = $this->PROFILER->stop();

        //Логируем
        $this->LOGGER->info();
        $this->LOGGER->info('COLLECTING TIME: {} sec', $sec->getTotalTime());
    }

}

?>