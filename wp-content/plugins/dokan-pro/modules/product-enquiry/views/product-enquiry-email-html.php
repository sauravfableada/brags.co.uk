<?php

defined( 'ABSPATH' ) || exit;

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo esc_html( wp_strip_all_tags( wptexturize( wp_kses_post( $message ) ) ) );

echo "\n\n----------------------------------------\n\n";

printf( __( 'Enquiry Summary:', 'dokan' ) );
echo "\n\n";
// translators: %1: Customer name, %2: Customer email.
printf( __( 'From: %1$s - %2$s', 'dokan' ), esc_html( wptexturize( $customer_name ) ), esc_html( wptexturize( $customer_email ) ) );
echo "\n";
// translators: %s: Product title
printf( __( 'Product: %s', 'dokan' ), esc_html( wptexturize( $product->get_title() ) ) );
echo "\n";
// translators: %s: Product URL
printf( __( 'Product URL: %s', 'dokan' ), esc_url( $product->get_permalink() ) );

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( ! empty( $additional_content ) ) {
    echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
    echo "\n\n----------------------------------------\n\n";
}

echo esc_html( wp_strip_all_tags( wptexturize( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ) ) );
