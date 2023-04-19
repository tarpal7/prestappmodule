<?php
if (!defined('_PS_VERSION_')) {
	exit;
}

class DataOrder
{
	public $id;
	public $id_payment;
	public $id_customer;
	public $id_address_delivery;
	public $id_address_invoice;
	public $id_cart;
	public $id_currency;
	public $id_lang;
	public $id_carrier;
	public $module;
	public $payment;
	public $total_paid;
	public $total_real_paid;
	public $total_products;
	public $total_products_wt;
	public $conversion_rate;
	public $stripe_token;
	public $id_transaction;
	public $reference;
	public $order_created;
	public $current_state;
	public $description;
	public $date_add;
	public $date_upd;

	public function __construct()
	{
		$this->id = null;
		$this->id_payment = null;
		$this->id_customer = null;
		$this->id_address_delivery = null;
		$this->id_address_invoice = null;
		$this->id_cart = null;
		$this->id_currency = null;
		$this->id_lang = null;
		$this->id_carrier = null;
		$this->module = null;
		$this->payment = null;
		$this->total_paid = null;
		$this->total_real_paid = null;
		$this->total_products = null;
		$this->total_products_wt = null;
		$this->conversion_rate = null;
		$this->stripe_token = null;
		$this->id_transaction = null;
		$this->reference = null;
		$this->current_state = null;
		$this->order_created = null;
		$this->description = null;
		$this->date_add = null;
		$this->date_upd = null;
	}

}