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

$quotation_status = $quote_status !== Quote::STATUS_APPROVED ? 'Updated' : 'Approved';
?>

<?php esc_attr_e( 'Summary of the Quote:', 'dokan' ); ?>
<?php echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n"; ?>

<?php esc_html_e( 'Quote #: ', 'dokan' ); ?><?php echo esc_html( $quote_id ); ?>
<?php echo "\n"; ?>
<?php esc_html_e( 'Quote Date: ', 'dokan' ); ?><?php echo esc_attr( dokan_format_datetime() ); ?>
<?php echo "\n"; ?>
<?php esc_html_e( 'Quote Status: ', 'dokan' ); ?><?php echo esc_html( $quotation_status ); ?>
<?php echo "\n"; ?>
<?php if ( 'seller' === $sending_to ) : ?>
	<?php esc_html_e( 'Customer name: ', 'dokan' ); ?><?php echo esc_html( $customer_info['name_field'] ?? '' ); ?>
	<?php echo "\n"; ?>
	<?php esc_html_e( 'Customer email: ', 'dokan' ); ?><?php echo esc_html( $customer_info['email_field'] ?? '' ); ?>
	<?php echo "\n"; ?>
<?php endif; ?>
<?php if ( ! empty( $vendor_msg ) ) : ?>
    <?php esc_html_e( 'Vendor Message: ', 'dokan' ); ?><?php echo esc_html( $vendor_msg ); ?>
<?php endif; ?>
<?php if ( $quote_status !== Quote::STATUS_UPDATE && $quote_expiry ) : ?>
    <?php esc_html_e( 'Quote Expiry: ', 'dokan' ); ?><?php echo esc_html( $quote_expiry ); ?>
<?php endif; ?>
<?php echo "\n"; ?>
<?php echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n"; ?>
<?php
echo __( 'Previous quote details', 'dokan' ) . "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";
$offered_total = 0;
foreach ( $old_quote_details as $quote_item ) {
    $_product    = wc_get_product( $quote_item->product_id );
    $price       = $_product->get_price();
    $offer_price = isset( $quote_item->offer_price ) ? floatval( $quote_item->offer_price ) : $price;
    $qty_display = $quote_item->quantity;
    ?>
    <?php
    // translators: %s is product name.
    printf( esc_html__( 'Product: %s', 'dokan' ), $_product->get_name() );
    echo "\n";
    echo esc_html__( 'SKU:', 'dokan' ) . '</strong> ' . esc_html( $_product->get_sku() );
    echo "\n";
    ?>
    <?php
    // translators: %s is price.
    printf( esc_html__( 'Offered Price: %s', 'dokan' ), $offer_price );
    echo "\n";
    ?>
    <?php
    // translators: %s is quantity.
    printf( esc_html__( 'Quantity: %s', 'dokan' ), $qty_display );
    echo "\n";
    ?>
    <?php
    // translators: %s is price.
    printf( esc_html__( 'Offered Subtotal: %s', 'dokan' ), ( $offer_price * $qty_display ) );
    $offered_total += ( $offer_price * $qty_display );
    ?>

    <?php
    echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";
}

/* translators: %s: Quotation status label */
printf( __( '%s quote details', 'dokan' ), $quotation_status );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";
foreach ( $quote_details as $quote_item ) {
    $_product    = wc_get_product( $quote_item->product_id );
    $price       = $_product->get_price();
    $offer_price = isset( $quote_item->offer_price ) ? floatval( $quote_item->offer_price ) : $price;
    $qty_display = $quote_item->quantity;
    ?>
    <?php
    // translators: %s is product name.
    echo "\n" . sprintf( esc_html__( 'Product: %s', 'dokan' ), $_product->get_name() );
    echo "\n" . esc_html__( 'SKU:', 'dokan' ) . ' ' . esc_html( $_product->get_sku() );

    echo "\n";
    ?>
    <?php
    // translators: %s is price.
    printf( esc_html__( 'Offered Price: %s', 'dokan' ), $offer_price );
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

    // translators: %s is price.
    printf( esc_html__( 'Offered Subtotal: %s', 'dokan' ), ( ( $offer_price * $qty_display ) + floatval( $shipping_cost ) ) );
    ?>

    <?php
    echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";
}

// translators: %s is price.
printf( esc_attr__( 'Total Offered Price: %s', 'dokan' ), $offered_total );
echo "\n\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( ! empty( $additional_content ) ) {
    echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
    echo "\n\n----------------------------------------\n\n";
}

echo esc_html( wp_strip_all_tags( wptexturize( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ) ) );
