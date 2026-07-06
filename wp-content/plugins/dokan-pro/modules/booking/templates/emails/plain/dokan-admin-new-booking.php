<?php
/**
 * Admin new booking email
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo '= ' . esc_html( wp_strip_all_tags( $email_heading ) ) . " =\n\n";

if ( wc_booking_order_requires_confirmation( $booking->get_order() ) && $booking->get_status() === 'pending-confirmation' ) {
    // translators: Booking Customer Name.
	$opening_paragraph = __( 'A booking has been made by %s and is awaiting your approval. The details of this booking are as follows:', 'dokan' );
} else {
    // translators: Booking Customer Name.
	$opening_paragraph = __( 'A new booking has been made by %s. The details of this booking are as follows:', 'dokan' );
}

if ( $booking->get_order() && $booking->get_order()->get_billing_first_name() && $booking->get_order()->get_billing_last_name() ) {
	echo sprintf( $opening_paragraph, $booking->get_order()->get_billing_first_name() . ' ' . $booking->get_order()->get_billing_last_name() ) . "\n\n";
}

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

// translators: Booking Product Title.
echo sprintf( __( 'Booked: %s', 'dokan' ), wptexturize( $booking->get_product()->get_title() ) ) . "\n";
// translators: Booking ID.
echo sprintf( __( 'Booking ID: %s', 'dokan' ), $booking->get_id() ) . "\n";

if ( $booking->has_resources() && ( $booking->get_resource() ) ) {
    // translators: Booking Type.
	echo sprintf( __( 'Booking Type: %s', 'dokan' ), $booking->get_resource()->post_title ) . "\n";
}
// translators: Booking Start Date.
echo sprintf( __( 'Booking Start Date: %s', 'dokan' ), $booking->get_start_date() ) . "\n";
// translators: Booking End Date.
echo sprintf( __( 'Booking End Date: %s', 'dokan' ), $booking->get_end_date() ) . "\n";

if ( $booking->has_persons() ) {
	foreach ( $booking->get_persons() as $person_id => $qty ) {
		if ( 0 === $qty ) {
			continue;
		}

		$person_type = ( 0 < $person_id ) ? get_the_title( $person_id ) : __( 'Person(s)', 'dokan' );
        // translators: 1) Booking Person Type, 2) Booking Person Quantity.
		echo sprintf( __( '%1$s: %2$d', 'dokan' ), wptexturize( $person_type ), $qty ) . "\n";
	}
}

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

if ( wc_booking_order_requires_confirmation( $booking->get_order() ) && $booking->get_status() === 'pending-confirmation' ) {
	echo esc_html__( 'This booking is awaiting your approval. Please check it and inform the customer if the date is available or not.', 'dokan' ) . "\n\n";
}

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( ! empty( $additional_content ) ) {
    echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
    echo "\n\n----------------------------------------\n\n";
}

echo esc_html( wp_strip_all_tags( wptexturize( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ) ) );
