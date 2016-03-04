<?php

/**
 * Класс для аудита отправки почты
 *
 * @author azazello
 */
final class MailAudit extends PsAuditAbstract {

    const ACTION_SENDED = 1;

    public function getDescription() {
        return 'Отправка почты';
    }

    /**
     * Аудит отправки письма
     */
    public static function afterSended(PsMailSender $sender) {
        parent::doAudit(self::ACTION_SENDED, "$sender", $sender->getUserIdTo());
    }

}

?>