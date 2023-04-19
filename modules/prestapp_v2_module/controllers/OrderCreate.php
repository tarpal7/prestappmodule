<?php

require_once _PS_ROOT_DIR_ . '/modules/prestapp_v2_module/controllers/MyPaymentModule.php';
require_once _PS_ROOT_DIR_ . '/modules/prestapp_v2_module/controllers/DataOrder.php';
require_once _PS_ROOT_DIR_ . '/modules/prestapp_v2_module/lib/PrestappDB.php';

use Stripe_officialClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;
use PrestaShop\Module\PrestashopCheckout\Builder\Payload\OrderPayloadBuilder;
use PrestaShop\Module\PrestashopCheckout\Exception\PsCheckoutException;
use PrestaShop\Module\PrestashopCheckout\Presenter\Cart\CartPresenter;
use PrestaShop\PrestaShop\Core\ConfigurationInterface;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;
use PayPal\Api\Payment;
use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\PayerInfo;
use PayPal\Api\RedirectUrls;
use PayPal\Api\ShippingAddress;
use PayPal\Api\Transaction;
use PaypalAddons\classes\AbstractMethodPaypal;
use PaypalAddons\classes\API\Request\RequestInteface;
use PaypalAddons\services\FormatterPaypal;

if (!defined('_PS_VERSION_')) {
    exit;
}

