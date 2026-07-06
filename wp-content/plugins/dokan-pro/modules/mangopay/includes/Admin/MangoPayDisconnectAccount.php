<?php

namespace WeDevs\DokanPro\Modules\MangoPay\Admin;

use WeDevs\DokanPro\Modules\MangoPay\Emails\MangoPayDisconnectNotification;
use WeDevs\DokanPro\Modules\MangoPay\Support\Meta;
use WeDevs\DokanPro\Modules\MangoPay\Support\Helper;

class MangoPayDisconnectAccount {

    /**
     * Initializes and calls all hooks
     *
     * @since 3.11.2
     */
    public function __construct() {
        add_action( 'init', array( $this, 'init_hooks' ) );
    }

    public function init_hooks() {
        // Register immediately
        add_action( 'dokan_generate_mangopay_vendor_disconnect_queue', [ self::class, 'disconnect_vendors' ], 10, 1 );
        add_action( 'dokan_mangopay_vendor_disconnect_queue', [ self::class, 'disconnect_vendor' ], 10, 3 );
    }

    public static function start_disconnect_queue( $offset = 0 ) {
        $query_limit = 10;

        // Get only vendors who have MangoPay accounts
        $vendors = dokan()->vendor->all(
            [
                'fields'     => 'ID',
                'status'     => [ 'approved' ],
                'number'     => $query_limit,
                'offset'     => $offset,
                'meta_query' => [
                    [
                        'relation' => 'OR',
                        [
                            'key'     => Meta::mangopay_meta_key() . '_trash',
                            'compare' => 'EXISTS',
                        ],
                        [
                            'key'     => Meta::mangopay_meta_key(),
                            'compare' => 'EXISTS',
                        ],
                    ],
                ],
            ]
        );

        if ( empty( $vendors ) ) {
            return;
        }

        foreach ( $vendors as $vendor_id ) {
            // Add to queue with proper error handling
            try {
                $disconnect_queue_params = array(
                    'user_id' => $vendor_id,
                    'force'   => true,
                    'reason'  => 'credentials_changed',
                );
                WC()->queue()->add(
                    'dokan_mangopay_vendor_disconnect_queue',
                    $disconnect_queue_params,
                    'dokan'
                );
            } catch ( \Exception $e ) {
                Helper::log( "Failed to queue vendor {$vendor_id} for disconnection: " . $e->getMessage() );
            }
        }

        // Get total connected vendors

        if ( $offset + $query_limit < dokan()->vendor->get_total() ) {
            try {
                WC()->queue()->add(
                    'dokan_generate_mangopay_vendor_disconnect_queue',
                    [ 'offset' => $offset + $query_limit ],
                    'dokan'
                );
            } catch ( \Exception $e ) {
                Helper::log( 'Failed to queue next batch: ' . $e->getMessage() );
            }
        }
    }


    public static function disconnect_vendors( $offset = 0 ) {
        dokan_log( 'Starting MangoPay vendor disconnection process with offset: ' . $offset );
        self::start_disconnect_queue( $offset );
    }

    /**
     * Disconnect individual vendor from MangoPay
     *
     * @since 4.0.7
     *
     * @param int    $user_id The user ID of the vendor
     * @param bool   $force   Whether to force disconnection
     * @param string $reason  Reason for disconnection
     *
     * @return void
     */
    public static function disconnect_vendor( $user_id, $force = true, $reason = 'disconnect' ) {
		$delete_from_account = Meta::delete_mangopay_account_id( $user_id, $force );
		$delete_from_trash   = Meta::delete_trashed_mangopay_account_id( $user_id );

        $log_message = "Vendor($user_id) disconnected from MangoPay" . ( $reason ? " - Reason: $reason" : '' );
        Helper::log( $log_message );

        // Consider implementing vendor email notification
        // Send email notification
        $is_deleted = $delete_from_account || $delete_from_trash;
        $vendor = dokan()->vendor->get( $user_id );
        if ( $vendor && $vendor->get_email() && $is_deleted ) {
            $email = WC()->mailer()->emails['Dokan_MangoPay_Disconnect_Notification'];
            if ( $email instanceof MangoPayDisconnectNotification ) {
                try {
                    $email->trigger( $vendor, $reason );
                    Helper::log( "Disconnection email sent to vendor {$user_id}" );
                } catch ( \Exception $e ) {
                    Helper::log( "Failed to send disconnection email to vendor {$user_id}: " . $e->getMessage(), 'error' );
                }
            } else {
                Helper::log( "MangoPay disconnect email class not found for vendor {$user_id}", 'error' );
            }
        }
    }
}
