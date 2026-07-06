<?php

namespace WeDevs\DokanPro\Dashboard\ManualOrders;

use WeDevs\Dokan\Vendor\Vendor;

/**
 * Class Settings
 *
 * Handles settings related to manual orders functionality
 *
 * @since 4.0.0
 */
class Settings {
    /**
     * Constructor.
     *
     * Sets up hooks for settings.
     */
    public function __construct() {
        // Register settings field.
        add_filter( 'dokan_settings_selling_option_vendor_capability', [ $this, 'add_manual_order_settings_field' ] );
        add_filter( 'dokan_rest_store_restricted_fields_for_update', [ $this, 'add_pro_restricted_fields' ] );
        add_action( 'dps_subscription_product_fields_after_pack_validity', [ $this, 'add_manual_order_field_for_subscription' ] );

        // Save settings field data.
        add_action( 'dokan_before_create_vendor', [ $this, 'save_enable_manual_order_for_vendor' ], 10, 2 );
        add_action( 'dokan_before_update_vendor', [ $this, 'save_enable_manual_order_for_vendor' ], 10, 2 );
        add_action( 'dps_process_subcription_product_meta', [ $this, 'save_manual_order_subscription_field' ] );

        // Add capability to vendor shop data.
        add_filter( 'dokan_vendor_to_array', [ $this, 'add_manual_order_capability_to_vendor_shop_data' ], 10, 2 );
        add_filter( 'dokan_vendor_shop_data', [ $this, 'add_manual_order_capability_to_vendor_shop_data' ], 10, 2 );

        // update rest api schema
        add_filter( 'dokan_rest_api_store_update_params', [ $this, 'update_rest_api_schema' ] );

        add_filter( 'dokan_admin_dashboard_vendors_settings', [ $this, 'add_manual_order_setting_to_dashboard_data' ] );
    }

	/**
	 * Get the meta key for manual order capability.
	 *
	 * @since 4.0.0
	 *
	 * @return string Meta key.
	 */
	public function get_meta_key(): string {
		return '_dokan_enable_manual_order';
	}

    /**
     * Add pro restricted fields for store update
     *
     * @since 4.2.4
     *
     * @param array $restricted_fields
     *
     * @return array
     */
    public function add_pro_restricted_fields( $restricted_fields ) {
        $is_admin = current_user_can( 'manage_options' );

        if ( ! $is_admin ) {
            $restricted_fields[] = 'enable_manual_order';
        }
        return $restricted_fields;
    }

    /**
     * Add manual order field to vendor capability section
     *
     * @since 4.0.0
     *
     * @param array $settings_fields Settings fields.
     *
     * @return array
     */
	public function add_manual_order_settings_field( array $settings_fields ): array {
		$manual_order_field = [
			'allow_vendor_create_manual_order' => [
				'name'    => 'allow_vendor_create_manual_order',
				'label'   => esc_html__( 'Allow Vendors to Create Orders', 'dokan' ),
				'desc'    => esc_html__( 'Enable vendors to create orders manually from their dashboard.', 'dokan' ),
				'type'    => 'switcher',
				'default' => 'off',
				'tooltip' => esc_html__( 'When enabled, vendors can create manual orders directly from their dashboard for direct sales.', 'dokan' ),
			],
		];

		// Add our field after new_seller_enable_selling
		return dokan_array_after( $settings_fields, 'new_seller_enable_selling', $manual_order_field );
	}

	/**
	 * Add checkbox field after pack validity in subscription product
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function add_manual_order_field_for_subscription(): void {
		woocommerce_wp_checkbox(
			[
				'id'          => $this->get_meta_key(),
				'label'       => esc_html__( 'Enable Order Creation', 'dokan' ),
				'description' => esc_html__( 'Enable vendors to create orders manually from their dashboard.', 'dokan' ),
			]
		);
	}

    /**
     * Save the manual order capability for vendor
     *
     * @since 4.0.0
     *
     * @param int   $user_id User ID.
     * @param array $data    Data from the form.
     *
     * @return void
     */
    public function save_enable_manual_order_for_vendor( int $user_id, array $data ): void {
        if ( ! isset( $data['enable_manual_order'] ) ) { // phpcs:ignore WordPress.Security
            return;
        }

        // Get the value from the form
        $enable_manual_order = wc_bool_to_string( $data['enable_manual_order'] ); // phpcs:ignore WordPress.Security

        // Save the value to the user meta
        update_user_meta( $user_id, $this->get_meta_key(), $enable_manual_order );
    }

	/**
	 * Save the manual order field value for subscription products
	 *
	 * @since 4.0.0
	 *
	 * @param int $post_id Product post ID.
	 *
	 * @return void
	 */
	public function save_manual_order_subscription_field( int $post_id ): void {
		$product = wc_get_product( $post_id );

		if ( ! $product || ! $product->is_type( 'product_pack' ) ) {
			return;
		}

		// Get the value from the form, defaulting to 'no' if not set
		$enable_manual_order = isset( $_POST[ $this->get_meta_key() ] ) ? 'yes' : 'no'; // phpcs:ignore WordPress.Security

		// Save the value to the product meta
		$product->update_meta_data( $this->get_meta_key(), $enable_manual_order );
		$product->save();
	}

    /**
     * Add manual order capability to vendor shop data
     *
     * @since 4.0.0
     *
     * @param array  $shop_data Vendor shop data.
     * @param Vendor $vendor    Vendor object.
     *
     * @return array
     */
    public function add_manual_order_capability_to_vendor_shop_data( array $shop_data, Vendor $vendor ): array {
	    $is_enable = dokan_pro()->manual_orders->is_enabled_globally();
        if ( metadata_exists( 'user', $vendor->get_id(), $this->get_meta_key() ) ) {
            $meta_value = get_user_meta( $vendor->get_id(), $this->get_meta_key(), true );
            $is_enable  = wc_string_to_bool( $meta_value );
        }

        /**
         * Filter to allow vendors to enable manual order
         *
         * @since 4.0.0
         *
         * @param bool   $is_enable  Is manual order enabled or not
         * @param Vendor $vendor     Vendor object
         * @param array  $shop_data  Vendor shop data
         */
        $is_enable = apply_filters( 'dokan_vendor_enable_manual_order', $is_enable, $vendor, $shop_data );

        // Add property to shop data
        $shop_data['enable_manual_order'] = $is_enable;

        return $shop_data;
    }

    /**
     * Update the REST API schema for manual order capability
     *
     * @since 4.0.0
     *
     * @param array  $schema Schema data.
     *
     * @return array
     */
    public function update_rest_api_schema( array $schema ): array {
        // Add the manual order capability to the schema
        $schema['properties']['enable_manual_order'] = [
            'type'    => 'boolean',
            'context' => [ 'view', 'edit' ],
            'readonly' => true,
            'default' => false,
        ];

        return $schema;
    }

    /**
     * Adds the manual order setting to the vendor dashboard data.
     *
     * @since 4.2.1
     *
     * @param array $settings Existing dashboard settings data.
     *
     * @return array
     */
    public function add_manual_order_setting_to_dashboard_data( $settings ) {
        $settings['allow_vendor_create_manual_order'] = dokan_pro()->manual_orders->is_enabled_globally();

        return $settings;
    }
}
