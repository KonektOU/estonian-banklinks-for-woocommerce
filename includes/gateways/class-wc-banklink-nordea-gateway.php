<?php
class WC_Banklink_Nordea_Gateway extends WC_Banklink_Solo {
	/**
	 * WC_Banklink_Nordea_Gateway
	 */
	function __construct() {
		$this->id           = 'nordea';
		$this->method_title = __( 'Nordea', 'wc-gateway-estonia-banklink' );

		parent::__construct();
	}
}