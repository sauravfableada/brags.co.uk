<?php
/**
 * New Product Email ( plain text )
 *
 * An email sent to the admin when a new Product is created by vendor.
 *
 * @class       Dokan_Email_New_Product
 * @since       3.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use WeDevs\DokanPro\Modules\RequestForQuotation\Model\Quote;

echo '= ' . esc_attr( $email_heading ) . " =\n\n";
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";
esc_attr_e( 'Summary of the Quote:', 'dokan' );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

esc_html_e( 'Quote #: ', 'dokan' );
echo esc_html( $quote_id );

esc_html_e( 'Quote Date: ', 'dokan' );
echo esc_attr( dokan_format_datetime() );

esc_html_e( 'Quote Status: ', 'dokan' );
echo esc_html( Quote::get_status_label( 'pending' ) );

if ( 'customer' === $sending_to ) :
    if ( ! empty( $store_info['store_name'] ) ) :
        esc_html_e( 'Store: ', 'dokan' );
        echo esc_html( $store_info['store_name'] );
     endif;
    if ( ! empty( $store_info['store_email'] ) ) :
        esc_html_e( 'Store Email: ', 'dokan' );
        echo esc_html( $store_info['store_email'] );
    endif;
    if ( ! empty( $store_info['store_phone'] ) ) :
        esc_html_e( 'Store Phone: ', 'dokan' );
        echo esc_html( $store_info['store_phone'] );
    endif;
    if ( ! empty( $customer_info['customer_additional_msg'] ) ) :
        esc_html_e( 'Additional Message: ', 'dokan' );
        echo esc_html( $customer_info['customer_additional_msg'] );
    endif;
    if ( ! empty( $expected_date ) ) :
        esc_html_e( 'Expected Delivery Date: ', 'dokan' );
        echo esc_html( $expected_date );
    endif;
else :
    if ( ! empty( $customer_info['name_field'] ) ) :
        esc_html_e( 'Customer Name: ', 'dokan' );
        echo esc_html( $customer_info['name_field'] ?? '' );
    endif;
    if ( ! empty( $customer_info['email_field'] ) ) :
        esc_html_e( 'Customer Email: ', 'dokan' );
        echo esc_html( $customer_info['email_field'] ?? '' );
    endif;
    if ( ! empty( $customer_info['phone_field'] ) ) :
        esc_html_e( 'Customer Phone: ', 'dokan' );
        echo esc_html( $customer_info['phone_field'] ?? '' );
    endif;
endif;
if ( ! empty( $expected_date ) ) :
    esc_html_e( 'Expected Delivery Date: ', 'dokan' );
    echo esc_html( $expected_date );
endif;
if ( ! empty( $customer_info['customer_additional_msg'] ) ) :
    esc_html_e( 'Additional Message: ', 'dokan' );
    echo esc_html( $customer_info['customer_additional_msg'] );
endif;
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

$offered_total = 0;
foreach ( $quote_details as $quote_item ) {
    $_product    = wc_get_product( $quote_item->product_id );
    $price       = $_product->get_price();
    $offer_price = isset( $quote_item->offer_price ) ? floatval( $quote_item->offer_price ) : $price;
    $qty_display = $quote_item->quantity;

    // translators: %s is product name.
    echo "\n" . sprintf( esc_html__( 'Product: %s', 'dokan' ), $_product->get_name() );
    echo "\n" . esc_html__( 'SKU:', 'dokan' ) . ' ' . esc_html( $_product->get_sku() );

    echo "\n";

    // translators: %s is price.
    printf( esc_html__( 'Offered Price: %s', 'dokan' ), $offer_price );
    echo "\n";
    // translators: %s is quantity.
    printf( esc_html__( 'Quantity: %s', 'dokan' ), $qty_display );
    echo "\n";
    // translators: %s is price.
    printf( esc_html__( 'Offered Subtotal: %s', 'dokan' ), ( $offer_price * $qty_display ) );
    $offered_total += ( $offer_price * $qty_display );
    echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";
}
// translators: %s is price.
printf( esc_attr__( 'Total Offered Price: %s', 'dokan' ), $offered_total );
echo "\n\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

esc_attr_e( 'Shipping address:', 'dokan' );
echo "\n\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";

// translators: %s is shipment name.
echo "\n" . sprintf( esc_html__( 'Name: %s', 'dokan' ), $customer_info['name_field'] ?? '' );
// translators: %s is shipment phone.
echo "\n" . sprintf( esc_html__( 'Phone: %s', 'dokan' ), $customer_info['phone_field'] ?? '' );
// translators: %s is shipment address line 1.
echo "\n" . sprintf( esc_html__( 'Address Line 1: %s', 'dokan' ), $customer_info['addr_line_1'] ?? '' );
// translators: %s is shipment address line 2.
echo "\n" . sprintf( esc_html__( 'Address Line 2: %s', 'dokan' ), $customer_info['addr_line_2'] ?? '' );
// translators: %s is shipment city.
echo "\n" . sprintf( esc_html__( 'City: %s', 'dokan' ), $customer_info['city'] ?? '' );
// translators: %s is shipment post code.
echo "\n" . sprintf( esc_html__( 'Post Code: %s', 'dokan' ), $customer_info['post_code'] ?? '' );
/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( ! empty( $additional_content ) ) {
    echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
    echo "\n\n----------------------------------------\n\n";
}

echo esc_html( wp_strip_all_tags( wptexturize( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ) ) );
