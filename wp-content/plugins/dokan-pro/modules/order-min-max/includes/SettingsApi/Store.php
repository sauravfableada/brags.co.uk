<?php

namespace WeDevs\DokanPro\Modules\OrderMinMax\SettingsApi;

defined( 'ABSPATH' ) || exit;

/**
 * Vendor settings api for OrderMinMax
 *
 * @since 3.7.13
 */
class Store {

	/**
	 * Constructor function
	 *
	 * @since 3.7.13
	 */
	public function __construct() {
		add_filter( 'dokan_vendor_settings_api_store_details_tab', array( $this, 'add_min_max_card_to_vendor_settings_api' ) );
	}

	/**
	 * Adds variation min max settings to the vendor dashboard.
	 *
	 * @since 3.7.13
	 *
	 * @param array $store_details_tab array of store details tab.
	 *
	 * @return array
	 */
	public function add_min_max_card_to_vendor_settings_api( array $store_details_tab ): array {
		$order_min_max_fields = array();
		$min_max_amounts      = array(
			array(
				'id'        => 'enable_vendor_min_max_amount',
				'title'     => esc_html__( 'Enable Min/Max Product Amount', 'dokan' ),
				'desc'      => esc_html__( 'Activating this will set min and max amount for selected products & category', 'dokan' ),
				'icon'      => '',
				'default'   => 'no',
				'options'   => array(
					'yes' => esc_html__( 'Yes', 'dokan' ),
					'no'  => esc_html__( 'No', 'dokan' ),
				),
				'type'      => 'checkbox',
				'parent_id' => 'order_min_max',
			),
			array(
				'id'        => 'min_amount_to_order',
				'title'     => esc_html__( 'Minimum Amount', 'dokan' ),
				'desc'      => esc_html__( 'Minimum Amount for Order', 'dokan' ),
				'icon'      => '',
				'type'      => 'number',
				'increment' => 0.01,
				'minimum'   => 0,
				'mode'      => 'currency',
				'parent_id' => 'order_min_max',
			),
			array(
				'id'        => 'max_amount_to_order',
				'title'     => esc_html__( 'Maximum Amount', 'dokan' ),
				'desc'      => esc_html__( 'Maximum Amount for Order', 'dokan' ),
				'icon'      => '',
				'type'      => 'number',
				'increment' => 0.01,
				'minimum'   => 0,
				'mode'      => 'currency',
				'parent_id' => 'order_min_max',
			),
		);
		array_push( $order_min_max_fields, ...$min_max_amounts );

		$minmax_card   = array();
		$minmax_card[] = array(
			'id'        => 'min_max_quantities_card',
			'title'     => esc_html__( 'Define Min/Max Quantities & Amount', 'dokan' ),
			'desc'      => esc_html__( 'Set minimum or maximum order limit on specific items.', 'dokan' ),
			'info'      => array(
				array(
					'text' => esc_html__( 'Docs', 'dokan' ),
					'url'  => 'https://dokan.co/docs/wordpress/modules/how-to-enable-minimum-maximum-order-amount-for-dokan/',
					'icon' => 'dokan-icon-doc',
				),
			),
			'icon'      => 'dokan-icon-products',
			'type'      => 'card',
			'parent_id' => 'store',
			'tab'       => 'store_details',
			'editable'  => true,
		);

		$minmax_card[] = array(
			'id'        => 'order_min_max',
			'title'     => '',
			'desc'      => '',
			'info'      => array(),
			'icon'      => '',
			'type'      => 'section',
			'parent_id' => 'store',
			'tab'       => 'store_details',
			'card'      => 'min_max_quantities_card',
			'editable'  => false,
			'fields'    => $order_min_max_fields,
		);

		/**
		 * Filter to add more fields in min max quantities card
		 *
		 * @since 3.7.13
		 *
		 * @param array $minmax_card
		 */
		$minmax_card = apply_filters( 'dokan_pro_vendor_settings_api_min_max_quantities_amount_card', $minmax_card );
		array_push( $store_details_tab, ...$minmax_card );

		return $store_details_tab;
	}
}
