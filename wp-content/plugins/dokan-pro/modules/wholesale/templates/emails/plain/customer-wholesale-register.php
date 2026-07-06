<?php
/**
 * Admin new wholesale customer email
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo '= ' . esc_html( wp_strip_all_tags( $email_heading ) ) . " =\n\n";

$opening_paragraph = __( 'A customer has been request for beign wholesale. and is awaiting your approval. The details of this  are as follows:', 'dokan' );

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";
// translators: %s: customer name.
echo sprintf( __( 'User Name: %s', 'dokan' ), $user->display_name ) . "\n";
// translators: %s: customer email.
echo sprintf( __( 'User Email: %s', 'dokan' ), $user->user_email ) . "\n";

// translators: %s: customer NiceName.
echo sprintf( __( 'User NiceName: %s', 'dokan' ), $user->user_nicename ) . "\n";
// translators: %s: customer spending amount.
echo sprintf( __( 'User Total Spent: %s', 'dokan' ), (int) wc_get_customer_total_spent( $user->ID ) ) . "\n";


echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

esc_html_e( 'View and edit this this request in teh admin panel by following the link below', 'dokan' );

echo esc_url( untrailingslashit( admin_url() ) . '?page=dokan#/wholesale-customer' );

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( ! empty( $additional_content ) ) {
    echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
    echo "\n\n----------------------------------------\n\n";
}

echo esc_html( wp_strip_all_tags( wptexturize( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ) ) );
