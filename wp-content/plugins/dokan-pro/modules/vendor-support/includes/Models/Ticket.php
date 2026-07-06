<?php

namespace WeDevs\DokanPro\Modules\VendorSupport\Models;

use WeDevs\Dokan\Cache;
use WeDevs\Dokan\Models\BaseModel;
use WeDevs\DokanPro\Modules\VendorSupport\Models\DataStore\TicketStore;
use WeDevs\DokanPro\Modules\VendorSupport\Models\Conversation;
use WeDevs\DokanPro\Modules\VendorSupport\Models\StatusLog;

defined( 'ABSPATH' ) || exit;

/**
 * Class Ticket
 *
 * Represents a vendor support ticket.
 *
 * @since 4.1.2
 *
 * @package WeDevs\DokanPro\Modules\VendorSupport\Models
 */
class Ticket extends BaseModel {

    /**
     * Object type.
     *
     * @since 4.1.2
     *
     * @var string
     */
    protected $object_type = 'vendor_support_ticket';

    /**
     * Data store class.
     *
     * @since 4.1.2
     *
     * @var string
     */
    protected $cache_group = 'dokan_vendor_support_tickets';

    /**
     * Ticket statuses.
     *
     * @since 4.1.2
     */
    const STATUS_OPEN = 'open';
    const STATUS_CLOSED = 'closed';

    /**
     * Ticket priorities.
     *
     * @since 4.1.2
     */
    const PRIORITY_LOW = 'low';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_HIGH = 'high';

    /**
     * Reply sender types.
     *
     * @since 4.1.2
     */
    const SENDER_ADMIN = 'admin';
    const SENDER_VENDOR = 'vendor';

    /**
     * Default data for the ticket.
     *
     * @since 4.1.2
     *
     * @var array
     */
    protected $data = [
        'vendor_id'        => 0,
        'subject'          => '',
        'status'           => self::STATUS_OPEN,
        'priority'         => self::PRIORITY_NORMAL,
        'last_reply_by'    => null,
        'last_reply_at'    => null,
        'is_read_by_admin' => false,
        'is_read_by_vendor' => true,
        'date_created'     => null,
        'date_updated'     => null,
    ];

    /**
     * Initialize the data store.
     *
     * @since 4.1.2
     *
     * @param int $ticket_id Ticket ID.
     *
     * @throws \Exception If the ticket ID is invalid or the data store cannot be initialized.
     */
    public function __construct( int $ticket_id = 0 ) {
        parent::__construct( $ticket_id );
        $this->data_store = new TicketStore();

        // If ticket ID is provided, read the ticket data from the data store.
        if ( $ticket_id > 0 ) {
            $this->set_id( $ticket_id );
        }

        if ( $this->get_id() > 0 ) {
            $this->data_store->read( $this );
        }
    }

    /**
     * Get vendor ID.
     *
     * @since 4.1.2
     *
     * @param string $context View or edit context.
     * @return int
     */
    public function get_vendor_id( $context = 'view' ) {
        return $this->get_prop( 'vendor_id', $context );
    }

    /**
     * Set vendor ID.
     *
     * @since 4.1.2
     *
     * @param int $vendor_id Vendor ID.
     * @return void
     */
    public function set_vendor_id( $vendor_id ) {
        $this->set_prop( 'vendor_id', absint( $vendor_id ) );
    }

    /**
     * Get ticket subject.
     *
     * @since 4.1.2
     *
     * @param string $context View or edit context.
     * @return string
     */
    public function get_subject( $context = 'view' ) {
        return $this->get_prop( 'subject', $context );
    }

    /**
     * Set ticket subject.
     *
     * @since 4.1.2
     *
     * @param string $subject Ticket subject.
     * @return void
     */
    public function set_subject( $subject ) {
        $this->set_prop( 'subject', sanitize_text_field( $subject ) );
    }

    /**
     * Get ticket status.
     *
     * @since 4.1.2
     *
     * @param string $context View or edit context.
     * @return string
     */
    public function get_status( $context = 'view' ) {
        return $this->get_prop( 'status', $context );
    }

    /**
     * Set ticket status.
     *
     * @since 4.1.2
     *
     * @param string $status Ticket status.
     * @return void
     */
    public function set_status( $status ) {
        $valid_statuses = self::get_valid_statuses();
        if ( in_array( $status, $valid_statuses, true ) ) {
            $old_status = $this->get_status();
            $this->set_prop( 'status', $status );

            // Create status log if status has changed (Scenarios 3 & 4)
            if ( $this->get_object_read() && $old_status !== $status && $this->get_id() ) {
                $this->create_status_change_log( $old_status, $status );
            }
        }
    }

    /**
     * Get ticket priority.
     *
     * @since 4.1.2
     *
     * @param string $context View or edit context.
     * @return string
     */
    public function get_priority( $context = 'view' ) {
        return $this->get_prop( 'priority', $context );
    }

    /**
     * Set ticket priority.
     *
     * @since 4.1.2
     *
     * @param string $priority Ticket priority.
     * @return void
     */
    public function set_priority( $priority ) {
        $valid_priorities = [ self::PRIORITY_LOW, self::PRIORITY_NORMAL, self::PRIORITY_HIGH ];
        if ( in_array( $priority, $valid_priorities, true ) ) {
            $this->set_prop( 'priority', $priority );
        }
    }

    /**
     * Get last reply by.
     *
     * @since 4.1.2
     *
     * @param string $context View or edit context.
     * @return string|null
     */
    public function get_last_reply_by( $context = 'view' ) {
        return $this->get_prop( 'last_reply_by', $context );
    }

    /**
     * Set last reply by.
     *
     * @since 4.1.2
     *
     * @param string|null $last_reply_by Last reply by (admin or vendor).
     * @return void
     */
    public function set_last_reply_by( $last_reply_by ) {
        $valid_senders = [ self::SENDER_ADMIN, self::SENDER_VENDOR, null ];
        if ( in_array( $last_reply_by, $valid_senders, true ) ) {
            $this->set_prop( 'last_reply_by', $last_reply_by );
        }
    }

    /**
     * Get last reply at.
     *
     * @since 4.1.2
     *
     * @param string $context View or edit context.
     * @return \WC_DateTime|null
     */
    public function get_last_reply_at( $context = 'view' ) {
        return $this->get_prop( 'last_reply_at', $context );
    }

    /**
     * Set last reply at.
     *
     * @since 4.1.2
     *
     * @param \WC_DateTime|null $last_reply_at Last reply date.
     * @return void
     */
    public function set_last_reply_at( ?\WC_DateTime $last_reply_at ) {
        $this->set_prop( 'last_reply_at', $last_reply_at );
    }

    /**
     * Get is read by admin.
     *
     * @since 4.1.2
     *
     * @param string $context View or edit context.
     * @return bool
     */
    public function get_is_read_by_admin( $context = 'view' ) {
        return $this->get_prop( 'is_read_by_admin', $context );
    }

    /**
     * Set is read by admin.
     *
     * @since 4.1.2
     *
     * @param bool $is_read_by_admin Whether admin has read the ticket.
     * @return void
     */
    public function set_is_read_by_admin( $is_read_by_admin ) {
        $this->set_prop( 'is_read_by_admin', (bool) $is_read_by_admin );
    }

    /**
     * Get is read by vendor.
     *
     * @since 4.1.2
     *
     * @param string $context View or edit context.
     * @return bool
     */
    public function get_is_read_by_vendor( $context = 'view' ) {
        return $this->get_prop( 'is_read_by_vendor', $context );
    }

    /**
     * Set is read by vendor.
     *
     * @since 4.1.2
     *
     * @param bool $is_read_by_vendor Whether vendor has read the ticket.
     * @return void
     */
    public function set_is_read_by_vendor( $is_read_by_vendor ) {
        $this->set_prop( 'is_read_by_vendor', (bool) $is_read_by_vendor );
    }

    /**
     * Get date created.
     *
     * @since 4.1.2
     *
     * @param string $context View or edit context.
     * @return \WC_DateTime|null
     */
    public function get_date_created( $context = 'view' ) {
        return $this->get_prop( 'date_created', $context );
    }

    /**
     * Set date created.
     *
     * @since 4.1.2
     *
     * @param \WC_DateTime|null $date_created Date created.
     * @return void
     */
    public function set_date_created( ?\WC_DateTime $date_created ) {
        $this->set_prop( 'date_created', $date_created );
    }

    /**
     * Get date updated.
     *
     * @since 4.1.2
     *
     * @param string $context View or edit context.
     * @return \WC_DateTime|null
     */
    public function get_date_updated( $context = 'view' ) {
        return $this->get_prop( 'date_updated', $context );
    }

