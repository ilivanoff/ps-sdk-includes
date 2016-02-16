<?php

//Определим константу, которая однозначно будет означать, что мы работаем в ajax контексте
define('PS_AJAX_CONTEXT', true);

//Подключаем ресурсы проекта
require_once dirname(__DIR__) . '/MainImport.php';

/**
 * Метод вызывается для завершения успешного выполнения Ajax-запроса
 * 
 * @param mixed $data - данные, которые будут возвращены на клиента
 */
function json_success($data) {
    exit(json_encode(array('res' => $data)));
}

/**
 * Метод вызывается для завершения выполнения Ajax-запроса с ошибкой
 * 
 * @param mixed $error - данные, которые будут возвращены на клиента
 */
function json_error($error) {
    exit(json_encode(array('err' => $error)));
}

/**
 * Метод проверяет маркер сессии пользователя
 * 
 * @param string $marker
 */
function check_user_session_marker($marker) {
    if (!AuthManager::checkUserSessionMarker($marker)) {
        json_error('Передан некорректный маркер сессии');
    }
}

?>