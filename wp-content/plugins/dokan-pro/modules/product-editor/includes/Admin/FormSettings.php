<?php

namespace WeDevs\DokanPro\Modules\ProductEditor\Admin;

use WeDevs\Dokan\Admin\Dashboard\Pages\AbstractPage;
use WeDevs\Dokan\ProductEditor\Elements;
use WeDevs\DokanPro\Product\FormSchema;

class FormSettings extends AbstractPage {

    /**
     * Schema properties that can be overridden by saved settings.
     *
     * Only these keys are merged from / persisted to the saved settings option;
     * everything else comes from the live PHP schema.
     *
     * @since 5.0.5
     *
     * @var string[]
     */
    const MERGEABLE_KEYS = [
        'label',
        'labels',
        'description',
        'visibility',
        'visibilities',
        'required',
        'requireds',
        'placeholder',
    ];

    public function __construct() {
        $this->register_hooks();
        add_filter( 'dokan_product_editor_prepared_schema', [ $this, 'form_schema_response' ] );
    }

    /**
     * Get the schema properties that can be overridden by saved settings.
     *
     * Wraps the MERGEABLE_KEYS constant in a filter so third parties can
     * extend the list of mergeable properties.
     *
     * @since 5.0.5
     *
     * @return string[] List of mergeable schema property keys.
     */
    public static function get_mergeable_keys(): array {
        return apply_filters( 'dokan_product_editor_mergeable_keys', self::MERGEABLE_KEYS );
    }

    public $script_key = 'dokan-product-editor-admin';

    /**
     * Get the ID of the page.
     *
     * @since 5.0.0
     *
     * @return string
     */
    public function get_id(): string {
        return 'product_editor';
    }

    /**
     * @inheritDoc
     */
    public function menu( string $capability, string $position ): array {
        return [
            'page_title' => __( 'Product Form Manager', 'dokan' ),
            'menu_title' => __( 'Product Form Manager', 'dokan' ),
            'route'      => 'product-form-manager',
            'capability' => $capability,
            'position'   => $position ?? 110,
        ];
    }

    /**
     * @inheritDoc
     */
    public function settings(): array {
        return [];
    }

    /**
     * Get the product editor schema and product types.
     *
     * Returns the full form schema (with saved overrides merged in)
     * and the available product types including variation forms.
     *
     * @since 5.0.0
     *
     * @return array{schema: array, types: array}
     */
    public static function get_settings_data(): array {
        $form          = dokan()->product_editor;
        $schema        = $form->get_schema();

        foreach ( $schema as &$item ) {
            if ( ! isset( $item['is_custom'] ) && isset( $item['options'] ) ) {
                unset( $item['options'] );
            }
        }
        unset( $item );

        $product_types = $form->get_product_types();

        foreach ( $product_types as $type ) {
            if ( str_contains( $type['value'], 'variable' ) ) {
                // Replace "variable" with "variation" to avoid confusion with the variation type used for variations in variable products.
                $label = str_replace( 'Variable', 'Variation Product', $type['label'] );
                $value = str_replace( 'variable', 'variation', $type['value'] );

                $product_types[] = [
                    // translators: %s is replaced with the product type label, e.g. "Variation Form".
                    'label' => sprintf( __( '%s Form', 'dokan' ), esc_html( $label ) ),
                    'value' => $value,
                ];
            }
        }

        return [
            'schema' => $schema,
            'types'  => $product_types,
        ];
    }

    /**
     * @inheritDoc
     */
    public function scripts(): array {
        // No direct asset registration needed; uses main admin dashboard bundle.
        return [ $this->script_key ];
    }

    /**
     * Get the styles.
     *
     * @since 4.2.0
     *
     * @return array<string> An array of style handles.
     */
    public function styles(): array {
        return [];
    }

    /**
     * Register the page scripts and styles.
     *
     * @since 4.2.0
     *
     * @return void
     */
    public function register(): void {
        $assets = DOKAN_PRODUCT_EDITOR_DIR . '/assets/js/product-editor.asset.php';
        if ( ! file_exists( $assets ) ) {
            return;
        }
        $assets = require $assets;

        wp_register_script(
            $this->script_key,
            DOKAN_PRODUCT_EDITOR_ASSETS . '/js/product-editor.js',
            $assets['dependencies'],
            $assets['version'],
            true
        );
    }

    public static function get_data() {
        return get_option( FormSchema::SETTINGS_KEY, [] );
    }

    /**
     * Merge saved form settings into the schema response.
     *
     * Saved settings override default schema properties (label, visibility, etc.)
     * and append custom sections/fields that don't exist in the default schema.
     *
     * @since 5.0.0
     *
     * @param array $items Flat schema items from FormSchema::get_schema().
     *
     * @return array Merged schema items.
     */
    public function form_schema_response( array $items ): array {
        $saved = self::get_data();

        if ( empty( $saved ) || ! is_array( $saved ) ) {
            return $items;
        }

        // Index saved items by id for quick lookup.
        $saved_map = [];
        foreach ( $saved as $saved_item ) {
            if ( ! empty( $saved_item['id'] ) ) {
                $saved_map[ $saved_item['id'] ] = $saved_item;
            }
        }

        // Properties that can be overridden from saved settings.
        $mergeable_keys = self::get_mergeable_keys();

        $excluded_mergeable_fields = [
            Elements::WEIGHT,
            Elements::DIMENSIONS_LENGTH,
            Elements::DIMENSIONS_WIDTH,
            Elements::DIMENSIONS_HEIGHT,
            'dokan_advertise_this_product',
        ];

        $excluded_mergeable_keys = [
            'label',
            'placeholder',
        ];

        // Merge saved overrides into default schema items.
        foreach ( $items as &$item ) {
            $id = $item['id'] ?? '';

            if ( ! isset( $saved_map[ $id ] ) ) {
                continue;
            }

            $override = $saved_map[ $id ];

            foreach ( $mergeable_keys as $key ) {
                if ( array_key_exists( $key, $override ) ) {
                    if ( in_array( $id, $excluded_mergeable_fields, true ) && in_array( $key, $excluded_mergeable_keys, true ) ) {
                        continue;
                    }
                    $item[ $key ] = $override[ $key ];
                }
            }

            // Variant and options are only merged for custom fields; default fields get these from PHP.
            if ( ! empty( $override['is_custom'] ) ) {
                if ( array_key_exists( 'variant', $override ) ) {
                    $item['variant'] = $override['variant'];
                }
                if ( array_key_exists( 'options', $override ) ) {
                    $item['options'] = $override['options'];
                }
            }

            // Mark as processed so we can detect custom-only items.
            unset( $saved_map[ $id ] );
        }
        unset( $item );

        // Append custom sections and fields that only exist in saved data.
        foreach ( $saved_map as $custom_item ) {
            if ( ! empty( $custom_item['is_custom'] ) ) {
                $items[] = $custom_item;
            }
        }

        return $items;
    }
}
