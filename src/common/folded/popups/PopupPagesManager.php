<?php

class PopupPagesManager extends PopupPagesResources {

    /**
     * Типы popup плагинов (плагинов, которые могут быть отображены в popup окне или popup старниц, работающих как плагины)
     */
    const TYPE_PAGE = 'P';
    const TYPE_PLUGIN = 'L';

    /**
     * Константы для кеша
     */
    const CACHABLE_VISIBLE = 'VISIBLE';

    /**
     * Список видимых сущностей
     * @var type 
     */
    private $SNAPSHOT = null;

    /**
     * Плагины, использованные в постах
     */
    public function getVisiblePages() {
        if (!is_array($this->SNAPSHOT)) {
            $this->SNAPSHOT = array();

            //Соберём все видимые попап-страницы и плагины
            foreach ($this->getEntityClassInsts() as $ident => $popup) {
                if ($popup->getPopupVisibility()) {
                    $this->SNAPSHOT[self::TYPE_PAGE . '_' . $ident] = $popup;
                }
            }
            foreach (PluginsManager::inst()->getEntityClassInsts() as $ident => $popup) {
                if ($popup->getPopupVisibility()) {
                    $this->SNAPSHOT[self::TYPE_PLUGIN . '_' . $ident] = $popup;
                }
            }

            //Отсортируем собранные сущности по названию
            uasort($this->SNAPSHOT, function($e1, $e2) {
                $str1 = $e1 instanceof BasePopupPage ? $e1->getTitle() : $e1->getName();
                $str2 = $e2 instanceof BasePopupPage ? $e2->getTitle() : $e2->getName();
                return strcasecmp($str1, $str2);
            });
        }
        return $this->SNAPSHOT;
    }

    /** @return BasePopupPage */
    public function getPage($ident) {
        return $this->getEntityClassInst($ident);
    }

    public function isPageVisible($type, $ident) {
        return array_key_exists($type . '_' . $ident, $this->getVisiblePages());
    }

    protected function isPageAsPlugin($ident) {
        return $this->isPageVisible(self::TYPE_PAGE, $ident);
    }

    /**
     * Валидация запроса
     */
    public function isValidPageRequested() {
        return is_array($this->getRequestParams());
    }

    public function getRequestParams() {
        $PARAMS = array();
        $RQ = RequestArrayAdapter::inst();

        //СТРАНИЦА
        if (!$RQ->has(POPUP_WINDOW_PARAM)) {
            return $PARAMS; //---
        }

        $pageIdent = $RQ->str(POPUP_WINDOW_PARAM);

        if (!$this->hasAccess($pageIdent, true)) {
            return false;
        }

        $PARAMS[POPUP_WINDOW_PARAM] = $pageIdent;

        if ($pageIdent != PP_plugin::getIdent()) {
            return $PARAMS;
        }

        //ПЛАГИН
        if (!$RQ->has(GET_PARAM_PLUGIN_IDENT)) {
            return false; //---
        }

        $pluginIdent = $RQ->str(GET_PARAM_PLUGIN_IDENT);
        if (!$this->isPageVisible(self::TYPE_PLUGIN, $pluginIdent)) {
            return false;
        }
        $PARAMS[GET_PARAM_PLUGIN_IDENT] = $pluginIdent;

        return $PARAMS;
    }

    //Возвращает страницу, которая будет построена
    /** @return BasePopupPage */
    public function getCurPage() {
        $GA = RequestArrayAdapter::inst();
        return $GA->has(POPUP_WINDOW_PARAM) ? $this->getPage($GA->str(POPUP_WINDOW_PARAM)) : $this->getPage(PP_404::getIdent());
    }

    //Если показываемая страница отображается как плагин, то для неё будет показан заголовок
    public function isShowPageHeader() {
        $ident = $this->getCurPage()->getIdent();

        $headerPages[] = PP_404::getIdent();
        $headerPages[] = PP_plugin::getIdent();

        return in_array($ident, $headerPages) || $this->isPageAsPlugin($ident);
    }

    /**
     * Ссылки на popup-страницы
     */
    public function getPageUrl($page, array $params = array()) {
        $ident = $page instanceof BasePopupPage ? $page->getIdent() : $page;
        $params[POPUP_WINDOW_PARAM] = $this->assertExistsEntity($ident);
        return WebPage::inst(PAGE_POPUP)->getUrl(false, $params);
    }

    /**
     * Список страниц в виде массива:
     * {
     * type,
     * ident,
     * fav,
     * cover,
     * name,
     * url
     * }
     */
    private function getPagesInfo($typeIdentArr) {
        $RESULT = array();

        $PLM = PluginsManager::inst();

        foreach ($typeIdentArr as $id => $page) {
            $item['id'] = $id; //id - уникальная связка типа и идентификатора

            if ($page instanceof BasePopupPage) {
                $item['type'] = self::TYPE_PAGE;
                $item['ident'] = $page->getIdent();
                $item['name'] = $page->getTitle();
                $item['url'] = $this->getPageUrl($page);
                $item['cover'] = $this->getCover($page->getIdent(), '36x36')->getRelPath();
                $item['descr'] = $page->getDescr();
            }

            if ($page instanceof BasePlugin) {
                $item['type'] = self::TYPE_PLUGIN;
                $item['ident'] = $page->getIdent();
                $item['name'] = $page->getName();
                $item['url'] = $this->getPageUrl(PP_plugin::getIdent(), array(GET_PARAM_PLUGIN_IDENT => $page->getIdent()));
                $item['cover'] = $PLM->getCover($page->getIdent(), '36x36')->getRelPath();
                $item['descr'] = $page->getDescr();
            }

            $RESULT[] = $item;
        }

        return $RESULT;
    }

    /**
     * Данные для построения списков плагинов
     */
    public function getPagesList() {
        return $this->getPagesInfo($this->getVisiblePages());
    }

    /**
     * Урл для pageIdent будем спрашивать менеджера всплывающих окон, так как он может знать о том,
     * что плагинам вообще запрещено открываться в отдельных окнах.
     */
    public function getPluginUrl(BasePlugin $plugin) {
        if (!PopupVis::isCanBeVisible($plugin->getPopupVisibility())) {
            return null;
        }
        //Во всех других случаях добавим ссылку на открытие плагина
        return PsUrl::addParams(PP_plugin::getIdent(), array(GET_PARAM_PLUGIN_IDENT => $plugin->getIdent()));
    }

    /**
     * Метод фактически строит страницу.
     * Нам нужно выполнить множество различных действий, поэтому перенесём все их сюда.
     * К моменту выполнения у страницы уже вызван метод doProcess
     */
    public function getPopupPageContent(BasePopupPage $page) {
        return $this->getResourcesLinks($page->getIdent(), ContentHelper::getContent($page));
    }

    /**
     * Предпросмотр страницы при редактировании
     */
    public function getFoldedEntityPreview($ident) {
        $page = $this->getPage($ident);
        $page->doProcess(ArrayAdapter::inst());
        return array(
            'info' => $page->getTitle(),
            'content' => $this->getPopupPageContent($page)
        );
    }

    /** @return PopupPagesManager */
    public static function inst() {
        return parent::inst();
    }

}

?>