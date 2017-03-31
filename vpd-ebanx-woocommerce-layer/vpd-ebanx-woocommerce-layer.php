<?php
/**
 * Plugin Name: VPD EBANX WooCommerce Layer
 * Plugin URI: https://www.ebanx.com/business/en/developers/integrations/extensions-and-plugins/woocommerce-plugin
 * Description: Plugin to read VDP custom files
 * Author: EBANX
 * Author URI: https://www.ebanx.com/business/en
 * Version: 1.0.0
 * License: MIT
 * Text Domain: vpd-ebanx-woocommerce-layer
 * Domain Path: /languages
 *
 * @package VPD_EBANX_WooCommerce_Layer
 */

if (!defined('ABSPATH')) {
	exit;
}


class VPD_EBANX_WC {
	const INCLUDES_DIR = __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR;
	const INTERCEPTORS_DIR = __DIR__ . DIRECTORY_SEPARATOR . 'interceptors' . DIRECTORY_SEPARATOR;

	const EBANX_PLUGIN_NAME = 'woocommerce-gateway-ebanx' . DIRECTORY_SEPARATOR . 'woocommerce-gateway-ebanx.php';
	const MIN_WC_EBANX_VERSION = '1.11.0';

	/**
	 * Holds the singleton
	 */
	private static $instance = null;

	/**
	 * Holds interceptors' instances
	 */
	private $interceptors = array();

	/**
	 * Singleton initializer
	 *
	 * @return VPD_EBANX_WC_IR
	 */
	public static function get_instance() {
		if (self::$instance == null) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	// PRIVATE METHODS

	/**
	 * Plugin main code
	 */
	private function __construct() {
		$this->load_libs();

		if (!class_exists('WC_EBANX')) {
			// TODO: a view with an install button
			(new WC_VPD_EBANX_Notice())
				->with_type('error')
				->with_message('VPD Layer requires EBANX Payment Gateway for WooCommerce to work.')
				->persistent()
				->enqueue();
			return;
		}

		if (version_compare(WC_EBANX::VERSION, self::MIN_WC_EBANX_VERSION, '<')) {
			// TODO: a view with an update button
			(new WC_VPD_EBANX_Notice())
				->with_type('error')
				->with_message('VPD Layer requires EBANX Payment Gateway for WooCommerce '.self::MIN_WC_EBANX_VERSION.' or higher to work.')
				->persistent()
				->enqueue();
			return;
		}

		$this->bind_hooks();
	}

	/**
	 * Loads the plugin libs
	 */
	private function load_libs() {
		//Includes
		require_once(self::INCLUDES_DIR . 'class-wc-ebanx-notice.php');
		require_once(self::INCLUDES_DIR . 'class-wc-vpd-xml-interest-calculator.php');

		//Interceptors
		require_once(self::INTERCEPTORS_DIR . 'class-wc-vpd-settings-interceptor.php');
		require_once(self::INTERCEPTORS_DIR . 'class-wc-vpd-credit-card-gateway-interceptor.php');
	}

	/**
	 * Binds hooks to functions
	 */
	private function bind_hooks() {
		$this->interceptors[] = new WC_VPD_Settings_Interceptor();
		$this->interceptors[] = new WC_VPD_Credit_Card_Gateway_Interceptor();
	}
}

add_action('plugins_loaded', array('VPD_EBANX_WC', 'get_instance'));
