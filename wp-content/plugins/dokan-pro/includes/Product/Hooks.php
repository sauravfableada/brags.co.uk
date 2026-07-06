<?php
namespace WeDevs\DokanPro\Product;

use WC_Product_Variation;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;


/**
 * Dokan Product Form Helper Class
 *
 * @package WeDevs\DokanPro\Modules\ProductEditor
 *
 * @since 5.0.0
 */
class Hooks {
    public function __construct() {
        // Save custom field values as product meta after product create/update.
        add_action( 'dokan_new_product_added', [ $this, 'save_custom_fields' ], 10, 2 );
        add_action( 'dokan_product_updated', [ $this, 'save_custom_fields' ], 10, 2 );
        add_action( 'dokan_rest_insert_product_variation_object', [ $this, 'save_custom_variation_fields' ], 10, 2 );

        // Pass variation form layouts to the frontend.
        add_filter( 'dokan_product_editor_args', [ $this, 'add_variation_form_layouts' ] );
    }

    /**
     * Save custom field values as product meta.
     *
     * Custom field keys (e.g. dokan_custom_field_xxx) pass through PayloadResolver
     * but are ignored by WC REST API. This method extracts them from the request
     * params and persists them as product meta.
     *
     * @since 5.0.0
     *
     * @param int   $product_id Product ID.
     * @param array $params     Request parameters.
     *
     * @return void
     */
    public function save_custom_fields( int $product_id, array $params ): void {
        if ( ! $product_id ) {
            return;
        }

        $saved_schema = get_option( FormSchema::SETTINGS_KEY, [] );

        if ( empty( $saved_schema ) || ! is_array( $saved_schema ) ) {
            return;
        }

        // Build a map of custom field ID => variant.
        $custom_fields = [];
        foreach ( $saved_schema as $item ) {
            if ( ! empty( $item['is_custom'] ) && isset( $item['type'] ) && 'field' === $item['type'] && ! empty( $item['id'] ) ) {
                $custom_fields[ $item['id'] ] = $item['variant'] ?? 'text';
            }
        }

        if ( empty( $custom_fields ) ) {
            return;
        }

        $product = wc_get_product( $product_id );

        if ( ! $product ) {
            return;
        }

        foreach ( $custom_fields as $field_id => $variant ) {
            if ( ! array_key_exists( $field_id, $params ) ) {
                continue;
            }

            $value = $this->sanitize_custom_field_value( $params[ $field_id ], $variant );
            $product->update_meta_data( $field_id, $value );
        }

        $product->save_meta_data();
    }

    /**
     * Sanitize a custom field value based on its variant.
     *
     * @since 5.0.0
     *
     * @param mixed  $value   Raw field value.
     * @param string $variant Field variant type.
     *
     * @return mixed Sanitized value.
     */
    public function sanitize_custom_field_value( $value, string $variant ) {
        switch ( $variant ) {
            case 'file':
                // Store only attachment IDs from [ { id, file, name }, ... ].
                if ( ! is_array( $value ) ) {
                    return [];
                }

                return array_values(
                    array_filter(
                        array_map(
                            function ( $file ) {
                                return absint( is_array( $file ) ? ( $file['id'] ?? 0 ) : $file );
                            },
                            $value
                        )
                    )
                );

            case 'image':
                // Value shape: attachment ID (int) or { id, url }.
                if ( is_array( $value ) ) {
                    return absint( $value['id'] ?? 0 );
                }

                return absint( $value );

            case 'gallery_images':
                // Value shape: [ int, int, ... ].
                if ( ! is_array( $value ) ) {
                    return [];
                }

                return array_map( 'absint', $value );

            case 'multiselect':
                if ( ! is_array( $value ) ) {
                    return [ sanitize_text_field( $value ) ];
                }

                return array_map( 'sanitize_text_field', $value );

            case 'number':
                return is_numeric( $value ) ? $value + 0 : 0;

            case 'textarea':
            case 'editor':
                return wp_kses_post( $value );

            case 'checkbox':
            case 'text':
            case 'select':
            case 'radio':
            case 'date':
            default:
                return sanitize_text_field( $value );
        }
    }

    /**
     * Save custom field values for variations.
     *
     * @since 5.0.0
     *
     * @param WC_Product_Variation $variation Variation object.
     * @param WP_REST_Request      $request   REST request.
     *
     * @return void
     */
    public function save_custom_variation_fields( WC_Product_Variation $variation, WP_REST_Request $request ): void {
        // Reuse the same logic as saving custom fields for simple products.
        $this->save_custom_fields( $variation->get_id(), $request->get_params() );
    }

    /**
     * Add variation form layouts to the product editor localized data.
     *
     * @since 5.0.0
     *
     * @param array $args Localized data array.
     *
     * @return array
     */
    public function add_variation_form_layouts( array $args ): array {
        $args['variation_form_layouts'] = FormSchema::get_variation_layouts();

        return $args;
    }
}
