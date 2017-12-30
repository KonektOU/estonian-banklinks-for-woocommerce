<?php
class WC_Banklink_Maksekeskus_Billing_API extends WC_Banklink_Maksekeskus {

	/**
	 * Selected gateway method meta field name
	 * @var string
	 */
	private $selected_method_meta_field;

	/**
	 * WC_Banklink_Maksekeskus_Redirect_Gateway
	 */
	function __construct() {
		$this->id           = 'maksekeskus_billing_api';
		$this->method_title = __( 'Maksekeskus', 'wc-gateway-estonia-banklink' );

		// Set gateway method meta field name
		$this->selected_method_meta_field = sprintf( '_%s_selected_method', $this->id );

		parent::__construct();

		// Only do the following when this gateway is enambled
		if( $this->enabled !== 'no' ) {
			add_action( 'template_redirect',                        array( $this, 'request_available_payment_methods' ), 0 );
			add_action( 'woocommerce_checkout_update_order_meta',   array( $this, 'checkout_save_order_gateway_method_meta' ), 10, 2 );
			add_action( 'woocommerce_checkout_update_order_review', array( $this, 'checkout_save_session_gateway_method' ), 10, 1 );
			add_filter( 'woocommerce_before_template_part',         array( $this, 'checkout_maybe_save_session_gateway_method' ), 10, 1 );

			// Enqueue some gateway specific javascript
			if( is_checkout() ) {
				wc_enqueue_js( $this->gateway_method_js() );
			}

			$this->has_fields = true;
		}
	}

	public function get_api_url( $method, $query_data = array() ) {
		if( is_array( $method ) ) {
			$method = implode( '/', $method );
		}

		$api_request_url = trailingslashit( $this->get_option( 'api_url' ) ) . $method;

		return $query_data ? add_query_arg( $query_data, $api_request_url ) : $api_request_url;
	}

	public function request_available_payment_methods() {
		if( $this->get_option( 'shop_id' ) && $this->get_option( 'api_url' ) && $this->get_option( 'api_secret' ) ) {
			$customer_country = wc_estonian_gateways_get_customer_billing_country();

			if ( ! $this->get_available_payment_methods( $customer_country ) || WP_DEBUG === TRUE ) {
				$methods_query   = array(
					'currency' => $this->get_option( 'currency' ),
					'country'  => $customer_country
				);
				$methods_request = $this->request_from_api( $this->get_api_url( 'methods', $methods_query ), 'GET' );

				if( is_object( $methods_request ) && ! empty( $methods_request ) ) {
					$this->set_available_payment_methods( $customer_country, $methods_request->banklinks );
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
			foreach( $methods as $key => $method ) {
				$methods[$key]->logo = $this->get_maksekeskus_gateway_logo_url( $method->name );
			}

			$current_method = null;

			if( $selected_method = WC()->session->get( $this->selected_method_meta_field ) ) {
				foreach( $methods as $method ) {
					if( $method->name == $selected_method ) {
						$current_method = $selected_method;
					}
				}
			}

			if( ! $current_method ) {
				$current_method = reset( $methods )->name;
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

	private function request_from_api( $url, $method = 'POST', $body = array() ) {
		$args = array(
			'headers' => array(
				'Authorization' => sprintf( 'Basic %s', base64_encode( sprintf( '%s:%s', $this->get_option( 'shop_id' ), $this->get_option( 'api_secret' ) ) ) ),
				'Content-Type'  => 'application/json'
			)
		);

		if( ! empty( $body ) ) {
			$args['body'] = json_encode( $body );
		}

		if( $method == 'GET' ) {
			$request = wp_remote_get( $url, $args );
		}
		else {
			$request = wp_remote_post( $url, $args );
		}

		if( wp_remote_retrieve_response_code( $request ) == 200 || ( $method == 'POST' && in_array( wp_remote_retrieve_response_code( $request ), array( 200, 201 ) ) ) ) {
			$request_response = json_decode( wp_remote_retrieve_body( $request ) );

			return $request_response;
		}
		else {
			$this->debug( $request, 'emergency' );
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
			WC()->session->set( $this->selected_method_meta_field, $_POST['banklink_gateway_maksekeskus_method'] );
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
	function output_gateway_redirection_form( $order_id ) {
		// Get the order
		$order              = wc_get_order( $order_id );

		// Return URL
		$return_url = array(
			'url'    => $this->get_option( 'return_url' ),
			'method' => 'POST'
		);

		$transaction_data   = array(
			'transaction' => array(
				'amount'          => wc_estonian_gateways_get_order_total( $order ),
				'currency'        => wc_estonian_gateways_get_order_currency( $order ),
				'reference'       => wc_estonian_gateways_get_order_id( $order ),
				'transaction_url' => $this->get_transaction_urls()
			),
			'customer'    => array(
				'email'     => wc_estonian_gateways_get_order_billing_email( $order ),
				'country'   => wc_estonian_gateways_get_customer_billing_country(),
				'locale'    => $this->get_option( 'locale' ),
				'ip'        => wc_estonian_gateways_get_customer_ip_address( $order )
			)
		);

		$transaction_data    = $this->hookable_transaction_data( $transaction_data, $order );
		$transaction_request = $this->request_from_api( $this->get_api_url( 'transactions' ), 'POST', $transaction_data );

		if( $transaction_request ) {
			// Set transaction ID
			$order->set_transaction_id( $transaction_request->id );
			$order->save();

			// Out method
			$selected_method     = get_post_meta( $order_id, $this->selected_method_meta_field, true );
			$selected_method_url = null;

			// Find correct URL to redirect to
			foreach( $transaction_request->payment_methods->banklinks as $method ) {
				if( $method->name == $selected_method ) {
					$selected_method_url = $method->url;
				}
			}

			return $this->get_redirect_form( $selected_method_url );
		}
	}

	function get_redirect_form( $url, $fields = array() ) {
		// Add form
		$form = sprintf( '<form action="%s" method="get" id="banklink_%s_submit_form"><button class="button submit">%s</button></form>', esc_attr( $selected_method_url ), $this->id, __( 'Pay', 'wc-gateway-estonia-banklink' ) );

		// Add inline JS
		wc_enqueue_js( sprintf( 'window.location = "%s";', $selected_method_url ) );

		return $form;
	}

	function gateway_method_js() {
		ob_start();
		?>
		$( 'body' )
			.on( 'click', '.banklink-maksekeskus-selection', function(event) {
				event.preventDefault();

				var $this = $( this );

				$this.siblings().removeClass( 'banklink-maksekeskus-selection--active' );
				$this.addClass( 'banklink-maksekeskus-selection--active' );
				$this.siblings( '.banklink-maksekeskus-selection__value' ).val( $this.data( 'name' ) );
			});
		<?php
		return ob_get_clean();
	}
}