<?php

namespace WeDevs\DokanPro\Modules\VendorSupport\Models\DataStore;

use WeDevs\Dokan\Models\DataStore\BaseDataStore;
use WeDevs\Dokan\Models\BaseModel;
use WeDevs\DokanPro\Modules\VendorSupport\Models\Ticket;

defined( 'ABSPATH' ) || exit;

/**
 * Class TicketStore
 *
 * Data store for vendor support tickets.
 *
 * @since 4.1.2
 *
 * @package WeDevs\DokanPro\Modules\VendorSupport\Models\DataStore
 */
class TicketStore extends BaseDataStore {

    /**
     * Get table name.
     *
     * @since 4.1.2
     *
     * @return string
     */
    public function get_table_name(): string {
        return 'dokan_vendor_support_tickets';
    }

    /**
     * Create a new ticket record in the database.
     *
     * @since 4.1.2
     *
     * @param BaseModel $model The ticket model.
     * @return int The inserted ID.
     */
    public function create( BaseModel &$model ) {
        $current_timestamp = current_time( 'timestamp', true );

        // Build WC_DateTime now in WP timezone
        $now_dt = new \WC_DateTime( "@{$current_timestamp}", new \DateTimeZone( 'UTC' ) );
        if ( get_option( 'timezone_string' ) ) {
            $now_dt->setTimezone( new \DateTimeZone( wc_timezone_string() ) );
        } else {
            $now_dt->set_utc_offset( wc_timezone_offset() );
        }

        // Set date_created if not already set
        if ( ! $model->get_date_created() ) {
            $model->set_date_created( $now_dt );
        }

        // Set date_updated if not already set
        if ( ! $model->get_date_updated() ) {
            $model->set_date_updated( clone $now_dt );
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
            'vendor_id'        => '%d',
            'subject'          => '%s',
            'status'           => '%s',
            'priority'         => '%s',
            'last_reply_by'    => '%s',
            'last_reply_at'    => '%s',
            'is_read_by_admin' => '%d',
            'is_read_by_vendor' => '%d',
            'date_created'     => '%s',
            'date_updated'     => '%s',
        ];
    }

    /**
     * Map boolean values for database storage.
     *
     * @since 4.1.2
     *
     * @param Ticket $model The ticket model.
     * @param string $context The context.
     * @return int
     */
    protected function get_is_read_by_admin( Ticket $model, string $context = 'edit' ): int {
        return $model->get_is_read_by_admin( $context ) ? 1 : 0;
    }

    /**
     * Map boolean values for database storage.
     *
     * @since 4.1.2
     *
     * @param Ticket $model The ticket model.
     * @param string $context The context.
     * @return int
     */
    protected function get_is_read_by_vendor( Ticket $model, string $context = 'edit' ): int {
        return $model->get_is_read_by_vendor( $context ) ? 1 : 0;
    }

    /**
     * Map last_reply_at date for database storage.
     *
     * @since 4.1.2
     *
     * @param Ticket $model The ticket model.
     * @param string $context The context.
     * @return string|null
     */
    protected function get_last_reply_at( Ticket $model, string $context = 'edit' ) {
        $date = $model->get_last_reply_at( $context );
        return $date ? gmdate( 'Y-m-d H:i:s', $date->getTimestamp() ) : null;
    }

    /**
     * Map date_created for database storage.
     *
     * @since 4.1.2
     *
     * @param Ticket $model The ticket model.
     * @param string $context The context.
     * @return string|null
     */
    protected function get_date_created( Ticket $model, string $context = 'edit' ) {
        $date = $model->get_date_created( $context );
        return $date ? gmdate( 'Y-m-d H:i:s', $date->getTimestamp() ) : null;
    }

