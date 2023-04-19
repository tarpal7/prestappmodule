<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__DIR__).'/DiscountUpsert.php';
require_once dirname(__DIR__).'/OrderUpsert.php';
require_once dirname(__DIR__).'/CarrierGet.php';
require_once dirname(__DIR__).'/ElementorProductGet.php';
require_once dirname(__DIR__).'/ElementorProductLangGet.php';
require_once dirname(__DIR__).'/OrderInvoicePaymentUpsert.php';
require_once dirname(__DIR__).'/CustomerStoreUpsert.php';
require_once dirname(__DIR__).'/CartCartRuleGet.php';
require_once dirname(__DIR__).'/CartCartRuleDelete.php';

require_once dirname(__DIR__).'/CartRuleCarrierGet.php';
require_once dirname(__DIR__).'/CartRuleCombinationGet.php';
require_once dirname(__DIR__).'/CartRuleCountryGet.php';
require_once dirname(__DIR__).'/CartRuleGroupGet.php';
require_once dirname(__DIR__).'/CartRuleProductRuleGet.php';
require_once dirname(__DIR__).'/CartRuleProductRuleGroupGet.php';
require_once dirname(__DIR__).'/CartRuleProductRuleValueGet.php';
require_once dirname(__DIR__).'/OrderCreate.php';
require_once _PS_ROOT_DIR_ . '/modules/prestapp_v2_module/lib/PrestappDB.php';

class Prestapp_V2_ModuleApiModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        $input = json_decode(@Tools::file_get_contents("php://input"));

        switch ($_SERVER['REQUEST_METHOD']) {
            case 'GET':
                if (isset($_GET['idShopName']) &&
                    isset($_GET['action'])
                ) {
                    $idShopName = Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME');
                    $action = $_GET['action'];
                    
                    if ($_GET['idShopName'] === $idShopName) {
                        switch($action) {
                            case "carrier.get":
                                CarrierGet::init();
                                break;
                            case "elementor_product.get":
                                if (isset($_GET['limit']) &&
                                    isset($_GET['offset'])
                                )
                                    {
                                        ElementorProductGet::init((int)$_GET['limit'], (int)$_GET['offset']);
                                    } else {
                                        echo "No limit, offset";
                                    }
                                break;
                            case "elementor_product_lang.get":
                                if (isset($_GET['limit']) &&
                                    isset($_GET['offset'])
                                )
                                    {
                                        ElementorProductLangGet::init((int)$_GET['limit'], (int)$_GET['offset']);
                                    } else {
                                        echo "No limit, offset";
                                    }
                                break;
                            case "table_sync.get":
                                if (isset($_GET['limit']) &&
                                    isset($_GET['offset'])
                                )
                                    {
                                        $result = PrestappDB::getAllItems((int)$_GET['limit'], (int)$_GET['offset']);
                                        echo json_encode($result);
                                    } else {
                                        echo "No limit, offset";
                                    }
                                break;
                            case "cart_cart_rule.get":
                                if (isset($_GET['idCartRule']) &&
                                    isset($_GET['idCart']) &&
                                    isset($_GET['find'])
                                )
                                    {
                                        CartCartRuleGet::init((int)$_GET['idCartRule'], (int)$_GET['idCart'], $_GET['find']);
                                    } else {
                                        echo "No id_cart_rule";
                                    }
                                break;
                            case "cart_rule_carrier.get":
                                if (isset($_GET['limit']) &&
                                    isset($_GET['offset'])
                                )
                                    {
                                        CartRuleCarrierGet::init((int)$_GET['limit'], (int)$_GET['offset']);
                                    } else {
                                        echo "No limit, offset";
                                    }
                                break;
                            case "cart_rule_combination.get":
                                if (isset($_GET['limit']) &&
                                    isset($_GET['offset'])
                                )
                                    {
                                        CartRuleCombinationGet::init((int)$_GET['limit'], (int)$_GET['offset']);
                                    } else {
                                        echo "No limit, offset";
                                    }
                                break;
                            case "cart_rule_country.get":
                                if (isset($_GET['limit']) &&
                                    isset($_GET['offset'])
                                )
                                    {
                                        CartRuleCountryGet::init((int)$_GET['limit'], (int)$_GET['offset']);
                                    } else {
                                        echo "No limit, offset";
                                    }
                                break;
                            case "cart_rule_group.get":
                                if (isset($_GET['limit']) &&
                                    isset($_GET['offset'])
                                )
                                    {
                                        CartRuleGroupGet::init((int)$_GET['limit'], (int)$_GET['offset']);
                                    } else {
                                        echo "No limit, offset";
                                    }
                                break;
                            case "cart_rule_product_rule.get":
                                if (isset($_GET['limit']) &&
                                    isset($_GET['offset'])
                                )
                                    {
                                        CartRuleProductRuleGet::init((int)$_GET['limit'], (int)$_GET['offset']);
                                    } else {
                                        echo "No limit, offset";
                                    }
                                break;
                            case "cart_rule_product_group.get":
                                if (isset($_GET['limit']) &&
                                    isset($_GET['offset'])
                                )
                                    {
                                        CartRuleProductRuleGroupGet::init((int)$_GET['limit'], (int)$_GET['offset']);
                                    } else {
                                        echo "No limit, offset";
                                    }
                                break;
                            case "cart_rule_product_value.get":
                                if (isset($_GET['limit']) &&
                                    isset($_GET['offset'])
                                )
                                    {
                                        CartRuleProductRuleValueGet::init((int)$_GET['limit'], (int)$_GET['offset']);
                                    } else {
                                        echo "No limit, offset";
                                    }
                                break;
                            default:
                                echo "Not exist action";
                                break;
                        }
                    } else {
                        http_response_code(402);
                        echo "Unauthorized";
                    }
                } else {
                    http_response_code(500);
                    echo "Data error";
                }
                break;
            case 'POST':
                if (isset($input->idShopName) &&
                    isset($input->action) &&
                    isset($input->data))
                {
                    $idShopName = Configuration::get('PRESTAPP_V2_MODULE_API_ID_SHOPNAME');
                    $action = $input->action;
                    $data = $input->data;

                    if ($idShopName === $input->idShopName) {

                        http_response_code(200);
                        switch($action) {
                            case "discount.upsert":
                                DiscountUpsert::init($data);
                                break;
                            case "store_order.upsert":
                                OrderUpsert::init($data);
                                break;
                            case "table_sync.delete":
                                $sql = "";
                                for ($i = 0; $i < count($data); $i++) {
                                    $resultsql = PrestappDB::deleteItem($data[$i]);
                                    $sql = $sql.$resultsql;
                                }
                                echo $sql;
                                break;
                            case "order_invoice_payment.upsert":
                                OrderInvoicePaymentUpsert::init($data);
                                break;
                            case "customer_store.upsert":
                                CustomerStoreUpsert::init($data);
                                break;
                            case "cart_cart_rule.delete":
                                CartCartRuleDelete::init($data);
                                break;
                            case "store_order.create":
                                OrderCreate::init($data);
                                break;      
                            default:
                                echo "Not exist action";
                                break;
                        }
                        
                    } else {
                        http_response_code(402);
                        echo "Unauthorized";
                    }
                } else {
                    http_response_code(500);
                    echo "Data error";
                }
                break;
            default:
                echo "REQUEST METHOD NOT RECOGNIZED";
                break;
        }
        exit();
    }
}