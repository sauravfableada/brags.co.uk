<?php
/**
 * Refund Canceled Email.
 *
 * An email sent to the vendor when a refund request get canceled.
 *
 * @class   Dokan_Email_Refund_Canceled_Vendor
 * @version 3.3.6
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
do_action( 'woocommerce_email_header', $email_heading, $email );
?>
<p>
    <?php
    // translators: 1: Seller name, 2: Newline character.
    echo esc_html( sprintf( __( 'Hi %1$s,', 'dokan' ), $seller_name ) );
    ?>
</p>
<p>
    <?php
    // translators: 1: Refund Request Status, 2: Newline character.
    echo esc_html( sprintf( __( 'Your refund request is %1$s.', 'dokan' ), $status ) );
    ?>
</p>
<hr>
<p>
    <?php
    // translators: 1: Order ID, 2: Newline character.
    echo esc_html( sprintf( __( 'Order ID : %1$s', 'dokan' ), $order_id ) ) . "\n";
    ?>
    <?php
    // translators: 1: Refund amount, 2: Newline character.
    echo esc_html( sprintf( __( 'Refund Amount : %1$s', 'dokan' ), $amount ) ) . "\n";
    ?>
    <?php
    // translators: 1: Refund reason, 2: Newline character.
    echo esc_html( sprintf( __( 'Refund Reason : %1$s', 'dokan' ), $reason ) ) . "\n";
    ?>
</p>
<p>
    <?php
    // translators: 1: Order page URL, 2: Newline character.
    echo wp_kses_post( sprintf( __( 'You can view the order details by clicking <a href="%1$s">here</a>%2$s', 'dokan' ), $order_link, " \n" ) );
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
