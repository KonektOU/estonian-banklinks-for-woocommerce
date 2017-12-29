<?php
/*
	Plugin Name: Estonian Banklinks for WooCommerce
	Plugin URI: https://wordpress.org/plugins/estonian-banklinks-for-woocommerce/
	Description: Extends WooCommerce with most commonly used Estonian banklinks.
	Version: 1.3.4
	Author: Konekt OÜ
	Author URI: https://www.konekt.ee
	License: GPLv2 or later
	Text Domain: wc-gateway-estonia-banklink
	WC requires at least: 2.6
	WC tested up to: 3.2.6
*/

// Security check
if ( ! defined( 'ABSPATH' ) ) exit;

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
		add_action( 'plugins_loaded',                   array( $this, 'plugins_loaded' ) );
		add_action( 'wp_enqueue_scripts',               array( $this, 'wp_enqueue_scripts' ) );

		// Allow WC template file search in this plugin
		add_filter( 'woocommerce_locate_template',      array( $this, 'locate_template' ), 20, 3 );
		add_filter( 'woocommerce_locate_core_template', array( $this, 'locate_template' ), 20, 3 );
	}

	/**
	 * Initialize plugin
	 * @return void
	 */
	public function plugins_loaded() {
		// Check if payment gateways are available
		if ( $this->is_payment_gateway_class_available() ) {
			add_filter( 'woocommerce_payment_gateways', array( $this, 'register_gateways' ) );

			// Load functionality, translations
			$this->includes();
			$this->load_translations();
		}
	}

	public function wp_enqueue_scripts() {
		wp_register_style( 'wc-gateway-estonia-banklink', plugins_url( 'assets/css/style.css', WC_ESTONIAN_GATEWAYS_MAIN_FILE ) );
		wp_register_script( 'wc-gateway-estonia-banklink', plugins_url( 'assets/js/script.js', WC_ESTONIAN_GATEWAYS_MAIN_FILE ), array( 'jquery' ), '1.0', true );

		if( function_exists( 'is_checkout' ) && is_checkout() ) {
			wp_enqueue_style( 'wc-gateway-estonia-banklink' );
			wp_enqueue_script( 'wc-gateway-estonia-banklink' );
		}
	}

	/**
	 * Require functionality
	 *
	 * @return void
	 */
	public function includes() {
		// Compatibility helpers
		require_once WC_ESTONIAN_GATEWAYS_INCLUDES_PATH . '/compatibility-helpers.php';

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
		require_once WC_ESTONIAN_GATEWAYS_INCLUDES_PATH . '/gateways/class-wc-banklink-nordea-ipizza-gateway.php';
		require_once WC_ESTONIAN_GATEWAYS_INCLUDES_PATH . '/gateways/class-wc-banklink-liisi-gateway.php';

		// Solo
		require_once WC_ESTONIAN_GATEWAYS_INCLUDES_PATH . '/gateways/class-wc-banklink-nordea-gateway.php';

		// Other
		require_once WC_ESTONIAN_GATEWAYS_INCLUDES_PATH . '/gateways/class-wc-banklink-maksekeskus-redirect-gateway.php';
		require_once WC_ESTONIAN_GATEWAYS_INCLUDES_PATH . '/gateways/class-wc-banklink-maksekeskus-billing-api.php';
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
	 * the translation files in wp-content/languages/estonian-banklinks-for-woocommerce
	 *
	 * @return void
	 */
	function load_translations() {
		$domain = 'wc-gateway-estonia-banklink';
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, WP_LANG_DIR . '/estonian-banklinks-for-woocommerce/' . $domain . '-' . $locale . '.mo' );
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
		$gateways[] = 'WC_Banklink_Nordea_Ipizza_Gateway';
		$gateways[] = 'WC_Banklink_Liisi_Gateway';
		$gateways[] = 'WC_Banklink_Maksekeskus_Redirect_Gateway';
		$gateways[] = 'WC_Banklink_Maksekeskus_Billing_API';
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

	/**
	 * Locates the WooCommerce template files from this plugin directory
	 *
	 * @param  string $template      Already found template
	 * @param  string $template_name Searchable template name
	 * @param  string $template_path Template path
	 * @return string                Search result for the template
	 */
	function locate_template( $template, $template_name, $template_path ) {
		// Tmp holder
		$_template = $template;

		if ( ! $template_path ) $template_path = WC_TEMPLATE_PATH;

		// Set our base path
		$plugin_path = plugin_dir_path( WC_ESTONIAN_GATEWAYS_MAIN_FILE ) . '/woocommerce/';

		// Look within passed path within the theme - this is priority
		$template = locate_template(
			array(
				trailingslashit( $template_path ) . $template_name,
				$template_name
			)
		);

		// Get the template from this plugin, if it exists
		if ( ! $template && file_exists( $plugin_path . $template_name ) )
			$template	= $plugin_path . $template_name;

		// Use default template
		if ( ! $template )
			$template = $_template;

		// Return what we found
		return $template;
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
