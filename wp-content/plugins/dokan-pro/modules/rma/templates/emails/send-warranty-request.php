<?php
/**
 * New RMA Warranty Request Email.
 *
 * An email sent to the vendor when a request is sent to vendor.
 *
 * @var array $data Email info.
 */

defined( 'ABSPATH' ) || exit;

$replace = isset( $replace ) && is_array( $replace ) ? $replace : array();

$details       = $data['details'] ?? '';
$request_type  = ! empty( $data['type'] ) ? ucwords( $data['type'] ) : '';
$order_id      = $data['order_id'] ?? '';
$reason        = ! empty( $data['reasons'] ) ? dokan_rma_refund_reasons( $data['reasons'] ) : '';
$customer_name = $replace['{customer_name}'] ?? '';
$order_link    = esc_url(
    add_query_arg(
        [
            'order_id'   => $order_id,
            '_view_mode' => 'email',
            'permission' => '1',
        ], dokan_get_navigation_url( 'orders' )
    )
);
?>

<?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p><?php esc_html_e( 'Hello,', 'dokan' ); ?></p>

<p>
    <?php
    // translators: Customer name.
    printf( esc_html__( 'A new refunds and return request is made by %s', 'dokan' ), esc_html( $customer_name ) );
    ?>
</p>

<p><?php esc_html_e( 'Summary of the Refund Request:', 'dokan' ); ?></p>
<hr>

<p>
    <?php
    // translators: Request Type.
    printf( __( 'Request Type: %s', 'dokan' ), $request_type );
    ?>
</p>
<p>
    <?php
    // translators: Request Details.
    printf( __( 'Request Details: %s', 'dokan' ), $details );
    ?>
</p>
<p>
    <?php
    // translators: 1) Order URL, 2) Order ID.
    printf( __( 'Order ID: <a target="_blank" href="%1$s">%2$s</a>', 'dokan' ), $order_link, $order_id );
    ?>
</p>
<p>
    <?php
    // translators: Reason.
    printf( __( 'Reason: %s', 'dokan' ), $reason );
    ?>
</p>

<?php
/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
    echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}
do_action( 'woocommerce_email_footer', $email );
