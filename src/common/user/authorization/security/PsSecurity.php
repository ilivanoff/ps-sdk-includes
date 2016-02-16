<?php

/**
 * Класс является хранилищем ссылки на актуальный провайдер безопасности.
 * Провайдер может быть установлен в рабочим окружении (см. PsEnvironment).
 * 
 * @author azaz
 */
final class PsSecurity {

    /** @var PsSecurityProvider */
    private static $provider = null;

    /** Признак проинициализированности */
    private static $inited = false;

    /**
     * Метод убеждается в том, что провайдер безопасности установлен.
     * Наша задача - установить провайдер один раз и проверить, чтобы он больше не менялся.
     */
    public static function init() {
        self::$inited = check_condition(!self::$inited, 'Cannot initialize ' . __CLASS__ . ' twice');

        if (self::$provider instanceof PsSecurityProvider) {
            //Провайдер безопастности был уже установлен
        } else {
            check_condition(is_null(self::$provider), __CLASS__ . ' is not correctly initialized');

            if (PsContext::isCmd()) {
                //Если работаем под процессом - установим специальный провайдер безопастности
                self::$provider = new PsSecurityProviderCmd();
            } else {
                //Устанавливаем базовый провайдер безопасности на основе сессии
                self::$provider = new PsSecurityProviderSdk();
            }
        }

        check_condition(!PsContext::isCmd() || self::$provider instanceof PsSecurityProviderCmd, 'Invalid security provider for cmd process');

        $LOGGER = PsLogger::inst(__CLASS__);
        if ($LOGGER->isEnabled()) {
            $LOGGER->info('Context:       {}', PsContext::describe());
            $LOGGER->info('Provider:      {}', get_class(self::$provider));
            $LOGGER->info('Is authorized: {}', var_export(self::$provider->isAuthorized(), true));
            $LOGGER->info('Is admin:      {}', var_export(self::$provider->isAuthorizedAsAdmin(), true));
            $LOGGER->info('User ID:       {}', self::$provider->getUserId());
        }
    }

    /**
     * Метод устанавливает провайдер безопасности
     */
    public static function set(PsSecurityProvider $provider) {
        check_condition(!self::$inited, __CLASS__ . ' is already initialized');
        check_condition(is_null(self::$provider), __CLASS__ . ' provider is already setted');
        self::$provider = $provider;
    }

    /**
     * Метод проверяет, используем ли мы базовый провайдер безопасности
     */
    public static function isBasic() {
        return self::$provider instanceof PsSecurityProviderSdk;
    }

    /**
     * Метод возвращает экземпляр класса, отвечающего за вопросы авторизации.
     * Для переопределения этого класса, на уровне проектного config.ini
     * должен быть задан другой класс.
     * 
     * Это позволит:
     * 1. Использовать сторонний механизм авторизации и регистрации пользователей
     * 
     * @return PsSecurityProvider
     */
    public static final function provider() {
        return self::$provider ? self::$provider : raise_error('Class ' . __CLASS__ . ' is not initialized');
    }

    /**
     * Конструктор может быть переопределён и в нём должна быть выполнена вся работа
     */
    private function __construct() {
        
    }

}

?>