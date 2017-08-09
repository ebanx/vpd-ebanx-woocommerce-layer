<?php

if (!defined('ABSPATH')) {
	exit;
}

class WC_VPD_Settings_Interceptor{
	/**
	 * Identify interceptor instances
	 * @var string
	 */
	public $id = "settings";

	/**
	 * Binds hooks, starts vars
	 */
	public function __construct() {
		add_action('admin_init', array($this, 'admin_init'));
		add_filter('ebanx_settings_form_fields', array($this, 'form_fields'));
	}

	/**
	 * Displays some messages to warn the user
	 */
	public function admin_init(){
		if (!isset($_GET['section']) || $_GET['section'] !== 'ebanx-global') {
			return;
		}

		(new WC_VPD_EBANX_Notice())
			->with_type('info')
			->with_message('VPD Layer - Interest rates file lookup: <strong>'.WC_VPD_XML_Interest_Calculator::get_xml_path().'</strong>!')
			->persistent()
			->enqueue(100);
	}

	/**
	 * Removes some form fields from settings to prevent use
	 *
	 * @param  array $fields The original form fields from EBANX plugin
	 *
	 * @return array The modified form fields
	 */
	public function form_fields( $fields ) {
		foreach ( $fields as $field => $properties ) {
			if ( strpos( $field, 'interest_rates_' ) !== 0 ) {
				continue;
			}

			unset( $fields[ $field ] );
		}
		$fields = array(
			'xml_path' => array(
				'title' => __( 'XML Path', 'vpd-ebanx-woocommerce-layer' ),
				'type'  => 'text',
			)
		) + $fields;

		return $fields;
	}
}
