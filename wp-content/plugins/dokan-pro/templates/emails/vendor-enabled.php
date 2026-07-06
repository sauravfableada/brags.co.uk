<?php
/**
 * Vendor enable email to vendors.
 *
 * An email sent to the vendor(s) when a he or she is enabled by the admin
 *
 * @class    Dokan_Email_Vendor_Enable
 * @version  2.7.6
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

do_action( 'woocommerce_email_header', $email_heading, $email ); ?>
    <p>
        <?php
        // translators: %s, Vendor Name.
        printf( __( 'Congratulations %s!', 'dokan' ), $data['{display_name}'] );
        ?>
    </p>
    <p>
        <?php esc_html_e( 'Your vendor account is activated', 'dokan' ); ?>
    </p>
    <p>
        <?php
        // translators: %s, WooCommerce my account page URL.
        printf( __( 'You can <a href="%s" target="_blank">login here</a> ', 'dokan' ), wc_get_page_permalink( 'myaccount' ) );
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
