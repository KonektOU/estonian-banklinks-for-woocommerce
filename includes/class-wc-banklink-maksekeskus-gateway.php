<?php
class WC_Banklink_Maksekeskus_Gateway extends WC_Banklink {
	private $request_variable_order = array( 'shopId', 'paymentId', 'amount' );
	private $response_variable_order = array( 'paymentId', 'amount', 'status' );

	function __construct() {
		$this->id           = 'maksekeskus_redirect';
		$this->title        = __( 'Maksekeskus', 'wc-gateway-estonia-banklink' );
		$this->method_title = $this->get_title();

		parent::__construct();
	}


	/**
	 * Set settings fields
	 *
	 * @return void
	 */
	function init_form_fields() {
		// Set fields
		$this->form_fields = array(
			'enabled'         => array(
				'title'       => __( 'Enable Maksekeskus', 'wc-gateway-estonia-banklink' ),
				'type'        => 'checkbox',
				'default'     => 'no',
				'label'       => __( 'Enable this payment gateway', 'wc-gateway-estonia-banklink' )
			),
			'currency'        => array(
				'title'       => __( 'Currency', 'wc-gateway-estonia-banklink' ),
				'type'        => 'select',
				'options'     => get_woocommerce_currencies(),
				'default'     => get_woocommerce_currency()
			),
			'title'           => array(
				'title'       => __( 'Title', 'wc-gateway-estonia-banklink' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which user sees during checkout.', 'wc-gateway-estonia-banklink' ),
				'default'     => $this->get_title(),
				'desc_tip'    => TRUE
			),
			'description'     => array(
				'title'       => __( 'Customer message', 'wc-gateway-estonia-banklink' ),
				'type'        => 'textarea',
				'default'     => '',
				'description' => __( 'This will be visible when user selects this payment gateway during checkout.', 'wc-gateway-estonia-banklink' ),
				'desc_tip'    => TRUE
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
				'default'     => 'et'
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
		);
	}

	/**
	 * Create form for bank
	 * @param  integer $order_id Order ID
	 * @return string            HTML form
	 */
	function generate_submit_form( $order_id ) {
		// Get the order
		$order      = wc_get_order( $order_id );

		$request    = array(
			'shopId'    => $this->get_option( 'shop_id' ),
			'paymentId' => $order->id,
			'amount'    => round( $order->get_total(), 2 )
		);

		$request['signature'] = $this->get_request_signature( $request );

		$macFields = array(
			'json'   => json_encode( $request ),
			'locale' => $this->get_option( 'locale' )
		);

		// Start form
		$post = '<form action="'. htmlspecialchars( $this->get_option( 'destination_url' ) ) .'" method="post" id="banklink_'. $this->id .'_submit_form">';

		foreach( $macFields as $name => $value ) {
			$post .= '<input type="hidden" name="'. esc_attr( $name ) .'" value="'. esc_attr( $value ) .'">';
		}

		// Show "Pay" button and end the form
		$post .= '<input type="submit" name="send_banklink" class="button" value="'. __( 'Pay', 'wc-gateway-estonia-banklink' ) .'"/>';
		$post .= "</form>";

		// Add inline JS
		wc_enqueue_js( 'jQuery( "#banklink_'. $this->id .'_submit_form" ).submit();' );

		// Output form
		return $post;
	}

	private function get_request_signature( $request ) {
		return $this->get_signature( $request, 'request' );
	}

	private function get_response_signature( $response ) {
		return $this->get_signature( $response, 'response' );
	}

	private function get_signature( $fields, $type ) {
		$signature = '';
		$fields    = (array) $fields;
		$variables = $type == 'request' ? $this->request_variable_order : $this->response_variable_order;

		foreach( $variables as $variable ) {
			$signature .= $fields[ $variable ];
		}

		return strtoupper( hash( 'sha512', $signature . $this->get_option( 'api_secret' ) ) );
	}

	/**
	 * Listen for the response from bank
	 * @return void
	 */
	function check_bank_response() {
		@ob_clean();

		$response = ! empty( $_REQUEST ) ? stripslashes_deep( $_REQUEST ) : false;

		if( $response && isset( $response['json'] ) ) {
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
	 * @param  array $request Response
	 * @return void
	 */
	function validate_bank_response( $response ) {
		$validation = $this->validate_bank_payment( $response );
		$order      = wc_get_order( $validation['data'] );

		if( $validation['status'] == 'success' ) {
			// Get return URL
			$return_url = $this->get_return_url( $order );

			if( in_array( $order->get_status(), array( 'processing', 'cancelled', 'refunded', 'completed' ) ) ) {
				// Order already dealt with
			}
			else {
				// Payment completed
				$order->add_order_note( $this->get_title() . ': ' . __( 'Payment completed.', 'wc-gateway-estonia-banklink' ) );
				$order->payment_complete();
			}
		}
		elseif( $validation['status'] == 'cancelled' ) {
			// Set status to on-hold
			$order->update_status( 'cancelled', $this->get_title() . ': ' . __( 'Payment cancelled.', 'wc-gateway-estonia-banklink' ) );

			// Cancel order URL
			$return_url = $order->get_cancel_order_url();
		}
		elseif( $validation['status'] == 'received' ) {
			// Set status to on-hold
			$order->update_status( 'on-hold', $this->get_title() . ': ' . __( 'Payment not made or is not verified.', 'wc-gateway-estonia-banklink' ) );

			// Go back to pay
			$return_url = $order->get_checkout_payment_url();
		}
		else {
			// Not verified signature, go home
			$return_url = home_url();
		}

		wp_redirect( $return_url );

		exit;
	}

	/**
	 * Validate response from the gateway
	 * @param  array $request Response
	 * @return void
	 */
	function validate_bank_payment( $response ) {
		$result    = array(
			'data'   => '',
			'amount' => '',
			'status' => 'failed'
		);

		if( ! is_array( $response ) || empty( $response ) || ! isset( $response['json'] ) ) {
			return $result;
		}

		$macFields = $response['json'];

		$message   = @json_decode( $macFields );

		if( ! $message ) {
			$message = @json_decode( stripslashes( $macFields ) );
		}

		if( ! $message ) {
			$message = @json_decode( htmlspecialchars_decode( $macFields ) );
		}

		if( ! $message || ! isset( $message->signature ) || ! $message->signature ) {
			return $result;
		}

		$response_signature = $message->signature;

		if( $this->get_response_signature( $message ) == $response_signature ) {
			switch( $message->status ) {
				case 'RECEIVED':
					$result['status'] = 'received';
					$result['data']   = $message->paymentId;
					$result['amount'] = $message->amount;
				break;

				case 'PAID':
					$result['status'] = 'success';
					$result['data']   = $message->paymentId;
					$result['amount'] = $message->amount;
				break;

				case 'CANCELLED':
				case 'EXPIRED':
					$result['status'] = 'cancelled';
					$result['data']   = $message->paymentId;
				break;

				default:
					// Nothing by default
				break;
			}
		}

		return $result;
	}
}