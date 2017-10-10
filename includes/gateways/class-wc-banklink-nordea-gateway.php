<?php
class WC_Banklink_Nordea_Gateway extends WC_Banklink_Solo {
	/**
	 * WC_Banklink_Nordea_Gateway
	 */
	function __construct() {
		$this->id           = 'nordea';
		$this->method_title       = __( 'Luminor', 'wc-gateway-estonia-banklink' );
		$this->method_description = __( 'Luminor (former Nordea) payment gateway via SOLO protocol. Use for older clients.', 'wc-gateway-estonia-banklink' );

		parent::__construct();
	}
}