<?php

namespace WeDevs\DokanPro\Modules\ProductAddon;

use WC_Product;
use WeDevs\Dokan\ProductEditor\Elements;
use WeDevs\DokanPro\Product\FormSchema as ProFormSchema;

defined( 'ABSPATH' ) || exit;

/**
 * Wires Product Add-ons into the new Product Form Manager (v5.0.0+).
 *
 * Data round-trips through the same `_product_addons` post meta the legacy
 * editor and storefront use, so no frontend rendering code needs to change.
 *
 * @since 5.0.3
 */
class ProductEditorFields {

    const SECTION_ID     = 'product_addons_section';
    const FIELD_ADDONS   = 'product_addons';
    const FIELD_EXCLUDE  = '_product_addons_exclude_global';
    const SAVED_FLAG_KEY = 'dokan_product_addons_saved_by_new_editor';

    // Admin-only Form Manager rows; the React variant reads their resolved state to drive what vendors see.
    const SUB_TITLE              = 'product_addons_sub_title';
    const SUB_TYPE               = 'product_addons_sub_type';
    const SUB_DISPLAY_AS         = 'product_addons_sub_display_as';
    const SUB_FORMAT_TITLE       = 'product_addons_sub_format_title';
    const SUB_DESCRIPTION        = 'product_addons_sub_description';
    const SUB_DESCRIPTION_TEXT   = 'product_addons_sub_description_text';
    const SUB_REQUIRED           = 'product_addons_sub_required_field';
    const SUB_RESTRICTIONS       = 'product_addons_sub_restrictions';
    const SUB_RESTRICTIONS_MIN   = 'product_addons_sub_restrictions_min';
    const SUB_RESTRICTIONS_MAX   = 'product_addons_sub_restrictions_max';
    const SUB_ADJUST_PRICE       = 'product_addons_sub_adjust_price';
    const SUB_ADJUST_PRICE_TYPE  = 'product_addons_sub_adjust_price_type';
    const SUB_ADJUST_PRICE_VALUE = 'product_addons_sub_adjust_price_value';
    const SUB_IMPORT_EXPORT      = 'product_addons_sub_import_export';

    /**
     * Sub-control row definitions for the Form Manager.
     *
     * Tuple shape: [ react-key, default-label, accepts-placeholder, default-required ].
     * `default-required` seeds the initial toggle position; we never use `is_mandatory` here because that flag also disables the section toggle.
     *
     * @since 5.0.3
     *
     * @return array
     */
    protected static function sub_controls(): array {
        return [
            self::SUB_TITLE              => [ 'title', __( 'Title', 'dokan' ), true, true ],
            self::SUB_TYPE               => [ 'type', __( 'Type', 'dokan' ), false, true ],
            self::SUB_DISPLAY_AS         => [ 'display_as', __( 'Display as', 'dokan' ), false, false ],
            self::SUB_FORMAT_TITLE       => [ 'format_title', __( 'Format title', 'dokan' ), false, false ],
            self::SUB_DESCRIPTION        => [ 'description', __( 'Add description toggle', 'dokan' ), false, false ],
            self::SUB_DESCRIPTION_TEXT   => [ 'description_text', __( 'Description text', 'dokan' ), true, false ],
            self::SUB_REQUIRED           => [ 'required_field', __( 'Required field', 'dokan' ), false, false ],
            self::SUB_RESTRICTIONS       => [ 'restrictions', __( 'Restrictions', 'dokan' ), false, false ],
            self::SUB_RESTRICTIONS_MIN   => [ 'restrictions_min', __( 'Minimum', 'dokan' ), true, false ],
            self::SUB_RESTRICTIONS_MAX   => [ 'restrictions_max', __( 'Maximum', 'dokan' ), true, false ],
            self::SUB_ADJUST_PRICE       => [ 'adjust_price', __( 'Adjust price', 'dokan' ), false, false ],
            self::SUB_ADJUST_PRICE_TYPE  => [ 'adjust_price_type', __( 'Price type', 'dokan' ), false, false ],
            self::SUB_ADJUST_PRICE_VALUE => [ 'adjust_price_value', __( 'Price', 'dokan' ), true, false ],
            self::SUB_IMPORT_EXPORT      => [ 'import_export', __( 'Import / Export buttons', 'dokan' ), false, false ],
        ];
    }

    public function __construct() {
        add_filter( 'dokan_product_editor_schema', [ $this, 'extend_default_fields' ] );
        add_filter( 'dokan_product_editor_layouts', [ $this, 'extend_layouts' ] );
        add_filter( 'dokan_product_editor_schema_value', [ $this, 'resolve_fields_value' ], 10, 3 );
        add_filter( 'dokan_product_editor_schema_payload', [ $this, 'resolve_payload' ] );
        // Runs after the Form Manager merge (priority 10) so stale `is_mandatory` from older saves can't keep the section toggle locked.
        add_filter( 'dokan_product_editor_prepared_schema', [ $this, 'clear_legacy_mandatory_flags' ], 15, 2 );
        // Vendor-only: hide admin-only sub-rows and (when the section is off) the addon repeater itself.
        add_filter( 'dokan_product_editor_prepared_schema', [ $this, 'strip_sub_controls_for_editor' ], 20, 2 );
        // Vendor-only: re-resolve subFields after admin overrides so the React variant sees the latest labels/required flags.
        add_filter( 'dokan_product_editor_prepared_schema', [ $this, 'inject_resolved_sub_fields' ], 30, 2 );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_product_editor_scripts' ] );
    }

    /**
     * Clears any stale `is_mandatory: true` left in the saved Form Manager option.
     *
     * The flag was previously used to lock Title / Type but it bubbled up via FieldsSection's hasMandatoryField and disabled the section toggle.
     *
     * @since 5.0.3
     *
     * @param array $items Prepared schema items.
     *
     * @return array
     */
    public function clear_legacy_mandatory_flags( array $items ): array {
        $sub_ids = array_keys( self::sub_controls() );

        foreach ( $items as &$item ) {
            $id = $item['id'] ?? '';
            if ( in_array( $id, $sub_ids, true ) || self::SECTION_ID === $id ) {
                $item['is_mandatory'] = false;
            }
        }
        unset( $item );

        return $items;
    }

    /**
     * Reads the saved Form Manager override entry for the given field id.
     *
     * @since 5.0.3
     *
     * @param string $id Schema field id to look up.
     *
     * @return array
     */
    protected static function saved_field( string $id ): array {
        if ( ! class_exists( ProFormSchema::class ) ) {
            return [];
        }

        $saved = get_option( ProFormSchema::SETTINGS_KEY, [] );
        if ( ! is_array( $saved ) ) {
            return [];
        }

        foreach ( $saved as $item ) {
            if ( ( $item['id'] ?? '' ) === $id ) {
                return is_array( $item ) ? $item : [];
            }
        }

        return [];
    }

    /**
     * Whether the Add-ons section should be rendered for the vendor.
     *
     * @since 5.0.3
     *
     * @return bool
     */
    public static function is_section_visible(): bool {
        $saved = self::saved_field( self::SECTION_ID );
        if ( array_key_exists( 'visibility', $saved ) ) {
            return (bool) $saved['visibility'];
        }
        return true;
    }

