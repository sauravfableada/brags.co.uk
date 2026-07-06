<?php

use WeDevs\DokanPro\Modules\RequestForQuotation\SettingsHelper;

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product->is_purchasable() ) {
    return;
}

if ( empty( $single_quote_rule->button_text ) ) {
    $single_quote_rule->button_text = __( 'Add to Quote', 'dokan' );
}

if ( ! $product->is_in_stock() && ! SettingsHelper::is_out_of_stock_enabled() ) {
    echo wp_kses_post( wc_get_stock_html( $product ) ); // phpcs:ignore WordPress.Security.EscapeOutput.
} else {
    do_action( 'woocommerce_before_add_to_cart_form' ); ?>

    <form class="cart" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>" method="post" enctype='multipart/form-data'>
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
            $button_html = '<button type="submit" name="add-to-cart" value="' . $product->get_id() . '" class="single_add_to_cart_button button alt">' . __( 'Add to cart', 'dokan' ) . '</button> ';
            echo apply_filters( 'dokan_request_for_quote_add_to_cart_button_html', $button_html, $product );
        }
        if ( ! dokan_is_product_author( $product->get_id() ) ) {
            echo '<a href="javascript:void(0)" rel="nofollow" data-product_id="' . $product->get_id() . '" data-product_sku="' . esc_attr( $product->get_sku() ) . '" class="button dokan_request_button single_add_to_cart_button alt add_to_cart_button product_type_' . esc_attr( $product->get_type() ) . '">' . esc_html( $single_quote_rule->button_text ) . '</a>';
        }

        do_action( 'dokan_after_add_to_quote_button' );
        do_action( 'woocommerce_after_add_to_cart_button' );

        ?>

    </form>

    <?php
    do_action( 'woocommerce_after_add_to_cart_form' );
}
