<?php

namespace WeDevs\DokanPro\Modules\OrderMinMax\DataSource;

defined( 'ABSPATH' ) || exit;

/**
 * Handles vendor cart amount settings
 *
 * @since 3.12.0
 */
class StoreMinMaxSettings {

	const SETTINGS_BASE_KEY = 'order_min_max';
	const MIN_AMOUNT_KEY    = 'min_amount_to_order';
	const MAX_AMOUNT_KEY    = 'max_amount_to_order';

	protected $cache = array();

	/**
	 * Initializes the object
	 *
	 * @since 3.12.0
	 *
	 * @return void
	 */
	public function __construct() {
		add_filter( 'dokan_store_profile_settings_args', array( $this, 'add_vendor_cart_amount_settings' ), 21 );
	}

	/**
	 * Saves vendors cart min max settings
	 *
	 * @since 3.12.0
	 *
	 * @param array $store_settings An array of store settings for dokan vendor dashboard
	 *
	 * @return array
	 */
	public function add_vendor_cart_amount_settings( array $store_settings ): array {
		[ $min_amount, $max_amount ] = $this->get_valid_min_max_amount_from_submission();

		if ( ! empty( $min_amount ) && ! empty( $max_amount ) && ( $min_amount > $max_amount ) ) {
			wp_send_json_error( esc_html__( 'Minimum cart amount can\'t be greater then maximum cart amount.', 'dokan' ) );
		} else {
			$store_settings[ self::SETTINGS_BASE_KEY ][ self::MIN_AMOUNT_KEY ] = $min_amount;
			$store_settings[ self::SETTINGS_BASE_KEY ][ self::MAX_AMOUNT_KEY ] = $max_amount;
		}

		return $store_settings;
	}

	/**
	 * Validates min max amount from store page submission and returns the data
	 *
	 * @since 3.12.0
	 *
	 * @return array
	 */
	protected function get_valid_min_max_amount_from_submission(): array {
		$min_amount = 0.00;
		$max_amount = 0.00;

		if ( isset( $_POST[ self::MIN_AMOUNT_KEY ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$min_amount = wc_format_decimal( sanitize_text_field( wp_unslash( $_POST[ self::MIN_AMOUNT_KEY ] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		}

		if ( isset( $_POST[ self::MAX_AMOUNT_KEY ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$max_amount = wc_format_decimal( sanitize_text_field( wp_unslash( $_POST[ self::MAX_AMOUNT_KEY ] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		}

		return array( $min_amount, $max_amount );
	}

	/**
	 * Checks if specified vendor data is already fetched
	 *
	 * @since 3.12.0
	 *
	 * @param int $vendor_id Vendor ID
	 *
	 * @return bool
	 */
	protected function is_fetched_vendor_data( int $vendor_id = 0 ): bool {
		$vendor_id = $vendor_id ? $vendor_id : dokan_get_current_user_id();

		return isset( $this->cache[ $vendor_id ] );
	}

	/**
	 * Returns vendor data
	 *
	 * @since 3.12.0
	 *
	 * @param int $vendor_id Vendor ID
	 *
	 * @return array
	 */
	protected function get_vendor_data( int $vendor_id = 0 ) {
		$vendor_id = $vendor_id ? $vendor_id : dokan_get_current_user_id();

		return $this->cache[ $vendor_id ];
	}

	/**
	 * Returns a valid vendor ID
	 *
	 * @since 3.12.0
	 *
	 * @param int $vendor_id
	 *
	 * @return int
	 */
	protected function get_valid_vendor_id( int $vendor_id = 0 ): int {
		return 0 !== $vendor_id ? $vendor_id : dokan_get_current_user_id();
	}

	/**
	 * Fetches min max amount settings data
	 *
	 * @param int $vendor_id Vendor ID
	 *
	 * @since 3.12.0
	 *
	 * @return void
	 */
	protected function fetch_min_max_amount_settings( int $vendor_id = 0 ) {
		$vendor_id = $this->get_valid_vendor_id( $vendor_id );

		if ( $this->is_fetched_vendor_data( $vendor_id ) ) {
			return;
		}

		$vendor_profile_settings = dokan_get_store_info( $vendor_id );

		$this->cache[ $vendor_id ][ self::MIN_AMOUNT_KEY ] = $vendor_profile_settings[ self::SETTINGS_BASE_KEY ][ self::MIN_AMOUNT_KEY ] ?? '';
		$this->cache[ $vendor_id ][ self::MAX_AMOUNT_KEY ] = $vendor_profile_settings[ self::SETTINGS_BASE_KEY ][ self::MAX_AMOUNT_KEY ] ?? '';
	}

	/**
	 * Returns minimum amount to order
	 *
	 * @since 3.12.0
	 *
	 * @param int $vendor_id Vendor ID
	 * @param string $context Context for which the value is being fetched, default is 'view', possible values are 'view', 'edit'.
	 *
	 * @return float
	 */
	public function get_min_amount_for_order( int $vendor_id = 0, string $context = 'view' ) {
		$vendor_id = $this->get_valid_vendor_id( $vendor_id );

		if ( ! $this->is_fetched_vendor_data( $vendor_id ) ) {
			$this->fetch_min_max_amount_settings( $vendor_id );
		}

		$cache_amount = $this->get_vendor_data( $vendor_id );
		$min_amount   = ! empty( $cache_amount[ self::MIN_AMOUNT_KEY ] ) ? (float) $cache_amount[ self::MIN_AMOUNT_KEY ] : 0.00;

		if ( 'view' === $context ) {
			/**
			 * Filters the minimum amount for the order
			 *
			 * @since 3.12.0
			 *
			 * @param float $min_amount Minimum amount for the order
			 * @param int $vendor_id Vendor ID
			 */
			$min_amount = apply_filters( 'dokan_order_min_max_store_min_amount', $min_amount, $vendor_id );
		}

		return $min_amount;
	}

	/**
	 * Returns maximum amount to order
	 *
	 * @since 3.12.0
	 *
	 * @param int $vendor_id Vendor ID
	 * @param string $context Context for which the value is being fetched, default is 'view', possible values are 'view', 'edit'.
	 *
	 * @return float
	 */
	public function get_max_amount_for_order( int $vendor_id = 0, string $context = 'view' ) {
		$vendor_id = $this->get_valid_vendor_id( $vendor_id );

		if ( ! isset( $this->cache[ $vendor_id ] ) ) {
			$this->fetch_min_max_amount_settings( $vendor_id );
		}

		$cache_amount = $this->get_vendor_data( $vendor_id );
		$max_amount   = ! empty( $cache_amount[ self::MAX_AMOUNT_KEY ] ) ? (float) $cache_amount[ self::MAX_AMOUNT_KEY ] : 0.00;

		if ( 'view' === $context ) {
			/**
			 * Filters the maximum amount for the order
			 *
			 * @since 3.12.0
			 *
			 * @param float $max_amount Miximum amount for the order
			 * @param int $vendor_id Vendor ID
			 */
			$max_amount = apply_filters( 'dokan_order_min_max_store_max_amount', $max_amount, $vendor_id );
		}

		return $max_amount;
	}
}
