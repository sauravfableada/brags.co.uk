<?php
/**
 * Dokan Product Q&A Vendor Notification plain Email Template.
 *
 * An email sent to the vendor when a new Question is asked by customer.
 *
 * @class       Dokan_Email_Product_QA_Vendor
 * @version 3.11.0
 */


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

esc_html_e( 'Hello there,', 'dokan' );
echo " \n\n";

esc_html_e( 'A new question is asked to one of your product.', 'dokan' );
echo " \n\n";

esc_html_e( 'Summary of the question:', 'dokan' );

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

// translators: 1) product title
echo sprintf( esc_html__( 'Product Title: %1$s', 'dokan' ), esc_html( $data['{product_name}'] ) );
echo " \n";

// translators: 1) Question asked by name.
echo sprintf( esc_html__( 'Asked by: %1$s', 'dokan' ), esc_html( $data['{customer_name}'] ) );
echo " \n";

// translators: 1) product category
echo sprintf( esc_html__( 'Question: %1$s', 'dokan' ), esc_html( $data['{question}'] ) );
echo " \n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( ! empty( $additional_content ) ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n----------------------------------------\n\n";
}

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );
