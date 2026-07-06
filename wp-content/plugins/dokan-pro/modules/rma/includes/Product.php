<?php

namespace WeDevs\DokanPro\Modules\RMA;

use WC_Product;
use WeDevs\DokanPro\Modules\RMA\Traits\RMACommon;

/**
* Vendor product releate functions
*
* @since 1.0.0
*
* @package dokan
*/
class Product {

    use RMACommon;

    /**
     * Load automatically when class initiate
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_action( 'dokan_product_edit_after_inventory_variants', [ $this, 'load_rma_content' ], 30, 2 );
        add_action( 'dokan_new_product_added', [ $this, 'save_rma_data' ], 12 );
        add_action( 'dokan_product_updated', [ $this, 'save_rma_data' ], 12 );
        add_filter( 'woocommerce_product_tabs', [ $this, 'refund_policy_tab' ] );
        add_filter( 'woocommerce_rest_prepare_product_object', [ $this, 'add_rma_settings_into_product' ], 10, 2 );
    }

    /**
    * Render product rma options
    *
    * @since 1.0.0
    *
    * @return void
    **/
    public function load_rma_content( $post, $post_id ) {
        $user_id          = dokan_get_current_user_id();
        $override_default = get_post_meta( $post_id, '_dokan_rma_override_product', true );
        $reasons          = dokan_rma_refund_reasons();
        $rma_settings     = $this->get_settings( $post_id );

        dokan_get_template_part(
            'rma/product', '', array(
				'is_rma'           => true,
				'reasons'          => $reasons,
				'rma_settings'     => $rma_settings,
				'override_default' => $override_default,
            )
        );
    }

    /**
     * Save RMA data
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function save_rma_data( $post_id ) {
        if ( ! $post_id ) {
            return;
        }

        if ( ! isset( $_POST['dokan_rma_product_override'] ) ) {
            return;
        }

        $override_default = $_POST['dokan_rma_product_override'];

        if ( ! empty( $_POST['dokan_rma_product_override'] ) ) {
            update_post_meta( $post_id, '_dokan_rma_override_product', $override_default );
        }

        if ( 'yes' === $override_default ) {
            $product_rma_settings = $this->transform_rma_settings( $_POST );
            update_post_meta( $post_id, '_dokan_rma_settings', $product_rma_settings );
        } else {
            delete_post_meta( $post_id, '_dokan_rma_settings' );
        }
    }

    /**
     * Refund policy tab
     *
     * @since  2.9.16
     *
     * @param  array $tabs
     *
     * @return array
     */
    public function refund_policy_tab( $tabs ) {
        global $product;

        if ( ! $product instanceof WC_Product ) {
            return $tabs;
        }

        $product_id = $product->get_id();
        $warranty   = $this->get_settings( $product_id );
        $policy     = ! empty( $warranty['policy'] ) ? $warranty['policy'] : '';

        if ( ! $policy ) {
            return $tabs;
        }

        $tabs['refund_policy'] = [
            'title'    => __( 'Warranty Policy', 'dokan' ),
            'priority' => 100,
            'policy'   => $policy,
            'callback' => [ $this, 'get_refund_policy_tab' ],
        ];

        return $tabs;
    }

    /**
     * Get refund policy tab template
     *
     * @since  2.9.16
     *
     * @param  string $title
     * @param  array $data
     *
     * @return void
     */
    public function get_refund_policy_tab( $title, $data ) {
        if ( empty( $data['policy'] ) ) {
            return;
        }

        dokan_get_template_part(
            'rma/refund-policy', '', [
				'is_rma' => true,
				'policy' => $data['policy'],
			]
        );
    }

    /**
     * Add RMA settings into product REST API response
     *
     * @param $response
     * @param $product
     *
     * @since 4.2.0
     *
     * @return mixed
     */
    public function add_rma_settings_into_product( $response, $product ) {
        $product_id = $product->get_id();
        $rma_settings = $this->get_settings( $product_id );
        $response->data['dokan_rma_settings'] = $rma_settings;

        return $response;
    }
}
