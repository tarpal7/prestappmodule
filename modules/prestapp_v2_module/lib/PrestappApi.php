<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_ROOT_DIR_ . '/modules/prestapp_v2_module/lib/PrestappDB.php';

class ShippingSettings
{
    public $shipping_free_price_;
    public $shipping_free_weight_;
    public $shipping_handling_;
}

class AppleDetails
{
    public $appID;
    public $paths;
}

class MessageWrap
{
    public $success;
    public $message;
}

class PrestappApi
{

    /**
     * Receiving shop data from an admin panel Prestapp
     */
    public static function getDataFromAdminPanel($type)
    {
        // check prestashop
        $urlCheck = strval(Configuration::get('PRESTAPP_V2_MODULE_API_BACKEND_URL')).'/api/dashboard/v1/check-presta';
        $dataCheck = array(
            "presta_key" => strval(Configuration::get('PRESTAPP_V2_MODULE_PRESTA_KEY')),
        );
        $successCheck = false;
        $shopDataCheck = PrestappConnect::sendTo($urlCheck, $dataCheck);

        $shopDataCheckArr = json_decode($shopDataCheck);
        if (isset($shopDataCheckArr->success)) {
            $successCheck = boolval($shopDataCheckArr->success);
        }
        if (isset($shopDataCheckArr->isError)) {
            $successCheck = false;
        }

        $message = new MessageWrap();
        $message->success = $successCheck;

        if ($successCheck) {
            $idCluster = $shopDataCheckArr->data->cluster_id;
            $email = $shopDataCheckArr->data->email;
            $idShopname = $shopDataCheckArr->data->id_shopname;
            $token = $shopDataCheckArr->data->token;
            $schema_shop = Prestapp_V2_Module::getSchemaShop();

            // create Table for sync
            $resultsql = PrestappDB::createTable();
            if (!$resultsql) return false;

            if (
                $idCluster ||
                !empty($idCluster) ||
                Validate::isGenericName($idCluster) || 
                $email || 
                !empty($email)||
                Validate::isGenericName($email) ||
                $idShopname || 
                !empty($idShopname) ||
                Validate::isGenericName($idShopname) ||
                $token || 
                !empty($token) ||
                Validate::isGenericName($token)
            ) {
                Configuration::updateValue('PRESTAPP_V2_MODULE_ID_CLUSTER', $idCluster);
                Configuration::updateValue('PRESTAPP_V2_MODULE_TOKEN', $token);
                Configuration::updateValue('PRESTAPP_V2_MODULE_API_ID_SHOPNAME', $idShopname);
                Configuration::updateValue('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP', $schema_shop);
                Configuration::updateValue('PRESTAPP_V2_MODULE_LOGIN', $email);

                // get prestashop config
                $urlConfig = strval(Configuration::get('PRESTAPP_V2_MODULE_API_BACKEND_URL')).'/'.strval($idCluster).'/api/dashboard/v1/datasync';
                $dataConfig = array(
                    "email" => strval($email),
                    "id_shopname" => strval($idShopname),
                    "cluster_id" => strval($idCluster),
                );
                $successConfig = false;
                $shopDataConfig = PrestappConnect::sendTo($urlConfig, $dataConfig);

                $shopDataConfigArr = json_decode($shopDataConfig);
                if (isset($shopDataConfigArr->success)) {
                    $successConfig = boolval($shopDataConfigArr->success);
                }
                if (isset($shopDataConfigArr->isError)) {
                    $successConfig = false;
                }

                if ($successConfig) {
                    $api_shop_url = $shopDataConfigArr->data[0]->link_api;
                    $shop_key = $shopDataConfigArr->data[0]->shop_key;
                    $appID = $shopDataConfigArr->data[0]->app_id;

                    if (
                        $api_shop_url ||
                        !empty($api_shop_url) ||
                        Validate::isGenericName($api_shop_url) ||
                        $shop_key ||
                        !empty($shop_key) ||
                        Validate::isGenericName($shop_key) ||
                        $idShopname ||
                        !empty($idShopname) ||
                        Validate::isGenericName($idShopname) ||
                        $appID ||
                        !empty($appID) ||
                        Validate::isGenericName($appID) ||
                        $idCluster ||
                        !empty($idCluster) ||
                        Validate::isGenericName($idCluster) 
                    ) {
                        Configuration::updateValue('PRESTAPP_V2_MODULE_API_URL', $api_shop_url);
                        Configuration::updateValue('PRESTAPP_V2_MODULE_ID_CLUSTER', $idCluster);
                        Configuration::updateValue('PRESTAPP_V2_MODULE_API_KEY', $shop_key);
                        Configuration::updateValue('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP', $schema_shop);
                        Configuration::updateValue('PRESTAPP_V2_MODULE_API_BACKEND_APP_ID', $appID);
                        PrestappApi::initSchema();
                        PrestappApi::getFriendlyURLs();
                        PrestappApi::sendShopSettings();
                        PrestappApi::getListPaymentsMethod();
                        PrestappApi::addAppleDomainFile();
                        if ($type === 'sync_full' ) {
                            $resultsql = PrestappDB::deleteAllItems();
                            $initShop = PrestappApi::initShop();
                            $initShopArr = json_decode($initShop);
                            if (!boolval($initShopArr->success)) {
                                $message->success = boolval($initShopArr->success);
                                $message->message = $initShopArr->message;
                            }
                        }
                    }
                }
        
            }
        } else {
            $message->message = $shopDataCheckArr->message;
        }

        $arrTemp = [
            "presta_key" => strval(Configuration::get('PRESTAPP_V2_MODULE_PRESTA_KEY'))
        ];
        PrestappApi::saveCookies($arrTemp);
        return $message;
    }

    public static function arrayToJson($arr)
    {
        $apple_file = _PS_ROOT_DIR_.'/.well-known/apple-app-site-association';
        $apple_file_root_dir = _PS_ROOT_DIR_.'/apple-app-site-association';
        $json = json_encode(array('applinks' => $arr));
        if (file_put_contents($apple_file, $json) && file_put_contents($apple_file_root_dir, $json))
            return true;
        else
            return false;
    }

    public static function initSchema()
    {
        $url = strval(Configuration::get('PRESTAPP_V2_MODULE_API_BACKEND_URL')).'/'.strval(Configuration::get('PRESTAPP_V2_MODULE_ID_CLUSTER')).'/api/prestashop/v1/init-schema';
        $data = array(
            "schemaShop" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')),
        );
        PrestappConnect::sendTo($url, $data);
    }

    public static function initShop()
    {
        $url = strval(Configuration::get('PRESTAPP_V2_MODULE_API_BACKEND_URL')).'/'.strval(Configuration::get('PRESTAPP_V2_MODULE_ID_CLUSTER')).'/api/prestashop/v1/init-shop';
        $data = array(
            "schemaShop" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')),
            "idShopname" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')),
            "url" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_URL'))."/",
            "wsKey" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_KEY')),
        );
        return PrestappConnect::sendTo($url, $data);
    }

    public static function stopSync()
    {
        $message = new MessageWrap();
        $url = strval(Configuration::get('PRESTAPP_V2_MODULE_API_BACKEND_URL')).'/'.strval(Configuration::get('PRESTAPP_V2_MODULE_ID_CLUSTER')).'/api/prestashop/v1/init-shop';
        $data = array(
            "schemaShop" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')),
            "idShopname" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')),
            "url" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_URL'))."/",
            "wsKey" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_KEY')),
            "stopSync" => true
        );
        $result = PrestappConnect::sendTo($url, $data);
        $resultArr = json_decode($result);
        $message->message = $resultArr->message;
        return $message;
    }

    public static function addAppleDomainFile()
    {
        if (!is_dir(_PS_ROOT_DIR_.'/.well-known')) {
            if (!mkdir(_PS_ROOT_DIR_.'/.well-known')) {return false;}
        }
        $details = new AppleDetails();
        $details->appID = strval(Configuration::get('PRESTAPP_V2_MODULE_API_BACKEND_APP_ID'));
        $details->paths = ["*"];
        $array = [
            "apps" => [],
            "details" => [$details],
        ];

        $apple_file = _PS_ROOT_DIR_.'/.well-known/apple-app-site-association';
        $apple_file_root_dir = _PS_ROOT_DIR_.'/apple-app-site-association';
        if (!file_exists($apple_file) && !file_exists($apple_file_root_dir)) {
            PrestappApi::arrayToJson($array);
        } else {
            unlink($apple_file);
            unlink($apple_file_root_dir);
            PrestappApi::arrayToJson($array);
        }
    }

    public static function saveCookies(array $params)
    {
        $cookie = Context::getContext()->cookie;
        foreach ($params as $k => $v) {
            $cookie->__set($k, $v);
        }
        $cookie->write();
    }

    public static function removeCookie(string $key)
    {
        $cookie = Context::getContext()->cookie;
        $cookie->__unset($key);
    }

    public static function getCookie(string $key)
    {
        $cookie = Context::getContext()->cookie;
        $cookie->__get($key);
    }

    public static function getListPaymentsMethod()
    {
        $query_dbname = 'SELECT DATABASE() as dbname';
        $dbname = '`' . (Db::getInstance()->ExecuteS($query_dbname))[0]['dbname'] . '`' . '.';
        $query_payments = 'SELECT m.*, group_concat(c.iso_code) as currencies, (case m.name when \'ps_checkpayment\' then \'[{"en": "Payments by check"}, {"es": "Pagos por checque"}]\' when \'ps_wirepayment\' then \'[{"en": "Bank transfer"}, {"es": "Pagos por transferencia bancaria"}]\' when \'ps_checkout\' then \'[{"en": "Checkout Prestashop"}, {"es": "Checkout Prestashop"}]\' when \'stripe_official\' then \'[{"en": "Stripe Official"}, {"es": "Stripe Official"}]\' when \'paypal\' then \'[{"en": "PayPal"}, {"es": "PayPal"}]\' end) as payment_names FROM '. $dbname._DB_PREFIX_ .'hook_module h left join '. $dbname._DB_PREFIX_ .'module m on m.id_module=h.id_module left join '. $dbname._DB_PREFIX_ .'module_currency mc on mc.id_module=m.id_module left join '. $dbname._DB_PREFIX_ .'currency c on c.id_currency=mc.id_currency WHERE id_hook in (select id_hook from '. $dbname._DB_PREFIX_ .'hook where name = \'paymentOptions\') group by m.id_module';
        $payments = Db::getInstance()->ExecuteS($query_payments);
        $url = strval(Configuration::get('PRESTAPP_V2_MODULE_API_BACKEND_URL')).'/'.strval(Configuration::get('PRESTAPP_V2_MODULE_ID_CLUSTER')).'/api/prestashop/v1/sync-payment-methods-list';
        $data = array(
            "payments" => $payments,
            "schemaShop" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')),
            "idShopname" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')),
        );
        PrestappConnect::sendTo($url, $data);
    }

    public static function getFriendlyURLs()
    {
        $query_dbname = 'SELECT DATABASE() as dbname';
        $dbname = '`' . (Db::getInstance()->ExecuteS($query_dbname))[0]['dbname'] . '`' . '.';
        $query_friendly_urls = 'SELECT conf.id_configuration, conf.name, conf.value FROM '.$dbname._DB_PREFIX_.'configuration conf WHERE conf.name LIKE \'%' . strtoupper(_DB_PREFIX_) . 'ROUTE%\'';
        $friendly_urls = Db::getInstance()->ExecuteS($query_friendly_urls);
        $url = strval(Configuration::get('PRESTAPP_V2_MODULE_API_BACKEND_URL')).'/'.strval(Configuration::get('PRESTAPP_V2_MODULE_ID_CLUSTER')).'/api/prestashop/v1/sync-friendly-urls';
        $data = array(
            "friendly_urls" => $friendly_urls,
            "db_prefix" => _DB_PREFIX_,
            "schemaShop" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')),
            "idShopname" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')),
        );
        PrestappConnect::sendTo($url, $data);
    }

    public static function sendShopSettings()
    {
        $query_dbname = 'SELECT DATABASE() as dbname';
        $dbname = '`' . (Db::getInstance()->ExecuteS($query_dbname))[0]['dbname'] . '`' . '.';
        $ship_set = new ShippingSettings;
        $ship_set->shipping_free_price_ = (Db::getInstance()->ExecuteS('SELECT conf.value FROM '. $dbname._DB_PREFIX_ . 'configuration conf WHERE conf.name = "PS_SHIPPING_FREE_PRICE"'))[0];
        $ship_set->shipping_free_weight_ = (Db::getInstance()->ExecuteS('SELECT conf.value FROM '. $dbname._DB_PREFIX_ . 'configuration conf WHERE conf.name = "PS_SHIPPING_FREE_WEIGHT"'))[0];
        $ship_set->shipping_handling_ = (Db::getInstance()->ExecuteS('SELECT conf.value FROM '. $dbname._DB_PREFIX_ . 'configuration conf WHERE conf.name = "PS_SHIPPING_HANDLING"'))[0];

        $cookie_key = constant('_COOKIE_KEY_');

        $url = strval(Configuration::get('PRESTAPP_V2_MODULE_API_BACKEND_URL')).'/'.strval(Configuration::get('PRESTAPP_V2_MODULE_ID_CLUSTER')).'/api/prestashop/v1/sync-shop_settings';
        $data = array(
            "shipping_settings" => $ship_set,
            "cookie_key" => $cookie_key,
            "schemaShop" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')),
            "idShopname" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')),
        );
        PrestappConnect::sendTo($url, $data);
    }

    public static function TemplateContent($id_customer, $o_st_id, $o_date, $o_id)
    {
        $customer = new Customer((int) $id_customer);
        $languages = Language::getLanguages(true);

        $customer_fn = $customer->firstname;
        $customer_ln = $customer->lastname;
        $date_add = $o_date;
        $id_order = $o_id;

        $notif_content = Configuration::get('PRESTAPP_V2_MODULE_NOTIF_CONTENTS');


        $search_cont = ['{{firstname}}', '{{lastname}}', '{{order_id}}', '{{date}}'];
        $replaces_cont = [$customer_fn, $customer_ln, strval($id_order), $date_add];
        $notif_content = str_replace($search_cont, $replaces_cont, $notif_content);
        $notif_content = json_decode($notif_content);

//        Changes language id to iso_code

        $order_states = new OrderState((int) $o_st_id, (int) Configuration::get('PS_LANG_DEFAULT'));

        if (!empty($notif_content)) {
            foreach ($notif_content as $key => $value) {
                foreach ($languages as $lang) {
                    if (strtolower($key) === strtolower($lang['iso_code'])) {
                        $order_states = new OrderState((int) $o_st_id, (int) $lang['id_lang']);
                    }
                }
                $notif_content->$key = str_replace
                ('{{order_status}}', $order_states->name, $notif_content->$key);
            }
        }

        return $notif_content;
    }

    public static function TemplateHeading($o_date, $o_id)
    {
        $date_add = $o_date;
        $id_order = $o_id;

        $notif_heading = Configuration::get('PRESTAPP_V2_MODULE_NOTIF_HEADINGS');

        $search_head = ['{{order_id}}', '{{date}}'];
        $replaces_head = [strval($id_order), $date_add];
        $notif_heading = str_replace($search_head, $replaces_head, $notif_heading);

        return json_decode($notif_heading);
    }

    public static function OrderStatusUpdate($params)
    {
        $notif_content = PrestappApi::TemplateContent
            (
                $params['cart']->id_customer,
                $params['newOrderStatus']->id,
                $params['cart']->date_add,
                $params['id_order']
            );
        $notif_heading = PrestappApi::TemplateHeading
            (
                $params['cart']->date_add,
                $params['id_order']
            );


        $order = new Order((int) $params['id_order']);
        $url = strval(Configuration::get('PRESTAPP_V2_MODULE_API_BACKEND_URL')).'/'.strval(Configuration::get('PRESTAPP_V2_MODULE_ID_CLUSTER')).'/api/prestashop/v1/notification-send';
        $data = array(
            "schemaShop" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')),
            "idShopname" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')),
        );
        if (Validate::isLoadedObject($order)) {
            $data = array(
                "contents" => $notif_content,
                "headings" => $notif_heading,
                "id_customer" => array(strval($order->id_customer)),
                "id_shopname" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')),
                "isAndroidS" => true,
                "isIOS" => true,
                "type_notification" => 2,
            );
            // PrestappConnect::sendTo($url, $data);
        }
    }

    /**
     * PRODUCT
     */

