<?php

namespace WeDevs\DokanPro\Modules\VendorSupport\Models\DataStore;

use WeDevs\Dokan\Models\DataStore\BaseDataStore;
use WeDevs\Dokan\Models\BaseModel;
use WeDevs\DokanPro\Modules\VendorSupport\Models\Conversation;

defined( 'ABSPATH' ) || exit;

/**
 * Class ConversationStore
 *
 * Data store for vendor support conversations.
 *
 * @since 4.1.2
 *
 * @package WeDevs\DokanPro\Modules\VendorSupport\Models\DataStore
 */
class ConversationStore extends BaseDataStore {

    /**
     * Get table name.
     *
     * @since 4.1.2
     *
     * @return string
     */
    public function get_table_name(): string {
        return 'dokan_vendor_support_conversations';
    }

    /**
     * Create a new conversation record in the database.
     *
     * @since 4.1.2
     *
     * @param BaseModel $model The conversation model.
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
            'ticket_id'    => '%d',
            'message'      => '%s',
            'sender_type'  => '%s',
            'sender_id'    => '%d',
            'is_read'      => '%d',
            'date_created' => '%s',
        ];
    }

    /**
     * Map boolean values for database storage.
     *
     * @since 4.1.2
     *
     * @param Conversation $model The conversation model.
     * @param string $context The context.
     * @return int
     */
    protected function get_is_read( Conversation $model, string $context = 'edit' ): int {
        return $model->get_is_read( $context );
    }

    /**
     * Map date_created for database storage.
     *
     * @since 4.1.2
     *
     * @param Conversation $model The conversation model.
     * @param string $context The context.
     * @return string|null
     */
    protected function get_date_created( Conversation $model, string $context = 'edit' ) {
        $date = $model->get_date_created( $context );
        return $date ? gmdate( 'Y-m-d H:i:s', $date->getTimestamp() ) : null;
    }

    /**
     * Map model data to database data for saving.
     *
     * @since 4.1.2
     *
     * @param BaseModel $model The conversation model.
     * @return array
     */
    protected function map_model_to_db_data( BaseModel &$model ): array {
        $data = [];

        // Get all field values from the model
        $data['ticket_id']   = $model->get_ticket_id( 'edit' );
        $data['message']     = $model->get_message( 'edit' );
        $data['sender_type'] = $model->get_sender_type( 'edit' );
        $data['sender_id']   = $model->get_sender_id( 'edit' );
        $data['is_read']     = $model->get_is_read( 'edit' );

        // Convert date to string format for database
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

        // Convert boolean field from database integer to boolean
        if ( isset( $mapped_data['is_read'] ) ) {
            $mapped_data['is_read'] = (bool) $mapped_data['is_read'];
        }

        // Convert GMT date strings to WC_DateTime in WP timezone.
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
     * Get conversations by ticket ID.
     *
     * @since 4.1.2
     *
     * @param int   $ticket_id Ticket ID.
     * @param array $args      Query arguments.
     * @return array
     */
    public function get_conversations_by_ticket( int $ticket_id, array $args = [] ): array {
        global $wpdb;

        $defaults = [
            'limit'    => 0,
            'offset'   => 0,
            'orderby'  => 'date_created',
            'order'    => 'ASC',
            'is_read'  => '',
        ];

        $args = wp_parse_args( $args, $defaults );

        $this->add_sql_clause( 'select', '*' );
        $this->add_sql_clause( 'from', $this->get_table_name_with_prefix() );
        $this->add_sql_clause( 'where', $wpdb->prepare( ' AND ticket_id = %d', $ticket_id ) );

        // Filter by read status
        if ( '' !== $args['is_read'] ) {
            $is_read = $args['is_read'] ? 1 : 0;
            $this->add_sql_clause( 'where', $wpdb->prepare( ' AND is_read = %d', $is_read ) );
        }

        // Order by
        $valid_orderby = [ 'id', 'date_created', 'sender_type' ];
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
     * Get conversations count by ticket ID.
     *
     * @since 4.1.2
     *
     * @param int   $ticket_id Ticket ID.
     * @param array $args      Query arguments.
     * @return int
     */
    public function get_conversations_count_by_ticket( int $ticket_id, array $args = [] ): int {
        global $wpdb;

        $defaults = [
            'is_read' => '',
        ];

        $args = wp_parse_args( $args, $defaults );

        $this->add_sql_clause( 'select', 'COUNT(*)' );
        $this->add_sql_clause( 'from', $this->get_table_name_with_prefix() );
        $this->add_sql_clause( 'where', $wpdb->prepare( ' AND ticket_id = %d', $ticket_id ) );

        // Filter by read status
        if ( '' !== $args['is_read'] ) {
            $is_read = $args['is_read'] ? 1 : 0;
            $this->add_sql_clause( 'where', $wpdb->prepare( ' AND is_read = %d', $is_read ) );
        }

        $query_statement = $this->get_query_statement();

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        return (int) $wpdb->get_var( $query_statement );
    }

    /**
     * Get latest conversation by ticket ID.
     *
     * @since 4.1.2
     *
     * @param int $ticket_id Ticket ID.
     * @return array|null
     */
    public function get_latest_conversation_by_ticket( int $ticket_id ) {
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
     * Get unread conversations count by ticket ID and recipient type.
     *
     * @since 4.1.2
     *
     * @param int    $ticket_id Ticket ID.
     * @param string $recipient_type Recipient type (admin or vendor).
     * @return int
     */
    public function get_unread_count_by_ticket_and_recipient( int $ticket_id, string $recipient_type ): int {
        global $wpdb;

        // If recipient is admin, count unread messages from vendor
        // If recipient is vendor, count unread messages from admin
        $sender_type = ( 'admin' === $recipient_type ) ? Conversation::SENDER_VENDOR : Conversation::SENDER_ADMIN;

        $this->add_sql_clause( 'select', 'COUNT(*)' );
        $this->add_sql_clause( 'from', $this->get_table_name_with_prefix() );
        $this->add_sql_clause( 'where', $wpdb->prepare( ' AND ticket_id = %d', $ticket_id ) );
        $this->add_sql_clause( 'where', $wpdb->prepare( ' AND sender_type = %s', $sender_type ) );
        $this->add_sql_clause( 'where', ' AND is_read = 0' );

        $query_statement = $this->get_query_statement();

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        return (int) $wpdb->get_var( $query_statement );
    }

    /**
     * Mark conversations as read by ticket ID and recipient type.
     *
     * @since 4.1.2
     *
     * @param int    $ticket_id Ticket ID.
     * @param string $recipient_type Recipient type (admin or vendor).
     * @return int Number of updated conversations.
     */
    public function mark_as_read_by_ticket_and_recipient( int $ticket_id, string $recipient_type ): int {
        global $wpdb;

        // If recipient is admin, mark messages from vendor as read
        // If recipient is vendor, mark messages from admin as read
        $sender_type = ( 'admin' === $recipient_type ) ? Conversation::SENDER_VENDOR : Conversation::SENDER_ADMIN;

        $updated = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$this->get_table_name_with_prefix()}
                SET is_read = 1
                WHERE ticket_id = %d
                AND sender_type = %s
                AND is_read = 0",
                $ticket_id,
                $sender_type
            )
        );

        return (int) $updated;
    }