    /**
     * Resolved per-sub-control configuration map handed to the React variant.
     *
     * Each entry carries the resolved visibility / required / label / description / placeholder reflecting what the admin configured.
     *
     * @since 5.0.3
     *
     * @return array
     */
    protected static function get_sub_field_configs(): array {
        $out = [];
        foreach ( self::sub_controls() as $id => $def ) {
            [ $key, $default_label, $accepts_placeholder, $default_required ] = $def;

            $saved   = self::saved_field( $id );
            $visible = array_key_exists( 'visibility', $saved )
                ? (bool) $saved['visibility']
                : true;
            $required = array_key_exists( 'required', $saved )
                ? (bool) $saved['required']
                : (bool) $default_required;

            $out[ $key ] = [
                'visible'     => $visible,
                'required'    => $required,
                'label'       => ! empty( $saved['label'] ) ? (string) $saved['label'] : $default_label,
                'description' => isset( $saved['description'] ) ? (string) $saved['description'] : '',
                'placeholder' => $accepts_placeholder && isset( $saved['placeholder'] ) ? (string) $saved['placeholder'] : '',
            ];
        }
        return $out;
    }

    /**
     * Emits the Add-ons section, repeater field, sub-control rows and Exclude global field.
     *
     * Sections are always emitted so the admin Form Manager can re-enable the section after it has been toggled off.
     *
     * @since 5.0.3
     *
     * @param array $fields Existing schema items.
     *
     * @return array
     */
    public function extend_default_fields( array $fields ): array {
        $fields[] = [
            'id'          => self::SECTION_ID,
            'type'        => 'section',
            'label'       => __( 'Add-ons', 'dokan' ),
            'description' => __( 'Set your add-on options for this product', 'dokan' ),
            'visibility'  => true,
            'priority'    => 45,
        ];

        $fields[] = [
            'id'            => self::FIELD_ADDONS,
            'section_id'    => self::SECTION_ID,
            'type'          => 'field',
            'variant'       => 'product_addons',
            'label'         => __( 'Product Add-ons', 'dokan' ),
            'required'      => false,
            'visibility'    => true,
            // Hidden from the admin list — the section toggle stands in for this row.
            'show_in_admin' => false,
            'priority'      => 5,
            'options'       => [
                'subFields' => self::get_sub_field_configs(),
            ],
        ];

        // Sub-control rows live in the admin Form Manager only.
        $position = 0;
        foreach ( self::sub_controls() as $id => $def ) {
            [ $key, $label, $accepts_placeholder, $default_required ] = $def;

            $row = [
                'id'             => $id,
                'section_id'     => self::SECTION_ID,
                'type'           => 'field',
                'variant'        => 'checkbox',
                'label'          => $label,
                'visibility'     => true,
                'required'       => (bool) $default_required,
                // Only sub-controls that map to a text-like input expose a
                // Placeholder field — toggles, selects and button labels
                // don't need one.
                'no_placeholder' => ! $accepts_placeholder,
                'priority'       => 10 + $position,
            ];

            $fields[] = $row;
            ++$position;
        }

        $fields[] = [
            'id'          => self::FIELD_EXCLUDE,
            'section_id'  => self::SECTION_ID,
            'type'        => 'field',
            // Custom variant keeps the (REQUIRED) badge inline; the built-in `checkbox` editor stacks it below the label.
            'variant'     => 'product_addons_exclude',
            'label'       => __( 'Exclude global add-ons', 'dokan' ),
            'description' => __( 'Disable global add-ons for this product.', 'dokan' ),
            'required'    => false,
            'visibility'  => true,
            // No free-text placeholder for a checkbox.
            'no_placeholder' => true,
            'priority'    => 90,
        ];

        return $fields;
    }

    /**
     * Places the Add-ons card in the primary column of the editor layout.
     *
     * @since 5.0.3
     *
     * @param array $layouts Existing layout items.
     *
     * @return array
     */
    public function extend_layouts( array $layouts ): array {
        foreach ( $layouts as &$layout ) {
            if ( isset( $layout['id'] ) && Elements::SECTION_SHIPPING === $layout['id'] ) {
                $layout['priority'] = 50;
                break;
            }
        }
        unset( $layout );

        $layouts[] = [
            'id'        => self::SECTION_ID,
            'parent_id' => Elements::PRIMARY_COLUMN,
            'priority'  => 45,
            'layout'    => [
                'type'       => 'card',
                'withHeader' => true,
            ],
        ];

        return $layouts;
    }

