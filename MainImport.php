<?php

//Засекаем время начала выполнения скрипта. Далее будет использовано в профайлере.
define('SCRIPT_EXECUTION_START', microtime(true));

/*
 * Разделитель директорий
 */
define('DIR_SEPARATOR', '/');

/*
 * Корневая папка (DocumentRoot) - C:/www/postupayu.ru/www/
 */
define('PATH_BASE_DIR', str_replace('\\', DIR_SEPARATOR, dirname(__DIR__)) . DIR_SEPARATOR);

/*
 * Название папки с проектными файлами (классы, ajax-действия, ресурсы и т.д.)
 */
define('PS_DIR_ADDON', 'ps-addon');

/*
 * Название папки с включениями sdk (классы, библиотеки и т.д.)
 */
define('PS_DIR_INCLUDES', 'ps-includes');

/*
 * Название папки с содержимым (временные файлы, загрузки и т.д.)
 */
define('PS_DIR_CONTENT', 'ps-content');

/*
 * Проверим, что данный файл лежит в папке с включениями
 */
if (PS_DIR_INCLUDES != basename(__DIR__)) {
    die('Invalid ps-sdk includes dir: ' . basename(__DIR__));
}

/*
 * Стартуем сессию 
 * TODO - надо ли?
 */
if (!isset($_SESSION)) {
    session_start();
}

/*
 * Подключим все классы из src/auto
 */
include_once __DIR__ . '/src/auto/PsCoreIncluder.php';
PsCoreIncluder::inst()->includeCore();

/*
 * Зарегистрируем наш обработчик для php ошибок
 */
ExceptionHandler::register4errors();

/*
 * Подключим обработчик эксепшенов. Позднее мы подключим "красивый" обработчик ошибок.
 */
ExceptionHandler::register();

/*
 * Подключим загрузчик служебных классов
 */
Autoload::inst()->register();

/*
 * Если мы работаем под процессом - не подключаемся автоматически к DB и используем специальный провайдер безопасности
 */
if (PsContext::isCmd()) {
    /*
     * Установим специальный провайдер безопасности для консольного процесса
     */
    PsSecurity::set(new PsSecurityProviderCmd());
} else {
    /*
     * Автоматически подключаемся к БД
     */
    PsConnectionPool::configure(PsConnectionParams::production());
}

/*
 * Инициализируем окружение, если мы работаем под ним.
 * Подключаемое окружение может установить свой провайдер безопасности.
 * Важно! Вызов не перемещать в if, так как метод init должен быть вызван обязательно.
 */
PsEnvironment::init();

/*
 * Инициализируем подсистему безопасности
 */
PsSecurity::init();

//Зарегистрируем функцию, подключающую админские ресурсы
function ps_admin_on($force = false) {
    if ($force || AuthManager::isAuthorizedAsAdmin()) {
        Autoload::inst()->registerAdminBaseDir();
    }
}

//Ну и сразу попытаемся подключить админские ресурсы
ps_admin_on();

//Подключаем файл глобальных настроек, если он существует и мы работаем в рамках проекта
PsGlobals::init();

//Получим экземпляр профайлера, чтобы подписаться на PsShotdown, если профилирование включено
PsProfiler::inst()->add('ScriptInit', Secundomer::inst()->add(1, microtime(true) - SCRIPT_EXECUTION_START));
?>