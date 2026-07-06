<?php
/**
 * New Coupon Email
 *
 * An email is sent to admin and customer when a vendor generate a coupon
 *
 * @var WC_Coupon $coupon Coupon.
 * @var array $data Array of rma data.
 *
 * @version 4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! $coupon ) {
    return;
}

$my_account_link = wc_get_page_permalink( 'myaccount' );
$rma_id          = $data['{rma_id}'];
$rma_link        = $my_account_link . "/view-rma-requests/{$rma_id}/";

do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p><?php esc_html_e( 'Hello,', 'dokan' ); ?></p>

<p> <?php esc_html_e( 'A new coupon is generated for you.', 'dokan' ); ?> </p>

<p><?php esc_html_e( 'Summary of the Coupon:', 'dokan' ); ?></p>
<hr>

<p>
    <?php
    // translators: Coupon Code.
    printf( __( 'Coupon Code: %s', 'dokan' ), $coupon->get_code() );
    ?>
</p>
<p>
    <?php
    // translators: Coupon Code.
    printf( __( 'Coupon Amount: %s', 'dokan' ), $coupon->get_amount() );
    ?>
</p>

<?php if ( ! current_user_can( 'manage_woocommerce' ) ) : ?>
    <p>
        <?php
        // translators: Customer RMA Request Details URl.
        printf( __( '<a target="_blank" href="%s">Click Here to See Details</a>', 'dokan' ), $rma_link );
        ?>
    </p>
<?php endif; ?>

<?php
/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
    echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}
do_action( 'woocommerce_email_footer', $email );
