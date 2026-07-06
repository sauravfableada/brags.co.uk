<?php
/**
 * Vendor Verification Request Submission Email.
 *
 * An email sent to the admin when a vendor submit a document for verification.
 *
 * @since 3.7.23
 */

defined( 'ABSPATH' ) || exit;

echo '= ' . esc_html( wp_strip_all_tags( $email_heading ) ) . " =\n\n";
// translators: 1: Newline character.
echo sprintf( __( 'Hi, %1$s', 'dokan' ), " \n\n" );
// translators: 1: Store name, 2: Newline character.
echo sprintf( __( 'A new verification request has been made by %1$s %2$s.', 'dokan' ), wp_strip_all_tags( $store_name ), " \n\n" );
// translators: 1: Newline character, 2: Admin URL, 3: Newline character.
echo sprintf( __( 'You can approve or reject it by going to the Dokan Admin Dashboard by following the URL Below %1$s %2$s %3$s', 'dokan' ), " \n", esc_url( $admin_url ), " \n\n" );

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( ! empty( $additional_content ) ) {
    echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
    echo "\n\n----------------------------------------\n\n";
}

echo esc_html( wp_strip_all_tags( wptexturize( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ) ) );
