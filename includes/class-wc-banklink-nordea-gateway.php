<?php
class WC_Banklink_Nordea_Gateway extends WC_Banklink_Solo {
	function __construct() {
		$this->id    = 'nordea';
		$this->title = __( 'Nordea', 'wc-gateway-estonia-banklink' );

		parent::__construct();
	}
}