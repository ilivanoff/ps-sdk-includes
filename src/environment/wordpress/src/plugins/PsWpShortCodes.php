<?php

/**
 * Шорткоды, добавляемые в wordpress посредством вызова add_shortcode.
 * https://codex.wordpress.org/Function_Reference/add_shortcode
 * Достаточно определить в этом классе public funal метод и он будет добавлен в качестве shortcode.
 *
 * @author azazello
 */
class PsWpShortCodes {

    /**
     * Подключение плагина
     * [psplugin name='advgraph' param1='value1']Мой плагин[/psplugin]
     */
    public final function psplugin(array $atts, $content = "") {
        $params = ArrayAdapter::inst($atts);
        $ident = $params->str(GET_PARAM_PLUGIN_IDENT);
        echo PluginsManager::inst()->buildAsShortcode($ident, $content, $params);
    }

    public final function teximg() {
        $src = TexImager::inst()->getImgDi('\sin(\alpha+\beta)')->getRelPath();
        return "Ps plugin included. <img src='$src'/>";
    }

}

?>