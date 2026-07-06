<?php
/**
 * Vendor Support Ticket Reply Email
 *
 * This template can be overridden by copying it to yourtheme/dokan/emails/vendor-ticket-reply.php.
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
    // Fallback to 'Admin' if admin_name is not set
    $admin_name = isset( $admin_name ) ? esc_html( $admin_name ) : __( 'Admin', 'dokan' );
    // translators: %s admin name
    printf( esc_html__( '%s replied to your query which is detailed as follows:', 'dokan' ), $admin_name );
    ?>
</p>

<p>
    <?php
    // Fallback to empty string if ticket_details is not set
    $ticket_details = isset( $ticket_details ) ? $ticket_details : '';
    echo wp_kses_post( $ticket_details ? nl2br( $ticket_details ) : __( 'No details provided.', 'dokan' ) );
    ?>
</p>

<p>-</p>

<p>
    <?php
    // Fallback to 0 if ticket_id is not set
    $ticket_id = isset( $ticket_id ) ? absint( $ticket_id ) : 0;
    $admin_support_url = isset( $admin_support_url ) ? $admin_support_url : dokan_get_navigation_url( 'admin-support' ) . '?view=' . $ticket_id;
    printf(
        // translators: %s url
        wp_kses_post( __( 'To reply to this conversation please view it on %s', 'dokan' ) ),
        '<a href="' . esc_url( $admin_support_url ) . '">' . esc_html__( 'Admin Support', 'dokan' ) . '</a>'
    );
    ?>
</p>

<p style="font-size: 12px; color: #888;">
    <?php esc_html_e( 'This is an automated message. Please do not reply to this email.', 'dokan' ); ?>
</p>

<?php do_action( 'woocommerce_email_footer', $email ); ?>
