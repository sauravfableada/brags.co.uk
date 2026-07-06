<?php

namespace WeDevs\DokanPro\Modules\OrderMinMax;

defined( 'ABSPATH' ) || exit;

/**
 * Loads assets and templates
 *
 * @since 3.12.0
 */
class Assets {

	const VENDOR_SETTINGS_FORM_VALIDATION = 'order-min-max-vendor-settings-form-validation';

	/**
	 * Initializing hooks and template files
	 *
	 * @since 3.12.0
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'dokan_register_scripts', array( $this, 'register_script' ) );
		add_action( 'dokan_register_scripts', array( $this, 'register_style' ) );

		add_action( 'dokan_enqueue_admin_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'dokan_enqueue_scripts', array( $this, 'enqueue_vendor_assets' ) );

		add_filter( 'dokan_set_template_path', array( $this, 'load_templates' ), 20, 3 );

		add_filter( 'dokan_localized_args', array( $this, 'localized_args' ) );
	}

	/**
	 * Registering necessary styles
	 *
	 * @since 3.12.0
	 *
	 * @return void
	 */
	public function register_style(): void {
		[ $suffix, $version ] = dokan_get_script_suffix_and_version();

		wp_register_style( Constants::ORDER_MIN_MAX_ADMIN_STYLE, DOKAN_ORDER_MIN_MAX_ASSETS . '/css/admin.css', array(), $version );
		wp_register_style( Constants::ORDER_MIN_MAX_VENDOR_STYLE, DOKAN_ORDER_MIN_MAX_ASSETS . '/css/vendor.css', array(), $version );
	}

	/**
	 * Registering necessary assets
	 *
	 * @since 3.12.0
	 *
	 * @return void
	 */
	public function register_script(): void {
		[ $suffix, $version ] = dokan_get_script_suffix_and_version();

		wp_register_script( self::VENDOR_SETTINGS_FORM_VALIDATION, DOKAN_ORDER_MIN_MAX_ASSETS . '/js/vendor-settings-form.js', array( 'jquery' ), $version, true );
		// @TODO Asses the necessity of js/order-min-max.js and refactor at a later time

		/**
		 * Order min max admin area script registration
		 */
		wp_register_script( Constants::ORDER_MIN_MAX_ADMIN_SCRIPT, DOKAN_ORDER_MIN_MAX_ASSETS . '/js/admin.js', array( 'jquery' ), $version, true );
		wp_localize_script( Constants::ORDER_MIN_MAX_ADMIN_SCRIPT, Constants::ORDER_MIN_MAX_JS_CONSTANT_OBJECT, array( 'constants' => Constants::get_all_static_constants() ) );
		/**
		 * Order min max vendor area script registration
		 */
		wp_register_script( Constants::ORDER_MIN_MAX_VENDOR_SCRIPT, DOKAN_ORDER_MIN_MAX_ASSETS . '/js/vendor.js', array( 'jquery' ), $version, true );
		wp_localize_script( Constants::ORDER_MIN_MAX_VENDOR_SCRIPT, Constants::ORDER_MIN_MAX_JS_CONSTANT_OBJECT, array( 'constants' => Constants::get_all_static_constants() ) );
	}

	/**
	 * Enqueueing script on product page for WordPress admin
	 *
	 * @since 3.12.0
	 *
	 * @return void
	 */
	public function enqueue_admin_assets(): void {
		global $wp, $typenow;

		$is_product_page = isset( $wp->query_vars['products'] )
            && isset( $_GET['product_id'], $_GET['action'] ) // phpcs:ignore
            && 'edit' === sanitize_text_field( wp_unslash( $_GET['action'] ) ); // phpcs:ignore

		$is_store_settings = isset( $wp->query_vars['settings'] ) && 'store' === $wp->query_vars['settings'];

        $is_new_product_page = isset( $_GET['post_type'] ) // phpcs:ignore
            && 'product' === sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) // phpcs:ignore
            && ! isset( $_GET['taxonomy'] ); // phpcs:ignore

		if (
			( dokan_is_seller_dashboard() && ( $is_product_page || $is_store_settings ) )
			|| ( is_admin() && ( 'product' === $typenow || $is_new_product_page ) )
		) {
			wp_enqueue_script( Constants::ORDER_MIN_MAX_ADMIN_SCRIPT );
			wp_enqueue_style( Constants::ORDER_MIN_MAX_ADMIN_STYLE );
		}
	}

	/**
	 * Adds vendor style and script to vendor dashboard
	 *
	 * @since 3.12.0
	 *
	 * @return void
	 */
	public function enqueue_vendor_assets(): void {
		if ( $this->is_vendor_dashboard_product_page() ) {
			wp_enqueue_style( Constants::ORDER_MIN_MAX_VENDOR_STYLE );
			wp_enqueue_script( Constants::ORDER_MIN_MAX_VENDOR_SCRIPT );
		}

		if ( $this->is_vendor_dashboard_store_page() ) {
			wp_enqueue_script( self::VENDOR_SETTINGS_FORM_VALIDATION );
		}
	}

	/**
	 * Checks if it is from vendor dashboard product page
	 *
	 * @since 3.12.0
	 *
	 * @return bool
	 */
	protected function is_vendor_dashboard_product_page(): bool {
		global $wp;
		return dokan_is_seller_dashboard() && isset( $wp->query_vars['products'] );
	}

	/**
	 * Checks if it is form vendor dashboard settings/store page
	 *
	 * @since 3.12.0
	 *
	 * @return bool
	 */
	protected function is_vendor_dashboard_store_page(): bool {
		global $wp;
		return dokan_is_seller_dashboard()
			&& isset( $wp->query_vars['settings'] )
			&& 'store' === sanitize_text_field( wp_unslash( $wp->query_vars['settings'] ) );
	}

	/**
	 * Set template path
	 *
	 * @since 3.12.0
	 *
	 * @param string $template_path Template path to be set for the template
	 * @param string $template Template name to be set for the template
	 * @param array $args Arguments to be passed to the template
	 *
	 * @return string
	 */
	public function load_templates( string $template_path, string $template, array $args ): string {
		if ( ( isset( $args['order_min_max_template'] ) && $args['order_min_max_template'] ) ) {
			return DOKAN_ORDER_MIN_MAX_TEMPLATE_PATH;
		}
		return $template_path;
	}

	/**
	 * Necessary localized arguments for this module
	 *
	 * @param array $default_args Default arguments for localization
	 *
	 * @return array
	 *
	 * @since 3.12.0
	 */
	public function localized_args( array $default_args ): array {
		$custom_args = array(
			'dokan_i18n_negative_value_not_approved' => esc_html__( 'Value can not be null or negative', 'dokan' ),
			'dokan_i18n_value_set_successfully'      => esc_html__( 'Value successfully set', 'dokan' ),
			'dokan_i18n_deactivated_successfully'    => esc_html__( 'Deactivated successfully.', 'dokan' ),
		);

		return array_merge( $default_args, $custom_args );
	}
}
