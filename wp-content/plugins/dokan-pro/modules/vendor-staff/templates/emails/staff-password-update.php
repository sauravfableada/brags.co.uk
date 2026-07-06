<?php
/**
 * Staff new order email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/admin-new-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @author  Dokan
 * @package WooCommerce/Templates/Emails/HTML
 * @version 3.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$text_align = is_rtl() ? 'right' : 'left';

/**
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email );
?>
<p style="text-align: <?php echo esc_attr( $text_align ); ?>">
    <?php
    // translators: %1$s is the name of the email recipient
    printf( __( 'Hello %1$s,', 'dokan' ), esc_html( $staff_name ) );
    ?>
</p>

<p style="text-align: <?php echo esc_attr( $text_align ); ?>">
    <?php
    // translators: %1$s is the store name
    printf( __( 'The notice is to inform you that, your password for the %1$s has been changed by the Vendor. Please contact your Vendor to collect the password to access your account.', 'dokan' ), $store_info );
    ?>
</p>

<p style="text-align: <?php echo esc_attr( $text_align ); ?>">
    <?php
    // translators: %1$s is the email address of the recipient
    printf( __( 'This email was sent to %1$s.', 'dokan' ), esc_html( $staff_email ) );
    ?>
</p>
<br>
<p style="text-align: <?php echo esc_attr( $text_align ); ?>">
    <?php
    /**
     * Show user-defined additional content - this is set in each email's settings.
     */
    if ( $additional_content ) {
        echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
    }

    // translators: %1$s is the blog title or marketplace name
    printf( __( 'Regards,<br>Admin,<br>%1$s', 'dokan' ), esc_html( $blog_title ) );
    ?>
</p>

<?php

/**
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );
