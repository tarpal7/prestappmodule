<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class PrestappDB
{
    function __construct() {
    }

    public static function createTable()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'prestapp_v2_hEnvio` (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `action` varchar(255) NOT NULL,
            `item_name` varchar(255) NOT NULL,
            `item_id` varchar(255),
            `id_product` varchar(255),
            `idshopname` varchar(255) NOT NULL,
            `schemeshop` varchar(255) NOT NULL,
            `params` text,
            PRIMARY KEY (`id`),
            UNIQUE  `UNIQ` (  `id` )
            ) DEFAULT CHARSET=utf8';

        $result = Db::getInstance()->Execute($sql);
        return $result;
    }

    public static function deleteAllItems()
    {
        $sql = 'DELETE FROM `' . _DB_PREFIX_ . 'prestapp_v2_hEnvio`';

        $result = Db::getInstance()->Execute($sql);
        return $result;
    }

    public static function addItem($action, $item_name, $item_id = null, $id_product = null, $idshopname, $schemeshop, $params)
    {
        $sql = 'INSERT INTO  `' . _DB_PREFIX_ . 'prestapp_v2_hEnvio`(`action`, `item_name`, `item_id`, `id_product`, `idshopname`, `schemeshop`) VALUES('."'". $action ."',"."'". $item_name ."',"."'". $item_id ."',"."'". $id_product ."',"."'". $idshopname ."',"."'". $schemeshop ."'".');';

        $result = Db::getInstance()->Execute($sql);
        return $result;
    }

    public static function deleteItem($id)
    {
        $sql = "DELETE FROM `" . _DB_PREFIX_ . "prestapp_v2_hEnvio` WHERE id = ".$id.";";

        $result = Db::getInstance()->Execute($sql);
        return $result;
    }

    public static function getAllItems($limit, $offset)
    {
        $sql = "SELECT * FROM `" . _DB_PREFIX_ . "prestapp_v2_hEnvio` LIMIT ".$limit." OFFSET ".$offset.";";

        $result = Db::getInstance()->executeS($sql);
        return $result;
    }

    public static function arrayToText($params)
    {
        return json_encode($params);
    }


    public static function addDataOrder($order)
    {
        $id_payment = $order->id_payment;
        $id_customer = $order->id_customer;
        // С–РЅС€С– РїРѕР»СЏ С‚Р°Р±Р»РёС†С–

        $sql = "INSERT INTO `" . _DB_PREFIX_ . "prestapp_v2_orders` (`id_payment`, `id_customer`, `id_address_delivery`, ...) 
        VALUES ('$order->id_payment', '$order->id_customer', '$order->id_address_delivery', ...)";

        $sql = "INSERT INTO `" . _DB_PREFIX_ . "prestapp_v2_orders` (`id_payment`, `id_customer`, `id_address_delivery`, `id_address_invoice`, `id_cart`, `id_currency`, `id_lang`, `id_carrier`, `module`, `payment`, `total_paid`, `total_real_paid`, `total_products`, `total_products_wt`, `conversion_rate`, `stripe_token`, `id_transaction`, `reference`, `order_created`, `description`, `current_state`) 
        VALUES ('$order->id_payment',
                '$order->id_customer',
                '$order->id_address_delivery',
                '$order->id_address_invoice',
                '$order->id_cart',
                '$order->id_currency',
                '$order->id_lang',
                '$order->id_carrier',
                '$order->module',
                '$order->payment',
                '$order->total_paid',
                '$order->total_real_paid',
                '$order->total_products',
                '$order->total_products_wt',
                '$order->conversion_rate',
                '$order->stripe_token',
                '$order->id_transaction',
                '$order->reference',
                '$order->order_created',
                '$order->description',
                '$order->current_state'
                )";

        $result = Db::getInstance()->Execute($sql);
        return $result;
    }

    /**
     * Update order_created field in prestapp_v2_orders table by id_payment
     *
     * @param string $id_payment
     * @param string $order_created
     * @return bool
     */
    public static function updateOrderCreatedByIdPayment(string $id_payment, string $reference, string $order_created): bool
    {
        $sql = "UPDATE `" . _DB_PREFIX_ . "prestapp_v2_orders` SET `order_created` = '$order_created', `reference` = '$reference' WHERE `id_payment` = '$id_payment'";
        return Db::getInstance()->execute($sql);
    }

    /**
     * Update order_created field in prestapp_v2_orders table by stripe_tocken
     *
     * @param string $id_payment
     * @param string $order_created
     * @return bool
     */
    public static function updateOrderCreatedByStripeToken(string $stripe_token, string $order_created): bool
    {
        $sql = "UPDATE `" . _DB_PREFIX_ . "prestapp_v2_orders` SET `order_created` = '$order_created' WHERE `stripe_token` = '$stripe_token'";
        return Db::getInstance()->execute($sql);
    }


    /**
     * Update order_created field in prestapp_v2_orders table by reference
     *
     * @param string $id_payment
     * @param string $order_created
     * @return bool
     */
    public static function updateOrderCreatedByReference(string $reference, string $order_created): bool
    {
        $sql = "UPDATE `" . _DB_PREFIX_ . "prestapp_v2_orders` SET `order_created` = '$order_created' WHERE `reference` = '$reference'";
        return Db::getInstance()->execute($sql);
    }

    /**
     * Get orderData by `id_payment`
     *
     * @param string $id_payment
     * @return object|null
     */

    public static function getOrderByIdPayment($id_payment)
    {
        $sql = "SELECT * FROM `" . _DB_PREFIX_ . "prestapp_v2_orders` WHERE `id_payment` = '" . pSQL($id_payment) . "'";
        $result = Db::getInstance()->getRow($sql);
        return $result ?: null;
    }

}