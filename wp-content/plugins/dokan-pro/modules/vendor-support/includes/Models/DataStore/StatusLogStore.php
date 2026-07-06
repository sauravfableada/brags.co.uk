<?php

namespace WeDevs\DokanPro\Modules\VendorSupport\Models\DataStore;

use WeDevs\Dokan\Models\DataStore\BaseDataStore;
use WeDevs\Dokan\Models\BaseModel;
use WeDevs\DokanPro\Modules\VendorSupport\Models\StatusLog;

defined( 'ABSPATH' ) || exit;

/**
 * Class StatusLogStore
 *
 * Data store for status log operations.
 *
 * @since 4.1.2
 *
 * @package WeDevs\DokanPro\Modules\VendorSupport\Models\DataStore
 */
class StatusLogStore extends BaseDataStore {

    /**
     * Get table name.
     *
     * @since 4.1.2
     *
     * @return string
     */
    public function get_table_name(): string {
        return 'dokan_vendor_support_status_logs';
    }

    /**
     * Create a new status log record in the database.
     *
     * @since 4.1.2
     *
     * @param BaseModel $model The status log model.
     * @return int The inserted ID.
     */
    public function create( BaseModel &$model ) {
        // Set date_created if not already set
        if ( ! $model->get_date_created() ) {
            $ts = current_time( 'timestamp', true );
            $dt = new \WC_DateTime( "@{$ts}", new \DateTimeZone( 'UTC' ) );
            if ( get_option( 'timezone_string' ) ) {
                $dt->setTimezone( new \DateTimeZone( wc_timezone_string() ) );
            } else {
                $dt->set_utc_offset( wc_timezone_offset() );
            }
            $model->set_date_created( $dt );
        }

        return parent::create( $model );
    }

    /**
     * Get fields with format.
     *
     * @since 4.1.2
     *
     * @return array
     */
    protected function get_fields_with_format(): array {
        return [
            'ticket_id'       => '%d',
            'old_status'      => '%s',
            'new_status'      => '%s',
            'changed_by_type' => '%s',
            'changed_by_id'   => '%d',
            'note'            => '%s',
            'date_created'    => '%s',
        ];
    }

    /**
     * Map date_created for database storage.
     *
     * @since 4.1.2
     *
     * @param StatusLog $model The status log model.
     * @param string $context The context.
     * @return string|null
     */
    protected function get_date_created( StatusLog $model, string $context = 'edit' ) {
        $date = $model->get_date_created( $context );
        return $date ? gmdate( 'Y-m-d H:i:s', $date->getTimestamp() ) : null;
    }

    /**
     * Map model data to database data for saving.
     *
     * @since 4.1.2
     *
     * @param BaseModel $model The status log model.
     * @return array
     */
    protected function map_model_to_db_data( BaseModel &$model ): array {
        $data = [];

        // Get all field values from the model
        $data['ticket_id']       = $model->get_ticket_id( 'edit' );
        $data['old_status']      = $model->get_old_status( 'edit' );
        $data['new_status']      = $model->get_new_status( 'edit' );
        $data['changed_by_type'] = $model->get_changed_by_type( 'edit' );
        $data['changed_by_id']   = $model->get_changed_by_id( 'edit' );
        $data['note']            = $model->get_note( 'edit' );

        // Convert date to string format for database (always GMT)
        $date_created = $model->get_date_created( 'edit' );
        $data['date_created'] = $date_created ? gmdate( 'Y-m-d H:i:s', $date_created->getTimestamp() ) : current_time( 'mysql', true );

        return $data;
    }

    /**
     * Map database raw data to model data.
     *
     * @since 4.1.2
     *
     * @param array $raw_data Raw data from database.
     * @return array
     */
    protected function map_db_raw_to_model_data( $raw_data ): array {
        $mapped_data = parent::map_db_raw_to_model_data( $raw_data );

        // Convert GMT date strings from DB to WC_DateTime in WP timezone.
        if ( ! empty( $mapped_data['date_created'] ) && is_string( $mapped_data['date_created'] ) ) {
            $ts = strtotime( $mapped_data['date_created'] . ' UTC' );
            if ( $ts ) {
                $dt = new \WC_DateTime( "@{$ts}", new \DateTimeZone( 'UTC' ) );
                if ( get_option( 'timezone_string' ) ) {
                    $dt->setTimezone( new \DateTimeZone( wc_timezone_string() ) );
                } else {
                    $dt->set_utc_offset( wc_timezone_offset() );
                }
                $mapped_data['date_created'] = $dt;
            } else {
                $mapped_data['date_created'] = null;
            }
        }

        return $mapped_data;
    }