    /**
     * Map date_updated for database storage.
     *
     * @since 4.1.2
     *
     * @param Ticket $model The ticket model.
     * @param string $context The context.
     * @return string|null
     */
    protected function get_date_updated( Ticket $model, string $context = 'edit' ) {
        $date = $model->get_date_updated( $context );
        return $date ? gmdate( 'Y-m-d H:i:s', $date->getTimestamp() ) : null;
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

        // Convert boolean fields from database integers to boolean
        if ( isset( $mapped_data['is_read_by_admin'] ) ) {
            $mapped_data['is_read_by_admin'] = (bool) $mapped_data['is_read_by_admin'];
        }

        if ( isset( $mapped_data['is_read_by_vendor'] ) ) {
            $mapped_data['is_read_by_vendor'] = (bool) $mapped_data['is_read_by_vendor'];
        }

        // Convert GMT date strings from DB to WC_DateTime in WP timezone.
        foreach ( [ 'date_created', 'date_updated', 'last_reply_at' ] as $date_field ) {
            if ( ! empty( $mapped_data[ $date_field ] ) && is_string( $mapped_data[ $date_field ] ) ) {
                $ts = strtotime( $mapped_data[ $date_field ] . ' UTC' );
                if ( $ts ) {
                    $dt = new \WC_DateTime( "@{$ts}", new \DateTimeZone( 'UTC' ) );
                    if ( get_option( 'timezone_string' ) ) {
                        $dt->setTimezone( new \DateTimeZone( wc_timezone_string() ) );
                    } else {
                        $dt->set_utc_offset( wc_timezone_offset() );
                    }
                    $mapped_data[ $date_field ] = $dt;
                } else {
                    $mapped_data[ $date_field ] = null;
                }
            }
        }

        return $mapped_data;
    }

