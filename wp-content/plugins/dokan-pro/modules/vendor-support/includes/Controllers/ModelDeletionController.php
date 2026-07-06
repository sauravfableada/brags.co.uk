<?php

namespace WeDevs\DokanPro\Modules\VendorSupport\Controllers;

use WeDevs\Dokan\Contracts\Hookable;
use WeDevs\DokanPro\Modules\VendorSupport\Models\DataStore\ConversationStore;
use WeDevs\DokanPro\Modules\VendorSupport\Models\DataStore\StatusLogStore;

defined( 'ABSPATH' ) || exit;

/**
 * Handles model deletion side effects for the Vendor Support module.
 *
 * - Listens to ticket delete events and deletes related conversations and status logs.
 *
 * @since 4.1.2
 */
class ModelDeletionController implements Hookable {

    /**
     * Register hooks
     */
    public function register_hooks(): void {
        // Fired from WeDevs\Dokan\Models\DataStore\BaseDataStore::delete_by_id
        // Table name for tickets is 'dokan_vendor_support_tickets' (without $wpdb prefix here),
        // so hook becomes: dokan_dokan_vendor_support_tickets_deleted
        add_action( 'dokan_dokan_vendor_support_tickets_deleted', [ $this, 'on_ticket_deleted' ], 10, 2 );
    }

    /**
     * Callback for when a ticket is deleted.
     *
     * @param int $ticket_id Deleted ticket ID.
     * @param int $affected_rows Number of rows affected in the tickets table.
     *
     * @return void
     */
    public function on_ticket_deleted( int $ticket_id, int $affected_rows ): void {
        if ( empty( $ticket_id ) ) {
            return;
        }

        try {
            // Delete related conversations
            $conversation_store = new ConversationStore();
            $conversation_store->delete_conversations_by_ticket( $ticket_id );

            // Delete related status logs
            $status_log_store = new StatusLogStore();
            $status_log_store->delete_status_logs_by_ticket( $ticket_id );
        } catch ( \Throwable $e ) {
            // Fail silently but allow developers to hook into errors if necessary
            do_action( 'dokan_vendor_support_ticket_related_delete_failed', $ticket_id, $e );
        }

        /**
         * Action fired after all related vendor support records are deleted for a ticket.
         *
         * @param int $ticket_id
         * @param int $affected_rows
         */
        do_action( 'dokan_vendor_support_ticket_related_deleted', $ticket_id, $affected_rows );
    }
}
