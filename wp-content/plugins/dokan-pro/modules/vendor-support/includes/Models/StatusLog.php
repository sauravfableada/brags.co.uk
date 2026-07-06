<?php

namespace WeDevs\DokanPro\Modules\VendorSupport\Models;

use WC_DateTime;
use WeDevs\Dokan\Cache;
use WeDevs\Dokan\Models\BaseModel;
use WeDevs\DokanPro\Modules\VendorSupport\Models\DataStore\StatusLogStore;

defined( 'ABSPATH' ) || exit;

/**
 * Class StatusLog
 *
 * Represents a status change log entry for a vendor support ticket.
 *
 * @since 4.1.2
 *
 * @package WeDevs\DokanPro\Modules\VendorSupport\Models
 */
class StatusLog extends BaseModel {

    /**
     * Object type.
     *
     * @since 4.1.2
     *
     * @var string
     */
    protected $object_type = 'vendor_support_status_log';

    protected $cache_group = 'dokan_vendor_support_status_logs';

    /**
     * Changed by types.
     *
     * @since 4.1.2
     */
    const CHANGED_BY_ADMIN = 'admin';
    const CHANGED_BY_VENDOR = 'vendor';

    /**
     * Default data for the status log.
     *
     * @since 4.1.2
     *
     * @var array
     */
    protected $data = [
        'ticket_id'       => 0,
        'old_status'      => '',
        'new_status'      => '',
        'changed_by_type' => self::CHANGED_BY_ADMIN,
        'changed_by_id'   => 0,
        'note'            => '',
        'date_created'    => null,
    ];

    /**
     * Initialize the data store.
     *
     * @since 4.1.2
     *
     * @param int $status_log_id Status log ID.
     */
    public function __construct( int $status_log_id = 0 ) {
        parent::__construct( $status_log_id );
        $this->data_store = new StatusLogStore();

        if ( $status_log_id > 0 ) {
            $this->set_id( $status_log_id );
        }

        if ( $this->get_id() > 0 ) {
            $this->data_store->read( $this );
        }
    }

    /**
     * Get ticket ID.
     *
     * @since 4.1.2
     *
     * @param string $context View or edit context.
     * @return int
     */
    public function get_ticket_id( $context = 'view' ) {
        return $this->get_prop( 'ticket_id', $context );
    }

    /**
     * Set ticket ID.
     *
     * @since 4.1.2
     *
     * @param int $ticket_id Ticket ID.
     * @return void
     */
    public function set_ticket_id( $ticket_id ) {
        $this->set_prop( 'ticket_id', absint( $ticket_id ) );
    }

    /**
     * Get old status.
     *
     * @since 4.1.2
     *
     * @param string $context View or edit context.
     * @return string
     */
    public function get_old_status( $context = 'view' ) {
        return $this->get_prop( 'old_status', $context );
    }

    /**
     * Set old status.
     *
     * @since 4.1.2
     *
     * @param string $old_status Old status.
     * @return void
     */
    public function set_old_status( $old_status ) {
        $this->set_prop( 'old_status', $old_status );
    }

    /**
     * Get new status.
     *
     * @since 4.1.2
     *
     * @param string $context View or edit context.
     * @return string
     */
    public function get_new_status( $context = 'view' ) {
        return $this->get_prop( 'new_status', $context );
    }

    /**
     * Set new status.
     *
     * @since 4.1.2
     *
     * @param string $new_status New status.
     * @return void
     */
    public function set_new_status( $new_status ) {
        $this->set_prop( 'new_status', $new_status );
    }

    /**
     * Get changed by type.
     *
     * @since 4.1.2
     *
     * @param string $context View or edit context.
     * @return string
     */
    public function get_changed_by_type( $context = 'view' ) {
        return $this->get_prop( 'changed_by_type', $context );
    }

    /**
     * Set changed by type.
     *
     * @since 4.1.2
     *
     * @param string $changed_by_type Changed by type.
     * @return void
     */
    public function set_changed_by_type( $changed_by_type ) {
        $valid_types = $this->get_valid_changed_by_types();
        if ( in_array( $changed_by_type, $valid_types, true ) ) {
            $this->set_prop( 'changed_by_type', $changed_by_type );
        }
    }

