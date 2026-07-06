<?php

namespace WeDevs\DokanPro\Modules\OrderMinMax;

use WC_Product;
use WC_Product_Variation;
use WeDevs\DokanPro\Modules\OrderMinMax\DataSource\ProductMinMaxSettings;
use WeDevs\Dokan\ProductEditor\Elements;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

/**
 * Class Product Form Fields.
 *
 * @since 5.0.0
 */
class ProductEditorFields {

    const MIN_MAX_SECTION = 'min_max_section';

    /**
     * Class Constructor.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function __construct() {
        add_filter( 'dokan_product_editor_schema', [ $this, 'add_order_min_max_fields' ] );
        add_filter( 'dokan_product_editor_layouts', [ $this, 'extend_layouts' ] );
        add_filter( 'dokan_product_editor_schema_value', [ $this, 'resolve_fields_value' ], 10, 3 );
        add_action( 'dokan_new_product_added', [ $this, 'save_product_data' ], 12, 2 );
        add_action( 'dokan_product_updated', [ $this, 'save_product_data' ], 12, 2 );
        add_action( 'dokan_rest_insert_product_variation_object', [ $this, 'save_product_variable_data' ], 12, 2 );
    }

    /**
     * Add min max fields to product form.
     *
     * @since 5.0.0
     *
     * @param array $fields Product form fields.
     *
     * @return array
     */
    public function add_order_min_max_fields( array $fields ): array {
        $dep_non_variable = [
            [
                'comparison' => '!=',
                'key'        => Elements::TYPE,
                'value'      => Elements::PRODUCT_TYPE_VARIABLE,
            ],
        ];

        $fields[] = [
            'id'          => self::MIN_MAX_SECTION,
            'type'        => 'section',
            'label'       => __( 'Min/Max Options', 'dokan' ),
            'description' => __( 'Manage min max options for this product', 'dokan' ),
            'visibility'  => true,
        ];

        $fields[] = [
            'id'          => Constants::SIMPLE_PRODUCT_MIN_QUANTITY,
            'section_id'  => self::MIN_MAX_SECTION,
            'type'        => 'field',
            'variant'     => 'number',
            'label'       => __( 'Min Quantity', 'dokan' ),
            'description' => __( 'Enter the minimum quantity for this product', 'dokan' ),
            'required'    => false,
            'visibility'  => true,
            'dependencies' => $dep_non_variable,
        ];

        $fields[] = [
            'id'          => Constants::SIMPLE_PRODUCT_MAX_QUANTITY,
            'section_id'  => self::MIN_MAX_SECTION,
            'type'        => 'field',
            'variant'     => 'number',
            'label'       => __( 'Max Quantity', 'dokan' ),
            'description' => __( 'Enter the maximum quantity for this product', 'dokan' ),
            'required'    => false,
            'visibility'  => true,
            'dependencies' => $dep_non_variable,
        ];

        return $fields;
    }


    /**
     * Add min/max section layout to the product editor form layout.
     *
     * @since 5.0.0
     *
     * @param array $layouts Flat layout items.
     *
     * @return array
     */
    public function extend_layouts( array $layouts ): array {
        $layouts[] = [
            'id'        => self::MIN_MAX_SECTION,
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
     * Save min/max data from the product editor payload.
     *
     * @since 5.0.0
     *
     * @param int   $product_id Product ID.
     * @param array $data       Request payload.
     *
     * @return void
     */
    public function save_product_data( $product_id, $data = [] ): void {
        if ( ! isset( $data[ Constants::SIMPLE_PRODUCT_MIN_QUANTITY ] ) && ! isset( $data[ Constants::SIMPLE_PRODUCT_MAX_QUANTITY ] ) ) {
            return;
        }

        $product = wc_get_product( $product_id );

        if ( ! $product ) {
            return;
        }

        $min_quantity = isset( $data[ Constants::SIMPLE_PRODUCT_MIN_QUANTITY ] ) && $data[ Constants::SIMPLE_PRODUCT_MIN_QUANTITY ] > 0
            ? absint( $data[ Constants::SIMPLE_PRODUCT_MIN_QUANTITY ] )
            : 0;
        $max_quantity = isset( $data[ Constants::SIMPLE_PRODUCT_MAX_QUANTITY ] ) && $data[ Constants::SIMPLE_PRODUCT_MAX_QUANTITY ] > 0
            ? absint( $data[ Constants::SIMPLE_PRODUCT_MAX_QUANTITY ] )
            : 0;

        $min_max_data = [
            ProductMinMaxSettings::MIN_QUANTITY => $min_quantity,
            ProductMinMaxSettings::MAX_QUANTITY => $max_quantity,
        ];

        $settings = new ProductMinMaxSettings( $product );
        $settings->set_data( $min_max_data );
        $settings->save();
    }

    /**
     * Save min/max data for a product variation.
     *
     * @since 5.0.0
     *
     * @param \WC_Product_Variation $variation Variation object.
     * @param WP_REST_Request       $request   REST request.
     *
     * @return void
     */
    public function save_product_variable_data( $variation, WP_REST_Request $request ) {
        $data = $request->get_params();
        /** @var WC_Product_Variation $variation */
        $this->save_product_data( $variation->get_id(), (array) $data );
    }

    /**
     * Resolve field values for min/max fields.
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

        $settings = new ProductMinMaxSettings( $product );

        switch ( $field_name ) {
            case Constants::SIMPLE_PRODUCT_MIN_QUANTITY:
                return $settings->min_quantity( 'edit' );

            case Constants::SIMPLE_PRODUCT_MAX_QUANTITY:
                return $settings->max_quantity( 'edit' );

            default:
                return $value;
        }
    }
}
