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
do_action( 'woocommerce_email_header', $email_heading, $email );
?>
<p>
    <?php
    // translators: %s Seller name.
    echo esc_html( sprintf( __( 'Hi %s', 'dokan' ), $data['{seller_name}'] ) );
    ?>
</p>
<p>
    <?php
    // translators: %s Status.
    echo esc_html( sprintf( __( 'Your refund request is %s', 'dokan' ), $data['{status}'] ) );
    ?>
</p>
<hr>
<p>
    <?php
    // translators: %s Order ID.
    echo esc_html( sprintf( __( 'Order ID :  %s, ', 'dokan' ), $data['{order_id}'] ) );
    // translators: %s Refund Amount.
    echo esc_html( sprintf( __( 'Refund Amount :  %s, ', 'dokan' ), $data['{amount}'] ) );
    // translators: %s Refund Reason.
    echo esc_html( sprintf( __( 'Refund Reason :  %s ', 'dokan' ), $data['{reason}'] ) );
    ?>
</p>
<p>
    <?php
    // translators: %s Order details URL.
    echo wp_kses_post( sprintf( __( 'You can view the order details by clicking <a href="%s">here</a>', 'dokan' ), $data['{order_link}'] ) );
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
