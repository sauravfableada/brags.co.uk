<?php
/**
 * New Coupon Email
 *
 * An email is sent to admin and customer when a vendor generate a coupon
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! $coupon ) {
    return;
}

$my_account_link = wc_get_page_permalink( 'myaccount' );
$rma_id          = $data['{rma_id}'];
$rma_link        = $my_account_link . "/view-rma-requests/{$rma_id}/";

echo '= ' . esc_html( wp_strip_all_tags( $email_heading ) ) . " =\n\n";

esc_html_e( 'Hello there,', 'dokan' );
echo " \n\n";

esc_html_e( 'A new coupon is generated for you.', 'dokan' );
echo " \n\n";

esc_html_e( 'Summary of the Coupon:', 'dokan' );
echo " \n\n";
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";
// translators: Coupon Code.
printf( __( 'Coupon Code: %s', 'dokan' ), $coupon->get_code() );
echo " \n";

// translators: Coupon Amount.
printf( __( 'Coupon Amount: %s', 'dokan' ), $coupon->get_amount() );
echo " \n";

if ( ! current_user_can( 'manage_woocommerce' ) ) {
    // translators: Customer RMA details page URL.
    printf( __( "Follow belows URL to See Details \n %s \n\n", 'dokan' ), esc_url( $rma_link ) );
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
