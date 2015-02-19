<?php
class WC_Banklink_Danske_Gateway extends WC_Banklink_Ipizza {
	function __construct() {
		$this->id    = 'danske';
		$this->title = __( 'Danske', 'wc-gateway-estonia-banklink' );

		parent::__construct();
	}
}