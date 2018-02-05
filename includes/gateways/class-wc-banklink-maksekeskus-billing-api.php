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
		$this->method_title = __( 'Maksekeskus API (BETA)', 'wc-gateway-estonia-banklink' );

		// Set gateway method meta field name
		$this->selected_method_meta_field = sprintf( '_%s_selected_method', $this->id );

		parent::__construct();

		// Only do the following when this gateway is enambled
		if( $this->enabled !== 'no' ) {
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

	/**
	 * Get compiled API url with method and query data added
	 *
	 * @param  string $method     API method
	 * @param  array  $query_data Query data to be added to URL
	 * @return string             API url
	 */
	public function get_api_url( $method, $query_data = array() ) {
		// Add methods
		if( is_array( $method ) ) {
			$method = implode( '/', $method );
		}

		$api_request_url = trailingslashit( $this->get_option( 'api_url' ) ) . $method;

		return $query_data ? add_query_arg( $query_data, $api_request_url ) : $api_request_url;
	}

	/**
	 * Fetch available payment methods from API or cached transient
	 *
	 * @param  string $customer_country Customer country code
	 * @return array                    Available payment methods
	 */
	public function get_available_payment_methods( $customer_country = null ) {
		// Get customer country if not provided
		if( ! $customer_country ) {
			$customer_country = wc_estonian_gateways_get_customer_billing_country();
		}

		// Methods transient name
		$transient_name = sprintf( '%s_%s_available_methods', $this->id, $customer_country );

		// Try cached data
		if( $payment_methods = get_transient( $transient_name ) ) {
			return $payment_methods;
		}
		else {
			// Only request when Shop ID, API URL and API secret are provided
			if( $this->get_option( 'shop_id' ) && $this->get_option( 'api_url' ) && $this->get_option( 'api_secret' ) ) {
				// Set method query data
				$methods_query    = array(
					'currency' => $this->get_option( 'currency' ),
					'country'  => $customer_country
				);

				// Make a request to API
				$methods_request = $this->request_from_api( $this->get_api_url( 'methods', $methods_query ), 'GET' );

				// Check response
				if( is_object( $methods_request ) && ! empty( $methods_request ) && property_exists( $methods_request, 'banklinks' ) ) {
					set_transient( $transient_name, $methods_request->banklinks, DAY_IN_SECONDS );

					// Return fetched methods
					return $methods_request->banklinks;
				}
			}
		}

		return array();
	}

	/**
	 * Render payment fields
	 *
	 * @return void
	 */
	public function payment_fields() {
		// Get methods
		$methods = $this->get_available_payment_methods();

		// Only render the fields when we have methods
		if( is_array( $methods ) && ! empty( $methods ) ) {
			// Add every method logo
			foreach( $methods as $key => $method ) {
				$methods[$key]->logo = $this->get_maksekeskus_gateway_logo_url( $method->name );
			}

			$current_method = null;

			// Search for user selected method, saved by session
			// in case of ajax has refreshed fragments etc
			if( $selected_method = WC()->session->get( $this->selected_method_meta_field ) ) {
				foreach( $methods as $method ) {
					if( $method->name == $selected_method ) {
						$current_method = $selected_method;
					}
				}
			}

			// If we didn't find user selected method
			// select first method from availables
			if( ! $current_method ) {
				$current_method = reset( $methods )->name;
			}

			// Pass template data through filter
			$template_data = apply_filters( 'woocommerce_' . $this->id . '_gateway_template_data', array(
				'methods'        => $methods,
				'current_method' => $current_method
			) );

			// Get WooCommerce template
			// This can easily be overridden by copying the file to themes folder
			wc_get_template( 'checkout/payment-method-maksekeskus.php', $template_data );
		}
	}

	/**
	 * Get logos from Maksekeskus
	 *
	 * @param  string $gateway Gateway or method name
	 * @return string          Logo URL
	 */
	public function get_maksekeskus_gateway_logo_url( $gateway ) {
		// Pass through filter so devs can hook into it
		return apply_filters( 'woocommerce_' . $this->id . '_gateway_logo_url', sprintf( 'https://static.maksekeskus.ee/img/channel/lnd/%s.png', $gateway ), $gateway );
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

	/**
	 * Saves selected method in session whilst template is requested (just before it)
	 *
	 * @param  string $posted Posted data
	 *
	 * @return void
	 */
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

		// Prepare transaction data
		$transaction_data   = array(
			// Order information
			'transaction' => array(
				'amount'          => wc_estonian_gateways_get_order_total( $order ),
				'currency'        => wc_estonian_gateways_get_order_currency( $order ),
				'reference'       => wc_estonian_gateways_get_order_id( $order ),
				'transaction_url' => $this->get_transaction_urls()
			),
			// Set customer data
			'customer'    => array(
				'email'     => wc_estonian_gateways_get_order_billing_email( $order ),
				'country'   => wc_estonian_gateways_get_customer_billing_country(),
				'locale'    => $this->get_option( 'locale' ),
				'ip'        => wc_estonian_gateways_get_customer_ip_address( $order )
			)
		);

		// Pass through filter and send the request to API
		$transaction_data    = $this->hookable_transaction_data( $transaction_data, $order );
		$transaction_request = $this->request_from_api( $this->get_api_url( 'transactions' ), 'POST', $transaction_data );

		// If went OK, we should pass this
		if( $transaction_request ) {
			// Set transaction ID to order
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

	/**
	 * Compiles the redirection form to Banklink
	 *
	 * @param  string $url    Gateway method URL with transaction ID in it
	 * @param  array  $fields Fields that should be passed to the form
	 * @return string         HTML form
	 */
	function get_redirect_form( $url, $fields = array() ) {
		// Add form
		$form = sprintf( '<form action="%s" method="get" id="banklink_%s_submit_form"><button class="button submit">%s</button></form>', esc_attr( $url ), $this->id, __( 'Pay', 'wc-gateway-estonia-banklink' ) );

		// Add inline JS
		wc_enqueue_js( sprintf( 'window.location = "%s";', $url ) );

		return $form;
	}

	/**
	 * JS for activating and passing proper data for user method selection
	 *
	 * @return string JS with jQuery
	 */
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

	/**
	 * Sends a request to Maksekeskus' API with credentials and other information
	 *
	 * @param  string $url    URL to be requested
	 * @param  string $method HTTP method (POST, PUT, GET etc)
	 * @param  array  $body   Data that will be sent as body to the request. It will be JSON encoded before submittance.
	 * @return mixed          Null if the request did not succeed, StdObject when request succeeded
	 */
	private function request_from_api( $url, $method = 'POST', $body = array() ) {
		// Set request args
		$args = array(
			'headers' => array(
				'Authorization' => sprintf( 'Basic %s', base64_encode( sprintf( '%s:%s', $this->get_option( 'shop_id' ), $this->get_option( 'api_secret' ) ) ) ),
				'Content-Type'  => 'application/json'
			)
		);

		// Add JSON encoded data to body
		if( ! empty( $body ) ) {
			$args['body'] = json_encode( $body );
		}

		// Do the request
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
}