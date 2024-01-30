<?php
class WC_Banklink_SEB_Gateway extends WC_Banklink_Ipizza {

	/**
	 * WC_Banklink_SEB_Gateway
	 */
	function __construct() {
		$this->id           = 'seb';
		$this->method_title = __( 'SEB', 'wc-gateway-estonia-banklink' );

		parent::__construct();
	}
}
