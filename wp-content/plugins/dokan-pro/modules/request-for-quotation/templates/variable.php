<?php
defined( 'ABSPATH' ) || exit;

global $product;
?>
<div class="woocommerce-variation-add-to-cart variations_button">
	<?php do_action( 'woocommerce_before_add_to_cart_button' ); ?>

	<?php
	do_action( 'woocommerce_before_add_to_cart_quantity' );

	woocommerce_quantity_input(
		array(
			'min_value'   => apply_filters( 'woocommerce_quantity_input_min', $product->get_min_purchase_quantity(), $product ),
			'max_value'   => apply_filters( 'woocommerce_quantity_input_max', $product->get_max_purchase_quantity(), $product ),
			'input_value' => $product->get_min_purchase_quantity(), // WPCS: CSRF ok, input var ok.
		)
	);

	do_action( 'woocommerce_after_add_to_cart_quantity' );

    if ( 'keep_and_add_new' === $single_quote_rule->hide_cart_button ) {
        ?>
	    <button type="submit" class="single_add_to_cart_button button alt<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>"><?php echo esc_html( $product->single_add_to_cart_text() ); ?></button>
        <?php
    }
    if ( ! dokan_is_product_author( $product->get_id() ) ) {
        echo '<a href="javascript:void(0)" rel="nofollow" data-product_id="' . $product->get_ID() . '" data-product_sku="' . esc_attr( $product->get_sku() ) . '" class="dokan_request_button button single_add_to_cart_button alt product_type_' . esc_attr( $product->get_type() ) . '">' . esc_html( $single_quote_rule->button_text ) . '</a>';
        do_action( 'dokan_pro_after_add_to_quote_button' );
    }
	do_action( 'woocommerce_after_add_to_cart_button' );
	?>

	<input type="hidden" name="add-to-cart" value="<?php echo absint( $product->get_id() ); ?>" />
	<input type="hidden" name="product_id" value="<?php echo absint( $product->get_id() ); ?>" />
	<input type="hidden" name="variation_id" class="variation_id" value="0" />
</div>
