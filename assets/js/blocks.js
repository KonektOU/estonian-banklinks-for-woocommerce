/**
 * Register the banklinks with the checkout block.
 *
 * One file for every gateway: window.wcEstonianGateways.gateways lists the
 * ids to look for, and each gateway's own data arrives from PHP under
 * "<id>_data". A gateway that is not available right now sends no data, so
 * it is skipped.
 *
 * @package Estonian Banklinks For WooCommerce
 */

( function ( registry, settings, element, htmlEntities ) {
	'use strict';

	if ( ! registry || ! settings || ! element ) {
		return;
	}

	var createElement = element.createElement;
	var decodeEntities = ( htmlEntities && htmlEntities.decodeEntities ) || function ( value ) {
		return value;
	};

	var gateways = ( window.wcEstonianGateways && window.wcEstonianGateways.gateways ) || [];

	/**
	 * What is shown once the method is selected: the shop's own message, if
	 * it set one. These gateways take no card details - the customer is sent
	 * to the bank after placing the order.
	 *
	 * @param {Object} props Component props.
	 *
	 * @return {Object|null} Element.
	 */
	function Description( props ) {
		if ( ! props.description ) {
			return null;
		}

		return createElement( 'p', null, decodeEntities( props.description ) );
	}

	/**
	 * The method's label: its title, with its logo beside it when it has one.
	 *
	 * @param {string} title Title.
	 * @param {string} icon  Icon URL.
	 *
	 * @return {Object} Element.
	 */
	function label( title, icon ) {
		var children = [ createElement( 'span', { key: 'title' }, title ) ];

		if ( icon ) {
			children.push(
				createElement( 'img', {
					key: 'icon',
					src: icon,
					alt: title,
					className: 'wc-estonian-gateway-icon',
					style: { marginLeft: 'auto', maxHeight: '24px' }
				} )
			);
		}

		return createElement(
			'span',
			{ className: 'wc-estonian-gateway-label', style: { display: 'flex', width: '100%', alignItems: 'center' } },
			children
		);
	}

	gateways.forEach( function ( id ) {
		var data = settings.getSetting( id + '_data', null );

		if ( ! data ) {
			return;
		}

		var title = decodeEntities( data.title || id );
		var content = createElement( Description, { description: data.description } );

		registry.registerPaymentMethod( {
			name: id,
			label: label( title, data.icon ),
			ariaLabel: title,
			content: content,
			edit: content,
			canMakePayment: function () {
				return true;
			},
			supports: {
				features: data.supports || [ 'products' ]
			}
		} );
	} );
} )(
	window.wc && window.wc.wcBlocksRegistry,
	window.wc && window.wc.wcSettings,
	window.wp && window.wp.element,
	window.wp && window.wp.htmlEntities
);
