<?php

namespace WeDevs\DokanPro\Modules\Geolocation;

defined( 'ABSPATH' ) || exit;

use WC_Product;

/**
 * Block data handler.
 *
 * @since 3.7.17
 */
class BlockData {

    /**
     * Block section name.
     *
     * @since 3.7.17
     *
     * @var string
     */
    public $section;

    /**
     * Constructor class.
     *
     * @since 3.7.17
     */
    public function __construct() {
        $this->section = 'geolocation';
        $this->hooks();
    }

    /**
     * Registers necessary hooks.
     *
     * @since 3.7.17
     *
     * @return void
     */
    protected function hooks() {
        // Get and Set block
        add_filter( 'dokan_rest_get_product_block_data', [ $this, 'get_product_block_data' ], 10, 3 );
        add_action( 'dokan_rest_insert_product_object', [ $this, 'set_product_block_data' ], 10, 3 );
    }

    /**
     * Get eu compliance product data for Dokan-pro.
     *
     * @since 3.7.17
     *
     * @param array      $block
     * @param WC_Product $product
     * @param string     $context
     *
     * @return array
     */
    public function get_product_block_data( array $block, $product, string $context ) {
        if ( ! $product instanceof WC_Product ) {
            return $block;
        }

        $block[ $this->section ] = dokan_geo_get_product_data( $product->get_id() );

        return $block;
    }

    /**
     * Save order-min-max data after REST-API insert or update.
     *
     * @since 3.7.17
     *
     * @param WC_Product      $product  Inserted object.
     * @param WP_REST_Request $request  Request object.
     * @param boolean         $creating True when creating object, false when updating.
     *
     * @return void
     */
    public function set_product_block_data( $product, $request, $creating = true ) {
        if ( ! $product instanceof WC_Product ) {
            return;
        }

        $store_id = ! empty( $request['dokan_product_author_override'] )
            ? intval( $request['dokan_product_author_override'] )
            : dokan_get_current_user_id();

        $dokan_geo_public = get_user_meta( $store_id, 'dokan_geo_public', true );

        if ( ! empty( $request['use_store_settings'] ) ) {
            $use_store_settings = 'no' === $request['use_store_settings'] ? 'no' : 'yes';
            $product->update_meta_data( '_dokan_geolocation_use_store_settings', $use_store_settings );
        } else {
            $use_store_settings = 'no' === $product->get_meta( '_dokan_geolocation_use_store_settings', true ) ? 'no' : 'yes';
        }

        if ( 'yes' !== $use_store_settings ) {
            $dokan_geo_latitude = ! empty( $request['dokan_geo_latitude'] )
                ? $request['dokan_geo_latitude']
                : $product->get_meta( 'dokan_geo_latitude' );

            $dokan_geo_longitude = ! empty( $request['dokan_geo_longitude'] )
                ? $request['dokan_geo_longitude']
                : $product->get_meta( 'dokan_geo_longitude' );

            $dokan_geo_address = ! empty( $request['dokan_geo_address'] )
                ? $request['dokan_geo_address']
                : $product->get_meta( 'dokan_geo_address' );
        } else {
            $dokan_geo_latitude  = get_user_meta( $store_id, 'dokan_geo_latitude', true );
            $dokan_geo_longitude = get_user_meta( $store_id, 'dokan_geo_longitude', true );
            $dokan_geo_address   = get_user_meta( $store_id, 'dokan_geo_address', true );
        }

        $product->update_meta_data( 'dokan_geo_latitude', $dokan_geo_latitude );
        $product->update_meta_data( 'dokan_geo_longitude', $dokan_geo_longitude );
        $product->update_meta_data( 'dokan_geo_address', $dokan_geo_address );
        $product->update_meta_data( 'dokan_geo_public', $dokan_geo_public );
        $product->save();
    }
}
