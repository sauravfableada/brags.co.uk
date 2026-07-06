<?php

namespace WeDevs\DokanPro\Modules\VendorSupport\Models;

use WC_DateTime;
use WeDevs\Dokan\Cache;
use WeDevs\Dokan\Models\BaseModel;
use WeDevs\DokanPro\Modules\VendorSupport\Models\DataStore\ConversationStore;

defined( 'ABSPATH' ) || exit;

/**
 * Class Conversation
 *
 * Represents a conversation/reply in a vendor support ticket.
 *
 * @since 4.1.2
 *
 * @package WeDevs\DokanPro\Modules\VendorSupport\Models
 */
class Conversation extends BaseModel {

    /**
     * Object type.
     *
     * @since 4.1.2
     *
     * @var string
     */
    protected $object_type = 'vendor_support_conversation';

    protected $cache_group = 'dokan_vendor_support_conversations';

    /**
     * Sender types.
     *
     * @since 4.1.2
     */
    const SENDER_ADMIN = 'admin';
    const SENDER_VENDOR = 'vendor';

    /**
     * Default data for the conversation.
     *
     * @since 4.1.2
     *
     * @var array
     */
    protected $data = [
        'ticket_id'    => 0,
        'message'      => '',
        'sender_type'  => self::SENDER_VENDOR,
        'sender_id'    => 0,
        'is_read'      => false,
        'date_created' => null,
    ];