    /**
     * Strips admin-only sub-control rows from the vendor schema.
     *
     * When the section itself is toggled off, also drops the addon repeater and exclude_global field so DataForms doesn't render them without the gated JS handler.
     *
     * @since 5.0.3
     *
     * @param array $items      Prepared schema items.
     * @param int   $product_id Product id (>0 when called from the vendor editor).
     *
     * @return array
     */
    public function strip_sub_controls_for_editor( array $items, int $product_id = 0 ): array {
        if ( $product_id <= 0 ) {
            return $items;
        }

        // Effective visibility comes from the already-merged schema, so admin overrides win.
        $section_visible = true;
        foreach ( $items as $item ) {
            if ( ( $item['id'] ?? '' ) === self::SECTION_ID ) {
                $section_visible = ! array_key_exists( 'visibility', $item )
                    || (bool) $item['visibility'];
                break;
            }
        }

        $sub_ids = array_keys( self::sub_controls() );
        // When the section is off the JS bundle is also gated off; dropping the addon fields here avoids DataForms rendering them with no handler and crashing on the subFields object.
        $extra_drops = $section_visible
            ? []
            : [ self::SECTION_ID, self::FIELD_ADDONS, self::FIELD_EXCLUDE ];

        return array_values(
            array_filter(
                $items,
                static fn( $item ) => ! in_array( $item['id'] ?? '', $sub_ids, true )
                    && ! in_array( $item['id'] ?? '', $extra_drops, true )
            )
        );
    }

    /**
     * Re-resolves the subFields payload right before the schema ships to the vendor editor.
     *
     * @since 5.0.3
     *
     * @param array $items      Prepared schema items.
     * @param int   $product_id Product id (>0 when called from the vendor editor).
     *
     * @return array
     */
    public function inject_resolved_sub_fields( array $items, int $product_id = 0 ): array {
        if ( $product_id <= 0 ) {
            return $items;
        }

        $configs = self::get_sub_field_configs();

        foreach ( $items as &$item ) {
            if ( ( $item['id'] ?? '' ) === self::FIELD_ADDONS ) {
                $item['options']              = $item['options'] ?? [];
                $item['options']['subFields'] = $configs;
                break;
            }
        }
        unset( $item );

        return $items;
    }

    /**
     * Hydrates the editor schema with current product meta values.
     *
     * @since 5.0.3
     *
     * @param mixed       $value      Existing value.
     * @param string      $field_name Schema field id.
     * @param WC_Product  $product    Product object.
     *
     * @return mixed
     */
    public function resolve_fields_value( $value, $field_name, $product ) {
        if ( ! $product instanceof WC_Product ) {
            return $value;
        }

        if ( self::FIELD_ADDONS === $field_name ) {
            $addons = $product->get_meta( '_product_addons', true );
            return is_array( $addons ) ? array_values( $addons ) : [];
        }

        if ( self::FIELD_EXCLUDE === $field_name ) {
            return '1' === (string) $product->get_meta( '_product_addons_exclude_global', true );
        }

        return $value;
    }

