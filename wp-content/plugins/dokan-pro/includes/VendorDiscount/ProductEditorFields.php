<?php

namespace WeDevs\DokanPro\VendorDiscount;

use WeDevs\DokanPro\VendorDiscount\ProductDiscount;
use WeDevs\Dokan\ProductEditor\Elements;

defined( 'ABSPATH' ) || exit;

/**
 * Class Product Form Fields.
 *
 * @since 5.0.0
 */
class ProductEditorFields {

    const SECTION_DISCOUNT = 'discount';

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
        add_filter( 'dokan_product_editor_schema_payload', [ $this, 'resolve_fields_payload' ] );
    }

    /**
     * Extend default fields with discount section and fields.
     *
     * @since 5.0.0
     *
     * @param array $fields Flat sections + fields.
     *
     * @return array
     */
    public function extend_default_fields( array $fields ): array {
        // return if admin didn't enabled product discount
        if ( ! dokan_pro()->vendor_discount->admin_settings->is_product_discount_enabled() ) {
            return $fields;
        }
        $fields[] = [
            'id'          => self::SECTION_DISCOUNT,
            'type'        => 'section',
            'label'       => __( 'Discount Options', 'dokan' ),
            'description' => __( 'Set your discount for this product', 'dokan' ),
            'visibility'  => true,
        ];

        $fields[] = [
            'id'         => ProductDiscount::IS_LOT_DISCOUNT,
            'section_id' => self::SECTION_DISCOUNT,
            'type'       => 'field',
            'label'      => __( 'Enable bulk discount', 'dokan' ),
            'variant'    => 'checkbox',
            'required'   => false,
            'visibility' => true,
        ];

        $fields[] = [
            'id'                    => ProductDiscount::LOT_DISCOUNT_QUANTITY,
            'section_id'            => self::SECTION_DISCOUNT,
            'type'                  => 'field',
            'label'                 => __( 'Minimum quantity', 'dokan' ),
            'variant'               => 'number',
            'placeholder'           => '0',
            'required'              => false,
            'visibility'            => true,
            'dependencies'          => [
                [
                    'comparison' => '==',
                    'key'        => ProductDiscount::IS_LOT_DISCOUNT,
                    'value'      => true,
                ],
            ],
        ];

        $fields[] = [
            'id'                    => ProductDiscount::LOT_DISCOUNT_AMOUNT,
            'section_id'            => self::SECTION_DISCOUNT,
            'type'                  => 'field',
            'label'                 => __( 'Discount %', 'dokan' ),
            'variant'               => 'number',
            'placeholder'           => __( 'Percentage', 'dokan' ),
            'required'              => false,
            'visibility'            => true,
            'dependencies'          => [
                [
                    'comparison' => '==',
                    'key'        => ProductDiscount::IS_LOT_DISCOUNT,
                    'value'      => true,
                ],
            ],
        ];

        return $fields;
    }


    /**
     * Add discount section layout to the product editor form layout.
     *
     * @since 5.0.0
     *
     * @param array $layouts Flat layout items.
     *
     * @return array
     */
    public function extend_layouts( array $layouts ): array {
        $layouts[] = [
            'id'        => self::SECTION_DISCOUNT,
            'parent_id' => Elements::PRIMARY_COLUMN,
            'priority'  => 80,
            'layout'    => [
                'type'       => 'card',
                'withHeader' => true,
            ],
        ];

        return $layouts;
    }

    public function resolve_fields_payload( $payload ) {
        if ( isset( $payload[ ProductDiscount::IS_LOT_DISCOUNT ] ) ) {
            $payload[ ProductDiscount::IS_LOT_DISCOUNT ] = ! empty( $payload[ ProductDiscount::IS_LOT_DISCOUNT ] ) ? 'yes' : 'no';
        }
        return $payload;
    }
}
