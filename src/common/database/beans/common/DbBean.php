<?php

/**
 * Класс для работы с базой данных.
 */
class DbBean extends BaseBean {

    /**
     * Загружает список всех представений и таблиц базы данных. Восновном используется для
     * маппинга групп кеширования на сущности БД.
     */
    public function getAllTablesAndViews() {
        return $this->getValues('SELECT TABLE_NAME as value FROM INFORMATION_SCHEMA.TABLES WHERE table_schema=DATABASE() and TABLE_TYPE=? union all SELECT TABLE_NAME as value FROM INFORMATION_SCHEMA.VIEWS WHERE TABLE_SCHEMA=DATABASE()', 'BASE TABLE');
    }

    /*
     * СИНГЛТОН
     */

    /** @return DbBean */
    public static function inst() {
        return parent::inst();
    }

}

?>
