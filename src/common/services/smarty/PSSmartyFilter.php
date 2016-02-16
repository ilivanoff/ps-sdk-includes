<?php

/**
 * Класс фильтра для Smarty.
 * Может быть переопределён в config.ini
 */
class PSSmartyFilter {

    private $CALLTTL = 0;

    /**
     * Метод вызывается для привязки фильтра к Smarty
     * 
     * @param Smarty $smarty
     */
    public final function bind(Smarty $smarty) {
        $smarty->registerFilter(Smarty::FILTER_PRE, array($this, 'preCompile'));
        $smarty->registerFilter(Smarty::FILTER_POST, array($this, 'postCompile'));
        $smarty->registerFilter(Smarty::FILTER_OUTPUT, array($this, 'output'));
    }

    /**
     * Метод вызывается до компиляции макета (построения php-файла).
     * 
     * @param string $source - "сырой" код шаблона
     * @param Smarty_Internal_Template $template
     */
    public final function preCompile($source, Smarty_Internal_Template $template) {
        $this->onBeforeCall(Smarty::FILTER_PRE, $template);
        return $this->preCompileImpl($source, $template);
    }

    /**
     * Метод вызывается до компиляции макета (построения php-файла).
     * Может быть переопределён в наследнике.
     * 
     * @param string $source - "сырой" код шаблона
     * @param Smarty_Internal_Template $template
     */
    protected function preCompileImpl($source, Smarty_Internal_Template $template) {
        $source = str_replace('\(', '{literal}\(', $source);
        $source = str_replace('\)', '\){/literal}', $source);

        $source = str_replace('\{', '{literal}\{', $source);
        $source = str_replace('\}', '\}{/literal}', $source);

        $source = str_replace('\[', '{literal}\[', $source);
        $source = str_replace('\]', '\]{/literal}', $source);

        $source = PsStrings::pregReplaceCyclic('/\$\$/', $source, array('{literal}$$', '$${/literal}'));

        //Обернём математический текст, например: &alpha; перейдёт в <span class="math_text">&alpha;</span>
        $source = TextFormulesProcessor::replaceMathText($source);

        //Заменим некоторые блоки на вызов методов данного класса
        $source = SmartyReplacesIf::preCompile($source);

        return $source;
    }

    /**
     * Вызывается после компиляции макета (построения php-файла).
     * 
     * @param string $source - "сырой" код шаблона
     * @param Smarty_Internal_Template $template
     */
    public final function postCompile($source, Smarty_Internal_Template $template) {
        $this->onBeforeCall(Smarty::FILTER_POST, $template);
        return $this->postCompileImpl($source, $template);
    }

    /**
     * Вызывается после компиляции макета (построения php-файла).
     * Может быть переопределён в наследнике.
     * 
     * @param string $source - "сырой" код шаблона
     * @param Smarty_Internal_Template $template
     */
    protected function postCompileImpl($source, Smarty_Internal_Template $template) {
        return $source;
    }

    /**
     * Вызывается после компиляции и выполнения макета, но до показа пользователю.
     * ВАЖНО! Функция вызывается довольно часто, поэтому должна работать максимально быстро.
     * 
     * @param string $source - "сырой" код шаблона
     * @param Smarty_Internal_Template $template
     */
    public final function output($source, Smarty_Internal_Template $template) {
        $this->onBeforeCall(Smarty::FILTER_OUTPUT, $template);
        return $this->outputImpl($source, $template);
    }

    /**
     * Вызывается после компиляции макета (построения php-файла).
     * Может быть переопределён в наследнике.
     * 
     * @param string $source - "сырой" код шаблона
     * @param Smarty_Internal_Template $template
     */
    protected function outputImpl($source, Smarty_Internal_Template $template) {
        if (PsDefines::isReplaceFormulesWithImages()) {
            return TexImager::inst()->replaceInText($source);
        }
        return $source;
    }

    /**
     * Метод вызывается перед вызовом метода имплементации
     * 
     * @param type $filterType
     * @param Smarty_Internal_Template $template
     */
    private function onBeforeCall($filterType, Smarty_Internal_Template $template) {
        if (PsLogger::isEnabled()) {
            PsLogger::inst(__CLASS__)->info("{} {}.{}({})", pad_right( ++$this->CALLTTL . '.', 3, ' '), get_called_class(), $filterType, $template->template_resource);
        }
    }

    /** @var PSSmartyFilter */
    private static $inst;

    /**
     * Метод возвращает экземпляр класса-фильтра Smarty.
     * Для переопределения этого класса, на уровне проектного config.ini
     * должен быть задан другой класс.
     * 
     * Это позволит:
     * 1. Использовать стандартизованный метод фильтрации
     * 2. Переопределить стандартизованный метод фильтрации
     */
    public static final function inst() {
        if (isset(self::$inst)) {
            return self::$inst; //----
        }

        /*
         * Получим название класса
         */
        $class = ConfigIni::smartyFilter();

        /*
         * Класс подключения библиотек совпадает с базовым
         */
        if (__CLASS__ == $class) {
            return self::$inst = new PSSmartyFilter();
        }

        /*
         * Нам передан класс, который отличается от SDK
         */
        $classPath = Autoload::inst()->getClassPath($class);
        if (!PsCheck::isNotEmptyString($classPath)) {
            return PsUtil::raise('Не удалось найти класс фильтра Smarty [{}]', $class);
        }

        /*
         * Указанный класс должен быть наследником данного
         */
        if (!PsUtil::isInstanceOf($class, __CLASS__)) {
            return PsUtil::raise('Указанный фильтр Smarty [{}] не является наследником класса [{}]', $class, __CLASS__);
        }

        return self::$inst = new $class();
    }

}

?>