<?php

/**
 * Класс для работы с глобальными настройками, задаваемыми в файле
 * Globals.php, путь к которому указывается в config.ini [project-includes].
 * 
 * Для максимально быстрой работы мы храним глобальные настройки именно в виде
 * констант php.
 */
final class PsGlobals extends AbstractSingleton {

    /** @var DirItem */
    private $DI;

    /** @var array */
    private $GLOBALS;

    /** Время последней модификации файла глобальных настроек */
    private $FileMtime;

    /**
     * Обновление $FileMtime. Производится:
     * 1. До загрузки настроек из файла.
     * 2. После сохранения настроек в файл
     */
    private function FileMtimeUpdate() {
        $this->FileMtime = $this->DI->getModificationTime();
    }

    /**
     * Метод проверяет, существует ли файл глобальных настроек
     */
    public function exists() {
        return !!$this->FileMtime;
    }

    /**
     * Метод проверяет существование файла файла глобальных настроек
     */
    private function assertExists() {
        check_condition($this->exists(), 'Файл глобальных настроек не существует');
    }

    /**
     * Возвращает список глобальных свойств
     */
    public function getProps() {
        if (!is_array($this->GLOBALS)) {
            $this->assertExists();
            $this->GLOBALS = array();
            $this->FileMtimeUpdate();

            $comment = array();
            foreach ($this->DI->getFileLines() as $line) {
                $line = trim($line);
                if (!$line || starts_with($line, '/*') || ends_with($line, '*/')) {
                    continue;
                }
                if (starts_with($line, '*')) {
                    $line = trim(first_char_remove($line));
                    if ($line) {
                        $comment[] = $line;
                    }
                    continue;
                }
                if (starts_with($line, 'define')) {
                    $name = trim(array_get_value(1, explode("'", $line, 3)));
                    check_condition($name && defined($name), "Ошибка разбора файла глобальных настроек: свойство [$name] не определено.");
                    $this->GLOBALS[$name] = new PsGlobalProp($name, implode(' ', $comment));
                    $comment = array();
                    continue;
                }
            }
        }
        return $this->GLOBALS;
    }

    /**
     * Возвращает глобальные настройки в виде массива ключ-значение
     */
    public function getPropsKeyValue() {
        $result = array();
        /* @var $prop PsGlobalProp */
        foreach ($this->getProps() as $name => $prop) {
            $result[$name] = $prop->getValue();
        }
        return $result;
    }

    /**
     * @return PsGlobalProp
     */
    public function getProp($name) {
        return check_condition(array_get_value($name, $this->getProps()), "Глобальная настройка [$name] не зарегистрирована");
    }

    /**
     * Проверяет, есть ли модифицированные свойства
     */
    private function hasModified() {
        /* @var $prop PsGlobalProp */
        foreach ($this->getProps() as $prop) {
            if ($prop->isDearty()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Возсращает содержимое Globals.php, готовое к сохранению в файл
     */
    public function getPhpFileContents() {
        $content = "<?php\n\n";
        /* @var $prop PsGlobalProp */
        foreach ($this->getProps() as $prop) {
            $content .= $prop->getFileBlock();
            $content .= "\n";
        }
        $content .= '?>';
        return $content;
    }

    /**
     * Сохраняет глобальные настройки в Globals.php
     */
    public function save2file() {
        if (!$this->hasModified()) {
            return; //--- Нет модифицированных свойств
        }
        $content = $this->getPhpFileContents();
        check_condition($this->FileMtime === $this->DI->getModificationTime(), 'Файл глобальных настроек был изменён с момента загрузки');
        $this->DI->putToFile($content);
        $this->FileMtimeUpdate();

        //"Коммитим" настройки
        /* @var $prop PsGlobalProp */
        foreach ($this->getProps() as $prop) {
            $prop->commit();
        }
    }

    /**
     * Обновляет глобальные настройки из файла.
     * Автоматически производится сохранение настроек в файл.
     */
    public function updateProps(array $globals) {
        foreach ($globals as $name => $value) {
            $this->getProp($name)->setValue($value);
        }
        $this->save2file();
    }

    /** @return PsGlobals */
    public static function inst() {
        return parent::inst();
    }

    protected function __construct() {
        $this->DI = DirItem::inst(ConfigIni::projectGlobalsFilePath(), null, PsConst::EXT_PHP);
        $this->FileMtimeUpdate();
    }

    /**
     * Метод подключает файл глобальных настроек
     */
    public static function init() {
        if (ConfigIni::isProject() && self::inst()->exists()) {
            require_once self::inst()->DI->getAbsPath();
        }
    }

}

?>