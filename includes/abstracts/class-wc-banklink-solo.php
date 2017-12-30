<?php
abstract class WC_Banklink_Solo extends WC_Banklink {
	/**
	 * WC_Banklink_Solo
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
			'solopmt_dest'       => array(
				'title'          => __( 'Request URL', 'wc-gateway-estonia-banklink' ),
				'type'           => 'text',
				'default'        => '',
				'description'    => 'SOLOPMT_DEST',
				'desc_tip'       => TRUE
			),
			'solopmt_rcv_id'     => array(
				'title'          => __( 'Account ID', 'wc-gateway-estonia-banklink' ),
				'type'           => 'text',
				'default'        => '',
				'description'    => 'SOLOPMT_RCV_ID',
				'desc_tip'       => TRUE
			),
			'solopmt_rcv_account'=> array(
				'title'          => __( 'Account number', 'wc-gateway-estonia-banklink' ),
				'type'           => 'text',
				'default'        => '',
				'description'    => 'SOLOPMT_RCV_ACCOUNT',
				'desc_tip'       => TRUE
			),
			'solopmt_mac'        => array(
				'title'          => __( 'MAC Key', 'wc-gateway-estonia-banklink' ),
				'type'           => 'text',
				'default'        => ''
			),
			'solopmt_keyvers'    => array(
				'title'          => __( 'MAC Key version', 'wc-gateway-estonia-banklink' ),
				'type'           => 'select',
				'description'    => 'SOLOPMT_KEYVERS',
				'default'        => '0001',
				'options'        => array(
						'0001'   => __( '0001 (md5)', 'wc-gateway-estonia-banklink' ),
						'0002'   => __( '0002 (sha1)', 'wc-gateway-estonia-banklink' )
					),
				'desc_tip'       => TRUE
			),
			'solopmt_language'   => array(
				'title'          => __( 'Preferred language', 'wc-gateway-estonia-banklink' ),
				'type'           => 'select',
				'default'        => '4',
				'description'    => 'SOLOPMT_LANGUAGE',
				'desc_tip'       => TRUE,
				'options'        => array(
						'1'      => __( 'Finnish', 'wc-gateway-estonia-banklink' ),
						'3'      => __( 'English', 'wc-gateway-estonia-banklink' ),
						'4'      => __( 'Estonian', 'wc-gateway-estonia-banklink' ),
						'6'      => __( 'Latvian', 'wc-gateway-estonia-banklink' ),
						'7'      => __( 'Lithuanian', 'wc-gateway-estonia-banklink' )
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
	function output_gateway_redirection_form( $order_id ) {
		// Get the order
		$order			= wc_get_order( $order_id );

		// Set MAC fields
		$mac_fields		= array(
			'SOLOPMT_VERSION'     => '0003',
			'SOLOPMT_STAMP'       => wc_estonian_gateways_get_order_id( $order ),
			'SOLOPMT_RCV_ID'      => $this->get_option( 'solopmt_rcv_id' ),
			'SOLOPMT_RCV_ACCOUNT' => $this->get_option( 'solopmt_rcv_account' ),
			'SOLOPMT_LANGUAGE'    => $this->get_option( 'solopmt_language' ),
			'SOLOPMT_AMOUNT'      => wc_estonian_gateways_get_order_total( $order ),
			'SOLOPMT_REF'         => $this->generate_ref_num( wc_estonian_gateways_get_order_id( $order ) ),
			'SOLOPMT_DATE'        => 'EXPRESS',
			'SOLOPMT_MSG'         => sprintf( __( 'Order nr. %s payment', 'wc-gateway-estonia-banklink' ), wc_estonian_gateways_get_order_id( $order ) ),
			'SOLOPMT_RETURN'      => $this->notify_url,
			'SOLOPMT_CANCEL'      => $this->notify_url,
			'SOLOPMT_REJECT'      => $this->notify_url,
			'SOLOPMT_CONFIRM'     => 'YES',
			'SOLOPMT_KEYVERS'     => $this->get_option( 'solopmt_keyvers' ),
			'SOLOPMT_CUR'         => get_woocommerce_currency(),
		);

		// Allow hooking into the data
		$mac_fields                = $this->hookable_transaction_data( $mac_fields, $order );

		// Generate MAC string
		$mac_fields['SOLOPMT_MAC'] = $this->generate_mac_string( $mac_fields );

		// Output form
		return $this->get_redirect_form( $this->get_option( 'solopmt_dest' ), $mac_fields );
	}

	/**
	 * Listen for the response from bank
	 *
	 * @return void
	 */
	function check_bank_response() {
		// Debug response data
		$this->debug( $_REQUEST );

		if ( ! empty( $_REQUEST ) && isset( $_REQUEST['SOLOPMT_RETURN_MAC'] ) ) {
			// Validate response
			do_action( 'woocommerce_'. $this->id .'_check_response', $_REQUEST );
		}
	}

