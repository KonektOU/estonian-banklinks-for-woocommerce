<?php
/*
	Plugin Name: WooCommerce Estonian Banklinks
	Plugin URI: http://www.konekt.ee
	Description: Extends WooCommerce with Estonian most commonly used banklinks.
	Version: 1.0
	Author: Konekt OÜ
	Author URI: http://www.konekt.ee
*/


/**
 * Main file constant
 */
define( 'WC_ESTONIAN_GATEWAYS_MAIN_FILE', __FILE__ );

/**
 * Initialize plugin
 *
 * @return void
 */
function wc_estonian_gateways() {
	// Load translations
	load_plugin_textdomain( 'wc-estonian-gateways', false, dirname( plugin_basename( WC_ESTONIAN_GATEWAYS_MAIN_FILE ) ) . '/languages' );

	// Abstract classes
	require_once untrailingslashit( plugin_dir_path( WC_ESTONIAN_GATEWAYS_MAIN_FILE ) ) . '/includes/abstracts/class-wc-banklink.php';
	require_once untrailingslashit( plugin_dir_path( WC_ESTONIAN_GATEWAYS_MAIN_FILE ) ) . '/includes/abstracts/class-wc-banklink-ipizza.php';
	require_once untrailingslashit( plugin_dir_path( WC_ESTONIAN_GATEWAYS_MAIN_FILE ) ) . '/includes/abstracts/class-wc-banklink-solo.php';

	// IPizza
	require_once untrailingslashit( plugin_dir_path( WC_ESTONIAN_GATEWAYS_MAIN_FILE ) ) . '/includes/class-wc-banklink-danske-gateway.php';
	require_once untrailingslashit( plugin_dir_path( WC_ESTONIAN_GATEWAYS_MAIN_FILE ) ) . '/includes/class-wc-banklink-lhv-gateway.php';
	require_once untrailingslashit( plugin_dir_path( WC_ESTONIAN_GATEWAYS_MAIN_FILE ) ) . '/includes/class-wc-banklink-seb-gateway.php';
	require_once untrailingslashit( plugin_dir_path( WC_ESTONIAN_GATEWAYS_MAIN_FILE ) ) . '/includes/class-wc-banklink-swedbank-gateway.php';

	// Solo
	require_once untrailingslashit( plugin_dir_path( WC_ESTONIAN_GATEWAYS_MAIN_FILE ) ) . '/includes/class-wc-banklink-nordea-gateway.php';

	// Other
	require_once untrailingslashit( plugin_dir_path( WC_ESTONIAN_GATEWAYS_MAIN_FILE ) ) . '/includes/class-wc-banklink-maksekeskus-redirect-gateway.php';

	/**
	 * Register gateways
	 *
	 * @param  array $gateways Gateways
	 * @return array           Gateways
	 */
	function wc_register_estonian_gateways( $gateways ) {
		$gateways[] = 'WC_Banklink_Danske_Gateway';
		$gateways[] = 'WC_Banklink_LHV_Gateway';
		$gateways[] = 'WC_Banklink_SEB_Gateway';
		$gateways[] = 'WC_Banklink_Swedbank_Gateway';
		$gateways[] = 'WC_Banklink_Nordea_Gateway';
		$gateways[] = 'WC_Banklink_Maksekeskus_Redirect_Gateway';

		return $gateways;
	}
	add_filter( 'woocommerce_payment_gateways', 'wc_register_estonian_gateways' );
}
add_action( 'plugins_loaded', 'wc_estonian_gateways' );
