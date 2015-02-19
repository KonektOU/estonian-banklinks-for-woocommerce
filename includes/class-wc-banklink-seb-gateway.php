<?php
class WC_Banklink_SEB_Gateway extends WC_Banklink_Ipizza {
	function __construct() {
		$this->id    = 'seb';
		$this->title = __( 'SEB', 'wc-gateway-estonia-banklink' );

		parent::__construct();
	}
}