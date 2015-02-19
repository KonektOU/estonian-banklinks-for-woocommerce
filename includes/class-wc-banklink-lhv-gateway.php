<?php
class WC_Banklink_LHV_Gateway extends WC_Banklink_Ipizza {
	function __construct() {
		$this->id    = 'lhv';
		$this->title = __( 'LHV', 'wc-gateway-estonia-banklink' );

		parent::__construct();
	}
}