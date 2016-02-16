<?php

/**
 * Класс хранит в себе информацию о .php станице, которая может быть запрошена пользователем.
 */
final class WebPage {

    private $path;                //index.php
    private $name;                //О проекте
    private $pageCode;            //1
    private $pageCodeNoAccess;    //Код страницы, на которую будет произведён редирект при отсутствии доступа
    private $builderIdent;        //Построитель страницы
    private $authType;            //Тип авторизации, необходимый для доступа к данной странице

    public function __construct($path, $name, $pageCode, $authType, $pageCodeNoAccess, $pageBuilderIdent) {
        $this->path = $path;
        $this->name = $name;
        $this->pageCode = $pageCode;
        $this->authType = $authType;
        $this->pageCodeNoAccess = $pageCodeNoAccess;
        $this->builderIdent = $pageBuilderIdent;
    }

    /**
     * Возвращает ссылку на страницу:
     * <a href="helpus.php">Поддержать проект</a>
     * По умолчанию берёт название страницы, но можно передать кастомное.
     */
    public function getHref($content = null, $blank = false, $classes = null, $http = false, $urlParams = null, $sub = null, $title = null) {
        $PARAMS['href'] = $this->getUrl($http, $urlParams, $sub);
        $PARAMS['title'] = $title ? $title : $this->name;
        $PARAMS['class'] = $classes;

        return PsHtml::a($PARAMS, $content ? $content : $this->name, $blank);
    }

    //http://postupayu.ru/index.php
    public function getUrl($http = false, $params = null, $sub = null) {
        $url = PsUrl::addParams($this->path, $params, $sub);
        return $http ? PsUrl::toHttp($url) : $url;
    }

    //index.php
    public function getPath() {
        return $this->path;
    }

    //index
    public function getPathBase() {
        return get_file_name($this->path);
    }

    //О проекте
    public function getName() {
        return $this->name;
    }

    public function getCode() {
        return $this->pageCode;
    }

    public function getBuilderType() {
        return $this->builderIdent;
    }

    public function isIt($other) {
        return $this === self::inst($other, false);
    }

    public function isType($pageType) {
        return $pageType === $this->builderIdent;
    }

    public function hasAccess() {
        return AuthManager::hasAccess($this->authType);
    }

    /**
     * Метод выполняет редирек на данную страницу. 
     * При этом будет проверено, имеет ли пользователь доступ к ней и, если не имеет,
     * редирект будет произведён на страницу, к которой у пользователя есть доступ.
     */
    public function redirectHere() {
        //Имеем доступ? Редиректимся сюда.
        if ($this->hasAccess()) {
            PsUtil::redirectTo($this->path);
        }
        //Указана страница на случай отсутствия доступа? Редиректимся на неё.
        if (is_numeric($this->pageCodeNoAccess)) {
            WebPages::getPage($this->pageCodeNoAccess)->redirectHere();
        }
        //Редирект на index.php
        WebPages::getPage(BASE_PAGE_INDEX)->redirectHere();
    }

    /**
     * Основной метод, выполняющий построение страницы
     */
    public final function buildPage() {
        //Может ли данная страница вообще быть построена
        check_condition($this->builderIdent, "$this не может быть построена");

        //Проверим, установлена ли эта страница, как текущая
        check_condition(WebPages::isCurPage($this), "$this не установлена, как текущая, и не может быть построена");

        //Если у пользователя нет доступа к данной странице - выполним редирект
        if (!$this->hasAccess()) {
            $this->redirectHere();
        }

        //Теперь провалидируем установленный контекст
        $ctxt = PageContext::inst();
        check_condition($this->isIt($ctxt->getPage()), PsUtil::getClassName($ctxt) . ' проинициализирован некорректно');

        //Строим страницу
        PageBuilder::inst()->buildPage();
    }

    /**
     * Проксирующий метод, для удобства
     * 
     * @param WebPage
     */
    public static function inst($page, $ensure = true) {
        return WebPages::getPage($page, $ensure);
    }

    public function __toString() {
        return "WebPage: {$this->path} ('{$this->name}')";
    }

}

?>