<?php

/**
 * Процесс строит скрипты для вставки ячеек в БД
 * 
 * @param array $argv
 */
function executeProcess(array $argv) {
    $DM = DirManager::inst(array(__DIR__, 'output'));
    $customNum = $argv[1];

    dolog('Processing mosaic demo, custom num={}', $customNum);

    /* @var $dir DirItem */
    foreach ($DM->getDirContent(null, DirItemFilter::DIRS) as $dir) {
        if (is_inumeric($customNum) && $customNum != $dir->getName()) {
            continue; //----
        }

        $imgDM = DirManager::inst($dir->getAbsPath());
        $imgDI = end($imgDM->getDirContent(null, DirItemFilter::IMAGES));

        $map = $imgDM->getDirItem(null, 'map', 'txt')->getFileAsProps();

        $demoDM = DirManager::inst($imgDM->absDirPath(), 'demo');
        $demoDM->clearDir();

        dolog("Building map for: [{}].", $imgDI->getRelPath());

        //CELLS BINDING
        $dim = $imgDM->getDirItem(null, 'settings', 'txt')->getFileAsProps();
        $dim = $dim['dim'];
        $dim = explode('x', $dim);
        $cw = 1 * $dim[0];
        $ch = 1 * $dim[1];

        $sourceImg = SimpleImage::inst()->load($imgDI);
        $w = $sourceImg->getWidth();
        $h = $sourceImg->getHeight();
        $destImg = SimpleImage::inst()->create($w, $h, MosaicImage::BG_COLOR);

        dolog("Img size: [$w x $h].");

        check_condition($w > 0 && !($w % $cw), 'Bad width');
        check_condition($h > 0 && !($h % $ch), 'Bad height');

        $totalCells = count($map);
        $lengtn = strlen("$totalCells");

        //dolog("Cells cnt: [$xcells x $ycells], total: $totalCells.");
        //СТРОИМ КАРТИНКИ

        $secundomer = Secundomer::startedInst();

        //$encoder = new PsGifEncoder();
        for ($cellCnt = 0; $cellCnt <= $totalCells; $cellCnt++) {
            $name = pad_zero_left($cellCnt, $lengtn);
            $copyTo = $demoDM->absFilePath(null, $name, 'jpg');

            if ($cellCnt > 0) {
                $cellParams = $map[$cellCnt];
                $cellParams = explode('x', $cellParams);
                $xCell = $cellParams[0];
                $yCell = $cellParams[1];

                $x = ($xCell - 1) * $cw;
                $y = ($yCell - 1) * $ch;

                $destImg->copyFromAnother($sourceImg, $x, $y, $x, $y, $cw, $ch);
            }

            $destImg->save($copyTo);

            dolog("[$totalCells] $copyTo");
        }

        //$encoder->saveToFile($demoDM->absFilePath(null, 'animation'));

        $secundomer->stop();

        dolog('Execution time: ' . $secundomer->getTotalTime());
    }
}

//Отключаем автоматический коннект на базу, чтоыб наш генератор ничего ненабедокурил на продуктиве
$CALLED_FILE = __FILE__;
require_once dirname(__DIR__) . '/ProcessStarter.php';
?>