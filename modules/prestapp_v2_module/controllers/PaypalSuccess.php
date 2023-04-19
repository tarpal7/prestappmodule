<?php

require_once _PS_MODULE_DIR_ . 'paypal/vendor/autoload.php';
require_once _PS_ROOT_DIR_ . '/modules/prestapp_v2_module/controllers/MyPaymentModule.php';
require_once _PS_ROOT_DIR_ . '/classes/PaymentModule.php';
require_once _PS_ROOT_DIR_ . '/modules/prestapp_v2_module/controllers/DataOrder.php';
require_once _PS_ROOT_DIR_ . '/modules/prestapp_v2_module/lib/PrestappDB.php';

use Stripe_officialClasslib\Extensions\ProcessLogger\ProcessLoggerHandler;
use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;
use PayPal\Rest\ApiContext;
use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\PayerInfo;
use PayPal\Api\RedirectUrls;
use PayPal\Api\ShippingAddress;
use PayPal\Api\Transaction;
use PayPal\Api\Refund;
use PayPal\Api\RefundRequest;
use PayPal\Api\Sale;


if (!defined('_PS_VERSION_')) {
    exit;
}

class PaypalSuccess
{
    public static function init($paymentId, $payerId, $token)
    {

        $clientID = Configuration::get('PAYPAL_EC_CLIENTID_SANDBOX');
        $secret = Configuration::get('PAYPAL_EC_SECRET_SANDBOX');
        $credential = new PayPal\Auth\OAuthTokenCredential($clientID, $secret);

        $paypalSandbox = Configuration::get('PAYPAL_SANDBOX');
        $mode = $paypalSandbox == 1 ? 'sandbox' : 'live';

        $apiContext = new PayPal\Rest\ApiContext($credential);
        $apiContext->setConfig(
            array(
                'mode' => $mode
            )
        );

        $payment = Payment::get($paymentId, $apiContext);
        $execution = new PaymentExecution();
        $execution->setPayerId($payerId);
        $result = $payment->execute($execution, $apiContext);
		$transaction = $result->getTransactions();
		$payer = $result->getPayer();
		echo($transaction[0]);
		echo ($payer);
		var_dump($result);


        if ($result->getState() == 'approved') {

            // $orderInCache = apcu_fetch($paymentId);
			$orderData = PrestappDB::getOrderByIdPayment($paymentId);
 			$currentState = $orderData -> current_state;
			$currentStateInt = $currentState != '' ? (int) $currentState : 2;

			if ($orderData['id_payment'] !== '') {

                //$orderData = unserialize($orderInCache);
				$idCart = (int)$orderData['id_cart'];
                $customer = new Customer((int)$orderData['id_customer']);
                $cart = new Cart($idCart);
                $currency = new Currency((int)$orderData['id_currency']);
                $totalPaid = $orderData['total_paid'];
                $payment = $orderData['payment'];
                $module = $orderData['module'];

                $extra_vars = array(
                    'module' => $orderData['module'],
                    'transaction_id' => $paymentId
                );


				try {

                    $myPaymentModuleInstance = new MyPaymentModule($module);
                    $myPaymentModuleInstance->validateOrder($idCart, $currentStateInt, $cart->getOrderTotal(), $payment, null, $extra_vars, null, false, $customer->secure_key);

                    // $paymentModule = Module::getInstanceByName('paypal');
                    // $paymentModule->validateOrder($id_cart, Configuration::get('PS_OS_WS_PAYMENT'), $cart->getOrderTotal(), $paymentl,  null, [], null, false, $customer->secure_key);

                    $order = new Order(Order::getIdByCartId($idCart));
					PrestappDB::updateOrderCreatedByIdPayment($paymentId, $order->reference, "1");
                    // apcu_delete($paymentId);

                    return $order;

                } catch (Throwable $e) {
					try {

						// Get data about payment
						$payment = Payment::get($paymentId, $apiContext);

						// get data about transaction
						$sale = new Sale();
						$sale->setId($payment->getTransactions()[0]->getRelatedResources()[0]->getSale()->getId());
						$sale = Sale::get($sale->getId(), $apiContext);

						// create object for refund
						$refund = new RefundRequest();
						$refund->setAmount(new Amount(['currency' => $sale->getAmount()->getCurrency(), 'total' => $sale->getAmount()->getTotal()]));

						// make refund
						$refundedSale = $sale->refundSale($refund, $apiContext);

						// get ID of refund
						$refundId = $refundedSale->getId();

						// update dataOrder by id of refund in field order_created
						PrestappDB::updateOrderCreatedByIdPayment($paymentId, $order->reference, $refundId);

					} catch (PayPalConnectionException $ex) {
						echo $ex->getMessage();
					}

					ProcessLoggerHandler::logError(
                        preg_replace("/\n/", '<br>', (string)$e->getMessage() . '<br>' . $e->getTraceAsString()),
                        null,
                        null,
                        'ValidationOrderActions - createOrder'
                    );
                    ProcessLoggerHandler::closeLogger();
                    echo($e->getTraceAsString());
                    return false;
                }
            }
        } else {
            return false;
        }
    }

}
