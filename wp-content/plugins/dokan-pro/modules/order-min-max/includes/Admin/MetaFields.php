<?php

namespace WeDevs\DokanPro\Modules\OrderMinMax\Admin;

defined( 'ABSPATH' ) || exit;

use WeDevs\DokanPro\Modules\OrderMinMax\Constants;
use WeDevs\DokanPro\Modules\OrderMinMax\DataSource\ProductMinMaxSettings;
use WeDevs\DokanPro\Modules\OrderMinMax\Helper;
use WP_Post;

/**
 * Adds meta field to admin product edit section
 *
 * @since 3.12.0
 */
class MetaFields {

    /**
     * Initializing necessary hooks
     *
     * @return void
     * @since 3.12.0
     */
    public function __construct() {
        add_action( 'woocommerce_product_options_general_product_data', array( $this, 'add_simple_product_meta_fields' ) );
        add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'add_variable_product_meta_fields' ), 10, 3 );
    }

    /**
     * Adds simple product meta fields
     *
     * @return void
     * @since 3.12.0
     */
    public function add_simple_product_meta_fields() {
        global $product_object;

        $product_settings = new ProductMinMaxSettings( $product_object->get_id() );
        $wrapper_class    = Constants::SIMPLE_PRODUCT_MIN_MAX_WRAPPER;

        echo "<div class='options_group show_if_simple {$wrapper_class}'>"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

        woocommerce_wp_text_input(
            array(
                'id'                => Constants::SIMPLE_PRODUCT_MIN_QUANTITY,
                'value'             => $product_settings->min_quantity( 'edit' ),
                'type'              => 'number',
                'custom_attributes' => array(
                    'step' => 'any',
                    'min'  => '0',
                ),
                'label'             => esc_html__( 'Minimum quantity to order', 'dokan' ),
            )
        );
        woocommerce_wp_text_input(
            array(
                'id'                => Constants::SIMPLE_PRODUCT_MAX_QUANTITY,
                'value'             => $product_settings->max_quantity( 'edit' ),
                'type'              => 'number',
                'custom_attributes' => array(
                    'step' => 'any',
                    'min'  => '0',
                ),
                'label'             => esc_html__( 'Maximum quantity to order', 'dokan' ),
            )
        );

        $message_id = Constants::SIMPLE_PRODUCT_MESSAGE_SECTION;
        $message    = Helper::get_quantity_min_max_notice();
        echo "<div class='dokan-min-max-warning-message' id='{$message_id}'>{$message}</div>"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

        echo wp_nonce_field( Constants::SIMPLE_PRODUCT_MIN_MAX_NONCE, Constants::SIMPLE_PRODUCT_MIN_MAX_NONCE ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    /**
     * Adds variable product meta fields
     *
     * @param int $loop
     * @param array $variation_data
     * @param WP_Post $variation ,
     *
     * @return void
     * @since 3.12.0
     */
    public function add_variable_product_meta_fields( int $loop, array $variation_data, WP_Post $variation ) {
        $product_id = ! empty( $variation->ID ) ? $variation->ID : 0;
        $product    = wc_get_product( $product_id );

        if ( ! $product || 'variation' !== $product->get_type() ) {
            return;
        }

        $settings      = new ProductMinMaxSettings( $product );
        $wrapper_class = Constants::VARIATION_PRODUCT_MIN_MAX_WRAPPER;

        echo "<div class='{$wrapper_class}'>"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

        woocommerce_wp_text_input(
            array(
                'id'    => Constants::VARIATION_PRODUCT_MIN_QUANTITY . "_{$loop}",
                'name'  => Constants::VARIATION_PRODUCT_MIN_QUANTITY . "[{$loop}]",
                'class' => 'variable_min_quantity ' . Constants::VARIATION_PRODUCT_MIN_QUANTITY,
                'value' => $settings->min_quantity( 'edit' ),
                'type'  => 'number',
                'label' => esc_html__( 'Minimum quantity to order', 'dokan' ),
            )
        );
        woocommerce_wp_text_input(
            array(
                'id'    => Constants::VARIATION_PRODUCT_MAX_QUANTITY . "_{$loop}",
                'name'  => Constants::VARIATION_PRODUCT_MAX_QUANTITY . "[{$loop}]",
                'class' => 'variable_max_quantity ' . Constants::VARIATION_PRODUCT_MAX_QUANTITY,
                'value' => $settings->max_quantity( 'edit' ),
                'type'  => 'number',
                'label' => esc_html__( 'Maximum quantity to order', 'dokan' ),
            )
        );

        $message_class = 'dokan-min-max-warning-message ' . Constants::VARIATION_PRODUCT_MESSAGE_SECTION;
        $message       = Helper::get_quantity_min_max_notice();
        echo "<div class='{$message_class}'>{$message}</div>"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

        echo wp_nonce_field( Constants::VARIATION_PRODUCT_MIN_MAX_NONCE, Constants::VARIATION_PRODUCT_MIN_MAX_NONCE ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

        echo '</div>';
    }
}
