<?php

/*
 * Работа с ребусами.
 * Всё идёт по следующему сценарию:
 * Рядом с классом PsMathRebus лежит файл с ребусами и ответами на них: ps-inclides/.../answers.txt
 * Процесс берёт файл rebuses.txt с ребусами и обрабатывает его, учитывая, какие ребусы уже были обработаны.
 * По факту обработки строится файл с ответами и копируется на место ps-inclides/.../answers.txt
 */
$LOGGERS_LIST[] = 'PsMathRebusSolver';

function executeProcess(array $argv) {

    $rebuses = DirItem::inst(__DIR__, 'rebuses.txt')->getTextFileAdapter();
    $MR = PsMathRebus::inst();

    $result = array();

    foreach ($rebuses->getLines() as $rebus) {
        if (starts_with($rebus, '#')) {
            continue; //---
        }
        $rebus = $MR->normalize($rebus);
        switch ($MR->rebusState($rebus)) {
            case PsMathRebus::STATE_HAS_ANSWERS:
                dolog("Take rebus answers: $rebus");
                $result[$rebus] = $MR->rebusAnswers($rebus);
                break;
            case PsMathRebus::STATE_NO_ANSWERS:
                dolog("Skipping rebus: $rebus");
                $result[$rebus] = array();
                break;
            case PsMathRebus::STATE_NOT_REGISTERED:
                dolog("Processing rebus: $rebus");
                $result[$rebus] = PsMathRebusSolver::solve($rebus);
                break;
        }
    }

    $ansDI = DirItem::inst(__DIR__, 'answers.txt');
    $ansDI->remove();
    foreach ($result as $rebus => $answers) {
        $ansDI->writeLineToFile($rebus);
        foreach ($answers as $answer) {
            $ansDI->writeLineToFile($answer);
        }
        $ansDI->writeLineToFile();
    }

    /*
     * Если передан параметр копирования, то скопируем файл после обработки
     */
    if (1 == array_get_value(1, $argv, 0)) {
        dolog('Copy from [{}] to [{}]', $ansDI->getRelPath(), $MR->getAnswersDI()->getRelPath());
        $ansDI->copyTo($MR->getAnswersDI()->getAbsPath());
    }
}

//Отключаем автоматический коннект на базу, чтоыб наш генератор ничего ненабедокурил на продуктиве
$CALLED_FILE = __FILE__;
require_once dirname(__DIR__) . '/ProcessStarter.php';
?>
