<?php

namespace WeDevs\DokanPro\Modules\OrderMinMax\Vendor;

defined( 'ABSPATH' ) || exit;

use WeDevs\DokanPro\Modules\OrderMinMax\Constants;
use WeDevs\DokanPro\Modules\OrderMinMax\DataSource\ProductMinMaxSettings;

/**
 * Quick and bulk edit for dokan order min max
 *
 * @since 3.12.0
 */
class QuickAndBulkEdit {

	/**
	 * Initializing the hooks
	 *
	 * @since 3.12.0
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'dokan_product_posts_column', array( $this, 'inject_meta_data' ), 10, 2 );
		add_action( 'dokan_quick_edit_before_column_1_ends', array( $this, 'render_quick_edit_fields' ) );
		add_action( 'dokan_bulk_edit_before_column_1_ends', array( $this, 'render_bulk_edit_fields' ) );
		add_action( 'dokan_product_quick_edit_updated', array( $this, 'save_quick_edit' ) );
		add_action( 'dokan_before_bulk_edit_save_single_item', array( $this, 'save_bulk_edit' ) );
	}

	/**
	 * Injecting quick edit data
	 *
	 * @since 3.12.0
	 *
	 * @param string $column Column name of the product
	 * @param int $product_id Product ID of the product
	 *
	 * @return void
	 */
	public function inject_meta_data( string $column, int $product_id ): void {
		if ( 'name' === $column ) {
			$product_min_max_settings = new ProductMinMaxSettings( $product_id );
			$data                     = wp_json_encode(
				array(
					'min_quantity' => $product_min_max_settings->min_quantity(),
					'max_quantity' => $product_min_max_settings->max_quantity(),
				)
			);
			$css_class_name           = Constants::QUICK_EDIT_META_DATA;
			echo "<div class='{$css_class_name}' data-product-id='{$product_id}' data-min-max-data='{$data}'></div>"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * Render quick edit form fragment
	 *
	 * @since 3.12.0
	 *
	 * @param int $product_id Product ID of the product
	 *
	 * @return void
	 */
	public function render_quick_edit_fields( int $product_id ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		dokan_get_template_part(
			'vendor-dashboard/quick-edit',
			'',
			array(
				'order_min_max_template' => true,
			)
		);
	}

	/**
	 * Render bulk edit form fragment
	 *
	 * @since 3.12.0
	 *
	 * @return void
	 */
	public function render_bulk_edit_fields(): void {
		dokan_get_template_part(
			'vendor-dashboard/bulk-edit',
			'',
			array(
				'order_min_max_template' => true,
			)
		);
	}

	/**
	 * Saves quick edit data for product
	 *
	 * @since 3.12.0
	 *
	 * @param int $product_id Product ID of the product
	 *
	 * @return void
	 */
	public function save_quick_edit( int $product_id ): void {
		$parsed_data = $this->parse_quick_edit_data();
		// @TODO Check if empty data is updating as default values
		if ( ! empty( $parsed_data ) ) {
			$min_max_data = new ProductMinMaxSettings( $product_id );
			$min_max_data->set_data( $parsed_data );
			$min_max_data->save();
		}
	}

	/**
	 * Save bulk edit data
	 *
	 * @param \WC_Product $product Product object
	 *
	 * @return void
	 */
	public function save_bulk_edit( \WC_Product $product ): void {
        $data = $_REQUEST; // phpcs:ignore
        if ( ! isset( $data['dokan_vendor_override_bulk_product_min_max'] ) || intval( sanitize_text_field( $data['dokan_vendor_override_bulk_product_min_max'] ) ) !== 1 ) {
            return;
        }
		$parsed_data = $this->parse_bulk_edit_data();
		if ( ! empty( $parsed_data ) ) {
			$min_max_data = new ProductMinMaxSettings( $product );
            if ( $parsed_data[ ProductMinMaxSettings::MIN_QUANTITY ] === 0 ) {
                $parsed_data[ ProductMinMaxSettings::MIN_QUANTITY ] = $min_max_data->min_quantity();
            }
            if ( $parsed_data[ ProductMinMaxSettings::MAX_QUANTITY ] === 0 ) {
                $parsed_data[ ProductMinMaxSettings::MAX_QUANTITY ] = $min_max_data->max_quantity();
            }
			$min_max_data->set_data( $parsed_data );
			$min_max_data->save();
		}
	}

	/**
	 * Parses quick and bulk edit data from global request object
	 *
	 * @since 3.12.0
	 *
	 * @return array
	 */
	protected function parse_quick_edit_data(): array {
		$data        = array(); // Parsed data
        $data_source = $_POST['data']; // phpcs:ignore

		if ( ! empty( $data_source ) ) {
			$min_max_minimum_quantity = isset( $data_source[ Constants::QUICK_EDIT_MINIMUM_QUANTITY ] ) ? sanitize_text_field( $data_source[ Constants::QUICK_EDIT_MINIMUM_QUANTITY ] ) : 0;
			$min_max_minimum_quantity = (int) $min_max_minimum_quantity;
			$min_max_maximum_quantity = isset( $data_source[ Constants::QUICK_EDIT_MAXIMUM_QUANTITY ] ) ? sanitize_text_field( $data_source[ Constants::QUICK_EDIT_MAXIMUM_QUANTITY ] ) : 0;
			$min_max_maximum_quantity = (int) $min_max_maximum_quantity;

			$data = array(
				ProductMinMaxSettings::MIN_QUANTITY => $min_max_minimum_quantity,
				ProductMinMaxSettings::MAX_QUANTITY => $min_max_maximum_quantity,
			);
		}

		return $data;
	}

	/**
	 * Parses bulk edit data
	 *
	 * @since 3.12.0
	 *
	 * @return array
	 */
	protected function parse_bulk_edit_data(): array {
		$data        = array();
        $data_source = $_POST; // phpcs:ignore

		if ( ! empty( $data_source ) ) {
            $min_max_minimum_quantity = isset( $_POST[ Constants::BULK_EDIT_VENDOR_MINIMUM_QUANTITY ] ) // phpcs:ignore
                ? sanitize_text_field( $_POST[ Constants::BULK_EDIT_VENDOR_MINIMUM_QUANTITY ] ) // phpcs:ignore
				: 0;
			$min_max_minimum_quantity = (int) $min_max_minimum_quantity;
            $min_max_maximum_quantity = isset( $_POST[ Constants::BULK_EDIT_VENDOR_MAXIMUM_QUANTITY ] ) // phpcs:ignore
                ? sanitize_text_field( $_POST[ Constants::BULK_EDIT_VENDOR_MAXIMUM_QUANTITY ] ) // phpcs:ignore
				: 0;
			$min_max_maximum_quantity = (int) $min_max_maximum_quantity;
			$data                     = array(
				ProductMinMaxSettings::MIN_QUANTITY => $min_max_minimum_quantity,
				ProductMinMaxSettings::MAX_QUANTITY => $min_max_maximum_quantity,
			);
		}

		return $data;
	}
}
