<?php

/**
 * Класс содержит информацию о классе аудита, настроенном в config.ini
 *
 * @author azazello
 */
final class PsAuditController {

    /**
     * @var int - код аудита из конфига
     */
    private $code;

    /**
     * @var string - класс аудита из конфига
     */
    private $class;

    /**
     * @var array - действия
     */
    private $actions;

    /**
     * @var int - счётчик вызово
     */
    private $counter = 0;

    /** @var PsLoggerInterface */
    protected $LOGGER;

    /**
     * Список всех действий
     */
    public function getActions() {
        return $this->actions;
    }

    /**
     * Валидация типа действия
     */
    private function checkActionCode($actionCode) {
        $actionCode = PsCheck::int($actionCode);
        if (in_array($actionCode, $this->actions)) {
            return $actionCode;
        }
        return PsUtil::raise("Код действия [$actionCode] не зарегистрирован для аудита $this");
    }

    /**
     * Метод декодирует действие по коду
     */
    public function decodeAction($actionCode) {
        $actionCode = $this->checkActionCode($actionCode);
        return array_search($actionCode, $this->actions) . ' (' . $actionCode . ')';
    }

    /**
     * Проверка - включён ли этот аудит
     */
    private function isEnabled() {
        return !is_array(ConfigIni::auditsDisabled()) || !in_array($this->code, ConfigIni::auditsDisabled());
    }

    /**
     * Метод выполняет аудит
     * 
     * @param int $action - код действия, который должен быть определён в классе в виде константы ACTION_
     * @param mixed $data - данные аудита
     * @param int $userId - код пользователя для аудита. Авторизованный пользователь будет записан всё равно
     * @param type $instId - код экземпляра, для каждой подсистемы свой
     * @param type $typeId - код типа, для каждой подсистемы свой
     */
    public function doAudit($action, $data = null, $userId = null, $instId = null, $typeId = null) {
        try {
            $action = $this->checkActionCode($action);

            $userId = AuthManager::validateUserIdOrNull($userId);
            $userIdAuthed = AuthManager::getUserIdOrNull();
            $remoteIp = ServerArrayAdapter::REMOTE_ADDR();
            $userAgent = ServerArrayAdapter::HTTP_USER_AGENT();
            $instId = PsCheck::intOrNull($instId);
            $typeId = PsCheck::intOrNull($typeId);

            if ($this->LOGGER->isEnabled()) {
                $this->LOGGER->info();
                $this->LOGGER->info("<Запись #{}>", ++$this->counter);
                $this->LOGGER->info('Действие: {}', $this->decodeAction($action));
                $this->LOGGER->info('Пользователь: {}', is_null($userId) ? 'НЕТ' : $userId);
                $this->LOGGER->info('Авторизованный пользователь: {}', is_null($userIdAuthed) ? 'НЕТ' : $userIdAuthed);
                $this->LOGGER->info('REMOTE_ADDR: {}', $remoteIp);
                $this->LOGGER->info('HTTP_USER_AGENT: {}', $userAgent);
                $this->LOGGER->info('Код экземпляра: {}', $instId === null ? 'НЕТ' : $instId);
                $this->LOGGER->info('Код типы: {}', $typeId === null ? 'НЕТ' : $typeId);
                $this->LOGGER->info('Данные: {}', $data === null ? 'НЕТ' : print_r($data, true));
            }

            if (!$this->isEnabled()) {
                return; //---
            }

            $encoded = 0;
            if (is_array($data)) {
                if (count($data) == 0) {
                    $data = null;
                } else {
                    $data = self::encodeData($data);
                    $encoded = 1;
                }
            }

            PsCheck::phpVarType($data, array(PsConst::PHP_TYPE_NULL, PsConst::PHP_TYPE_STRING, PsConst::PHP_TYPE_DOUBLE, PsConst::PHP_TYPE_FLOAT, PsConst::PHP_TYPE_INTEGER));

            $what = array();
            $what['id_process'] = $this->code;
            $what['id_user'] = $userId;
            $what['id_user_authed'] = $userIdAuthed;
            $what['n_action'] = $action;
            $what['id_inst'] = $instId;
            $what['id_type'] = $typeId;
            $what['v_data'] = $data;
            $what['b_encoded'] = $encoded;
            $what['v_remote_addr'] = PsCheck::isIp($remoteIp) ? $remoteIp : null;
            $what['v_user_agent'] = PsStrings::ensureLen($userAgent);
            $what[] = Query::assocParam('dt_event', 'UNIX_TIMESTAMP()', false);

            $recId = PSDB::insert(Query::insert('ps_audit', $what));

            if ($this->LOGGER->isEnabled()) {
                if ($data !== null) {
                    $this->LOGGER->info('Данные кодированы: {}', $encoded ? "ДА ($data)" : 'НЕТ');
                }
                $this->LOGGER->info('Информация сохранена в базу, id={}', $recId);
            }

            $this->LOGGER->info('АУДИТ ПРОИЗВЕДЁН.');
        } catch (Exception $ex) {
            //Не удалось записать аудит, но работа должна быть продолжена!
            ExceptionHandler::dumpError($ex);
            throw $ex;
        }
    }

