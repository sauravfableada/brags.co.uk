<?php

namespace WeDevs\DokanPro\Modules\RMA;

use WC_Product;
use WeDevs\DokanPro\Modules\RMA\Traits\RMACommon;
use WeDevs\Dokan\ProductEditor\Elements;

defined( 'ABSPATH' ) || exit;

/**
 * Class Product Form Fields.
 *
 * @since 5.0.0
 */
class ProductEditorFields {

    use RMACommon;

    const SECTION_RMA          = 'dokan_rma';
    const RMA_PRODUCT_OVERRIDE = 'dokan_rma_product_override';
    const RMA_LABEL            = 'warranty_label';
    const RMA_TYPE             = 'warranty_type';
    const RMA_LENGTH           = 'warranty_length';
    const RMA_LENGTH_VALUE     = 'warranty_length_value';
    const RMA_LENGTH_DURATION  = 'warranty_length_duration';
    const RMA_REASON           = 'warranty_reason';
    const RMA_POLICY           = 'warranty_policy';

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
    }

    /**
     * Extend default fields with RMA section and fields.
     *
     * @since 5.0.0
     *
     * @param array $fields Flat sections + fields.
     *
     * @return array
     */
    public function extend_default_fields( array $fields ): array {
        $fields[] = [
            'id'          => self::SECTION_RMA,
            'type'        => 'section',
            'label'       => __( 'RMA Options', 'dokan' ),
            'description' => __( 'Set your RMA options for this product', 'dokan' ),
            'visibility'  => true,
        ];

        $fields[] = [
            'id'         => self::RMA_PRODUCT_OVERRIDE,
            'section_id' => self::SECTION_RMA,
            'type'       => 'field',
            'label'      => __( 'Override your default RMA settings for this product', 'dokan' ),
            'variant'    => 'checkbox',
            'required'   => false,
            'visibility' => true,
        ];

        $fields[] = [
            'id'           => self::RMA_LABEL,
            'section_id'   => self::SECTION_RMA,
            'type'         => 'field',
            'label'        => __( 'Label', 'dokan' ),
            'variant'      => 'text',
            'placeholder'  => __( 'Label', 'dokan' ),
            'tooltip'      => __( 'Warranty label what customer will see', 'dokan' ),
            'required'     => false,
            'visibility'   => true,
            'dependencies' => [
                [
                    'comparison' => '==',
                    'key'        => self::RMA_PRODUCT_OVERRIDE,
                    'value'      => true,
                ],
            ],
        ];

        $fields[] = [
            'id'           => self::RMA_TYPE,
            'section_id'    => self::SECTION_RMA,
            'type'         => 'field',
            'label'        => __( 'Type', 'dokan' ),
            'variant'      => 'select',
            'tooltip'      => __( 'Warranty and Return Type', 'dokan' ),
            'options'      => $this->get_warranty_type_options(),
            'required'     => false,
            'visibility'   => true,
            'dependencies' => [
                [
                    'comparison' => '==',
                    'key'        => self::RMA_PRODUCT_OVERRIDE,
                    'value'      => true,
                ],
            ],
        ];

        $fields[] = [
            'id'           => self::RMA_LENGTH,
            'section_id'    => self::SECTION_RMA,
            'type'         => 'field',
            'label'        => __( 'Length', 'dokan' ),
            'variant'      => 'select',
            'tooltip'      => __( 'Warranty length, How many times( day, weeks month, years ) you want to give warranty ', 'dokan' ),
            'options'      => $this->get_warranty_length_options(),
            'required'     => false,
            'visibility'   => true,
            'dependencies' => [
                [
                    'comparison' => '==',
                    'key'        => self::RMA_PRODUCT_OVERRIDE,
                    'value'      => true,
                ],
                [
                    'comparison' => '==',
                    'key'        => self::RMA_TYPE,
                    'value'      => 'included_warranty',
                ],
            ],
        ];

        $fields[] = [
            'id'                    => self::RMA_LENGTH_VALUE,
            'section_id'             => self::SECTION_RMA,
            'type'                  => 'field',
            'label'                 => __( 'Length Value', 'dokan' ),
            'variant'               => 'number',
            'tooltip'               => __( 'Warranty length value', 'dokan' ),
            'required'              => false,
            'visibility'            => true,
            'dependencies'          => [
                [
                    'comparison' => '==',
                    'key'        => self::RMA_PRODUCT_OVERRIDE,
                    'value'      => true,
                ],
                [
                    'comparison' => '==',
                    'key'        => self::RMA_TYPE,
                    'value'      => 'included_warranty',
                ],
                [
                    'comparison' => '!=',
                    'key'        => self::RMA_LENGTH,
                    'value'      => 'lifetime',
                ],
            ],
        ];

        $fields[] = [
            'id'           => self::RMA_LENGTH_DURATION,
            'section_id'    => self::SECTION_RMA,
            'type'         => 'field',
            'label'        => __( 'Length Duration', 'dokan' ),
            'variant'      => 'select',
            'tooltip'      => __( 'Warranty length, How many times( day, weeks month, years ) you want to give warranty ', 'dokan' ),
            'options'      => $this->get_warranty_length_duration_options(),
            'required'     => false,
            'visibility'   => true,
            'dependencies' => [
                [
                    'comparison' => '==',
                    'key'        => self::RMA_PRODUCT_OVERRIDE,
                    'value'      => true,
                ],
                [
                    'comparison' => '==',
                    'key'        => self::RMA_TYPE,
                    'value'      => 'included_warranty',
                ],
                [
                    'comparison' => '!=',
                    'key'        => self::RMA_LENGTH,
                    'value'      => 'lifetime',
                ],
            ],
        ];

        $fields[] = [
            'id'           => self::RMA_REASON,
            'section_id'    => self::SECTION_RMA,
            'type'         => 'field',
            'label'        => __( 'Refund Reasons', 'dokan' ),
            'variant'      => 'multiselect',
            'tooltip'      => __( 'Select your return reasons which will be displayed in customer end', 'dokan' ),
            'options'      => $this->get_refund_reason_options(),
            'required'     => false,
            'visibility'   => true,
            'dependencies' => [
                [
                    'comparison' => '==',
                    'key'        => self::RMA_PRODUCT_OVERRIDE,
                    'value'      => true,
                ],
            ],
        ];

        $fields[] = [
            'id'           => self::RMA_POLICY,
            'section_id'    => self::SECTION_RMA,
            'type'         => 'field',
            'label'        => __( 'RMA Policy', 'dokan' ),
            'variant'      => 'editor',
            'tooltip'      => __( 'Your store return and warranty policy', 'dokan' ),
            'required'     => false,
            'visibility'   => true,
            'dependencies' => [
                [
                    'comparison' => '==',
                    'key'        => self::RMA_PRODUCT_OVERRIDE,
                    'value'      => true,
                ],
            ],
        ];

        return $fields;
    }

    /**
     * Add RMA section layout to the product editor form layout.
     *
     * @since 5.0.0
     *
     * @param array $layouts Flat layout items.
     *
     * @return array
     */
    public function extend_layouts( array $layouts ): array {
        $layouts[] = [
            'id'        => self::SECTION_RMA,
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
     * Resolve field values for RMA fields.
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

        $product_id = $product->get_id();

        switch ( $field_name ) {
            case self::RMA_PRODUCT_OVERRIDE:
                $override = get_post_meta( $product_id, '_dokan_rma_override_product', true );

                return 'yes' === $override;

            case self::RMA_LABEL:
                return $this->get_rma_setting_value( $product_id, 'label' );

            case self::RMA_TYPE:
                return $this->get_rma_setting_value( $product_id, 'type' );

            case self::RMA_LENGTH:
                return $this->get_rma_setting_value( $product_id, 'length' );

            case self::RMA_LENGTH_VALUE:
                return $this->get_rma_setting_value( $product_id, 'length_value' );

            case self::RMA_LENGTH_DURATION:
                return $this->get_rma_setting_value( $product_id, 'length_duration' );

            case self::RMA_REASON:
                return $this->get_rma_setting_value( $product_id, 'reasons' );

            case self::RMA_POLICY:
                return $this->get_rma_setting_value( $product_id, 'policy' );

            default:
                return $value;
        }
    }

    /**
     * Resolve RMA override from boolean to 'yes'/'no' in the save payload.
     *
     * @since 5.0.0
     *
     * @param array $data Payload data.
     *
     * @return array
     */
    public function resolve_payload( array $data ): array {
        if ( array_key_exists( self::RMA_PRODUCT_OVERRIDE, $data ) ) {
            $data[ self::RMA_PRODUCT_OVERRIDE ] = ! empty( $data[ self::RMA_PRODUCT_OVERRIDE ] ) ? 'yes' : 'no';
        }

        return $data;
    }

    /**
     * Get a specific RMA setting value for a product.
     *
     * @since 5.0.0
     *
     * @param int    $product_id Product ID.
     * @param string $key        Setting key.
     *
     * @return mixed
     */
    private function get_rma_setting_value( int $product_id, string $key ) {
        try {
            $settings = $this->get_settings( $product_id );
			return $settings[ $key ] ?? '';
        } catch ( \Exception $e ) {
            return '';
        }
    }

    /**
     * Get warranty type options formatted for select field.
     *
     * @since 5.0.0
     *
     * @return array
     */
    private function get_warranty_type_options(): array {
        $options = [];

        foreach ( dokan_rma_warranty_type() as $key => $label ) {
            $options[] = [
                'label' => $label,
                'value' => $key,
            ];
        }

        return $options;
    }

    /**
     * Get warranty length options formatted for select field.
     *
     * @since 5.0.0
     *
     * @return array
     */
    private function get_warranty_length_options(): array {
        $options = [];

        foreach ( dokan_rma_warranty_length() as $key => $label ) {
            $options[] = [
                'label' => $label,
                'value' => $key,
            ];
        }

        return $options;
    }

    /**
     * Get warranty length duration options formatted for select field.
     *
     * @since 5.0.0
     *
     * @return array
     */
    private function get_warranty_length_duration_options(): array {
        $options = [];

        foreach ( dokan_rma_warranty_length_duration() as $key => $label ) {
            $options[] = [
                'label' => $label,
                'value' => $key,
            ];
        }

        return $options;
    }

    /**
     * Get refund reason options formatted for multiselect field.
     *
     * @since 5.0.0
     *
     * @return array
     */
    private function get_refund_reason_options(): array {
        $options = [];
        $reasons = dokan_rma_refund_reasons();

        if ( empty( $reasons ) ) {
            return $options;
        }

        foreach ( $reasons as $key => $label ) {
            $options[] = [
                'label' => $label,
                'value' => $key,
            ];
        }

        return $options;
    }
}
