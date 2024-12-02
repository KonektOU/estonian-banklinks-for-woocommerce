<?php
/**
 * Plugin Name: Estonian Banklinks for WooCommerce
 * Plugin URI: https://wordpress.org/plugins/estonian-banklinks-for-woocommerce/
 * Description: Extends WooCommerce with most commonly used Estonian banklinks.
 * Version: 1.5
 * Author: Konekt OÜ
 * Author URI: https://www.konekt.ee
 * Developer: Risto Niinemets
 * Developer URI: https://www.konekt.ee
 * License: GPLv2 or later
 * Text Domain: wc-gateway-estonia-banklink
 * Domain Path: /languages
 * WC requires at least: 3.3
 * WC tested up to: 8.5.2
 *
 * @package Estonian Banklinks for WooCommerce
 */

// Security check.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main file constant
 */
define( 'WC_ESTONIAN_GATEWAYS_MAIN_FILE', __FILE__ );

/**
 * Includes folder path
 */
define( 'WC_ESTONIAN_GATEWAYS_INCLUDES_PATH', plugin_dir_path( WC_ESTONIAN_GATEWAYS_MAIN_FILE ) . 'includes' );

/**
 * Templates folder path
 */
define( 'WC_ESTONIAN_GATEWAYS_TEMPLATES_PATH', plugin_dir_path( WC_ESTONIAN_GATEWAYS_MAIN_FILE ) . 'templates' );

/**
 * Main class
 *
 * @category Plugin
 * @package  Estonian_Banklinks_For_WooCommerce
 */
class Estonian_Banklinks_For_WooCommerce {

	/**
	 * Instance
	 *
	 * @var null
	 */
	private static $instance = null;

	/**
	 * Class constructor
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ) );
		add_action( 'init', array( $this, 'load_translations' ) );
	}

	/**
	 * Initialize plugin
	 *
	 * @return void
	 */
	public function plugins_loaded() {
		// Check if payment gateways are available.
		if ( $this->is_payment_gateway_class_available() ) {
			add_filter( 'woocommerce_payment_gateways', array( $this, 'register_gateways' ) );

			// Load functionality.
			$this->includes();
		}

		add_action( 'before_woocommerce_init', array( $this, 'declare_wc_cot_compatibility' ) );
	}

	/**
	 * Enqueue styles on checkout page
	 *
	 * @return void
	 */
	public function wp_enqueue_scripts() {
		wp_register_style( 'wc-gateway-estonia-banklink', plugins_url( 'assets/css/style.css', WC_ESTONIAN_GATEWAYS_MAIN_FILE ), array(), '1.5' );

		if ( function_exists( 'is_checkout' ) && is_checkout() ) {
			wp_enqueue_style( 'wc-gateway-estonia-banklink' );
		}
	}

