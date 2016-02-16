<?php

/**
 * Хранилище страниц, с которыми можно работать.
 * Можно переопределить в config.ini и использовать проектный.
 *
 * @author azazello
 */
class WebPagesStorage {

    /**
     * @var PsLoggerInterface 
     */
    private $LOGGER;

    /**
     * @var PsProfilerInterface 
     */
    private $PROFILER;

    /** @var WebPage Текущая страница */
    private $curpage;

    /** @var arr Список доступных web страниц */
    private $PAGES = array();

    /**
     * Регистрация страниц SDK
     */
    private function registerSdkPages() {
        $this->register('/index.php', 'Главная страница', BASE_PAGE_INDEX);
        $this->register('ps-env.php', 'Страница рабочего окружения', PAGE_ENV);
        $this->register('/ps-includes/ps-admin.php', 'Консоль администратора', PAGE_ADMIN, PB_admin::getIdent());
        $this->register('/ps-includes/ps-test.php', 'Тестовая страница', PAGE_TEST, PB_test::getIdent(), AuthManager::AUTH_TYPE_NO_MATTER, null, false);
        $this->register('/ps-includes/ps-popup.php', 'Всплывающее окно', PAGE_POPUP, PB_popup::getIdent());
    }

    /**
     * Регистрация проектных страниц
     */
    protected function registerProjectPages() {
        
    }

    /**
     * Метод регистрации страницы
     * 
     * WebPages::register('xxx.php', 'Консоль администратора', PAGE_ADMIN, self::getIdent(), AuthManager::AUTH_TYPE_NO_MATTER, PAGE_ADMIN);
     * 
     * @param string $path - путь к скрипту, например 'xxx.php'
     * @param string $name - название страницы, например 'Консоль администратора'
     * @param int $code - код страницы PAGE_ADMIN
     * @param int $builderIdent - идентификатор построителя страниц, например 'PB_admin::getIdent()'
     * @param int $authType - тип авторизации, необходимый для доступа к странице, например 'AuthManager::AUTH_TYPE_NO_MATTER'
     * @param int $pageCodeNoAccess - страница, на которую нужно перейти при отсутствии доступа, например 'BASE_PAGE_INDEX'
     * @param bool $allovedInProduction - признак, доступна ли страница в ProductionMode
     */
    protected final function register(//
    $path, //'xxx.php'
            $name, // 'Консоль администратора'
            $code, // PAGE_ADMIN
            $builderIdent = null, //Идентификатор построителя страниц
            $authType = AuthManager::AUTH_TYPE_NO_MATTER, //
            $pageCodeNoAccess = null, //
            $allovedInProduction = true//
    ) {
        if (!$allovedInProduction && PsDefines::isProduction()) {
            return; //----
        }

        $path = PsCheck::notEmptyString($path);
        $name = PsCheck::notEmptyString($name);
        $code = PsCheck::int($code);

        if (array_key_exists($code, $this->PAGES)) {
            PsUtil::raise('\'{}\' is already registered. Cannot register WebPage with same code \'{}\'.', $this->PAGES[$code], $code);
        } else {
            $this->PAGES[$code] = new WebPage($path, $name, $code, $authType, $pageCodeNoAccess, $builderIdent);
            $this->LOGGER->info('+{}. {}', pad_left(count($this->PAGES), 2, ' '), $this->PAGES[$code]);
        }
    }

    /**
     * Метод ищет страницу
     * 
     * @param int|string $search
     * @return WebPage
     */
    public final function searchPage($search) {
        return $this->getPage($search, false);
    }

