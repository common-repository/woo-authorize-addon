<?php
/**
 * The Template for list saved cards on checkout
 */

?>

<div id="woo_wcauthnet_credit_card_form">
	<div class="cards"><h6><?php _e( 'Your credit cards', 'woo-woocommerce-authorizenet-payment-gateway' ) ?></h6><?php

		foreach ( $payment_profiles as $payment_profile ) {
			?>
			<div class="card<?php echo $payment_profile['default'] == true ? ' selected' : '' ?>">
				<input type="radio" class="payment-profile-radio" value="<?php echo $payment_profile['profile_id'] ?>" id="<?php echo $payment_profile['profile_id'] ?>"
				       name="authorize_net_payment_profile" <?php checked( $payment_profile['default'], true ) ?> />
				<label for="<?php echo $payment_profile['profile_id'] ?>">
					<?php echo sprintf(
						'<span class="card-number">%s</span> <span class="card-expire">(%s)</span>',
						$payment_profile['account_num'],
						implode( '/', str_split( $payment_profile['expiration_date'], 2 ) )
					); ?>
				</label>
			</div>
		<?php
		}
		?>

		<div class="card new-profile">
			<input type="radio" class="payment-profile-radio" value="" name="authorize_net_payment_profile" id="woo_wcauthnet_payment_profile_new"/>
			<label for="woo_wcauthnet_payment_profile_new"><?php _e( 'New card', 'woo-woocommerce-authorizenet-payment-gateway' ) ?></label>

			<div class="new-profile-form" style="display: none;" >
				<?php $this->credit_card_form() ?>
			</div>
		</div>

		<div class="clear"></div>

	</div>
</div>