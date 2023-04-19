<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class CartCartRuleGet
{
    public static function init ($id_cart_rule, $id_cart, $find) {
        if ($find == "id_cart") {
            $request = 'SELECT id_cart FROM `' . _DB_PREFIX_ . 'cart_cart_rule` where id_cart_rule = '.$id_cart_rule.';';
            $return = Db::getInstance()->executeS($request);
            if ($return) {
                $newData = array();
                for ($i = 0; $i < count($return); $i++) {
                    $newData[$i] = (int) $return[$i]['id_cart'];
                }
                echo json_encode($newData);
            } else {
                echo "No carts";
            }
        } elseif ($find == "id_cart_rule") {
            $request = 'SELECT id_cart_rule FROM `' . _DB_PREFIX_ . 'cart_cart_rule` where id_cart = '.$id_cart.';';
            $return = Db::getInstance()->executeS($request);
            if ($return) {
                $newData = array();
                for ($i = 0; $i < count($return); $i++) {
                    $newData[$i] = (int) $return[$i]['id_cart_rule'];
                }
                echo json_encode($newData);
            } else {
                echo "No carts";
            }
        }
    }
}