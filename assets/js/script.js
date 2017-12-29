(function (window, document, $) {

	$( 'body' )
		.on( 'click', '.banklink-maksekeskus-selection', function(event) {
			event.preventDefault();

			var $this = $( this );

			$this.siblings().removeClass( 'banklink-maksekeskus-selection--active' );
			$this.addClass( 'banklink-maksekeskus-selection--active' );
			$this.siblings( '.banklink-maksekeskus-selection__value' ).val( $this.data( 'name' ) );
		});

})(window, document, jQuery);