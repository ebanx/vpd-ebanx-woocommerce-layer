<?php

if (!defined('ABSPATH')) {
	exit;
}

class WC_VPD_XML_Interest_Calculator {
	/**
	 * Stores xml data during the request
	 * @var SimpleXMLElement
	 */
	private static $product_data = null;

	/**
	 * Calculates the order total with interest for WC Orders
	 *
	 * @param  WC_Order $order       Order to calculate
	 * @param  int      $instalments The number of instalments
	 * @return array
	 */
	public static function calculate_total_for_order(WC_Order $order, $instalments) {
		return self::calculate_total(array_map(function($item){
			return array(
				'product_id' => $item['item_meta']['_product_id'][0],
				'quantity' => $item['item_meta']['_qty'][0]
			);
		}, $order->get_items()), $instalments);
	}

	/**
	 * Calculates the order total with interest
	 *
	 * @param  array $cart_items Cart items
	 * @param  int   $instalments The number of instalments
	 * @return array
	 */
	public static function calculate_total($cart_items, $instalments) {
		$order_total = 0.0;
		$has_interest = 0.0;

		foreach ($cart_items as $cart_item) {
			list($item_price, $item_interest) = self::calculate($cart_item, $instalments);
			$order_total += $item_price;
			$has_interest += $item_interest;
		}

		return array($order_total, !!$has_interest);
	}

	/**
	 * Calculates the item price with interest
	 *
	 * @param  array            $cart_item    Cart item data
	 * @param  int              $instalments  Number of instalments
	 * @param  SimpleXMLElement $product_data The product data from xml
	 * @return array The calculated price and interest rate
	 */
	public static function calculate($cart_item, $instalments = 1) {
		$product = new WC_Product($cart_item['product_id']);
		$quantity = $cart_item['quantity'];

		$info = self::get_product_rates($product->get_sku(), self::get_product_data());

		$interest_rate = 0.0;

		if ($info && $instalments > 0) {
			$interest_rate = $instalments > 4
				? $info->dezx // 5x and up
				: ( $instalments > 1
					? $info->quatrox // 2x to 4x
					: $info->avista ); // 1x
		}

		$base = $product->get_display_price();
		return array(( $base * ( 1.0 + $interest_rate ) ) * $quantity, $interest_rate);
	}

	/**
	 * Returns the xml load path
	 *
	 * @return string
	 */
	public static function get_xml_path() {
		return ABSPATH.'xml/sku_pagamentos.xml';
	}

	/**
	 * Search for the product rates by sku
	 *
	 * @param  string $sku The product sku
	 * @return SimpleXMLElement
	 */
	private static function get_product_rates($sku, $products) {
		if (!$products) {
			return null;
		}

		foreach ($products as $product) {
			if ($product->sku != $sku) {
				continue;
			}

			return $product;
		}

		return null;
	}

	/**
	 * Reads the xml file
	 *
	 * @return SimpleXMLElement
	 */
	private static function get_product_data() {
		if (self::$product_data == null) {
			$xml = simplexml_load_file(self::get_xml_path());
			if (!$xml) {
				return null;
			}
			self::$product_data = $xml->elemento;
		}

		return self::$product_data;
	}
}