    /**
     * Set date updated.
     *
     * @since 4.1.2
     *
     * @param \WC_DateTime|null $date_updated Date updated.
     * @return void
     */
    public function set_date_updated( ?\WC_DateTime $date_updated ) {
        $this->set_prop( 'date_updated', $date_updated );
    }

    /**
     * Set the updated date for the entity.
     *
     * This method is protected to ensure that only internal code like `set_props` method
     * can set the updated date dynamically. External clients should use
     * the `set_date_created` and `set_date_updated` methods to manage dates semantically.
     *
     * @since 4.1.2
     *
     * @param \WC_DateTime|null $date_updated Date updated.
     * @return void
     */
    protected function set_updated_at( ?\WC_DateTime $date_updated ) {
        $this->set_date_updated( $date_updated );
    }

    /**
     * Get all valid statuses.
     *
     * @since 4.1.2
     *
     * @return array
     */
    public static function get_valid_statuses() {
        $statuses = [
            self::STATUS_OPEN,
            self::STATUS_CLOSED,
        ];

        return apply_filters( 'dokan_vendor_support_ticket_valid_statuses', $statuses );
    }

    /**
     * Get all valid priorities.
     *
     * @since 4.1.2
     *
     * @return array
     */
    public static function get_valid_priorities() {
        $priorities = [
            self::PRIORITY_LOW,
            self::PRIORITY_NORMAL,
            self::PRIORITY_HIGH,
        ];
        return apply_filters( 'dokan_vendor_support_ticket_valid_priorities', $priorities );
    }

    /**
     * Check if ticket is closed.
     *
     * @since 4.1.2
     *
     * @return bool
     */
    public function is_closed() {
        return self::STATUS_CLOSED === $this->get_status();
    }

    /**
     * Check if ticket is open.
     *
     * @since 4.1.2
     *
     * @return bool
     */
    public function is_open() {
        return self::STATUS_OPEN === $this->get_status();
    }

    /**
     * Check if ticket is pending.
     *
     * @since 4.1.2
     *
     * @return bool
     */
    public function is_pending() {
        if ( ! $this->is_open() ) {
            return false;
        }

        // Get last reply by from ticket data
        $last_reply_by = $this->get_last_reply_by();

        // If no last_reply_by is set, check the latest conversation to maintain backward compatibility
        if ( null === $last_reply_by && $this->get_id() ) {
            $latest_conversation = Conversation::get_latest_by_ticket( $this->get_id() );
            if ( $latest_conversation ) {
                $last_reply_by = $latest_conversation->get_sender_type() ?? null;
            }
        }

        return $last_reply_by === self::SENDER_VENDOR;
    }

    /**
     * Check if ticket is active.
     *
     * @since 4.1.2
     *
     * @return bool
     */
    public function is_active() {
        return $this->is_open();
    }

    /**
     * Get conversations for this ticket.
     *
     * @since 4.1.2
     *
     * @param array $args Query arguments.
     * @return array Array of Conversation objects.
     */
    public function get_conversations( array $args = [] ): array {
        if ( ! $this->get_id() ) {
            return [];
        }

        return Conversation::query( $this->get_id(), $args );
    }

    /**
     * Get conversations count for this ticket.
     *
     * @since 4.1.2
     *
     * @param array $args Query arguments.
     * @return int
     */
    public function get_conversations_count( array $args = [] ): int {
        if ( ! $this->get_id() ) {
            return 0;
        }

        return Conversation::count( $this->get_id(), $args );
    }

    /**
     * Add a conversation to this ticket.
     *
     * @since 4.1.2
     *
     * @param array $conversation_data Conversation data.
     * @return Conversation|false Conversation object on success, false on failure.
     */
    public function add_conversation( array $conversation_data ) {
        if ( ! $this->get_id() ) {
            return false;
        }

        // Ensure ticket_id is set
        $conversation_data['ticket_id'] = $this->get_id();

        // Create new conversation
        $conversation = new Conversation();
        $conversation->set_props( $conversation_data );

        $conversation_id = $conversation->save();

        if ( ! $conversation_id ) {
            return false;
        }

        // Update ticket data based on the new conversation
        $this->update_ticket_on_conversation_change( $conversation );

        return $conversation;
    }

