<?php
defined( 'ABSPATH' ) || exit; // Exit if called directly

echo '= ' . esc_html( wp_strip_all_tags( $email_heading ) ) . " =\n\n";

// translators: 1) name of the blog, 2) link to checkout payment url, note: no full stop due to url at the end
printf( esc_html_x( 'The automatic payment to renew your subscription with %1$s has failed. To reactivate the subscription, please login and authorize the renewal from your account page: %2$s', 'In failed renewal authentication email', 'dokan' ), esc_html( get_bloginfo( 'name' ) ), esc_attr( $authorization_url ) );

echo "\n\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";

do_action( 'woocommerce_subscriptions_email_order_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( ! empty( $additional_content ) ) {
    echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
    echo "\n\n----------------------------------------\n\n";
}

echo esc_html( wp_strip_all_tags( wptexturize( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ) ) );
