<?php
/**
 * Staff Account Notification Email Template for Dokan Pro
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package Dokan Pro
 * @subpackage Vendor Staff
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

echo esc_html( wp_strip_all_tags( $email_heading ) ) . "\n\n";

/* translators: 1: Username, 2: Newline character. */
printf( esc_html__( 'Hello %1$s, %2$s', 'dokan' ), esc_html( $user_login ), "\n\n" );

echo esc_html__( 'Your account has been created successfully!', 'dokan' ) . "\n\n";
/* translators: 1: Username, 2: Newline character. */
printf( esc_html__( 'Username: %1$s %2$s', 'dokan' ), esc_html( $user_login ), "\n\n" );

echo esc_html__( 'To set your password, please click the button below:', 'dokan' ) . "\n\n";

echo esc_url( $password_reset_url ) . "\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
    echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
}

echo "\n\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );
