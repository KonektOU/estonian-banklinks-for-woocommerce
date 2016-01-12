<?php
/*
	Plugin Name: Estonian Banklinks for WooCommerce
	Plugin URI: https://github.com/KonektOU/estonian-banklinks-for-woocommerce
	Description: Extends WooCommerce with most commonly used Estonian banklinks.
	Version: 1.0.1
	Author: Konekt OÜ
	Author URI: http://www.konekt.ee
	License: GPLv2 or later
*/

// Security check
if( ! defined( 'ABSPATH' ) ) exit;

/**
 * Main file constant
 */
define( 'WC_ESTONIAN_GATEWAYS_MAIN_FILE', __FILE__ );

/**
 * Includes folder path
 */
define( 'WC_ESTONIAN_GATEWAYS_INCLUDES_PATH', plugin_dir_path( WC_ESTONIAN_GATEWAYS_MAIN_FILE ) . 'includes' );

/**
 * @class    Estonian_Gateways_For_WooCommerce
 * @category Plugin
 * @package  Estonian_Gateways_For_WooCommerce
 */
class Estonian_Gateways_For_WooCommerce {
	/**
	 * Instance
	 *
	 * @var null
	 */
	private static $instance = null;

	/**
	 * Class constructor
	 */
	function __construct() {
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
	}

	/**
	 * Initialize plugin
	 * @return void
	 */
	public function plugins_loaded() {
		// Check if payment gateways are available
		if( ! $this->is_payment_gateway_class_available() ) return FALSE;

		add_filter( 'woocommerce_payment_gateways', array( $this, 'register_gateways' ) );

		// Load functionality, translations
		$this->includes();
		$this->load_translations();
	}

	/**
	 * Require functionality
	 *
	 * @return void
	 */
	public function includes() {
		// Abstract classes
		require_once WC_ESTONIAN_GATEWAYS_INCLUDES_PATH . '/abstracts/class-wc-banklink.php';
		require_once WC_ESTONIAN_GATEWAYS_INCLUDES_PATH . '/abstracts/class-wc-banklink-ipizza.php';
		require_once WC_ESTONIAN_GATEWAYS_INCLUDES_PATH . '/abstracts/class-wc-banklink-solo.php';

		// IPizza
		require_once WC_ESTONIAN_GATEWAYS_INCLUDES_PATH . '/gateways/class-wc-banklink-danske-gateway.php';
		require_once WC_ESTONIAN_GATEWAYS_INCLUDES_PATH . '/gateways/class-wc-banklink-lhv-gateway.php';
		require_once WC_ESTONIAN_GATEWAYS_INCLUDES_PATH . '/gateways/class-wc-banklink-seb-gateway.php';
		require_once WC_ESTONIAN_GATEWAYS_INCLUDES_PATH . '/gateways/class-wc-banklink-swedbank-gateway.php';
		require_once WC_ESTONIAN_GATEWAYS_INCLUDES_PATH . '/gateways/class-wc-banklink-krediidipank-gateway.php';

		// Solo
		require_once WC_ESTONIAN_GATEWAYS_INCLUDES_PATH . '/gateways/class-wc-banklink-nordea-gateway.php';

		// Other
		require_once WC_ESTONIAN_GATEWAYS_INCLUDES_PATH . '/gateways/class-wc-banklink-maksekeskus-redirect-gateway.php';
		require_once WC_ESTONIAN_GATEWAYS_INCLUDES_PATH . '/gateways/class-wc-banklink-estcard-gateway.php';
	}

	/**
	 * Check if WooCommerce WC_Payment_Gateway class exists
	 *
	 * @return boolean True if it does
	 */
	function is_payment_gateway_class_available() {
		return class_exists( 'WC_Payment_Gateway' );
	}

	/**
	 * Load translations
	 *
	 * Allows overriding the offical translation by placing
	 * the translation files in wp-content/languages/woocommerce-estonian-banklinks
	 *
	 * @return void
	 */
	function load_translations() {
		$domain = 'wc-gateway-estonia-banklink';
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, WP_LANG_DIR . '/woocommerce-estonian-banklinks/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, FALSE, dirname( plugin_basename( WC_ESTONIAN_GATEWAYS_MAIN_FILE ) ) . '/languages/' );
	}

	/**
	 * Register gateways
	 *
	 * @param  array $gateways Gateways
	 * @return array           Gateways
	 */
	function register_gateways( $gateways ) {
		$gateways[] = 'WC_Banklink_Danske_Gateway';
		$gateways[] = 'WC_Banklink_LHV_Gateway';
		$gateways[] = 'WC_Banklink_SEB_Gateway';
		$gateways[] = 'WC_Banklink_Swedbank_Gateway';
		$gateways[] = 'WC_Banklink_Krediidipank_Gateway';
		$gateways[] = 'WC_Banklink_Nordea_Gateway';
		$gateways[] = 'WC_Banklink_Maksekeskus_Redirect_Gateway';
		$gateways[] = 'WC_Banklink_Estcard_Gateway';

		return $gateways;
	}


	/**
	 * Fetch instance of this plugin
	 *
	 * @return Estonian_Gateways_For_WooCommerce
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) )
			self::$instance = new self;

		return self::$instance;
	}
}


/**
 * Returns the main instance of Estonian_Gateways_For_WooCommerce to prevent the need to use globals.
 * @return Estonian_Gateways_For_WooCommerce
 */
function WC_Estonian_Gateways() {
	return Estonian_Gateways_For_WooCommerce::instance();
}

// Global for backwards compatibility.
$GLOBALS['wc_estonian_gateways'] = WC_Estonian_Gateways();
