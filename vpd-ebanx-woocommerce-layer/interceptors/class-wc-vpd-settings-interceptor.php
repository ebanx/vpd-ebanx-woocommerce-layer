<?php

if (!defined('ABSPATH')) {
	exit;
}

class WC_VPD_Settings_Interceptor{
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

		(new WC_EBANX_Notice())
			->with_type('info')
			->with_message('VPD Layer - Interest rates file lookup: "'.WC_VPD_XML_Interest_Calculator::get_xml_path().'"!')
			->persistent()
			->enqueue();
	}

	/**
	 * Removes some form fields from settings to prevent use
	 *
	 * @param  array $fields The original form fields from EBANX plugin
	 * @return array The modified form fields
	 */
	public function form_fields($fields) {
		unset($fields['interest_rates_enabled']);
		unset($fields['interest_rates_01']);

		for ($i = 2; $i <= 12; $i++) {
			unset($fields['interest_rates_'.sprintf("%02d", $i)]);
		}

		return $fields;
	}
}