class OrderCreate
{
    public static function init($data)
    {
        $idCustomer = (int)$data->id_customer;
        $idAddressDelivery = $data->id_address_delivery;
        $idAddressInvoice = $data->id_address_invoice;
        $idCart = (int)$data->id_cart;
        $idCurrency = $data->id_currency;
        $idLang = $data->id_lang;
        $idCutomer = $data->id_customer;
        $idCarrier = $data->id_carrier;
        $module = $data->module;
        $payment = $data->payment;
        $totalPaid = $data->total_paid;
        $totalRealPaid = $data->total_paid_real;
        $totalProducts = $data->total_products;
        $totalProductsWt = $data->total_products_wt;
        $conversionRate = $data->conversion_rate;
        $stripeToken = $data->stripe_token;
		$currentState = $data->current_state;

        $order = new Order();
        $order->id_customer = (int)$idCutomer;
        $order->id_address_delivery = (int)$idAddressDelivery;
        $order->id_address_invoice = (int)$idAddressInvoice;
        $order->id_currency = (int)$idCurrency;
        $order->id_lang = (int)$idLang;
        $order->id_cart = (int)$idCart;
        $order->id_carrier = (int)$idCarrier;
        $order->module = $module;
        $order->payment = $payment;
        $order->total_paid = (float)$totalPaid;
        $order->total_paid_real = (float)$totalRealPaid;
        $order->total_products_wt = (float)$totalProductsWt;
        $order->conversion_rate = (float)$conversionRate;

		$dataOrder = new DataOrder();
		// $dataOrder->id_payment = $idPayment;
		$dataOrder->id_cart = $data->id_cart;
		$dataOrder->id_customer = $data->id_customer;
		$dataOrder->id_address_delivery = $data->id_address_delivery;
		$dataOrder->id_address_invoice = $data->id_address_invoice;
		$dataOrder->id_currency = $data->id_currency;
		$dataOrder->id_lang = $data->id_lang;
		$dataOrder->id_carrier = $data->id_carrier;
		$dataOrder->module = $data->module;
		$dataOrder->payment = $data->payment;
		$dataOrder->total_paid = $data->total_paid;
		$dataOrder->total_real_paid = $data->total_paid_real;
		$dataOrder->total_products = $data->total_products;
		$dataOrder->total_products_wt = $data->total_products_wt;
		$dataOrder->conversion_rate = $data->conversion_rate;
		$dataOrder->stripe_token = $data->stripe_token;
		$dataOrder->id_transaction = null;
		$dataOrder->reference = null;
		$dataOrder->order_created = null;
		$dataOrder->current_state = $currentState;
		$dataOrder->description = null;
		$dataOrder->date_add = null;
		$dataOrder->date_upd = null;

		$currentStateInt = $currentState != '' ? (int)$currentState : 2; //Configuration::get('PS_OS_WS_PAYMENT')

		$cart = new Cart($idCart);
        $customer = new CustomerCore($idCustomer);

        if ($module === 'stripe_official') {

            $stripeModule = Module::getInstanceByName('stripe_official');
            $stripeSecretKey = $stripeModule->getSecretKey(1);
            \Stripe\Stripe::setApiKey($stripeSecretKey);

            try {
                $order->reference = Order::generateReference();
				$dataOrder->reference = $order->reference;
                $charge = \Stripe\Charge::create([
                    'amount' => $totalPaid * 100,
                    'currency' => 'eur',
                    'description' => 'Payment for order ' . $order->reference,
                    'source' => $stripeToken,
                ]);

				$dataOrder->id_payment = $charge->id;
				
				PrestappDB::addDataOrder($dataOrder);

                if ($charge->status === 'succeeded') {
                    try {
                        $extra_vars = array(
                            'reference' => $order->reference,
                            'module' => $order->module,
                            'transaction_id' => $charge->id
                        );
                        $myPaymentModuleInstance = new MyPaymentModule();
                        $myPaymentModuleInstance->validateOrder($idCart, $currentStateInt, $cart->getOrderTotal(), $payment, null, $extra_vars, null, false, $customer->secure_key);

                        $order = new Order(Order::getIdByCartId($order->id_cart));

						PrestappDB::updateOrderCreatedByReference($order->reference, "1");

						$orderArray = $order->getFields();
                        $json = json_encode($orderArray);
                        header('Content-Type: application/json');
                        echo $json;
                        return true;

                    } catch (Exception $e) {

						// refund
						$refund = \Stripe\Refund::create([
							'charge' => $charge->id,
						]);

						PrestappDB::updateOrderCreatedByReference($order->reference, $refund->id);

                        ProcessLoggerHandler::logError(
                            preg_replace("/\n/", '<br>', (string)$e->getMessage() . '<br>' . $e->getTraceAsString()),
                            null,
                            null,
                            'ValidationOrderActions - createOrder'
                        );
                        ProcessLoggerHandler::closeLogger();
                        $error = array(
                            'message' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        );
                        header('Content-Type: application/json');
                        echo json_encode($error);
                        return false;
                    }
                }

            } catch (\Stripe\Exception\InvalidRequestException $e) {

                $error = array(
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                );
                header('Content-Type: application/json');
                echo json_encode($error);
                return false;
            }

        } else if ($module === 'paypal') {

            $clientID = Configuration::get('PAYPAL_EC_CLIENTID_SANDBOX');
            $secret = Configuration::get('PAYPAL_EC_SECRET_SANDBOX');

            $apiContext = new ApiContext(
                new OAuthTokenCredential(
                    $clientID,
                    $secret
                )
            );

            $paypalSandbox = Configuration::get('PAYPAL_SANDBOX');
            $mode = $paypalSandbox == 1 ? 'sandbox' : 'live';

            $apiContext->setConfig(
                array(
                    'mode' => $mode
                )
            );

            $payment = new Payment();

            $redirectUrls = new RedirectUrls();
            $link = new Link();
            $redirectUrls->setReturnUrl($link->getModuleLink('prestapp_v2_module', 'paypal-success'));
            $redirectUrls->setCancelUrl($link->getModuleLink('prestapp_v2_module', 'paypal-cancel'));

            $transaction = new Transaction();
            $transaction->setAmount(new Amount(array('total' => $order->total_paid, 'currency' => 'EUR')));
            // ->setDescription('description')

            $payer = new Payer();
            $payer->setPaymentMethod('paypal');

            $payment->setIntent('sale')
                ->setPayer($payer)
                ->setTransactions(array($transaction))
                ->setRedirectUrls($redirectUrls);

            try {

                $payment->create($apiContext);
                $token = $payment->getId();

				$dataOrder->id_payment = $token;
				PrestappDB::addDataOrder($dataOrder);
                //apcu_store($token, serialize($order), 3600);

                $responseData = [
                    'token' => $token,
                    'paymentData' => [
                        'intent' => $payment->getIntent(),
                        'payer' => [
                            'payment_method' => 'paypal'
                        ],
                        'transactions' => [
                            [
                                'amount' => [
                                    'total' => $transaction->getAmount()->getTotal(),
                                    'currency' => $transaction->getAmount()->getCurrency()
                                ],
                                'description' => $transaction->getDescription()
                            ]
                        ],
                        'redirect_urls' => [
                            'return_url' => $redirectUrls->getReturnUrl(),
                            'cancel_url' => $redirectUrls->getCancelUrl(),
                            'url' => $payment->getApprovalLink()
                        ]
                    ]
                ];

                header('Content-Type: application/json');
                echo json_encode($responseData);

            } catch (PayPal\Exception\PayPalConnectionException $ex) {
                die($ex->getMessage());
            }

        } else {
            try {
                // $paymentModule = Module::getInstanceByName($module);
                // $paymentModule->validateOrder($idCart, Configuration::get('PS_OS_WS_PAYMENT'), $cart->getOrderTotal(), $module, null, [], null, false, $customer->secure_key);

                $extra_vars = array(
                    'module' => $order->module,
                );

				$cashOnDeliveryId = 'CONID_'.uniqid();
				$dataOrder->id_payment = $cashOnDeliveryId;

				PrestappDB::addDataOrder($dataOrder);
                $myPaymentModuleInstance = new MyPaymentModule();
                $myPaymentModuleInstance->validateOrder($idCart, $currentStateInt, $cart->getOrderTotal(), $payment, null, $extra_vars, null, false, $customer->secure_key);

                $order = new Order(Order::getIdByCartId($order->id_cart));

				PrestappDB::updateOrderCreatedByIdPayment($cashOnDeliveryId, $order->reference, "1");

                $orderArray = $order->getFields();
                $json = json_encode($orderArray);
                header('Content-Type: application/json');
                echo $json;
            } catch (Throwable $e) {
                ProcessLoggerHandler::logError(
                    preg_replace("/\n/", '<br>', (string)$e->getMessage() . '<br>' . $e->getTraceAsString()),
                    null,
                    null,
                    'ValidationOrderActions - createOrder'
                );
                ProcessLoggerHandler::closeLogger();

                $error = array(
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                );
                header('Content-Type: application/json');
                echo json_encode($error);
                return false;

            }
        }

    }
}