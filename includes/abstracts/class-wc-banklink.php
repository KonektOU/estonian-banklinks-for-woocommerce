<?php
abstract class WC_Banklink extends WC_Payment_Gateway {
	function __construct() {

		$this->has_fields  = FALSE;
		$this->notify_url  = WC()->api_request_url( get_class( $this ) );

		// Get the settings
		$this->title       = $this->get_option( 'title' );
		$this->enabled     = $this->get_option( 'enabled' );
		$this->description = $this->get_option( 'description' );

		// Load the settings
		$this->init_form_fields();
		$this->init_settings();

		// Payment listener/API hook
		add_action( 'woocommerce_api_' . strtolower( get_class( $this ) ),      array( $this, 'check_bank_response' ) );

		// Actions
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_receipt_' . $this->id,                         array( $this, 'receipt_page' ) );
		add_action( 'woocommerce_'. $this->id .'_check_response',               array( $this, 'validate_bank_response' ) );
	}

	/**
	 * Generate some kind of reference number
	 *
	 * @param  string $stamp Purchase ID
	 * @return string        Reference number
	 */
	function generate_ref_num( $stamp ) {
		$chcs = array(7, 3, 1);
		$sum  = 0;
		$pos  = 0;

		for ( $i = 0; $i < strlen( $stamp ); $i++ ) {
			$x   = (int) ( substr( $stamp, strlen( $stamp ) - 1 - $i, 1) );
			$sum = $sum + ( $x * $chcs[ $pos ] );

			if ( $pos == 2 ) $pos = 0;
			else $pos = $pos + 1;
		}

		$x   = 10 - ( $sum % 10 );
		$sum = ( $x != 10 ) ? $x : 0;

		return $stamp . $sum;
	}

	/**
	 * Payment processing
	 *
	 * @param  integer $order_id Order ID
	 * @return array             Redirect URL and result (success)
	 */
	function process_payment( $order_id ) {
		// Get the order
		$order = wc_get_order( $order_id );

		// Redirect
		return array(
			'result'	=> 'success',
			'redirect'	=> $order->get_checkout_payment_url( true )
		);
	}

	function receipt_page( $order_id ) {
		// Say thank you :)
		echo apply_filters( 'the_content', sprintf( __( 'Thank you for your order, please click the button below to pay with %s in case automatic redirection does not work.', 'wc-gateway-estonia-banklink' ), $this->get_title() ) );

		// Generate the form
		echo $this->generate_submit_form( $order_id );
	}

	/**
	 * Is this gateway available?
	 *
	 * @return boolean
	 */
	function is_available() {
		return $this->get_option( 'enabled', 'no' ) != 'no';
	}
}