<?php
/**
 * Admin New Support Ticket Email
 *
 * @package Dokan/VendorSupport/Templates/Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

do_action( 'woocommerce_email_header', $email_heading, $email );
?>

<p>
    <?php
    // Fallback to 'Unknown Vendor' if vendor_name is not set
    $vendor_name = ! empty( $vendor_name ) ? esc_html( $vendor_name ) : __( 'Unknown Vendor', 'dokan' );
    // translators: %s vendor name
    printf( esc_html__( '%s asked about a query which is detailed as follows:', 'dokan' ), $vendor_name );
    ?>
</p>

<p>
    <?php
    // Fallback to 'No details provided' if ticket_details is not set
    $ticket_details = ! empty( $ticket_details ) ? $ticket_details : __( 'No details provided.', 'dokan' );
    echo wp_kses_post( nl2br( $ticket_details ) );
    ?>
</p>

<p>-</p>

<p>
    <?php
    // Use consistent URL structure with vendor_support_email.php
    $ticket_id = isset( $ticket_id ) ? absint( $ticket_id ) : 0;
    $admin_ticket_url = isset( $vendor_support_url ) ? esc_url( $vendor_support_url ) : admin_url( 'admin.php?page=dokan-vendor-support&ticket_id=' . $ticket_id );
    printf(
        // translators: %s url
        wp_kses_post( __( 'To reply to this conversation please view it on %s', 'dokan' ) ),
        '<a href="' . esc_url( $admin_ticket_url ) . '">' . esc_html__( 'Vendor Support', 'dokan' ) . '</a>'
    );
    ?>
</p>

<p style="font-size: 12px; color: #888;">
    <?php esc_html_e( 'This is an automated message. Please do not reply to this email.', 'dokan' ); ?>
</p>

<?php do_action( 'woocommerce_email_footer', $email ); ?>
