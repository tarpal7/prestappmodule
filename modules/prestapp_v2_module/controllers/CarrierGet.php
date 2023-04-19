<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class CarrierGet
{
    public static function init () {
        $request = 'SELECT * FROM `' . _DB_PREFIX_ . 'carrier_zone`';
        $return = Db::getInstance()->executeS($request);
        echo json_encode($return);
    }
}