<?php

/**
 * Метод позволяет генерировать случайные изображения
 *
 * @author azazello
 */
class PsImgRand {

    /**
     * Метод возвращает ссылку наслучайное изображение
     * 
     * @param type $dim - размер изображения
     * @param type $type - тип изображения
     * @return DirItem созданная картинка. Должна быть удалена извне
     */
    public static function nextImg($dim, $type = PsConst::EXT_PNG) {
        $dim = parse_dim($dim);
        $w = PsCheck::int($dim[0]);
        $h = PsCheck::int($dim[1]);


        $di = DirManager::autogen(DirManager::DIR_IMAGES)->getDirItem(null, PsUtil::fileUniqueTime(), PsImg::getExt($type));
        $success = copy("http://lorempixel.com/$w/$h/?a=" . time(), $di->getAbsPath());

        PsUtil::assert($success, 'Не удалось создать случайное изображение');

        return $di;
    }

    /**
     * Метод выводит произвольную <img />
     * 
     * @param type $dim - размер изображения
     * @param array $attrs - параметры для картинки
     * @param type $type - тип изображения
     * @return type
     */
    public static function nextImgHtml($dim, array $attrs = array(), $type = PsConst::EXT_PNG) {
        $attrs['src'] = self::nextImg($dim, $type);
        return PsHtml::img($attrs);
    }

}

?>
