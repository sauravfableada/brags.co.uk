<?php
/**
 * MangoPay disconnect notification email
 *
 *
 * @var $vendor_name
 * @var $email_heading
 * @var $email
 *
 * @package dokan
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$vendor_name        = isset( $vendor_name ) ? $vendor_name : '';
$email_heading      = isset( $email_heading ) ? $email_heading : esc_html__( 'MangoPay Account Disconnected', 'dokan' );

do_action( 'woocommerce_email_header', $email_heading, $email );
?>

    <p>
        <?php
        printf(
        // Translators: %s is the vendor's name.
            esc_html__( 'Hi %s,', 'dokan' ), $vendor_name
        );
        ?>
    </p>

    <p><?php esc_html_e( 'Your Dokan MangoPay account got disconnected because of credential update. Kindly check again and connect your MangoPay account to resume payment related operations through MangoPay.', 'dokan' ); ?></p>


    <p><?php esc_html_e( 'Thank You', 'dokan' ); ?></p>

<?php
do_action( 'woocommerce_email_footer', $email );
