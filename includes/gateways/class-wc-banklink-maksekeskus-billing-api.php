<?php
class WC_Banklink_Maksekeskus_Billing_API extends WC_Banklink {

	public $api_url;

	private $api_secret;

	private $shop_id;

	private $selected_method_meta_field;

	/**
	 * WC_Banklink_Maksekeskus_Redirect_Gateway
	 */
	function __construct() {
		$this->id           = 'maksekeskus_billing_api';
		$this->method_title = __( 'Maksekeskus', 'wc-gateway-estonia-banklink' );

		$this->selected_method_meta_field = sprintf( '_%s_selected_method', $this->id );

		parent::__construct();

		if( $this->enabled !== 'no' ) {
			add_action( 'template_redirect',                        array( $this, 'request_available_payment_methods' ), 0 );
			add_action( 'woocommerce_checkout_update_order_meta',   array( $this, 'checkout_save_order_gateway_method_meta' ), 10, 2 );
			add_action( 'woocommerce_checkout_update_order_review', array( $this, 'checkout_save_session_gateway_method' ), 10, 1 );
			add_filter( 'woocommerce_before_template_part',         array( $this, 'checkout_maybe_save_session_gateway_method' ), 10, 1 );

			$this->api_url    = $this->get_option( 'api_url' );
			$this->api_secret = $this->get_option( 'api_secret' );
			$this->shop_id    = $this->get_option( 'shop_id' );
			$this->has_fields = true;
		}
	}

	/**
	 * Set settings fields
	 *
	 * @return void
	 */
	function init_form_fields() {
		parent::init_form_fields();

		// Add fields
		$this->form_fields = array_merge( $this->form_fields, array(
			'currency'        => array(
				'title'       => __( 'Currency', 'wc-gateway-estonia-banklink' ),
				'type'        => 'select',
				'options'     => get_woocommerce_currencies(),
				'default'     => get_woocommerce_currency()
			),
			'shop_id'         => array(
				'title'       => __( 'Shop ID', 'wc-gateway-estonia-banklink' ),
				'type'        => 'text',
				'default'     => '',
				'description' => __( 'This will be provided by Maksekeskus', 'wc-gateway-estonia-banklink' ),
				'desc_tip'    => TRUE
			),
			'api_url'         => array(
				'title'       => __( 'API URL', 'wc-gateway-estonia-banklink' ),
				'type'        => 'text',
				'default'     => 'https://api.maksekeskus.ee/v1/'
			),
			'api_secret'      => array(
				'title'       => __( 'API secret', 'wc-gateway-estonia-banklink' ),
				'type'        => 'text',
				'description' => __( 'This will be provided by Maksekeskus', 'wc-gateway-estonia-banklink' ),
				'desc_tip'    => TRUE
			),
			'locale'          => array(
				'title'       => __( 'Preferred locale', 'wc-gateway-estonia-banklink' ),
				'type'        => 'text',
				'description' => __( 'RFC-2616 format locale', 'wc-gateway-estonia-banklink' ),
				'desc_tip'    => TRUE,
				'default'     => $this->get_default_language()
			),
			'return_url'            => array(
				'title'             => __( 'Return URL', 'wc-gateway-estonia-banklink' ),
				'type'              => 'text',
				'default'           => $this->notify_url,
				'description'       => __( 'URL, where customer is redirected after the payment.', 'wc-gateway-estonia-banklink' ),
				'desc_tip'          => TRUE,
				'custom_attributes' => array(
					'readonly' => 'readonly'
				)
			)
		) );
	}

	public function get_api_url( $method, $query_data = array() ) {
		if( is_array( $method ) ) {
			$method = implode( '/', $method );
		}

		$api_request_url = trailingslashit( $this->api_url ) . $method;

		return $query_data ? add_query_arg( $query_data, $api_request_url ) : $api_request_url;
	}

	public function request_available_payment_methods() {
		if( $this->shop_id && $this->api_url && $this->api_secret ) {
			$customer_country = wc_estonian_gateways_get_customer_billing_country();

			if ( ! $this->get_available_payment_methods( $customer_country ) || WP_DEBUG === TRUE ) {
				$methods_query   = array(
					'currency' => $this->get_option( 'currency' ),
					'country'  => $customer_country
				);
				$methods_request = $this->request_from_api( $this->get_api_url( 'methods', $methods_query ), 'GET' );

				if( is_object( $methods_request ) && ! empty( $methods_request ) ) {
					$this->set_available_payment_methods( $customer_country, $methods_request );
				}
			}
		}
	}

