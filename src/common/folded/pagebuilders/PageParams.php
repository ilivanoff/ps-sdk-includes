<?php

/**
 * Description of PageParams
 *
 * @author azazello
 */
class PageParams extends FoldedTplFetchPrams {

    const PARAM_JS = 'js';
    const PARAM_TITLE = 'title';
    const PARAM_RESOURCES = 'smres';
    const PARAM_BUILD_OPTIONS = 'bopts';

    /*
     * Признак экспортирования фолдингов
     */
    const BO_EXPORT_FOLDINDS = 'ef';

    /**
     * Заголовок страницы
     * @return string
     */
    public function getTitle() {
        return trim(parent::__get(self::PARAM_TITLE));
    }

    /**
     * Дополнительные параметры JavaScript. На странице доступны через defs.
     * @return array
     */
    public function getJsParams() {
        return to_array(parent::__get(self::PARAM_JS));
    }

    /**
     * Параметры smarty для page_resources
     * @return array
     */
    public function getSmartyParams4Resources() {
        return to_array(parent::__get(self::PARAM_RESOURCES));
    }

    /**
     * Метод загружает параметр построения
     * 
     * @param string $key - ключ
     * @param mixed $default - значение поумолчанию
     * @return mixed
     */
    private function getBuildOption($key, $default = null) {
        return array_get_value($key, to_array(parent::__get(self::PARAM_BUILD_OPTIONS)), $default);
    }

    /**
     * Метод проверяет булев параметр построения
     */
    private function isBuildOption($key, $default = false) {
        return !!$this->getBuildOption($key, $default);
    }

    /**
     * Добавлять ли информацию о фолдингах: defs['foldings']
     */
    public function isAddFoldingsInfo() {
        return $this->isBuildOption(self::BO_EXPORT_FOLDINDS, true);
    }

}

?>
