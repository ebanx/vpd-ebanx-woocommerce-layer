<?php

if (!defined('ABSPATH')) {
	exit;
}

class WC_VPD_XML_Interest_Calculator {
	/**
	 * Calculates the order total with interest
	 *
	 * @param  WC_Order $order       The order object
	 * @param  int      $instalments The installments number
	 * @return float
	 */
	public static function calculate_total(WC_Order $order, $instalments) {
		$order_total = 0.0;

		foreach ($order->get_items() as $key => $value) {
			$product = new WC_Product($value['item_meta']['_product_id'][0]);
			$quantity = $value['item_meta']['_qty'][0];
			$info = self::get_product_rates( $product->get_sku() );

			if (!$info) {
				$order_total += $product->get_display_price() * $quantity;
				continue;
			}

			$interest_rate = $instalments > 4
				? $info->dezx // 5x and up
				: ( $instalments > 1
					? $info->quatrox // 2x to 4x
					: $info->avista ); // 1x

			$base = (float) $info->boleto;
			$rate = (float) $interest_rate;
			$order_total += ( $base * ( 1 + $rate ) ) * $quantity;
		}

		return $order_total;
	}

	/**
	 * @param  string $sku The product sku
	 * @return object
	 */
	public static function get_product_rates($sku)
	{
		$path = self::get_xml_path();
		$products = simplexml_load_file( $path );

		foreach ($products->elemento as $single) {
			if ($single->sku != $sku) {
				continue;
			}

			return $single;
		}

		return null;
	}

	public static function get_xml_path(){
		return ABSPATH.'sku_parques_pag.xml';
		//return get_bloginfo( 'template_directory' ) . '/xml/' . 'sku_parques_pag.xml';
	}
}
