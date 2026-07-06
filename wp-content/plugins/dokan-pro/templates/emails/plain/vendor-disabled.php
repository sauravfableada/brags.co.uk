<?php
/**
 * Vendor enable email to vendors.
 *
 * An email sent to the vendor(s) when a he or she is enabled by the admin
 *
 * @class       Dokan_Email_Vendor_Enable
 * @version     2.7.6
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

echo '= ' . esc_html( wp_strip_all_tags( $email_heading ) ) . " =\n\n";
?>

------------------------------------------------------------

<?php printf( __( 'Hello %s', 'dokan' ), $data['{display_name}'] ); echo " \n\n";  ?>

------------------------------------------------------------

<p>
    <?php esc_html_e( 'Sorry, your vendor account is deactivated.', 'dokan' ); ?>
</p>
<p>
    <?php esc_html_e( 'You can\'t sell or upload product. To activate your account please contact with the admin.', 'dokan' ); ?>
</p>

<?php

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( ! empty( $additional_content ) ) {
    echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
    echo "\n\n----------------------------------------\n\n";
}

echo esc_html( wp_strip_all_tags( wptexturize( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ) ) );