    public static function ProductUpdate($params)
    {
        $url = strval(Configuration::get('PRESTAPP_V2_MODULE_API_BACKEND_URL')).'/'.strval(Configuration::get('PRESTAPP_V2_MODULE_ID_CLUSTER')).'/api/prestashop/v1/sync-item-update';
        $data = array(
            "item_name" => "products",
            "item_id" => strval($params['id_product']),
            "schemaShop" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')),
            "idShopname" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')),
        );
        if (Configuration::get('PRESTAPP_V2_MODULE_SYNC_TYPE') === "henvio") {
            PrestappDB::addItem("item-update", "products", strval($params['id_product']), "", strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')), strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')), PrestappDB::arrayToText($params));
        } else {
            PrestappConnect::sendTo($url, $data);
        }
    }

    public static function ProductDelete($params)
    {
        $url = strval(Configuration::get('PRESTAPP_V2_MODULE_API_BACKEND_URL')).'/'.strval(Configuration::get('PRESTAPP_V2_MODULE_ID_CLUSTER')).'/api/prestashop/v1/sync-item-delete';
        $data = array(
            "item_name" => "products",
            "item_id" => strval($params['id_product']),
            "schemaShop" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')),
            "idShopname" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')),
        );
        if (Configuration::get('PRESTAPP_V2_MODULE_SYNC_TYPE') === "henvio") {
            PrestappDB::addItem("item-delete", "products", strval($params['id_product']), "", strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')), strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')), PrestappDB::arrayToText($params));
        } else {
            PrestappConnect::sendTo($url, $data);
        }
    }

    /**
     * CATEGORY
     */

    public static function CategoryUpdate($params)
    {
        $url = strval(Configuration::get('PRESTAPP_V2_MODULE_API_BACKEND_URL')).'/'.strval(Configuration::get('PRESTAPP_V2_MODULE_ID_CLUSTER')).'/api/prestashop/v1/sync-item-update';
        $data = array(
            "item_name" => "categories",
            "item_id" => strval($params['category']->id),
            "schemaShop" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')),
            "idShopname" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')),
        );
        if (Configuration::get('PRESTAPP_V2_MODULE_SYNC_TYPE') === "henvio") {
            PrestappDB::addItem("item-update", "categories", strval($params['category']->id), "", strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')), strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')), PrestappDB::arrayToText($params));
        } else {
            PrestappConnect::sendTo($url, $data);
        }
    }

    public static function CategoryDelete($params)
    {
        $url = strval(Configuration::get('PRESTAPP_V2_MODULE_API_BACKEND_URL')).'/'.strval(Configuration::get('PRESTAPP_V2_MODULE_ID_CLUSTER')).'/api/prestashop/v1/sync-item-delete';
        $data = array(
            "item_name" => "categories",
            "item_id" => strval($params['category']->id_category),
            "schemaShop" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')),
            "idShopname" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')),
        );
        if (Configuration::get('PRESTAPP_V2_MODULE_SYNC_TYPE') === "henvio") {
            PrestappDB::addItem("item-delete", "categories", strval($params['category']->id), "", strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')), strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')), PrestappDB::arrayToText($params));
        } else {
            PrestappConnect::sendTo($url, $data);
        }
    }

    /**
     * ATTRIBUTES
     */

    public static function AttributeGroupUpdate($params)
    {
        $url = strval(Configuration::get('PRESTAPP_V2_MODULE_API_BACKEND_URL')).'/'.strval(Configuration::get('PRESTAPP_V2_MODULE_ID_CLUSTER')).'/api/prestashop/v1/sync-table-update';
        $data = array(
            "item_name" => "product_options",
            "schemaShop" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')),
            "idShopname" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')),
        );
        if (Configuration::get('PRESTAPP_V2_MODULE_SYNC_TYPE') === "henvio") {
            PrestappDB::addItem("table-update", "product_options", "", "", strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')), strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')), PrestappDB::arrayToText($params));
        } else {
            PrestappConnect::sendTo($url, $data);
        }
    }

    public static function AttributeGroupDelete($params)
    {
        $url = strval(Configuration::get('PRESTAPP_V2_MODULE_API_BACKEND_URL')).'/'.strval(Configuration::get('PRESTAPP_V2_MODULE_ID_CLUSTER')).'/api/prestashop/v1/sync-item-delete';
        $data = array(
            "item_name" => "product_options",
            "item_id" => $params['id_attribute_group'],
            "schemaShop" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')),
            "idShopname" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')),
        );
        if (Configuration::get('PRESTAPP_V2_MODULE_SYNC_TYPE') === "henvio") {
            PrestappDB::addItem("item-delete", "product_options", $params['id_attribute_group'], "", strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')), strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')), PrestappDB::arrayToText($params));
        } else {
            PrestappConnect::sendTo($url, $data);
        }
    }

    public static function AttributeUpdate($params)
    {
        $url = strval(Configuration::get('PRESTAPP_V2_MODULE_API_BACKEND_URL')).'/'.strval(Configuration::get('PRESTAPP_V2_MODULE_ID_CLUSTER')).'/api/prestashop/v1/sync-table-update';
        $data = array(
            "item_name" => "product_option_values",
            "schemaShop" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')),
            "idShopname" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')),
        );
        if (Configuration::get('PRESTAPP_V2_MODULE_SYNC_TYPE') === "henvio") {
            PrestappDB::addItem("table-delete", "product_option_values", "", "", strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')), strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')), PrestappDB::arrayToText($params));
        } else {
            PrestappConnect::sendTo($url, $data);
        }
    }

    /**
     * MANUFACTURERS
     */

    public static function ManufacturerUpdate($params)
    {
        $url = strval(Configuration::get('PRESTAPP_V2_MODULE_API_BACKEND_URL')).'/'.strval(Configuration::get('PRESTAPP_V2_MODULE_ID_CLUSTER')).'/api/prestashop/v1/sync-table-update';
        $data = array(
            "item_name" => "manufacturers",
            "schemaShop" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')),
            "idShopname" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')),
        );
        if (Configuration::get('PRESTAPP_V2_MODULE_SYNC_TYPE') === "henvio") {
            PrestappDB::addItem("table-update", "manufacturers", "", "", strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')), strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')), PrestappDB::arrayToText($params));
        } else {
            PrestappConnect::sendTo($url, $data);
        }
    }

    public static function ManufacturerDelete($params)
    {
        $url = strval(Configuration::get('PRESTAPP_V2_MODULE_API_BACKEND_URL')).'/'.strval(Configuration::get('PRESTAPP_V2_MODULE_ID_CLUSTER')).'/api/prestashop/v1/sync-item-delete';
        $data = array(
            "item_name" => "manufacturers",
            "item_id" => strval($params['object']->id),
            "schemaShop" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')),
            "idShopname" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')),
        );
        if (Configuration::get('PRESTAPP_V2_MODULE_SYNC_TYPE') === "henvio") {
            PrestappDB::addItem("item-delete", "manufacturers", strval($params['object']->id), "", strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')), strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')), PrestappDB::arrayToText($params));
        } else {
            PrestappConnect::sendTo($url, $data);
        }
    }

    /**
     * ADDRESSES
     */
    public static function AddressUpdate($params)
    {
        $url = strval(Configuration::get('PRESTAPP_V2_MODULE_API_BACKEND_URL')).'/'.strval(Configuration::get('PRESTAPP_V2_MODULE_ID_CLUSTER')).'/api/prestashop/v1/sync-item-update';
        $data = array(
            "item_name" => "addresses",
            "item_id" => strval($params['object']->id),
            "schemaShop" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')),
            "idShopname" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')),
        );
        // if (Configuration::get('PRESTAPP_V2_MODULE_SYNC_TYPE') === "henvio") {
        //     PrestappDB::addItem("item-update", "addresses", strval($params['object']->id), "", strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')), strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')), PrestappDB::arrayToText($params));
        // } else {
        //     PrestappConnect::sendTo($url, $data);
        // }
        PrestappConnect::sendTo($url, $data);
    }

    public static function AddressDelete($params)
    {
        $url = strval(Configuration::get('PRESTAPP_V2_MODULE_API_BACKEND_URL')).'/'.strval(Configuration::get('PRESTAPP_V2_MODULE_ID_CLUSTER')).'/api/prestashop/v1/sync-item-delete';
        $data = array(
            "item_name" => "addresses",
            "item_id" => strval($params['object']->id),
            "schemaShop" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')),
            "idShopname" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')),
        );
        // if (Configuration::get('PRESTAPP_V2_MODULE_SYNC_TYPE') === "henvio") {
        //     PrestappDB::addItem("item-delete", "addresses", strval($params['object']->id), "", strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')), strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')), PrestappDB::arrayToText($params));
        // } else {
        //     PrestappConnect::sendTo($url, $data);
        // }
        PrestappConnect::sendTo($url, $data);
    }

    /**
     * CUSTOMERS
     */

    public static function CustomerUpdateItem($customerId)
    {
        $url = strval(Configuration::get('PRESTAPP_V2_MODULE_API_BACKEND_URL')).'/'.strval(Configuration::get('PRESTAPP_V2_MODULE_ID_CLUSTER')).'/api/prestashop/v1/sync-item-update';
        $data = array(
            "item_name" => "customers",
            "item_id" => strval($customerId),
            "schemaShop" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')),
            "idShopname" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')),
        );
        // if (Configuration::get('PRESTAPP_V2_MODULE_SYNC_TYPE') === "henvio") {
        //     PrestappDB::addItem("item-update", "customers", strval($customerId), "", strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')), strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')), PrestappDB::arrayToText($customerId));
        // } else {
        //     PrestappConnect::sendTo($url, $data);
        // }
        PrestappConnect::sendTo($url, $data);
    }

    public static function CustomerDelete($params)
    {
        $url = strval(Configuration::get('PRESTAPP_V2_MODULE_API_BACKEND_URL')).'/'.strval(Configuration::get('PRESTAPP_V2_MODULE_ID_CLUSTER')).'/api/prestashop/v1/sync-item-delete';
        $data = array(
            "item_name" => "customers",
            "item_id" => strval($params['object']->id),
            "schemaShop" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')),
            "idShopname" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')),
        );
        // if (Configuration::get('PRESTAPP_V2_MODULE_SYNC_TYPE') === "henvio") {
        //     PrestappDB::addItem("item-delete", "customers", strval($params), "", strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')), strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')), PrestappDB::arrayToText($params));
        // } else {
        //     PrestappConnect::sendTo($url, $data);
        // }
        PrestappConnect::sendTo($url, $data);
    }

    /**
     * CARRIER
     */

    public static function CarrierUpdateTable($params)
    {
        $url = strval(Configuration::get('PRESTAPP_V2_MODULE_API_BACKEND_URL')).'/'.strval(Configuration::get('PRESTAPP_V2_MODULE_ID_CLUSTER')).'/api/prestashop/v1/sync-table-update';
        $data = array(
            "item_name" => "carriers",
            "schemaShop" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')),
            "idShopname" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')),
        );
        if (Configuration::get('PRESTAPP_V2_MODULE_SYNC_TYPE') === "henvio") {
            PrestappDB::addItem("table-update", "carriers", "", "", strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')), strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')), PrestappDB::arrayToText($params));
        } else {
            PrestappConnect::sendTo($url, $data);
        }
    }
    public static function CarrierUpdateItem($params)
    {
        $url = strval(Configuration::get('PRESTAPP_V2_MODULE_API_BACKEND_URL')).'/'.strval(Configuration::get('PRESTAPP_V2_MODULE_ID_CLUSTER')).'/api/prestashop/v1/sync-item-update';
        $data = array(
            "item_name" => "carriers",
            "item_id" => strval($params['object']->id),
            "schemaShop" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')),
            "idShopname" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')),
        );
        if (Configuration::get('PRESTAPP_V2_MODULE_SYNC_TYPE') === "henvio") {
            PrestappDB::addItem("item-update", "carriers", strval($params['object']->id), "", strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')), strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')), PrestappDB::arrayToText($params));
        } else {
            PrestappConnect::sendTo($url, $data);
        }
    }

    /**
     * CART RULES
     */

    public static function CartRuleUpdateTable($params)
    {
        $url = strval(Configuration::get('PRESTAPP_V2_MODULE_API_BACKEND_URL')).'/'.strval(Configuration::get('PRESTAPP_V2_MODULE_ID_CLUSTER')).'/api/prestashop/v1/sync-table-update';
        $data = array(
            "item_name" => "cart_rules",
            "schemaShop" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')),
            "idShopname" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')),
        );
        if (Configuration::get('PRESTAPP_V2_MODULE_SYNC_TYPE') === "henvio") {
            PrestappDB::addItem("table-update", "cart_rules", "", "", strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')), strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')), PrestappDB::arrayToText($params));
        } else {
            PrestappConnect::sendTo($url, $data);
        }
    }
    public static function CartRuleDelete($cartRuleId)
    {
        $url = strval(Configuration::get('PRESTAPP_V2_MODULE_API_BACKEND_URL')).'/'.strval(Configuration::get('PRESTAPP_V2_MODULE_ID_CLUSTER')).'/api/prestashop/v1/sync-item-delete';
        $data = array(
            "item_name" => "cart_rules",
            "item_id" => strval($cartRuleId),
            "schemaShop" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')),
            "idShopname" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')),
        );
        if (Configuration::get('PRESTAPP_V2_MODULE_SYNC_TYPE') === "henvio") {
            PrestappDB::addItem("table-delete", "cart_rules", strval($cartRuleId), "", strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')), strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')), PrestappDB::arrayToText($cartRuleId));
        } else {
            PrestappConnect::sendTo($url, $data);
        }
    }

    /**
     * CART
     */

    public static function CartUpdate($cartId)
    {
        $url = strval(Configuration::get('PRESTAPP_V2_MODULE_API_BACKEND_URL')).'/'.strval(Configuration::get('PRESTAPP_V2_MODULE_ID_CLUSTER')).'/api/prestashop/v1/sync-item-update';
        $data = array(
            "item_name" => "carts",
            "item_id" => strval($cartId),
            "schemaShop" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')),
            "idShopname" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')),
        );
        // if (Configuration::get('PRESTAPP_V2_MODULE_SYNC_TYPE') === "henvio") {
        //     PrestappDB::addItem("item-update", "carts", strval($cartId), "", strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')), strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')), PrestappDB::arrayToText($cartId));
        // } else {
        //     PrestappConnect::sendTo($url, $data);
        // }
        PrestappConnect::sendTo($url, $data);
    }

    public static function CartDelete($cartId)
    {
        $url = strval(Configuration::get('PRESTAPP_V2_MODULE_API_BACKEND_URL')).'/'.strval(Configuration::get('PRESTAPP_V2_MODULE_ID_CLUSTER')).'/api/prestashop/v1/sync-item-delete';
        $data = array(
            "item_name" => "carts",
            "item_id" => strval($cartId),
            "schemaShop" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')),
            "idShopname" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')),
        );
        // if (Configuration::get('PRESTAPP_V2_MODULE_SYNC_TYPE') === "henvio") {
        //     PrestappDB::addItem("item-delete", "carts", strval($cartId), "", strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')), strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')), PrestappDB::arrayToText($cartId));
        // } else {
        //     PrestappConnect::sendTo($url, $data);
        // }
        PrestappConnect::sendTo($url, $data);
    }

    /**
     * ORDER
     */

    public static function OrderUpdate($orderId)
    {
        $url = strval(Configuration::get('PRESTAPP_V2_MODULE_API_BACKEND_URL')).'/'.strval(Configuration::get('PRESTAPP_V2_MODULE_ID_CLUSTER')).'/api/prestashop/v1/sync-item-update';
        $data = array(
            "item_name" => "orders",
            "item_id" => strval($orderId),
            "schemaShop" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')),
            "idShopname" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')),
        );
        // if (Configuration::get('PRESTAPP_V2_MODULE_SYNC_TYPE') === "henvio") {
        //     PrestappDB::addItem("item-update", "orders", strval($orderId), "", strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')), strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')), PrestappDB::arrayToText($orderId));
        // } else {
        //     PrestappConnect::sendTo($url, $data);
        // }
        PrestappConnect::sendTo($url, $data);
    }


    /**
     * COUNTRIES
     */

    public static function CountryUpdateItem($countryId)
    {
        $url = strval(Configuration::get('PRESTAPP_V2_MODULE_API_BACKEND_URL')).'/'.strval(Configuration::get('PRESTAPP_V2_MODULE_ID_CLUSTER')).'/api/prestashop/v1/sync-item-update';
        $data = array(
            "item_name" => "countries",
            "item_id" => strval($countryId),
            "schemaShop" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')),
            "idShopname" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')),
        );
        if (Configuration::get('PRESTAPP_V2_MODULE_SYNC_TYPE') === "henvio") {
            PrestappDB::addItem("item-update", "countries", strval($countryId), "", strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')), strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')), PrestappDB::arrayToText($countryId));
        } else {
            PrestappConnect::sendTo($url, $data);
        }
    }
    public static function CountryDelete($countryId)
    {
        $url = strval(Configuration::get('PRESTAPP_V2_MODULE_API_BACKEND_URL')).'/'.strval(Configuration::get('PRESTAPP_V2_MODULE_ID_CLUSTER')).'/api/prestashop/v1/sync-item-delete';
        $data = array(
            "item_name" => "countries",
            "item_id" => strval($countryId),
            "schemaShop" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')),
            "idShopname" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')),
        );
        if (Configuration::get('PRESTAPP_V2_MODULE_SYNC_TYPE') === "henvio") {
            PrestappDB::addItem("item-delete", "countries", strval($countryId), "", strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')), strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')), PrestappDB::arrayToText($countryId));
        } else {
            PrestappConnect::sendTo($url, $data);
        }
    }

    /**
     * CURRENCIES
     */

    public static function CurrencyUpdateTable($params)
    {
        $url = strval(Configuration::get('PRESTAPP_V2_MODULE_API_BACKEND_URL')).'/'.strval(Configuration::get('PRESTAPP_V2_MODULE_ID_CLUSTER')).'/api/prestashop/v1/sync-table-update';
        $data = array(
            "item_name" => "currencies",
            "schemaShop" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')),
            "idShopname" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')),
        );
        PrestappDB::addItem("table-update", "currencies", "", "", strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')), strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')), PrestappDB::arrayToText($params));
        PrestappConnect::sendTo($url, $data);
    }

    /**
     * Hooks action for LANGUAGES
     */
    public static function LanguageUpdateTable($params)
    {
        $url = strval(Configuration::get('PRESTAPP_V2_MODULE_API_BACKEND_URL')).'/'.strval(Configuration::get('PRESTAPP_V2_MODULE_ID_CLUSTER')).'/api/prestashop/v1/sync-table-update';
        $data = array(
            "item_name" => "languages",
            "schemaShop" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')),
            "idShopname" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')),
        );
        if (Configuration::get('PRESTAPP_V2_MODULE_SYNC_TYPE') === "henvio") {
            PrestappDB::addItem("table-update", "languages", "", "", strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')), strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')), PrestappDB::arrayToText($params));
        } else {
            PrestappConnect::sendTo($url, $data);
        }
    }
    public static function LanguageDelete($languageId)
    {
        $url = strval(Configuration::get('PRESTAPP_V2_MODULE_API_BACKEND_URL')).'/'.strval(Configuration::get('PRESTAPP_V2_MODULE_ID_CLUSTER')).'/api/prestashop/v1/sync-item-delete';
        $data = array(
            "item_name" => "languages",
            "item_id" => strval($languageId),
            "schemaShop" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')),
            "idShopname" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')),
        );
        if (Configuration::get('PRESTAPP_V2_MODULE_SYNC_TYPE') === "henvio") {
            PrestappDB::addItem("item-delete", "languages", strval($languageId), "", strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')), strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')), PrestappDB::arrayToText($languageId));
        } else {
            PrestappConnect::sendTo($url, $data);
        }
    }
    /**
     * Hooks action for SPECIFIC PRICES
     */
    public static function SpecificPriceUpdateTable($params)
    {
        $url = strval(Configuration::get('PRESTAPP_V2_MODULE_API_BACKEND_URL')).'/'.strval(Configuration::get('PRESTAPP_V2_MODULE_ID_CLUSTER')).'/api/prestashop/v1/sync-table-update';
        $data = array(
            "item_name" => "specific_prices",
            "schemaShop" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')),
            "idShopname" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')),
            "id_product" => strval($params['object']->id_product),
        );
        if (Configuration::get('PRESTAPP_V2_MODULE_SYNC_TYPE') === "henvio") {
            PrestappDB::addItem("table-update", "specific_prices", "", strval($params['object']->id_product), strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')), strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')), PrestappDB::arrayToText($params));
        } else {
            PrestappConnect::sendTo($url, $data);
        }
    }
    public static function SpecificPriceDelete($params)
    {
        $url = strval(Configuration::get('PRESTAPP_V2_MODULE_API_BACKEND_URL')).'/'.strval(Configuration::get('PRESTAPP_V2_MODULE_ID_CLUSTER')).'/api/prestashop/v1/sync-item-delete';
        $data = array(
            "item_name" => "specific_prices",
            "item_id" => strval($params['object']->id),
            "id_product" => strval($params['object']->id_product),
            "schemaShop" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')),
            "idShopname" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')),
        );
        if (Configuration::get('PRESTAPP_V2_MODULE_SYNC_TYPE') === "henvio") {
            PrestappDB::addItem("item-delete", "specific_prices", strval($params['object']->id), strval($params['object']->id_product), strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')), strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')), PrestappDB::arrayToText($params));
        } else {
            PrestappConnect::sendTo($url, $data);
        }
    }


    /**
     * Hooks action for TAX RULES
     */
    public static function TaxRuleUpdateTable($params)
    {
        $url = strval(Configuration::get('PRESTAPP_V2_MODULE_API_BACKEND_URL')).'/'.strval(Configuration::get('PRESTAPP_V2_MODULE_ID_CLUSTER')).'/api/prestashop/v1/sync-table-update';
        $data = array(
            "item_name" => "tax_rules",
            "schemaShop" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')),
            "idShopname" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')),
        );
        if (Configuration::get('PRESTAPP_V2_MODULE_SYNC_TYPE') === "henvio") {
            PrestappDB::addItem("table-update", "tax_rules", "", "", strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')), strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')), PrestappDB::arrayToText($params));
        } else {
            PrestappConnect::sendTo($url, $data);
        }
    }
    public static function TaxRuleDelete($taxRuleId)
    {
        $url = strval(Configuration::get('PRESTAPP_V2_MODULE_API_BACKEND_URL')).'/'.strval(Configuration::get('PRESTAPP_V2_MODULE_ID_CLUSTER')).'/api/prestashop/v1/sync-item-delete';
        $data = array(
            "item_name" => "tax_rules",
            "item_id" => strval($taxRuleId),
            "schemaShop" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')),
            "idShopname" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')),
        );
        if (Configuration::get('PRESTAPP_V2_MODULE_SYNC_TYPE') === "henvio") {
            PrestappDB::addItem("item-delete", "tax_rules", strval($taxRuleId), "", strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')), strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')), PrestappDB::arrayToText($taxRuleId));
        } else {
            PrestappConnect::sendTo($url, $data);
        }
    }

    /**
     * Hooks action for TAX RULE GROUPS
     */

    public static function TaxRulesGroupUpdateTable($params)
    {
        $url = strval(Configuration::get('PRESTAPP_V2_MODULE_API_BACKEND_URL')).'/'.strval(Configuration::get('PRESTAPP_V2_MODULE_ID_CLUSTER')).'/api/prestashop/v1/sync-table-update';
        $data = array(
            "item_name" => "tax_rule_groups",
            "schemaShop" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')),
            "idShopname" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')),
        );
        if (Configuration::get('PRESTAPP_V2_MODULE_SYNC_TYPE') === "henvio") {
            PrestappDB::addItem("table-update", "tax_rule_groups", "", "", strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')), strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')), PrestappDB::arrayToText($params));
        } else {
            PrestappConnect::sendTo($url, $data);
        }
    }
    public static function TaxRulesGroupDelete($taxRulesGroupId)
    {
        $url = strval(Configuration::get('PRESTAPP_V2_MODULE_API_BACKEND_URL')).'/'.strval(Configuration::get('PRESTAPP_V2_MODULE_ID_CLUSTER')).'/api/prestashop/v1/sync-item-delete';
        $data = array(
            "item_name" => "tax_rule_groups",
            "item_id" => strval($taxRulesGroupId),
            "schemaShop" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')),
            "idShopname" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')),
        );
        if (Configuration::get('PRESTAPP_V2_MODULE_SYNC_TYPE') === "henvio") {
            PrestappDB::addItem("item-delete", "tax_rule_groups", strval($taxRulesGroupId), "", strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')), strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')), PrestappDB::arrayToText($taxRulesGroupId));
        } else {
            PrestappConnect::sendTo($url, $data);
        }
    }

    /**
     * Hooks action for TAXES
     */
    public static function TaxUpdateTable($params)
    {
        $url = strval(Configuration::get('PRESTAPP_V2_MODULE_API_BACKEND_URL')).'/'.strval(Configuration::get('PRESTAPP_V2_MODULE_ID_CLUSTER')).'/api/prestashop/v1/sync-table-update';
        $data = array(
            "item_name" => "taxes",
            "id_product" => $params,
            "schemaShop" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')),
            "idShopname" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')),
        );
        if (Configuration::get('PRESTAPP_V2_MODULE_SYNC_TYPE') === "henvio") {
            PrestappDB::addItem("table-update", "taxes", strval($taxRulesGroupId), "", strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')), strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')), PrestappDB::arrayToText($params));
        } else {
            PrestappConnect::sendTo($url, $data);
        }
    }
    public static function TaxDelete($taxId)
    {
        $url = strval(Configuration::get('PRESTAPP_V2_MODULE_API_BACKEND_URL')).'/'.strval(Configuration::get('PRESTAPP_V2_MODULE_ID_CLUSTER')).'/api/prestashop/v1/sync-item-delete';
        $data = array(
            "item_name" => "taxes",
            "item_id" => strval($taxId),
            "schemaShop" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')),
            "idShopname" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')),
        );
        if (Configuration::get('PRESTAPP_V2_MODULE_SYNC_TYPE') === "henvio") {
            PrestappDB::addItem("item-delete", "taxes", strval($taxId), "", strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')), strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')), PrestappDB::arrayToText($taxId));
        } else {
            PrestappConnect::sendTo($url, $data);
        }
    }

    /**
     * Hooks action for STOCK AVAILABLE
     */

    public static function StockAvailableUpdateItem($stockAvailableId)
    {
        $url = strval(Configuration::get('PRESTAPP_V2_MODULE_API_BACKEND_URL')).'/'.strval(Configuration::get('PRESTAPP_V2_MODULE_ID_CLUSTER')).'/api/prestashop/v1/sync-item-update';
        $data = array(
            "item_name" => "stock_availables",
            "item_id" => strval($stockAvailableId),
            "schemaShop" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')),
            "idShopname" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')),
        );
        // if (Configuration::get('PRESTAPP_V2_MODULE_SYNC_TYPE') === "henvio") {
        //     PrestappDB::addItem("item-update", "stock_availables", strval($stockAvailableId), "", strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')), strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')), PrestappDB::arrayToText($stockAvailableId));
        // } else {
        //     PrestappConnect::sendTo($url, $data);
        // }
        PrestappConnect::sendTo($url, $data);
    }
    /**
     * Hooks action for COMBINATIONS
     */
    public static function CombinationUpdateItem($combinationId)
    {
        $url = strval(Configuration::get('PRESTAPP_V2_MODULE_API_BACKEND_URL')).'/'.strval(Configuration::get('PRESTAPP_V2_MODULE_ID_CLUSTER')).'/api/prestashop/v1/sync-item-update';
        $data = array(
            "item_name" => "combinations",
            "item_id" => strval($combinationId),
            "schemaShop" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')),
            "idShopname" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')),
        );
        if (Configuration::get('PRESTAPP_V2_MODULE_SYNC_TYPE') === "henvio") {
            PrestappDB::addItem("item-update", "combinations", strval($combinationId), "", strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')), strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')), PrestappDB::arrayToText($combinationId));
        } else {
            PrestappConnect::sendTo($url, $data);
        }
    }
    public static function CombinationDelete($params)
    {
        $url = strval(Configuration::get('PRESTAPP_V2_MODULE_API_BACKEND_URL')).'/'.strval(Configuration::get('PRESTAPP_V2_MODULE_ID_CLUSTER')).'/api/prestashop/v1/sync-item-delete';
        $data = array(
            "item_name" => "combinations",
            "item_id" => strval($params['object']->id),
            "id_product" => strval($params['object']->id_product),
            "schemaShop" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')),
            "idShopname" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')),
        );
        if (Configuration::get('PRESTAPP_V2_MODULE_SYNC_TYPE') === "henvio") {
            PrestappDB::addItem("item-delete", "combinations", strval($params['object']->id), strval($params['object']->id_product), strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')), strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')), PrestappDB::arrayToText($params));
        } else {
            PrestappConnect::sendTo($url, $data);
        }
    }

    /**
     * Hooks action for FRIENDLY URL
     */


    /**
     * Hooks action for CMS
     */
    public static function CMSUpdate($cmsId)
    {
        $url = strval(Configuration::get('PRESTAPP_V2_MODULE_API_BACKEND_URL')).'/'.strval(Configuration::get('PRESTAPP_V2_MODULE_ID_CLUSTER')).'/api/prestashop/v1/sync-item-update';
        $data = array(
            "item_name" => "content_management_system",
            "item_id" => strval($cmsId),
            "schemaShop" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')),
            "idShopname" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')),
        );
        if (Configuration::get('PRESTAPP_V2_MODULE_SYNC_TYPE') === "henvio") {
            PrestappDB::addItem("item-update", "content_management_system", strval($cmsId), "", strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')), strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')), PrestappDB::arrayToText($cmsId));
        } else {
            PrestappConnect::sendTo($url, $data);
        }
    }
    public static function CMSDelete($cmsId)
    {
        $url = strval(Configuration::get('PRESTAPP_V2_MODULE_API_BACKEND_URL')).'/'.strval(Configuration::get('PRESTAPP_V2_MODULE_ID_CLUSTER')).'/api/prestashop/v1/sync-item-delete';
        $data = array(
            "item_name" => "content_management_system",
            "item_id" => strval($cmsId),
            "schemaShop" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')),
            "idShopname" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')),
        );
        if (Configuration::get('PRESTAPP_V2_MODULE_SYNC_TYPE') === "henvio") {
            PrestappDB::addItem("item-delete", "content_management_system", strval($cmsId), "", strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')), strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')), PrestappDB::arrayToText($cmsId));
        } else {
            PrestappConnect::sendTo($url, $data);
        };
    }

    public static function FeaturesDelete ($features) {
        $url = strval(Configuration::get('PRESTAPP_V2_MODULE_API_BACKEND_URL')).'/'.strval(Configuration::get('PRESTAPP_V2_MODULE_ID_CLUSTER')).'/api/prestashop/v1/sync-item-delete';
        $data = array(
            "item_name" => "product_features",
            "item_id" => strval($features),
            "schemaShop" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')),
            "idShopname" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')),
        );
        if (Configuration::get('PRESTAPP_V2_MODULE_SYNC_TYPE') === "henvio") {
            PrestappDB::addItem("item-delete", "product_features", strval($features), "", strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')), strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')), PrestappDB::arrayToText($features));
        } else {
            PrestappConnect::sendTo($url, $data);
        };
    }

    public static function FeaturesUpdate ($features) {
        $url = strval(Configuration::get('PRESTAPP_V2_MODULE_API_BACKEND_URL')).'/'.strval(Configuration::get('PRESTAPP_V2_MODULE_ID_CLUSTER')).'/api/prestashop/v1/sync-item-update';
        $data = array(
            "item_name" => "product_features",
            "item_id" => strval($features),
            "schemaShop" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')),
            "idShopname" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')),
        );
        if (Configuration::get('PRESTAPP_V2_MODULE_SYNC_TYPE') === "henvio") {
            PrestappDB::addItem("item-update", "product_features", strval($features), "", strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')), strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')), PrestappDB::arrayToText($features));
        } else {
            PrestappConnect::sendTo($url, $data);
        };
    }

    public static function FeaturesValueDelete ($features_value) {
        $url = strval(Configuration::get('PRESTAPP_V2_MODULE_API_BACKEND_URL')).'/'.strval(Configuration::get('PRESTAPP_V2_MODULE_ID_CLUSTER')).'/api/prestashop/v1/sync-item-delete';
        $data = array(
            "item_name" => "product_feature_values",
            "item_id" => strval($features_value),
            "schemaShop" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')),
            "idShopname" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')),
        );
        if (Configuration::get('PRESTAPP_V2_MODULE_SYNC_TYPE') === "henvio") {
            PrestappDB::addItem("item-delete", "product_feature_values", strval($features_value), "", strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')), strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')), PrestappDB::arrayToText($features_value));
        } else {
            PrestappConnect::sendTo($url, $data);
        };
    }

    public static function FeaturesValueUpdate ($features_value) {
        $url = strval(Configuration::get('PRESTAPP_V2_MODULE_API_BACKEND_URL')).'/'.strval(Configuration::get('PRESTAPP_V2_MODULE_ID_CLUSTER')).'/api/prestashop/v1/sync-item-update';
        $data = array(
            "item_name" => "product_feature_values",
            "item_id" => strval($features_value),
            "schemaShop" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')),
            "idShopname" => strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')),
        );
        if (Configuration::get('PRESTAPP_V2_MODULE_SYNC_TYPE') === "henvio") {
            PrestappDB::addItem("item-update", "product_feature_values", strval($features_value), "", strval(Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME')), strval(Configuration::get('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP')), PrestappDB::arrayToText($features_value));
        } else {
            PrestappConnect::sendTo($url, $data);
        };
    }

}