<?php
/**
 * Accepted Quote Email ( plain text )
 *
 * An email sent to the vendor & admin when a quote accepted by customer.
 *
 * @since 3.12.3
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use WeDevs\DokanPro\Modules\RequestForQuotation\Model\Quote;

echo '= ' . esc_attr( $email_heading ) . " =\n\n";
?>

<?php esc_html_e( 'Summary of the Quote:', 'dokan' ); ?>
<?php echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n"; ?>

<?php esc_html_e( 'Quote #: ', 'dokan' ); ?><?php echo esc_html( $quote_id ); ?>
<?php echo "\n"; ?>
<?php esc_html_e( 'Quote Date: ', 'dokan' ); ?><?php echo esc_attr( dokan_format_datetime() ); ?>
<?php echo "\n"; ?>
<?php esc_html_e( 'Quote Status: ', 'dokan' ); ?><?php echo esc_html( Quote::get_status_label( 'accepted' ) ); ?>
<?php echo "\n"; ?>
<?php esc_html_e( 'Customer name: ', 'dokan' ); ?><?php echo esc_html( $customer_info['name_field'] ?? '' ); ?>
<?php echo "\n"; ?>
<?php esc_html_e( 'Customer email: ', 'dokan' ); ?><?php echo esc_html( $customer_info['email_field'] ?? '' ); ?>
<?php echo "\n"; ?>
<?php if ( ! empty( $customer_info['customer_additional_msg'] ) ) : ?>
    <?php esc_html_e( 'Additional Message: ', 'dokan' ); ?><?php echo esc_html( $customer_info['customer_additional_msg'] ); ?>
    <?php echo "\n"; ?>
<?php endif; ?>
<?php if ( ! empty( $expected_date ) ) : ?>
    <?php esc_html_e( 'Expected Delivery Date: ', 'dokan' ); ?><?php echo esc_html( $expected_date ); ?>
    <?php echo "\n"; ?>
<?php endif; ?>
<?php if ( ! empty( $quote_expiry ) ) : ?>
    <?php esc_html_e( 'Quote Expiry: ', 'dokan' ); ?><?php echo esc_html( $quote_expiry ); ?>
    <?php echo "\n"; ?>
<?php endif; ?>
<?php echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n"; ?>
<?php
$offered_total = 0;

echo __( 'Accepted quote details', 'dokan' ) . "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";
foreach ( $quote_details as $quote_item ) {
    $_product    = wc_get_product( $quote_item->product_id );
    $price       = $_product->get_price();
    $offer_price = isset( $quote_item->offer_price ) ? floatval( $quote_item->offer_price ) : $price;
    $qty_display = $quote_item->quantity;
    ?>
    <?php
    // translators: %s is product name.
    echo "\n" . sprintf( esc_html__( 'Product: %s', 'dokan' ), $_product->get_name() );
    echo "\n" . esc_html__( 'SKU:', 'dokan' ) . '</strong> ' . esc_html( $_product->get_sku() );

    echo "\n";
    ?>
    <?php
    // translators: %s is price.
    printf( esc_html__( 'Offered Price: %s', 'dokan' ), wc_price( $offer_price ) );
    echo "\n";
    ?>
    <?php
    // translators: %s is quantity.
    printf( esc_html__( 'Quantity: %s', 'dokan' ), $qty_display );
    echo "\n";
    ?>
    <?php
    if ( $shipping_cost > 0 ) :
        $offered_total += floatval( $shipping_cost );

        // translators: %s: Shipping price.
        printf( esc_html__( 'Shipping Cost: %s', 'dokan' ), wc_price( $shipping_cost ) );
        echo "\n";
    endif;
    ?>
    <?php
    // translators: %s is price.
    printf( esc_html__( 'Offered Subtotal: %s', 'dokan' ), wc_price( $offer_price * $qty_display ) );
    $offered_total += ( $offer_price * $qty_display );
    ?>

    <?php
    echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";
}

// translators: %s is price.
printf( esc_html__( 'Total Offered Price: %s', 'dokan' ), wc_price( $offered_total ) );
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


echo esc_html( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );
