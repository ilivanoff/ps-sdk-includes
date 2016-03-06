<?php

final class DevUsers {
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
            $userId = AdminDevBean::inst()->createTestUser();
            self::updateUserAvatars($userId);
        }
    }

    /**
     * Установка аватаров пользователя
     */
    public static final function updateUserAvatars($userId = null) {
        foreach (AdminDevBean::inst()->getUserIds($userId) as $uId) {
            AdminDevBean::inst()->unsetAvatarUploads($uId);
            $avatarDi = self::getAvatarImg();
            $uploadedDi = AvatarUploader::inst()->makeUploadedFile($avatarDi, $uId);
            PsUser::inst($uId)->setAvatar($uploadedDi->getData('id'));
        }
    }

    /**
     * Удаление тестовых пользователей
     */
    public static final function removeTestUsers() {
        foreach (AdminDevBean::inst()->getUserIds() as $uId) {
            if (AdminDevBean::inst()->isTestUser($uId)) {
                AdminDevBean::inst()->unsetAvatarUploads($uId);
                AdminDevBean::inst()->removeTestUser($uId);
            }
        }
    }

}

?>