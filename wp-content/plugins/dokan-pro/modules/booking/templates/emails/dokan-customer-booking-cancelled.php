<?php
/**
 * Admin booking cancelled email
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>

<?php do_action( 'woocommerce_email_header', $email_heading ); ?>

<p><?php esc_html_e( 'The following booking has been cancelled by the customer. The details of the cancelled booking can be found below.', 'dokan' ); ?></p>

<table cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #eee;" border="1" bordercolor="#eee">
	<tbody>
		<?php if ( $booking->get_product() ) : ?>
			<tr>
				<th scope="row" style="text-align:left; border: 1px solid #eee;"><?php esc_html_e( 'Booked Product', 'dokan' ); ?></th>
				<td style="text-align:left; border: 1px solid #eee;"><?php echo esc_html( wptexturize( $booking->get_product()->get_title() ) ); ?></td>
			</tr>
		<?php endif; ?>
		<tr>
			<th style="text-align:left; border: 1px solid #eee;" scope="row"><?php esc_html_e( 'Booking ID', 'dokan' ); ?></th>
			<td style="text-align:left; border: 1px solid #eee;"><?php echo $booking->get_id(); ?></td>
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

<p>
    <?php
    $dashboard_text = __( 'View and edit this booking in the dashboard', 'dokan' );
    echo wp_kses_post(
        make_clickable(
            sprintf(
                // translators: 1) Dashboard URL, 2) URL Text.
                '<a href="%1$s">%2$s</a>',
                add_query_arg( 'booking_id', $booking->get_id(), dokan_get_navigation_url( 'booking/booking-details' ) ),
                $dashboard_text
            )
        )
    );
    ?>
</p>

<?php do_action( 'woocommerce_email_footer' ); ?>
