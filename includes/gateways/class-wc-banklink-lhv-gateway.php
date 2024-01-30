<?php
class WC_Banklink_LHV_Gateway extends WC_Banklink_Ipizza {

	/**
	 * WC_Banklink_LHV_Gateway
	 */
	function __construct() {
		$this->id           = 'lhv';
		$this->method_title = __( 'LHV', 'wc-gateway-estonia-banklink' );

		parent::__construct();
	}
}
