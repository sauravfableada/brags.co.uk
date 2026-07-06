<?php

namespace WeDevs\DokanPro\Modules\Printful\Providers;

use Exception;
use WeDevs\DokanPro\Dependencies\Printful\Exceptions\PrintfulException;
use WeDevs\DokanPro\Dependencies\Printful\PrintfulApiClient;
use WeDevs\DokanPro\Dependencies\Printful\PrintfulWebhook;
use WeDevs\DokanPro\Dependencies\Printful\Structures\Webhook\WebhookItem;
use WeDevs\DokanPro\Modules\Printful\Auth;
use WeDevs\DokanPro\Modules\Printful\EventsHandler;
use WeDevs\DokanPro\Modules\Printful\Processors\PrintfulProductProcessor;

/**
 * Webhook Service Provider.
 *
 * @since 3.13.0
 */
class WebhookProvider {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'woocommerce_api_dokan_printful', [ $this, 'event_listener' ] );
        add_action( 'dokan_pro_printful_product_processor', [ $this, 'process_product' ], 10, 4 );
    }

    /**
     * Register webhook to Printful.
     *
     * @since 3.13.0
     *
     * @return bool
     */
    public static function register(): bool {
        $vendor_id = dokan_get_current_user_id();

        try {
            $webhook = self::get_webhook_handler( $vendor_id );

            /**
             * Actions before URL translation in Dokan for WPML compatibility.
             *
             * @since 3.13.0
             */
            do_action( 'dokan_disable_url_translation' );

            $url = add_query_arg(
                [
                    'vendor_id' => $vendor_id,
                ],
                home_url( 'wc-api/dokan_printful' )
            );

            /**
             * Actions after URL translation is re-enabled in Dokan for WPML compatibility.
             *
             * @since 3.13.0
             */
            do_action( 'dokan_enable_url_translation' );

            /**
             * Filter the events that will be registered with the Printful webhook.
             *
             * @since 3.13.0
             *
             * @param int $vendor_id Vendor ID.
             */
            $events = apply_filters(
                'dokan_pro_printful_webhook_events',
                [
                    'package_shipped',
                    'package_returned',
                    'order_created',
                    'order_updated',
                    'order_failed',
                    'order_canceled',
                    'product_updated',
                    'product_deleted',
                    'order_put_hold',
                    'order_put_hold_approval',
                    'order_remove_hold',
                ],
                $vendor_id
            );

            $webhook->registerWebhooks( $url, $events );
        } catch ( Exception $e ) {
            // translators: %d Vendor ID, %s Error Message.
            dokan_log( sprintf( esc_html__( 'Printful Webhook Register failed for Vendor %1$d, Error: %2$s', 'dokan' ), $vendor_id, $e->getMessage() ), 'error' );
            return false;
        }

        return true;
    }

    /**
     * Deregister webhook to Printful.
     *
     * @since 3.13.0
     *
     * @return bool
     */
    public static function deregister(): bool {
        $vendor_id = dokan_get_current_user_id();

        try {
            $webhook = self::get_webhook_handler( $vendor_id );
            $webhook->disableWebhooks();
        } catch ( Exception $e ) {
            // translators: %d Vendor ID, %s Error Message.
            dokan_log( sprintf( esc_html__( 'Printful Webhook Deregister failed for Vendor %1$d, Error: %2$s', 'dokan' ), $vendor_id, $e->getMessage() ), 'error' );
            return false;
        }

        return true;
    }

    /**
     * Get pre-configured webhook handler.
     *
     * @since 3.13.0
     *
     * @param int $vendor_id Vendor ID.
     *
     * @return PrintfulWebhook
     * @throws PrintfulException|Exception If vendor not found or vendor is not connected or Printful error.
     */
    public static function get_webhook_handler( int $vendor_id = 0 ): PrintfulWebhook {
        $vendor_id = ! $vendor_id ? dokan_get_current_user_id() : $vendor_id;

        if ( ! $vendor_id ) {
            throw new Exception( esc_html__( 'Vendor ID Missing.', 'dokan' ) );
        }

        $auth = new Auth( $vendor_id );

        if ( ! $auth->is_connected() ) {
            throw new Exception( esc_html__( 'Vendor is not connected to Printful store.', 'dokan' ) );
        }

        $client = PrintfulApiClient::createOauthClient( $auth->get_access_token() );

        return new PrintfulWebhook( $client );
    }

    /**
     * Printful Event listener.
     *
     * @since 3.13.0
     *
     * @return void
     */
    public function event_listener() {
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
        if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
            return;
        }

        $request_body = file_get_contents( 'php://input' );
        $event        = json_decode( $request_body, true );

        if ( empty( $event['store'] ) ) {
            return;
        }

        try {
            $parsed_event = WebhookItem::fromArray( $event );
            EventsHandler::handle( $parsed_event );

            status_header( 200 );
            exit;
        } catch ( Exception $e ) {
            dokan_log( 'Printful Webhook Processing Error (Event ): ' . $e->getMessage(), 'error' );
            status_header( 400 );
            exit;
        }
    }

    /**
     * Process product.
     *
     * @since 3.13.0
     *
     * @param int $printful_product_id Printful Product ID.
     * @param int $product_id Product ID.
     * @param int $vendor_id Vendor ID.
     * @param string $previous_status Previous product status.
     *
     * @return void
     */
    public function process_product( $printful_product_id, $product_id, $vendor_id, $previous_status ) {
        $printful_product_processor = new PrintfulProductProcessor( $vendor_id );

        $printful_product_processor->process( $printful_product_id, $product_id, $previous_status );
    }
}