    /**
     * Get tickets by vendor ID.
     *
     * @since 4.1.2
     *
     * @param int   $vendor_id Vendor ID.
     * @param array $args      Query arguments.
     * @return array
     */
    public function get_tickets_by_vendor( int $vendor_id, array $args = [] ): array {
        global $wpdb;

        $defaults = [
            'status'    => '',
            'priority'  => '',
            'limit'     => 20,
            'offset'    => 0,
            'orderby'   => 'last_reply_at',
            'order'     => 'DESC',
            'search'    => '',
            'date_from' => '',
            'date_to'   => '',
        ];

        $args = wp_parse_args( $args, $defaults );

        $this->add_sql_clause( 'select', '*' );
        $this->add_sql_clause( 'from', $this->get_table_name_with_prefix() );
        $this->add_sql_clause( 'where', $wpdb->prepare( ' AND vendor_id = %d', $vendor_id ) );

        // Filter by status
        if ( ! empty( $args['status'] ) && in_array( $args['status'], Ticket::get_valid_statuses(), true ) ) {
            $this->add_sql_clause( 'where', $wpdb->prepare( ' AND status = %s', $args['status'] ) );
        }

        // Filter by priority
        if ( ! empty( $args['priority'] ) && in_array( $args['priority'], ( new Ticket() )->get_valid_priorities(), true ) ) {
            $this->add_sql_clause( 'where', $wpdb->prepare( ' AND priority = %s', $args['priority'] ) );
        }

        // Date range filter
        if ( ! empty( $args['date_from'] ) ) {
            $this->add_sql_clause( 'where', $wpdb->prepare( ' AND DATE(date_created) >= %s', $args['date_from'] ) );
        }

        if ( ! empty( $args['date_to'] ) ) {
            $this->add_sql_clause( 'where', $wpdb->prepare( ' AND DATE(date_created) <= %s', $args['date_to'] ) );
        }

        // Search functionality
        if ( ! empty( $args['search'] ) ) {
            $search_term = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $this->add_sql_clause( 'where', $wpdb->prepare( ' AND (subject LIKE %s OR id LIKE %s)', $search_term, $search_term ) );
        }

        // Order by
        $valid_orderby = [ 'id', 'subject', 'status', 'date_created', 'last_reply_at' ];
        $orderby = in_array( $args['orderby'], $valid_orderby, true ) ? $args['orderby'] : 'last_reply_at';
        $order = in_array( strtoupper( $args['order'] ), [ 'ASC', 'DESC' ], true ) ? strtoupper( $args['order'] ) : 'DESC';

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
     * Get tickets count by vendor ID.
     *
     * @since 4.1.2
     *
     * @param int   $vendor_id Vendor ID.
     * @param array $args      Query arguments.
     * @return int
     */
    public function get_tickets_count_by_vendor( int $vendor_id, array $args = [] ): int {
        global $wpdb;

        $defaults = [
            'status'   => '',
            'priority' => '',
            'search'   => '',
            'date_from'  => '',
            'date_to'    => '',
        ];

        $args = wp_parse_args( $args, $defaults );

        $this->add_sql_clause( 'select', 'COUNT(*)' );
        $this->add_sql_clause( 'from', $this->get_table_name_with_prefix() );
        $this->add_sql_clause( 'where', $wpdb->prepare( ' AND vendor_id = %d', $vendor_id ) );

        // Filter by status
        if ( ! empty( $args['status'] ) && in_array( $args['status'], Ticket::get_valid_statuses(), true ) ) {
            $this->add_sql_clause( 'where', $wpdb->prepare( ' AND status = %s', $args['status'] ) );
        }

        // Filter by priority
        if ( ! empty( $args['priority'] ) && in_array( $args['priority'], ( new Ticket() )->get_valid_priorities(), true ) ) {
            $this->add_sql_clause( 'where', $wpdb->prepare( ' AND priority = %s', $args['priority'] ) );
        }

        // Date range filter
        if ( ! empty( $args['date_from'] ) ) {
            $this->add_sql_clause( 'where', $wpdb->prepare( ' AND DATE(date_created) >= %s', $args['date_from'] ) );
        }

        if ( ! empty( $args['date_to'] ) ) {
            $this->add_sql_clause( 'where', $wpdb->prepare( ' AND DATE(date_created) <= %s', $args['date_to'] ) );
        }

        // Search functionality
        if ( ! empty( $args['search'] ) ) {
            $search_term = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $this->add_sql_clause( 'where', $wpdb->prepare( ' AND (subject LIKE %s OR id LIKE %s)', $search_term, $search_term ) );
        }

        $query_statement = $this->get_query_statement();

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        return (int) $wpdb->get_var( $query_statement );
    }

    /**
     * Get all tickets for admin with filtering options.
     *
     * @since 4.1.2
     *
     * @param array $args Query arguments.
     * @return array
     */
    public function get_all_tickets( array $args = [] ): array {
        global $wpdb;

        $defaults = [
            'status'     => '',
            'priority'   => '',
            'vendor_id'  => 0,
            'limit'      => 20,
            'offset'     => 0,
            'orderby'    => 'last_reply_at',
            'order'      => 'DESC',
            'search'     => '',
            'date_from'  => '',
            'date_to'    => '',
        ];

        $args = wp_parse_args( $args, $defaults );

        $this->add_sql_clause( 'select', '*' );
        $this->add_sql_clause( 'from', $this->get_table_name_with_prefix() );

        // Filter by vendor
        if ( ! empty( $args['vendor_id'] ) ) {
            $this->add_sql_clause( 'where', $wpdb->prepare( ' AND vendor_id = %d', $args['vendor_id'] ) );
        }

        // Filter by status
        if ( ! empty( $args['status'] ) && in_array( $args['status'], Ticket::get_valid_statuses(), true ) ) {
            $this->add_sql_clause( 'where', $wpdb->prepare( ' AND status = %s', $args['status'] ) );
        }

        // Filter by priority
        if ( ! empty( $args['priority'] ) && in_array( $args['priority'], ( new Ticket() )->get_valid_priorities(), true ) ) {
            $this->add_sql_clause( 'where', $wpdb->prepare( ' AND priority = %s', $args['priority'] ) );
        }

        // Date range filter
        if ( ! empty( $args['date_from'] ) ) {
            $this->add_sql_clause( 'where', $wpdb->prepare( ' AND DATE(date_created) >= %s', $args['date_from'] ) );
        }

        if ( ! empty( $args['date_to'] ) ) {
            $this->add_sql_clause( 'where', $wpdb->prepare( ' AND DATE(date_created) <= %s', $args['date_to'] ) );
        }

        // Search functionality
        if ( ! empty( $args['search'] ) ) {
            $search_term = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $this->add_sql_clause( 'where', $wpdb->prepare( ' AND (subject LIKE %s OR id LIKE %s)', $search_term, $search_term ) );
        }

        // Order by
        $valid_orderby = [ 'id', 'subject', 'status', 'date_created', 'last_reply_at', 'vendor_id' ];
        $orderby = in_array( $args['orderby'], $valid_orderby, true ) ? $args['orderby'] : 'last_reply_at';
        $order = in_array( strtoupper( $args['order'] ), [ 'ASC', 'DESC' ], true ) ? strtoupper( $args['order'] ) : 'DESC';

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
     * Get total tickets count for admin with filtering options.
     *
     * @since 4.1.2
     *
     * @param array $args Query arguments.
     * @return int
     */
    public function get_all_tickets_count( array $args = [] ): int {
        global $wpdb;

        $defaults = [
            'status'     => '',
            'priority'   => '',
            'vendor_id'  => 0,
            'search'     => '',
            'date_from'  => '',
            'date_to'    => '',
        ];

        $args = wp_parse_args( $args, $defaults );

        $this->add_sql_clause( 'select', 'COUNT(*)' );
        $this->add_sql_clause( 'from', $this->get_table_name_with_prefix() );

        // Filter by vendor
        if ( ! empty( $args['vendor_id'] ) ) {
            $this->add_sql_clause( 'where', $wpdb->prepare( ' AND vendor_id = %d', $args['vendor_id'] ) );
        }

        // Filter by status
        if ( ! empty( $args['status'] ) && in_array( $args['status'], Ticket::get_valid_statuses(), true ) ) {
            $this->add_sql_clause( 'where', $wpdb->prepare( ' AND status = %s', $args['status'] ) );
        }

        // Filter by priority
        if ( ! empty( $args['priority'] ) && in_array( $args['priority'], ( new Ticket() )->get_valid_priorities(), true ) ) {
            $this->add_sql_clause( 'where', $wpdb->prepare( ' AND priority = %s', $args['priority'] ) );
        }

        // Date range filter
        if ( ! empty( $args['date_from'] ) ) {
            $this->add_sql_clause( 'where', $wpdb->prepare( ' AND DATE(date_created) >= %s', $args['date_from'] ) );
        }

        if ( ! empty( $args['date_to'] ) ) {
            $this->add_sql_clause( 'where', $wpdb->prepare( ' AND DATE(date_created) <= %s', $args['date_to'] ) );
        }

        // Search functionality
        if ( ! empty( $args['search'] ) ) {
            $search_term = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $this->add_sql_clause( 'where', $wpdb->prepare( ' AND (subject LIKE %s OR id LIKE %s)', $search_term, $search_term ) );
        }

        $query_statement = $this->get_query_statement();

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        return (int) $wpdb->get_var( $query_statement );
    }

    /**
     * Delete tickets older than specified months.
     *
     * @since 4.1.2
     *
     * @param int $months Number of months.
     * @return int Number of deleted tickets.
     */
    public function delete_tickets_older_than( int $months ): int {
        global $wpdb;

        $date_threshold = gmdate( 'Y-m-d H:i:s', strtotime( "-{$months} months" ) );

        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->get_table_name_with_prefix()} WHERE date_created < %s",
                $date_threshold
            )
        );

        return (int) $deleted;
    }

    /**
     * Mark all tickets as read by admin.
     *
     * @since 4.1.2
     *
     * @return int Number of updated tickets.
     */
    public function mark_all_as_read_by_admin(): int {
        global $wpdb;

        $updated = $wpdb->query(
            "UPDATE {$this->get_table_name_with_prefix()} SET is_read_by_admin = 1 WHERE is_read_by_admin = 0"
        );

        return (int) $updated;
    }
}
