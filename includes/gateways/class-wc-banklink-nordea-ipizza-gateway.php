<?php
class WC_Banklink_Nordea_Ipizza_Gateway extends WC_Banklink_Ipizza {
	/**
	 * WC_Banklink_Nordea_IPizza_Gateway
	 */
	function __construct() {
		$this->id           = 'nordea_ipizza';
		$this->method_title = __( 'Nordea', 'wc-gateway-estonia-banklink' );
		$this->method_description = __( 'Nordea payment gateway via IPIZZA protocol. Use for new clients.', 'wc-gateway-estonia-banklink' );

		parent::__construct();
	}
}