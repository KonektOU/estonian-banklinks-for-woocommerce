<?php
class WC_Banklink_Estcard_Gateway extends WC_Banklink {


	/**
	 * WC_Banklink_Estcard_Gateway
	 */
	function __construct() {
		$this->id           = 'estcard';
		$this->method_title = __( 'Estcard', 'wc-gateway-estonia-banklink' );

		parent::__construct();
	}

	/**
	 * Set settings fields
	 *
	 * @return void
	 */
	function init_form_fields() {
		parent::init_form_fields();

		// Set fields
		$this->form_fields = array_merge(
			$this->form_fields,
			array(
				'destination_url'  => array(
					'title'   => __( 'Destination URL', 'wc-gateway-estonia-banklink' ),
					'type'    => 'text',
					'default' => '',
				),
				'merchant_id'      => array(
					'title'   => __( 'Account ID', 'wc-gateway-estonia-banklink' ),
					'type'    => 'text',
					'default' => '',
				),
				'private_key'      => array(
					'title'   => __( 'Your Private Key', 'wc-gateway-estonia-banklink' ),
					'type'    => 'textarea',
					'default' => '',
				),
				'private_key_pass' => array(
					'title'   => __( 'Private Key Password', 'wc-gateway-estonia-banklink' ),
					'type'    => 'text',
					'default' => '',
				),
				'public_key'       => array(
					'title'   => __( 'Bank`s Public Key', 'wc-gateway-estonia-banklink' ),
					'type'    => 'textarea',
					'default' => '',
				),
				'lang'             => array(
					'title'       => __( 'Default language', 'wc-gateway-estonia-banklink' ),
					'type'        => 'text',
					'default'     => $this->get_default_language(),
					'description' => __( 'Default UI language locale sent to the bank. Currently supported: et, en, fi, de. Defaults to et.', 'wc-gateway-estonia-banklink' ),
					'desc_tip'    => true,
				),
			)
		);
	}

	/**
	 * Create form for bank
	 *
	 * @param  integer $order_id Order ID
	 * @return string            HTML form
	 */
	function output_gateway_redirection_form( $order_id ) {
		// Get the order
		$order = wc_get_order( $order_id );

		// Get language code accepted by Nets Estonia
		$lang = $this->get_option( 'lang' );

		if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
			$lang = ICL_LANGUAGE_CODE; // WPML
		} elseif ( function_exists( 'qtrans_getLanguage' ) ) {
			$lang = qtrans_getLanguage(); // qtranslate
		}

		// All accepted language codes
		$accepted_lang_codes = array( 'et', 'en', 'fi', 'de' );

		// Current language code
		$lang_code = in_array( $lang, $accepted_lang_codes ) ? $lang : 'en';

		// Set MAC fields
		$mac_fields = array(
			'action'         => 'gaf',
			'ver'            => '004',
			'id'             => $this->get_option( 'merchant_id' ),
			'ecuno'          => $this->generate_unique_ecuno( wc_estonian_gateways_get_order_id( $order ) ),
			'eamount'        => sprintf( '%012d', wc_estonian_gateways_get_order_total( $order ) * 100 ),
			'additionalinfo' => sprintf( __( 'Order nr: %s', 'wc-gateway-estonia-banklink' ), wc_estonian_gateways_get_order_id( $order ) ),
			'cur'            => get_woocommerce_currency(),
			'datetime'       => date( 'YmdHis' ),
			'feedBackUrl'    => $this->notify_url,
			'delivery'       => 'S',
			'lang'           => $lang_code,
			'charEncoding'   => 'utf-8',
		);

		// Allow hooking into the data
		$mac_fields = $this->hookable_transaction_data( $mac_fields, $order );

		$key        = openssl_pkey_get_private( $this->get_option( 'private_key' ), $this->get_option( 'private_key_pass' ) );
		$signature  = '';
		$mac_string = $this->generate_mac_string( $mac_fields );

		// Try to sign the macstring
		if ( ! openssl_sign( $mac_string, $signature, $key, OPENSSL_ALGO_SHA1 ) ) {
			$this->debug( 'Unable to generate signature', 'emergency' );

			die( 'Unable to generate signature' );
		}

		// Add signature
		$mac_fields['mac'] = bin2hex( $signature );

