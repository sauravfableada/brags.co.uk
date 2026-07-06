<?php
/**
 * Customer booking confirmed email
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

echo '= ' . esc_html( wp_strip_all_tags( $email_heading ) ) . " =\n\n";

esc_html_e( 'The following booking has been cancelled by the customer. The details of the cancelled booking can be found below.', 'dokan' );
echo "\n\n";

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";
// translators: Booking Title.
echo esc_html( wptexturize( sprintf( __( 'Booked: %s', 'dokan' ), $booking->get_product()->get_title() ) ) ) . "\n";
// translators: Booking Title.
echo sprintf( __( 'Booking ID: %s', 'dokan' ), $booking->get_id() ) . "\n";

if ( $booking->has_resources() && $booking->get_resource() ) {
    // translators: Booking Type.
	echo esc_html( wptexturize( sprintf( __( 'Booking Type: %s', 'dokan' ), $booking->get_resource()->post_title ) ) ) . "\n";
}
// translators: Booking Start Date.
echo esc_html( wptexturize( sprintf( __( 'Booking Start Date: %s', 'dokan' ), $booking->get_start_date() ) ) ) . "\n";
// translators: Booking End Date.
echo esc_html( wptexturize( sprintf( __( 'Booking End Date: %s', 'dokan' ), $booking->get_end_date() ) ) ) . "\n";

if ( $booking->has_persons() ) {
	foreach ( $booking->get_persons() as $person_id => $qty ) {
		if ( 0 === $qty ) {
			continue;
		}

		$person_type = ( 0 < $person_id ) ? get_the_title( $person_id ) : __( 'Person(s)', 'dokan' );
        // translators: 1) Booking person type, 2) booking person quantity.
		echo sprintf( __( '%1$s: %2$d', 'dokan' ), $person_type, $qty ) . "\n";
	}
}

echo "\n\n";
esc_html_e( 'View and edit this booking in the dashboard by following the URL below', 'dokan' );
echo "\n\n";
echo esc_url( add_query_arg( 'booking_id', $booking->get_id(), dokan_get_navigation_url( 'booking/booking-details' ) ) );
echo "\n\n";
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( ! empty( $additional_content ) ) {
    echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
    echo "\n\n----------------------------------------\n\n";
}

echo esc_html( wp_strip_all_tags( wptexturize( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ) ) );