    /**
     * Edit a conversation in this ticket.
     *
     * @since 4.1.2
     *
     * @param int   $conversation_id Conversation ID.
     * @param array $conversation_data Updated conversation data.
     * @return Conversation|false Conversation object on success, false on failure.
     */
    public function edit_conversation( int $conversation_id, array $conversation_data ) {
        if ( ! $this->get_id() || ! $conversation_id ) {
            return false;
        }

        $conversation = new Conversation( $conversation_id );

        if ( ! $conversation->get_id() || $conversation->get_ticket_id() !== $this->get_id() ) {
            return false;
        }

        // Update conversation data
        $conversation->set_props( $conversation_data );

        $updated_id = $conversation->save();

        if ( ! $updated_id ) {
            return false;
        }

        // Update ticket data if needed
        $this->update_ticket_on_conversation_change( $conversation );

        return $conversation;
    }

    /**
     * Delete a conversation from this ticket.
     *
     * @since 4.1.2
     *
     * @param int $conversation_id Conversation ID.
     * @return bool True on success, false on failure.
     */
    public function delete_conversation( int $conversation_id ): bool {
        if ( ! $this->get_id() || ! $conversation_id ) {
            return false;
        }

        $conversation = new Conversation( $conversation_id );

        if ( ! $conversation->get_id() || $conversation->get_ticket_id() !== $this->get_id() ) {
            return false;
        }

        $deleted = $conversation->delete();

        if ( $deleted ) {
            // Update ticket data after conversation deletion
            $this->update_ticket_after_conversation_deletion();
        }

        return $deleted;
    }

    /**
     * Delete all conversations for this ticket.
     *
     * @since 4.1.2
     *
     * @return int Number of conversations deleted.
     */
    public function delete_all_conversations(): int {
        if ( ! $this->get_id() ) {
            return 0;
        }

        $conversation_store = new \WeDevs\DokanPro\Modules\VendorSupport\Models\DataStore\ConversationStore();
        $deleted_count = $conversation_store->delete_conversations_by_ticket( $this->get_id() );

        if ( $deleted_count > 0 ) {
            // Reset ticket conversation-related data
            $this->set_last_reply_by( null );
            $this->set_last_reply_at( null );
            $this->set_is_read_by_admin( true );
            $this->set_is_read_by_vendor( true );
            $this->save();
        }

        return $deleted_count;
    }

    /**
     * Update ticket data when a conversation is added or edited.
     *
     * @since 4.1.2
     *
     * @param Conversation $conversation The conversation object.
     * @return void
     */
    protected function update_ticket_on_conversation_change( Conversation $conversation ): void {
        $current_status = $this->get_status();

        // Handle vendor conversation scenarios
        if ( $conversation->is_from_vendor() ) {
            // Scenario 1: Vendor adds conversation to open ticket → ticket remains open but is_pending() will return true
            // No status change needed - the is_pending() method will detect this based on last_reply_by

            // Scenario 2: Vendor adds conversation to closed ticket → should be opened
            if ( $current_status === self::STATUS_CLOSED ) {
                $this->set_status( self::STATUS_OPEN );
            }
        }

        // Update last reply information
        $this->set_last_reply_by( $conversation->get_sender_type() );
        $this->set_last_reply_at( $conversation->get_date_created() );

        // Update read status based on sender
        if ( $conversation->is_from_admin() ) {
            $this->set_is_read_by_admin( true );
            $this->set_is_read_by_vendor( false );
        } else {
            $this->set_is_read_by_admin( false );
            $this->set_is_read_by_vendor( true );
        }

        // Update the ticket's updated date (store as UTC timestamp)
        $now_ts = current_time( 'timestamp', true );
        $now_dt = new \WC_DateTime( "@{$now_ts}", new \DateTimeZone( 'UTC' ) );
        if ( get_option( 'timezone_string' ) ) {
            $now_dt->setTimezone( new \DateTimeZone( wc_timezone_string() ) );
        } else {
            $now_dt->set_utc_offset( wc_timezone_offset() );
        }
        $this->set_date_updated( $now_dt );

        // Save the ticket
        $this->save();
    }

