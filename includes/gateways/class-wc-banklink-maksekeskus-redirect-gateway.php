<?php
class WC_Banklink_Maksekeskus_Redirect_Gateway extends WC_Banklink_Maksekeskus {

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

		// Replace default value and title for this gateway
		$this->form_fields['api_url']['default'] = $this->get_option( 'destination_url', 'https://payment.maksekeskus.ee/pay/1/signed.html' );
		$this->form_fields['api_url']['title']   = __( 'Destination URL', 'wc-gateway-estonia-banklink' );
	}

	/**
	 * Create form for bank
	 *
	 * @param  integer $order_id Order ID
	 * @return string            HTML form
	 */
	function output_gateway_redirection_form( $order_id ) {
		// Get the order
		$order       = wc_get_order( $order_id );

		// Request
		$request     = array(
			'shop'            => $this->get_option( 'shop_id' ),
			'amount'          => wc_estonian_gateways_get_order_total( $order ),
			'reference'       => wc_estonian_gateways_get_order_id( $order ),
			'country'         => wc_estonian_gateways_get_customer_billing_country( $order ),
			'locale'          => $this->get_option( 'locale' ),
			'transaction_url' => $this->get_transaction_urls()
		);

		// Allow hooking into the data
		$request     = $this->hookable_transaction_data( $request, $order );

		// Generate MAC code
		$mac_code    = $this->get_signature( $request );

		// Form fields
		$form_fields = array(
			'json' => json_encode( $request, JSON_UNESCAPED_UNICODE ),
			'mac'  => $mac_code
		);

		// Output form
		return $this->get_redirect_form( $this->get_option( 'api_url' ), $form_fields );
	}
}
