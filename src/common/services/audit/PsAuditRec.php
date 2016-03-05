<?php

/**
 * Запись аудита - класс для удобной работы с аудитом
 *
 * @author azazello
 */
class PsAuditRec {

    /** @var mixed идентификатор аудита */
    private $ident;

    /** @var int код действия */
    private $action;

    /** @var int код пользователя */
    private $userId;

    /** @var int экземпляр сущности */
    private $instId;

    /** @var int тип события */
    private $typeId;

    /** @var midex данные аудита */
    private $data;

    /**
     * Метод создаёт экземпляр записи аудита
     */
    public static function inst($ident, $action) {
        return new PsAuditRec($ident, $action);
    }

    /**
     * Конструктор обязан принять идентификатор аудита и код действия - обязательные параметра
     */
    private function __construct($ident, $action) {
        $this->ident = $ident;
        $this->action = $action;
    }

    public function setData($data) {
        $this->data = $data;
        return $this;
    }

    public function setUserId($userId) {
        $this->userId = $userId;
        return $this;
    }

    public function setInstId($instId) {
        $this->instId = $instId;
        return $this;
    }

    public function setTypeId($typeId) {
        $this->typeId = $typeId;
        return $this;
    }

    /**
     * Метод выполняет вставку записи
     */
    public function submit() {
        PsAuditController::inst($this->ident)->doAudit($this->action, $this->data, $this->userId, $this->instId, $this->typeId);
    }

}

?>
