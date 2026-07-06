<?php

namespace WeDevs\DokanPro\Modules\TableRate\Models\DataStore;

use WeDevs\Dokan\Models\DataStore\BaseDataStore;

/**
 * Distance Rate Shipping Store Class
 *
 * @since 4.1.3
 */
class DistanceRateShippingStore extends BaseDataStore {

	/**
	 * Get the fields with format as an array where key is the db field name and value is the format.
	 *
	 * @since 4.1.3
	 *
	 * @return array
	 */
	protected function get_fields_with_format(): array {
		return array(
			'rate_id'        => '%d',
			'vendor_id'      => '%d',
			'zone_id'        => '%d',
			'instance_id'    => '%d',
			'rate_condition' => '%s',
			'rate_min'       => '%s',
			'rate_max'       => '%s',
			'rate_cost'      => '%s',
			'rate_cost_unit' => '%s',
			'rate_fee'       => '%s',
			'rate_break'     => '%d',
			'rate_abort'     => '%d',
			'rate_order'     => '%d',
		);
	}

	/**
	 * Get the table name.
	 *
	 * @since 4.1.3
	 *
	 * @return string
	 */
	public function get_table_name(): string {
		return 'dokan_distance_rate_shipping';
	}

	/**
	 * Get the ID field name.
	 *
	 * @since 4.1.3
	 *
	 * @return string
	 */
	protected function get_id_field_name(): string {
		return 'rate_id';
	}

	/**
	 * Save or update distance rate data.
	 *
	 * @since 4.1.3
	 *
	 * @param array $rate_data Rate data to save.
	 * @param int   $rate_id   Rate ID for update, 0 for insert.
	 *
	 * @return void.
	 */
	public function save_rates( array $rate_data, int $rate_id = 0 ) {
		global $wpdb;

		$formats        = array_values( $this->get_fields_with_format() );
		$rate_id_format = $formats[0] ?? '%d';

		// Remove the rate_id format from the array.
		unset( $formats[0] );

        // @codingStandardsIgnoreStart
		if ( $rate_id > 0 ) {
			$wpdb->update(
				$this->get_table_name_with_prefix(),
				$rate_data,
				array( 'rate_id' => $rate_id ),
				$formats,
				array( $rate_id_format )
			);

			/**
			 * Action after updating a distance rate shipping.
			 *
			 * @since 4.1.3
			 *
			 * @param array $rate_data The rate data that was updated
			 * @param int   $rate_id   The rate ID
			 */
			do_action( 'dokan_distance_rate_shipping_rate_updated', $rate_data, $rate_id );
		} else {
			$wpdb->insert(
				$this->get_table_name_with_prefix(),
				$rate_data,
				$formats
			);

            /**
             * Action after inserting a new distance rate shipping.
             *
             * @since 4.1.3
             *
             * @param array $rate_data The rate data that was inserted
             * @param int   $insert_id The inserted rate ID
             */
            do_action( 'dokan_distance_rate_shipping_rate_inserted', $rate_data, $wpdb->insert_id );
		}
        // @codingStandardsIgnoreEnd
	}

	/**
	 * Get shipping distance rates.
	 *
	 * @since 4.1.3
	 *
	 * @param string   $output      Output format.
	 * @param int|null $instance_id Instance ID.
	 *
	 * @return array|object Distance rate data.
	 */
	public function get_shipping_rates( $output, $instance_id ) {
		global $wpdb;

		// Clear previous clauses (for any other potential filters added before).
		$this->clear_all_clauses();

		// Add SQL clause to get all columns from the table.
		$this->add_sql_clause( 'select', '*' );
		$this->add_sql_clause( 'from', $this->get_table_name_with_prefix() );
		$this->add_sql_clause( 'where', $wpdb->prepare( ' AND instance_id = %d', $instance_id ) );

		// Add ORDER BY clause to order results by rate_order.
		$this->add_sql_clause( 'order_by', ' rate_order ASC' );

		// Build the query statement.
		$query_statement = $this->get_query_statement();

		// Execute the query and return the results.
		return $wpdb->get_results( $query_statement, $output ); // phpcs:ignore
	}

	/**
	 * Get distance rate info by seller.
	 *
	 * @since 4.1.3
	 *
	 * @param int $seller_id Seller ID.
	 * @param int $id        Rate ID.
	 *
	 * @return array|null Array of distance rate info or null.
	 */
	public function get_rate_info_by_seller( $id, $seller_id ) {
		global $wpdb;

		// Clear previous clauses (for any other potential filters added before)
		$this->clear_all_clauses();

		// Add SQL clause to get all columns from the table
		$this->add_sql_clause( 'select', '*' );
		$this->add_sql_clause( 'from', $this->get_table_name_with_prefix() );
		$this->add_sql_clause( 'where', $wpdb->prepare( ' AND seller_id = %d', $seller_id ) );

		if ( $id ) {
			$this->add_sql_clause( 'where', $wpdb->prepare( ' AND id = %d', $id ) );
		}

		// Build the query statement
		$query_statement = $this->get_query_statement();

        return $wpdb->get_results( $query_statement ); // phpcs:ignore
	}

	/**
	 * Delete distance rates.
	 *
	 * @since 4.1.3
	 *
	 * @param array $rate_ids Distance rate IDs to delete.
	 *
	 * @return bool|int|null|void
	 */
	public function delete_rates( array $rate_ids ): int {
		global $wpdb;

		// Build placeholder string based on the number of IDs.
		$placeholders = implode( ',', array_fill( 0, count( $rate_ids ), '%d' ) );

        // @codingStandardsIgnoreStart
		return $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$this->get_table_name_with_prefix()} WHERE rate_id IN ({$placeholders})",
				array_map( 'intval', $rate_ids )
			)
		);
        // @codingStandardsIgnoreEnd
	}

	/**
	 * Clear shipping transients.
	 *
	 * @since 4.1.3
	 *
	 * @return void
	 */
	public static function clear_shipping_transients() {
		global $wpdb;

        // @codingStandardsIgnoreStart
		$wpdb->query(
            "DELETE FROM `$wpdb->options` WHERE `option_name` LIKE ('_transient_dokan_ship_%')"
        );
        // @codingStandardsIgnoreEnd
	}
}
