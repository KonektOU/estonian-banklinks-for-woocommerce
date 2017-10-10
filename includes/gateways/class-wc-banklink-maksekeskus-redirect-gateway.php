<?php
class WC_Banklink_Maksekeskus_Redirect_Gateway extends WC_Banklink {

	/**
	 * WC_Banklink_Maksekeskus_Redirect_Gateway
	 */
	function __construct() {
		$this->id           = 'maksekeskus_redirect';
		$this->method_title = __( 'Maksekeskus', 'wc-gateway-estonia-banklink' );

		parent::__construct();
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
			'destination_url' => array(
				'title'       => __( 'Destination URL', 'wc-gateway-estonia-banklink' ),
				'type'        => 'text',
				'default'     => 'https://payment.maksekeskus.ee/pay/1/signed.html',
				'description' => __( 'URL, where customer is redirected to start the payment.', 'wc-gateway-estonia-banklink' ),
				'desc_tip'    => TRUE
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

	/**
	 * Create form for bank
	 *
	 * @param  integer $order_id Order ID
	 * @return string            HTML form
	 */
	function generate_submit_form( $order_id ) {
		// Get the order
		$order       = wc_get_order( $order_id );

		// Request
		$request     = array(
			'shop'      => $this->get_option( 'shop_id' ),
			'amount'    => round( $order->get_total(), 2 ),
			'reference' => wc_estonian_gateways_get_order_id( $order ),
			'country'   => wc_estonian_gateways_get_customer_billing_country( $order ),
			'locale'    => $this->get_option( 'locale' )
		);

		// Generate MAC code
		$mac_code    = $this->get_signature( $request );

		// Form fields
		$form_fields = array(
			'json' => json_encode( $request, JSON_UNESCAPED_UNICODE ),
			'mac'  => $mac_code
		);

		// Start form
		$post = '<form action="'. htmlspecialchars( $this->get_option( 'destination_url' ) ) .'" method="post" id="banklink_'. $this->id .'_submit_form">';

		// Add other data as hidden fields
		foreach ( $form_fields as $name => $value ) {
			$post .= '<input type="hidden" name="'. esc_attr( $name ) .'" value="'. esc_attr( $value ) .'">';
		}

		// Show "Pay" button and end the form
		$post .= '<input type="submit" name="send_banklink" class="button" value="'. __( 'Pay', 'wc-gateway-estonia-banklink' ) .'"/>';
		$post .= "</form>";

		// Debug output
		$this->debug( $form_fields );

		// Add inline JS
		wc_enqueue_js( 'jQuery( "#banklink_'. $this->id .'_submit_form" ).submit();' );

		// Output form
		return $post;
	}

	/**
	 * Generate response/request signature of MAC fields
	 *
	 * @param  array  $fields Fields
	 * @return string         Signature
	 */
	private function get_signature( $fields ) {
		return strtoupper( hash( 'sha512', json_encode( $fields, JSON_UNESCAPED_UNICODE ) . $this->get_option( 'api_secret' ) ) );
	}

	/**
	 * Listen for the response from bank
	 *
	 * @return void
	 */
	function check_bank_response() {
		@ob_clean();

		$response = ! empty( $_REQUEST ) ? stripslashes_deep( $_REQUEST ) : false;

		// Debug response data
		$this->debug( $response );

		if ( $response && isset( $response['json'] ) ) {
			header( 'HTTP/1.1 200 OK' );

			// Validate response
			do_action( 'woocommerce_'. $this->id .'_check_response', $response );
		}
		else {
			wp_die( 'Response failed', $this->get_title(), array( 'response' => 200 ) );
		}
	}
	/**
	 * Validate response from the bank
	 *
	 * @param  array $request Response
	 * @return void
	 */
	function validate_bank_response( $response ) {
		// Try to validate the response
		$validation = $this->validate_bank_payment( $response );
		$order      = wc_get_order( $validation['order_id'] );

		// Payment success
		if ( $validation['status'] == 'success' ) {
			// Get return URL
			$return_url = $this->get_return_url( $order );

			if ( in_array( $order->get_status(), array( 'processing', 'cancelled', 'refunded', 'completed' ) ) ) {
				// Order already dealt with
			}
			else {
				// Payment completed
				$order->add_order_note( sprintf( '%s: %s', $this->get_title(), __( 'Payment completed.', 'wc-gateway-estonia-banklink' ) ) );
				$order->payment_complete();
			}
		}
		// Payment cancelled
		elseif ( $validation['status'] == 'cancelled' ) {
			// Set status to on-hold
			$order->update_status( 'cancelled', sprintf( '%s: %s', $this->get_title(), __( 'Payment cancelled.', 'wc-gateway-estonia-banklink' ) ) );

			// Cancel order URL
			$return_url = $order->get_cancel_order_url();
		}
		// Payment started, waiting
		elseif ( $validation['status'] == 'received' ) {
			// Set status to on-hold
			$order->update_status( 'on-hold', sprintf( '%s: %s', $this->get_title(), __( 'Payment not made or is not verified.', 'wc-gateway-estonia-banklink' ) ) );

			// Go back to pay
			$return_url = $order->get_checkout_payment_url();
		}
		// Validation failed
		else {
			// Not verified signature, go home
			$return_url = home_url();
		}

		wp_redirect( $return_url );

		exit;
	}

	/**
	 * Validate response from the gateway
	 *
	 * @param  array $request Response
	 * @return void
	 */
	function validate_bank_payment( $response ) {
		// Result failed by default
		$result    = array(
			'order_id' => '',
			'amount'   => '',
			'status'   => 'failed'
		);

		if ( ! is_array( $response ) || empty( $response ) || ! isset( $response['json'] ) ) {
			return $result;
		}

		// Get fields from response
		$json_data    = $response['json'];
		$response_mac = $response['mac'];

		$message   = @json_decode( $json_data );

		if ( ! $message ) {
			$message = @json_decode( stripslashes( $json_data ) );
		}

		if ( ! $message ) {
			$message = @json_decode( htmlspecialchars_decode( $json_data ) );
		}

		// Compare signatures
		if ( $this->get_signature( $message ) == $response_mac ) {
			// Get order ID based on version
			if( isset( $message->reference ) ) {
				$result['order_id'] = $message->reference;
			}
			else {
				$result['order_id'] = $message->paymentId;
			}

			// Set amount
			$result['amount'] = $message->amount;

			switch( $message->status ) {
				// Payment started, but not paid
				case 'RECEIVED':
				case 'CREATED':
					$result['status'] = 'received';
				break;

				// Paid
				case 'PAID':
				case 'COMPLETED':
					$result['status'] = 'success';
				break;

				// Cancelled or paid
				case 'CANCELLED':
				case 'EXPIRED':
					$result['status'] = 'cancelled';
				break;

				default:
					// Nothing by default
				break;
			}
		}

		return $result;
	}
}
