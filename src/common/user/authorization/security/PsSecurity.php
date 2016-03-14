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

        /*
         * Сбрасываем провайдер (что также приведёт и к логированию)
         */
        self::providerReset();
    }

    /**
     * Метод проверяет состояние
     */
    private static function assertState($isInited, $hasProvider) {
        PsUtil::assert($isInited === self::$inited, self::$inited ? '{} is already initialized' : '{} is not initialized yet', __CLASS__);
        PsUtil::assert($hasProvider === !is_null(self::$provider), !!self::$provider ? '{} provider is already setted' : '{} provider is not setted yet', __CLASS__);
    }

    /**
     * Метод устанавливает провайдер безопасности
     * На момент установки провайдера не должен быть вызван метод #init() и другой провайдер не должен быть ранее установлен
     */
    public static function set(PsSecurityProvider $provider) {
        self::assertState(false, false);
        self::$provider = $provider;
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
        self::assertState(true, true);
        return self::$provider;
    }

    /**
     * Метод проверяет, используем ли мы базовый провайдер безопасности
     */
    public static function isBasic() {
        return self::provider() instanceof PsSecurityProviderSdk;
    }

    /**
     * Метод сбрасывает состояние провайдера безопасности.
     * 
     * @return PsSecurityProvider
     */
    public static final function providerReset() {
        self::provider()->reset();

        $LOGGER = PsLogger::inst(__CLASS__);
        if ($LOGGER->isEnabled()) {
            $LOGGER->info();
            $LOGGER->info('Context:       {}', PsContext::describe());
            $LOGGER->info('Provider:      {}', get_class(self::$provider));
            $LOGGER->info('Is authorized: {}', var_export(self::$provider->isAuthorized(), true));
            $LOGGER->info('Is admin:      {}', var_export(self::$provider->isAuthorizedAsAdmin(), true));
            $LOGGER->info('User ID:       {}', self::$provider->getUserId());
        }
    }

    /**
     * Конструктор может быть переопределён и в нём должна быть выполнена вся работа
     */
    private function __construct() {
        
    }

}

?>