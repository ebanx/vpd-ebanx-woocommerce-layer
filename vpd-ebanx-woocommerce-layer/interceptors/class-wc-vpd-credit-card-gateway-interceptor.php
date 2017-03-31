<?php

if (!defined('ABSPATH')) {
	exit;
}

class WC_VPD_Credit_Card_Gateway_Interceptor {
	/**
	 * Identify interceptor instances
	 * @var string
	 */
	public $id = "credit-card-gateway-interceptor";

	/**
	 * Payment methods we want to intercept are listed here
	 *
	 * @var array
	 */
	private static $payment_methods = array(
		'ebanx-credit-card-mx',
		'ebanx-credit-card-br'
	);

	/**
	 * Binds hooks, starts vars
	 */
	public function __construct(){
		add_action('ebanx_before_process_payment', array($this, 'before_process_payment'));
	}

	/**
	 * Code to intercept "process payment" hook in credit card payment methods
	 *
	 * @param WC_Order $order The order to work with
	 */
	public function before_process_payment($order) {
		if ( ! in_array($order->payment_method, self::$payment_methods) )
			return;

		$instalments = 1;

		if ( isset( $_POST['ebanx_billing_instalments'] ) ) {
			$instalments = $_POST['ebanx_billing_instalments'];
		}

		$order->set_total(WC_VPD_XML_Interest_Calculator::calculate_total($order, $instalments));
	}
}
