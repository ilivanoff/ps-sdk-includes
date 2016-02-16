<?php

/**
 * Базовый интерфейс, который должны наследовать все реализации кеша.
 *
 * @author azazello
 */
interface PSCacheEngine {

    /**
     * Метод загружает значение из кеша
     * 
     * @param string $id - Код значения
     * @param string $group - Группа, в которую входит код
     */
    public function getFromCache($id, $group);

    /**
     * Метод сохраняет значение в кеш
     * 
     * @param mixed $object - сохраняемое значение
     * @param string $id - Код значения
     * @param string $group - Группа, в которую входит код
     */
    public function saveToCache($object, $id, $group);

    /**
     * Метод удаляет значение из кеша
     * 
     * @param string $id - Код значения
     * @param string $group - Группа, в которую входит код
     */
    public function removeFromCache($id, $group);

    /**
     * Метод очищает кеш или определённую группу кешей
     * 
     * @param string|null $group - код группы, которую нужно очистить
     */
    public function cleanCache($group = null);
}

?>
