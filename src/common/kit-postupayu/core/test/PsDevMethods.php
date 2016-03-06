<?php

final class PsDevMethods {

    /** @var TESTBean */
    private $BEAN;


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
    private $avatars;

    /** @return DirItem */
    private function getAvatarImg() {
        if (!isset($this->avatars)) {
            $this->avatars = array_values(DirManager::images('avatars')->getDirContent(null, DirItemFilter::IMAGES));
            check_condition($this->avatars, 'No avatar images');
        }
        return $this->avatars[rand(0, count($this->avatars) - 1)];
    }

    /**
     * Генерация пользователей.
     * cnt - кол-во пользователей, которое будет сгенерировано
     */
    public final function genereteTestUsers($cnt = 10) {
        for ($index = 0; $index < $cnt; $index++) {
            $userId = $this->BEAN->createTestUser();
            $this->updateUserAvatars($userId);
        }
    }

    /**
     * Установка аватаров пользователя
     */
    public final function updateUserAvatars($userId = null) {
        $userIds = TESTBean::inst()->getUserIds($userId);
        foreach ($userIds as $userId) {
            $this->BEAN->unsetAvatarUploads($userId);
            $avatarDi = $this->getAvatarImg();
            $uploadedDi = AvatarUploader::inst()->makeUploadedFile($avatarDi, $userId);
            PsUser::inst($userId)->setAvatar($uploadedDi->getData('id'));
        }
    }

    /**
     * Удаление тестовых пользователей
     */
    public final function removeTestUsers() {
        $userIds = TESTBean::inst()->getUserIds();
        foreach ($userIds as $userId) {
            if ($this->BEAN->isTestUser($userId)) {
                $this->BEAN->unsetAvatarUploads($userId);
                $this->BEAN->removeTestUser($userId);
            }
        }
    }

    /**
     * Даёт очки пользователю
     */
    public final function givePoints2Users($userId = null, $cnt = 15) {
        $users = TESTBean::inst()->getUserIds($userId);
        foreach ($users as $uid) {
            UP_fromadmin::inst()->givePoints(PsUser::inst($uid), $cnt, getRandomString());
        }
    }

    /**
     * Привязывает все ячейки пользователя к картинке-мозайке
     */
    public final function bindAllUsersCells($imgId = 1, $userId = null) {
        foreach (TESTBean::inst()->getUserIds($userId) as $uId) {
            MosaicImage::inst($imgId)->bindAllUserCells($uId);
        }
    }

}

?>