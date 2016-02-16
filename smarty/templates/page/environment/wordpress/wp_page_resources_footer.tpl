{*Ресурсы для wordpress*}
<link rel="stylesheet" href="/ps-content/css/core.css" type="text/css" media="all" />
<!--<link rel="stylesheet" href="/ps-content/css/common.css" type="text/css" media="all" />-->
<link rel="stylesheet" href="/ps-content/css/common.widgets.css" type="text/css" media="all" />

{*hint Css [https://github.com/chinchang/hint.css]*}

<link rel="stylesheet" href="/ps-content/css/hint/hint.min.css" />

{*<script type="text/javascript" src="/ps-content/js-lib/jquery-1.8.2.js"></script>*}

<script type="text/javascript" src="/ps-content/js-lib/jquery.livequery.js"></script>

<script type="text/javascript" src="/ps-content/js-lib/jquery.form.js"></script>
<script type="text/javascript" src="/ps-content/js-lib/jquery-validation-1.10.0/dist/jquery.validate.min.js"></script>
{*<script type="text/javascript" src="/ps-content/js-lib/jquery-validation-1.10.0/dist/additional-methods.min.js"></script>*}
<script type="text/javascript" src="/ps-content/js-lib/jquery.scrollTo.1.4.7/jquery.scrollTo.min.js"></script>

{*<script type="text/javascript" src="/ps-content/js-lib/textarea-expander/jquery.textarea-expander.js"></script>*}

<script type="text/javascript" src="/ps-content/js-lib/shortcut.js"></script>

<script type="text/javascript" src="/ps-content/js-lib/store/source/store.js"></script>

<script type="text/javascript" src="/ps-content/js-lib/md5.js"></script>

<script type="text/javascript" src="/ps-content/js-lib/date.format.js"></script>

{*jQuery UI*}
{*
<link rel="stylesheet" href="/ps-content/js-lib/jquery-ui-1.9.1.custom/css/smoothness/jquery-ui-1.9.1.custom.min.css" type="text/css" media="all"/>
<script type="text/javascript" src="/ps-content/js-lib/jquery-ui-1.9.1.custom/js/jquery-ui-1.9.1.custom.min.js"></script>
*}
<link rel="stylesheet" href="/ps-content/js-lib/jquery-ui/jquery-ui-1.11.4.custom/jquery-ui.css" type="text/css" media="all"/>
<script type="text/javascript" src="/ps-content/js-lib/jquery-ui/jquery-ui-1.11.4.custom/jquery-ui.js"></script>


<link rel="stylesheet" href="/ps-content/js-lib/color-picker/colorPicker.css" type="text/css" media="all" />
<script type="text/javascript" src="/ps-content/js-lib/color-picker/jquery.colorPicker.min.js"></script>

{*http://jsdraw2dx.jsfiction.com/*}
<script type="text/javascript" src="/ps-content/js-lib/jsDraw2D/jsDraw2D.js"></script>

{*CODEMIRROR*}
<link rel="stylesheet" href="/ps-content/js-lib/codemirror-2.36/lib/codemirror.css" type="text/css" />
<script type="text/javascript" src="/ps-content/js-lib/codemirror-2.36/lib/codemirror.js"></script>
<script type="text/javascript" src="/ps-content/js-lib/codemirror-2.36/lib/util/formatting.js"></script>

<script type="text/javascript" src="/ps-content/js-lib/codemirror-2.36/mode/xml/xml.js"></script>
<script type="text/javascript" src="/ps-content/js-lib/codemirror-2.36/mode/css/css.js"></script>
<script type="text/javascript" src="/ps-content/js-lib/codemirror-2.36/mode/javascript/javascript.js"></script>
<script type="text/javascript" src="/ps-content/js-lib/codemirror-2.36/mode/htmlmixed/htmlmixed.js"></script>
<script type="text/javascript" src="/ps-content/js-lib/codemirror-2.36/mode/clike/clike.js"></script>
<script type="text/javascript" src="/ps-content/js-lib/codemirror-2.36/mode/php/php.js"></script>
<script type="text/javascript" src="/ps-content/js-lib/codemirror-2.36/mode/scheme/scheme.js"></script>

{*TIME PICKER*}
<link rel="stylesheet" href="/ps-content/js-lib/Timepicker/jquery-ui-timepicker-addon.css" type="text/css" />
<script type="text/javascript" src="/ps-content/js-lib/Timepicker/jquery-ui-timepicker-addon.js"></script>
{*<script type="text/javascript" src="/ps-content/js-lib/Timepicker/jquery-ui-sliderAccess.js"></script>*}
<script type="text/javascript" src="/ps-content/js-lib/Timepicker/jquery-ui-timepicker-ps-ru.js"></script>
{if isset($UPLOADIFY_ENABE) && $UPLOADIFY_ENABE}
    <link rel="stylesheet" href="/ps-content/js-lib/uploadify/Uploadify-3.2.1/uploadify.css" type="text/css" media="all" />
    {*<script type="text/javascript" src="/ps-content/js-lib/uploadify/swfobject.js"></script>*}
    <script type="text/javascript" src="/ps-content/js-lib/uploadify/Uploadify-3.2.1/jquery.uploadify.min.js"></script>
{/if}

{if isset($ATOOL_ENABLE) && $ATOOL_ENABLE}
    {*Скрипт для получения выделения на странице*}
    <script type="text/javascript" src="/ps-content/js-lib/jquery.a-tools-1.5.2.min.js"></script>
{/if}

{if isset($TIMELINE_ENABE) && $TIMELINE_ENABE}
    <script type="text/javascript">
    Timeline_ajax_url = "/ps-content/js-lib/timeline_2.3.0/timeline_ajax/simile-ajax-api.js";
    Timeline_urlPrefix = "/ps-content/js-lib/timeline_2.3.0/timeline_js/";
    Timeline_parameters = "bundle=true&defaultLocale=ru&forceLocale=ru";
    </script>
    <script type="text/javascript" src="/ps-content/js-lib/timeline_2.3.0/timeline_js/timeline-api.js"></script>
    <link rel="stylesheet" href="/ps-content/css/timeline-bundle.css" type="text/css" media="all" />
{/if}

{if !isset($MATHJAX_DISABLE) || !$MATHJAX_DISABLE}
    {linkup_js dir='/ps-content/js-lib' name="MathJax/MathJax.js"}
{/if}


{*
========================
Базовые ресурсы сайта
========================
*}

<script type="text/javascript" src="/ps-content/js/core.js"></script>
<script type="text/javascript" src="/ps-content/js/core.math.js"></script>
{$JS_DEFS}
<script type="text/javascript" src="/ps-content/js/common.js"></script>
<script type="text/javascript" src="/ps-content/js/common.math.js"></script>
<script type="text/javascript" src="/ps-content/js/common.ajax.js"></script>
<script type="text/javascript" src="/ps-content/js/common.forms.js"></script>
<script type="text/javascript" src="/ps-content/js/common.localbus.js"></script>
<script type="text/javascript" src="/ps-content/js/common.dialog.js"></script>
<script type="text/javascript" src="/ps-content/js/common.managers.js"></script>
<script type="text/javascript" src="/ps-content/js/common.bubbles.js"></script>
<script type="text/javascript" src="/ps-content/js/common.widgets.js"></script>
<script type="text/javascript" src="/ps-content/js/common.dekartfield.js"></script>
<script type="text/javascript" src="/ps-content/js/common.workers.base.js"></script>
<script type="text/javascript" src="/ps-content/js/common.workers.client.js"></script>

{devmodeOrAdmin}
<script type="text/javascript" src="/ps-content/js/common.dev.or.admin.js"></script>
<link rel="stylesheet" href="/ps-content/css/common.dev.or.admin.css" type="text/css" media="all" />
{/devmodeOrAdmin}
