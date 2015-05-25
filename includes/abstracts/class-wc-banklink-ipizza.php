<?php
abstract class WC_Banklink_Ipizza extends WC_Banklink {
	/**
	 * Variables for different iPizza requests
	 *
	 * @var array
	 */
	private $variable_order = array(
		1011 => array( 'VK_SERVICE', 'VK_VERSION', 'VK_SND_ID', 'VK_STAMP', 'VK_AMOUNT', 'VK_CURR', 'VK_ACC', 'VK_NAME', 'VK_REF', 'VK_MSG', 'VK_RETURN', 'VK_CANCEL', 'VK_DATETIME' ),
		1012 => array( 'VK_SERVICE', 'VK_VERSION', 'VK_SND_ID', 'VK_STAMP', 'VK_AMOUNT', 'VK_CURR', 'VK_REF', 'VK_MSG', 'VK_RETURN', 'VK_CANCEL', 'VK_DATETIME' ),
		1111 => array( 'VK_SERVICE', 'VK_VERSION', 'VK_SND_ID', 'VK_REC_ID', 'VK_STAMP', 'VK_T_NO', 'VK_AMOUNT', 'VK_CURR', 'VK_REC_ACC', 'VK_REC_NAME', 'VK_SND_ACC', 'VK_SND_NAME', 'VK_REF', 'VK_MSG', 'VK_T_DATETIME' ),
		1911 => array( 'VK_SERVICE', 'VK_VERSION', 'VK_SND_ID', 'VK_REC_ID', 'VK_STAMP', 'VK_REF', 'VK_MSG' )
	);

	/**
	 * Languagecodes
	 *
	 * @var array
	 */
	private $lang_codes = array( 'et' => 'EST', 'en' => 'ENG', 'ru' => 'RUS' );

	/**
	 * WC_Banklink_Ipizza
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
		$this->form_fields = array_merge( $this->form_fields, array(
			'vk_dest'         => array(
				'title'       => __( 'Request URL', 'wc-gateway-estonia-banklink' ),
				'type'        => 'text',
				'default'     => '',
				'description' => 'VK_DEST',
				'desc_tip'    => TRUE
			),
			'vk_snd_id'       => array(
				'title'       => __( 'Account ID', 'wc-gateway-estonia-banklink' ),
				'type'        => 'text',
				'default'     => '',
				'description' => 'VK_SND_ID',
				'desc_tip'    => TRUE
			),
			'vk_privkey'      => array(
				'title'       => __( 'Your Private Key', 'wc-gateway-estonia-banklink' ),
				'type'        => 'textarea',
				'default'     => ''
			),
			'vk_pass'         => array(
				'title'       => __( 'Private Key Password', 'wc-gateway-estonia-banklink' ),
				'type'        => 'text',
				'default'     => ''
			),
			'vk_pubkey'       => array(
				'title'       => __( 'Bank`s Public Key', 'wc-gateway-estonia-banklink' ),
				'type'        => 'textarea',
				'default'     => ''
			),
			'vk_lang'         => array(
				'title'       => __( 'Default language', 'wc-gateway-estonia-banklink' ),
				'type'        => 'text',
				'default'     => $this->get_default_language(),
				'description' => __( 'Default UI language locale sent to the bank. Currently supported: et, en, ru. Defaults to et.', 'wc-gateway-estonia-banklink' ),
				'desc_tip'    => TRUE
			),
		) );
	}

	/**
	 * Generates MAC string as needed according to the service number
	 *
	 * @param  array  $macFields MAC fields
	 * @return string            MAC string
	 */
	function generate_mac_string( $macFields ) {
		// Get service number
		$serviceNumber = $macFields[ 'VK_SERVICE' ];

		// Data holder
		$data          = '';

		// Append data as needed
		foreach ( $this->variable_order[ $serviceNumber ] as $key ) {
			$value     = $macFields[ $key ];
			$data      .= str_pad( mb_strlen( $value ), 3, '0', STR_PAD_LEFT ) . $value;
		}

		// Return data
		return $data;
	}

	/**
	 * Listen for the response from bank
	 *
	 * @return void
	 */
	function check_bank_response() {
		@ob_clean();

		$response = ! empty( $_REQUEST ) ? $_REQUEST : false;

		if( $response && isset( $response['VK_STAMP'] ) ) {
			header( 'HTTP/1.1 200 OK' );

			// Validate response
			do_action( 'woocommerce_'. $this->id .'_check_response', $response );
		}
		else {
			wp_die( 'Response failed', $this->title, array( 'response' => 200 ) );
		}
	}

	/**
	 * Validate response from the bank
	 * @param  array $request Response
	 * @return void
	 */
	function validate_bank_response( $request ) {
		$order      = wc_get_order( $request['VK_STAMP'] );
		$return_url = $this->get_return_url( $order );

		if( in_array( $order->get_status(), array( 'processing', 'cancelled' ) ) ) {
			wp_redirect( $return_url );

			exit;
		}

		// Validate the results with public key
		$public_key = $this->get_option( 'vk_pubkey' );
		$validation = $this->validate_banklink_payment( $request, $public_key );

		// Check validation
		if ( isset( $validation['payment'] ) && $validation['payment'] == 'completed' ) {
			// Payment completed
			$order->add_order_note( $this->get_title() . ': ' . __( 'Payment completed.', 'wc-gateway-estonia-banklink' ) );
			$order->payment_complete();
		}
		else {
			// Set status to on-hold
			$order->update_status( 'failed', $this->get_title() . ': ' . __( 'Payment not made or is not verified.', 'wc-gateway-estonia-banklink' ) );
		}

		// Redirect to order details
		if( isset( $request['VK_AUTO'] ) && $request['VK_AUTO'] == 'N' ) {
			wp_redirect( $return_url );
		}

		exit;
	}

	/**
	 * Validate the results with public key
	 *
	 * @param  array  $params Fields received from the bank
	 * @param  string $pubkey Public key
	 * @return array          Array containing information about the validation
	 */
	function validate_banklink_payment( $params, $pubkey ) {
		// Set some variables
		$result     = array( 'payment' => 'failed' );
		$vk_service = $params['VK_SERVICE'];
		$macFields  = array();

		// Generate MAC fields
		foreach ( (array) $params as $f => $v ) {
			if ( substr($f, 0, 3) == 'VK_' ) {
				$macFields[$f] = $v;
			}
		}

		// Get public key
		$key        = openssl_pkey_get_public( $pubkey );
		$macString  = $this->generate_mac_string( $macFields );
		$verify_mac = openssl_verify( $macString, base64_decode( $macFields['VK_MAC'] ), $key, OPENSSL_ALGO_SHA1 );

		// Check the key
		if ( $verify_mac === 1 ) {
			// Correct signature
			if ( $vk_service == '1111' ) {
				$result['payment'] = 'completed';
			}
			else {
				$result['payment'] = 'cancelled';
			}
		}

		return $result;
	}

	/**
	 * Create form for bank
	 *
	 * @param  integer $order_id Order ID
	 * @return string            HTML form
	 */
	function generate_submit_form( $order_id ) {
		// Get the order
		$order      = wc_get_order( $order_id );

		// Current time
		$datetime   = new DateTime( 'NOW' );

		// Set MAC fields
		$macFields  = array(
			'VK_SERVICE'  => '1012',
			'VK_VERSION'  => '008',
			'VK_SND_ID'   => $this->get_option( 'vk_snd_id' ),
			'VK_STAMP'    => $order->id,
			'VK_AMOUNT'   => round( $order->get_total(), 2 ),
			'VK_CURR'     => get_woocommerce_currency(),
			'VK_REF'      => $this->generate_ref_num( $order->id ),
			'VK_MSG'      => sprintf( __( 'Order nr. %s payment', 'wc-gateway-estonia-banklink' ), $order->id ),
			'VK_RETURN'   => $this->notify_url,
			'VK_CANCEL'   => $this->notify_url,
			'VK_DATETIME' => $datetime->format( DateTime::ISO8601 )
		);

		// Generate MAC string from the private key
		$key        = openssl_pkey_get_private( $this->get_option( 'vk_privkey' ), $this->get_option( 'vk_pass' ) );
		$signature  = '';
		$macString  = $this->generate_mac_string( $macFields );

		// Try to sign the macstring
		if ( ! openssl_sign( $macString, $signature, $key, OPENSSL_ALGO_SHA1 ) ) {
			die( "Unable to generate signature" );
		}

		// Encode signature
		$macFields['VK_MAC'] = base64_encode( $signature );

		// language support: informs bank of preferred UI language
		$lang = $this->get_option( 'vk_lang' );

		if ( defined( 'ICL_LANGUAGE_CODE' ) )
			$lang = ICL_LANGUAGE_CODE; // WPML
		elseif ( function_exists( 'qtrans_getLanguage' ) )
			$lang = qtrans_getLanguage(); // qtranslate

		$macFields['VK_LANG'] = isset( $this->lang_codes[ $lang ] ) ? $this->lang_codes[ $lang ] : $this->lang_codes[0];

		// Start form
		$post = '<form action="'. $this->get_option( 'vk_dest' ) .'" method="post" id="banklink_'. $this->id .'_submit_form">';

		// Add fields to form inputs
		foreach ( $macFields as $name => $value ) {
			$post .= '<input type="hidden" name="'. $name .'" value="'. htmlspecialchars( $value ) .'" />';
		}

		// avoids occasional encoding errors for SEB
		$post .= '<input type="hidden" name="VK_ENCODING" value="utf-8" />';

		// Show "Pay" button and end the form
		$post .= '<input type="submit" name="send_banklink" class="button" value="'. __( 'Pay', 'wc-gateway-estonia-banklink' ) .'">';
		$post .= "</form>";

		// Add inline JS
		wc_enqueue_js( 'jQuery( "#banklink_'. $this->id .'_submit_form" ).submit();' );

		// Output form
		return $post;
	}
}