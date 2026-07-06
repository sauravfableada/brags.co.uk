<?php

namespace WeDevs\DokanPro\Product;

defined( 'ABSPATH' ) || exit;

/**
 * Renders custom product form fields on WooCommerce single product page.
 *
 * @since 5.0.0
 *
 * @package WeDevs\DokanPro\Modules\ProductEditor
 */
class Frontend {

    /**
     * Schema cache keyed by product ID.
     *
     * @since 5.0.0
     *
     * @var array
     */
    private static array $schema_cache = [];

    /**
     * Custom field definitions, built once per request.
     *
     * Variation custom fields share the same definitions across every
     * variation and product, so the set is built once and reused; only the
     * values are resolved per variation.
     *
     * @since 5.0.5
     *
     * @var array|null
     */
    private static ?array $custom_field_defs = null;

    /**
     * Class Constructor.
     *
     * @since 5.0.0
     */
    public function __construct() {
        add_filter( 'woocommerce_product_tabs', [ $this, 'register_custom_tabs' ], 99 );
        add_action( 'woocommerce_single_product_summary', [ $this, 'print_general_section_custom_fields' ], 60 );
        add_action( 'woocommerce_product_additional_information', [ $this, 'print_section_custom_fields' ], 20 );

        // Variation custom fields support.
        add_filter( 'woocommerce_available_variation', [ $this, 'add_variation_custom_fields' ], 10, 3 );
        add_action( 'woocommerce_single_product_summary', [ $this, 'print_variation_custom_fields_container' ], 65 );
        add_action( 'wp_footer', [ $this, 'print_variation_custom_fields_js' ] );
    }


    /**
     * Register custom sections as WooCommerce product tabs.
     *
     * @since 5.0.0
     *
     * @param array $tabs Existing WooCommerce product tabs.
     *
     * @return array Modified tabs array.
     */
    public function register_custom_tabs( array $tabs ): array {
        global $product;

        if ( ! $product instanceof \WC_Product ) {
            return $tabs;
        }

        $schema   = $this->get_schema( $product->get_id() );
        $sections = $this->get_sections( $schema, true );

        foreach ( $sections as $section ) {
            $section_id = $section['id'];
            $fields     = $this->get_custom_fields_by_section( $schema, $section_id );

            if ( empty( $fields ) ) {
                continue;
            }

            $tabs[ $section_id ] = [
                'title'    => $section['label'] ?? $section_id,
                'priority' => $section['priority'] ?? 50,
                'callback' => [ $this, 'print_custom_section_content' ],
                'id'       => $section_id,
            ];
        }

        return $tabs;
    }

    /**
     * Print custom fields from the General section on the single product page.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function print_general_section_custom_fields(): void {
        global $product;

        if ( ! $product instanceof \WC_Product ) {
            return;
        }

        $schema = $this->get_schema( $product->get_id() );
        $fields = $this->get_custom_fields_by_section( $schema, 'general' );

        if ( empty( $fields ) ) {
            return;
        }

        $this->render_field_table( $fields );
    }

    /**
     * Print content for a custom product tab.
     *
     * @since 5.0.0
     *
     * @param string $tab_id The tab key.
     * @param array  $tab    The tab data array.
     *
     * @return void
     */
    public function print_custom_section_content( string $tab_id, array $tab ): void {
        global $product;

        if ( ! $product instanceof \WC_Product ) {
            return;
        }

        $section_id = $tab['id'] ?? $tab_id;
        $schema     = $this->get_schema( $product->get_id() );
        $fields     = $this->get_custom_fields_by_section( $schema, $section_id );

        if ( empty( $fields ) ) {
            return;
        }

        $this->render_field_table( $fields );
    }

    /**
     * Print custom fields from the Shipping section in the Additional Information tab.
     *
     * @since 5.0.0
     *
     * @param \WC_Product $product The product object.
     *
     * @return void
     */
    public function print_section_custom_fields( $product ): void {
        if ( ! $product instanceof \WC_Product ) {
            return;
        }

        $schema = $this->get_schema( $product->get_id() );
        // get all the sections except 'general' since they have their own dedicated areas.
        $sections = array_filter(
            $this->get_sections( $schema ),
            function ( $item ) {
                return ! in_array( $item['id'], [ 'general' ], true );
            }
        );

        foreach ( $sections as $section ) {
            $section_id = $section['id'];
            $fields     = $this->get_custom_fields_by_section( $schema, $section_id );

            if ( empty( $fields ) ) {
                continue;
            }

            $this->render_field_table( $fields );
        }
    }

