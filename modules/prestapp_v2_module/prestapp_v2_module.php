<?php
/**
* 2007-2021 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2021 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once(dirname(__FILE__) . '/lib/PrestappApi.php');
require_once(dirname(__FILE__) . '/lib/PrestappConnect.php');
require_once(dirname(__FILE__) . '/config.php');

class Prestapp_V2_Module extends Module
{
    protected $config_form = false;
    protected $_html = '';

    const BACKEND_URL = PrestappV2ModuleConfig::BACKEND_URL;
    const SYNC_TYPE = PrestappV2ModuleConfig::SYNC_TYPE;
    const SCHEMA_SHOP = PrestappV2ModuleConfig::SCHEMA_SHOP;

    const NOTIF_CONTENTS = '{"en":"Dear, {{firstname}} {{lastname}}. Status of order {{order_id}} dated  {{date}} was changed to the following {{order_status}}","es":"Estimado, {{firstname}} {{lastname}}. Estado del pedido {{order_id}} de fecha  {{date}} había cambiado a lo siguiente {{order_status}}","ru":"Уважаемый, {{firstname}} {{lastname}}. Статус ордера {{order_id}} от  {{date}} был изменен на {{order_status}}"}';
    const NOTIF_HEADINGS = '{"en":"Update status of order {{order_id}} dated {{date}}","es":"Renovación del estado del pedido {{order_id}} de fecha {{date}}","ru":"Обновление статуса ордера {{order_id}} от {{date}}"}';

    const AVAILABLE_HOOKS = [
        'header',
        'backOfficeHeader',
        'actionAuthentication',
        'actionOrderStatusUpdate',
        'actionProductSave',
        'actionProductUpdate',
        'actionProductDelete',
        'actionProductAdd',
        'actionProductAttributeDelete',
        'actionProductAttributeUpdate',
        'actionCategoryAdd',
        'actionCategoryUpdate',
        'actionCategoryDelete',
        'actionAttributeGroupSave',
        'actionAttributeSave',
        'actionAttributeGroupDelete',
        'actionAttributeDelete',
        'actionAttributeCombinationDelete',
        'actionProductAttributeDelete',
        'actionProductAttributeUpdate',
        'actionObjectManufacturerAddAfter',
        'actionObjectManufacturerUpdateAfter',
        'actionAfterCreateManufacturerAddressFormHandler',
        'actionObjectManufacturerDeleteAfter',
        'actionObjectAddressAddAfter',
        'actionObjectAddressUpdateAfter',
        'actionObjectAddressDeleteAfter',
        'actionCustomerAccountAdd',
        'actionCustomerAccountUpdate',
        'actionObjectCustomerAddAfter',
        'actionObjectCustomerUpdateAfter',
        'actionObjectCustomerDeleteAfter',
        'actionObjectCarrierAddAfter',
        'actionObjectCarrierUpdateAfter',
        'actionCarrierProcess',
        'actionObjectCartRuleAddAfter',
        'actionObjectCartRuleUpdateAfter',
        'actionObjectCartRuleDeleteAfter',
        'actionObjectCountryAddAfter',
        'actionObjectCountryUpdateAfter',
        'actionObjectCurrencyAddAfter',
        'actionObjectCurrencyUpdateAfter',
        'actionObjectLanguageAddAfter',
        'actionObjectLanguageUpdateAfter',
        'actionObjectLanguageDeleteAfter',
        'actionObjectSpecificPriceAddAfter',
        'actionObjectSpecificPriceUpdateAfter',
        'actionObjectSpecificPriceDeleteAfter',
        'actionObjectTaxRuleAddAfter',
        'actionObjectTaxRuleUpdateAfter',
        'actionObjectTaxRuleDeleteAfter',
        'actionObjectTaxRulesGroupAddAfter',
        'actionObjectTaxRulesGroupUpdateAfter',
        'actionObjectTaxRulesGroupDeleteAfter',
        'actionObjectTaxUpdateAfter',
        'actionObjectTaxDeleteAfter',
        'actionObjectTaxAddAfter',
        'actionObjectStockAvailableUpdateAfter',
        'actionObjectCombinationAddAfter',
        'actionObjectCombinationUpdateAfter',
        'actionObjectCombinationDeleteAfter',
        'actionObjectCartAddAfter',
        'actionObjectCartUpdateAfter',
        'actionObjectCartDeleteAfter',
        'actionObjectOrderAddAfter',
        'actionObjectOrderUpdateAfter',
        'actionObjectCMSAddAfter',
        'actionObjectCMSUpdateAfter',
        'actionObjectCMSDeleteAfter',
        'actionFeatureDelete',
        'actionFeatureSave',
        'actionFeatureValueDelete',
        'actionFeatureValueSave',
        'ModuleRoutes',
    ];
    public function __construct()
    {
        $this->name = 'prestapp_v2_module';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Galvintec';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('PrestApp V2 Module');
        $this->description = $this->l('This is a module of PrestaShop');
        $this->confirmUninstall = $this->l('Are you sure about removing this module?', 'prestapp_v2_module');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    public static function getBackendUrl()
    {
        return self::BACKEND_URL;
    }

    public static function getSyncType()
    {
        return self::SYNC_TYPE;
    }

    public static function getSchemaShop()
    {
        return self::SCHEMA_SHOP;
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        $sqlCreateTableOrders = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'prestapp_v2_orders` (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `id_payment` varchar(255),
            `id_customer` varchar(10),
            `id_address_delivery` varchar(10),
            `id_address_invoice` varchar(10),
            `id_cart` varchar(10),
            `id_currency` varchar(10),
            `id_lang` varchar(10),
            `id_carrier` varchar(10),
            `module` varchar(256),
            `payment` varchar(256),
            `total_paid` varchar(65),
            `total_real_paid` varchar(65),
            `total_products` varchar(65),
            `total_products_wt` varchar(65),
            `conversion_rate` varchar(65),
            `stripe_token` varchar(256),
            `id_transaction` varchar(256),
            `reference` varchar(16),
            `order_created` varchar(65),
            `current_state` varchar(4),
            `description` text,
            `date_add` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `date_upd` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE  `UNIQ` (  `id` )
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8';

        if (Db::getInstance()->execute($sqlCreateTableOrders) == false) {
            return false;
        }
        
        $backendUrl = $this->getBackendUrl();
        $sync_type = $this->getSyncType();
        $schema_shop = $this->getSchemaShop();
        $old_order_state = Configuration::get('PS_OS_WS_PAYMENT');
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        if (!parent::install() ||
            !$this->registerHook(self::AVAILABLE_HOOKS) ||
            // !$this->addOrderState($this->l('Prestapp order created')) ||
            !Configuration::updateValue('PRESTAPP_V2_MODULE_SYNC_TYPE', $sync_type) ||
            !Configuration::updateValue('PRESTAPP_V2_MODULE_LOGIN', null) ||
            !Configuration::updateValue('PRESTAPP_V2_MODULE_PASSWORD', null) ||
            !Configuration::updateValue('PRESTAPP_V2_MODULE_API_BACKEND_URL', $backendUrl) ||
            !Configuration::updateValue('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP', $schema_shop) ||
            !Configuration::updateValue('PRESTAPP_V2_MODULE_API_BACKEND_APP_ID', null) ||
            !Configuration::updateValue('PRESTAPP_V2_MODULE_NOTIF_CONTENTS', self::NOTIF_CONTENTS) ||
            !Configuration::updateValue('PRESTAPP_V2_MODULE_NOTIF_HEADINGS', self::NOTIF_HEADINGS) ||
            !Configuration::updateValue('PRESTAPP_V2_MODULE_ID_CLUSTER', null) || 
            !Configuration::updateValue('PRESTAPP_V2_MODULE_PRESTA_KEY', null) ||
            !Configuration::updateValue('PRESTAPP_V2_MODULE_TOKEN', null) ||
            !Configuration::updateValue('PRESTAPP_V2_MODULE_PS_OLD_OS_WS_PAYMENT', $old_order_state)
            // !Configuration::updateValue('PS_OS_WS_PAYMENT', Configuration::get('PRESTAPP_V2_MODULE_PS_NEW_OS_WS_PAYMENT'))
        ) {
            return false;
        }
        return true;
    }

    // public function addOrderState($name)
    // {
    //     $state_exist = false;
    //     $state_exist_id = null;
    //     $states = OrderState::getOrderStates((int)$this->context->language->id);
 
    //     // check if order state exist
    //     foreach ($states as $state) {
    //         if (in_array($name, $state)) {
    //             $state_exist = true;
    //             if(isset($state['id_order_state'])) {
    //                 $state_exist_id = (int) $state['id_order_state'];
    //             }
    //             break;
    //         }
    //     }

    //     if ($state_exist_id != null) {
    //         Configuration::updateValue('PRESTAPP_V2_MODULE_PS_NEW_OS_WS_PAYMENT', $state_exist_id);
    //     }
 
    //     // If the state does not exist, we create it.
    //     if (!$state_exist) {
    //         // create new order state
    //         $order_state = new OrderState();
    //         $order_state->color = '#00ffff';
    //         $order_state->send_email = false;
    //         $order_state->module_name = '';
    //         $order_state->template = '';
    //         $order_state->name = array();
    //         $languages = Language::getLanguages(false);
    //         foreach ($languages as $language)
    //             $order_state->name[ $language['id_lang'] ] = $name;
 
    //         // Update object
    //         $order_state->add();

    //         Configuration::updateValue('PRESTAPP_V2_MODULE_PS_NEW_OS_WS_PAYMENT', (int) $order_state->id);
    //     }
 
    //     return true;
    // }

    public function uninstall()
    {
        $old_order_state = Configuration::get('PRESTAPP_V2_MODULE_PS_OLD_OS_WS_PAYMENT');
        if (!parent::uninstall() ||
            !Configuration::deleteByName('PRESTAPP_V2_MODULE_SYNC_TYPE') ||
            !Configuration::deleteByName('PRESTAPP_V2_MODULE_LOGIN')||
            !Configuration::deleteByName('PRESTAPP_V2_MODULE_PASSWORD')||
            !Configuration::deleteByName('PRESTAPP_V2_MODULE_API_BACKEND_URL') ||
            !Configuration::deleteByName('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP') ||
            !Configuration::deleteByName('PRESTAPP_V2_MODULE_API_BACKEND_APP_ID') ||
            !Configuration::deleteByName('PRESTAPP_V2_MODULE_NOTIF_CONTENTS') ||
            !Configuration::deleteByName('PRESTAPP_V2_MODULE_NOTIF_HEADINGS') ||
            !Configuration::deleteByName('PRESTAPP_V2_MODULE_ID_CLUSTER') ||
            !Configuration::deleteByName('PRESTAPP_V2_MODULE_PRESTA_KEY') ||
            !Configuration::deleteByName('PRESTAPP_V2_MODULE_TOKEN') ||
            !Configuration::deleteByName('PRESTAPP_V2_MODULE_PS_OLD_OS_WS_PAYMENT') ||
            !Configuration::deleteByName('PRESTAPP_V2_MODULE_PS_NEW_OS_WS_PAYMENT')
            // !Configuration::updateValue('PS_OS_WS_PAYMENT', $old_order_state)
        ) {
            return false;
        }
        return true;
    }


    public function getContent()
    {
        $backendUrl = $this->getBackendUrl();
        $schema_shop = $this->getSchemaShop();
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        if (Tools::isSubmit('sync_full')) {
            // $api_login = strval(Tools::getValue('PRESTAPP_V2_MODULE_LOGIN'));
            // $api_password = strval(Tools::getValue('PRESTAPP_V2_MODULE_PASSWORD'));
            $this->registerHook(self::AVAILABLE_HOOKS);
            $presta_key = strval(Tools::getValue('PRESTAPP_V2_MODULE_PRESTA_KEY'));
            if (
                !$presta_key ||
                empty($presta_key) ||
                !Validate::isGenericName($presta_key)
            ) {
                $this->_html .= $this->displayError($this->l('All fields with (*) are required'));
            } else {
                Configuration::updateValue('PRESTAPP_V2_MODULE_PRESTA_KEY', $presta_key);
                Configuration::updateValue('PRESTAPP_V2_MODULE_API_BACKEND_URL', $backendUrl);
                Configuration::updateValue('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP', $schema_shop);

                $message = PrestappApi::getDataFromAdminPanel('sync_full');
                if ($message->success) {
                    $this->_html .= $this->displayConfirmation($this->l($message->message));
                } else {
                    $this->_html .= $this->displayError($this->l($message->message));
                }
            }
        }

        if (Tools::isSubmit('sync_only_settings')) {
            // $api_login = strval(Tools::getValue('PRESTAPP_V2_MODULE_LOGIN'));
            // $api_password = strval(Tools::getValue('PRESTAPP_V2_MODULE_PASSWORD'));
            $this->registerHook(self::AVAILABLE_HOOKS);
            $presta_key = strval(Tools::getValue('PRESTAPP_V2_MODULE_PRESTA_KEY'));
            if (
                !$presta_key ||
                empty($presta_key) ||
                !Validate::isGenericName($presta_key)
            ) {
                $this->_html .= $this->displayError($this->l('All fields with (*) are required'));
            } else {
                // Configuration::updateValue('PRESTAPP_V2_MODULE_LOGIN', $api_login);
                // Configuration::updateValue('PRESTAPP_V2_MODULE_PASSWORD', $api_password);
                Configuration::updateValue('PRESTAPP_V2_MODULE_PRESTA_KEY', $presta_key);
                Configuration::updateValue('PRESTAPP_V2_MODULE_API_BACKEND_URL', $backendUrl);
                Configuration::updateValue('PRESTAPP_V2_MODULE_API_SCHEMA_SHOP', $schema_shop);

                $message = PrestappApi::getDataFromAdminPanel('sync_only_settings');
                if ($message->success) {
                    $this->_html .= $this->displayConfirmation($this->l($message->message));
                } else {
                    $this->_html .= $this->displayError($this->l($message->message));
                }
            }
        }

        if (Tools::isSubmit('stop_sync')) {
            // $api_login = strval(Tools::getValue('PRESTAPP_V2_MODULE_LOGIN'));
            // $api_password = strval(Tools::getValue('PRESTAPP_V2_MODULE_PASSWORD'));
            $presta_key = strval(Tools::getValue('PRESTAPP_V2_MODULE_PRESTA_KEY'));
            if (
                !$presta_key ||
                empty($presta_key) ||
                !Validate::isGenericName($presta_key)
            ) {
                $this->_html .= $this->displayError($this->l('All fields with (*) are required'));
            } else {

                $message = PrestappApi::stopSync();
                if ($message->success) {
                    $this->_html .= $this->displayConfirmation($this->l($message->message));
                } else {
                    $this->_html .= $this->displayError($this->l($message->message));
                }
            }
        }

        if (Tools::isSubmit('stop_hook')) {
            for ($i = 0; $i < count(self::AVAILABLE_HOOKS); $i++) {
                $this->unregisterHook(self::AVAILABLE_HOOKS[$i]);
            }
        }

        $this->_html .= $this->renderForm();
        return $this->_html;
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }

    /** when customer sign in account */
    public function hookActionAuthentication() {}

    /**
     *    Hook for update status order
     */

    public function hookActionOrderStatusUpdate($params)
    {
        PrestappApi::OrderStatusUpdate($params);
    }

    /**
     * Hooks action for PRODUCT
    */

    public function hookActionProductAdd($params)
    {
        PrestappApi::ProductUpdate($params);
    }

    public function hookActionProductUpdate($params)
    {
        PrestappApi::ProductUpdate($params);
    }

    public function hookActionProductDelete($params)
    {
        PrestappApi::ProductDelete($params);
    }

    public function hookActionProductSave($params) {}

    /**
     * Hooks action for CATEGORIES
     */

    public function hookActionCategoryAdd($params)
    {
        PrestappApi::CategoryUpdate($params);
    }

    public function hookActionCategoryUpdate($params)
    {
        PrestappApi::CategoryUpdate($params);
    }

    public function hookActionCategoryDelete($params)
    {
        PrestappApi::CategoryDelete($params);
    }

    /**
     * Hooks action for ATTRIBUTES
     */

    /** When SAVING an attribute group after creating or updating */
    public function hookActionAttributeGroupSave($params)
    {
        PrestappApi::AttributeGroupUpdate($params);
    }

    /** When SAVING an attribute after creating or updating */
    public function hookActionAttributeSave($params)
    {
        PrestappApi::AttributeUpdate($params);
        PrestappApi::AttributeGroupUpdate($params);
    }

    /** When you DELETE a group of attributes */
    public function hookActionAttributeGroupDelete($params)
    {
        PrestappApi::AttributeGroupDelete($params);
    }

    /** When you DELETE the attribute */
    public function hookActionAttributeDelete($params)
    {
        PrestappApi::AttributeDelete($params);
        PrestappApi::AttributeGroupUpdate($params);
    }

    /** Когда удаляется продукт с атребутом
     * для ActionAttributeCombinationDelete: $params - {"id_product_attribute":41,"cookie":{},"cart":null,"altern":1}
     * для ActionProductAttributeDelete:  $params - {"id_product_attribute":0,"id_product":30,"deleteAllAttributes":true,"cookie":{},"cart":null,"altern":1}*/
    public function hookActionAttributeCombinationDelete($params) {}
    public function hookActionProductAttributeDelete($params) {}

    /** Когда создается продукт с аттребутом $params - {"id_product_attribute":40,"cookie":{},"cart":null,"altern":1}*/
    public function hookActionProductAttributeUpdate($params) {}


    /**
     * Hooks action for MANUFACTURERS
     */

    /** when will you ADD a new brand */
    public function hookActionObjectManufacturerAddAfter($params)
    {
        PrestappApi::ManufacturerUpdate($params);
    }
    /** when you UPDATE an existing brand */
    public function hookActionObjectManufacturerUpdateAfter($params)
    {
        PrestappApi::ManufacturerUpdate($params);
    }

    /** When you ADD a new manufacturer address, the brand table is updated. Adding an address is handled by another hook -> hookActionObjectAddressAddBefore. */
    public function hookActionAfterCreateManufacturerAddressFormHandler($params)
    {
        PrestappApi::ManufacturerUpdate($params);
    }

    /** When you DELETE a manufacturer */
    public function hookActionObjectManufacturerDeleteAfter($params)
    {
        PrestappApi::ManufacturerDelete($params);
    }


    /**
     * Hooks action for ADDRESS (also used for manufacturer addresses)
     */

    public function hookActionObjectAddressAddAfter ($params)
    {
        PrestappApi::AddressUpdate($params);
    }
    public function hookActionObjectAddressUpdateAfter ($params)
    {
        PrestappApi::AddressUpdate($params);
    }
    public function hookActionObjectAddressDeleteAfter ($params)
    {
        PrestappApi::AddressDelete($params);
        if ($params['object']->id_manufacturer !== 0) {
            PrestappApi::ManufacturerUpdate($params);
        }
    }


    /**
     * Hooks action for CUSTOMERS
     */

    /** When you ADD a user from the admin panel or store */
    public function hookActionObjectCustomerAddAfter ($params)
    {
        PrestappApi::CustomerUpdateItem($params['object']->id);
    }

    /** When you ADD a user only from the store */
    public function hookActionCustomerAccountAdd ($params)
    {
        PrestappApi::CustomerUpdateItem($params['newCustomer']->id);
    }

    /** When you UPDATE a user only from the store */
    public function hookActionCustomerAccountUpdate ($params)
    {
        PrestappApi::CustomerUpdateItem($params['customer']->id);
    }

    /** When you UPDATE a user only from the admin area */
    public function hookActionObjectCustomerUpdateAfter ($params)
    {
        PrestappApi::CustomerUpdateItem($params['object']->id);
    }

    /** When you completely DELETE a user from the database */
    public function hookActionObjectCustomerDeleteAfter($params)
    {
        PrestappApi::CustomerDelete($params);
    }


    /**
     * Hooks action for CARRIERS
     */

    public function hookActionObjectCarrierAddAfter ($params)
    {
        PrestappApi::CarrierUpdateItem($params);
    }
    /** When you update o delete carrier*/
    public function hookActionObjectCarrierUpdateAfter ($params)
    {
        PrestappApi::CarrierUpdateTable($params);
    }

    /** Во время каждого шаг в чекауте
     * $params - {"cart":{"id":158,"id_shop_group":"1","id_shop":"1","id_address_delivery":"38","id_address_invoice":"38","id_currency":"1","id_customer":"13","id_guest":"23","id_lan g":"1","recyclable":"0","gift":"0","gift_message":"","mobile_theme":"0","date_add":"2021-03-05 17:03:24","secure_key":"bd2199c9e8019cab0b70cd2c8d926de5","id_carrier":"0","date_upd":"2021-03-05 17:05:50","checkedTos":false,"pictures":null,"textFields":null,"delivery_option":"","allow_seperated_package":"0","id_shop_list":[],"force_id":false},"cookie":{},"altern":1},*/
    public function hookActionCarrierProcess ($params) {}

    /**
     * Hooks action for CART RULES (Discounts)
     */

    public function hookActionObjectCartRuleAddAfter ($params)
    {
        PrestappApi::CartRuleUpdateTable($params);
    }
    public function hookActionObjectCartRuleUpdateAfter ($params)
    {
        PrestappApi::CartRuleUpdateTable($params);
    }
    public function hookActionObjectCartRuleDeleteAfter ($params)
    {
        PrestappApi::CartRuleDelete($params['object']->id);
    }

    /**
     * Hooks action for CART
     */

    public function hookActionObjectCartAddAfter ($params)
    {
        PrestappApi::CartUpdate($params['object']->id);
    }
    public function hookActionObjectCartUpdateAfter ($params)
    {
        PrestappApi::CartUpdate($params['object']->id);
    }
    public function hookActionObjectCartDeleteAfter ($params)
    {
        PrestappApi::CartDelete($params['object']->id);
    }

    /**
     * Hooks action for ORDER
     */

    public function hookActionObjectOrderAddAfter ($params)
    {
        PrestappApi::OrderUpdate($params['object']->id);
    }
    public function hookActionObjectOrderUpdateAfter ($params)
    {
        PrestappApi::OrderUpdate($params['object']->id);
    }

    /**
     * Hooks action for COUNTRIES
     */
    public function hookActionObjectCountryAddAfter ($params)
    {
        PrestappApi::CountryUpdateItem($params['object']->id);
    }
    public function hookActionObjectCountryUpdateAfter ($params)
    {
        PrestappApi::CountryUpdateItem($params['object']->id);
    }
    public function hookActionObjectCountryDeleteAfter ($params)
    {
        PrestappApi::CountryDelete($params['object']->id);
    }


    /**
     * Hooks action for CURRENCIES
     */
    public function hookActionObjectCurrencyAddAfter ($params)
    {
        PrestappApi::CurrencyUpdateTable($params);
    }
    /** Used when updating and deleting */
    public function hookActionObjectCurrencyUpdateAfter ($params)
    {
        PrestappApi::CurrencyUpdateTable($params);
    }


    /**
     * Hooks action for LANGUAGES
     */
    public function hookActionObjectLanguageAddAfter ($params)
    {
        PrestappApi::LanguageUpdateTable($params);
    }
    public function hookActionObjectLanguageUpdateAfter ($params)
    {
        PrestappApi::LanguageUpdateTable($params);
    }
    public function hookActionObjectLanguageDeleteAfter ($params)
    {
        PrestappApi::LanguageDelete($params['object']->id);
    }

    /**
     * Hooks action for SPECIFIC PRICES
     */
    public function hookActionObjectSpecificPriceAddAfter ($params)
    {
        PrestappApi::SpecificPriceUpdateTable($params);
    }
    public function hookActionObjectSpecificPriceUpdateAfter ($params)
    {
        PrestappApi::SpecificPriceUpdateTable($params);
    }
    public function hookActionObjectSpecificPriceDeleteAfter ($params)
    {
        PrestappApi::SpecificPriceDelete($params);
    }

    /**
     * Hooks action for TAX RULES
     */
    public function hookActionObjectTaxRuleAddAfter ($params)
    {
        PrestappApi::TaxRuleUpdateTable($params);
    }
    public function hookActionObjectTaxRuleUpdateAfter ($params)
    {
        PrestappApi::TaxRuleUpdateTable($params);
    }
    public function hookActionObjectTaxRuleDeleteAfter ($params)
    {
        PrestappApi::TaxRuleDelete($params['object']->id);
    }

    /**
     * Hooks action for TAX RULE GROUPS
     */

    public function hookActionObjectTaxRulesGroupAddAfter ($params)
    {
        PrestappApi::TaxRulesGroupUpdateTable($params);
    }
    public function hookActionObjectTaxRulesGroupUpdateAfter ($params)
    {
        PrestappApi::TaxRulesGroupUpdateTable($params);
    }
    public function hookActionObjectTaxRulesGroupDeleteAfter ($params)
    {
        PrestappApi::TaxRulesGroupDelete($params['object']->id);
    }


    /**
     * Hooks action for TAXES
     */
    public function hookActionObjectTaxAddAfter ($params)
    {
        PrestappApi::TaxUpdateTable($params);
    }
    public function hookActionObjectTaxUpdateAfter ($params)
    {
        PrestappApi::TaxUpdateTable($params);
    }
    public function hookActionObjectTaxDeleteAfter ($params)
    {
        PrestappApi::TaxDelete($params['object']->id);
    }

    /**
     * Hooks action for STOCK AVAILABLE
     */
    public function hookActionObjectStockAvailableUpdateAfter ($params)
    {
        PrestappApi::StockAvailableUpdateItem($params['object']->id);
    }

    /**
     * Hooks action for COMBINATIONS
     */
    public function hookActionObjectCombinationAddAfter ($params)
    {
        PrestappApi::CombinationUpdateItem($params['object']->id);
    }
    public function hookActionObjectCombinationUpdateAfter ($params)
    {
        PrestappApi::CombinationUpdateItem($params['object']->id);
    }
    public function hookActionObjectCombinationDeleteAfter ($params)
    {
        PrestappApi::CombinationDelete($params);
    }

    /**
     * Hooks action for CMS
     */
    public function hookActionObjectCMSAddAfter ($params)
    {
        PrestappApi::CMSUpdate($params['object']->id);
    }
    public function hookActionObjectCMSUpdateAfter ($params)
    {
        PrestappApi::CMSUpdate($params['object']->id);
    }
    public function hookActionObjectCMSDeleteAfter ($params)
    {
        PrestappApi::CMSDelete($params['object']->id);
    }

    /**
     * Hooks action for FEATURES
     */
    public function hookActionFeatureDelete ($params)
    {
        PrestappApi::FeaturesDelete($params['id_feature']);
    }
    public function hookActionFeatureSave ($params)
    {
        PrestappApi::FeaturesUpdate($params['id_feature']);
    }

    public function hookActionFeatureValueDelete ($params)
    {
        PrestappApi::FeaturesValueDelete($params['id_feature_value']);
    }
    public function hookActionFeatureValueSave ($params)
    {
        PrestappApi::FeaturesValueUpdate($params['id_feature_value']);
    }

    /**
     * Hook for API
     */
    public function hookModuleRoutes()
    {
        return array(
            'module-prestapp_v2_module-api' => array(
                'controller' => 'api',
                'keywords' => [],
                'rule' =>  'prestapp/api',
                'params' => array(
                    'fc' => 'module',
                    'module' => 'prestapp_v2_module',
                )
            )
        );
    } 


    public function renderForm()
    {
        // Get default language
        $defaultLang = (int)Configuration::get('PS_LANG_DEFAULT');
        // Init Fields form array
        $fieldsForm = array();
        $fieldsForm[0]['form'] = [
            'legend' => [
                'title' => $this->l('LogIn'),
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('Your key prestapp'),
                    'name' => 'PRESTAPP_V2_MODULE_PRESTA_KEY',
                    'size' => 100,
                    'required' => true
                ],
                // [
                //     'type' => 'password',
                //     'label' => $this->l('Your password'),
                //     'name' => 'PRESTAPP_V2_MODULE_PASSWORD',
                //     'size' => 50,
                //     'required' => true
                // ]
            ],
            'buttons' => [
                [
                    'type' => 'submit',
                    'title' => $this->l('Sync only settings'),
                    'class' => 'btn btn-default pull-left',
                    'value' => 'test',
                    'name' => 'sync_only_settings'
                ],
                [
                    'type' => 'submit',
                    'title' => $this->l('Stop sync'),
                    'class' => 'btn btn-default pull-left',
                    'value' => 'test',
                    'name' => 'stop_sync'
                ],
                [
                    'type' => 'submit',
                    'title' => $this->l('Stop Hook'),
                    'class' => 'btn btn-default pull-left',
                    'value' => 'test',
                    'name' => 'stop_hook'
                ]
            ]
        ];
        // $api_login = strval(Tools::getValue('PRESTAPP_V2_MODULE_LOGIN'));
        // $api_password = strval(Tools::getValue('PRESTAPP_V2_MODULE_PASSWORD'));
        $presta_key = strval(Tools::getValue('PRESTAPP_V2_MODULE_PRESTA_KEY'));

        if (
            !$presta_key || 
            empty($presta_key) ||
            !Validate::isGenericName($presta_key)
        ) {
            $fieldsForm[0]['form']['submit'] = [
                'title' => $this->l('Synchronize'),
                'class' => 'btn btn-default pull-right',
                'name' => 'sync_full'
            ];
        } else {
            $fieldsForm[0]['form']['submit'] = [
                'title' => $this->l('Update'),
                'class' => 'btn btn-default pull-right',
                'name' => 'sync_full'
            ];
        }

        $helper = new HelperForm();

        // Language
        $helper->default_form_language = $defaultLang;
        $helper->allow_employee_form_lang = $defaultLang;

        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;        // false -> remove toolbar
        $helper->toolbar_scroll = true;      // yes -> Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'btnSubmit';
        $helper->toolbar_btn = [
            'submit' => [
                'desc' => $this->l('Submit'),
                'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules'),
            ]
        ];

        // Load current value
        $helper->fields_value['PRESTAPP_V2_MODULE_PRESTA_KEY'] = Tools::getValue('PRESTAPP_V2_MODULE_PRESTA_KEY', Configuration::get('PRESTAPP_V2_MODULE_PRESTA_KEY'));

        return $helper->generateForm($fieldsForm);
    }
}
