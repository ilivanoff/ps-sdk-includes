<?php

/**
 * Класс содержит утилитные методы для получения разной информации о классах
 *
 * @author azaz
 */
class PsReflect {

    /**
     * Названия аннотация
     */
    const ANN_PARAM = 'param';

    /**
     * Метод загружает описание метода в классе
     * 
     * @param mixed $class - название класса или объект
     * @param string $method - название метода
     */
    public static function describeMethod($ClassName, $MethodName) {
        $RM = new ReflectionMethod($ClassName, $MethodName);

        $ann = array();

        $INFO['name'] = $MethodName;
        $INFO['descr'] = implode("\n", StringUtils::parseMultiLineComments($RM->getDocComment(), $ann));
        $INFO['params'] = array();

        $params = array();
        foreach ($ann as $key => $value) {
            if ($key != self::ANN_PARAM) {
                continue; //---
            }
            //mixed $class - название класса или объект
            $tokens = explode(' ', $value);
            foreach ($tokens as $token) {
                if (starts_with($token, '$')) {
                    $paramName = trim(first_char_remove($token));
                    if ($paramName) {
                        $params[$paramName] = trim(array_get_value(1, explode('-', $value, 2)));
                    }
                }
            }
        }

        /* @var $param ReflectionParameter */
        foreach ($RM->getParameters() as $param) {
            $pname = $param->getName();
            $INFO['params'][$pname] = array(
                'name' => $pname,
                'dflt' => $param->isDefaultValueAvailable() ? var_export($param->getDefaultValue(), true) : null,
                'descr' => array_get_value($pname, $params)
            );
        }

        return $INFO;
    }

}
