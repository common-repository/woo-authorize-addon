<?php
/**
 * The Template for list saved cards on checkout
 */

do_action( 'woocommerce_before_saved_cards' );
?>

<?php if ( ! empty( $payment_methods ) ) : ?>

	<table class="shop_table shop_table_responsive my_account_orders my_account_authorize_payment_methods">

		<thead>
		<tr>
			<th class="payment-method-type"><span class="nobr"><?php _e( 'Card', 'woo-woocommerce-authorizenet-payment-gateway' ) ?></span></th>
			<th class="payment-method-expire"><span class="nobr"><?php _e( 'Expire', 'woo-woocommerce-authorizenet-payment-gateway' ) ?></span></th>
			<th class="payment-method-actions">&nbsp;</th>
		</tr>
		</thead>

		<tbody>

		<?php foreach ( $payment_methods as $payment_method ) : ?>
			<tr class="order">
				<td class="payment-method-type" data-title="<?php _e( 'Account Number', 'woo-woocommerce-authorizenet-payment-gateway' ) ?>">
					<?php printf(
						'<span class="payment-method-number"><small>%s</small></span>',
						$payment_method['account_num']
					); ?>
					<?php if ( $payment_method['default'] ) : ?>
						<span class="tag-label default"><?php _e( 'default', 'woo-woocommerce-authorizenet-payment-gateway' ) ?></span>
					<?php else : ?>
						<a class="tag-label default show-on-hover" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'wcauthnet-action' => 'set-default-card', 'id' => $payment_method['profile_id'] ) ), 'wcauthnet-set-default-card' ) ) ?>" data-table-action="default"><?php _e( 'set default', 'woo-woocommerce-authorizenet-payment-gateway' ) ?></a>
					<?php endif; ?>
				</td>
				<td class="payment-method-expire" data-title="<?php _e( 'Expire', 'woo-woocommerce-authorizenet-payment-gateway' ) ?>">
					<?php echo implode( '/', str_split( $payment_method['expiration_date'], 2 ) ) ?>
				</td>
				<td class="payment-method-actions">
					<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'wcauthnet-action' => 'delete-card', 'id' => $payment_method['profile_id'] ) ), 'wcauthnet-delete-card' ) ) ?>" class="button delete" data-table-action="delete"><?php _e( 'Delete', 'woo-woocommerce-authorizenet-payment-gateway' ) ?></a>
				</td>
			</tr>
		<?php endforeach; ?>

		</tbody>

	</table>

<?php else : ?>

	<p><?php _e( 'No cards saved', 'woo-woocommerce-authorizenet-payment-gateway' ) ?></p>

<?php endif; ?>