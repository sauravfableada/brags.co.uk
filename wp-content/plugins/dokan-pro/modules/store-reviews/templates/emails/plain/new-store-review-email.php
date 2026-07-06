<?php
/**
 * New Review Email.
 *
 * An email sent to the vendor and admin when a new review is created by customer.
 *
 * @since 3.5.5
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
echo '= ' . esc_html( wp_strip_all_tags( $email_heading ) ) . " =\n";
?>
<?php echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n"; ?>

<?php esc_html_e( 'Store Name: ', 'dokan' ); ?><?php echo esc_html( $store_name ) . "\n"; ?>
<?php esc_html_e( 'Reviewed by: ', 'dokan' ); ?><?php echo esc_html( $reviewer_name ) . "\n"; ?>
<?php esc_html_e( 'Rating: ', 'dokan' ); ?><?php echo esc_html( $rating ) . "\n"; ?>
<?php esc_html_e( 'Title: ', 'dokan' ); ?><?php echo esc_html( $post_title ) . "\n"; ?>
<?php esc_html_e( 'Details: ', 'dokan' ); ?><?php echo esc_html( $post_details ) . "\n"; ?>
<?php echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n"; ?>

<?php
/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( ! empty( $additional_content ) ) {
    echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
    echo "\n\n----------------------------------------\n\n";
}

echo esc_html( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );
