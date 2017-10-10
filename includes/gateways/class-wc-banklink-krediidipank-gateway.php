<?php
class WC_Banklink_Krediidipank_Gateway extends WC_Banklink_Ipizza {
	/**
	 * WC_Banklink_Krediidipank_Gateway
	 */
	function __construct() {
		$this->id           = 'krediidipank';
		$this->method_title = __( 'Coop pank', 'wc-gateway-estonia-banklink' );
		$this->logo         = $this->get_option( 'logo', plugins_url( 'assets/img/coop.gif', WC_ESTONIAN_GATEWAYS_MAIN_FILE ) );

		parent::__construct();
	}
}