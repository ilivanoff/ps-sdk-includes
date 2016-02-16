<?php

final class Handlers {

    private $foldings = array();
    private $libs = array();
    private $bubbles = array();
    private $panels = array();

    private function __construct() {
        PsProfiler::inst(__CLASS__)->start(__FUNCTION__);

        //Фолдинги
        $this->foldings[] = PopupPagesManager::inst();
        $this->foldings[] = PluginsManager::inst();
        $this->foldings[] = TimeLineManager::inst();
        $this->foldings[] = UserPointsManager::inst();
        $this->foldings[] = StockManager::inst();
        $this->foldings[] = HelpManager::inst();
        $this->foldings[] = EmailManager::inst();
        $this->foldings[] = PSForm::inst();
        $this->foldings[] = DialogManager::inst();
        //Библиотеки
        $this->foldings[] = PoetsManager::inst();
        $this->foldings[] = ScientistsManager::inst();
        //Админские страницы
        $this->foldings[] = APagesResources::inst();

        /*
         * Выделим различные подклассы фолдингов
         */
        foreach ($this->foldings as $folding) {
            //Фолдинги библиотек
            if ($folding instanceof LibResources) {
                $this->libs[] = $folding;
            }
            //Фолдинги для баблов
            if ($folding instanceof BubbledFolding) {
                $this->bubbles[] = $folding;
            }
            //Фолдинги, предоставляющие панели
            if ($folding instanceof PanelFolding) {
                $this->panels[] = $folding;
            }
        }

        PsProfiler::inst(__CLASS__)->stop();
    }

    /*
     * Фолдинги
     */

    public function getBubbles() {
        return $this->bubbles;
    }

    public function getPanelProviders() {
        return $this->panels;
    }

    /*
     * Библиотеки
     */

    public function getLibManagers() {
        return $this->libs;
    }

    /** @return LibResources */
    public function getLibManager($libType, $assert = true) {
        return FoldedStorageInsts::byTypeStype(LibResources::LIB_FOLDING_TYPE, $libType, $assert);
    }

    /** @return PostsProcessor */
    public function getPostsProcessorByPostType($postType, $isEnsure = true) {
        //TODO
        return array();
    }

    public function getTimeLineFolding() {
        $insts = array();
        foreach (FoldedStorageInsts::listFoldings() as $folding) {
            if ($folding instanceof TimeLineFolding) {
                $insts[] = $folding;
            }
        }
        return $insts;
    }

    private static $inst;

    /** @return Handlers */
    public static function getInstance() {
        return self::$inst ? self::$inst : self::$inst = new Handlers();
    }

}

?>
