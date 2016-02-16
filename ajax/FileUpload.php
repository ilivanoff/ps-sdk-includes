<?php

$type = array_key_exists('type', $_POST) ? $_POST['type'] : null;
$marker = array_key_exists('marker', $_POST) ? $_POST['marker'] : null;

if (!$type) {
    die('Bad type given.');
}

//MD5_STR_LENGTH ещё использовать нельзя, так как Defines не подключен
if (!$marker || (strlen($marker) <= 32)) {
    die('Bad marker given.');
}

$sessionId = substr($marker, 32);

session_id($sessionId);

require_once 'AjaxTools.php';

check_user_session_marker($marker);

$LOGGER = PsLogger::inst('AjaxFileUpload');

try {
    $res = FileUploader::inst($type)->assertAutonomous()->saveUploadedFile(true, null, $_POST);
    json_success(array('path' => $res->getRelPath()));
} catch (Exception $ex) {
    $exMessage = $ex->getMessage();
    //Отлогируем
    if ($LOGGER->isEnabled()) {
        $LOGGER->info('Ошибка загрузки файла: {}', $exMessage);
        $LOGGER->info($ex->getTraceAsString());
    }
    //Снимем дамп ошибки
    ExceptionHandler::dumpError($ex);
    //Запишем ошибку в ответ
    json_error($exMessage ? $exMessage : 'Файл небыл загружен');
}
?>