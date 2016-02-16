<?php

/**
 * Админская часть работы с маппингами.
 * Все методы модификации маппинга находятся именно здесь.
 *
 * @author azazello
 */
class AdminMappings {

    public static function getAllMappings() {
        return MappingStorage::listMappings();
    }

    /** @return Mapping */
    public static function getMapping($mhash) {
        return MappingStorage::getMapping($mhash);
    }

    public static function saveMapping($mhash, $lident, array $ridents) {
        AdminMappingBean::inst()->saveMapping($mhash, $lident, $ridents);
    }

    public static function cleanMapping($mhash) {
        AdminMappingBean::inst()->cleanMapping($mhash);
    }

}

?>
