<?php

/**
 * Класс позволяет строить gif изображения
 * 
  $ge = new PsGifEncoder();
  $ge->addImg('c:\gif\author.jpg');
  $ge->addImg('c:\gif\author2.jpg');
  $ge->addImg('c:\gif\author3.jpg');
  $ge->addImg('c:\gif\author4.jpg');
  $ge->saveToFile('c:\gif\1.gif');
 */
class PsGifEncoder {

    private $IMAGES = array();
    private $animation;

    /**
     * Метод добавляет картинку в анимацию
     */
    public function addImg($path, $delay = 40) {
        if ($path instanceof DirItem) {
            $path = $path->getAbsPath();
        }

        PsImg::assertIsImg($path);

        $this->IMAGES[$path] = $delay;
        unset($this->animation);
    }

    /**
     * Анимация
     */
    public function getAnimation() {
        if (isset($this->animation)) {
            return $this->animation;
        }
        check_condition($this->IMAGES, 'No images for gif');

        PsLibs::inst()->GifEncoder();

        $frames = array();
        $framed = array();

        foreach ($this->IMAGES as $path => $delay) {
            ob_start();
            SimpleImage::inst()->load($path)->output(IMAGETYPE_GIF)->close();
            $frames[] = ob_get_contents();
            $framed[] = $delay; // Delay in the animation.
            ob_end_clean();
        }

        // Generate the animated gif and output to screen.
        $gif = new GIFEncoder($frames, $framed, 0, 2, 0, 0, 0, 'bin');
        $this->animation = $gif->GetAnimation();
        return $this->animation;
    }

    /**
     * Метод сохраняет картинку в файл
     */
    public function saveToFile(DirItem $path) {
        $path->assertExtension(PsConst::EXT_GIF);

        $fp = fopen($path->getAbsPath(), 'w');
        fwrite($fp, $this->getAnimation());
        fclose($fp);
    }

    /**
     * Метод выводит картинку на экран
     */
    public function outputToScreen() {
        echo $this->getAnimation();
    }

}

?>
