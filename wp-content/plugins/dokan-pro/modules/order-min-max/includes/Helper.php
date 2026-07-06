<?php

namespace WeDevs\DokanPro\Modules\OrderMinMax;

defined( 'ABSPATH' ) || exit;

/**
 * OrderMinMax Module Helper.
 *
 * @since 3.7.13
 */
class Helper {

	/**
	 * Get the minimum amount notice
	 *
	 * @since 3.7.13
	 *
	 * @return string
	 */
	public static function get_amount_min_max_notice(): string {
		return esc_html__( 'Please leave both fields empty or set to 0 to disable the minimum and maximum cart amount. Ensure the minimum amount is not greater than the maximum amount.', 'dokan' );
	}

	/**
	 * Get the minimum quantity notice
	 *
	 * @since 3.7.13
	 *
	 * @return string
	 */
	public static function get_quantity_min_max_notice(): string {
		return esc_html__( 'Please leave both fields empty or set to 0 to disable the minimum and maximum product quantity. Ensure the minimum quantity is not greater than the maximum quantity.', 'dokan' );
	}
}
