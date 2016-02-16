<?php

/**
 * Основной класс для работы с кешем
 */
final class PSCache extends AbstractSingleton {

    /** @var PsLoggerInterface */
    private $LOGGER;

    /** @var array */
    private $CACHE_LOCAL = array();

    /** @var PSCacheEngine */
    private $CACHE_ENGINE;

    const KEY_DATA = 'data';
    const KEY_SIGN = 'sign';

    /**
     * Метод валидирует значение кода группы и ключа.
     * 
     * @param string $id - Код значения
     * @param string $group - Группа, в которую входит код
     */
    private function buildCacheKey($id, $group) {
        return PsCheck::notEmptyString($group) . ' [' . PsCheck::notEmptyString($id) . ']';
    }

    /**
     * Метод удаляет из кеша значение по ключу
     * 
     * @param string $id - Код значения
     * @param string $group - Группа, в которую входит код
     */
    private function cleanCacheKey($id, $group) {
        unset($this->CACHE_LOCAL[$group][$id]);
        $this->CACHE_ENGINE->removeFromCache($id, $group);
    }

    /**
     * Метод удаляет значение из кеша
     * 
     * @param string $id - Код значения
     * @param string $group - Группа, в которую входит код
     */
    public function removeFromCache($id, $group) {
        $cacheId = $this->buildCacheKey($id, $group);
        $this->LOGGER->info("- Удалена информация по ключу '$cacheId'");
        $this->cleanCacheKey($id, $group);
    }

    /**
     * Метод загружает значение из кеша
     * 
     * @param string $id - Код значения
     * @param string $group - Группа, в которую входит код
     * @param array|null $REQUIRED_KEYS - Ключи, наличие которых должны быть в кеше.
     *                                    Если переданы - будет проверено, что значение является массивом и содержит все необходимые ключи
     * @param mixed $sign - Подпись, которая должна совпасть для кеша
     */
    public function getFromCache($id, $group, array $REQUIRED_KEYS = null, $sign = null) {
        $cacheId = $this->buildCacheKey($id, $group);

        $CACHED = null; //---
        $SEARCH_WITH_ENGINE = false;

        if (array_key_exists($group, $this->CACHE_LOCAL) && array_key_exists($id, $this->CACHE_LOCAL[$group])) {
            //Прежде всего поищем в локальном хранилище, постараемся найти значение максимально быстро
            $CACHED = $this->CACHE_LOCAL[$group][$id];
            $this->LOGGER->info("Информация по ключу '$cacheId' найдена в быстром кеше");
        } else {
            //Используем движок для поиска в нём
            $this->LOGGER->info("За информацией по ключу '$cacheId' обращаемся в долговременное хранилище");

            PsProfiler::inst(__CLASS__)->start('LOAD from cache engine');
            $CACHED = $this->CACHE_ENGINE->getFromCache($id, $group);
            PsProfiler::inst(__CLASS__)->stop();

            $SEARCH_WITH_ENGINE = true;
        }

        if (!$CACHED) {
            $this->LOGGER->info("Информация по ключу '$cacheId' не найдена в кеше");
            return null; //---
        }

        if (!is_array($CACHED)) {
            $this->LOGGER->info("Информация по ключу '$cacheId' найдена в хранилище, но не является массивом. Чистим...");
            $this->cleanCacheKey($id, $group);
            return null; //---
        }

        if (!array_key_exists(self::KEY_SIGN, $CACHED) || !array_key_exists(self::KEY_DATA, $CACHED)) {
            $this->LOGGER->info("Информация по ключу '$cacheId' найдена в хранилище, но отсутствует параметр {} или {}. Чистим...", self::KEY_SIGN, self::KEY_DATA);
            $this->cleanCacheKey($id, $group);
            return null; //---
        }

        if ($CACHED[self::KEY_SIGN] != $sign) {
            $this->LOGGER->info("Информация по ключу '$cacheId' найдена в хранилище, но старая и новая подписи не совпадают: [{}]!=[{}]. Чистим...", $CACHED[self::KEY_SIGN], $sign);
            $this->cleanCacheKey($id, $group);
            return null; //---
        }

        if (!is_array($REQUIRED_KEYS)) {
            if ($SEARCH_WITH_ENGINE) {
                $this->LOGGER->info("Информация по ключу '$cacheId' найдена в долговременном хранилище");
                $this->CACHE_LOCAL[$group][$id] = $CACHED;
            }
            return $CACHED[self::KEY_DATA]; //---
        }

        if (!is_array($CACHED[self::KEY_DATA])) {
            $this->LOGGER->info("Информация по ключу '$cacheId' найдена в хранилище, но не является массивом. Чистим...");
            $this->cleanCacheKey($id, $group);
            return null; //---
        }

        if (!empty($REQUIRED_KEYS)) {
            foreach ($REQUIRED_KEYS as $key) {
                if (!array_key_exists($key, $CACHED[self::KEY_DATA])) {
                    $this->LOGGER->info("Информация по ключу '$cacheId' найдена, но в данных отсутствует обязательный ключ [$key]. Чистим...");
                    $this->cleanCacheKey($id, $group);
                    return null; //---
                }
            }
        }

        //Если мы нашли эти данные в долгосрочном хранилище - перенесём в быстрый доступ
        if ($SEARCH_WITH_ENGINE) {
            $this->LOGGER->info("Информация по ключу '$cacheId' найдена в долговременном хранилище");
            $this->CACHE_LOCAL[$group][$id] = $CACHED;
        }

        return $CACHED[self::KEY_DATA]; //---
    }

