<?php
abstract class WC_Banklink_Maksekeskus extends WC_Banklink {

	/**
	 * WC_Banklink_Maksekeskus
	 */
	function __construct() {
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
		$this->form_fields = array_merge(
			$this->form_fields,
			array(
				'currency'   => array(
					'title'   => __( 'Currency', 'wc-gateway-estonia-banklink' ),
					'type'    => 'select',
					'options' => get_woocommerce_currencies(),
					'default' => get_woocommerce_currency(),
				),
				'shop_id'    => array(
					'title'       => __( 'Shop ID', 'wc-gateway-estonia-banklink' ),
					'type'        => 'text',
					'default'     => '',
					'description' => __( 'This will be provided by Maksekeskus', 'wc-gateway-estonia-banklink' ),
					'desc_tip'    => true,
				),
				'api_url'    => array(
					'title'   => __( 'API URL', 'wc-gateway-estonia-banklink' ),
					'type'    => 'text',
					'default' => 'https://api.maksekeskus.ee/v1/',
				),
				'api_secret' => array(
					'title'       => __( 'API secret', 'wc-gateway-estonia-banklink' ),
					'type'        => 'text',
					'description' => __( 'This will be provided by Maksekeskus', 'wc-gateway-estonia-banklink' ),
					'desc_tip'    => true,
				),
				'locale'     => array(
					'title'       => __( 'Preferred locale', 'wc-gateway-estonia-banklink' ),
					'type'        => 'text',
					'description' => __( 'RFC-2616 format locale', 'wc-gateway-estonia-banklink' ),
					'desc_tip'    => true,
					'default'     => $this->get_default_language(),
				),
				'return_url' => array(
					'title'             => __( 'Return URL', 'wc-gateway-estonia-banklink' ),
					'type'              => 'text',
					'default'           => $this->notify_url,
					'description'       => __( 'URL, where customer is redirected after the payment.', 'wc-gateway-estonia-banklink' ),
					'desc_tip'          => true,
					'custom_attributes' => array(
						'readonly' => 'readonly',
					),
				),
			)
		);
	}

	/**
	 * Transaction URLs that are sent to Maksekeskus
	 *
	 * @return array Return (return_url), Cancel (cancel_url) and Notification (notification_url) URLs
	 */
	function get_transaction_urls() {
		// Return URL
		$return_url = array(
			'url'    => $this->get_option( 'return_url' ),
			'method' => 'POST',
		);

		return array(
			'return_url'       => $return_url,
			'cancel_url'       => $return_url,
			'notification_url' => $return_url,
		);
	}

	/**
	 * Generate response/request signature of MAC fields
	 *
	 * @param  array $fields Fields
	 * @return string         Signature
	 */
	function get_signature( $fields ) {
		return $this->get_signature_from_string( json_encode( $fields, JSON_UNESCAPED_UNICODE ) );
	}

	/**
	 * Signature of a JSON string exactly as it stands
	 *
	 * @param  string $json JSON string.
	 * @return string       Signature
	 */
	function get_signature_from_string( $json ) {
		return strtoupper( hash( 'sha512', $json . $this->get_option( 'api_secret' ) ) );
	}

