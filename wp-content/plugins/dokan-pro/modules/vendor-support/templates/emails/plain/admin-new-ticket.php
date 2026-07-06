<?php
/**
 * Admin New Support Ticket Email (Plain Text)
 *
 * @package Dokan/VendorSupport/Templates/Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

echo '= ' . esc_html( wp_strip_all_tags( wptexturize( $email_heading ) ) ) . " =\n\n";

// Fallback to 'Unknown Vendor' if vendor_name is not set

$vendor_name = ! empty( $vendor_name ) ? esc_html( $vendor_name ) : __( 'Unknown Vendor', 'dokan' );
// translators: %s vendor name
printf( esc_html__( '%s asked about a query which is detailed as follows:', 'dokan' ), $vendor_name );
echo "\n\n";

// Fallback to 'No details provided' if ticket_details is not set
$ticket_details = ! empty( $ticket_details ) ? $ticket_details : __( 'No details provided.', 'dokan' );
echo esc_html( wp_strip_all_tags( wptexturize( $ticket_details ) ) );
echo "\n\n";

echo "-\n\n";

// Use consistent URL structure with vendor_support_email.php
$ticket_id = isset( $ticket_id ) ? absint( $ticket_id ) : 0;
$admin_ticket_url = isset( $vendor_support_url ) ? esc_url( $vendor_support_url ) : admin_url( 'admin.php?page=dokan-vendor-support&ticket_id=' . $ticket_id );
// translators: %s url
printf( esc_html__( 'To reply to this conversation please view it on %s', 'dokan' ), esc_url( $admin_ticket_url ) );
echo "\n\n";

echo esc_html__( 'This is an automated message. Please do not reply to this email.', 'dokan' );
echo "\n\n";

echo esc_html( wp_strip_all_tags( wptexturize( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ) ) );
