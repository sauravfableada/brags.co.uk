<?php

namespace WeDevs\DokanPro\Product;

use WC_Product;
use WeDevs\Dokan\ProductEditor\Elements;

defined( 'ABSPATH' ) || exit;

/**
 * Init Product Form Fields
 *
 * @since 5.0.0
 */
class FormSchema {

    /**
     * Option key for the stored product editor form schema overrides.
     *
     * @since 5.0.0
     */
    const SETTINGS_KEY = 'dokan_product_editor';

    /**
     * Class Constructor.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function __construct() {
        add_filter( 'dokan_product_editor_schema', [ $this, 'add_product_fields' ], 10, 2 );
        add_filter( 'dokan_product_editor_layouts', [ $this, 'add_product_layouts' ] );
        add_filter( 'dokan_product_editor_schema_value', [ $this, 'resolve_fields_value' ], 10, 3 );
        add_filter( 'dokan_product_editor_schema_payload', [ $this, 'resolve_meta_booleans' ] );
    }


    /**
     * Default extension for flat form items, using dokan_product_editor_schema.
     *
     * @since 5.0.0
     *
     * @param array $fields     Flat sections + fields.
     * @param int   $product_id Product ID (may be a variation ID).
     *
     * @return array
     */
    public function add_product_fields( array $fields, int $product_id = 0 ): array {
        $product      = $product_id ? wc_get_product( $product_id ) : null;
        $is_variation = $product && $product->is_type( 'variation' );
        $fields[] = [
            'id'              => Elements::EXTERNAL_URL,
            'section_id'      => Elements::SECTION_GENERAL,
            'type'            => 'field',
            'label'           => __( 'Product URL', 'dokan' ),
            'variant'         => 'text',
            'placeholder'     => 'https://',
            'required'        => false,
            'visibility'      => true,
            'dependencies'    => [
                [
                    'comparison' => '==',
                    'key'        => Elements::TYPE,
                    'value'      => Elements::PRODUCT_TYPE_EXTERNAL,
                ],
            ],
        ];

        $fields[] = [
            'id'               => Elements::BUTTON_TEXT,
            'section_id'       => Elements::SECTION_GENERAL,
            'type'             => 'field',
            'label'            => __( 'Button text', 'dokan' ),
            'variant'          => 'text',
            'placeholder'      => __( 'Buy product', 'dokan' ),
            'required'        => false,
            'visibility'      => true,
            'dependencies'    => [
                [
                    'comparison' => '==',
                    'key'        => Elements::TYPE,
                    'value'      => Elements::PRODUCT_TYPE_EXTERNAL,
                ],
            ],
        ];

        // Shipping & Tax fields (under shipping section).
        $shipping_class_options = $this->get_shipping_class_options();
        $is_shipping_disabled = 'sell_digital' === dokan_pro()->digital_product->get_selling_product_type();
        $label = $is_shipping_disabled ? __( 'Tax', 'dokan' ) : __( 'Shipping and Tax', 'dokan' );
        $tab_desc = $is_shipping_disabled ? __( 'Manage tax for this product', 'dokan' ) : __( 'Manage shipping and tax for this product', 'dokan' );

        $fields[] = [
            'id' => Elements::SECTION_SHIPPING,
            'type' => 'section',
            'label' => $label,
            'description' => $tab_desc,
            'visibility' => true,
        ];

        $fields[] = [
            'id'               => Elements::DISABLE_SHIPPING_META,
            'section_id'       => Elements::SECTION_SHIPPING,
            'type'             => 'field',
            'label'            => __( 'This product requires shipping', 'dokan' ),
            'variant'          => 'checkbox',
            'required'        => false,
            'visibility'      => true,
        ];

        $shipping_deps = [
            [
                'comparison' => '==',
                'key'        => Elements::DISABLE_SHIPPING_META,
                'value'      => true,
            ],
        ];

        $weight_unit = get_option( 'woocommerce_weight_unit' );
        $dimension_unit = get_option( 'woocommerce_dimension_unit' );

        foreach ( [
            [ Elements::WEIGHT, __( 'Weight', 'dokan' ) ],
            [ Elements::DIMENSIONS_LENGTH, __( 'Length', 'dokan' ) ],
            [ Elements::DIMENSIONS_WIDTH, __( 'Width', 'dokan' ) ],
            [ Elements::DIMENSIONS_HEIGHT, __( 'Height', 'dokan' ) ],
        ] as $def ) {
            $label = sprintf(
                // translators: 1: field label, 2: unit
                __( '%1$s (%2$s)', 'dokan' ),
                $def[1],
                $def[0] === Elements::WEIGHT ? $weight_unit : $dimension_unit
            );
            $fields[] = [
                'id'               => $def[0],
                'section_id'       => Elements::SECTION_SHIPPING,
                'type'             => 'field',
                'label'            => $label,
                'variant'          => 'text',
                'placeholder'      => $label,
                'required'        => false,
                'visibility'      => true,
                'dependencies'    => $shipping_deps,
            ];
        }

        // Prepend "Same as parent" for variations.
        if ( $is_variation ) {
            array_unshift(
                $shipping_class_options,
                [
                    'label' => __( 'Same as parent', 'dokan' ),
                    'value' => '-1',
                ]
            );
        }

        $fields[] = [
            'id'               => Elements::SHIPPING_CLASS,
            'section_id'       => Elements::SECTION_SHIPPING,
            'type'             => 'field',
            'label'            => __( 'Shipping Class', 'dokan' ),
            'variant'          => 'select',
            'placeholder'      => __( 'Select shipping class', 'dokan' ),
            // translators: 1: shipping url
            'description'      => sprintf( __( 'Shipping classes are used by certain shipping methods to group similar products. Before adding a product, please configure the <a href="%s"> shipping settings </a>', 'dokan' ), dokan_get_navigation_url( 'settings/shipping' ) ),
            'options'         => $shipping_class_options,
            'required'        => false,
            'visibility'      => true,
        ];

        $fields[] = [
            'id'               => Elements::TAX_STATUS,
            'section_id'       => Elements::SECTION_SHIPPING,
            'type'             => 'field',
            'label'            => __( 'Tax Status', 'dokan' ),
            'variant'          => 'select',
            'options'         => [
                'taxable'  => __( 'Taxable', 'dokan' ),
                'shipping' => __( 'Shipping only', 'dokan' ),
                'none'     => _x( 'None', 'Tax status', 'dokan' ),
            ],
            'required'        => false,
            'visibility'      => true,
        ];

        $tax_class_options = wc_get_product_tax_class_options();

        // Prepend "Same as parent" for variations.
        if ( $is_variation ) {
            $tax_class_options = array_merge(
                [ 'parent' => __( 'Same as parent', 'dokan' ) ],
                $tax_class_options
            );
        }

        $fields[] = [
            'id'               => Elements::TAX_CLASS,
            'section_id'       => Elements::SECTION_SHIPPING,
            'type'             => 'field',
            'label'            => __( 'Tax Class', 'dokan' ),
            'variant'          => 'select',
            'options'         => $tax_class_options,
            'required'        => false,
            'visibility'      => true,
        ];

        $fields[] = [
            'id'               => Elements::OVERWRITE_SHIPPING_META,
            'section_id'       => Elements::SECTION_SHIPPING,
            'type'             => 'field',
            'label'            => __( 'Override your store\'s default shipping cost for this product', 'dokan' ),
            'variant'          => 'checkbox',
            'value'            => false,
            'required'        => false,
            'visibility'      => true,
            'dependencies'    => $shipping_deps,
        ];

        $shipping_cost_deps = [
            [
                'comparison' => '==',
                'key'        => Elements::OVERWRITE_SHIPPING_META,
                'value'      => true,
            ],
        ];

        foreach ( [
            [ Elements::ADDITIONAL_SHIPPING_COST_META, __( 'Additional cost', 'dokan' ), '0.00' ],
            [ Elements::ADDITIONAL_SHIPPING_QUANTITY_META, __( 'Per Qty Additional Price', 'dokan' ), '0' ],
        ] as $def ) {
            $fields[] = [
                'id'               => $def[0],
                'section_id'       => Elements::SECTION_SHIPPING,
                'type'             => 'field',
                'label'            => $def[1],
                'variant'          => 'number',
                'placeholder'      => $def[2],
                'required'        => false,
                'visibility'      => true,
                'dependencies'    => $shipping_cost_deps,
            ];
        }

        $fields[] = [
            'id'               => Elements::ADDITIONAL_SHIPPING_PROCESSING_TIME_META,
            'section_id'       => Elements::SECTION_SHIPPING,
            'type'             => 'field',
            'label'            => __( 'Processing Time', 'dokan' ),
            'variant'          => 'select',
            'placeholder'      => __( 'Select processing time', 'dokan' ),
            'options'         => dokan_get_shipping_processing_times(),
            'required'        => false,
            'visibility'      => true,
            'dependencies'    => $shipping_cost_deps,
        ];

        // attributes
        $fields[] = [
            'id'          => Elements::SECTION_ATTRIBUTES_AND_VARIATIONS,
            'label'       => __( 'Attributes', 'dokan' ),
            'type'        => 'section',
            'description' => __( 'Manage attributes and variations for this variable product.', 'dokan' ),
            'priority'    => 20,
        ];

        $fields[] = [
            'id'               => Elements::ATTRIBUTES,
            'section_id'       => Elements::SECTION_ATTRIBUTES_AND_VARIATIONS,
            'type'             => 'field',
            'label'            => __( 'Attributes', 'dokan' ),
            'variant'          => 'attribute',
            // Global attribute taxonomies and their terms are loaded lazily by
            // the product editor via the products/attributes REST endpoints, so
            // they are not embedded in the schema (avoids exhausting memory on
            // stores with large attribute taxonomies).
            'required'        => false,
            'priority'        => 20,
            'visibility'      => true,
        ];

        // linked product
        $fields[] = [
            'id'          => Elements::SECTION_LINKED,
            'type'        => 'section',
            'label'       => __( 'Linked Products', 'dokan' ),
            'description' => __( 'Set your linked products for upsell and cross-sells', 'dokan' ),
        ];

        $fields[] = [
            'id'               => Elements::UPSELL_IDS,
            'section_id'       => Elements::SECTION_LINKED,
            'type'             => 'field',
            'label'            => __( 'Upsells', 'dokan' ),
            'variant'          => 'async_select',
            'api_endpoint'     => '/dokan/v2/products?product_type=simple',
            'placeholder'      => __( 'Search for a product...', 'dokan' ),
            'required'        => false,
            'visibility'      => true,
        ];

        $fields[] = [
            'id'               => Elements::CROSS_SELL_IDS,
            'section_id'       => Elements::SECTION_LINKED,
            'type'             => 'field',
            'label'            => __( 'Cross-sells', 'dokan' ),
            'variant'          => 'async_select',
            'api_endpoint'     => '/dokan/v2/products?product_type=simple',
            'placeholder'      => __( 'Search for a product...', 'dokan' ),
            'required'        => false,
            'visibility'      => true,
        ];

        $fields[] = [
            'id'               => Elements::GROUPED_PRODUCTS,
            'section_id'       => Elements::SECTION_LINKED,
            'type'             => 'field',
            'label'            => __( 'Grouped products', 'dokan' ),
            'variant'          => 'async_select',
            'api_endpoint'     => '/dokan/v2/products?product_type=grouped',
            'placeholder'      => __( 'Search for a product...', 'dokan' ),
            'required'        => false,
            'visibility'      => true,
            'dependencies'    => [
                [
                    'comparison' => '==',
                    'key'        => Elements::TYPE,
                    'value'      => Elements::PRODUCT_TYPE_GROUPED,
                ],
            ],
        ];

        foreach ( $fields as &$field ) {
            if ( isset( $field['section_id'] ) && $field['section_id'] === Elements::SECTION_SHIPPING ) {
                $field['visibilities'] = array_merge(
                    $field['visibilities'] ?? [],
                    [
                        Elements::PRODUCT_TYPE_GROUPED  => false,
                        Elements::PRODUCT_TYPE_EXTERNAL => false,
                    ]
                );
            }
        }

        return $fields;
    }

