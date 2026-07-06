<?php
/**
 * Vendor Support Ticket Reply Email (Plain Text)
 *
 * This template can be overridden by copying it to yourtheme/dokan/emails/plain/vendor-ticket-reply.php.
 *
 * @package Dokan/VendorSupport/Templates/Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

echo '= ' . esc_html( wp_strip_all_tags( wptexturize( $email_heading ) ) ) . " =\n\n";

// Fallback to 'Admin' if admin_name is not set
$admin_name = isset( $admin_name ) ? esc_html( $admin_name ) : __( 'Admin', 'dokan' );
// translators: %s admin name
printf( esc_html__( '%s replied to your query which is detailed as follows:', 'dokan' ), $admin_name );
echo "\n\n";

// Fallback to empty string if ticket_details is not set
$ticket_details = isset( $ticket_details ) ? $ticket_details : '';
echo esc_html( wp_strip_all_tags( wptexturize( $ticket_details ? $ticket_details : __( 'No details provided.', 'dokan' ) ) ) );
echo "\n\n";

echo "-\n\n";

// Fallback to 0 if ticket_id is not set
$ticket_id = isset( $ticket_id ) ? absint( $ticket_id ) : 0;
$admin_support_url = isset( $admin_support_url ) ? $admin_support_url : dokan_get_navigation_url( 'admin-support' ) . '?view=' . $ticket_id;
// translators: %s url
printf( esc_html__( 'To reply to this conversation please view it on %s', 'dokan' ), esc_url( $admin_support_url ) );
echo "\n\n";

echo esc_html__( 'This is an automated message. Please do not reply to this email.', 'dokan' );
echo "\n\n";

echo esc_html( wp_strip_all_tags( wptexturize( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ) ) );
