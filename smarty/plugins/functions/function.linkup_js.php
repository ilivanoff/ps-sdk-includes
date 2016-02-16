<?php

/*
 * Подключает ресурс к странице.
 * 
 * Рекомендуется пользоваться только в том случае, когда нет уверенности в сущестровании ресурса.
 */

function smarty_function_linkup_js($params, Smarty_Internal_Template & $smarty) {
    $di = DirItem::inst(array_get_value('dir', $params), array_get_value('name', $params), PsConst::EXT_JS);
    echo $di->isFile() ? PsHtml::linkJs($di) : '';
}

?>
