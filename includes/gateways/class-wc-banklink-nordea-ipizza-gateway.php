<?php
class WC_Banklink_Nordea_Ipizza_Gateway extends WC_Banklink_Ipizza {

	/**
	 * WC_Banklink_Nordea_IPizza_Gateway
	 */
	function __construct() {
		$this->id                 = 'nordea_ipizza';
		$this->method_title       = __( 'Luminor', 'wc-gateway-estonia-banklink' );
		$this->method_description = __( 'Luminor (former Nordea) payment gateway via IPIZZA protocol. Use for new clients.', 'wc-gateway-estonia-banklink' );

		$this->encoding = 'UTF-8';

		parent::__construct();
	}

	/**
	 * Get encryption algorithm
	 *
	 * Luminor is on VK version 008, which is SHA-1. The name of this method
	 * was misspelled when it was added in 1.6, so it was never called and
	 * every request to Luminor went out signed with the parent's SHA-512
	 * beside a VK_VERSION saying SHA-1 - which the bank cannot verify.
	 *
	 * @return int
	 */
	public function get_algorithm() {
		return OPENSSL_ALGO_SHA1;
	}

	/**
	 * Get VK version
	 *
	 * @return string
	 */
	public function get_vk_version() {
		return '008';
	}
}
