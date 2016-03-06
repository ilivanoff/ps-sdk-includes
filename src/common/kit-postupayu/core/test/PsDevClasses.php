<?php

/**
 * Менеджер классов, работающих только в девелоперском режиме
 *
 * @author azazello
 */
final class PsDevClasses {

    /** Список методов, которые можно вызывать */
    private static $METHODS;

    /**
     * Список методов, доступных для вызова
     */
    public static function getMethodsList() {
        if (is_array(self::$METHODS)) {
            return self::$METHODS; //---
        }

        //Проверяем доступ админа
        AuthManager::checkAdminAccess();

        //Проверим, что это девелоперский режим
        PsDefines::assertProductionOff(__CLASS__);

        //Пробегаемся по всем настроенным классам и выбираем их 'public static funal' методы (сначала отдадим проектные методы)
        foreach (array_reverse(ConfigIni::devClasses()) as $devClassName) {
            self::$METHODS[$devClassName] = array();

            foreach (PsUtil::getClassMethods($devClassName, true, true, true, true) as $methodName) {
                $method = new ReflectionMethod($devClassName, $methodName);

                $params['name'] = $methodName;
                $params['descr'] = implode("\n", StringUtils::parseMultiLineComments($method->getDocComment()));
                $params['params'] = array();

                /* @var $param ReflectionParameter */
                foreach ($method->getParameters() as $param) {
                    $params['params'][] = array(
                        'name' => $param->getName(),
                        'dflt' => $param->isDefaultValueAvailable() ? var_export($param->getDefaultValue(), true) : null
                    );
                }

                self::$METHODS[$devClassName][$methodName] = $params;
            }
        }

        return self::$METHODS;
    }

    /**
     * Метод проверяет существование девелоперского класса и метода в нём
     * 
     * @param string $class - название класса
     * @param string $method - название метода
     * @return bool
     */
    private static function hasMethod($class, $method) {
        return is_array(array_get_value_in(array($class, $method), self::getMethodsList()));
    }

    /**
     * Вызов выполнения метода. Используется из ajax.
     */
    public static function execute($class, $method, array $params) {
        if (!self::hasMethod($class, $method)) {
            return PsUtil::raise('Метод {}::{} не зарегистрирован', $class, $method);
        }

        PsUtil::startUnlimitedMode();

        PsLogger::inst('PsDevClasses')->info('Method {}::{} called with params: {}', $class, $method, array_to_string($params));

        $s = Secundomer::startedInst();
        call_user_func_array(array($class, $method), $params);
        $s->stop();

        PsLogger::inst('PsDevClasses')->info("Call done in {$s->getTotalTime()} seconds");
    }

}

?>
