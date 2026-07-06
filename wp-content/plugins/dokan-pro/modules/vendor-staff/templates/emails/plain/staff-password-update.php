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

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

// translators: %1$s is the name of the email recipient
printf( __( 'Hello %1$s,', 'dokan' ), wp_strip_all_tags( $staff_name ) );
echo "\n";

// translators: %1$s is the store name
printf( __( 'The notice is to inform you that, your password for the %1$s has been changed by the Vendor. Please contact your Vendor to collect the password to access your account.', 'dokan' ), wp_strip_all_tags( $store_info ) );
echo "\n";

// translators: %1$s is the email address of the recipient
printf( __( 'This email was sent to %1$s.', 'dokan' ), wp_strip_all_tags( $staff_email ) );

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
    echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
    echo "\n\n----------------------------------------\n\n";
}

echo "\n\n";

echo "Regards,\n";
echo "Admin,\n";
echo wp_strip_all_tags( $blog_title );


echo esc_html( wp_strip_all_tags( wptexturize( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ) ) );