    /**
     * Update ticket data after a conversation is deleted.
     *
     * @since 4.1.2
     *
     * @return void
     */
    protected function update_ticket_after_conversation_deletion(): void {
        // Get the latest conversation to update ticket data
        $latest_conversation = Conversation::get_latest_by_ticket( $this->get_id() );

        if ( $latest_conversation ) {
            $this->set_last_reply_by( $latest_conversation->get_sender_type() );
            $this->set_last_reply_at( $latest_conversation->get_date_created() );
        } else {
            // No conversations left
            $this->set_last_reply_by( null );
            $this->set_last_reply_at( null );
            $this->set_is_read_by_admin( true );
            $this->set_is_read_by_vendor( true );
        }

        // Update the ticket's updated date (store as UTC timestamp)
        $now_ts = current_time( 'timestamp', true );
        $now_dt = new \WC_DateTime( "@{$now_ts}", new \DateTimeZone( 'UTC' ) );
        if ( get_option( 'timezone_string' ) ) {
            $now_dt->setTimezone( new \DateTimeZone( wc_timezone_string() ) );
        } else {
            $now_dt->set_utc_offset( wc_timezone_offset() );
        }
        $this->set_date_updated( $now_dt );

        // Save the ticket
        $this->save();
    }

    /**
     * Get status logs for this ticket.
     *
     * @since 4.1.2
     *
     * @param array $args Query arguments.
     * @return array Array of StatusLog objects.
     */
    public function get_status_logs( array $args = [] ): array {
        if ( ! $this->get_id() ) {
            return [];
        }

        return StatusLog::query( $this->get_id(), $args );
    }

    /**
     * Get status logs count for this ticket.
     *
     * @since 4.1.2
     *
     * @param array $args Query arguments.
     * @return int
     */
    public function get_status_logs_count( array $args = [] ): int {
        if ( ! $this->get_id() ) {
            return 0;
        }

        return StatusLog::count( $this->get_id(), $args );
    }

    /**
     * Add a status log entry for this ticket.
     *
     * @since 4.1.2
     *
     * @param array $status_log_data Status log data.
     * @return StatusLog|false StatusLog object on success, false on failure.
     */
    public function add_status_log( array $status_log_data ) {
        if ( ! $this->get_id() ) {
            return false;
        }

        // Ensure ticket_id is set
        $status_log_data['ticket_id'] = $this->get_id();

        // Create new status log
        $status_log = new StatusLog();
        $status_log->set_props( $status_log_data );

        $status_log_id = $status_log->save();

        if ( ! $status_log_id ) {
            return false;
        }

        return $status_log;
    }

    /**
     * Delete all status logs for this ticket.
     *
     * @since 4.1.2
     *
     * @return int Number of deleted status logs.
     */
    public function delete_all_status_logs(): int {
        if ( ! $this->get_id() ) {
            return 0;
        }

        $status_log_store = new \WeDevs\DokanPro\Modules\VendorSupport\Models\DataStore\StatusLogStore();
        return $status_log_store->delete_status_logs_by_ticket( $this->get_id() );
    }

    /**
     * Create a status change log entry.
     *
     * @since 4.1.2
     *
     * @param string $old_status The old status.
     * @param string $new_status The new status.
     * @return StatusLog|false StatusLog object on success, false on failure.
     */
    protected function create_status_change_log( $old_status, $new_status ) {
        if ( ! $this->get_id() ) {
            return false;
        }

        // Determine who is making the change
        $changed_by_type = current_user_can( 'manage_options' ) ? self::SENDER_ADMIN : self::SENDER_VENDOR;
        $changed_by_id = get_current_user_id();

        // Create appropriate note based on status change
        $note = '';
        if ( $new_status === self::STATUS_CLOSED ) {
            $note = sprintf(
                // translators: %s: Event performer type (admin or vendor).
                esc_html__( 'Ticket closed by %s', 'dokan' ),
                $changed_by_type === self::SENDER_ADMIN ? __( 'admin', 'dokan' ) : __( 'vendor', 'dokan' )
            );
        } elseif ( $new_status === self::STATUS_OPEN && $old_status === self::STATUS_CLOSED ) {
            $note = sprintf(
                // translators: %s: Event performer type (admin or vendor).
                esc_html__( 'Ticket reopened by %s', 'dokan' ),
                $changed_by_type === self::SENDER_ADMIN ? __( 'admin', 'dokan' ) : __( 'vendor', 'dokan' )
            );
        } else {
            $note = sprintf(
                // translators: %1$s: Old status, %2$s: New status, %3$s: Event performer type (admin or vendor).
                esc_html__( 'Ticket status changed from %1$s to %2$s by %3$s', 'dokan' ),
                $old_status,
                $new_status,
                $changed_by_type === self::SENDER_ADMIN ? __( 'admin', 'dokan' ) : __( 'vendor', 'dokan' )
            );
        }

        $status_log_data = [
            'old_status'       => $old_status,
            'new_status'       => $new_status,
            'changed_by_type'  => $changed_by_type,
            'changed_by_id'    => $changed_by_id,
            'note'             => $note,
        ];

        return $this->add_status_log( $status_log_data );
    }