    /**
     * Add Pro section layouts to the product editor form layout.
     *
     * @since 5.0.0
     *
     * @param array $layouts Flat layout items.
     *
     * @return array
     */
    public function add_product_layouts( array $layouts ): array {
        // 5. Linked Products section.
        $layouts[] = [
            'id'        => Elements::SECTION_LINKED,
            'parent_id' => Elements::PRIMARY_COLUMN,
            'priority'  => 80,
            'layout'    => [
                'type'       => 'card',
                'withHeader' => true,
            ],
        ];

        // 6. Attributes & Variations section.
        $layouts[] = [
            'id'        => Elements::SECTION_ATTRIBUTES_AND_VARIATIONS,
            'parent_id' => Elements::PRIMARY_COLUMN,
            'priority'  => 60,
            'layout'    => [
                'type'       => 'card',
                'withHeader' => true,
            ],
        ];

        return $layouts;
    }

    /**
     * Resolve field values for product editor fields.
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
        switch ( $field_name ) {
            case Elements::ATTRIBUTES:
                return $this->get_attributes_value( $product );
            case Elements::DISABLE_SHIPPING_META:
                if ( $product->is_type( 'variation' ) ) {
                    return true;
                }
                $meta_value = $product->get_meta( Elements::DISABLE_SHIPPING_META, true );
                if ( empty( $meta_value ) ) {
                    return true;
                }
                // Meta is inverse: '_disable_shipping' === 'yes' means shipping disabled.
                return $meta_value !== 'yes';
            case Elements::SHIPPING_CLASS:
                $shipping_class_id = $product->get_shipping_class_id( 'edit' );
                // Variation with no explicit shipping class → "Same as parent".
                if ( $product->is_type( 'variation' ) && empty( $shipping_class_id ) ) {
                    return '-1';
                }
                return $shipping_class_id;
            case Elements::TAX_CLASS:
                // Use 'edit' context so 'parent' is preserved instead of being resolved to the parent's actual tax class.
                return $product->get_tax_class( 'edit' );
            case Elements::UPSELL_IDS:
                return $this->products_to_async_options( (array) $product->get_upsell_ids() );
            case Elements::CROSS_SELL_IDS:
                return $this->products_to_async_options( (array) $product->get_cross_sell_ids() );
            case Elements::GROUPED_PRODUCTS:
                return $this->products_to_async_options( (array) $product->get_children() );
            default:
                return $value;
        }
    }

    private function get_attributes_value( WC_Product $product ): array {
        $attributes = [];
        $raw_attributes = $product->get_attributes( 'edit' );

        // Variations return slug => value (strings), not WC_Product_Attribute objects.
        if ( $product->is_type( 'variation' ) ) {
            return [];
        }

        $defaults = $product->get_default_attributes();

        foreach ( $raw_attributes as $attr ) {
            if ( ! is_object( $attr ) || ! method_exists( $attr, 'is_taxonomy' ) ) {
                continue;
            }
            $terms = [];
            if ( $attr->is_taxonomy() && ! empty( $attr->get_options() ) ) {
                $wp_terms = get_terms(
                    [
                        'taxonomy'   => $attr->get_name(),
                        'include'    => $attr->get_options(),
                        'hide_empty' => false,
                    ]
                );

                if ( ! is_wp_error( $wp_terms ) ) {
                    foreach ( $wp_terms as $term ) {
                        $terms[] = [
                            'label' => $term->name,
                            'value' => $term->term_id,
                        ];
                    }
                }
            }

            // Resolve the default value for this attribute.
            $default_option = '';
            $attr_taxonomy  = $attr->get_name();
            // WC stores custom attribute defaults keyed by sanitize_title( name ).
            $lookup_key = $attr->is_taxonomy() ? $attr_taxonomy : sanitize_title( $attr_taxonomy );
            if ( $attr->get_variation() && isset( $defaults[ $lookup_key ] ) ) {
                $default_option = $defaults[ $lookup_key ];
                // Convert term slug to display name for taxonomy attributes.
                if ( $attr->is_taxonomy() ) {
                    $term = get_term_by( 'slug', $default_option, $attr_taxonomy );
                    if ( $term && ! is_wp_error( $term ) ) {
                        $default_option = $term->name;
                    }
                }
            }

            $attributes[] = [
                'id'          => $attr->get_id(),
                'name'        => wc_attribute_label( $attr->get_name() ),
                'value'       => $attr->get_name(),
                'options'     => $attr->get_options(),
                'visible'     => $attr->get_visible(),
                'variation'   => $attr->get_variation(),
                'position'    => $attr->get_position(),
                'is_taxonomy' => $attr->is_taxonomy(),
                'terms'       => $terms,
                'default'     => $default_option,
            ];
        }

        return $attributes;
    }

    private function products_to_async_options( array $ids ): array {
        $options = [];
        foreach ( array_filter( array_map( 'absint', $ids ) ) as $id ) {
            $p = wc_get_product( $id );
            if ( ! $p ) {
                continue;
            }
            $options[] = [
                'label' => $p->get_name(),
                'value' => $id,
            ];
        }
        return $options;
    }

    /**
     * Get variation form layout items.
     *
     * Returns a flat array of layout items with parent-child relationships.
     * Same format as FormSchema::get_layouts() in dokan-lite.
     *
     * @since 5.0.0
     *
     * @return array Flat layout items with parent-child relationships.
     */
    public static function get_variation_layouts(): array {
        $layouts = [
            // Image + digital options + SKU (responsive row).
            [
                'id'         => 'variation_image_sku',
                'parent_id'  => null,
                'priority'   => 10,
                'layout'     => [
                    'type'      => 'row',
                    'alignment' => 'start',
                ],
                'responsive' => [
                    [
                        'maxWidth' => 768,
                        'layout'   => [ 'type' => 'regular' ],
                    ],
                ],
                'children'   => [],
            ],
            // Image + digital options wrapper.
            [
                'id'        => 'image_and_digital_options',
                'parent_id' => 'variation_image_sku',
                'priority'  => 10,
                'layout'    => [
                    'type'      => 'row',
                    'alignment' => 'start',
                    'styles'    => [
                        Elements::FEATURED_IMAGE_ID     => [ 'width' => 'max-content' ],
                        'variable_downloadable_options' => [ 'flex' => '1' ],
                    ],
                ],
                'children'  => [ Elements::FEATURED_IMAGE_ID ],
            ],
            // Digital options group (enabled, downloadable, virtual, manage_stock).
            [
                'id'        => 'variable_downloadable_options',
                'parent_id' => 'image_and_digital_options',
                'priority'  => 10,
                'children'  => [
                    Elements::ENABLED,
                    Elements::DOWNLOADABLE,
                    Elements::VIRTUAL,
                    Elements::MANAGE_STOCK,
                ],
            ],
            // SKU wrapper.
            [
                'id'        => 'variation_sku',
                'parent_id' => 'variation_image_sku',
                'priority'  => 20,
                'layout'    => [ 'type' => 'regular' ],
                'children'  => [ Elements::SKU, Elements::STOCK_STATUS ],
            ],

            // Prices row.
            [
                'id'        => 'variation_prices',
                'parent_id' => null,
                'priority'  => 20,
                'layout'    => [
                    'type'      => 'row',
                    'alignment' => 'start',
                ],
                'children'  => [ Elements::REGULAR_PRICE, Elements::SALE_PRICE ],
            ],

            // Discount toggle (standalone field).
            [
                'id'        => 'variation_discount_toggle',
                'parent_id' => null,
                'priority'  => 30,
                'children'  => [ Elements::CREATE_SCHEDULE_FOR_DISCOUNT ],
            ],

            // Discount schedule row.
            [
                'id'        => 'variation_discount_schedule',
                'parent_id' => null,
                'priority'  => 40,
                'layout'    => [ 'type' => 'row' ],
                'children'  => [ Elements::DATE_ON_SALE_FROM, Elements::DATE_ON_SALE_TO ],
            ],

            // Subscription fields.
            [
                'id'        => 'variation_subscription',
                'parent_id' => null,
                'priority'  => 50,
                'children'  => [
                    '_subscription_period_interval',
                    '_subscription_period',
                    '_subscription_length',
                    '_subscription_sign_up_fee',
                    '_subscription_trial_length',
                    '_subscription_trial_period',
                ],
            ],

            // Stock quantity + backorders row.
            [
                'id'        => 'variation_stock_row',
                'parent_id' => null,
                'priority'  => 60,
                'layout'    => [
                    'type'      => 'row',
                    'alignment' => 'start',
                ],
                'children'  => [ Elements::STOCK_QUANTITY, Elements::BACKORDERS ],
            ],

            [
                'id'        => 'variation_shipping_dimensions',
                'parent_id' => null,
                'priority'  => 60,
                'layout'    => [ 'type' => 'row' ],
                'children'  => [
                    Elements::WEIGHT,
                    Elements::DIMENSIONS_LENGTH,
                    Elements::DIMENSIONS_WIDTH,
                    Elements::DIMENSIONS_HEIGHT,
                ],
            ],

            // Standalone fields.
            [
                'id'        => 'variation_standalone_fields',
                'parent_id' => null,
                'priority'  => 70,
                'children'  => [
                    Elements::LOW_STOCK_AMOUNT,
                    Elements::SHIPPING_CLASS,
                    Elements::TAX_CLASS,
                    Elements::DESCRIPTION,
                ],
            ],

            // Downloads section (card with header).
            [
                'id'        => 'variation_downloads',
                'parent_id' => null,
                'priority'  => 80,
                'layout'    => [
                    'type'       => 'card',
                    'withHeader' => true,
                ],
                'children'  => [ Elements::DOWNLOADS ],
            ],
            // Download limit + expiry row.
            [
                'id'        => 'variation_downloads_settings',
                'parent_id' => 'variation_downloads',
                'priority'  => 10,
                'layout'    => [
                    'type'      => 'row',
                    'alignment' => 'start',
                ],
                'children'  => [ Elements::DOWNLOAD_LIMIT, Elements::DOWNLOAD_EXPIRY ],
            ],

            // Wholesale section (with heading).
            [
                'id'        => 'variation_wholesale_section',
                'parent_id' => null,
                'priority'  => 90,
                'children'  => [ 'enable_wholesale' ],
            ],
            // Wholesale price + quantity row.
            [
                'id'        => 'variation_wholesale_fields',
                'parent_id' => 'variation_wholesale_section',
                'priority'  => 10,
                'layout'    => [
                    'type'      => 'row',
                    'alignment' => 'start',
                ],
                'children'  => [ 'wholesale_price', 'wholesale_quantity' ],
            ],

            // Min/Max order quantity section (with heading).
            [
                'id'        => 'variation_min_max_section',
                'parent_id' => null,
                'priority'  => 100,
                'children'  => [ 'min_quantity', 'max_quantity' ],
            ],
        ];

        $layouts = array_map(
            static function ( $item ) {
                if ( ! isset( $item['children'] ) ) {
                    $item['children'] = [];
                }

                /**
                 * Filter individual variation layout item children.
                 *
                 * @since 5.0.0
                 *
                 * @param array $children Child field IDs.
                 * @param array $item     The layout item.
                 */
                $item['children'] = apply_filters( 'dokan_product_editor_variation_layout_children', $item['children'], $item );

                return $item;
            },
            $layouts
        );

        /**
         * Filter the variation form layout.
         *
         * Flat array of layout items with parent-child relationships,
         * same format as `dokan_product_editor_layouts`.
         *
         * @since 5.0.0
         *
         * @param array $layouts Flat layout items.
         */
        $layouts = apply_filters( 'dokan_product_editor_variation_layouts', $layouts );

        usort(
            $layouts,
            static function ( $a, $b ) {
                return ( $a['priority'] ?? 999 ) <=> ( $b['priority'] ?? 999 );
            }
        );

        return $layouts;
    }

