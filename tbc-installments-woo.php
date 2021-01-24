<?php

/**
 * Plugin Name: TBC Installments for WooCoommerce
 * Plugin URI: https://www.tbcbank.ge/
 * Author: George Burduli
 * Author URI: https://github.com/burdulixda
 * Description: A custom WooCommerce payment gateway for processing installments in TBC bank.
 * Version: 1.0.1
 * License: 1.0
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * text-domain: tbc-woo
 * 
 * Class WC_Gateway_TBC file.
 *
 * @package WooCommerce\TBC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) return;

add_action( 'plugins_loaded', 'tbc_payment_init', 11 );
add_filter( 'woocommerce_currencies', 'tbc_add_gel_currencies' );
add_filter( 'woocommerce_currency_symbol', 'tbc_add_gel_currencies_symbol', 10, 2 );
add_filter( 'woocommerce_payment_gateways', 'add_to_woo_tbc_installment_gateway' );

function tbc_payment_init() {
	if ( class_exists( 'WC_Payment_Gateway' ) ) {
		require_once plugin_dir_path( __FILE__ ) . '/includes/class-wc-payment-gateway-tbc.php';
		// require_once plugin_dir_path( __FILE__ ) . '/includes/tbc-order-statuses.php';
	}
}

function add_to_woo_tbc_installment_gateway( $gateways ) {
	$gateways[] = 'WC_Gateway_TBC';
	return $gateways;
}

function tbc_add_gel_currencies( $currencies ) {
	$currencies['GEL'] = __( 'Georgian lari', 'tbc-woo' );

	return $currencies;
}

function tbc_add_gel_currencies_symbol( $currency_symbol, $currency ) {
	switch ( $currency ) {
		case 'GEL':
			$currency_symbol = '₾';
		break;
	}
	return $currency_symbol;
}