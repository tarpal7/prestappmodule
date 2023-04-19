<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class CartRuleGroupGet
{
    public static function init ($limit, $offset) {
        $request = 'SELECT * FROM `' . _DB_PREFIX_ . 'cart_rule_group`  LIMIT '.$limit.' OFFSET '.$offset.';';
        $requestCount = 'SELECT count(*) as count FROM `' . _DB_PREFIX_ . 'cart_rule_group`;';
        $return = Db::getInstance()->executeS($request);
        $returnCount = Db::getInstance()->executeS($requestCount);
        $newData = array(
            'count' => '0',
            'data' => array()
        );
        if ($returnCount) {
            $newData['count'] = $returnCount[0]['count'];
        }
        for ($i = 0; $i < count($return); $i++) {
            $newData['data'][$i] = $return[$i];
        }
        echo json_encode($newData);
    }
}