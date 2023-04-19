<?php

require_once dirname(__DIR__) . '/PaypalSuccess.php';

class Prestapp_v2_modulePaypalsuccessModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        $result = PaypalSuccess::init($_GET['paymentId'], $_GET['PayerID'], $_GET['token']);
        if ($result) {
            $orderArray = $result->getFields();
			// $json = json_encode($orderArray);
            // header('Content-Type: application/json');
            // echo $json;
            $orderReference = $result->reference;
            // header('Content-Type: application/json');
            // echo json_encode(['reference' => $orderReference]);
            $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>success</title></head><body><h1>' . $orderReference . '</h1></body></html>';
            echo $html;
        } else {
            // echo json_encode(['error' => 'order not created']);
            $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>cancel</title></head><body>order not create, payment canceled</body></html>';
            echo $html;
        }
        exit();
    }
}
