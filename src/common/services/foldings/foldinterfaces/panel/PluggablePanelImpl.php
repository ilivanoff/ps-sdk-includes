<?php

/**
 * Имплементация PluggablePanel
 *
 * @author azazello
 */
class PluggablePanelImpl implements PluggablePanel {

    private $html;
    private $jsParams;
    private $smartyParams4Resources;

    function __construct($html, $jsParams = array(), $smartyParams4Resources = array()) {
        $this->html = $html;
        $this->jsParams = $jsParams;
        $this->smartyParams4Resources = $smartyParams4Resources;
    }

    public function getHtml() {
        return $this->html;
    }

    public function getJsParams() {
        return $this->jsParams;
    }

    public function getSmartyParams4Resources() {
        return $this->smartyParams4Resources;
    }

}

?>
