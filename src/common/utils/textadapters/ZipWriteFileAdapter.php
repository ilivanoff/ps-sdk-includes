<?php

/**
 * Класс позволяет работать с Zip-архивами
 */
class ZipWriteFileAdapter extends AbstractDirItemAdapter {

    /** @var ZipArchive */
    private $zip;

    /**
     * При инициализации мы мгновенно стартуем новый архив
     */
    protected function onInit(DirItem $di) {
        $this->zip = new ZipArchive();
        $res = $this->zip->open($di->remove()->touch()->getAbsPath(), ZipArchive::OVERWRITE);
        PsUtil::assert($res === true, 'Cannot start zip archive {}. Err code: {}.', $di->getRelPath(), $res);
    }

    /**
     * Метод закрывает архив
     */
    public function close() {
        if ($this->zip) {
            $this->zip->close();
            $this->zip = null;
        }
        return $this->di;
    }

    /**
     * Метод проверяет открытость архива
     */
    private function ZipArchive() {
        return PsUtil::assert($this->zip, 'Zip archive {} is already closed.', $this->di->getRelPath());
    }

    /**
     * Метод добавляет элемент в архив
     */
    public function addItem(DirItem $item) {
        $added = true;
        if ($item->isDir()) {
            $added = $this->ZipArchive()->addEmptyDir($item->getRelPathNoDs());
        } else if ($item->isFile()) {
            $added = $this->ZipArchive()->addFile($item->getAbsPath(), $item->getRelPathNoDs());
        } else {
            //У нас не директория и не файл, просто пропускаем
        }
        check_condition($added, "Error adding file {$item->getAbsPath()} to zip");
    }

    /**
     * Метод добавляет элементы в архив
     */
    public function addItems(array $items) {
        foreach ($items as $item) {
            if (is_array($item)) {
                $this->addItems($item);
                return; //---
            }
            if ($item instanceof DirItem) {
                $this->addItem($item);
                return; //---
            }
            //Не гураемся, просто пропускаем
        }
    }

    /**
     * Метод добавляет элементы в архив
     */
    public function setComment($comment) {
        $this->ZipArchive()->setArchiveComment($comment);
    }

    /**
     * Метод добавляет непосредственно текст
     */
    public function addFromString($localname, $contents) {
        $this->ZipArchive()->addFromString($localname, $contents);
    }

    /**
     * Метод добавляет в архив содержимое запроса из таблицы.
     * 
     * @param PSSelect $select - запрос из таблицы
     * @param string|null $localname - название в архиве. Поумолчанию берётся директория: /sql/иаблица.sql
     */
    public function addTableDump(PSSelect $select, $localname = null) {
        $localname = $localname ? $localname : 'sql/' . $select->getTable() . '.' . PsConst::EXT_SQL;
        $this->addFromString($localname, PsTable::inst($select->getTable())->exportAsSqlString(PS_ACTION_CREATE, $select));
    }

    /**
     * Метод добавляет подпись к архиву
     * TODO
     */
    protected function addSign(DirItem $di) {
        $secret = $this->ZIP_SECRET;
        $sign = md5("$name-$secret");
        $comment = "$name;$sign";
        $zip->setArchiveComment($comment);
    }

    /**
     * Закроем архив, если пользователь забыл это сделать
     */
    public function __destruct() {
        $this->close();
    }

}

?>