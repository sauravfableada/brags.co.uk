<?php

namespace WeDevs\DokanPro\Modules\VSP;

use WeDevs\Dokan\ProductEditor\Elements;

defined( 'ABSPATH' ) || exit;

/**
 * Class Product Form Fields.
 *
 * @since 5.0.0
 */
class ProductEditorFields {

    // Product type constants.
    const PRODUCT_TYPE_SUBSCRIPTION              = 'subscription';
    const PRODUCT_TYPE_VARIABLE_SUBSCRIPTION     = 'variable-subscription';
    const PRODUCT_TYPE_SUBSCRIPTION_VARIATION    = 'subscription_variation';

    // Field ID constants.
    const SUBSCRIPTION_PERIOD_INTERVAL    = '_subscription_period_interval';
    const SUBSCRIPTION_PERIOD             = '_subscription_period';
    const SUBSCRIPTION_LENGTH             = '_subscription_length';
    const SUBSCRIPTION_SIGN_UP_FEE        = '_subscription_sign_up_fee';
    const SUBSCRIPTION_TRIAL_LENGTH       = '_subscription_trial_length';
    const SUBSCRIPTION_TRIAL_PERIOD       = '_subscription_trial_period';

    /**
     * Class Constructor.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function __construct() {
        add_filter( 'dokan_product_editor_price_visibilities', [ $this, 'extend_field_visibilities' ] );
        add_filter( 'dokan_product_editor_price_labels', [ $this, 'extend_price_labels' ] );
        add_filter( 'dokan_product_editor_digital_option_visibilities', [ $this, 'extend_field_visibilities' ] );
        add_filter( 'dokan_product_editor_schema', [ $this, 'extend_default_fields' ] );
        add_filter( 'dokan_product_editor_schema_payload', [ $this, 'resolve_fields_payload' ] );
        add_filter( 'dokan_product_editor_variation_payload', [ $this, 'resolve_variation_payload' ] );
        add_action( 'dokan_rest_insert_product_variation_object', [ $this, 'save_variation_subscription_metadata' ], 10, 2 );
    }

    /**
     * Extend field visibilities for subscription product types.
     *
     * @since 5.0.0
     *
     * @param array $visibilities Per-type visibility map.
     *
     * @return array
     */
    public function extend_field_visibilities( array $visibilities ): array {
        $visibilities[ self::PRODUCT_TYPE_VARIABLE_SUBSCRIPTION ]  = false;
        $visibilities[ self::PRODUCT_TYPE_SUBSCRIPTION_VARIATION ] = true;

        return $visibilities;
    }

    public function extend_price_labels( array $labels ): array {
        $labels[ self::PRODUCT_TYPE_SUBSCRIPTION ]              = __( 'Subscription Price', 'dokan' );
        $labels[ self::PRODUCT_TYPE_VARIABLE_SUBSCRIPTION ]     = __( 'Subscription Price', 'dokan' );
        $labels[ self::PRODUCT_TYPE_SUBSCRIPTION_VARIATION ]    = __( 'Subscription Price', 'dokan' );

        return $labels;
    }

