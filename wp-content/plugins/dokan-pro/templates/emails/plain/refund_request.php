<?php
/**
 * New Refund request Email. (plain text)
 *
 * An email sent to the admin when a new refund request is created by vendor.
 *
 * @class       Dokan_Email_Refund_Request
 * @version     2.6.6
 *
 */

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
echo '= ' . esc_html( wp_strip_all_tags( $email_heading ) ) . " =\n\n";
?>

<?php esc_html_e( 'Hi', 'dokan' ); ?>,

<?php
// translators: %s is order id.
echo esc_html( sprintf( __( 'New refund request for order #%s', 'dokan' ), $data['{order_id}'] ) );
echo " \n";
?>

<?php
// translators: %s is refund url.
echo esc_html( sprintf( __( 'You can process the request by going here: %s', 'dokan' ), $data['{refund_url}'] ) );
echo " \n";
?>

<?php
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( ! empty( $additional_content ) ) {
    echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
    echo "\n\n----------------------------------------\n\n";
}

echo esc_html( wp_strip_all_tags( wptexturize( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ) ) );
