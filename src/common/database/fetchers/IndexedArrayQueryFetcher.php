<?php

/**
 * Description of ObjectQueryFetcher
 *
 * @author azazello
 */
class IndexedArrayQueryFetcher extends ArrayQueryFetcher {

    /** Колонка индексирования */
    private $idxCol;

    /** ПРизнак множественности */
    private $multi;

    protected function __construct($idxCol, $multi) {
        parent::__construct(true, false);
        $this->idxCol = $idxCol;
        $this->multi = $multi;
    }

    public function fetchResult(array $ROWS) {
        $result = array();
        foreach ($ROWS as $row) {
            $idx = $row[$this->idxCol];
            if ($this->filterKey($idx)) {
                if ($this->multi) {
                    $result[$idx][] = $row;
                } else {
                    $result[$idx] = $row;
                }
            }
        }
        return $result;
    }

    /** @return IndexedArrayQueryFetcher */
    public static function inst($idxCol, $multi = false) {
        return new IndexedArrayQueryFetcher($idxCol, $multi);
    }

}

?>