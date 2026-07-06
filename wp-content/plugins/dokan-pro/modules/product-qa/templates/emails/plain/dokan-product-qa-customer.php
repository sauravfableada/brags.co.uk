<?php
/**
 * Dokan Product Q&A Customer Notification plain Email Template.
 *
 * n email sent to the customer when a Question is answered by vendor.
 *
 * @class       Dokan_Email_Product_QA_Customer
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

esc_html_e( 'Your question is answered by the vendor of the product.', 'dokan' );
echo " \n\n";

esc_html_e( 'Summary of the question and answer:', 'dokan' );

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

// translators: 1) product title
echo sprintf( esc_html__( 'Product Title: %1$s', 'dokan' ), esc_html( $data['{product_name}'] ) );
echo " \n";

// translators: 1) product category
echo sprintf( esc_html__( 'Question: %1$s', 'dokan' ), esc_html( $data['{question}'] ) );
echo " \n";

// translators: 1) Question answered by name.
echo sprintf( esc_html__( 'Answered by: %1$s', 'dokan' ), esc_html( $data['{seller_name}'] ) );
echo " \n";

// translators: 1) product category
echo sprintf( esc_html__( 'Answer: %1$s', 'dokan' ), esc_html( wp_strip_all_tags( wptexturize( $data['{answer}'] ) ) ) );
echo " \n";


/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( ! empty( $additional_content ) ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n----------------------------------------\n\n";
}

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );
