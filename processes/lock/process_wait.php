<?php

function executeProcess(array $argv) {
    dolog('try to get lock');
    $taken = PsLock::lock('mylock-wait', true);
    dolog('lock ' . ($taken ? 'taken' : 'not taken'));
    if ($taken) {
        sleep(10);
        PsLock::unlock();
        dolog('lock released');
    }
}

//Отключаем автоматический коннект на базу, чтоыб наш генератор ничего ненабедокурил на продуктиве
$CALLED_FILE = __FILE__;
require_once dirname(__DIR__) . '/ProcessStarter.php';
?>