    /**
     * Метод сохраняет значение в кеш
     * 
     * @param mixed $object - сохраняемое значение
     * @param string $id - Код значения
     * @param string $group - Группа, в которую входит код
     * @param mixed $sign - Подпись, которая должна совпасть для кеша
     */
    public function saveToCache($object, $id, $group, $sign = null) {
        $cacheId = $this->buildCacheKey($id, $group);
        $this->LOGGER->info("+ Информация по ключу '$cacheId' сохранена в кеш с подписью '$sign'");

        $CACHED[self::KEY_SIGN] = $sign;
        $CACHED[self::KEY_DATA] = $object;

        //Нужно быть аккуратным - в cacheLite мы храним данные и подпись, а в local CACHE только данные
        PsProfiler::inst(__CLASS__)->start('SAVE to cache engine');
        $this->CACHE_ENGINE->saveToCache($CACHED, $id, $group);
        PsProfiler::inst(__CLASS__)->stop();

        //Перенесём данные в локальный кеш для быстрого доступа
        $this->CACHE_LOCAL[$group][$id] = $CACHED;

        return $object;
    }

    /**
     * Метод очищает кеш или определённую группу кешей
     * 
     * @param string|null $group - код группы, которую нужно очистить
     */
    public function cleanCache($group = null) {
        if ($group === null) {
            /*
             * Полная очистка кеша
             */
            $this->LOGGER->info('--- Полная очистка кеша');

            $this->CACHE_LOCAL = array();
        } else {
            $group = PsCheck::notEmptyString($group);
            /*
             * Полная очистка кеша
             */
            $this->LOGGER->info("-- Очистка кеша по группе [$group]");

            unset($this->CACHE_LOCAL[$group]);
        }

        $this->CACHE_ENGINE->cleanCache($group);
    }

    /**
     * Конструктор класса для работы с кешем.
     * Кеширование идёт в два этапа:
     * 1. Кеширование на уровне класса для максимально быстрого доступа
     * 2. Кеширование в долгосрочном хранилище, которое реализуется отдельным классом - "движком" кеширования
     * 
     * Движок кеширования должен быть задан на уровне config.ini
     * 
     * @return PSCache
     */
    protected function __construct() {
        $this->LOGGER = PsLogger::inst(__CLASS__);

        /*
         * Получим название класса "движка" кеширования
         */
        $class = ConfigIni::cacheEngine();

        /*
         * Проверим наличие класса
         */
        $classPath = Autoload::inst()->getClassPath($class);
        if (!PsCheck::isNotEmptyString($classPath)) {
            return PsUtil::raise('Не удалось найти класс для кеширования [{}]', $class);
        }

        /*
         * Правильный ли класс указан?
         */
        if (!PsUtil::isInstanceOf($class, 'PSCacheEngine')) {
            return PsUtil::raise('Указанный класс кеширования [{}] не является наследником класса [{}]', $class, 'PSCacheEngine');
        }

        $this->LOGGER->info('Используем движок кеширования: {}', $class);
        $this->CACHE_ENGINE = new $class();
    }

    /** @return PSCache */
    public static function inst() {
        return parent::inst();
    }

}

?>