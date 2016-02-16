<?php

/**
 * Конвертирует картинки в .png
 * 
 * @param array $argv
 */
function executeProcess(array $argv) {
    $SRC_DIR = 'source';
    $OUT_DIR = 'output';

    $dm = DirManager::inst(__DIR__);

    //Создадим $SRC_DIR
    $dm->makePath($SRC_DIR);

    //Перенесём все картинки из корня в $SRC_DIR
    $items = $dm->getDirContent(null, DirItemFilter::IMAGES);

    /* @var $img DirItem */
    foreach ($items as $img) {
        $img->copyTo($dm->absFilePath($SRC_DIR, $img->getName()))->remove();
    }

    //Очистим $OUT_DIR
    $dm->clearDir($OUT_DIR)->makePath($OUT_DIR);

    //Список картинок
    $items = $dm->getDirContent($SRC_DIR, DirItemFilter::IMAGES);

    /* @var $img DirItem */
    foreach ($items as $img) {
        SimpleImage::inst()->load($img)->resizeSmart(36, 36)->save($dm->absFilePath($OUT_DIR, $img->getNameNoExt(), 'png'), IMAGETYPE_PNG)->close();
    }
}

//Отключаем автоматический коннект на базу, чтоыб наш генератор ничего ненабедокурил на продуктиве
$CALLED_FILE = __FILE__;
require_once dirname(__DIR__) . '/ProcessStarter.php';
?>