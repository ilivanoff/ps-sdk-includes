<?php

final class PSSmarty extends AbstractSingleton {

    /** @var Smarty */
    private $smarty;

    protected function __construct() {
        PsLibs::inst()->Smarty();

        /*
         * Начиная с версии 5.4 в функции htmlentities параметр encoding был изменён на UTF-8, 
         * до этого момента после применения данного метода к тексту шаблона мы будем получать кракозябру.
         */
        SmartyCompilerException::$escape = is_phpver_is_or_greater(5, 4);

        //Получим и сконфигурируем экземпляр Smarty
        $this->smarty = new Smarty();
        $this->smarty->compile_check = true;
        $this->smarty->force_compile = false;
        //$this->smarty->caching = TRUE;

        /*
         * УПРАВЛЯЮЩИЕ ДИРЕКТОРИИ
         */

        //Директории с шаблонами .tpl : PSSmarty::template('common/citata.tpl');
        $this->smarty->setTemplateDir(ConfigIni::smartyTemplates());

        //Директория, в которую складываются скомпилированные шаблоны
        $this->smarty->setCompileDir(DirManager::autogen('/smarty/templates_c/')->absDirPath());

        //Директория, в которую складываются кеши
        $this->smarty->setCacheDir(DirManager::autogen('/smarty/cache/')->absDirPath());

        //Директория с конфигами
        $this->smarty->setConfigDir(PATH_BASE_DIR . PS_DIR_INCLUDES . '/smarty/configs/');

        //Директории с плагинами - блочными функциями, функциями, модификатор
        $this->smarty->addPluginsDir(ConfigIni::smartyPlugins());

        /*
         * Импортируем константы некоторых классов, чтобы на них можно было ссылаться через 
         * {$smarty.const.CONST_NAME}
         */
        //PsConstJs::defineAllConsts();

        /*
         * ПОДКЛЮЧИМ ФИЛЬТРЫ
         */
        PSSmartyFilter::inst()->bind($this->smarty);

        /*
         * ПОДКЛЮЧАЕМ ПЛАГИНЫ
         */
        PSSmartyPlugin::inst()->bind($this->smarty);
    }

    /** @return Smarty */
    public static function smarty() {
        return parent::inst()->smarty;
    }

    /** @return Smarty_Internal_Template */
    public static function template($path, $data = null) {
        return self::smarty()->createTemplate($path instanceof DirItem ? $path->getAbsPath() : $path, $data);
    }

}

?>