    private function get_shipping_class_options(): array {
        $options = [
            [
                'label' => __( 'No shipping class', 'dokan' ),
                'value' => '',
            ],
        ];

        if ( WC()->shipping() ) {
            foreach ( WC()->shipping()->get_shipping_classes() as $shipping_class ) {
                $options[] = [
                    'label' => $shipping_class->name,
                    'value' => $shipping_class->term_id,
                ];
            }
        }

        return $options;
    }


    /**
     * Convert boolean meta flags to yes/no strings expected by WooCommerce.
     *
     * @since 5.0.0
     */
    public static function resolve_meta_booleans( array $data ): array {
        if ( array_key_exists( Elements::DISABLE_SHIPPING_META, $data ) ) {
            // Form: true = requires shipping, Meta: 'no' = not disabled.
            $data[ Elements::DISABLE_SHIPPING_META ] = $data[ Elements::DISABLE_SHIPPING_META ] ? 'no' : 'yes';
        }

        if ( array_key_exists( Elements::OVERWRITE_SHIPPING_META, $data ) ) {
            $data[ Elements::OVERWRITE_SHIPPING_META ] = $data[ Elements::OVERWRITE_SHIPPING_META ] ? 'yes' : 'no';
        }

        if ( array_key_exists( Elements::SHIPPING_CLASS, $data ) ) {
            // "Same as parent" (-1): clear shipping class so WC inherits from the parent product.
            if ( '-1' === (string) $data[ Elements::SHIPPING_CLASS ] ) {
                $data[ Elements::SHIPPING_CLASS ] = '';
                $data['product_shipping_class']   = '';
            } else {
                $data['product_shipping_class'] = $data[ Elements::SHIPPING_CLASS ]; // for legacy support
                // get term_id to slug for shipping class, as WC expects the slug in the product_shipping_class field.
                $shipping_class_term = get_term( $data['product_shipping_class'], 'product_shipping_class' );
                if ( $shipping_class_term && ! is_wp_error( $shipping_class_term ) ) {
                    $data[ Elements::SHIPPING_CLASS ] = $shipping_class_term->slug;
                } else {
                    unset( $data[ Elements::SHIPPING_CLASS ] );
                }
            }
        }

        return $data;
    }
}
