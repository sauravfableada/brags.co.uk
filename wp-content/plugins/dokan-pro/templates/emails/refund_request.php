<?php
/**
 * New Refund request Email.
 *
 * An email sent to the admin when a new refund request is created by vendor.
 *
 * @class       Dokan_Email_Refund_Request
 * @version     2.6.6
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

do_action( 'woocommerce_email_header', $email_heading, $email ); ?>
<p>
    <?php esc_html_e( 'Hi', 'dokan' ); ?>,
</p>
<p>
    <?php
    // translators: %s Order ID.
    echo esc_html( sprintf( __( 'New refund request for order #%s', 'dokan' ), $data['{order_id}'] ) );
    ?>
</p>
<p>
    <?php
    // translators: %s Request approval url.
    echo wp_kses_post( sprintf( __( 'You can process the request by going <a href="%s">here</a>', 'dokan' ), $data['{refund_url}'] ) );
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
