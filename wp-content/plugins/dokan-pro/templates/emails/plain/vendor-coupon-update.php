<?php
/**
 * Vendor Coupon Update email.
 *
 * An email sent to the vendor(s) when a coupon is updated by the admin.
 *
 * @class Dokan_Email_Vendor_Coupon_Update
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/* translators: %s: coupon code */
printf( esc_html__( 'Your coupon %1$s was updated by %2$s.', 'dokan' ), esc_html( $data['coupon_code'] ), esc_html( $data['site_name'] ) );
echo "\n\n";
echo esc_html__( 'Review the updated coupon details here:', 'dokan' );
echo "\n";
echo esc_url( $data['coupon_edit_url'] );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( ! empty( $additional_content ) ) {
    echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
    echo "\n\n----------------------------------------\n\n";
}

echo esc_html( wp_strip_all_tags( wptexturize( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ) ) );
