<?php

/**
 * Необходимо наследовать данный класс для определения плагинов для Smarty.
 * Регистрация плагинов происходит в конструкторе.
 */
abstract class AbstractSmartyPlugin {

    //Плагины
    private $PLUGINS = null;

    private function raiseNotRealised($__CLASS__, $__FUNCTION__, $tagName) {
        PsUtil::raise("Не реализована функиця $__CLASS__->$__FUNCTION__, при этом она вызвана для тега $tagName");
    }

    protected function do_block($tagName, $params, $content, Smarty_Internal_Template $smarty) {
        $this->raiseNotRealised(__CLASS__, __FUNCTION__, tagName);
    }

    protected function do_function($tagName, $params, Smarty_Internal_Template $smarty) {
        $this->raiseNotRealised(__CLASS__, __FUNCTION__, tagName);
    }

    protected function do_modifier($tagName) {
        $this->raiseNotRealised(__CLASS__, __FUNCTION__, tagName);
    }

    /**
     * Метод регистрирует плагины
     */
    public final function getPlugins() {
        if (!is_array($this->PLUGINS)) {
            $this->PLUGINS = array();
            $this->registerPluginsImpl();
        }
        return $this->PLUGINS;
    }

    /**
     * Метод вызывается для фактической регистрации плагинов
     */
    protected abstract function registerPluginsImpl();

    /**
     * Методы, позволяющие зарегистрировать плагины.
     * 
     * @param string $tagName - название тега для вызова
     * @param string $pluginType - тип плагина
     */
    protected final function register($tagName, $pluginType) {
        check_condition(is_array($this->PLUGINS), 'Регистрация функций Smarty допускается только в методе #registerPluginsImpl');
        $this->PLUGINS[PsCheck::notEmptyString($tagName)] = PSSmartyTools::checkFunctionType($pluginType);
    }

    /**
     * Метод регистрирует блочные функции
     * 
     * @param string $tagName - название тега для вызова
     */
    protected final function registerBlock($tagName) {
        $this->register($tagName, Smarty::PLUGIN_BLOCK);
    }

    /**
     * Метод регистрирует функцию
     * 
     * @param string $tagName - название тега для вызова
     */
    protected final function registerFunction($tagName) {
        $this->register($tagName, Smarty::PLUGIN_FUNCTION);
    }

    /**
     * Метод регистрирует модификатор
     * 
     * @param string $tagName - название тега для вызова
     */
    protected final function registerModifier($tagName) {
        $this->register($tagName, Smarty::PLUGIN_MODIFIER);
    }

    /**
     * Метод регистрирует плагины смарти для вызова
     * 
     * @param Smarty $smarty
     * @param PsLoggerInterface $LOGGER
     */
    public final function bind(Smarty $smarty, PsLoggerInterface $LOGGER) {
        foreach ($this->getPlugins() as $tagName => $pluginType) {
            $needAdd = !array_key_exists($pluginType, $smarty->registered_plugins) || !array_key_exists($tagName, $smarty->registered_plugins[$pluginType]);
            if ($needAdd) {
                $smarty->registerPlugin($pluginType, $tagName, array($this, 'do_' . $pluginType . '_' . $tagName));
            }
            $LOGGER->info('   [{}] {} {}', $pluginType, 'smarty_' . $pluginType . '_' . $tagName, $needAdd ? '' : ' (NOT ADDED)');
        }
    }

    /**
     * Метод вызывается для запуска метода плагина
     * 
     * @param type $name
     * @param type $arguments
     * @return type
     */
    public final function __call($name, $arguments) {
        $tokens = explode('_', $name, 3); //0-do, 1-$pluginType, 2-$tagName

        check_condition('do' == $tokens[0], 'Illegal to call ' . get_called_class() . '::' . $name);

        $pluginType = $tokens[1];
        $tagName = $tokens[2];

        if (method_exists($this, $tagName)) {
            //Метод, совпадающий с названием тега, есть в классе. Просто вызовем его.
            return call_user_func_array(array($this, $tagName), $arguments);
        } else {
            //Метода нет, вызываем do_...
            //К параметрам в начало добавим название тега.
            array_unshift($arguments, $tagName);
            return call_user_func_array(array($this, "do_$pluginType"), $arguments);
        }
    }

}

?>