	/**
	 * Require functionality
	 *
	 * @return void
	 */
	public function includes() {
		// Compatibility helpers.
		include_once WC_ESTONIAN_GATEWAYS_INCLUDES_PATH . '/compatibility-helpers.php';

		// Abstract classes.
		include_once WC_ESTONIAN_GATEWAYS_INCLUDES_PATH . '/abstracts/class-wc-banklink.php';
		include_once WC_ESTONIAN_GATEWAYS_INCLUDES_PATH . '/abstracts/class-wc-banklink-ipizza.php';
		include_once WC_ESTONIAN_GATEWAYS_INCLUDES_PATH . '/abstracts/class-wc-banklink-solo.php';
		include_once WC_ESTONIAN_GATEWAYS_INCLUDES_PATH . '/abstracts/class-wc-banklink-maksekeskus.php';

		// IPizza.
		include_once WC_ESTONIAN_GATEWAYS_INCLUDES_PATH . '/gateways/class-wc-banklink-danske-gateway.php';
		include_once WC_ESTONIAN_GATEWAYS_INCLUDES_PATH . '/gateways/class-wc-banklink-lhv-gateway.php';
		include_once WC_ESTONIAN_GATEWAYS_INCLUDES_PATH . '/gateways/class-wc-banklink-seb-gateway.php';
		include_once WC_ESTONIAN_GATEWAYS_INCLUDES_PATH . '/gateways/class-wc-banklink-swedbank-gateway.php';
		include_once WC_ESTONIAN_GATEWAYS_INCLUDES_PATH . '/gateways/class-wc-banklink-krediidipank-gateway.php';
		include_once WC_ESTONIAN_GATEWAYS_INCLUDES_PATH . '/gateways/class-wc-banklink-nordea-ipizza-gateway.php';
		include_once WC_ESTONIAN_GATEWAYS_INCLUDES_PATH . '/gateways/class-wc-banklink-liisi-gateway.php';

		// Solo.
		include_once WC_ESTONIAN_GATEWAYS_INCLUDES_PATH . '/gateways/class-wc-banklink-nordea-gateway.php';

		// Maksekeskus.
		include_once WC_ESTONIAN_GATEWAYS_INCLUDES_PATH . '/gateways/class-wc-banklink-maksekeskus-redirect-gateway.php';
		include_once WC_ESTONIAN_GATEWAYS_INCLUDES_PATH . '/gateways/class-wc-banklink-maksekeskus-billing-api.php';

		// Other.
		include_once WC_ESTONIAN_GATEWAYS_INCLUDES_PATH . '/gateways/class-wc-banklink-estcard-gateway.php';
	}

	/**
	 * Check if WooCommerce WC_Payment_Gateway class exists
	 *
	 * @return boolean True if it does
	 */
	public function is_payment_gateway_class_available() {
		return class_exists( 'WC_Payment_Gateway' );
	}

	/**
	 * Load translations
	 *
	 * Allows overriding the offical translation by placing
	 * the translation files in wp-content/languages/estonian-banklinks-for-woocommerce
	 *
	 * @return void
	 */
	public function load_translations() {
		$domain = 'wc-gateway-estonia-banklink';
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, WP_LANG_DIR . '/estonian-banklinks-for-woocommerce/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, false, dirname( plugin_basename( WC_ESTONIAN_GATEWAYS_MAIN_FILE ) ) . '/languages/' );
	}

	/**
	 * Register gateways
	 *
	 * @param  array $gateways Gateways.
	 * @return array           Gateways
	 */
	public function register_gateways( $gateways ) {
		$gateways[] = 'WC_Banklink_Danske_Gateway';
		$gateways[] = 'WC_Banklink_LHV_Gateway';
		$gateways[] = 'WC_Banklink_SEB_Gateway';
		$gateways[] = 'WC_Banklink_Swedbank_Gateway';
		$gateways[] = 'WC_Banklink_Krediidipank_Gateway';
		$gateways[] = 'WC_Banklink_Nordea_Gateway';
		$gateways[] = 'WC_Banklink_Nordea_Ipizza_Gateway';
		$gateways[] = 'WC_Banklink_Liisi_Gateway';
		$gateways[] = 'WC_Banklink_Maksekeskus_Redirect_Gateway';
		$gateways[] = 'WC_Banklink_Maksekeskus_Billing_API';
		$gateways[] = 'WC_Banklink_Estcard_Gateway';

		return $gateways;
	}

	/**
	 * Declare high performance order storage (COT) compatibility
	 *
	 * @return void
	 */
	public function declare_wc_cot_compatibility() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', WC_ESTONIAN_GATEWAYS_MAIN_FILE, true );
		}
	}

	/**
	 * Fetch instance of this plugin
	 *
	 * @return Estonian_Banklinks_For_WooCommerce
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

/**
 * Returns the main instance of Estonian_Banklinks_For_WooCommerce to prevent the need to use globals.
 *
 * @return Estonian_Banklinks_For_WooCommerce
 */
function wc_estonian_gateways() {
	return Estonian_Banklinks_For_WooCommerce::instance();
}

// Global for backwards compatibility.
$GLOBALS['wc_estonian_gateways'] = wc_estonian_gateways();
