<?php
class WC_Banklink_Swedbank_Gateway extends WC_Banklink_Ipizza {
	function __construct() {
		$this->id           = 'swedbank';
		$this->method_title = __( 'Swedbank', 'wc-gateway-estonia-banklink' );

		parent::__construct();
	}
}