    public function extend_default_fields( array $fields ): array {
        // Per-type visibility: show for simple subscription & subscription variation, hide for variable-subscription.
        $subscription_visibilities = [
            Elements::PRODUCT_TYPE_SIMPLE                => false,
            Elements::PRODUCT_TYPE_VARIABLE              => false,
            Elements::PRODUCT_TYPE_VARIATION             => false,
            Elements::PRODUCT_TYPE_GROUPED               => false,
            Elements::PRODUCT_TYPE_EXTERNAL              => false,
            self::PRODUCT_TYPE_SUBSCRIPTION              => true,
            self::PRODUCT_TYPE_VARIABLE_SUBSCRIPTION     => false,
            self::PRODUCT_TYPE_SUBSCRIPTION_VARIATION    => true,
        ];

        $fields[] = [
            'id'           => self::SUBSCRIPTION_PERIOD_INTERVAL,
            'section_id'   => Elements::SECTION_GENERAL,
            'type'         => 'field',
            'label'        => __( 'Billing Interval', 'dokan' ),
            'variant'      => 'select',
            'placeholder'  => __( 'Select billing interval', 'dokan' ),
            'options'      => $this->get_period_interval_options(),
            'required'     => false,
            'visibility'   => true,
            'visibilities' => $subscription_visibilities,
        ];

        $fields[] = [
            'id'           => self::SUBSCRIPTION_PERIOD,
            'section_id'   => Elements::SECTION_GENERAL,
            'type'         => 'field',
            'label'        => __( 'Billing Period', 'dokan' ),
            'variant'      => 'select',
            'placeholder'  => __( 'Select billing period', 'dokan' ),
            'value'        => 'day', // Default to 'day' to ensure a valid value is always set for the subscription length field, which is required when a billing period is set.
            'options'      => $this->get_period_options(),
            'required'     => false,
            'visibility'   => true,
            'visibilities' => $subscription_visibilities,
        ];

        $length_options = $this->get_subscription_length_options();

        $fields[] = [
            'id'           => self::SUBSCRIPTION_LENGTH,
            'section_id'   => Elements::SECTION_GENERAL,
            'type'         => 'field',
            'label'        => __( 'Subscription Expire After', 'dokan' ),
            'variant'      => 'select',
            'placeholder'  => __( 'Select expire after', 'dokan' ),
            'options'      => $length_options['month'] ?? [],
            'options_map'  => $length_options,
            'dependencies' => [
                [
                    'key'        => self::SUBSCRIPTION_PERIOD,
                    'comparison' => 'not_empty',
                    'type'       => 'options',
                ],
            ],
            'required'     => false,
            'visibility'   => true,
            'visibilities' => $subscription_visibilities,
        ];

        $fields[] = [
            'id'           => self::SUBSCRIPTION_SIGN_UP_FEE,
            'section_id'   => Elements::SECTION_GENERAL,
            'type'         => 'field',
            'label'        => __( 'Sign-up Fee', 'dokan' ),
            'variant'      => 'number',
            'placeholder'  => '0.00',
            'required'     => false,
            'visibility'   => true,
            'visibilities' => $subscription_visibilities,
        ];

        $fields[] = [
            'id'           => self::SUBSCRIPTION_TRIAL_LENGTH,
            'section_id'   => Elements::SECTION_GENERAL,
            'type'         => 'field',
            'label'        => __( 'Free Trial Length', 'dokan' ),
            'variant'      => 'number',
            'placeholder'  => '0',
            'required'     => false,
            'visibility'   => true,
            'visibilities' => $subscription_visibilities,
        ];

        $fields[] = [
            'id'           => self::SUBSCRIPTION_TRIAL_PERIOD,
            'section_id'   => Elements::SECTION_GENERAL,
            'type'         => 'field',
            'label'        => __( 'Free Trial Period', 'dokan' ),
            'variant'      => 'select',
            'placeholder'  => __( 'Select free trial period', 'dokan' ),
            'options'      => $this->get_trial_period_options(),
            'required'     => false,
            'visibility'   => true,
            'visibilities' => $subscription_visibilities,
        ];

        return $fields;
    }

    /**
     * Resolve fields payload for subscription fields.
     *
     * @since 5.0.0
     *
     * @param array $payload Payload array.
     *
     * @return array
     */
    public function resolve_fields_payload( array $payload ): array {
        if ( isset( $payload[ self::SUBSCRIPTION_SIGN_UP_FEE ] ) ) {
            $payload[ self::SUBSCRIPTION_SIGN_UP_FEE ] = wc_format_decimal( $payload[ self::SUBSCRIPTION_SIGN_UP_FEE ] );
        }

        // Ensure trial period has a safe default so handle_subscription_metadata() never
        // indexes wcs_get_subscription_ranges() with an empty/missing key.
        if ( empty( $payload[ self::SUBSCRIPTION_TRIAL_PERIOD ] ) ) {
            $payload[ self::SUBSCRIPTION_TRIAL_PERIOD ] = 'day';
        }

        // Clamp trial length to the maximum allowed by WooCommerce Subscriptions.
        if ( isset( $payload[ self::SUBSCRIPTION_TRIAL_LENGTH ] ) ) {
            $trial_length        = absint( $payload[ self::SUBSCRIPTION_TRIAL_LENGTH ] );
            $subscription_ranges = wcs_get_subscription_ranges();
            $trial_period        = $payload[ self::SUBSCRIPTION_TRIAL_PERIOD ];

            if ( $trial_length > 0 && isset( $subscription_ranges[ $trial_period ] ) ) {
                $max_trial_length = count( $subscription_ranges[ $trial_period ] ) - 1;
                $trial_length     = min( $trial_length, $max_trial_length );
            }

            $payload[ self::SUBSCRIPTION_TRIAL_LENGTH ] = $trial_length;
        }

        // Map form manager field names to the keys that handle_subscription_metadata() expects in $_POST.
        if ( isset( $payload[ Elements::TYPE ] ) ) {
            $payload['product_type'] = $payload[ Elements::TYPE ];
        }

        if ( isset( $payload[ Elements::REGULAR_PRICE ] ) ) {
            $payload['_subscription_price'] = wc_format_decimal( $payload[ Elements::REGULAR_PRICE ] );
        }

        if ( isset( $payload[ Elements::SALE_PRICE ] ) ) {
            $payload['_subscription_sale_price'] = wc_format_decimal( $payload[ Elements::SALE_PRICE ] );
        }

        if ( isset( $payload[ Elements::DATE_ON_SALE_FROM ] ) ) {
            $payload['_subscription_sale_price_dates_from'] = $payload[ Elements::DATE_ON_SALE_FROM ];
        }

        if ( isset( $payload[ Elements::DATE_ON_SALE_TO ] ) ) {
            $payload['_subscription_sale_price_dates_to'] = $payload[ Elements::DATE_ON_SALE_TO ];
        }

        return $payload;
    }

