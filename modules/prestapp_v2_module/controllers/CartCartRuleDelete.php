<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class CartCartRuleDelete
{
    public static function init ($data) {
        if ($data) {
            if ($data->id_cart &&
                $data->id_cart_rule) {
                    $request = 'DELETE FROM `' . _DB_PREFIX_ . 'cart_cart_rule`  WHERE id_cart = '.$data->id_cart.' and id_cart_rule = '.$data->id_cart_rule.';';
                    $return = Db::getInstance()->execute($request);
                    if ($return == 1) {
                        echo "Executed successfuly ".$request."\n";
                    } else {
                        echo "Error ".$request."\n";
                    }
                    
            } else {
                echo "No id_cart or id_cart_rule";
            }
        } else {
            echo "No data";
        }
    }
}