    /**
     * Get conversations by sender.
     *
     * @since 4.1.2
     *
     * @param string $sender_type Sender type (admin or vendor).
     * @param int    $sender_id   Sender ID.
     * @param array  $args        Query arguments.
     * @return array
     */
    public function get_conversations_by_sender( string $sender_type, int $sender_id, array $args = [] ): array {
        global $wpdb;

        $defaults = [
            'limit'    => 20,
            'offset'   => 0,
            'orderby'  => 'date_created',
            'order'    => 'DESC',
        ];

        $args = wp_parse_args( $args, $defaults );

        $this->add_sql_clause( 'select', '*' );
        $this->add_sql_clause( 'from', $this->get_table_name_with_prefix() );
        $this->add_sql_clause( 'where', $wpdb->prepare( ' AND sender_type = %s', $sender_type ) );
        $this->add_sql_clause( 'where', $wpdb->prepare( ' AND sender_id = %d', $sender_id ) );

        // Order by
        $valid_orderby = [ 'id', 'date_created', 'ticket_id' ];
        $orderby = in_array( $args['orderby'], $valid_orderby, true ) ? $args['orderby'] : 'date_created';
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
     * Delete conversations by ticket ID.
     *
     * @since 4.1.2
     *
     * @param int $ticket_id Ticket ID.
     * @return int Number of deleted conversations.
     */
    public function delete_conversations_by_ticket( int $ticket_id ): int {
        global $wpdb;

        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->get_table_name_with_prefix()} WHERE ticket_id = %d",
                $ticket_id
            )
        );

        return (int) $deleted;
    }

    /**
     * Get total unread conversations count for a recipient.
     *
     * @since 4.1.2
     *
     * @param string $recipient_type Recipient type (admin or vendor).
     * @param int    $recipient_id   Recipient ID (0 for admin, vendor ID for vendor).
     * @return int
     */
    public function get_total_unread_count_for_recipient( string $recipient_type, int $recipient_id = 0 ): int {
        global $wpdb;

        // If recipient is admin, count unread messages from all vendors
        // If recipient is vendor, count unread messages from admin for this vendor's tickets
        if ( 'admin' === $recipient_type ) {
            $this->add_sql_clause( 'select', 'COUNT(*)' );
            $this->add_sql_clause( 'from', $this->get_table_name_with_prefix() );
            $this->add_sql_clause( 'where', $wpdb->prepare( ' AND sender_type = %s', Conversation::SENDER_VENDOR ) );
            $this->add_sql_clause( 'where', ' AND is_read = 0' );
        } else {
            // For vendor, join with tickets table to get only their tickets
            $tickets_table = $wpdb->prefix . 'dokan_vendor_support_tickets';

            $this->add_sql_clause( 'select', 'COUNT(c.id)' );
            $this->add_sql_clause( 'from', $this->get_table_name_with_prefix() . ' c' );
            $this->add_sql_clause( 'join', "INNER JOIN {$tickets_table} t ON c.ticket_id = t.id" );
            $this->add_sql_clause( 'where', $wpdb->prepare( ' AND t.vendor_id = %d', $recipient_id ) );
            $this->add_sql_clause( 'where', $wpdb->prepare( ' AND c.sender_type = %s', Conversation::SENDER_ADMIN ) );
            $this->add_sql_clause( 'where', ' AND c.is_read = 0' );
        }

        $query_statement = $this->get_query_statement();

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        return (int) $wpdb->get_var( $query_statement );
    }
}
