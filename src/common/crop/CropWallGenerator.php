<?php

/**
 * Класс генерации стены
 */
class CropWallGenerator {

    private function generateImpl() {
        $images = DirManager::inst('ps-addon/crop')->getDirContent('oboi', DirItemFilter::FILES);

        for ($i = 0; $i < rand(2000, 3000); $i++) {
            $img = $images[array_rand($images)];
            echo PsHtml::img(array('src' => PsImgEditor::resize($img, '60x60', null)));
        }
    }

    public static function generate() {
        $cg = new CropWallGenerator();
        return $cg->generateImpl();
    }

}

?>