    /**
     * Get changed by ID.
     *
     * @since 4.1.2
     *
     * @param string $context View or edit context.
     * @return int
     */
    public function get_changed_by_id( $context = 'view' ) {
        return $this->get_prop( 'changed_by_id', $context );
    }

    /**
     * Set changed by ID.
     *
     * @since 4.1.2
     *
     * @param int $changed_by_id Changed by ID.
     * @return void
     */
    public function set_changed_by_id( $changed_by_id ) {
        $this->set_prop( 'changed_by_id', absint( $changed_by_id ) );
    }

    /**
     * Get note.
     *
     * @since 4.1.2
     *
     * @param string $context View or edit context.
     * @return string
     */
    public function get_note( $context = 'view' ) {
        return $this->get_prop( 'note', $context );
    }

    /**
     * Set note.
     *
     * @since 4.1.2
     *
     * @param string $note Note.
     * @return void
     */
    public function set_note( $note ) {
        $this->set_prop( 'note', $note );
    }

    /**
     * Get date created.
     *
     * @since 4.1.2
     *
     * @param string $context View or edit context.
     * @return WC_DateTime|null
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
     * Get valid changed by types.
     *
     * @since 4.1.2
     *
     * @return array
     */
    public function get_valid_changed_by_types() {
        return [
            self::CHANGED_BY_ADMIN,
            self::CHANGED_BY_VENDOR,
        ];
    }

    /**
     * Check if the status was changed by admin.
     *
     * @since 4.1.2
     *
     * @return bool
     */
    public function is_changed_by_admin() {
        return self::CHANGED_BY_ADMIN === $this->get_changed_by_type();
    }

    /**
     * Check if the status was changed by vendor.
     *
     * @since 4.1.2
     *
     * @return bool
     */
    public function is_changed_by_vendor() {
        return self::CHANGED_BY_VENDOR === $this->get_changed_by_type();
    }

    /**
     * Get the name of the person who changed the status.
     *
     * @since 4.1.2
     *
     * @return string
     */
    public function get_changed_by_name() {
        $changed_by_id = $this->get_changed_by_id();
        if ( ! $changed_by_id ) {
            return '';
        }

        $user = get_user_by( 'id', $changed_by_id );
        return $user ? $user->display_name : '';
    }

    /**
     * Get the email of the person who changed the status.
     *
     * @since 4.1.2
     *
     * @return string
     */
    public function get_changed_by_email() {
        $changed_by_id = $this->get_changed_by_id();
        if ( ! $changed_by_id ) {
            return '';
        }

        $user = get_user_by( 'id', $changed_by_id );
        return $user ? $user->user_email : '';
    }

    /**
     * Query status logs for a specific ticket.
     *
     * @since 4.1.2
     *
     * @param int   $ticket_id Ticket ID.
     * @param array $args      Query arguments.
     * @return array Array of StatusLog objects.
     */
    public static function query( int $ticket_id, array $args = [] ): array {
        if ( ! $ticket_id ) {
            return [];
        }

        $data_store = new StatusLogStore();
        $status_log_ids = $data_store->query( $ticket_id, $args );

        $status_logs = [];
        foreach ( $status_log_ids as $status_log_id ) {
            $status_logs[] = new self( $status_log_id );
        }

        return $status_logs;
    }

    /**
     * Count status logs for a specific ticket.
     *
     * @since 4.1.2
     *
     * @param int   $ticket_id Ticket ID.
     * @param array $args      Query arguments.
     * @return int
     */
    public static function count( int $ticket_id, array $args = [] ): int {
        if ( ! $ticket_id ) {
            return 0;
        }

        $data_store = new StatusLogStore();
        return $data_store->count( $ticket_id, $args );
    }

    /**
     * Clear cache for status logs.
     *
     * @since 4.1.2
     *
     * @param int $ticket_id Ticket ID.
     * @return void
     */
    public static function clear_cache( int $ticket_id ): void {
        Cache::delete( "status_logs_ticket_{$ticket_id}", 'dokan_vendor_support_status_logs' );
    }
}
