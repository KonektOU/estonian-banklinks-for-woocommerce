<div class="wc-gateway-estonia-banklink__maksekeskus">

	<?php foreach( $methods as $method_group => $gateways ) : ?>
		<?php foreach( $gateways as $gateway ) : ?>
			<div class="banklink-maksekeskus-selection<?php if( $current_method == $gateway->name ) echo ' banklink-maksekeskus-selection--active' ?>" data-name="<?php echo esc_attr( $gateway->name ) ?>">
				<img src="<?php echo $gateway->logo ?>" alt="" class="banklink-maksekeskus-selection__logo">
			</div>
		<?php endforeach ?>
	<?php endforeach ?>

	<input type="hidden" name="banklink_gateway_maksekeskus_method" class="banklink-maksekeskus-selection__value" value="<?php echo $current_method ?>">
</div>