    /**
     * Метод сериализует данные аудита
     * 
     * @param array $data - данные аудита
     * @return string
     */
    private static final function encodeData(array $data) {
        return serialize($data);
    }

    /**
     * Метод десериализует данные аудита
     * 
     * @param string $data - данные аудита
     * @return array|null
     */
    public static final function decodeData($data) {
        return $data ? @unserialize($data) : null;
    }

    /**
     * Экземпляры аудитов
     * 
     * @var array
     */
    private static $items = array();

    /**
     * Метод возвращает класс контроллера для аудита
     * @return PsAuditController контроллер класса аудита
     */
    public static function inst($ident) {
        /*
         * Поищем класс аудита по его коду
         */
        if (PsCheck::isInt($ident)) {
            return array_key_exists($ident, self::$items) ? self::$items[$ident] : self::$items[$ident] = new PsAuditController($ident);
        }

        /*
         * Поищем класс аудита по названию
         */
        if (PsCheck::isNotEmptyString($ident)) {
            $code = array_search($ident, ConfigIni::audits());
            if (PsCheck::isInt($code)) {
                return self::inst($code);
            } else {
                return PsUtil::raise('Класс аудита \'{}\' не зарегистрирован', $ident);
            }
        }

        /*
         * Не удалось найти класс аудита
         */
        return PsUtil::raise('Класс аудита \'{}\' не зарегистрирован', $ident);
    }

    /**
     * @param int $code - код аудита в файле конфига
     */
    private function __construct($code) {
        $this->code = PsCheck::int($code);
        $this->class = array_get_value($code, ConfigIni::audits());

        if (!PsCheck::isNotEmptyString($this->class)) {
            return PsUtil::raise('Класс аудита с кодом \'{}\' не зарегистрирован', $this->code);
        }

        if (!class_exists($this->class)) {
            return PsUtil::raise('Класс аудита \'{}\' не найден', $this->class);
        }

        //Проверим, что коды действий уникальны
        PsUtil::assertClassHasDifferentConstValues($this->class, 'ACTION_');

        //Загрузим все действия, откинув префикс ACTION_
        $this->actions = array();
        foreach (PsUtil::getClassConsts($this->class, 'ACTION_') as $aname => $acode) {
            $this->actions[cut_string_start($aname, 'ACTION_')] = $acode;
        }

        //Если нет действий - это ошибка
        if (empty($this->actions)) {
            return PsUtil::raise('Не зарегистрировано ни одного действия в классе аудита \'{}\'', $this->class);
        }

        $this->LOGGER = PsLogger::inst($this->class);
    }

    public final function __toString() {
        return "[{$this->code}] {$this->class}";
    }

}

?>