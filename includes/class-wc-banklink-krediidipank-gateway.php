<?php
class WC_Banklink_Krediidipank_Gateway extends WC_Banklink_Ipizza {
	function __construct() {
		$this->id           = 'krediidipank';
		$this->method_title = __( 'Krediidipank', 'wc-gateway-estonia-banklink' );

		parent::__construct();
	}
}