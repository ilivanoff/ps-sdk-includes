<?php

/**
 * Класс предоставляет доступ к массиву в случайном порядке.
 * Прелесть в том, что пока мы не вернём все элементы массива в случайном порядке, они ни разу не повторятся.
 *
 * @author azaz
 */
class PsArrayRandomAccess {

    /**
     * @var array - массив. Мы уверены, что массив не пуст.
     */
    private $array;

    /**
     * @var array - ключи в случайном порядке (shuffled keys)
     */
    private $keys = null;

    /**
     * @param array $array - массив, к которому будет осуществляться случайный доступ
     */
    private function __construct(array $array) {
        $this->array = PsCheck::notEmptyArray($array);
    }

    /**
     * Метод возвращает экземпляр класса случайного доступа к элементам массива
     * 
     * @param array $array - массив, к которому будет осуществляться случайный доступ
     * @return \PsArrayRandomAccess
     */
    public static function inst(array $array) {
        return new PsArrayRandomAccess($array);
    }

    /**
     * Метод возвращает случайный ключ
     */
    public function getKey() {
        if (!PsCheck::isNotEmptyArray($this->keys)) {
            $this->keys = array_keys($this->array);
            shuffle($this->keys);
        }
        return array_shift($this->keys);
    }

    /**
     * Метод возвращает случайное значение
     */
    public function getValue() {
        return $this->array[$this->getKey()];
    }

    /**
     * Метод возвращает исходный массив
     * 
     * @return array
     */
    public function getArray() {
        return $this->array;
    }

    /**
     * Метод возвращает случайное значение
     */
    public function kv(&$k, &$v) {
        $k = $this->getKey();
        $v = $this->array[$k];
    }

}