    /**
     * Initialize the data store.
     *
     * @since 4.1.2
     *
     * @param int $conversation_id Conversation ID.
     */
    public function __construct( int $conversation_id = 0 ) {
        parent::__construct( $conversation_id );
        $this->data_store = new ConversationStore();

        if ( $conversation_id > 0 ) {
            $this->set_id( $conversation_id );
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
     * Get message.
     *
     * @since 4.1.2
     *
     * @param string $context View or edit context.
     * @return string
     */
    public function get_message( $context = 'view' ) {
        return $this->get_prop( 'message', $context );
    }

    /**
     * Set message.
     *
     * @since 4.1.2
     *
     * @param string $message Message content.
     * @return void
     */
    public function set_message( $message ) {
        $this->set_prop( 'message', wp_kses_post( $message ) );
    }

    /**
     * Get sender type.
     *
     * @since 4.1.2
     *
     * @param string $context View or edit context.
     * @return string
     */
    public function get_sender_type( $context = 'view' ) {
        return $this->get_prop( 'sender_type', $context );
    }

    /**
     * Set sender type.
     *
     * @since 4.1.2
     *
     * @param string $sender_type Sender type (admin or vendor).
     * @return void
     */
    public function set_sender_type( $sender_type ) {
        $valid_types = [ self::SENDER_ADMIN, self::SENDER_VENDOR ];
        if ( in_array( $sender_type, $valid_types, true ) ) {
            $this->set_prop( 'sender_type', $sender_type );
        }
    }

    /**
     * Get sender ID.
     *
     * @since 4.1.2
     *
     * @param string $context View or edit context.
     * @return int
     */
    public function get_sender_id( $context = 'view' ) {
        return $this->get_prop( 'sender_id', $context );
    }

    /**
     * Set sender ID.
     *
     * @since 4.1.2
     *
     * @param int $sender_id Sender ID (admin user ID or vendor ID).
     * @return void
     */
    public function set_sender_id( $sender_id ) {
        $this->set_prop( 'sender_id', absint( $sender_id ) );
    }

    /**
     * Get is read.
     *
     * @since 4.1.2
     *
     * @param string $context View or edit context.
     * @return bool
     */
    public function get_is_read( $context = 'view' ) {
        return $this->get_prop( 'is_read', $context );
    }

    /**
     * Set is read.
     *
     * @since 4.1.2
     *
     * @param bool $is_read Whether the message has been read.
     * @return void
     */
    public function set_is_read( bool $is_read ) {
        $this->set_prop( 'is_read', $is_read );
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
     * Get all valid sender types.
     *
     * @since 4.1.2
     *
     * @return array
     */
    public static function get_valid_sender_types() {
        return [
            self::SENDER_ADMIN,
            self::SENDER_VENDOR,
        ];
    }

    /**
     * Check if sender is admin.
     *
     * @since 4.1.2
     *
     * @return bool
     */
    public function is_from_admin() {
        return self::SENDER_ADMIN === $this->get_sender_type();
    }

    /**
     * Check if sender is vendor.
     *
     * @since 4.1.2
     *
     * @return bool
     */
    public function is_from_vendor() {
        return self::SENDER_VENDOR === $this->get_sender_type();
    }

    /**
     * Check if message is read.
     *
     * @since 4.1.2
     *
     * @return bool
     */
    public function is_read() {
        return $this->get_is_read();
    }

    /**
     * Mark message as read.
     *
     * @since 4.1.2
     *
     * @return void
     */
    public function mark_as_read() {
        $this->set_is_read( true );
    }

    /**
     * Mark message as unread.
     *
     * @since 4.1.2
     *
     * @return void
     */
    public function mark_as_unread() {
        $this->set_is_read( false );
    }

    /**
     * Get sender name.
     *
     * @since 4.1.2
     *
     * @return string
     */
    public function get_sender_name() {
        if ( $this->is_from_admin() ) {
            $user = get_user_by( 'id', $this->get_sender_id() );
            return $user ? $user->display_name : __( 'Admin', 'dokan' );
        }

        if ( $this->is_from_vendor() ) {
            $vendor = dokan()->vendor->get( $this->get_sender_id() );
            return $vendor->get_shop_name();
        }

        return __( 'Unknown', 'dokan' );
    }

    /**
     * Get sender email.
     *
     * @since 4.1.2
     *
     * @return string
     */
    public function get_sender_email() {
        if ( $this->is_from_admin() ) {
            $user = get_user_by( 'id', $this->get_sender_id() );
            return $user ? $user->user_email : '';
        }

        if ( $this->is_from_vendor() ) {
            $vendor = dokan()->vendor->get( $this->get_sender_id() );
            return $vendor->get_email();
        }

        return '';
    }

    /**
     * Get formatted message content.
     *
     * @since 4.1.2
     *
     * @return string
     */
    public function get_formatted_message() {
        $message = $this->get_message();
        return wpautop( $message );
    }

    /**
     * Get message excerpt.
     *
     * @since 4.1.2
     *
     * @param int $length Excerpt length in characters.
     * @return string
     */
    public function get_message_excerpt( $length = 100 ) {
        $message = wp_strip_all_tags( $this->get_message() );
        if ( strlen( $message ) <= $length ) {
            return $message;
        }
        return substr( $message, 0, $length );
    }

    /**
     * Query conversations by ticket ID.
     *
     * @since 4.1.2
     *
     * @param int   $ticket_id Ticket ID.
     * @param array $args      Query arguments.
     * @return array<Conversation>
     */
    public static function query( int $ticket_id, array $args = [] ): array {
        $cache_key = 'dokan_vendor_support_ticket_conversation_query_' . $ticket_id . '_' . md5( maybe_serialize( $args ) );
        $raw_results = Cache::get( $cache_key, 'dokan_vendor_support_conversations' );

        if ( false === $raw_results ) {
            // Convert cached raw data to Conversation model instances
            $store = new ConversationStore();
            $raw_results = $store->get_conversations_by_ticket( $ticket_id, $args );

            // Cache raw results for 5 minutes
            Cache::set( $cache_key, $raw_results, 'dokan_vendor_support_conversations', 300 );
        }

        // Convert raw data to Conversation model instances
        $results = [];
        foreach ( $raw_results as $raw_conversation ) {
            $results[] = new self( $raw_conversation['id'] );
        }

        return $results;
    }

    /**
     * Count conversations by ticket ID.
     *
     * @since 4.1.2
     *
     * @param int   $ticket_id Ticket ID.
     * @param array $args      Query arguments.
     * @return int
     */
    public static function count( int $ticket_id, array $args = [] ): int {
        $cache_key = 'dokan_vendor_support_ticket_conversation_count_' . $ticket_id . '_' . md5( maybe_serialize( $args ) );
        $cached_result = Cache::get( $cache_key, 'dokan_vendor_support_conversations' );

        if ( false !== $cached_result ) {
            return $cached_result;
        }

        $store = new ConversationStore();
        $count = $store->get_conversations_count_by_ticket( $ticket_id, $args );

        // Cache for 5 minutes
        Cache::set( $cache_key, $count, 'dokan_vendor_support_conversations', 300 );

        return $count;
    }

    /**
     * Query conversations by sender.
     *
     * @since 4.1.2
     *
     * @param string $sender_type Sender type (admin or vendor).
     * @param int    $sender_id   Sender ID.
     * @param array  $args        Query arguments.
     * @return array<Conversation>
     */
    public static function query_by_sender( string $sender_type, int $sender_id, array $args = [] ): array {
        $cache_key = 'dokan_vendor_support_ticket_conversation_sender_' . $sender_type . '_' . $sender_id . '_' . md5( maybe_serialize( $args ) );
        $raw_results = Cache::get( $cache_key, 'dokan_vendor_support_conversations' );

        if ( false === $raw_results ) {
            $store = new ConversationStore();
            $raw_results = $store->get_conversations_by_sender( $sender_type, $sender_id, $args );

            // Cache raw results for 5 minutes
            Cache::set( $cache_key, $raw_results, 'dokan_vendor_support_conversations', 300 );
        }

        // Convert raw data to Conversation model instances
        $results = [];
        foreach ( $raw_results as $raw_conversation ) {
            $results[] = new self( $raw_conversation['id'] );
        }

        return $results;
    }

    /**
     * Get latest conversation by ticket ID.
     *
     * @since 4.1.2
     *
     * @param int $ticket_id Ticket ID.
     *
     * @return Conversation|null
     */
    public static function get_latest_by_ticket( int $ticket_id ) {
        $cache_key = 'dokan_vendor_support_ticket_latest_conversation_' . $ticket_id;
        $cached_result = Cache::get( $cache_key, 'dokan_vendor_support_conversations' );

        if ( false === $cached_result ) {
            $store = new ConversationStore();
            $result = $store->get_latest_conversation_by_ticket( $ticket_id );

            // Cache for 5 minutes
            Cache::set( $cache_key, $result, 'dokan_vendor_support_conversations', 300 );
        }

        if ( ! $result ) {
            return null;
        }

        // Convert raw data to Conversation model instance
        return new self( $result['id'] );
    }

    /**
     * Get unread count by ticket and recipient.
     *
     * @since 4.1.2
     *
     * @param int    $ticket_id      Ticket ID.
     * @param string $recipient_type Recipient type (admin or vendor).
     * @return int
     */
    public static function get_unread_count( int $ticket_id, string $recipient_type ): int {
        $cache_key = 'dokan_vendor_support_ticket_unread_count_' . $ticket_id . '_' . $recipient_type;
        $cached_result = Cache::get( $cache_key, 'dokan_vendor_support_conversations' );

        if ( false !== $cached_result ) {
            return $cached_result;
        }

        $store = new ConversationStore();
        $count = $store->get_unread_count_by_ticket_and_recipient( $ticket_id, $recipient_type );

        // Cache for 5 minutes
        Cache::set( $cache_key, $count, 'dokan_vendor_support_conversations', 300 );

        return $count;
    }

    /**
     * Get total unread count for recipient.
     *
     * @since 4.1.2
     *
     * @param string $recipient_type Recipient type (admin or vendor).
     * @param int    $recipient_id   Recipient ID.
     * @return int
     */
    public static function get_total_unread_count( string $recipient_type, int $recipient_id ): int {
        $cache_key = 'dokan_vendor_support_ticket_total_unread_' . $recipient_type . '_' . $recipient_id;
        $cached_result = Cache::get( $cache_key, 'dokan_vendor_support_conversations' );

        if ( false !== $cached_result ) {
            return $cached_result;
        }

        $store = new ConversationStore();
        $count = $store->get_total_unread_count_for_recipient( $recipient_type, $recipient_id );

        // Cache for 5 minutes
        Cache::set( $cache_key, $count, 'dokan_vendor_support_conversations', 300 );

        return $count;
    }

    /**
     * Mark conversations as read by ticket and recipient.
     *
     * @since 4.1.2
     *
     * @param int    $ticket_id      Ticket ID.
     * @param string $recipient_type Recipient type (admin or vendor).
     * @return int Number of affected rows.
     */
    public static function mark_as_read_by_ticket( int $ticket_id, string $recipient_type ): int {
        $store = new ConversationStore();
        $result = $store->mark_as_read_by_ticket_and_recipient( $ticket_id, $recipient_type );

        // Clear related cache
        static::clear_cache( $ticket_id );

        return $result;
    }

    /**
     * Clear cache for conversations.
     *
     * @since 4.1.2
     *
     * @param int|null $ticket_id Optional ticket ID to clear specific ticket cache.
     * @return void
     */
    public static function clear_cache( ?int $ticket_id = null ): void {
        // Clear all vendor support conversation cache
        Cache::invalidate_group( 'dokan_vendor_support_conversations' );
    }
}
