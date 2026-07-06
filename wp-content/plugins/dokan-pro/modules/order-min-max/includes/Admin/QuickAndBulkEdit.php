<?php

namespace WeDevs\DokanPro\Modules\OrderMinMax\Admin;

defined( 'ABSPATH' ) || exit;

use WC_Product;
use WeDevs\DokanPro\Modules\OrderMinMax\Constants;
use WeDevs\DokanPro\Modules\OrderMinMax\DataSource\ProductMinMaxSettings;

/**
 * Quick and bulk edit manager for dokan min max
 *
 * @since 3.12.0
 */
class QuickAndBulkEdit {

	/**
	 * Initializing hooks
	 *
	 * @since 3.12.0
	 *
	 * @return void
	 */
	public function __construct() {
		// Injecting data for quick edit
		add_action( 'manage_product_posts_custom_column', array( $this, 'inject_meta_data' ), 10, 2 );

		// Rendering fields for quick edit and bulk edit
		add_action( 'woocommerce_product_bulk_edit_end', array( $this, 'render_fields' ) );
		add_action( 'woocommerce_product_quick_edit_end', array( $this, 'render_fields' ) );

		// Saving quick and bulk edit fields
		add_action( 'woocommerce_product_quick_edit_save', array( $this, 'save_quick_edit_and_bulk_edit' ) );
		add_action( 'woocommerce_product_bulk_edit_save', array( $this, 'save_quick_edit_and_bulk_edit' ) );
	}

	/**
	 * Injecting order min max metadata to product table row.
	 *
	 * @since 3.12.0
	 *
	 * @param string $column
	 * @param int $product_id
	 *
	 * @return void
	 */
	public function inject_meta_data( string $column, int $product_id ) {
		if ( 'name' === $column ) {
			$product_min_max_settings = new ProductMinMaxSettings( $product_id );
			$data                     = wp_json_encode(
				array(
					'min_quantity' => $product_min_max_settings->min_quantity( 'edit' ),
					'max_quantity' => $product_min_max_settings->max_quantity( 'edit' ),
				)
			);
			$css_class_name           = Constants::QUICK_EDIT_META_DATA;
			echo "<div class='{$css_class_name}' data-product-id='{$product_id}' data-min-max-data='{$data}'></div>"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * Renders quick and bulk edit fields
	 *
	 * @since 3.12.0
	 *
	 * @return void
	 */
	public function render_fields() {
		dokan_get_template_part(
			'admin/quick-and-bulk-edit',
			'',
			array(
				'order_min_max_template' => true,
			)
		);
	}

	/**
	 * Saving product quick edit data
	 *
	 * @since 3.12.0
	 *
	 * @param WC_Product $product
	 *
	 * @return void
	 */
	public function save_quick_edit_and_bulk_edit( WC_Product $product ) {
        $data = $_REQUEST; // phpcs:ignore
        if ( ! isset( $data['dokan_override_bulk_product_min_max'] ) || intval( sanitize_text_field( $data['dokan_override_bulk_product_min_max'] ) ) !== 1 ) {
            return;
        }
		$parsed_data = $this->parse_data();

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
	 * Parse data from global variables for quick edit and bulk edit submission
	 *
	 * @since 3.12.0
	 *
	 * @return array
	 */
	protected function parse_data(): array {
		$data = array(); // Parsed data

        $data_source = $_REQUEST; // phpcs:ignore

		if ( ! empty( $data_source ) ) {
			$min_max_minimum_quantity = isset( $data_source[ Constants::QUICK_EDIT_MINIMUM_QUANTITY ] )
				? sanitize_text_field( wp_unslash( $data_source[ Constants::QUICK_EDIT_MINIMUM_QUANTITY ] ) ) : 0;
			$min_max_minimum_quantity = (int) $min_max_minimum_quantity;
			$min_max_maximum_quantity = isset( $data_source[ Constants::QUICK_EDIT_MAXIMUM_QUANTITY ] )
				? sanitize_text_field( wp_unslash( $data_source[ Constants::QUICK_EDIT_MAXIMUM_QUANTITY ] ) ) : 0;
			$min_max_maximum_quantity = (int) $min_max_maximum_quantity;

			$data = array(
				ProductMinMaxSettings::MIN_QUANTITY => $min_max_minimum_quantity,
				ProductMinMaxSettings::MAX_QUANTITY => $min_max_maximum_quantity,
			);
		}

		return $data;
	}
}
