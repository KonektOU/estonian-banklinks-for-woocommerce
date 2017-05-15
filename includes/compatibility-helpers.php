<?php
/**
 * Get order ID based on WC version
 *
 * @since  1.3.2
 * @param  WC_Order $order Order
 * @return integer         Order ID
 */
function wc_estonian_gateways_get_order_id( $order ) {
	if( method_exists( $order, 'get_id' ) ) {
		return $order->get_id();
	}
	else {
		return $order->id;
	}
}

/**
 * Get customer billing country based on WC version
 *
 * @since  1.3.2
 * @return string Country
 */
function wc_estonian_gateways_get_customer_billing_country() {
	if( method_exists( WC()->customer, 'get_billing_country' ) ) {
		return WC()->customer->get_billing_country();
	}
	else {
		return WC()->customer->get_country();
	}
}