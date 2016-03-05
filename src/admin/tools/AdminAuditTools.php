<?php

/**
 * Утилиты для работы с аудитом
 *
 * @author azazello
 */
final class AdminAuditTools {

    /**
     * Все типы аудитов для формы поиска
     */
    public static function getAuditTypeCombo() {
        $data = array();
        foreach (ConfigIni::audits()as $processCode => $class) {
            $data[] = PsHtml::comboOption($processCode, "[$processCode] $class");
        }
        return $data;
    }

    /**
     * Все типы аудитов для формы поиска
     */
    public static function getAuditActionsCombo() {
        $data = array();
        foreach (ConfigIni::audits()as $processCode => $class) {
            foreach (PsAuditController::inst($processCode)->getActions() as $name => $actionCode) {
                $data[] = PsHtml::comboOption($actionCode, "[$actionCode] $name", array('data' => array('process' => $processCode)));
            }
        }
        return $data;
    }

    /**
     * метод загружает кол-во записей для каждого аудита
     */
    public static function getAuditStatistic($dateTo) {
        $statistic = AdminAuditBean::inst()->getProcessStatistic($dateTo);
        $RESULT = array();
        foreach (ConfigIni::audits()as $processCode => $class) {
            foreach (PsAuditController::inst($processCode)->getActions() as $actionName => $actionCode) {
                $RESULT[] = array('name' => $class, 'action' => "$actionName ($actionCode)", 'cnt' => array_get_value_in(array($processCode, $actionCode), $statistic));
            }
        }
        return $RESULT;
    }

}

?>