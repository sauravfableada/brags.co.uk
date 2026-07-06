<?php

namespace WeDevs\DokanPro\Upgrade\Upgraders;

use WeDevs\DokanPro\Abstracts\DokanProUpgrader;

class V_3_12_3 extends DokanProUpgrader {

    /**
     * Updates RFQ database table.
     *
     * @since 3.12.3
     *
     * @return void
     */
    public static function update_request_for_quote_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'dokan_request_quotes';

        // Search if the dokan_request_quotes table exists.
        $has_rfq_table = $wpdb->get_var(
            $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) ) // phpcs:ignore Squiz.WhiteSpace.SuperfluousWhitespace.EndLine
        );

        if ( $has_rfq_table !== $table_name ) {
            return;
        }

        // Columns list of dokan_request_quotes.
        $existing_columns = $wpdb->get_col( "DESC {$table_name}", 0 ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        // Determine which columns to add.
        $columns_to_add = [];
        if ( ! in_array( 'store_info', $existing_columns, true ) ) {
            $columns_to_add[] = 'ADD COLUMN `store_info` longtext NULL AFTER `quote_title`';
        }
        if ( ! in_array( 'shipping_cost', $existing_columns, true ) ) {
            $columns_to_add[] = 'ADD COLUMN `%3$s` decimal(10, 2) NOT NULL DEFAULT 0.00 AFTER `customer_info`';
        }
        if ( ! in_array( 'expiry_date', $existing_columns, true ) ) {
            $columns_to_add[] = 'ADD COLUMN `%4$s` int(11) unsigned DEFAULT 0 AFTER `status`';
        }
        if ( ! in_array( 'expected_date', $existing_columns, true ) ) {
            $columns_to_add[] = 'ADD COLUMN `%5$s` int(11) unsigned DEFAULT 0 AFTER `expiry_date`';
        }

        // If there are columns to add, run the ALTER TABLE query.
        if ( ! empty( $columns_to_add ) ) {
            $alter_query = 'ALTER TABLE `%1$s` ' . implode( ', ', $columns_to_add );

            $wpdb->query(
                $wpdb->prepare( $alter_query, $table_name, 'store_id', 'shipping_cost', 'expiry_date', 'expected_date' ) // phpcs:ignore
            );
        }
    }
}
