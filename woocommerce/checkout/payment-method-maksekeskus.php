<div class="wc-gateway-estonia-banklink__maksekeskus">

	<?php foreach ( $methods as $key => $method ) : ?>
		<div class="banklink-maksekeskus-selection
		<?php
		if ( $current_method == $method->name ) {
			echo ' banklink-maksekeskus-selection--active';}
		?>
		" data-name="<?php echo esc_attr( $method->name ); ?>">
			<img src="<?php echo esc_url( $method->logo ); ?>" alt="" class="banklink-maksekeskus-selection__logo">
		</div>
	<?php endforeach ?>

	<input type="hidden" name="banklink_gateway_maksekeskus_method" class="banklink-maksekeskus-selection__value" value="<?php echo esc_attr( $current_method ); ?>">
</div>
