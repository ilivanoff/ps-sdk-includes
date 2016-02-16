<?php

/**
 * Загрузчик аватаров
 *
 * @author azazello
 */
class CropUploader extends FileUploader {

    protected function getAuthType() {
        return AuthManager::AUTH_TYPE_NO_MATTER;
    }

    protected function isStoreToDb() {
        return true;
    }

    protected function isAutonomous() {
        return true;
    }

    protected function onBeforeSave(DirItem $source, $userId, ArrayAdapter $params) {
        $source->assertIsImg();
        //check_condition($this->getUploadedFilesCount($userId) < 2, 'Больше нельзя загружать аватары');
    }

    protected function onAfterSave(DirItem $uploaded, $userId, ArrayAdapter $params) {
        
    }

}

?>