    /**
     * Query tickets by vendor ID.
     *
     * @since 4.1.2
     *
     * @param int   $vendor_id Vendor ID.
     * @param array $args      Query arguments.
     * @return array<Ticket>
     */
    public static function query( int $vendor_id, array $args = [] ): array {
        $cache_key = 'tickets_' . $vendor_id . '_' . md5( serialize( $args ) );
        $cached_raw_results = Cache::get( $cache_key, 'dokan_vendor_support_tickets' );

        if ( false !== $cached_raw_results ) {
            // Convert cached raw data to Ticket model instances
            $results = [];
            foreach ( $cached_raw_results as $raw_ticket ) {
                $results[] = new self( $raw_ticket['id'] );
            }
            return $results;
        }

        $store = new TicketStore();
        $raw_results = $store->get_tickets_by_vendor( $vendor_id, $args );

        // Cache raw results for 5 minutes
        Cache::set( $cache_key, $raw_results, 'dokan_vendor_support_tickets', 300 );

        // Convert raw data to Ticket model instances
        $results = [];
        foreach ( $raw_results as $raw_ticket ) {
            $results[] = new self( $raw_ticket['id'] );
        }

        return $results;
    }

    /**
     * Count tickets by vendor ID.
     *
     * @since 4.1.2
     *
     * @param int   $vendor_id Vendor ID.
     * @param array $args      Query arguments.
     * @return int
     */
    public static function count( int $vendor_id, array $args = [] ): int {
        $cache_key = 'tickets_count_' . $vendor_id . '_' . md5( serialize( $args ) );
        $cached_result = Cache::get( $cache_key, 'dokan_vendor_support_tickets' );

        if ( false !== $cached_result ) {
            return $cached_result;
        }

        $store = new TicketStore();
        $count = $store->get_tickets_count_by_vendor( $vendor_id, $args );

        // Cache for 5 minutes
        Cache::set( $cache_key, $count, 'dokan_vendor_support_tickets', 300 );

        return $count;
    }

    /**
     * Query all tickets.
     *
     * @since 4.1.2
     *
     * @param array $args Query arguments.
     * @return array<Ticket>
     */
    public static function query_all( array $args = [] ): array {
        $cache_key = 'dokan_vendor_support_all_tickets_' . md5( serialize( $args ) );
        $raw_results = Cache::get( $cache_key, 'dokan_vendor_support_tickets' );

        if ( false === $raw_results ) {
            $store = new TicketStore();
            $raw_results = $store->get_all_tickets( $args );

            // Cache raw results for 5 minutes
            Cache::set( $cache_key, $raw_results, 'dokan_vendor_support_tickets', 300 );
        }

        // Convert raw data to Ticket model instances
        $results = [];
        foreach ( $raw_results as $raw_ticket ) {
            $results[] = new self( $raw_ticket['id'] );
        }

        return $results;
    }

    /**
     * Count all tickets.
     *
     * @since 4.1.2
     *
     * @param array $args Query arguments.
     * @return int
     */
    public static function count_all( array $args = [] ): int {
        $cache_key = 'dokan_vendor_support_all_tickets__count_' . md5( serialize( $args ) );
        $cached_result = Cache::get( $cache_key, 'dokan_vendor_support_tickets' );

        if ( false !== $cached_result ) {
            return $cached_result;
        }

        $store = new TicketStore();
        $count = $store->get_all_tickets_count( $args );

        // Cache for 5 minutes
        Cache::set( $cache_key, $count, 'dokan_vendor_support_tickets', 300 );

        return $count;
    }

    /**
     * Clear cache for tickets.
     *
     * @since 4.1.2
     *
     * @param int|null $vendor_id Optional vendor ID to clear specific vendor cache.
     * @return void
     */
    public static function clear_cache( ?int $vendor_id = null ): void {
        // Clear all vendor support ticket cache
        Cache::invalidate_group( 'dokan_vendor_support_tickets' );
    }
}
