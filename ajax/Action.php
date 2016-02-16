<?php

require_once 'AjaxTools.php';

/**
 * Определим функцию, которая выполнит все действия - не будем лишними переменными засорять глобальное пространство
 */
function psExecuteAjaxAction() {

    /*
     * Название действия должно быть в переменной запроса. Оно же - название класса, который будет выполнен.
     * Группа действия должны быть не обязательна, при определении действия группа нужна обязательно.
     */
    $actionName = RequestArrayAdapter::inst()->str(AJAX_ACTION_PARAM);
    $actionGroup = RequestArrayAdapter::inst()->str(AJAX_ACTION_GROUP_PARAM, 'client');

    if (!PsCheck::notEmptyString($actionName) || !PsCheck::notEmptyString($actionGroup)) {
        return json_error('Не передан код действия или его группа'); //---
    }

    /*
     * Экземпляр класса действия - должен быть наследником AbstractAjaxAction
     */
    $action = null;

    /*
     * Поищем в проектных действиях, они для нас имеют больший приоритет
     */
    foreach (ConfigIni::ajaxActionsAbs($actionGroup) as $dirAbsPath) {
        $classPath = file_path($dirAbsPath, $actionName, PsConst::EXT_PHP);
        if (is_file($classPath)) {
            /*
             * Нашли файл. Загрузим и проверим, является ли он наследником AbstractAjaxAction
             */
            require_once $classPath;

            if (!PsUtil::isInstanceOf($actionName, AbstractAjaxAction::getClassName())) {
                continue; //---
            }

            $action = new $actionName();

            break; //---
        }
    }

    /*
     * Проверим, существует ли действие.
     * Для безопасности не будем писать детали обработки.
     */
    if (!$action || !($action instanceof AbstractAjaxAction)) {
        return json_error('Действие не опеределено'); //---
    }

    /*
     * Выполняем
     */
    $result = null;

    try {
        $result = $action->execute();
    } catch (Exception $e) {
        $result = $e->getMessage();
    }

    /*
     * Проверим результат
     */
    if ($result instanceof AjaxSuccess) {
        json_success($result->getJsParams());
    } else {
        json_error($result ? $result : 'Ошибка выполнения действия');
    }
}

/**
 * Вызываем
 */
psExecuteAjaxAction();
?>