		// Return form
		return $this->get_redirect_form( $this->get_option( 'destination_url' ), $mac_fields );
	}

	/**
	 * Generates MAC string as needed according to the service number
	 *
	 * @param  array $macFields MAC fields
	 * @return string            MAC string
	 */
	function generate_mac_string( $fields ) {
		$data = false;

		if ( $fields['action'] == 'gaf' ) {
			$data = $this->mb_str_pad( $fields['ver'], 3, '0', STR_PAD_LEFT, 'utf-8' ) .
			  $this->mb_str_pad( $fields['id'], 10, ' ', STR_PAD_RIGHT, 'utf-8' ) .
			  $this->mb_str_pad( $fields['ecuno'], 12, '0', STR_PAD_LEFT, 'utf-8' ) .
			  $this->mb_str_pad( $fields['eamount'], 12, '0', STR_PAD_LEFT, 'utf-8' ) .
			  $this->mb_str_pad( $fields['cur'], 3, ' ', STR_PAD_RIGHT, 'utf-8' ) .
			  $this->mb_str_pad( $fields['datetime'], 14, ' ', STR_PAD_RIGHT, 'utf-8' ) .
			  $this->mb_str_pad( $fields['feedBackUrl'], 128, ' ', STR_PAD_RIGHT, 'utf-8' ) .
			  $this->mb_str_pad( $fields['delivery'], 1, ' ', STR_PAD_RIGHT, 'utf-8' ) .
			  $this->mb_str_pad( $fields['additionalinfo'], 128, ' ', STR_PAD_RIGHT, 'utf-8' );
		} elseif ( $fields['action'] == 'afb' ) {
			$data = $this->mb_str_pad( $fields['ver'], 3, '0', STR_PAD_LEFT, 'utf-8' ) .
					$this->mb_str_pad( $fields['id'], 10, ' ', STR_PAD_RIGHT, 'utf-8' ) .
					$this->mb_str_pad( $fields['ecuno'], 12, '0', STR_PAD_LEFT, 'utf-8' ) .
					$this->mb_str_pad( $fields['receipt_no'], 6, '0', STR_PAD_LEFT, 'utf-8' ) .
					$this->mb_str_pad( $fields['eamount'], 12, '0', STR_PAD_LEFT, 'utf-8' ) .
					$this->mb_str_pad( $fields['cur'], 3, ' ', STR_PAD_RIGHT, 'utf-8' ) .
					$this->mb_str_pad( $fields['respcode'], 3, '0', STR_PAD_LEFT, 'utf-8' ) .
					$this->mb_str_pad( $fields['datetime'], 14, ' ', STR_PAD_RIGHT, 'utf-8' ) .
					$this->mb_str_pad( $fields['msgdata'], 40, ' ', STR_PAD_RIGHT, 'utf-8' ) .
					$this->mb_str_pad( $fields['actiontext'], 40, ' ', STR_PAD_RIGHT, 'utf-8' );
		}

		return $data;
	}

	/**
	 * Padding with multibyte support
	 *
	 * @param  string   $input      Input
	 * @param  int      $pad_length Padding length
	 * @param  string   $pad_string Paddable string
	 * @param  constant $pad_type   Type
	 * @param  string   $encoding   Encoding
	 * @return string               Padded input
	 */
	function mb_str_pad( $input, $pad_length, $pad_string = ' ', $pad_type = STR_PAD_RIGHT, $encoding = null ) {
		if ( ! $encoding ) {
			$diff = strlen( $input ) - mb_strlen( $input );
		} else {
			$diff = strlen( $input ) - mb_strlen( $input, $encoding );
		}

		return str_pad( $input, $pad_length + $diff, $pad_string, $pad_type );
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

		if ( $response && isset( $response['ecuno'] ) ) {
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
	 * @param  array $response Response
	 * @return void
	 */
	function validate_bank_response( $response ) {
		$validation = $this->validate_bank_payment( $response );
		$order_id   = $this->get_order_id_by_ecuno_value( $response['ecuno'] );
		$order      = wc_get_order( $order_id );
		$return_url = $this->get_return_url( $order );

		if ( $order ) {
			// Check validation
			if ( isset( $validation['status'] ) && $validation['status'] == 'success' ) {
				// Payment completed
				$order->add_order_note( sprintf( '%s: %s', $this->get_title(), __( 'Payment completed.', 'wc-gateway-estonia-banklink' ) ) );
				$order->payment_complete();
			} else {
				// Set status to failed
				$order->update_status( 'failed', sprintf( '%s: %s', $this->get_title(), __( 'Payment not made or is not verified.', 'wc-gateway-estonia-banklink' ) ) );
			}
		}

		// Redirect to order details
		if ( isset( $response['auto'] ) && $response['auto'] == 'N' ) {
			wp_redirect( $return_url );
		}

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
		$result = array(
			'status' => 'failed',
		);

		if ( ! is_array( $response ) || empty( $response ) || ! isset( $response['ecuno'] ) ) {
			return $result;
		}

		// Generate mac string and verify signature
		$mac_string   = $this->generate_mac_string( $response );
		$verification = openssl_verify( $mac_string, pack( 'H*', $response['mac'] ), $this->get_option( 'public_key' ), OPENSSL_ALGO_SHA1 );

		// Check signature verification
		if ( $verification === 1 ) {
			switch ( $response['respcode'] ) {
				// Paid
				case '000':
					$result['status'] = 'success';
					break;

				default:
					// Nothing by default
					break;
			}
		}

		return $result;
	}

	/**
	 * Search for existing postmeta value for the key "_ecuno", which has to be unique
	 *
	 * @param  string $ecuno Unique transaction identifier
	 * @return int|bool        WP_Post ID or false when none found
	 */
	function get_order_id_by_ecuno_value( $ecuno ) {
		global $wpdb;

		return $wpdb->get_var(
			$wpdb->prepare(
				"
			SELECT postmeta.post_id
			FROM $wpdb->postmeta AS postmeta
			WHERE postmeta.meta_key = '_ecuno' AND postmeta.meta_value = '%s'
			LIMIT 1
		",
				$ecuno
			)
		);
	}

	/**
	 * Generate a new ecuno for order and store it in database. There can be only one per order.
	 *
	 * @param  int $order_id WC_Order ID
	 * @return string|bool           Transaction identifier or false on failure
	 */
	function generate_unique_ecuno( $order_id ) {
		$tries = 0;
		$date  = date( 'Ym' );

		// we don't expect to exceed 1, but need a limit
		while ( $tries < 50 ) {
			// new random - does NOT need to be cryptographically secure
			$rand    = rand( 100000, 999999 );
			$ecuno   = $date . $rand;
			$post_id = $this->get_order_id_by_ecuno_value( $ecuno );

			if ( ! $post_id ) {
				$order = wc_get_order( $order_id );
				$order->update_meta_data( '_ecuno', $ecuno );
				$order->save_meta_data();

				return $ecuno;
			}

			$tries++;
		}

		$this->debug( 'Could not generate a unique ecuno for Estcard payment transaction', 'emergency' );

		return false;
	}
}
