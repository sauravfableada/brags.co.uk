<?php
/**
 * Staff Account Notification Email Template for Dokan Pro
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package Dokan Pro
 * @subpackage Vendor Staff
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p style="font-size: 16px; color: #333333;">

    <?php
        /* translators: 1: Username */
        printf( esc_html__( 'Hello %s,', 'dokan' ), esc_html( $user_login ) );
    ?>
</p>

<div>
    <p style="font-size: 16px; margin: 0px; padding: 0px">
        <?php esc_html_e( 'Your account has been created successfully!', 'dokan' ); ?>
    </p>

    <p style="font-size: 16px; margin: 0px; padding: 0px">
        <strong><?php esc_html_e( 'Username:', 'dokan' ); ?></strong>
        <?php echo esc_html( $user_login ); ?>
    </p>

    <p style="font-size: 16px; margin: 0px; padding: 0px">
        <?php esc_html_e( 'To set your password, please click the button below:', 'dokan' ); ?>
    </p>

    <a href="<?php echo esc_url( $password_reset_url ); ?>" target="_blank" class="button" style="text-decoration: none; margin-top: 20px; font-size: 16px">
        <?php esc_html_e( 'Set Password', 'dokan' ); ?>
    </a>
</div>

<p>
    <?php
    /**
     * Show user-defined additional content - this is set in each email's settings.
     */
    if ( $additional_content ) {
        echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
    }
    ?>
</p>
<?php
/**
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );
