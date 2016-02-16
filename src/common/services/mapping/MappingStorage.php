<?php

/**
 * Класс предоставляет информацию обо всех маппингах, чтобы их можно было редактировать в админском интерфейсе.
 *
 * @author azazello
 */
class MappingStorage {

    /** Все маппинги */
    private $mappings = array();

    /**
     * Регистрация маппингов SDK
     */
    private final function registerSdkMappings() {
        $this->register(Mappings::FOLDINGS2DB());
    }

    /**
     * Регистрация проектных маппингов
     */
    protected function registerProjectMappings() {
        
    }

    /**
     * Метод регистрируем маппинг
     */
    protected final function register(Mapping $mapping) {
        if (array_key_exists($mapping->getHash(), $this->mappings)) {
            raise_error("Маппинг '$mapping' уже заругистрирован");
        } else {
            $this->mappings[$mapping->getHash()] = $mapping;
        }
    }

    /**
     * Метод получения всех маппингов системы
     * 
     * @return type
     */
    public static final function listMappings() {
        return self::inst()->mappings;
    }

    /** @return Mapping */
    public static final function getMapping($mhash) {
        return array_get_value($mhash, self::inst()->mappings);
    }

    /** @var PSSmartyFilter */
    private static $inst;

    /**
     * Метод возвращает экземпляр класса-хранилища маппингов.
     * Может быть переопределён в config.ini
     */
    private static final function inst() {
        if (isset(self::$inst)) {
            return self::$inst; //----
        }

        /*
         * Получим название класса
         */
        $class = ConfigIni::mappingStorage();

        /*
         * Класс совпадает с базовым?
         */
        if (__CLASS__ == $class) {
            return self::$inst = new MappingStorage();
        }

        /*
         * Нам передан класс, который отличается от SDK
         */
        $classPath = Autoload::inst()->getClassPath($class);
        if (!PsCheck::isNotEmptyString($classPath)) {
            return PsUtil::raise('Не удалось найти класс хранилища маппингов [{}]', $class);
        }

        /*
         * Указанный класс должен быть наследником данного
         */
        if (!PsUtil::isInstanceOf($class, __CLASS__)) {
            return PsUtil::raise('Указанное хранилище маппингов [{}] не является наследником класса [{}]', $class, __CLASS__);
        }

        return self::$inst = new $class();
    }

    /**
     * В конструкторе зарегистрируем все маппинги
     */
    protected final function __construct() {
        $this->registerSdkMappings();
        $this->registerProjectMappings();
    }

}

?>
