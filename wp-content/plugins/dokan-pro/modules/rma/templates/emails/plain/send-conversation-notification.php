<?php
/**
 * New Conversation Notification Email.
 * An email sent to the vendor or customer when a warranty request conversation is made by customer or vendor.
 *
 * @class       Dokan_Rma_Conversation_Notification
 * @version     3.9.5
 *
 * @var array $data Email info.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$to_name   = $data['to_name'] ?? '';
$from_name = $data['from_name'] ?? '';
$message   = $data['message'] ?? '';
$rma_url   = $data['rma_url'] ?? '';

echo '= ' . esc_html( wp_strip_all_tags( $email_heading ) ) . " =\n\n";

// translators: %s from name.
echo esc_html( sprintf( __( 'Hello %s,', 'dokan' ), $to_name ) );
echo " \n";

// translators: %s from name.
echo esc_html( sprintf( __( 'A new reply has been added to the conversation by %s', 'dokan' ), $from_name ) );
echo " \n";

echo esc_html( wp_strip_all_tags( wptexturize( $message ) ) );
echo " \n\n";

// translators: %s dashboard url.
esc_html_e( 'You can check this reply by clicking the url below.', 'dokan' );
echo esc_url( $rma_url );
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
