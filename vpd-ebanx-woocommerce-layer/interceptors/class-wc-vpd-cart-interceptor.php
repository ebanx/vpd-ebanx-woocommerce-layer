<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_VPD_Cart_Interceptor {
    const CART_MAX_AMOUNT = 3000;

    /**
     * Binds hooks, starts vars
     */
    public function __construct() {
        add_action( 'woocommerce_check_cart_items', array($this, 'limit_cart_total_amount') );
    }

    /**
     * It limits the cart total amount
     */
    public function limit_cart_total_amount() {
        if( ( ! is_cart()
            && ! is_checkout() )
            || WC()->cart->subtotal <= self::CART_MAX_AMOUNT ) {
            return;
        }
        wc_add_notice('<strong>Este pedido ultrapassa o limite de US$3000 por pessoa por mês. Qualquer dúvida, entre em contato com reservas@vpdtravel.com.</strong>','error');
    }
}