    /**
     * Persists add-on data from the editor payload and removes the schema keys
     * so the WC REST controller doesn't try to interpret them as product props.
     *
     * @since 5.0.3
     *
     * @param array $data Editor payload.
     *
     * @return array
     */
    public function resolve_payload( array $data ): array {
        $has_addons  = array_key_exists( self::FIELD_ADDONS, $data );
        $has_exclude = array_key_exists( self::FIELD_EXCLUDE, $data );

        // Sub-control ids never belong in the saved payload; strip them defensively.
        foreach ( array_keys( self::sub_controls() ) as $sub_id ) {
            unset( $data[ (string) $sub_id ] );
        }

        if ( ! $has_addons && ! $has_exclude ) {
            return $data;
        }

        $product_id = isset( $data['id'] ) ? absint( $data['id'] ) : 0;
        $product    = $product_id > 0 ? wc_get_product( $product_id ) : null;

        if ( ! $product instanceof WC_Product ) {
            unset( $data[ self::FIELD_ADDONS ], $data[ self::FIELD_EXCLUDE ] );
            return $data;
        }

        if ( $has_addons ) {
            $addons = is_array( $data[ self::FIELD_ADDONS ] ) ? $data[ self::FIELD_ADDONS ] : [];
            $product->update_meta_data( '_product_addons', $this->sanitize_addons( $addons ) );
        }

        if ( $has_exclude ) {
            $exclude = ! empty( $data[ self::FIELD_EXCLUDE ] );
            $product->update_meta_data( '_product_addons_exclude_global', $exclude ? '1' : '0' );
        }

        // Persisted here (during the payload resolve that runs on rest_pre_dispatch)
        // rather than on a later save hook: the batch path resolves every item up
        // front, so a deferred per-instance save would clobber across items. The
        // product already exists (auto-draft), so this is an update to known meta.
        $product->save();

        // Flag is keyed by product id so a multi-item request can't let one
        // product's save suppress the legacy listener for a different product.
        $GLOBALS[ self::SAVED_FLAG_KEY ][ $product_id ] = true;

        /**
         * Fires after the new product editor persists a product's add-on meta.
         *
         * @since 5.0.3
         *
         * @param int        $product_id Product ID.
         * @param WC_Product $product    Product object.
         */
        do_action( 'dokan_product_addons_saved', $product_id, $product );

        unset( $data[ self::FIELD_ADDONS ], $data[ self::FIELD_EXCLUDE ] );

        return $data;
    }

    /**
     * Normalises the add-on array coming from the React repeater into the
     * shape the legacy storefront / cart code expects.
     *
     * @since 5.0.3
     *
     * @param array $addons Raw add-ons from the request.
     *
     * @return array
     */
    protected function sanitize_addons( array $addons ): array {
        $sanitized = [];

        foreach ( $addons as $index => $addon ) {
            if ( ! is_array( $addon ) ) {
                continue;
            }

            $name = isset( $addon['name'] ) ? sanitize_text_field( wp_unslash( $addon['name'] ) ) : '';
            if ( '' === $name ) {
                continue;
            }

            $clean = [
                'name'               => $name,
                'title_format'       => $this->enum_value( $addon['title_format'] ?? 'label', [ 'label', 'heading', 'hide' ], 'label' ),
                'description_enable' => ! empty( $addon['description_enable'] ) ? 1 : 0,
                'description'        => isset( $addon['description'] ) ? wp_kses_post( wp_unslash( $addon['description'] ) ) : '',
                'type'               => $this->enum_value(
                    $addon['type'] ?? 'multiple_choice',
                    [ 'multiple_choice', 'checkbox', 'custom_text', 'custom_textarea', 'file_upload', 'custom_price', 'input_multiplier', 'heading' ],
                    'multiple_choice'
                ),
                'display'            => $this->enum_value( $addon['display'] ?? 'select', [ 'select', 'radiobutton', 'images' ], 'select' ),
                'position'           => isset( $addon['position'] ) ? absint( $addon['position'] ) : $index,
                'required'           => ! empty( $addon['required'] ) ? 1 : 0,
                'restrictions'       => ! empty( $addon['restrictions'] ) ? 1 : 0,
                'restrictions_type'  => $this->enum_value(
                    $addon['restrictions_type'] ?? 'any_text',
                    [ 'any_text', 'only_letters', 'only_numbers', 'only_letters_numbers', 'email' ],
                    'any_text'
                ),
                'adjust_price'       => ! empty( $addon['adjust_price'] ) ? 1 : 0,
                'price_type'         => $this->enum_value( $addon['price_type'] ?? 'flat_fee', [ 'flat_fee', 'quantity_based', 'percentage_based' ], 'flat_fee' ),
                'price'              => isset( $addon['price'] ) ? wc_format_decimal( $addon['price'] ) : '',
                'min'                => isset( $addon['min'] ) ? (float) $addon['min'] : 0,
                'max'                => isset( $addon['max'] ) ? (float) $addon['max'] : 0,
                'id'                 => ! empty( $addon['id'] ) ? sanitize_key( $addon['id'] ) : dokan_get_random_string(),
            ];

            $options       = isset( $addon['options'] ) && is_array( $addon['options'] ) ? $addon['options'] : [];
            $clean_options = [];

            foreach ( $options as $option ) {
                if ( ! is_array( $option ) ) {
                    continue;
                }

                $label = isset( $option['label'] ) ? sanitize_text_field( wp_unslash( $option['label'] ) ) : '';
                if ( '' === $label ) {
                    continue;
                }

                $clean_options[] = [
                    'label'      => $label,
                    'price'      => isset( $option['price'] ) ? wc_format_decimal( $option['price'] ) : '',
                    'image'      => isset( $option['image'] ) ? sanitize_text_field( wp_unslash( $option['image'] ) ) : '',
                    'price_type' => $this->enum_value(
                        $option['price_type'] ?? 'flat_fee',
                        [ 'flat_fee', 'quantity_based', 'percentage_based' ],
                        'flat_fee'
                    ),
                ];
            }

            if ( ! empty( $clean_options ) ) {
                $clean['options'] = $clean_options;
            }

            $sanitized[] = apply_filters( 'woocommerce_product_addons_save_data', $clean, $index );
        }

        /**
         * Filters the sanitized add-ons array before it is written to product meta.
         *
         * @since 5.0.3
         *
         * @param array $sanitized Sanitized add-ons.
         * @param array $addons    Raw add-ons from the editor payload.
         */
        return apply_filters( 'dokan_product_addons_sanitized', $sanitized, $addons );
    }

