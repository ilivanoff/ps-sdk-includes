<?php

/**
 * Глобальные переменные системы
 *
 * @author azazello
 */
final class PsDefineVar extends PsEnum {

    /** @return PsDefineVar */
    public static final function REPLACE_FORMULES_WITH_IMG() {
        return self::inst(PsDefines::TYPE_G, PsConst::PHP_TYPE_BOOLEAN, true);
    }

    /** @return PsDefineVar */
    public static final function REPLACE_FORMULES_WITH_SPRITES() {
        return self::inst(PsDefines::TYPE_G, PsConst::PHP_TYPE_BOOLEAN, true);
    }

    /** @return PsDefineVar */
    public static final function LOGGING_ENABLED() {
        return self::inst(PsDefines::TYPE_G, PsConst::PHP_TYPE_BOOLEAN, ConfigIni::isLoggingEnabled());
    }

    /** @return PsDefineVar */
    public static final function LOGGING_STREAM() {
        return self::inst(PsDefines::TYPE_G, PsConst::PHP_TYPE_INTEGER, PsLogger::OUTPUT_FILE);
    }

    /** @return PsDefineVar */
    public static final function LOGGERS_LIST() {
        return self::inst(PsDefines::TYPE_G, PsConst::PHP_TYPE_ARRAY);
    }

    /** @return PsDefineVar */
    public static final function PROFILING_ENABLED() {
        return self::inst(PsDefines::TYPE_G, PsConst::PHP_TYPE_BOOLEAN, ConfigIni::isProfilingEnabled());
    }

    /** @return PsDefineVar */
    public static final function NORMALIZE_PAGE() {
        return self::inst(PsDefines::TYPE_G, PsConst::PHP_TYPE_BOOLEAN, ConfigIni::isNormalizePage());
    }

    /**
     * Контекст, в котором можно производить поиск переопределения константы
     * @var string
     */
    private $ctxt;

    /**
     * Тип php переменной, к которой должна относиться переменная. Всегда может быть null.
     * @var string
     */
    private $phpType;

    /**
     * Значение по умолчанию
     * @var mixed
     */
    private $default;

    protected function init($ctxt = null, $phpType = null, $default = null) {
        $this->ctxt = $ctxt;
        $this->phpType = $phpType;
        $this->default = $default;
    }

    public function set($val) {
        PsDefines::set($this->name(), $val, $this->ctxt, $this->phpType, $this->default);
    }

    public function get() {
        return PsDefines::get($this->name(), $this->ctxt, $this->phpType, $this->default);
    }

}

?>