	/**
	 * Listen for the response from bank
	 *
	 * @return void
	 */
	function check_bank_response() {
		if ( ob_get_level() ) {
			ob_clean();
		}

		$response = ! empty( $_REQUEST ) ? stripslashes_deep( $_REQUEST ) : false;

		// Debug response data
		$this->debug( $response );

		if ( $response && isset( $response['json'], $response['mac'] ) ) {
			header( 'HTTP/1.1 200 OK' );

			// Validate response
			do_action( 'woocommerce_' . $this->id . '_check_response', $response );
		} else {
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
		$order      = $validation['order_id'] ? wc_get_order( absint( $validation['order_id'] ) ) : false;

		// The reference comes off a public URL, and a signature that does not
		// verify leaves it empty. Every branch below reaches into $order, so
		// an order that is not there was a fatal.
		if ( ! $order ) {
			$this->debug( 'Response names an order that does not exist, or did not verify', 'error' );

			wp_safe_redirect( home_url() );

			exit;
		}

		// Payment success
		if ( 'success' === $validation['status'] ) {
			// Get return URL
			$return_url = $this->get_return_url( $order );

			if ( in_array( $order->get_status(), array( 'processing', 'cancelled', 'refunded', 'completed' ), true ) ) {
				// Order already dealt with
			} elseif ( ! $this->response_matches_order( $validation, $order ) ) {
				$order->update_status( 'failed', sprintf( '%s: %s', $this->get_title(), __( 'Payment not made or is not verified.', 'wc-gateway-estonia-banklink' ) ) );
			} else {
				// Payment completed
				$order->add_order_note( sprintf( '%s: %s', $this->get_title(), __( 'Payment completed.', 'wc-gateway-estonia-banklink' ) ) );
				$order->payment_complete( isset( $validation['transaction_id'] ) ? $validation['transaction_id'] : '' );
			}
		}
		// Payment cancelled
		elseif ( 'cancelled' === $validation['status'] ) {
			// Set status to on-hold
			$order->update_status( 'cancelled', sprintf( '%s: %s', $this->get_title(), __( 'Payment cancelled.', 'wc-gateway-estonia-banklink' ) ) );

			// Cancel order URL
			$return_url = $order->get_cancel_order_url();
		}
		// Payment started, waiting
		elseif ( 'received' === $validation['status'] ) {
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

		wp_safe_redirect( $return_url );

		exit;
	}

	/**
	 * Whether a verified response is for the amount of the order.
	 *
	 * The signature says Maksekeskus sent it; it does not say it is about
	 * this order for this money.
	 *
	 * @param array    $validation Parsed response.
	 * @param WC_Order $order      Order.
	 *
	 * @return bool
	 */
	protected function response_matches_order( $validation, $order ) {
		$expected = wc_estonian_gateways_get_order_total( $order );
		$paid     = '' === $validation['amount'] ? '' : number_format( (float) $validation['amount'], 2, '.', '' );

		$matches = ( $expected === $paid );

		if ( ! $matches ) {
			$this->debug(
				sprintf( 'Response does not match order %d: paid %s, expected %s', $order->get_id(), $paid, $expected ),
				'alert'
			);

			$order->add_order_note(
				sprintf(
					/* translators: 1: paid amount, 2: expected amount */
					__( 'Payment response does not match the order: %1$s received, %2$s expected.', 'wc-gateway-estonia-banklink' ),
					$paid,
					$expected
				)
			);
		}

		/**
		 * Whether a verified response is accepted as payment for the order.
		 *
		 * @param bool     $matches    Whether the amount matches.
		 * @param array    $validation Parsed response.
		 * @param WC_Order $order      Order.
		 */
		return (bool) apply_filters( 'woocommerce_' . $this->id . '_response_matches_order', $matches, $validation, $order );
	}

	/**
	 * Validate response from the gateway
	 *
	 * @param  array $request Response
	 * @return void
	 */
	function validate_bank_payment( $response ) {
		// Result failed by default
		$result = array(
			'order_id' => '',
			'amount'   => '',
			'status'   => 'failed',
		);

		if ( ! is_array( $response ) || empty( $response ) || ! isset( $response['json'] ) ) {
			return $result;
		}

		// Get fields from response
		$json_data    = $response['json'];
		$response_mac = isset( $response['mac'] ) ? (string) $response['mac'] : '';

		$message = json_decode( $json_data );

		if ( ! $message ) {
			$message = json_decode( stripslashes( $json_data ) );
		}

		if ( ! $message ) {
			$message = json_decode( htmlspecialchars_decode( $json_data ) );
		}

		if ( ! is_object( $message ) || '' === $response_mac ) {
			return $result;
		}

		// Compare signatures. Maksekeskus signs the JSON string it sent, so
		// that string is what should be hashed; re-encoding what json_decode()
		// produced only happens to match when the round trip is byte for byte,
		// which is what the three decode attempts above are working around.
		// The re-encoded form is still accepted, so shops that have been
		// taking payments on it keep taking them.
		//
		// hash_equals() rather than ==: the comparison is against a value the
		// caller chose, and a plain compare leaks how much of it was right.
		$signature_matches = hash_equals( $this->get_signature_from_string( $json_data ), $response_mac )
			|| hash_equals( $this->get_signature( $message ), $response_mac );

		if ( $signature_matches ) {
			// Get order ID based on version
			if ( isset( $message->reference ) ) {
				$result['order_id'] = $message->reference;
			} elseif ( isset( $message->paymentId ) ) {
				$result['order_id'] = $message->paymentId;
			}

			// Set amount
			$result['amount'] = isset( $message->amount ) ? $message->amount : '';

			// Set transaction ID
			$result['transaction_id'] = isset( $message->transaction ) ? $message->transaction : '';

			switch ( isset( $message->status ) ? $message->status : '' ) {
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
