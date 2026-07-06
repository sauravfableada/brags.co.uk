<?php
/**
 * Subscription Expiry Alert to Vendor Email (Plain Text)
 *
 * @since 1.0.0
 *
 * An email is sent to vendor before a subscription expires.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;  
}
if ( ! $vendor ) {
    return;
}

echo '= ' . esc_html( wp_strip_all_tags( wptexturize( $email_heading ) ) ) . " =\n\n";


esc_html_e( 'Dear subscriber, Your subscription will be ending soon. Please renew your package in a timely manner for continued usage.', 'dokan' );
echo " \n\n";

if ( $subscription ) {
    printf( __( 'Here are your subscription pack details:', 'dokan' ) );
    echo " \n";
    echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

    // translators: %s Subscription Pack.
    printf( '<p>%s</p>', sprintf( __( 'Subscription Pack: %s', 'dokan' ), $subscription->get_package_title() ) );
    echo " \n";

    // translators: %s Price.
    printf( '<p>%s</p>', sprintf( __( 'Price: %s', 'dokan' ), esc_html( wp_strip_all_tags( wptexturize( dokan()->email->currency_symbol( $subscription->get_price() ) ) ) ) ) );
    echo " \n";
}

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( ! empty( $additional_content ) ) {
    echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
    echo "\n\n----------------------------------------\n\n";
}

echo esc_html( wp_strip_all_tags( wptexturize( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ) ) );
