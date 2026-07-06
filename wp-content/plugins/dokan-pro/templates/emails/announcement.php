<?php
/**
 * Announcement email to vendors.
 *
 * An email sent to the vendor(s) when a announcement is created by admin.
 *
 * @class       Dokan_Email_Announcement
 * @version     2.6.8
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

do_action( 'woocommerce_email_header', $email_heading, $email ); ?>
<p>
    <?php echo wp_kses_post( $announcement_body ); ?>
</p>
<hr>
<p>
    <?php
    // translators: %s dashboard url.
    echo wp_kses_post( sprintf( __( 'You can check this announcement in dashboard by clicking <a href="%s">here<a/>.', 'dokan' ), $data['{announcement_url}'] ) );
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