	/**
	 * Validate response from the bank
	 *
	 * @param  array $request Response
	 * @return void
	 */
	function validate_bank_response( $request ) {
		// Validate the results with public key
		$validation = $this->validate_banklink_payment( $request, $this->get_option( 'vk_pubkey' ) );
		$order	    = wc_get_order( $request['SOLOPMT_RETURN_STAMP'] );

		// Check validation
		if ( isset( $validation['payment'] ) && $validation['payment'] == 'success' ) {
			// Payment completed
			$order->add_order_note( sprintf( '%s: %s', $this->get_title(), __( 'Payment completed.', 'wc-gateway-estonia-banklink'  ) ) );
			$order->payment_complete();
		}
		else {
			// Set status to failed
			$order->update_status( 'failed', sprintf( '%s: %s', $this->get_title(), __( 'Payment not made or is not verified.', 'wc-gateway-estonia-banklink' ) ) );
		}

		// Redirect to order details
		wp_redirect( $this->get_return_url( $order ) );
		exit;
	}

	/**
	 * Generates MAC string as needed according to the service number
	 *
	 * @param  array  $mac_fields MAC fields
	 * @return string            MAC string
	 */
	function generate_mac_string( $mac_fields ) {
		// Set variable order according to service
		$variable_order = array( 'SOLOPMT_VERSION', 'SOLOPMT_STAMP', 'SOLOPMT_RCV_ID', 'SOLOPMT_AMOUNT', 'SOLOPMT_REF', 'SOLOPMT_DATE', 'SOLOPMT_CUR' );

		// Data holder
		$data           = '';

		// Append data as needed
		foreach ( $variable_order as $key ) {
			if ( isset( $mac_fields[$key] ) ) {;
				$data .= $mac_fields[$key] . '&';
			}
			else {
				$data	 .= '&';
			}
		}

		// Add MAC string
		$keyvers   = $this->get_option( 'solopmt_keyvers' );
		$mac       = $this->get_option( 'solopmt_mac' );
		$data      .= $mac . '&';

		// Encrypt data
		$data      = ( $keyvers == '0001' ) ? md5( $data ) : sha1( $data );

		// Return data
		return strtoupper( $data );
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
		$result         = array( 'orderNr' => $params['SOLOPMT_RETURN_STAMP'], 'payment' => 'failed' );
		$variable_order = array( 'SOLOPMT_RETURN_VERSION', 'SOLOPMT_RETURN_STAMP', 'SOLOPMT_RETURN_REF', 'SOLOPMT_RETURN_PAID' );

		$data           = '';

		foreach ( $variable_order as $var ) {
			if ( isset( $params[ $var ] ) ) {
				$data  .= $params[ $var ] . '&';
			}
			else {
				$data  .= '&';
			}
		}

		$keyvers = $this->get_option( 'solopmt_keyvers' );
		$mac     = $this->get_option( 'solopmt_mac' );
		$data    .= $mac . '&';

		// Encrypt data
		$data    = ( $keyvers == '0001' ) ? md5( $data ) : sha1( $data );

		// Check the MACs
		if ( isset( $params['SOLOPMT_RETURN_MAC'] ) && strtoupper( $data ) == $params['SOLOPMT_RETURN_MAC'] ) {
			// Correct signature
			if ( isset( $params['SOLOPMT_RETURN_PAID'] ) ) {
				$result['payment'] = 'success';
			}
			else {
				$result['payment'] = 'cancelled';
			}
		}

		return $result;
	}
}