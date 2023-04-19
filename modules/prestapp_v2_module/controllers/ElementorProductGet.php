<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class ElementorProductGet
{
    public static function init ($limit, $offset) {
        $request = 'SELECT * FROM `' . _DB_PREFIX_ . 'iqit_elementor_product` LIMIT '.$limit.' OFFSET '.$offset.';';
        $return = Db::getInstance()->executeS($request);
        echo json_encode($return);
    }
}