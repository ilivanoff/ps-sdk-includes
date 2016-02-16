<?php

/**
 * В системе возможны два типа настроек:
 * 1. Константы, определяемые через define. Они живут в двух файлах - Globals.php и Defines.php.
 * 2. Глобальные переменные, которые потом импортируютися в функции через global.
 * 
 * Данный класс предоставляет информацию о всех настройках сразу. При этом мы можем переопределить 
 * значение любой настройки, заданной с помощью одного из описанных выше свойств.
 * 
 * Приоритет настроек, при этом, будет следующий:
 * 
 * 1. Значения, установленные с помощью {@link PsDefines::set}.
 * 2. Глобальная переменная (импортируемая через global).
 * 3. Константа, определяемая через define.
 * 
 * Данное поведение позволит нам гибко настраивать поведение системы. Если нам нужно форсированно заставить 
 * определённый участок кода работать с заданными настройками (например - обработать пост без замены формул на картинки),
 * мы просто устанавливаем это свойство с помощью метода {@link PsDefines::set}.
 * 
 * Или, если нам нужно форсированно отключить логирование (как делается для админских страниц),
 * мы можем определить глобальную переменную $LOGGING_ENABLED = false и она перекроет значение константы LOGGING_ENABLED.
 * 
 * Допустимые глобальные свойства:
 * 
 * LOGGING_ENABLED   - включено ли логирование
 * LOGGING_STREAM    - направление потока логирования
 * LOGGERS_LIST      - список разрещённых логгеров
 * PROFILING_ENABLED - включено ли профилирование
 * 
 * @author azazello
 */
abstract class PsDefinesEngine {

    const TYPE_G = 1;  //Global  ($GLOBAL)
    const TYPE_D = 2;  //Defines (define('xxx', 1))
    const TYPE_GD = 3; //Global or Defines

    /**
     * Метод извлекает настройку из заданного контекста, проверяя её тип.
     * 
     * @param string $name - название настройки
     * @param int $ctxt - контекст поиска
     * @param str $phpType - тип переменной
     * @param mixed $default - значение по умолчанию
     */

    private static final function extract($name, $ctxt, $phpType, $default) {
        $name = PsCheck::notEmptyString($name);
        $value = null;

        switch ($ctxt) {
            case self::TYPE_G:
                $value = array_get_value($name, $GLOBALS, $default);
                break; //---
            case self::TYPE_D:
                $value = defined($name) ? constant($name) : $default;
                break; //---
            case self::TYPE_GD:
                if (array_key_exists($name, $GLOBALS)) {
                    $value = $GLOBALS[$name];
                } else if (defined($name)) {
                    $value = constant($name);
                } else {
                    $value = $default;
                }
                break; //---
            default:
                return raise_error("Illegal property search ctxt: [$ctxt].");
        }

        return PsCheck::phpVarType($value, array(PsConst::PHP_TYPE_NULL, $phpType));
    }

    /**
     * Переопределённые настройки.
     * Можно устанавливать несколько раз, будет взята последняя.
     */
    private static $DEFINES = array();

    /**
     * Метод переопределения настройки.
     * 
     * @return PsDefines
     */
    public static final function set($name, $newVal, $ctxt, $phpType, $default) {
        self::extract($name, $ctxt, $phpType, $default); //Для валидации
        self::$DEFINES[$name][] = PsCheck::phpVarType($newVal, array(PsConst::PHP_TYPE_NULL, $phpType));
        self::notifySavepoint($name, true);
    }

    /**
     * Метод восстановления значения ранее переопределённой настройки
     * 
     * @return PsDefines
     */
    public static final function restore($name) {
        check_condition(array_key_exists($name, self::$DEFINES), "Property [$name] cannot be restored, it wasn`t setted before.");
        array_pop(self::$DEFINES[$name]);
        if (empty(self::$DEFINES[$name])) {
            unset(self::$DEFINES[$name]);
        }
        self::notifySavepoint($name, false);
    }

    /**
     * Метод получения настройки
     */
    public static final function get($name, $ctxt, $phpType, $default) {
        return array_key_exists($name, self::$DEFINES) ? end(self::$DEFINES[$name]) : self::extract($name, $ctxt, $phpType, $default);
    }

    /*
     * ТОЧКИ СОХРАНЕНИЯ (SAVEPOINTS)
     */

    private static $SAVEPOINT = 0;
    private static $SAVEPOINT_PROPS = array();

    /**
     * Устанавливает точку сохранения.
     * Все изменения, сделанные после этого, будут разом откачены посредством вызова {@link PsDefines::savepointRestore()}
     * 
     * Всё работает довольно просто - с начала установки точки сохранения мы начинаем считать,
     * сколько раз свойство было установлено. После отката точки сохранения мы откатим каждое
     * свойство столько раз, сколько его устанавливали с момента установки точки сохранения.
     * 
     * @return PsDefines
     */
    public static final function savepointStart() {
        self::$SAVEPOINT_PROPS[++self::$SAVEPOINT] = array();
    }

    /**
     * Отказывает все изменения, следанные с момента установки последней точки сохранения.
     * 
     * @return PsDefines
     */
    public static final function savepointRestore() {
        check_condition(self::$SAVEPOINT > 0, 'Savepoint is not started');
        /*
         * Этапы очистки сейвпоинта:
         * 1. Загружаем все сдвиги настроек, которые мы успели сделать в этом сейвпоинте
         * 2. Очищаем сейвпоинт, чтобы не реагировать на вызов метода notifySavepoint
         * 3. Вызываем restore для каждой настройки столько раз, сколько она была установлена в этом сейвпоинте
         */
        $params = self::$SAVEPOINT_PROPS[self::$SAVEPOINT];
        unset(self::$SAVEPOINT_PROPS[self::$SAVEPOINT]);

        foreach ($params as $name => $cnt) {
            while ($cnt) {
                self::restore($name);
                --$cnt;
            }
        }
        --self::$SAVEPOINT;
    }

    private static function notifySavepoint($name, $set) {
        /*
         * Мы проверяем, установлен ли сейчас savepoint. Он может быть не установлен в двух случаях:
         * 1. Его действительно никто не устанавливал.
         * 2. Мы начали очистку состояния точки сохранения и нас не нужно лишний раз нотифицировать.
         */
        if (!array_key_exists(self::$SAVEPOINT, self::$SAVEPOINT_PROPS)) {
            return; //---
        }
        $valueSavepointOffset = array_get_value($name, self::$SAVEPOINT_PROPS[self::$SAVEPOINT], 0) + ($set ? 1 : -1);
        check_condition($valueSavepointOffset >= 0, "Property [$name] more restored then setted in savepoint " . self::$SAVEPOINT);
        self::$SAVEPOINT_PROPS[self::$SAVEPOINT][$name] = $valueSavepointOffset;
    }

}

?>