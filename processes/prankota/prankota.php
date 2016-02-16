<?php

/**
 * Процесс выполняет периодические задачи cron
 * 
 * @param array $argv
 */
function executeProcess(array $argv) {
    $SYNC_DIR = 'd:/Zona Downloads/Архив пранкоты/';

    $lists = DirManager::inst(__DIR__)->getDirContent('m3u', PsConst::EXT_M3U);

    $filesCopied = DirItem::inst(__DIR__, 'all_copied', 'list')->remove()->touch();
    $filesSkipped = DirItem::inst(__DIR__, 'all_skipped', 'list')->remove()->touch();

    $destDir = DirManager::inst(__DIR__, 'dest')->clearDir()->makePath();

    /* @var $list DirItem */
    foreach ($lists as $name => $list) {
        $name = mb_convert_encoding($name, 'UTF-8', 'cp1251');
        dolog("+ $name");
        foreach ($list->getFileLines() as $newFile) {
            $newFile = mb_convert_encoding($newFile, 'UTF-8', 'UTF-8');
            if (starts_with($newFile, '#')) {
                dolog(" - $newFile");
                continue; //---
            }
            $absPath = next_level_dir($SYNC_DIR, $newFile);
            $absPath = iconv('UTF-8', 'cp1251', $absPath);

            $isFile = is_file($absPath);
            dolog(" + $newFile ? {}", var_export($isFile, true));
            if (!$isFile) {
                $filesSkipped->writeLineToFile($absPath);
                continue; //---
            }

            $fileName = iconv('UTF-8', 'cp1251', basename($newFile));
            copy($absPath, $destDir->absFilePath(null, $fileName));

            $filesCopied->writeLineToFile($absPath);
        }
        //print_r($list->getFileLines());
    }
}

//Отключаем автоматический коннект на базу, чтоыб наш генератор ничего ненабедокурил на продуктиве
$CALLED_FILE = __FILE__;
require_once dirname(__DIR__) . '/ProcessStarter.php';
?>