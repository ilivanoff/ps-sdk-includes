<?php

/**
 * Процесс выполняет периодические задачи cron
 * 
 * @param array $argv
 */
function executeProcess(array $argv) {

    /*
     * Подключаемся к продакшену
     */
    PsConnectionPool::configure(PsConnectionParams::production());

    /*
     * Выполняем cron
     */
    PsCron::inst()->execute();
}

//Отключаем автоматический коннект на базу, чтоыб наш генератор ничего ненабедокурил на продуктиве
$CALLED_FILE = __FILE__;
$LOGGERS_LIST[] = 'PsCron';
require_once dirname(__DIR__) . '/ProcessStarter.php';
?>