    /**
     * Get the full schema for a product with static caching.
     *
     * @since 5.0.0
     *
     * @param int $product_id Product ID.
     *
     * @return array Flat schema array with resolved values.
     */
    private function get_schema( int $product_id ): array {
        if ( ! isset( self::$schema_cache[ $product_id ] ) ) {
            self::$schema_cache[ $product_id ] = dokan()->product_editor->get_schema( $product_id );
        }

        return self::$schema_cache[ $product_id ];
    }

    /**
     * Extract custom fields from a specific section.
     *
     * @since 5.0.0
     *
     * @param array  $schema     Flat schema array.
     * @param string $section_id The section ID to filter by.
     *
     * @return array Array of custom field items.
     */
    private function get_custom_fields_by_section( array $schema, string $section_id ): array {
        return array_filter(
            $schema,
            function ( $item ) use ( $section_id ) {
                return isset( $item['type'], $item['section_id'] )
                    && 'field' === $item['type']
                    && $section_id === $item['section_id']
                    && ! empty( $item['is_custom'] )
                    && ( ! isset( $item['visibility'] ) || false !== $item['visibility'] );
            }
        );
    }

    /**
     * Extract custom sections from the schema.
     *
     * @since 5.0.0
     *
     * @param array $schema Flat schema array.
     *
     * @return array Array of custom section items.
     */
    private function get_sections( array $schema, bool $is_custom = false ): array {
        return array_filter(
            $schema,
            function ( $item ) use ( $is_custom ) {
                $is_section = isset( $item['type'], $item['id'] )
                    && 'section' === $item['type']
                    && ( ! isset( $item['visibility'] ) || false !== $item['visibility'] );

                if ( ! $is_section ) {
                    return false;
                }

                if ( $is_custom ) {
                    return ! empty( $item['is_custom'] );
                }

                return empty( $item['is_custom'] );
            }
        );
    }

