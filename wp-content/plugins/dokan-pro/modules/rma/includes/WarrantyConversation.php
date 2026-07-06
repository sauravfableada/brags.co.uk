<?php

namespace WeDevs\DokanPro\Modules\RMA;

use WeDevs\DokanPro\Modules\RMA\Traits\RMACommon;
use WP_Error;

/**
* Warranty request related conversation
*
* @since 1.0.0
*
* @package dokan
*/
class WarrantyConversation {

    use RMACommon;

    protected string $table_name;

    /**
     * Construct functions
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function __construct() {
        global $wpdb;

        $this->table_name = $wpdb->prefix . 'dokan_rma_conversations';
    }

    /**
     * Insert conversation data to database
     *
     * @since 1.0.0
     *
     * @param array $data Conversation data
     *
     * @return int|WP_Error
     */
    public function insert( array $data = [] ) {
        global $wpdb;

        $default = [
            'request_id' => 0,
            'from'       => 0,
            'to'         => 0,
            'message'    => '',
            'created_at' => current_time( 'mysql' ),
        ];

        $data = dokan_parse_args( $data, $default );

        $conversation = $wpdb->insert(
            $this->table_name,
            [
                'request_id' => $data['request_id'],
                'from'       => $data['from'],
                'to'         => $data['to'],
                'message'    => $data['message'],
                'created_at' => $data['created_at'],
            ],
            [ '%d', '%d', '%d', '%s', '%s' ]
        );

        $conversation_id = $wpdb->insert_id;

        if ( ! $conversation ) {
            return new WP_Error( 'not-inserted', __( 'Conversation to saved', 'dokan' ) );
        }

        /**
         * Action hook to run after a conversation is created
         *
         * @since 1.0.0
         *
         * @param int $conversation_id Saved conversation id
         * @param array $data Conversation data
         */
        do_action( 'dokan_pro_rma_conversion_created', $conversation_id, $data );

        return $conversation_id;
    }

    /**
     * Get conversations
     *
     * @since 1.0.0
     *
     * @param array $data Conversation data
     *
     * @return array|WP_Error
     */
    public function get( array $data = [] ) {
        global $wpdb;

        $default = [
            'request_id' => 0,
            'from'       => 0,
            'to'         => 0,
        ];

        $data          = dokan_parse_args( $data, $default );
        $conversations = [];

        if ( empty( $data['request_id'] ) ) {
            return new WP_Error( 'no-request-id', __( 'No request id found', 'dokan' ) );
        }

        $request_id = $data['request_id'];
        $results = $wpdb->get_results( "SELECT * FROM {$this->table_name} WHERE `request_id`='$request_id'", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL

        foreach ( $results as $result ) {
            $conversations[] = $this->transform_request_conversation( $result );
        }

        return $conversations;
    }

    /**
     * Get single conversation
     *
     * @since 4.0.0
     *
     * @param int $id Conversation ID
     *
     * @return array|WP_Error
     */
    public function get_single( int $id ) {
        global $wpdb;

        $conversation = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE id = %d", $id ), // phpcs:ignore WordPress.DB.PreparedSQL
            ARRAY_A
        );

        if ( ! $conversation ) {
            return new WP_Error( 'not-found', __( 'Conversation not found', 'dokan' ), [ 'status' => 404 ] );
        }

        return $this->transform_request_conversation( $conversation );
    }
}
