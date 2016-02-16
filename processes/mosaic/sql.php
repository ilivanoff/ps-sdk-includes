<?php

/**
 * Процесс строит скрипты для вставки ячеек в БД
 * 
 * @param array $argv
 */
function executeProcess(array $argv) {
    $DM = DirManager::inst(__DIR__);

    //Перенесём картинки в директорию со всеми картинками
    /* @var $img DirItem */
    foreach ($DM->getDirContent(null, DirItemFilter::IMAGES) as $img) {
        $img->copyTo($DM->absFilePath('source', $img->getName()));
        $img->remove();
    }

    //Очистим директорию с выходными файлами
    $DM->clearDir('output');
    $DM->makePath('output');

    //Пробегаемся по картинкам, разбиваем их на ячейки и строим sql для вставки
    /* @var $img DirItem */
    foreach ($DM->getDirContent('source', DirItemFilter::IMAGES) as $img) {
        $id = $img->getNameNoExt();

        //Обрабатываем только картинки с целыми кодами
        if (is_inumeric($id)) {
            dolog("Processing img [{$img->getRelPath()}].");
            $id = 1 * $id;
        } else {
            dolog("Skip img [{$img->getRelPath()}], name is not numeric.");
            continue; //---
        }

        //Начало обработки
        $outDm = DirManager::inst(array(__DIR__, 'output'), $id);

        //Скопируем картинку
        $img->copyTo($outDm->absFilePath(null, $img->getName()));

        //Вычислим размеры ячейки
        $cw = 10;
        $ch = 10;

        $dim = $DM->getDirItem('source', $id, 'txt')->getFileAsProps(false);
        $dim = $dim['dim'];
        if ($dim) {
            $dim = explode('x', $dim);
            $cw = 1 * $dim[0];
            $ch = 1 * $dim[1];
        }

        //Скопируем свойства, с которыми она создавалась
        $outDm->getDirItem(null, 'settings', 'txt')->putToFile('dim=' . $cw . 'x' . $ch);
        dolog("Cell dimensions: [$cw x $ch].");

        //Размеры картинки получим у самой картинки
        $w = $img->getImageAdapter()->getWidth();
        $h = $img->getImageAdapter()->getHeight();
        dolog("Img size: [$w x $h].");

        check_condition($w > 0 && !($w % $cw), 'Bad width');
        check_condition($h > 0 && !($h % $ch), 'Bad height');

        $xcells = $w / $cw;
        $ycells = $h / $ch;
        $totalCells = $xcells * $ycells;

        dolog("Cells cnt: [$xcells x $ycells], total: $totalCells.");

        $generator = new MosaicCellsGenerator($totalCells);
        $secundomer = Secundomer::startedInst();

        $sqlDI = $outDm->getDirItem(null, 'fill', 'sql');
        $mapDI = $outDm->getDirItem(null, 'map', 'txt');

        $sqlImg = "delete from ps_img_mosaic_parts where id_img=$id;";
        $sqlDI->writeLineToFile($sqlImg);

        $sqlImg = "delete from ps_img_mosaic_answers where id_img=$id;";
        $sqlDI->writeLineToFile($sqlImg);

        $sqlImg = "delete from ps_img_mosaic where id_img=$id;";
        $sqlDI->writeLineToFile($sqlImg);

        $sqlImg = "insert into ps_img_mosaic (id_img, w, h, cx, cy, cw, ch) values ($id, $w, $h, $xcells, $ycells, $cw, $ch);";
        $sqlDI->writeLineToFile($sqlImg);

        for ($cellCnt = 1; $cellCnt <= $totalCells; $cellCnt++) {
            $cellNum = $generator->getCellNum();

            $xCell = $cellNum % $xcells;
            $xCell = $xCell == 0 ? $xcells : $xCell;
            $yCell = ($cellNum - $xCell) / $xcells + 1;

            $sqlCell = "insert into ps_img_mosaic_parts (id_img, n_part, x_cell, y_cell) values ($id, $cellCnt, $xCell, $yCell);";
            $sqlDI->writeLineToFile($sqlCell);

            $mapDI->writeLineToFile($cellCnt . '=' . $xCell . 'x' . $yCell);
        }

        $secundomer->stop();
        dolog('Execution time: ' . $secundomer->getTotalTime());
    }
}

//Отключаем автоматический коннект на базу, чтоыб наш генератор ничего ненабедокурил на продуктиве
$CALLED_FILE = __FILE__;
require_once dirname(__DIR__) . '/ProcessStarter.php';
?>