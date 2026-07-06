<?php
/**
 * Admin new booking email
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( wc_booking_order_requires_confirmation( $booking->get_order() ) && $booking->get_status() === 'pending-confirmation' ) {
    // translators: Customer Full Name.
	$opening_paragraph = __( 'A booking has been made by %s and is awaiting your approval. The details of this booking are as follows:', 'dokan' );
} else {
    // translators: Customer Full Name.
	$opening_paragraph = __( 'A new booking has been made by %s. The details of this booking are as follows:', 'dokan' );
}
?>

<?php do_action( 'woocommerce_email_header', $email_heading ); ?>

<?php if ( $booking->get_order() && $booking->get_order()->get_billing_first_name() && $booking->get_order()->get_billing_last_name() ) : ?>
	<p><?php printf( $opening_paragraph, $booking->get_order()->get_billing_first_name() . ' ' . $booking->get_order()->get_billing_last_name() ); ?></p>
<?php endif; ?>

<table cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #eee;" border="1" bordercolor="#eee">
	<tbody>
		<tr>
			<th scope="row" style="text-align:left; border: 1px solid #eee;"><?php esc_html_e( 'Booked Product', 'dokan' ); ?></th>
			<td style="text-align:left; border: 1px solid #eee;"><?php echo esc_html( wptexturize( $booking->get_product()->get_title() ) ); ?></td>
		</tr>
		<tr>
			<th style="text-align:left; border: 1px solid #eee;" scope="row"><?php esc_html_e( 'Booking ID', 'dokan' ); ?></th>
			<td style="text-align:left; border: 1px solid #eee;"><?php echo esc_html( $booking->get_id() ); ?></td>
		</tr>
		<?php if ( $booking->has_resources() && ( $booking->get_resource() ) ) : ?>
			<tr>
				<th style="text-align:left; border: 1px solid #eee;" scope="row"><?php esc_html_e( 'Booking Type', 'dokan' ); ?></th>
				<td style="text-align:left; border: 1px solid #eee;"><?php echo esc_html( wptexturize( $booking->get_resource()->post_title ) ); ?></td>
			</tr>
		<?php endif; ?>
		<tr>
			<th style="text-align:left; border: 1px solid #eee;" scope="row"><?php esc_html_e( 'Booking Start Date', 'dokan' ); ?></th>
			<td style="text-align:left; border: 1px solid #eee;"><?php echo esc_html( $booking->get_start_date() ); ?></td>
		</tr>
		<tr>
			<th style="text-align:left; border: 1px solid #eee;" scope="row"><?php esc_html_e( 'Booking End Date', 'dokan' ); ?></th>
			<td style="text-align:left; border: 1px solid #eee;"><?php echo esc_html( $booking->get_end_date() ); ?></td>
		</tr>
		<?php if ( $booking->has_persons() ) : ?>
            <?php
            foreach ( $booking->get_persons() as $person_id => $qty ) :
                if ( 0 === $qty ) {
                    continue;
                }

                $person_type = ( 0 < $person_id ) ? get_the_title( $person_id ) : __( 'Person(s)', 'dokan' );
                ?>
				<tr>
					<th style="text-align:left; border: 1px solid #eee;" scope="row"><?php echo esc_html( wptexturize( $person_type ) ); ?></th>
					<td style="text-align:left; border: 1px solid #eee;"><?php echo esc_html( $qty ); ?></td>
				</tr>
			<?php endforeach; ?>
		<?php endif; ?>
	</tbody>
</table>

<?php if ( wc_booking_order_requires_confirmation( $booking->get_order() ) && $booking->get_status() === 'pending-confirmation' ) : ?>
<p><?php esc_html_e( 'This booking is awaiting your approval. Please check it and inform the customer if the date is available or not.', 'dokan' ); ?></p>
<?php endif; ?>

<p>
    <?php
    $booking_text = __( 'View and edit this booking in the dashboard ', 'dokan' );
    echo wp_kses_post( make_clickable( sprintf( '<a href="%1$s">%2$s</a>', add_query_arg( 'booking_id', $booking->get_id(), dokan_get_navigation_url( 'booking/booking-details' ) ), $booking_text ) ) );
    ?>
</p>

<?php
/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
    echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

do_action( 'woocommerce_email_footer' );
?>
