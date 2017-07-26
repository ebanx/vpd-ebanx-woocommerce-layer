<?php

if (!defined('ABSPATH')) {
	exit;
}

class WC_VPD_Checkout_Interceptor
{

	/**
	 * Binds hooks, starts vars
	 */
	public function __construct()
	{
		add_action('woocommerce_checkout_process', array($this, 'save_user_cpf'));
	}

	public function save_user_cpf()
	{
		$cpf = WC_EBANX_Request::read('CPF', '');
		if (empty($cpf)) {
			wc_add_notice('<strong>CPF não informado</strong>. Por favor, informe um CPF válido.', 'error');

			return;
		}

		if (!$this->isValidCpf($cpf)) {
			wc_add_notice('<strong>CPF inválido</strong>. Por favor, verifique o CPF informado.', 'error');

			return;
		}

		$usuario = wp_get_current_user();
		update_user_meta($usuario->ID, 'cpf', sanitize_text_field($cpf));
	}

	/**
	 * @param string $cpf
	 *
	 * @return bool
	 */
	private function isValidCpf($cpf)
	{
		$cpf = preg_replace('/[^0-9]/is', '', $cpf);

		if (strlen($cpf) != 11 || preg_match('/(\d)\1{10}/', $cpf)) {
			return false;
		}

		for ($t = 9; $t < 11; $t++) {
			for ($d = 0, $c = 0; $c < $t; $c++) {
				$d += $cpf{$c} * (($t + 1) - $c);
			}
			$d = ((10 * $d) % 11) % 10;
			if ($cpf{$c} != $d) {
				return false;
			}
		}

		return true;
	}
}
