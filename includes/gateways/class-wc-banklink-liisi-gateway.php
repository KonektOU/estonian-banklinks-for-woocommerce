<?php
class WC_Banklink_Liisi_Gateway extends WC_Banklink_Ipizza {
	/**
	 * WC_Banklink_LHV_Gateway
	 */
	function __construct() {
		$this->id           = 'liisi';
		$this->method_title = __( 'Liisi', 'wc-gateway-estonia-banklink' );

		$this->encoding     = 'UTF-8';

		parent::__construct();
	}
}