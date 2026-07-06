<?php
/**
 * RMA Status Changed Email Template
 *
 * @package dokan
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$customer_name  = $replace['{customer_name}'] ?? '';
$vendor_name    = $replace['{vendor_name}'] ?? '';
$updated_status = $replace['{updated_status}'] ?? '';
$request_id     = $replace['{request_id}'] ?? '';
$order_number   = $replace['{order_number}'] ?? '';
$request_link = esc_url( wc_get_account_endpoint_url( 'view-rma-requests' ) ) . $request_id;

// WooCommerce "View Order" link.
$order_link = esc_url( wc_get_endpoint_url( 'view-order', $order_number, wc_get_page_permalink( 'myaccount' ) ) );

// Defensive defaults for undefined template vars.
$email_heading = isset( $email_heading ) ? $email_heading : '';
$email         = isset( $email ) ? $email : null;
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo esc_attr( get_option( 'blog_charset' ) ); ?>" />
</head>
<body>
    <?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

    <p>
        <?php
        /* translators: %s: customer's display name (e.g. "John"). */
        printf( esc_html__( 'Hi %s,', 'dokan' ), esc_html( $customer_name ) );
        ?>
    </p>

    <p>
        <?php
        printf(
           /* translators: %1$s: Order link; %2$s: Order number; %3$s: Vendor name. */
            wp_kses(
                __( 'This is an email to notify you that your Return/Warranty request for Order <a href="%1$s">#%2$s</a> has been updated by the vendor, %3$s.', 'dokan' ),
                array( 'a' => array( 'href' => array() ) )
            ),
            esc_url( $order_link ),
            esc_html( $order_number ),
            esc_html( $vendor_name )
        );
        ?>
    </p>

    <p><?php esc_html_e( 'Here are the details of the update:', 'dokan' ); ?></p>

    <ul>
        <li>
            <?php
            /* translators: %s: The request ID (numeric). */
            printf( esc_html__( 'Request ID: #%s', 'dokan' ), esc_html( $request_id ) );
            ?>
        </li>
        <li>
            <?php
            /* translators: %s: The updated status (e.g. "Approved", "Rejected"). */
            printf( esc_html__( 'Status: %s', 'dokan' ), esc_html( $updated_status ) );
            ?>
        </li>
    </ul>

    <p><?php esc_html_e( 'No further action is required from you at this time. You can view the full details and history of your request by visiting your account.', 'dokan' ); ?></p>

    <p>
        <a class="button" href="<?php echo esc_url( $request_link ); ?>">
            <?php esc_html_e( 'View Your RMA Request', 'dokan' ); ?>
        </a>
    </p>

    <p><?php esc_html_e( 'Thank you for your patience.', 'dokan' ); ?></p>

    <?php do_action( 'woocommerce_email_footer', $email ); ?>
</body>
</html>
