<?php
/**
 * RMA Status Changed - Plain Text Template
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
?>

<?php
/* translators: %s: customer's display name (e.g. "John") - used in the greeting "Hi %s," */
printf( esc_html__( 'Hi %s,', 'dokan' ), esc_html( $customer_name ) );
?>

<?php
printf(
    /* translators: %1$s: order number (e.g. 12345); %2$s: vendor display name (e.g. "Vendor Name"). */
    esc_html__( 'This is an email to notify you that your Return/Warranty request for Order #%1$s has been updated by the vendor, %2$s.', 'dokan' ),
    esc_html( $order_number ),
    esc_html( $vendor_name )
);
?>

<?php esc_html_e( 'Here are the details of the update:', 'dokan' ); ?>

<?php
/* translators: %s: The request ID number (numeric), inserted into the string "Request ID: #%s". */
printf( esc_html__( 'Request ID: #%s', 'dokan' ), esc_html( $request_id ) );
?>

<?php
/* translators: %s: The updated status label (e.g. "Approved", "Rejected"). */
printf( esc_html__( 'Status: %s', 'dokan' ), esc_html( $updated_status ) );
?>

<?php esc_html_e( 'No further action is required from you at this time. You can view the full details and history of your request by visiting your account.', 'dokan' ); ?>

<?php
/* translators: %s: Link to the RMA request page */
printf( esc_html__( 'View Your RMA Request: %s', 'dokan' ), $request_link );
?>

<?php
esc_html_e( 'Thank you for your patience.', 'dokan' );
