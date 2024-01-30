<?php
/**
 * Get order ID based on WC version
 *
 * @since  1.3.2
 * @param  WC_Order $order Order
 * @return integer         Order ID
 */
function wc_estonian_gateways_get_order_id( $order ) {
	if ( method_exists( $order, 'get_id' ) ) {
		return $order->get_id();
	} else {
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
	if ( method_exists( WC()->customer, 'get_billing_country' ) ) {
		return WC()->customer->get_billing_country();
	} else {
		return WC()->customer->get_country();
	}
}

/**
 * Get customer billing country based on WC version
 *
 * @since  1.4
 * @param  WC_Order $order Order
 * @return string Country
 */
function wc_estonian_gateways_get_customer_ip_address( $order ) {
	if ( method_exists( $order, 'get_customer_ip_address' ) ) {
		return $order->get_customer_ip_address();
	} else {
		return $order->customer_ip_address;
	}
}

/**
 * Get order total and format it to string. Fixes issue with PHP7.1
 * precision
 *
 * @since  1.3.4
 * @param  WC_Order $order Order
 * @return string          Formatted order total
 */
function wc_estonian_gateways_get_order_total( $order ) {
	return number_format( round( $order->get_total(), 2 ), 2, '.', '' );
}

/**
 * Get order billing email address based on WC version
 *
 * @since  1.4
 * @param  WC_Order $order Order
 * @return string          Order billing email
 */
function wc_estonian_gateways_get_order_billing_email( $order ) {
	if ( method_exists( $order, 'get_billing_email' ) ) {
		return $order->get_billing_email();
	} else {
		return $order->billing_email;
	}
}

/**
 * Get order currency based on WC version
 *
 * @since  1.4
 * @param  WC_Order $order Order
 * @return integer         Order ID
 */
function wc_estonian_gateways_get_order_currency( $order ) {
	if ( method_exists( $order, 'get_currency' ) ) {
		return $order->get_currency();
	} else {
		return $order->get_order_currency();
	}
}
