<?php
/**
 * Vendor Coupon Update email
 *
 * An email sent to the vendor(s) when a coupon is updated by the admin.
 *
 * @class       Dokan_Email_Vendor_Coupon_Update
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

do_action( 'woocommerce_email_header', $email_heading, $email ); ?>
<p>
    <?php
    /* translators: %s: coupon code */
    printf( esc_html__( 'Your coupon %1$s was updated by %2$s.', 'dokan' ), esc_html( $data['coupon_code'] ), esc_html( $data['site_name'] ) );
    ?>
</p>
<p>
    <?php esc_html_e( 'Review the updated coupon details here:', 'dokan' ); ?>
    <a href="<?php echo esc_url( $data['coupon_edit_url'] ); ?>"><?php esc_html_e( 'View Coupon', 'dokan' ); ?></a>
</p>
<?php
/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
    echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

do_action( 'woocommerce_email_footer', $email );
