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
		add_action('woocommerce_checkout_process', array($this, 'validar_total_mes'));
		add_action('wp_ajax_nopriv_validar_total_mes', array($this, 'validar_total_mes'));
		add_action('wp_ajax_validar_total_mes', array($this, 'validar_total_mes'));
	}

	/**
	 *  Saves the CPF the user entered to user meta.
	 */
	public function save_user_cpf()
	{
		$cpf = WC_EBANX_Request::read('ebanx_billing_brazil_document', '');
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
	 * As seen on VPD's original plugin
	 */
	public function validar_total_mes()
	{
		$cpf            = WC_EBANX_Request::read('ebanx_billing_brazil_document');
		$payment_method = WC_EBANX_Request::read('payment_method', '');

		//Valida os casos de TEF e BOLETO
		switch ($payment_method) {
			case 'ebanx_cc':
				$retorno = json_decode($this->verificar_saldo_ebanx($cpf), true);

				if ($retorno["status"] == "SUCCESS") {
					// Realiza a validação do total do mês
					$totalCarrinho = floatval(wp_kses_data(WC()->cart->get_total()));

					if ($totalCarrinho > floatval($retorno["document_balance"]["available"]) || $totalCarrinho > 3000.00) {
						wp_die(json_encode(array(
							'resposta' => false,
							'quantia'  => $retorno["document_balance"]["available"],
							'carrinho' => $totalCarrinho
						)));
					} else {
						wp_die(json_encode(array(
							'resposta' => true,
							'quantia'  => $retorno["document_balance"]["available"],
							'carrinho' => $totalCarrinho
						)));
					}
				} else {
					wp_die(json_encode(array('resposta' => false)));
				}
				break;
			default:
				$retorno = json_decode($this->verificar_saldo_ebanx($cpf), true);

				if ($retorno["status"] == "SUCCESS") {
					// Realiza a validação do total do mês
					$totalCarrinho = floatval(wp_kses_data(WC()->cart->get_total()));
					if ($totalCarrinho > floatval($retorno["document_balance"]["available"]) || $totalCarrinho > 3000.00) {
						wc_add_notice("Este pedido (US$" . $totalCarrinho . ") ultrapassa o limite de US$3000 por pessoa por mês. Qualquer dúvida, entre em contato com reservas@vpdtravel.com.",
							'error');
					}
				} else {
					wc_add_notice("Verifique o CPF informado.", 'error');
				}
				break;
		}
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

	private function verificar_saldo_ebanx($cpf)
	{
		return file_get_contents( "https://api.ebanx.com/ws/documentbalance?integration_key=d2d6a9311fe8c55eaada29acea2c9e869afd5643c8ebdf9f139589dbea79d6f73f8f1b14f7e8712126c5ecbe6abe61bac598&document=" . $cpf . "&currency_code=USD");
	}
}
