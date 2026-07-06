<?php

namespace WeDevs\DokanPro\Modules\PayPalMarketplace\WebhookEvents;

use DokanPro\Modules\Subscription\OrderRenew;
use WC_Order_Query;
use WeDevs\DokanPro\Modules\PayPalMarketplace\Abstracts\WebhookEventHandler;
use DokanPro\Modules\Subscription\Helper as SubscriptionHelper;
use WeDevs\DokanPro\Modules\PayPalMarketplace\Helper;
use WeDevs\DokanPro\Modules\Subscription\SubscriptionOrderMetaBuilder;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Class PaymentSaleCompleted
 * @package WeDevs\DokanPro\Payment\PayPal\WebhookEvents
 *
 * @since 3.3.7
 *
 * @author weDevs
 */
class PaymentSaleCompleted extends WebhookEventHandler {

    /**
     * CheckoutOrderApproved constructor.
     *
     * @param $event
     *
     * @since 3.3.7
     */
    public function __construct( $event ) {
        $this->set_event( $event );
    }

    /**
     * Handle payment sale
     *
     * @since 3.3.7
     *
     * @return void
     */
    public function handle() {
        try {
            // this hook is used for subscription payment only
            $event           = $this->get_event();
            $subscription_id = sanitize_text_field( $event->resource->billing_agreement_id );
            $order_id        = sanitize_text_field( $event->resource->custom );
            $state           = sanitize_text_field( $event->resource->state );

            // validate subscription status
            if ( 'completed' !== $state ) {
                return;
            }

            // check if vendor subscription module is active
            if ( ! Helper::has_vendor_subscription_module() ) {
                return;
            }

            // validate order
            $subscription = wc_get_order( $order_id );
            if ( ! $subscription ) {
                dokan_log( '[Dokan PayPal Marketplace] Webhook: PaymentSaleCompleted, Invalid Order id: ' . $order_id ); // maybe deleted order
                return;
            }

            // check payment gateway used was dokan paypal marketplace
            if ( $subscription->get_payment_method() !== Helper::get_gateway_id() ) {
                return;
            }

            // check if order is vendor subscription order
            if ( $subscription->get_meta( '_dokan_vendor_subscription_order' ) !== 'yes' ) {
                return;
            }

            // make sure subscription id match with stored subscription id
            $order_subscription_id = $subscription->get_meta( '_dokan_paypal_marketplace_vendor_subscription_id' );
            if ( empty( $order_subscription_id ) || $order_subscription_id !== $subscription_id ) {
                return;
            }

            //get vendor id
            $vendor_id = $subscription->get_customer_id( 'edit' );

            // get required data for webhook event
            $paypal_fee_data                = $event->resource->transaction_fee;
            $paypal_processing_fee_currency = $paypal_fee_data->currency;
            $paypal_processing_fee          = $paypal_fee_data->value;
            $paypal_transaction_id          = $event->resource->id;
            $paypal_renewal_order_total     = $event->resource->amount->total;

            // check if this is a renewal
            $is_renewal    = ! empty( $subscription->get_meta( '_dokan_paypal_payment_capture_id' ) );
            $renewal_order = null;

            if ( $is_renewal ) {
                // check if transaction already recorded
                $orders = wc_get_orders(
                    [
                        'customer_id'        => $subscription->get_customer_id(),
                        'limit'              => 1,
                        'orderby'            => 'date',
                        'order'              => 'DESC',
                        'return'             => 'ids',
                        'meta_query'         => [
                            [
                                'key'     => '_dokan_stripe_payment_capture_id',
                                'value'   => $paypal_transaction_id,
                                'compare' => '=',
                            ],
                        ],
                    ]
                );

                if ( ! empty( $orders ) ) {
                    // transaction is already recorded
                    $subscription->payment_complete( $paypal_transaction_id );
                    return;
                }

                // create new renewal order
                $renewal_order = SubscriptionHelper::create_renewal_order( $subscription, $paypal_renewal_order_total );

                if ( is_wp_error( $renewal_order ) ) {
                    dokan_log( '[PayPal Marketplace] Create Renewal Order Failed. Error: ' . $renewal_order->get_error_message() );
                    return;
                }

                // translators: %s: order number.
                $order_number = sprintf( _x( '#%s', 'hash before order number', 'dokan' ), $renewal_order->get_order_number() );
                // translators: %s: order number.
                $subscription_order_number = sprintf( _x( '#%s', 'hash before order number', 'dokan' ), $subscription->get_order_number() );
                // translators: placeholder is order ID
                $subscription->add_order_note( sprintf( __( 'Order %s created to record renewal.', 'dokan' ), sprintf( '<a href="%s">%s</a> ', esc_url( SubscriptionHelper::get_edit_post_link( $renewal_order->get_id() ) ), $order_number ) ) );
                // add order note on renewal order
                // translators: 1) subscription order number
                $renewal_order->add_order_note( sprintf( __( 'Order created to record renewal subscription for %s.', 'dokan' ), sprintf( '<a href="%s">%s</a> ', esc_url( SubscriptionHelper::get_edit_post_link( $subscription->get_id() ) ), $subscription_order_number ) ) );
                // set subscription to renewal order
                $subscription = $renewal_order;
            }

            $subscription->add_order_note(
                /* translators: %s: paypal processing fee */
                sprintf( __( 'PayPal processing fee is %s', 'dokan' ), $paypal_processing_fee )
            );

            $subscription->set_payment_method( Helper::get_gateway_id() );

            // Store Subscription Order Metadata.
            $meta_builder = new SubscriptionOrderMetaBuilder( $subscription, 'paypal' );
            $meta_builder->set_capture_id( $paypal_transaction_id )
                ->set_processing_fee( $paypal_processing_fee )
                ->set_processing_currency( $paypal_processing_fee_currency )
                ->set_gateway_fee( $paypal_processing_fee )
                ->set_gateway_fee_paid_by( 'admin' )
                ->set_shipping_fee_recipient( 'admin' )
                ->set_tax_fee_recipient( 'admin' )
                ->set_is_vendor_subscription_order( 'yes')
                ->build()
                ->save();

            $test_mode = Helper::is_test_mode() ? __( 'PayPal Sandbox Transaction ID', 'dokan' ) : __( 'PayPal Transaction ID', 'dokan' );
            $subscription->add_order_note(
                sprintf(
                    '%1$s: %2$s',
                    $test_mode,
                    $paypal_transaction_id
                )
            );

            $subscription->payment_complete( $paypal_transaction_id );

            // set can_post_product to 1
            update_user_meta( $vendor_id, 'can_post_product', '1' );

            // delete trail metas
            delete_user_meta( $vendor_id, '_dokan_subscription_is_on_trial' );
            delete_user_meta( $vendor_id, '_dokan_subscription_trial_until' );
        } catch ( \Exception $e ) {
            dokan_log( "[Dokan PayPal Marketplace] PaymentSaleCompleted Error:\n" . print_r( $e->getMessage(), true ), 'error' );
        }
    }
}