    /**
     * Get status logs by ticket ID.
     *
     * @since 4.1.2
     *
     * @param int   $ticket_id Ticket ID.
     * @param array $args      Query arguments.
     * @return array
     */
    public function get_status_logs_by_ticket( int $ticket_id, array $args = [] ): array {
        global $wpdb;

        $defaults = [
            'limit'           => 0,
            'offset'          => 0,
            'orderby'         => 'date_created',
            'order'           => 'ASC',
            'changed_by_type' => '',
        ];

        $args = wp_parse_args( $args, $defaults );

        $this->add_sql_clause( 'select', '*' );
        $this->add_sql_clause( 'from', $this->get_table_name_with_prefix() );
        $this->add_sql_clause( 'where', $wpdb->prepare( ' AND ticket_id = %d', $ticket_id ) );

        // Filter by changed_by_type
        if ( '' !== $args['changed_by_type'] ) {
            $this->add_sql_clause( 'where', $wpdb->prepare( ' AND changed_by_type = %s', $args['changed_by_type'] ) );
        }

        // Order by
        $valid_orderby = [ 'id', 'date_created', 'changed_by_type', 'new_status' ];
        $orderby = in_array( $args['orderby'], $valid_orderby, true ) ? $args['orderby'] : 'date_created';
        $order = in_array( strtoupper( $args['order'] ), [ 'ASC', 'DESC' ], true ) ? strtoupper( $args['order'] ) : 'ASC';

        $this->add_sql_clause( 'order_by', "{$orderby} {$order}" );

        // Limit and offset
        if ( $args['limit'] > 0 ) {
            $this->add_sql_clause( 'limit', $wpdb->prepare( 'LIMIT %d OFFSET %d', $args['limit'], $args['offset'] ) );
        }

        $query_statement = $this->get_query_statement();

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        return $wpdb->get_results( $query_statement, ARRAY_A );
    }

    /**
     * Get status logs count by ticket ID.
     *
     * @since 4.1.2
     *
     * @param int   $ticket_id Ticket ID.
     * @param array $args      Query arguments.
     * @return int
     */
    public function get_status_logs_count_by_ticket( int $ticket_id, array $args = [] ): int {
        global $wpdb;

        $defaults = [
            'changed_by_type' => '',
        ];

        $args = wp_parse_args( $args, $defaults );

        $this->add_sql_clause( 'select', 'COUNT(*)' );
        $this->add_sql_clause( 'from', $this->get_table_name_with_prefix() );
        $this->add_sql_clause( 'where', $wpdb->prepare( ' AND ticket_id = %d', $ticket_id ) );

        // Filter by changed_by_type
        if ( '' !== $args['changed_by_type'] ) {
            $this->add_sql_clause( 'where', $wpdb->prepare( ' AND changed_by_type = %s', $args['changed_by_type'] ) );
        }

        $query_statement = $this->get_query_statement();

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        return (int) $wpdb->get_var( $query_statement );
    }

    /**
     * Get latest status log by ticket ID.
     *
     * @since 4.1.2
     *
     * @param int $ticket_id Ticket ID.
     * @return array|null
     */
    public function get_latest_status_log_by_ticket( int $ticket_id ) {
        global $wpdb;

        $this->add_sql_clause( 'select', '*' );
        $this->add_sql_clause( 'from', $this->get_table_name_with_prefix() );
        $this->add_sql_clause( 'where', $wpdb->prepare( ' AND ticket_id = %d', $ticket_id ) );
        $this->add_sql_clause( 'order_by', 'date_created DESC' );
        $this->add_sql_clause( 'limit', 'LIMIT 1' );

        $query_statement = $this->get_query_statement();

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        return $wpdb->get_row( $query_statement, ARRAY_A );
    }

    /**
     * Delete status logs by ticket ID.
     *
     * @since 4.1.2
     *
     * @param int $ticket_id Ticket ID.
     * @return int Number of deleted rows.
     */
    public function delete_status_logs_by_ticket( int $ticket_id ): int {
        global $wpdb;

        $table_name = $this->get_table_name_with_prefix();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        return $wpdb->delete(
            $table_name,
            [ 'ticket_id' => $ticket_id ],
            [ '%d' ]
        );
    }

    /**
     * Query method for StatusLog model.
     *
     * @since 4.1.2
     *
     * @param int   $ticket_id Ticket ID.
     * @param array $args      Query arguments.
     * @return array Array of status log IDs.
     */
    public function query( int $ticket_id, array $args = [] ): array {
        $status_logs = $this->get_status_logs_by_ticket( $ticket_id, $args );
        return wp_list_pluck( $status_logs, 'id' );
    }

    /**
     * Count method for StatusLog model.
     *
     * @since 4.1.2
     *
     * @param int   $ticket_id Ticket ID.
     * @param array $args      Query arguments.
     * @return int
     */
    public function count( int $ticket_id, array $args = [] ): int {
        return $this->get_status_logs_count_by_ticket( $ticket_id, $args );
    }
}
