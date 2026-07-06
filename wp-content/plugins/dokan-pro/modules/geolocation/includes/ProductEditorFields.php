<?php

namespace WeDevs\DokanPro\Modules\Geolocation;

use WeDevs\Dokan\ProductEditor\Elements;

defined( 'ABSPATH' ) || exit;

/**
 * Class Product Editor Fields.
 *
 * Registers product geolocation field in the product editor form.
 *
 * @since 5.0.0
 */
class ProductEditorFields {

    public const SECTION_GEOLOCATION = 'product_geolocation_section';
    public const GEOLOCATION_MAP     = 'dokan_geolocation_map';

    /**
     * Class Constructor.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function __construct() {
        add_filter( 'dokan_product_editor_schema', [ $this, 'extend_default_fields' ] );
        add_filter( 'dokan_product_editor_layouts', [ $this, 'extend_layouts' ] );
        add_filter( 'dokan_product_editor_schema_value', [ $this, 'resolve_fields_value' ], 10, 3 );
        add_filter( 'dokan_product_editor_schema_payload', [ $this, 'resolve_payload' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_product_editor_scripts' ] );
    }

    /**
     * Extend default fields with geolocation section and map field.
     *
     * @since 5.0.0
     *
     * @param array $fields Flat sections + fields.
     *
     * @return array
     */
    public function extend_default_fields( array $fields ): array {
        $fields[] = [
            'id'          => self::SECTION_GEOLOCATION,
            'type'        => 'section',
            'label'       => __( 'Geolocation', 'dokan' ),
            'description' => __( 'Set your product location.', 'dokan' ),
            'visibility'  => true,
            'priority'    => 90,
        ];

        $fields[] = [
            'id'         => self::GEOLOCATION_MAP,
            'section_id' => self::SECTION_GEOLOCATION,
            'type'       => 'field',
            'variant'    => 'location_map',
            'label'      => __( 'Location', 'dokan' ),
            'required'   => false,
            'visibility' => true,
            'options'    => $this->get_map_config(),
        ];

        return $fields;
    }

    /**
     * Add geolocation section layout to the product editor form layout.
     *
     * @since 5.0.0
     *
     * @param array $layouts Flat layout items.
     *
     * @return array
     */
    public function extend_layouts( array $layouts ): array {
        $layouts[] = [
            'id'        => self::SECTION_GEOLOCATION,
            'parent_id' => Elements::PRIMARY_COLUMN,
            'priority'  => 85,
            'layout'    => [
                'type'       => 'card',
                'withHeader' => true,
            ],
        ];

        return $layouts;
    }

    /**
     * Resolve geolocation field value from the product.
     *
     * @since 5.0.0
     *
     * @param mixed       $value      Current field value.
     * @param string      $field_name Field name/id.
     * @param \WC_Product $product    Product object.
     *
     * @return mixed
     */
    public function resolve_fields_value( $value, $field_name, $product ) {
        if ( self::GEOLOCATION_MAP !== $field_name ) {
            return $value;
        }

        $product_id = $product->get_id();
        $data       = dokan_geo_get_product_data( $product_id );

        return [
            'latitude'           => $data['dokan_geo_latitude'] ?? '',
            'longitude'          => $data['dokan_geo_longitude'] ?? '',
            'address'            => $data['dokan_geo_address'] ?? '',
            'use_store_settings' => ( $data['use_store_settings'] ?? 'yes' ) === 'yes',
            'store_has_settings' => $data['store_has_settings'] ?? false,
            'store_settings_url' => $data['store_settings_url'] ?? '',
        ];
    }

    /**
     * Resolve geolocation data from the payload before saving.
     *
     * Extracts lat/lng/address from the composite map value and sets them
     * as individual meta fields that WC/Dokan expects.
     *
     * @since 5.0.0
     *
     * @param array $data Payload data.
     *
     * @return array
     */
    public function resolve_payload( array $data ): array {
        if ( ! array_key_exists( self::GEOLOCATION_MAP, $data ) ) {
            return $data;
        }

        $map_value = $data[ self::GEOLOCATION_MAP ];
        unset( $data[ self::GEOLOCATION_MAP ] );

        if ( is_string( $map_value ) ) {
            $decoded = json_decode( wp_unslash( $map_value ), true );
            if ( is_array( $decoded ) ) {
                $map_value = $decoded;
            }
        }

        if ( ! is_array( $map_value ) ) {
            return $data;
        }

        $use_store = ! empty( $map_value['use_store_settings'] );
        $data['_dokan_geolocation_use_store_settings'] = $use_store ? 'yes' : 'no';
        $data['use_store_settings'] = $use_store ? 'yes' : 'no';

        if ( ! $use_store ) {
            $data['_dokan_geolocation_product_dokan_geo_latitude']  = sanitize_text_field( $map_value['latitude'] ?? '' );
            $data['_dokan_geolocation_product_dokan_geo_longitude'] = sanitize_text_field( $map_value['longitude'] ?? '' );
            $data['_dokan_geolocation_product_dokan_geo_address']   = sanitize_text_field( $map_value['address'] ?? '' );
            $data['dokan_geo_public']                               = 1;
        }

        return $data;
    }

    /**
     * Get map provider configuration for the React frontend.
     *
     * @since 5.0.0
     *
     * @return array
     */
    private function get_map_config(): array {
        $source           = dokan_get_option( 'map_api_source', 'dokan_appearance', 'google_maps' );
        $default_location = dokan_geo_get_default_location();
        $map_zoom         = (int) dokan_get_option( 'map_zoom', 'dokan_geolocation', 11 );

        $config = [
            'map_source'       => $source,
            'default_location' => $default_location,
            'map_zoom'         => $map_zoom,
        ];

        if ( 'google_maps' === $source ) {
            $config['api_key'] = dokan_get_option( 'gmap_api_key', 'dokan_appearance', '' );
        } elseif ( 'mapbox' === $source ) {
            $config['access_token'] = dokan_get_option( 'mapbox_access_token', 'dokan_appearance', '' );
        }

        return $config;
    }

    /**
     * Enqueue product editor vendor dashboard scripts.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function enqueue_product_editor_scripts() {
        if ( ! dokan_is_seller_dashboard() ) {
            return;
        }

        // Load the map provider scripts (Google Maps or Mapbox).
        dokan()->scripts->load_gmap_script();

        // Register Product Editor Geolocation Script.
        $pe_asset_file = DOKAN_GEOLOCATION_PATH . '/assets/js/product-editor-geolocation.asset.php';
        if ( file_exists( $pe_asset_file ) ) {
            $pe_asset = require $pe_asset_file;
            $pe_deps  = $pe_asset['dependencies'] ?? [];
            wp_enqueue_script(
                'dokan-product-editor-geolocation',
                DOKAN_GEOLOCATION_ASSETS . '/js/product-editor-geolocation.js',
                $pe_deps,
                $pe_asset['version'],
                true
            );
        }
    }
}