    /**
     * Render a table of custom field values.
     *
     * @since 5.0.0
     *
     * @param array $fields Array of custom field items from the schema.
     *
     * @return void
     */
    private function render_field_table( array $fields ): void {
        $fields = array_filter(
            $fields,
            function ( $field ) {
                $value = $field['value'] ?? '';

                return is_array( $value ) ? ! empty( $value ) : ( '' !== $value && null !== $value );
            }
        );

        if ( empty( $fields ) ) {
            return;
        }

        echo '<table class="woocommerce-product-attributes shop_attributes dokan-custom-fields-table">';
        echo '<tbody>';

        foreach ( $fields as $field ) {
            $label   = $field['label'] ?? '';
            $value   = $field['value'] ?? '';
            $variant = $field['variant'] ?? 'text';
            $options = $field['options'] ?? [];

            echo '<tr class="dokan-custom-field dokan-custom-field-' . esc_attr( $field['id'] ) . '">';
            echo '<th>' . esc_html( $label ) . '</th>';
            echo '<td>';
            echo wp_kses_post( $this->render_field_value( $variant, $value, $options ) );
            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
    }

    /**
     * Render a field value based on its variant type.
     *
     * @since 5.0.0
     *
     * @param string $variant The field variant.
     * @param mixed  $value   The resolved field value.
     * @param array  $options The field options.
     *
     * @return string HTML output for the field value.
     */
    private function render_field_value( string $variant, $value, array $options ): string {
        switch ( $variant ) {
            case 'radio':
            case 'select':
                return esc_html( Helper::get_field_option_label_by_value( $options, $value ) );

            case 'multiselect':
                $values = is_array( $value ) ? $value : explode( ',', $value );

                return esc_html( Helper::get_field_option_labels_by_values( $options, $values ) );

            case 'checkbox':
                $is_checked = in_array( $value, [ true, 'yes', 'on', 1, '1' ], true );

                return $is_checked
                    ? esc_html__( 'Yes', 'dokan' )
                    : esc_html__( 'No', 'dokan' );

            case 'datetime':
                return esc_html( Helper::get_formatted_date_label( $value ) );

            case 'image':
                $image_id = is_array( $value ) ? ( $value['id'] ?? 0 ) : (int) $value;

                if ( $image_id ) {
                    return wp_get_attachment_image( $image_id, 'thumbnail', false, [ 'class' => 'dokan-custom-field-img' ] );
                }

                return '';

            case 'file':
                // Value shape: [ { id, file, name }, ... ].
                if ( ! is_array( $value ) || empty( $value ) ) {
                    return '';
                }

                $links = [];
                foreach ( $value as $file ) {
                    $name = ! empty( $file['name'] ) ? $file['name'] : basename( $file['file'] ?? '' );
                    $url  = $file['file'] ?? '';

                    if ( $url ) {
                        $links[] = '<a class="dokan-custom-field-file-link" href="' . esc_url( $url ) . '" target="_blank">' . esc_html( $name ) . '</a>';
                    }
                }

                return implode( '<br> ', $links );

            case 'textarea':
            case 'editor':
                return wp_kses_post( wpautop( $value ) );

            case 'number':
            case 'text':
            default:
                return esc_html( (string) $value );
        }
    }


    /**
     * Add custom field values to the available variation data.
     *
     * @since 5.0.0
     *
     * @param array                $variation_data Variation data.
     * @param \WC_Product_Variable $product        Variable product object.
     * @param \WC_Product_Variation $variation      Variation product object.
     *
     * @return array Modified variation data.
     */
    public function add_variation_custom_fields( array $variation_data, $product, $variation ): array {
        $fields = $this->get_custom_field_defs();

        // No custom variation fields registered — bail before any value resolution.
        if ( empty( $fields ) ) {
            return $variation_data;
        }

        // Resolve only the custom field values for this variation instead of
        // rebuilding the entire editor schema once per variation.
        $values = dokan()->product_editor->get_field_values( $fields, $variation );

        $fields_html = '';
        foreach ( $fields as $field ) {
            $value   = $values[ $field['id'] ] ?? '';
            $variant = $field['variant'] ?? 'text';
            $options = $field['options'] ?? [];

            if ( is_array( $value ) ? empty( $value ) : ( '' === $value || null === $value ) ) {
                continue;
            }

            $fields_html .= sprintf(
                '<tr class="dokan-custom-field dokan-custom-field-%s"><th>%s</th><td>%s</td></tr>',
                esc_attr( $field['id'] ),
                esc_html( $field['label'] ?? '' ),
                wp_kses_post( $this->render_field_value( $variant, $value, $options ) )
            );
        }

        if ( ! empty( $fields_html ) ) {
            $variation_data['dokan_variation_custom_fields_html'] = '<table class="woocommerce-product-attributes shop_attributes dokan-custom-fields-table"><tbody>' . $fields_html . '</tbody></table>';
        }

        return $variation_data;
    }

    /**
     * Get custom field definitions, built once per request.
     *
     * Variation custom field definitions are identical across every variation
     * and product, so the set is resolved a single time and reused. Passing
     * product ID 0 yields a definition-only schema (no per-product value
     * resolution); values are resolved per variation in
     * add_variation_custom_fields().
     *
     * @since 5.0.5
     *
     * @return array Custom field definition items.
     */
    private function get_custom_field_defs(): array {
        if ( null === self::$custom_field_defs ) {
            $schema = dokan()->product_editor->get_schema( 0 );

            self::$custom_field_defs = array_values(
                array_filter(
                    $schema,
                    function ( $item ) {
                        return isset( $item['type'] )
                            && 'field' === $item['type']
                            && ! empty( $item['is_custom'] )
                            && ( ! isset( $item['visibility'] ) || false !== $item['visibility'] );
                    }
                )
            );
        }

        return self::$custom_field_defs;
    }

    /**
     * Print a container for variation-specific custom fields.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function print_variation_custom_fields_container(): void {
        echo '<div class="dokan-variation-custom-fields-container" style="display:none;"></div>';
    }

    /**
     * Print JavaScript to update the variation custom fields container.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function print_variation_custom_fields_js(): void {
        global $post;

        if ( ! is_product() || empty( $post->ID ) ) {
            return;
        }

        $product = wc_get_product( $post->ID );
        if ( ! $product || ! $product->is_type( 'variable' ) ) {
            return;
        }
        ?>
        <script type="text/javascript">
            jQuery( function($) {
                $( '.variations_form' ).on( 'found_variation', function( event, variation ) {
                    var $container = $( '.dokan-variation-custom-fields-container' );
                    if ( variation.dokan_variation_custom_fields_html ) {
                        $container.html( variation.dokan_variation_custom_fields_html ).show();
                    } else {
                        $container.empty().hide();
                    }
                });

                $( '.variations_form' ).on( 'reset_data', function() {
                    $( '.dokan-variation-custom-fields-container' ).empty().hide();
                });
            });
        </script>
        <?php
    }
}