    /**
     * Resolve variation payload for subscription fields.
     *
     * Moves subscription meta fields into the `meta_data` array so the
     * WC REST Product Variations Controller persists them.
     *
     * @since 5.0.0
     *
     * @param array $payload Resolved variation payload.
     *
     * @return array
     */
    public function resolve_variation_payload( array $payload ): array {
        $subscription_keys = $this->get_subscription_meta_keys();
        $meta_data         = $payload['meta_data'] ?? [];

        foreach ( $subscription_keys as $key ) {
            if ( ! array_key_exists( $key, $payload ) ) {
                continue;
            }

            $meta_data[] = [
                'key'   => $key,
                'value' => $payload[ $key ],
            ];
            unset( $payload[ $key ] );
        }

        if ( ! empty( $meta_data ) ) {
            $payload['meta_data'] = $meta_data;
        }

        return $payload;
    }

    /**
     * Save subscription metadata after a variation is created or updated via REST API.
     *
     * Mirrors the logic in Dokan_VSP_Product::save_variation_metadata() but
     * reads from the REST request instead of $_POST.
     *
     * @since 5.0.0
     *
     * @param \WC_Product_Variation $variation The saved variation object.
     * @param \WP_REST_Request      $request   The REST request.
     *
     * @return void
     */
    public function save_variation_subscription_metadata( $variation, $request ): void {
        $params       = $request->get_params();
        $variation_id = $variation->get_id();
        $parent       = wc_get_product( $variation->get_parent_id() );

        if ( ! $parent || ! in_array( $parent->get_type(), [ self::PRODUCT_TYPE_VARIABLE_SUBSCRIPTION ], true ) ) {
            return;
        }

        // Sync subscription price with regular price.
        if ( isset( $params['regular_price'] ) ) {
            update_post_meta( $variation_id, '_subscription_price', wc_format_decimal( $params['regular_price'] ) );
        }

        // Clamp trial length to the maximum allowed by WooCommerce Subscriptions.
        if ( isset( $params[ self::SUBSCRIPTION_TRIAL_LENGTH ] ) && isset( $params[ self::SUBSCRIPTION_TRIAL_PERIOD ] ) ) {
            $subscription_ranges = wcs_get_subscription_ranges();
            $trial_period        = $params[ self::SUBSCRIPTION_TRIAL_PERIOD ];
            $trial_length        = absint( $params[ self::SUBSCRIPTION_TRIAL_LENGTH ] );

            if ( isset( $subscription_ranges[ $trial_period ] ) ) {
                $max_trial_length = count( $subscription_ranges[ $trial_period ] ) - 1;
                $trial_length     = min( $trial_length, $max_trial_length );
            }

            $trial_length = max( 0, $trial_length );
            update_post_meta( $variation_id, self::SUBSCRIPTION_TRIAL_LENGTH, $trial_length );
        }
    }

    /**
     * Get the list of subscription meta keys used for variations.
     *
     * @since 5.0.0
     *
     * @return string[]
     */
    private function get_subscription_meta_keys(): array {
        return [
            self::SUBSCRIPTION_PERIOD_INTERVAL,
            self::SUBSCRIPTION_PERIOD,
            self::SUBSCRIPTION_LENGTH,
            self::SUBSCRIPTION_SIGN_UP_FEE,
            self::SUBSCRIPTION_TRIAL_LENGTH,
            self::SUBSCRIPTION_TRIAL_PERIOD,
            '_subscription_price',
            '_subscription_sale_price',
            '_subscription_sale_price_dates_from',
            '_subscription_sale_price_dates_to',
        ];
    }

    /**
     * Get subscription period interval options.
     *
     * @since 5.0.0
     *
     * @return array
     */
    private function get_period_interval_options(): array {
        $options = [];

        foreach ( wcs_get_subscription_period_interval_strings() as $value => $label ) {
            $options[] = [
                'label' => $label,
                'value' => (string) $value,
            ];
        }

        return $options;
    }

    /**
     * Get subscription period options.
     *
     * @since 5.0.0
     *
     * @return array
     */
    private function get_period_options(): array {
        $options = [];

        foreach ( wcs_get_subscription_period_strings() as $value => $label ) {
            $options[] = [
                'label' => $label,
                'value' => $value,
            ];
        }

        return $options;
    }

    /**
     * Get subscription length options.
     *
     * @since 5.0.0
     *
     * @return array
     */
    private function get_subscription_length_options(): array {
        $options = [];

        foreach ( wcs_get_subscription_ranges() as $period => $ranges ) {
            $period_options = [];

            foreach ( $ranges as $value => $label ) {
                $period_options[] = [
                    'label' => $label,
                    'value' => (string) $value,
                ];
            }

            $options[ $period ] = $period_options;
        }

        return $options;
    }

    /**
     * Get trial period options.
     *
     * @since 5.0.0
     *
     * @return array
     */
    private function get_trial_period_options(): array {
        $options = [];

        foreach ( wcs_get_available_time_periods() as $value => $label ) {
            $options[] = [
                'label' => $label,
                'value' => $value,
            ];
        }

        return $options;
    }
}
