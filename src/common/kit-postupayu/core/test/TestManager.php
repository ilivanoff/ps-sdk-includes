<?php

final class TestManager extends AbstractSingleton {

    /** @var PsLoggerInterface */
    private $LOGGER;

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

    /*
     * 
     * 
     * 
     * 
     * =========================
     *        КОММЕНТАРИИ
     * =========================
     * 
     * 
     * 
     * 
     */

    const RND_STRING_LEN = 20;

    private $postData = array();

    public function getText(PostsProcessor $processor, $postId, $takeTextFromPost) {
        if (!$takeTextFromPost) {
            return getRandomString(TestManager::RND_STRING_LEN);
        }

        $ident = $processor->getPostType() . '_' . $postId;

        $matches = array();
        if (array_key_exists($ident, $this->postData)) {
            $matches = $this->postData[$ident];
        } else {
            $content = $processor->getPostContentProvider($postId)->getPostContent()->getContent();
            preg_match_all("/<p[^>]*>([^<]*)<\/p>/si", $content, $matches, PREG_PATTERN_ORDER);
            $matches = $matches[1];
            $this->postData[$ident] = $matches;
        }

        $cnt = count($matches);
        $text = trim($cnt == 0 ? getRandomString(TestManager::RND_STRING_LEN) : $matches[rand(0, $cnt - 1)]);
        return $text ? UserInputTools::safeLongText($text) : getRandomString(TestManager::RND_STRING_LEN);
    }

    /**
     * Удаление всех комментариев ко всем постам.
     */
    public final function deleteAllComments($postType = null) {
        $cproc = Handlers::getInstance()->getCommentProcessors($postType);
        /* @var $proc CommentsProcessor */
        foreach ($cproc as $proc) {
            TESTBean::inst()->deleteAllComments($proc->dbBean()->getCommentsTable());
        }
    }

    /**
     * Генерация лайков к сообщениям дискуссий
     */
    public final function generateCommentLikes() {
        TESTBean::inst()->cleanVotes();

        $userIds = TESTBean::inst()->getUserIds();

        $controllers = Handlers::getInstance()->getDiscussionControllers();
        /** @var $ctrl DiscussionController */
        foreach ($controllers as $ctrt) {
            $settings = $ctrt->getDiscussionSettings();
            if (!$settings->isVotable()) {
                continue; //---
            }
            $messages = TESTBean::inst()->getAllMessages($settings);

            foreach ($messages as $msg) {
                $msgId = $msg[$settings->getIdColumn()];
                $threadUnique = $settings->getThreadUnique($msg[$settings->getThreadIdColumn()]);
                $authorId = $msg['id_user'];
                foreach ($userIds as $userId) {
                    if ($authorId == $userId) {
                        continue; //За свои сообщения не голосуем
                    }
                    $votes = rand(-1, 1);
                    if (!$votes) {
                        continue;
                    }
                    VotesManager::inst()->addVote($threadUnique, $msgId, $userId, $authorId, $votes);
                }
            }
        }
    }

    /** @return TestManager */
    public static function inst() {
        return parent::inst();
    }

    protected function __construct() {
        //Разрешаем работать с классом только администратору
        AuthManager::checkAdminAccess();
        //Мы должны находиться не в продакшене
        PsDefines::assertProductionOff(__CLASS__);
        $this->LOGGER = PsLogger::inst(__CLASS__);
        $this->BEAN = TESTBean::inst();
    }

}

?>