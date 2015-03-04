<?php
if( ! class_exists( 'Maksekeskus_API' ) ) :

class Maksekeskus_API {
	function __construct( $options = array() ) {
		foreach( $options as $option_name => $option_value ) {
			$this->$option_name = $option_value;
		}
	}

	function get_shop() {
		return $this->request( 'shop' );
	}

	function update_shop( $data ) {
		return $this->request( 'shop', $data, 'PUT' );
	}

	function get_payment_methods( $data ) {
		return $this->request( 'methods/?' . http_build_query( $data ) );
	}

	function create_transaction( $data ) {
		return $this->request( 'transactions', $data );
	}

	function receive_transaction( $transaction_id ) {
		return $this->request( array( 'transactions', $transaction_id ) );
	}

	function create_payment( $transaction_id, $data ) {
		if( ! isset( $data['token'] ) )
			$data['token'] = $transaction_id;

		return $this->request( array( 'transactions', $transaction_id, 'payments' ), $data );
	}

	function create_refund( $transaction_id, $data ) {
		return $this->request( array( 'transactions', $transaction_id, 'refunds' ), $data );
	}

	function get_url( $method = '' ) {
		$base_url = $this->api_url;

		if( is_array( $method ) )
			$method = implode( '/', $method );

		return $base_url . $method;
	}

	function request( $method, $data = array(), $submit_method = 'POST' ) {
		$ch = curl_init();

		curl_setopt( $ch, CURLOPT_URL, $this->get_url( $method ) );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_HEADER, false );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );

		if( ! empty( $data ) ) {
			if( $submit_method == 'PUT' ) {
				curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'PUT' );
			}
			else {
				curl_setopt( $ch, CURLOPT_POST, true );
			}

			curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $data ) );
			curl_setopt( $ch, CURLOPT_HTTPHEADER, array( "Content-Type: application/json" ) );
		}

		curl_setopt( $ch, CURLOPT_USERPWD, $this->shop_id . ':' . $this->api_key );

		$response = curl_exec( $ch );

		curl_close( $ch );

		return @json_decode( $response );
	}
}

endif;