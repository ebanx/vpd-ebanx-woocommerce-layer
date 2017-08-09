<?php

if (!defined('ABSPATH')) {
	exit;
}

class WC_VPD_Gateway_Interceptor {
	/**
	 * Identify interceptor instances
	 * @var string
	 */
	public $id = "gateway";

	/**
	 * Payment methods that have intalments are listed here
	 *
	 * @var array
	 */
	private static $instalment_payment_methods = array(
		'ebanx-credit-card-mx',
		'ebanx-credit-card-br'
	);

	/**
	 * Binds hooks, starts vars
	 */
	public function __construct(){
		add_filter('ebanx_get_payment_terms', array($this, 'get_payment_terms'));
		add_filter('ebanx_get_custom_total_amount', array($this, 'get_custom_amount'), 10, 2);
		add_action('ebanx_before_process_payment', array($this, 'before_process_payment'));
	}

	public function get_custom_amount($amount, $instalments) {
		$cart_items = WC()->cart->get_cart();
		list($total, $has_interest) = WC_VPD_XML_Interest_Calculator::calculate_total($cart_items, $instalments);
//		var_dump($total);exit;
		return $total;
	}

	/**
	 * Code to intercept instalments presented to the user
	 *
	 * @param array $payment_terms
	 */
	public function get_payment_terms($payment_terms) {
		$cart_items = WC()->cart->get_cart();

		return array_map(function($term) use ($cart_items) {
			if (count($cart_items) === 0) {
				$cart_items = array($term);
			}

			$instalments = $term['number'];
			list($total, $has_interest) = WC_VPD_XML_Interest_Calculator::calculate_total($cart_items, $instalments);

			$result = array(
				'price' => $total / $instalments,
				'has_interest' => $has_interest,
				'number' => $instalments
			);

			if (count($cart_items) === 0) {
				$result['product_id'] = $term['product_id'];
				$result['quantity'] = $term['quantity'];
			}

			return $result;

		}, $payment_terms);
	}

	/**
	 * Code to intercept "process payment" hook in payment methods
	 *
	 * @param WC_Order $order The order to work with
	 */
	public function before_process_payment($order) {
		if ( !in_array($order->payment_method, self::$instalment_payment_methods) ) {
			return;
		}

		$instalments = 1;

		if ( isset( $_POST['ebanx_billing_instalments'] ) ) {
			$instalments = $_POST['ebanx_billing_instalments'];
		}

		list($total, $has_interest) = WC_VPD_XML_Interest_Calculator::calculate_total_for_order($order, $instalments);
		$order->set_total($total);
	}
}
