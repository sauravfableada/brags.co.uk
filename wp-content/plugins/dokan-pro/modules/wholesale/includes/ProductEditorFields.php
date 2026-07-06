<?php

namespace WeDevs\DokanPro\Modules\Wholesale;

use WC_Product;
use WC_Product_Variation;
use WeDevs\Dokan\ProductEditor\Elements;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

/**
 * Class Product Form Fields.
 *
 * @since 5.0.0
 */
class ProductEditorFields {

    const SECTION_WHOLESALE   = 'wholesale_section';
    const ENABLE_WHOLESALE    = 'enable_wholesale';
    const WHOLESALE_PRICE     = 'wholesale_price';
    const WHOLESALE_QUANTITY  = 'wholesale_quantity';
    const WHOLESALE_META_KEY  = '_dokan_wholesale_meta';

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
        add_filter( 'dokan_product_editor_schema_payload', [ $this, 'resolve_fields_payload' ] );
        add_action( 'dokan_rest_insert_product_variation_object', [ $this, 'save_product_variable_data' ], 12, 2 );
    }

    /**
     * Extend default fields with wholesale section and fields.
     *
     * @since 5.0.0
     *
     * @param array $fields Flat sections + fields.
     *
     * @return array
     */
    public function extend_default_fields( array $fields ): array {
        $dep_non_variable = [
            [
                'comparison' => '!=',
                'key'        => Elements::TYPE,
                'value'      => Elements::PRODUCT_TYPE_VARIABLE,
            ],
        ];
        $fields[] = [
            'id'          => self::SECTION_WHOLESALE,
            'type'        => 'section',
            'label'       => __( 'Wholesale Options', 'dokan' ),
            'description' => __( 'Set your wholesale options for this product', 'dokan' ),
            'visibility'  => true,
            'dependencies' => $dep_non_variable,
            'priority'    => 80,
        ];

        $fields[] = [
            'id'         => self::ENABLE_WHOLESALE,
            'section_id'  => self::SECTION_WHOLESALE,
            'type'       => 'field',
            'label'      => __( 'Enable wholesale for this product', 'dokan' ),
            'variant'    => 'checkbox',
            'required'   => false,
            'visibility' => true,
            'dependencies' => $dep_non_variable,
        ];

        $fields[] = [
            'id'           => self::WHOLESALE_PRICE,
            'section_id'   => self::SECTION_WHOLESALE,
            'type'         => 'field',
            'label'        => __( 'Wholesale Price', 'dokan' ),
            'variant'      => 'number',
            'placeholder'  => '0',
            'required'     => false,
            'visibility'   => true,
            'dependencies' => array_merge(
                $dep_non_variable, [
                    [
                        'comparison' => '==',
                        'key'        => self::ENABLE_WHOLESALE,
                        'value'      => true,
                    ],
                ]
            ),
        ];

        $fields[] = [
            'id'           => self::WHOLESALE_QUANTITY,
            'section_id'   => self::SECTION_WHOLESALE,
            'type'         => 'field',
            'label'        => __( 'Minimum Quantity for Wholesale', 'dokan' ),
            'variant'      => 'number',
            'placeholder'  => __( 'Minimum Quantity', 'dokan' ),
            'required'     => false,
            'visibility'   => true,
            'dependencies' => array_merge(
                $dep_non_variable, [
                    [
                        'comparison' => '==',
                        'key'        => self::ENABLE_WHOLESALE,
                        'value'      => true,
                    ],
                ]
            ),
        ];

        return $fields;
    }


    /**
     * Add wholesale section layout to the product editor form layout.
     *
     * @since 5.0.0
     *
     * @param array $layouts Flat layout items.
     *
     * @return array
     */
    public function extend_layouts( array $layouts ): array {
        $layouts[] = [
            'id'        => self::SECTION_WHOLESALE,
            'parent_id' => Elements::PRIMARY_COLUMN,
            'priority'  => 80,
            'layout'    => [
                'type'       => 'card',
                'withHeader' => true,
            ],
        ];

        return $layouts;
    }

    /**
     * Resolve fields payload for wholesale fields.
     *
     * @since 5.0.0
     *
     * @param array $payload Payload array.
     *
     * @return array
     */
    public function resolve_fields_payload( array $payload ): array {
        if ( isset( $payload[ self::ENABLE_WHOLESALE ] ) ) {
            $wholesale_data = [
                'enable_wholesale' => ! empty( $payload[ self::ENABLE_WHOLESALE ] ) ? 'yes' : 'no',
                'price'            => wc_format_decimal( $payload[ self::WHOLESALE_PRICE ] ?? 0 ),
                'quantity'         => absint( $payload[ self::WHOLESALE_QUANTITY ] ?? 0 ),
            ];
            $payload['wholesale'] = $wholesale_data;
        }

        return $payload;
    }

    /**
     * Resolve field values for wholesale fields.
     *
     * @since 5.0.0
     *
     * @param mixed      $value      Current field value.
     * @param string     $field_name Field name/id.
     * @param WC_Product $product    Product object.
     *
     * @return mixed
     */
    public function resolve_fields_value( $value, $field_name, $product ) {
        if ( ! $product instanceof WC_Product ) {
            return $value;
        }

        $wholesale = $product->get_meta( self::WHOLESALE_META_KEY, true ) ?? [];

        if ( empty( $wholesale ) ) {
            return $value;
        }

        switch ( $field_name ) {
            case self::ENABLE_WHOLESALE:
                return 'yes' === ( $wholesale['enable_wholesale'] ?? 'no' );

            case self::WHOLESALE_PRICE:
                return (float) ( $wholesale['price'] ?? 0 );

            case self::WHOLESALE_QUANTITY:
                return (float) ( $wholesale['quantity'] ?? 0 );

            default:
                return $value;
        }
    }

    /**
     * Save wholesale variation data.
     *
     * @since 5.0.0
     *
     * @param WC_Product_Variation $variation Variation object.
     * @param WP_REST_Request      $request   REST request.
     *
     * @return void
     */
    public function save_product_variable_data( $variation, WP_REST_Request $request ) {
        $data = $request->get_params();
        /** @var WC_Product_Variation $variation */
        $variation_id = $variation->get_id();

        if ( isset( $data['wholesale'] ) ) {
            $wholesale_data = [
                'enable_wholesale' => $data['wholesale']['enable_wholesale'],
                'price'            => wc_format_decimal( $data['wholesale']['price'] ?? 0 ),
                'quantity'         => absint( $data['wholesale']['quantity'] ?? 0 ),
            ];
            update_post_meta( $variation_id, self::WHOLESALE_META_KEY, $wholesale_data );
        }
    }
}