	public function get_available_payment_methods( $country ) {
		$payment_methods = get_transient( sprintf( '%s_%s_available_methods', $this->id, $country ) );

		return $payment_methods;
	}

	private function set_available_payment_methods( $country, $methods ) {
		set_transient( sprintf( '%s_%s_available_methods', $this->id, $country ), $methods, DAY_IN_SECONDS );
	}

	public function payment_fields() {
		$methods = $this->get_available_payment_methods( wc_estonian_gateways_get_customer_billing_country() );

		if( ! $methods ) {
			$this->request_available_payment_methods();

			$methods = $this->get_available_payment_methods( wc_estonian_gateways_get_customer_billing_country() );
		}

		if( $methods ) {
			foreach( $methods as $method_group => &$gateways ) {
				foreach( $gateways as $gateway ) {
					$gateway->logo = $this->get_maksekeskus_gateway_logo_url( $gateway->name );
				}
			}

			if( $selected_method = WC()->session->get( $this->selected_method_meta_field ) ) {
				$current_method = $selected_method;
			}
			else {
				$current_method = reset( $methods->banklinks )->name;
			}

			$template_data = apply_filters( 'woocommerce_' . $this->id . '_gateway_template_data', array(
				'methods'        => $methods,
				'current_method' => $current_method
			) );

			wc_get_template( 'checkout/payment-method-maksekeskus.php', $template_data );
		}
	}

	public function get_maksekeskus_gateway_logo_url( $gateway ) {
		return sprintf( 'https://static.maksekeskus.ee/img/channel/lnd/%s.png', $gateway );
	}

	private function request_from_api( $url, $method = 'POST' ) {
		$args = array(
			'headers' => array(
				'Authorization' => sprintf( 'Basic %s', base64_encode( sprintf( '%s:%s', $this->shop_id, $this->api_secret ) ) )
			)
		);

		if( $method == 'GET' ) {
			$request = wp_remote_get( $url, $args );
		}
		else {
			$request = wp_remote_post( $url, $args );
		}

		if( wp_remote_retrieve_response_code( $request ) == 200 ) {
			$request_response = json_decode( wp_remote_retrieve_body( $request ) );

			return $request_response;
		}

		return null;
	}

	/**
	 * Saves selected method to order meta
	 *
	 * @param  integer $order_id Order ID
	 * @param  array   $posted   WooCommerce posted data
	 *
	 * @return void
	 */
	function checkout_save_order_gateway_method_meta( $order_id, $posted ) {
		if( isset( $_POST['banklink_gateway_maksekeskus_method'] ) ) {
			update_post_meta( $order_id, $this->selected_method_meta_field, $_POST['banklink_gateway_maksekeskus_method'] );
		}
	}

	/**
	 * Saves selected method in session whilst order review updates
	 *
	 * @param  string $posted Posted data
	 *
	 * @return void
	 */
	function checkout_save_session_gateway_method( $post_data ) {
		parse_str( $post_data, $posted );

		if( isset( $posted['banklink_gateway_maksekeskus_method'] ) ) {
			WC()->session->set( $this->selected_method_meta_field, $posted['banklink_gateway_maksekeskus_method'] );
		}
	}

	function checkout_maybe_save_session_gateway_method( $template_name ) {
		if( $template_name == 'checkout/payment.php' && isset( $_POST['post_data'] ) ) {
			parse_str( $_POST['post_data'], $posted );

			if( isset( $posted['banklink_gateway_maksekeskus_method'] ) ) {
				WC()->session->set( $this->selected_method_meta_field, $posted['banklink_gateway_maksekeskus_method'] );
			}
		}
	}

	/**
	 * Create form for bank
	 *
	 * @param  integer $order_id Order ID
	 * @return string            HTML form
	 */
	function generate_submit_form( $order_id ) {
		// Get the order
		$order            = wc_get_order( $order_id );

		$transaction_data = array(
			'transaction' => array(
				'amount' => number_format( round( $order->get_total(), 2 ), 2, '.', '' )
			)
		);
	}
}