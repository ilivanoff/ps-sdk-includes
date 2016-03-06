<?php

final class PsDevMethods {
    /*
     * 
     * 
     * 
     * 
     * ==========================
     *        ПОЛЬЗОВАТЕЛИ
     * ==========================
     * 
     * 
     * 
     * 
     */

    private static $images = null; //---

    private static function avatarImages() {
        PsDefines::assertProductionOff(__CLASS__);
        return is_array(self::$images) ? self::$images : self::$images = DirManager::images()->getDirContent('avatars', DirItemFilter::IMAGES);
    }

    /** @return DirItem */
    private static function getAvatarImg() {
        return self::avatarImages()[array_rand(self::avatarImages())];
    }

    /**
     * Генерация пользователей.
     * cnt - кол-во пользователей, которое будет сгенерировано
     */
    public static final function genereteTestUsers($cnt = 10) {
        for ($index = 0; $index < $cnt; $index++) {
            $userId = TESTBean::inst()->createTestUser();
            self::updateUserAvatars($userId);
        }
    }

    /**
     * Установка аватаров пользователя
     */
    public static final function updateUserAvatars($userId = null) {
        foreach (TESTBean::inst()->getUserIds($userId) as $uId) {
            TESTBean::inst()->unsetAvatarUploads($uId);
            $avatarDi = self::getAvatarImg();
            $uploadedDi = AvatarUploader::inst()->makeUploadedFile($avatarDi, $uId);
            PsUser::inst($uId)->setAvatar($uploadedDi->getData('id'));
        }
    }

    /**
     * Удаление тестовых пользователей
     */
    public static final function removeTestUsers() {
        foreach (TESTBean::inst()->getUserIds() as $uId) {
            if (TESTBean::inst()->isTestUser($uId)) {
                TESTBean::inst()->unsetAvatarUploads($uId);
                TESTBean::inst()->removeTestUser($uId);
            }
        }
    }

}

?>