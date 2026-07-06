<?php
/**
 * Display a row in the related orders table for a subscription or order
 *
 * @var array $order A WC_Order or WC_Subscription order object to display
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<tr>
	<td>
		<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'order_id' => $order->get_id() ), dokan_get_navigation_url( 'orders' ) ), 'dokan_view_order' ) ); ?>">
			<?php echo sprintf( esc_html_x( '#%s', 'hash before order number', 'woocommerce-subscriptions' ), esc_html( $order->get_order_number() ) ); ?>
		</a>
	</td>
	<td>
		<?php echo esc_html( $order->get_meta( '_relationship', true ) ); ?>
	</td>
	<td>
		<?php
		$timestamp_gmt = $order->get_date_created()->getTimestamp();
		if ( $timestamp_gmt > 0 ) {
			// translators: php date format
			$t_time          = dokan_format_datetime( $timestamp_gmt );
			$date_to_display = ucfirst( wcs_get_human_time_diff( $timestamp_gmt ) );
		} else {
			$t_time = $date_to_display = __( 'Unpublished', 'woocommerce-subscriptions' );
		} ?>
		<abbr title="<?php echo esc_attr( $t_time ); ?>">
			<?php echo esc_html( $date_to_display ); ?>
		</abbr>
	</td>
	<td>
		<?php
		if ( wcs_is_subscription( $order ) ) {
		    echo esc_html( wcs_get_subscription_status_name( $order->get_status( 'view' ) ) );
		} else {
		    echo esc_html( wc_get_order_status_name( $order->get_status( 'view' ) ) );
		}
		?>
	</td>
	<td>
		<span class="amount"><?php echo wp_kses( $order->get_formatted_order_total(), array( 'small' => array(), 'span' => array( 'class' => array() ), 'del' => array(), 'ins' => array() ) ); ?></span>
	</td>
</tr>
