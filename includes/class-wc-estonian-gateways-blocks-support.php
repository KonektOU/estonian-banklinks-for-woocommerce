<?php
/**
 * Make the banklinks show up in the checkout block.
 *
 * WooCommerce's block checkout does not read the gateway objects the way the
 * shortcode checkout does: a gateway that has not registered a payment method
 * type is not hidden or degraded, it is simply not there. Every one of these
 * banklinks was invisible on a store using the block, which is what a store
 * built on any recent WooCommerce gets by default.
 *
 * @package Estonian Banklinks For WooCommerce
 */

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * One registration per gateway, all sharing a single script.
 */
final class WC_Estonian_Gateways_Blocks_Support extends AbstractPaymentMethodType {

	/**
	 * Script handle, registered once for all of the gateways.
	 */
	const SCRIPT_HANDLE = 'wc-estonian-gateways-blocks';

	/**
	 * Payment method name, which is the gateway id.
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * The gateway this registration stands for.
	 *
	 * @var WC_Banklink
	 */
	private $gateway;

	/**
	 * Constructor.
	 *
	 * @param WC_Banklink $gateway Gateway.
	 */
	public function __construct( $gateway ) {
		$this->gateway = $gateway;
		$this->name    = $gateway->id;
	}

	/**
	 * Called by the registry.
	 *
	 * @return void
	 */
	public function initialize() {
		$this->settings = get_option( 'woocommerce_' . $this->name . '_settings', array() );
	}

	/**
	 * Whether to offer this method in the block.
	 *
	 * @return bool
	 */
	public function is_active() {
		return $this->gateway->is_available();
	}

	/**
	 * Script handles for the front end.
	 *
	 * @return string[]
	 */
	public function get_payment_method_script_handles() {
		self::register_script();

		return array( self::SCRIPT_HANDLE );
	}

	/**
	 * Script handles for the editor, which needs the same file.
	 *
	 * @return string[]
	 */
	public function get_payment_method_script_handles_for_admin() {
		return $this->get_payment_method_script_handles();
	}

	/**
	 * What the block needs to draw this method.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		return array(
			'title'       => $this->gateway->get_title(),
			'description' => $this->gateway->get_description(),
			'icon'        => $this->gateway->icon,
			'supports'    => array_filter( $this->gateway->supports, array( $this->gateway, 'supports' ) ),
		);
	}

	/**
	 * Register the shared script, once.
	 *
	 * @return void
	 */
	private static function register_script() {
		if ( wp_script_is( self::SCRIPT_HANDLE, 'registered' ) ) {
			return;
		}

		wp_register_script(
			self::SCRIPT_HANDLE,
			plugins_url( 'assets/js/blocks.js', WC_ESTONIAN_GATEWAYS_MAIN_FILE ),
			array( 'wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-html-entities' ),
			WC_ESTONIAN_GATEWAYS_VERSION,
			true
		);

		// The script is one file for every gateway, so it has to be told
		// which ones to look for; each one's own data arrives separately
		// under "<id>_data".
		wp_localize_script(
			self::SCRIPT_HANDLE,
			'wcEstonianGateways',
			array( 'gateways' => wc_estonian_gateways()->get_blocks_gateway_ids() )
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( self::SCRIPT_HANDLE, 'wc-gateway-estonia-banklink' );
		}
	}
}