    /**
     * Returns $value when it is one of the allowed entries, otherwise $fallback.
     *
     * @since 5.0.3
     *
     * @param mixed  $value    Raw value.
     * @param array  $allowed  Allowed values.
     * @param string $fallback Fallback when invalid.
     *
     * @return string
     */
    protected function enum_value( $value, array $allowed, string $fallback ): string {
        $value = is_scalar( $value ) ? (string) $value : '';
        return in_array( $value, $allowed, true ) ? $value : $fallback;
    }

    /**
     * Enqueues the React variant bundle on the new vendor dashboard.
     *
     * @since 5.0.3
     *
     * @return void
     */
    public function enqueue_product_editor_scripts() {
        if ( is_admin() || ! function_exists( 'dokan_is_seller_dashboard' ) || ! dokan_is_seller_dashboard() ) {
            return;
        }

        global $wp;
        if ( ! isset( $wp->query_vars['new'] ) ) {
            return;
        }

        if ( ! self::is_section_visible() ) {
            return;
        }

        $asset_file = DOKAN_PRODUCT_ADDON_DIR . '/assets/js/product-editor-addon.asset.php';
        if ( ! file_exists( $asset_file ) ) {
            return;
        }

        $asset = require $asset_file;
        $deps  = $asset['dependencies'] ?? [];

        wp_enqueue_script(
            'dokan-product-editor-addon',
            DOKAN_PRODUCT_ADDON_ASSETS_DIR . '/js/product-editor-addon.js',
            $deps,
            $asset['version'] ?? false,
            true
        );

        wp_set_script_translations( 'dokan-product-editor-addon', 'dokan' );

        wp_add_inline_script(
            'dokan-product-editor-addon',
            'var dokanProductEditorAddon = ' . wp_json_encode(
                [
                    'globalAddonsUrl' => trailingslashit( dokan_get_navigation_url() ) . 'new/#settings/product-addon',
                ]
            ) . ';',
            'before'
        );
    }
}
