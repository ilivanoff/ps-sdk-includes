<?php

class PsTable extends BaseDataStore {

    private $columns; //Список колонок таблицы
    private $pk; //Колонка - первичный ключ
    private $title;

    /** @return PsTable */
    private function load() {
        if (!is_array($this->columns)) {
            $this->columns = AdminDbBean::inst()->getColumns($this->getName());

            $this->pk = null;
            $this->title = null;

            $titleCodeWords = array('name', 'title');

            /* @var $col PsTableColumn */
            foreach ($this->columns as $col) {
                if ($col->isPk()) {
                    $this->pk = $col;
                }

                if (!$this->title) {
                    if (contains_substring($col->getName(), $titleCodeWords)) {
                        $this->title = $col;
                    }
                }
            }

            //Если не определили titleColumn, то возмём первичный ключ
            $this->title = $this->title ? $this->title : $this->pk;
        }
        return $this;
    }

    public function getName() {
        return $this->TABLE_NAME;
    }

    public function getComment() {
        return $this->TABLE_COMMENT;
    }

    public function getColumns() {
        return $this->load()->columns;
    }

    public function hasColumn($colName) {
        return array_key_exists(lowertrim($colName), $this->getColumns());
    }

    /** @return PsTableColumn */
    public function getColumn($column) {
        return check_condition(array_get_value(lowertrim($column), $this->getColumns()), "Столбец {$this->getName()}.$column не существует.");
    }

    /** @return PsTableColumn */
    public function getPk() {
        return $this->load()->pk;
    }

    public function hasPk() {
        return !!$this->getPk();
    }

    //Проверка - является ли первичный ключ auto_increment полем
    public function isPkAi() {
        return $this->hasPk() && $this->getPk()->isAi();
    }

    /** @return PsTableColumn */
    public function getTitleColumn() {
        return $this->load()->title;
    }

    /**
     * Проверка, относится ли таблица к SDK
     */
    public function isSdk() {
        return DbIni::isSdkTable($this->getName());
    }

    /**
     * Проверка, относится ли таблица к SDK
     */
    public function getScope() {
        return $this->isSdk() ? ENTITY_SCOPE_SDK : ENTITY_SCOPE_PROJ;
    }

    /**
     * Список триггеров таблицы
     */
    private $triggers;

    public function getTriggers() {
        if (is_array($this->triggers)) {
            return $this->triggers;
        }
        return $this->triggers = AdminDbBean::inst()->getTableTriggers($this->getName());
    }

    public function hasTriggers() {
        return count($this->getTriggers()) > 0;
    }

    /**
     * Список записей из таблицы для построения комбо-бокса
     */
    private $selects;

    public function getSelectOptions() {
        return is_array($this->selects) ? $this->selects : $this->selects = AdminDbBean::inst()->getTableDataAsOptions($this->getName(), $this->getPk()->getName(), $this->getTitleColumn()->getName());
    }

    /** Выражение для ограничения where в строке */
    private function rowWhereExpr(array $row) {
        //Проверим, может ли быть идентификатором первичный ключ
        $tableName = $this->getName();
        $pk = check_condition($this->getPk(), $tableName . ' dont have PK');
        $pkName = $pk->getName();
        $id = array_get_value($pkName, $row);
        check_condition(is_inumeric($id), "Primaty key $pkName not given for table $tableName");
        return "$pkName=" . $pk->safe4insert($id);
    }

    /**
     * Построение sql запроса для создания/изменения записи
     * 
     * $canUsePk - признак, можно ли использовать первичный ключ при вставке. Если false, но не будет попытки замены ПК.
     */
    private function getSql(array $row, $action, array $currentRow = array()) {
        switch ($action) {
            case PS_ACTION_CREATE:
                $finalCols = array();
                $finalData = array();
                /* @var $col PsTableColumn */
                foreach ($this->getColumns() as $id => $col) {
                    if ($col->isUseOn($action) && array_key_exists($id, $row)) {
                        $finalCols[] = $id;
                        $finalData[] = $col->safe4insert($row[$id]);
                    }
                }
                check_condition($finalCols, 'No columns for insert, table: ' . $this->getName());
                return 'insert into ' . $this->getName() . ' (' . implode(', ', $finalCols) . ') values (' . implode(', ', $finalData) . ')';

            case PS_ACTION_EDIT:
                $tokens = array();
                /* @var $col PsTableColumn */
                foreach ($this->getColumns() as $id => $col) {
                    if ($col->isPk()) {
                        continue;
                    }
                    if ($col->isUseOn($action) && array_key_exists($id, $row)) {
                        if (!array_key_exists($id, $currentRow) || ($currentRow[$id] !== $row[$id])) {
                            $tokens[] = "$id=" . $col->safe4insert($row[$id]);
                        }
                    }
                }
                check_condition($tokens, 'No columns for update, table: ' . $this->getName());
                return 'update ' . $this->getName() . ' set ' . implode(', ', $tokens) . ' where ' . $this->rowWhereExpr($row);

            case PS_ACTION_DELETE:
                return 'delete from ' . $this->getName() . ' where ' . $this->rowWhereExpr($row);
        }

        PsUtil::raise('Unknown action: {}', $action);
    }

    /**
     * Выполняет загрузку всех строк из таблицы
     */
    public function getRows() {
        return PSDB::getArray('select * from ' . $this->getName());
    }

    /**
     * Выгружает данные таблицы в виде массива инсертов
     */
    public function exportAsSqlArray($action = PS_ACTION_CREATE) {
        $inserts = array();
        foreach ($this->getRows() as $row) {
            $inserts[] = $this->getSql($row, $action);
        }
        return $inserts;
    }

    /**
     * Выгружает данные таблицы в виде строки инсертов,
     * разделённых точкой запятой и переносом строки.
     * 
     * @return type
     */
    public function exportAsSqlString($action = PS_ACTION_CREATE) {
        $glue = ";\n";
        $inserts = implode($glue, $this->exportAsSqlArray($action));
        return $inserts ? $inserts . $glue : '';
    }

    /**
     * Экземпляр таблицы по названию
     * 
     * @return PsTable
     */
    public static function inst($name) {
        return AdminDbBean::inst()->getTable($name);
    }

    /**
     * Проверяет, существует ли таблица с заданным названием
     * 
     * @return bool
     */
    public static function exists($name) {
        return AdminDbBean::inst()->existsTable($name);
    }

    /**
     * Все таблицы системы
     */
    public static function all() {
        return AdminDbBean::inst()->getTables();
    }

}

?>