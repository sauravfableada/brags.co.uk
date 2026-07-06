<?php
namespace WeDevs\DokanPro\Modules\OrderMinMax\Frontend;

use WeDevs\DokanPro\Modules\OrderMinMax\DataSource\ProductMinMaxSettings;

defined( 'ABSPATH' ) || exit;

/**
 * Manages cart notice for min max restriction
 */
class Notice {

	/**
	 * List of notices
	 *
	 * @since 3.12.0
	 *
	 * @var array
	 */
	protected $notices = array();

	/**
	 * Returns all the notices
	 *
	 * @since 3.12.0
	 *
	 * @return array
	 */
	public function get_notices(): array {
		/**
		 * Filters the notices
		 *
		 * @since 3.12.0
		 *
		 * @param array $notices
		 */
		return apply_filters( 'dokan_order_min_max_validation_notices', $this->notices );
	}

	/**
	 * Add notices to notice class variable
	 *
	 * @since 3.12.0
	 *
	 * @param string $message
	 * @param string $type
	 *
	 * @return bool
	 */
	public function add_notice( string $message, string $type ): bool {
		if ( ! $this->is_notice_available( $message, $type ) ) {
			$this->notices[] = array(
				'message' => $message,
				'type'    => $type,
			);
			return true;
		}
		return false;
	}

	/**
	 * Checks if same notice is already added
	 *
	 * @since 3.12.0
	 *
	 * @param string $notice
	 * @param string $type
	 *
	 * @return bool
	 */
	protected function is_notice_available( $notice, $type ): bool {
		$notices      = $this->get_notices();
		$is_available = false;
		foreach ( $notices as $single_notice ) {
			if ( $single_notice['message'] === $notice && $single_notice['type'] === $type ) {
				$is_available = true;
				break;
			}
		}
		return $is_available;
	}

	/**
	 * Returns product with link
	 *
	 * @since 3.12.0
	 *
	 * @param $product_id
	 *
	 * @return string
	 */
	protected function get_product_with_link( $product_id ): string {
		$product           = wc_get_product( $product_id );
		$product_with_link = wp_kses(
			"<a href='{$product->get_permalink()}'>{$product->get_title()}</a>",
			$this->allowed_html()
		);
		return $product_with_link;
	}

	/**
	 * Minimum quantity violation notice
	 *
	 * @since 3.12.0
	 *
	 * @param $product_id
	 * @param $type
	 *
	 * @return void
	 */
	public function add_minimum_quantity_violation_notice( $product_id, $type = 'error' ) {
		$notice = sprintf(
			// Translators: 1. Product link 2. Minimum required quantity
			esc_html__( 'Minimum required quantity for %1$s is %2$s.', 'dokan' ),
			$this->get_product_with_link( $product_id ),
			( new ProductMinMaxSettings( $product_id ) )->min_quantity()
		);
		$this->add_notice( $notice, $type );
	}

	/**
	 * Maximum quantity violation notice
	 *
	 * @since 3.12.0
	 *
	 * @param $product_id
	 * @param $type
	 *
	 * @return void
	 */
	public function add_maximum_quantity_violation_notice( $product_id, $type = 'error' ) {
		$notice = sprintf(
			// Translators: 1. Product link 2. Maximum allowed quantity
			esc_html__( 'Maximum allowed quantity for %1$s is %2$s.', 'dokan' ),
			$this->get_product_with_link( $product_id ),
			( new ProductMinMaxSettings( $product_id ) )->max_quantity()
		);
		$this->add_notice( $notice, $type );
	}

	/**
	 * Returns shop with link
	 *
	 * @since 3.12.0
	 * @param $vendor_id
	 *
	 * @return string
	 */
	protected function get_shop_with_link( $vendor_id ): string {
		$store_name = dokan_get_vendor( $vendor_id )->get_shop_info()['store_name'];
		$store_url  = dokan_get_store_url( $vendor_id );
		return wp_kses(
			"<a href='$store_url'>$store_name</a>",
			$this->allowed_html()
		);
	}

	/**
	 * Minimum amount violation notice
	 *
	 * @since 3.12.0
	 *
	 * @param $vendor_id
	 * @param $type
	 *
	 * @return void
	 */
	public function add_minimum_amount_violation_notice( $vendor_id, $type = 'error' ) {
		$notice = sprintf(
			// Translators: 1. Shop link 2. Minimum required amount 3. Amount in cart
			esc_html__( 'Minimum required cart amount for %1$s is %2$s. You currently have %3$s in cart.', 'dokan' ),
			$this->get_shop_with_link( $vendor_id ),
			wc_price( dokan_pro()->module->order_min_max->store_min_max_settings->get_min_amount_for_order( $vendor_id ) ),
			wc_price( dokan_pro()->module->order_min_max->vendor_cart->get_cart_total_by_vendor( $vendor_id ) )
		);
		$this->add_notice( $notice, $type );
	}

	/**
	 * Maximum amount violation notice
	 *
	 * @since 3.12.0
	 *
	 * @param $vendor_id
	 * @param $type
	 *
	 * @return void
	 */
	public function add_maximum_amount_violation_notice( $vendor_id, $type = 'error' ) {
		$notice = sprintf(
			// Translators: 1. Shop link 2. Maximum allowed amount 3. Amount in cart
			esc_html__( 'Maximum allowed cart amount for %1$s is %2$s. You currently have %3$s in cart.', 'dokan' ),
			$this->get_shop_with_link( $vendor_id ),
			wc_price( dokan_pro()->module->order_min_max->store_min_max_settings->get_max_amount_for_order( $vendor_id ) ),
			wc_price( dokan_pro()->module->order_min_max->vendor_cart->get_cart_total_by_vendor( $vendor_id ) )
		);
		$this->add_notice( $notice, $type );
	}

	/**
	 * Empty all notices
	 *
	 * @since 3.12.0
	 *
	 * @return void
	 */
	public function flush() {
		$this->notices = array();
	}

	/**
	 * Allowed html for a link
	 *
	 * @since 3.12.0
	 *
	 * @return array
	 */
	protected function allowed_html(): array {
		return array(
			'a' => array(
				'href'  => array(),
				'title' => array(),
			),
		);
	}
}
