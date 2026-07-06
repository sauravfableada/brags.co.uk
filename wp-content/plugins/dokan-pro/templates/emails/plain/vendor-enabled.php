<?php
/**
 * Vendor enable email to vendors.
 *
 * An email sent to the vendor(s) when a he or she is enabled by the admin
 *
 * @class       Dokan_Email_Vendor_Enable
 * @version     2.7.6
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

echo '= ' . esc_html( wp_strip_all_tags( $email_heading ) ) . " =\n\n";
?>

    ------------------------------------------------------------

<?php
// translators: %s, Vendor Name
printf( __( 'Congratulations %s!', 'dokan' ), $data['{display_name}'] );
echo " \n\n";
?>

    ------------------------------------------------------------

<?php
esc_html_e( 'Your vendor account is activated', 'dokan' );
echo " \n\n";
esc_html_e( 'You can login by using the URL below.', 'dokan' );
echo " \n\n";
echo esc_url( wc_get_page_permalink( 'myaccount' ) );
echo " \n\n";

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";


/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( ! empty( $additional_content ) ) {
    echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
    echo "\n\n----------------------------------------\n\n";
}

echo esc_html( wp_strip_all_tags( wptexturize( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ) ) );
