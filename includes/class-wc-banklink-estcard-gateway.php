<?php
class WC_Banklink_Estcard_Gateway extends WC_Banklink {
	private $ecuno_prefix   = 100000;

	private $variable_order = array(
		004 => array( 'ver', 'id', 'ecuno', 'eamount', 'cur', 'datetime', 'feedBackUrl', 'delivery' )
	);

	function __construct() {
		$this->id    = 'estcard';
		$this->title = __( 'Estcard', 'wc-gateway-estonia-banklink' );

		parent::__construct();
	}

	/**
	 * Set settings fields
	 *
	 * @return void
	 */
	function init_form_fields() {

		// prepare locale info
		$locale = get_locale();
		if ( strlen( $locale ) > 2 )
			$locale = substr( $locale, 0, 2 );

		// Set fields
		$this->form_fields = array(
			'enabled'         => array(
				'title'       => __( 'Enable banklink', 'wc-gateway-estonia-banklink' ),
				'type'        => 'checkbox',
				'default'     => 'no',
				'label'       => __( 'Enable this payment gateway', 'wc-gateway-estonia-banklink' )
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
			'destination_url' => array(
				'title'       => __( 'Destination URL', 'wc-gateway-estonia-banklink' ),
				'type'        => 'text',
				'default'     => ''
			),
			'merchant_id'     => array(
				'title'       => __( 'Account ID', 'wc-gateway-estonia-banklink' ),
				'type'        => 'text',
				'default'     => ''
			),
			'private_key'     => array(
				'title'       => __( 'Your Private Key', 'wc-gateway-estonia-banklink' ),
				'type'        => 'textarea',
				'default'     => ''
			),
			'private_key_pass'=> array(
				'title'       => __( 'Private Key Password', 'wc-gateway-estonia-banklink' ),
				'type'        => 'text',
				'default'     => ''
			),
			'public_key'      => array(
				'title'       => __( 'Bank`s Public Key', 'wc-gateway-estonia-banklink' ),
				'type'        => 'textarea',
				'default'     => ''
			),
			'lang'            => array(
				'title'       => __( 'Default language', 'wc-gateway-estonia-banklink' ),
				'type'        => 'text',
				'default'     => $locale,
				'description' => __( 'Default UI language locale sent to the bank. Currently supported: et, en, fi, de. Defaults to et.', 'wc-gateway-estonia-banklink' ),
				'desc_tip'    => TRUE
			),
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

		// Set MAC fields
		$macFields  = array(
			'action'       => 'gaf',
			'ver'          => 004,
			'id'           => sprintf( '%-10s', $this->get_option( 'merchant_id' ) ),
			'ecuno'        => sprintf( '%012s', date( 'Ym' ) . ( $this->ecuno_prefix + $order->id ) ), // min. 100000
			'eamount'      => sprintf( '%012s', ( round( $order->get_total(), 2 ) * 100 ) ), // in cents
			'cur'          => get_woocommerce_currency(),
			'datetime'     => date( 'YmdHis' ),
			'lang'         => $this->get_option( 'lang' ),
			'charEncoding' => 'UTF-8',
			'feedBackUrl'  => sprintf( '%-128s', $this->notify_url ),
			'delivery'     => 'S',
		);

		$key        = openssl_pkey_get_private( $this->get_option( 'private_key' ), $this->get_option( 'private_key_pass' ) );
		$signature  = '';
		$macString  = $this->generate_mac_string( $macFields );

		// Try to sign the macstring
		if ( ! openssl_sign( $macString, $signature, $key, OPENSSL_ALGO_SHA1 ) ) {
			die( "Unable to generate signature" );
		}

		$macFields['mac'] = bin2hex( $signature );

		// Start form
		$post = '<form action="'. htmlspecialchars( $this->get_option( 'destination_url' ) ) .'" method="post" id="banklink_'. $this->id .'_submit_form">';

		// Add other data as hidden fields
		foreach( $macFields as $name => $value ) {
			$post .= '<input type="hidden" name="'. esc_attr( $name ) .'" value="'. esc_attr( $value ) .'">';
		}

		// Show "Pay" button and end the form
		$post .= '<input type="submit" name="send_banklink" class="button" value="'. __( 'Pay', 'wc-gateway-estonia-banklink' ) .'"/>';
		$post .= "</form>";

		// Add inline JS
		wc_enqueue_js( 'jQuery( "#banklink_'. $this->id .'_submit_form" ).submit();' );

		// Return form
		return $post;
	}

	/**
	 * Generates MAC string as needed according to the service number
	 *
	 * @param  array  $macFields MAC fields
	 * @return string            MAC string
	 */
	function generate_mac_string( $fields ) {
		$version = $fields['ver'];

		if( ! isset( $this->variable_order[ $version ] ) ) return FALSE;

		// Data holder
		$data    = '';

		foreach( $this->variable_order[ $version ] as $var ) {
			$data .= $fields[ $var ];
		}

		return $data;
	}

	/**
	 * Listen for the response from bank
	 * @return void
	 */
	function check_bank_response() {
		@ob_clean();

		$response = ! empty( $_REQUEST ) ? stripslashes_deep( $_REQUEST ) : false;

		if( $response && isset( $response['ecuno'] ) ) {
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
		$order_id   = substr( $response['ecuno'], 0, strlen( $this->ecuno_prefix ) + 6 );
		$order      = wc_get_order( $order );
		$return_url = $this->get_return_url( $order );
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
			'data'   => '',
			'amount' => '',
			'status' => 'failed'
		);

		if( ! is_array( $response ) || empty( $response ) || ! isset( $response['json'] ) ) {
			return $result;
		}

		// Get MAC fields from response
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

		// Compare signatures
		if( $this->get_response_signature( $message ) == $response_signature ) {
			switch( $message->status ) {
				// Payment started, but not paid
				case 'RECEIVED':
					$result['status'] = 'received';
					$result['data']   = $message->paymentId;
					$result['amount'] = $message->amount;
				break;

				// Paid
				case 'PAID':
					$result['status'] = 'success';
					$result['data']   = $message->paymentId;
					$result['amount'] = $message->amount;
				break;

				// Cancelled or paid
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