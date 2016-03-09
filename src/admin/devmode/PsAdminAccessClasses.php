<?php

/**
 * Менеджер классов, работающих только в девелоперском режиме
 *
 * @author azazello
 */
final class PsAdminAccessClasses {

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

        //Составим список методов
        $CLASSES[ConfigIni::GROUP_ADMIN_ACCESS_METHODS_ALL] = ConfigIni::adminAccessMethods(ConfigIni::GROUP_ADMIN_ACCESS_METHODS_ALL);
        //Если девелоперский режим - добавим методы девелоперского режима
        if (PsDefines::isDevmode()) {
            $CLASSES[ConfigIni::GROUP_ADMIN_ACCESS_METHODS_DEV] = ConfigIni::adminAccessMethods(ConfigIni::GROUP_ADMIN_ACCESS_METHODS_DEV);
        }

        //Пробегаемся по всем настроенным классам и выбираем их 'public static funal' методы
        foreach ($CLASSES as $Type => $classesArr) {
            //Сначала отдадим проектные методы
            foreach (array_reverse($classesArr) as $ClassName) {
                self::$METHODS[$Type][$ClassName] = array();

                foreach (PsUtil::getClassMethods($ClassName, true, true, true, true) as $MethodName) {
                    self::$METHODS[$Type][$ClassName][$MethodName] = PsReflect::describeMethod($ClassName, $MethodName);
                }
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
    private static function hasMethod($type, $class, $method) {
        return is_array(array_get_value_in(array($type, $class, $method), self::getMethodsList()));
    }

    /**
     * Вызов выполнения метода. Используется из ajax.
     */
    public static function execute($type, $class, $method, array $params) {
        //Проверим админские прова доступа
        AuthManager::checkAdminAccess();

        if (!self::hasMethod($type, $class, $method)) {
            return PsUtil::raise('Метод {}::{} (контекст {}) не зарегистрирован', $class, $method, $type);
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
