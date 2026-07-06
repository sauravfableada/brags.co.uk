<?php
/**
 * Refund processed Email.
 *
 * An email sent to the vendor when a refund request is processed by admin.
 *
 * @class       Dokan_Email_Refund_Vendor
 * @version     2.6.6
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

echo '= ' . esc_html( wp_strip_all_tags( $email_heading ) ) . " =\n\n";

// translators: %s Vendor Name.
echo esc_html( sprintf( __( 'Hi %s', 'dokan' ), $data['{seller_name}'] ) );
echo " \n";

// translators: %s Refund Status.
echo esc_html( sprintf( __( 'Your refund request is  %s', 'dokan' ), $data['{status}'] ) );
echo " \n";

// translators: %s Order Id.
echo esc_html( sprintf( __( 'Order ID : %s', 'dokan' ), $data['{order_id}'] ) );
echo " \n";

// translators: %s Refund Amount.
echo esc_html( sprintf( __( 'Refund Amount :  %s', 'dokan' ), $data['{amount}'] ) );
echo " \n";

// translators: %s Refund Reason
echo esc_html( sprintf( __( 'Refund Reason :  %s', 'dokan' ), $data['{reason}'] ) );
echo " \n";

esc_html_e( 'You can view the order details by visiting the url below.', 'dokan' );
echo esc_url( $data['{order_link}'] );
echo " \n";

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( ! empty( $additional_content ) ) {
    echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
    echo "\n\n----------------------------------------\n\n";
}

echo esc_html( wp_strip_all_tags( wptexturize( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ) ) );