    /**
     * Метод получает страницу
     */
    public final function getPage($search, $ensure = true) {
        if ($search instanceof WebPage) {
            return $search; //---
        }

        //По коду
        if (is_inumeric($search)) {
            if (array_key_exists($search, $this->PAGES)) {
                return $this->PAGES[$search]; //---
            }
            check_condition(!$ensure, "Страница с кодом [$search] не зарегистрирована");
            return null; //---
        }

        //Загрузка по идентификатору
        if (is_string($search)) {
            /* @var $page WebPage */
            foreach ($this->PAGES as $page) {
                if ($page->getPath() == $search) {
                    return $page; //---
                }
            }
            check_condition(!$ensure, "Страница с адресом [$search] не зарегистрирована");
            return null; //---
        }

        check_condition(!$ensure, "Страница $search не зарегистрирована");
        return null;
    }

    /**
     * Возвращает текущую страницу.
     * 
     * @return WebPage
     */
    public function hasCurPage() {
        return $this->curpage instanceof WebPage;
    }

    /**
     * Возвращает текущую страницу.
     * 
     * @return WebPage
     */
    public function getCurPage() {
        return check_condition($this->curpage, 'Текущая страница не установлена');
    }

    /**
     * Проверяет, является ли переданная страница - текущей
     * 
     * @param type $page
     */
    public function isCurPage($page) {
        return $this->hasCurPage() && ($this->curpage->isIt($page));
    }

    /**
     * Метод перезагружает текущую страницу
     */
    public final function reloadCurPage() {
        self::getCurPage()->redirectHere();
    }

    /**
     * Метод запускается после регистрации всех страниц
     */
    private final function init() {
        $this->curpage = $this->searchPage(ServerArrayAdapter::PHP_SELF());

        //Если страница не определена, то, возможно, это страница рабочего окружения (другой CMS).
        if (!$this->curpage && PsEnvironment::isIncluded()) {
            $this->curpage = $this->getPage(PAGE_ENV);
        }

        $this->LOGGER->info('CURRENT: {}', $this->curpage);
    }

    /** @var WebPagesStorage */
    private static $inst;

    /**
     * Метод возвращает экземпляр класса-хранилища страниц.
     * Для переопределения этого класса, на уровне проектного config.ini
     * должен быть задан другой класс.
     * 
     * @return WebPagesStorage
     */
    public static final function inst() {
        if (isset(self::$inst)) {
            return self::$inst; //----
        }

        /*
         * Получим название класса
         */
        $class = ConfigIni::webPagesStore();

        /*
         * Класс совпадает с базовым
         */
        if (__CLASS__ == $class) {
            return self::$inst = new WebPagesStorage();
        }

        /*
         * Нам передан класс, который отличается от SDK
         */
        $classPath = Autoload::inst()->getClassPath($class);
        if (!PsCheck::isNotEmptyString($classPath)) {
            return PsUtil::raise('Не удалось найти класс регистрации web страниц [{}]', $class);
        }

        /*
         * Указанный класс должен быть наследником данного
         */
        if (!PsUtil::isInstanceOf($class, __CLASS__)) {
            return PsUtil::raise('Указанный класс регистрации web страниц [{}] не является наследником класса [{}]', $class, __CLASS__);
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
        $this->LOGGER->info('USING {} STORAGE: {}', $basic ? 'SDK' : 'CUSTOM', $class);

        //Стартуем профайлер
        $this->PROFILER = PsProfiler::inst(__CLASS__);
        $this->PROFILER->start('Registering Web pages');

        //Регистрируем страницы SDK
        $this->LOGGER->info();
        $this->LOGGER->info('PAGES SDK:');
        $this->registerSdkPages();

        //Если используем не SDK провайдер, вызываем регистратор
        if (!$basic) {
            $this->LOGGER->info();
            $this->LOGGER->info('PAGES PROJECT:');
            $this->registerProjectPages();
        }

        //Проведём инициализацию
        $this->LOGGER->info();
        $this->LOGGER->info('INITIALIZING...');
        $this->init();

        //Останавливаем профайлер
        $sec = $this->PROFILER->stop();

        //Логируем
        $this->LOGGER->info();
        $this->LOGGER->info('REGISTERING TIME: {} sec', $sec->getTotalTime());
    }

}

?>
