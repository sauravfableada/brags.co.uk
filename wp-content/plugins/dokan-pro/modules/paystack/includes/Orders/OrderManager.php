<?php

namespace WeDevs\DokanPro\Modules\Paystack\Orders;

use Exception;
use WC_Order;
use WeDevs\DokanPro\Modules\Paystack\Api\SplitPayment;
use WeDevs\DokanPro\Modules\Paystack\Support\Helper;

class OrderManager {

    /**
     * Prepare split payment data for the order.
     *
     * @since 4.1.1
     */
    public static function prepare_split_payment_data( WC_Order $order ): array {
        // If the order has suborders, process each suborder
        $orders = $order->get_meta( 'has_sub_order' )
	        ? dokan()->order->get_child_orders( $order )
            : [ $order ];

        $subaccounts = [];

        foreach ( $orders as $suborder ) {
            $vendor_id              = dokan_get_seller_id_by_order( $suborder );
            $vendor_earning         = dokan()->commission->get_vendor_earning_subtotal_by_order( $suborder );
            $connected_vendor_id    = Helper::get_seller_account_id( $vendor_id );
            $order_total            = $suborder->get_total();

            if ( $order_total <= 0 ) {
                /* translators: 1: Order Number */
                $suborder->add_order_note( sprintf( __( 'Order %s payment completed.', 'dokan' ), $suborder->get_order_number() ) );
                continue;
            }

            if ( ! $connected_vendor_id ) {
                // If no connected vendor account, transfer to an admin account
                $suborder->add_order_note( __( 'Vendor payment will be transferred to the admin account since the vendor had not connected to Paystack.', 'dokan' ) );
                continue;
            }

            $subaccount = get_user_meta( $vendor_id, Helper::get_seller_account_id_key(), true );
            if ( ! $subaccount ) {
                // translators: 1: Vendor ID
                $suborder->add_order_note( sprintf( __( 'No Paystack account connected for vendor %s.', 'dokan' ), $vendor_id ) );
                continue;
            }
            // Prepare subaccount data
            $subaccounts[] = [
                'subaccount' => $subaccount,
                'share'     => Helper::format_paystack_amount( $vendor_earning ),
			];
        }

		/*
		 * Determine the bearer configuration for the split payment.
		 *
		 * If the gateway fee is paid by the seller:
		 * - Use 'all-proportional' for multiple subaccounts (fees distributed proportionally)
		 * - Use 'subaccount' for single subaccount (specific subaccount bears all fees)
		 * Otherwise, use 'account' as bearer_type to make the main account bear the fees.
		 *
		 * @see https://paystack.com/docs/payments/multi-split-payments/#fees-on-multi-split
		 */
	    $bearer_type = Helper::seller_pays_gateway_fee() ? 'all-proportional' : 'account';

		return [
			'name' => sprintf(
				/* translators: %s: Order ID */
				__( 'Order #%s', 'dokan' ),
				$order->get_id()
			),
			'type'         => 'flat',
			'bearer_type'  => $bearer_type,
			'subaccounts'  => $subaccounts,
			'currency'     => $order->get_currency(),
		];
    }

    /**
     * Prepare payment arguments for the order.
     *
     * @since 4.1.1
     *
     * @param WC_Order $order WooCommerce order object
     *
     * @return array
     */
    public static function payment_args( WC_Order $order ): array {
        $amount         = Helper::format_paystack_amount( $order->get_total() );
        $reference      = Helper::generate_reference( $order );
        $callback_url   = self::get_callback_url( $order, $reference );
        // update the metadata for the order
        $order->update_meta_data( '_dokan_paystack_reference', $reference );
        $order->update_meta_data( '_dokan_paystack_callback_url', $callback_url );
        // prepare the payment arguments
        $args = [
            'key' => Helper::get_public_api_key(),
			'email' => $order->get_billing_email(),
			'amount' => $amount,
			'currency' => $order->get_currency(),
			'reference' => $reference,
			'metadata' => self::prepare_metadata( $order ),
            'callback_url' => $callback_url,
		];
        return apply_filters( 'dokan_paystack_payment_args', $args, $order );
    }

    /**
     * Get the callback URL for the order.
     *
     * @since 5.0.1
     *
     * @param WC_Order $order WooCommerce order object
     * @param string $reference The unique reference for the order
     *
     * @return string
     */
    public static function get_callback_url( WC_Order $order, string $reference ): string {
        return add_query_arg(
            [
                'order_id' => $order->get_id(),
                'paystack_txnref' => $reference,
                'wc_payment_method' => Helper::get_gateway_id(),
            ], Helper::get_return_url( $order )
        );
    }

	/**
	 * Prepare metadata for the order.
	 *
	 * @since 4.1.1
	 *
	 * @param WC_Order $order WooCommerce order object
	 *
	 * @return array
	 */
	public static function prepare_metadata( WC_Order $order ): array {
        $billing_address = esc_html( preg_replace( '#<br\s*/?>#i', ', ', $order->get_formatted_billing_address() ) );
		$shipping_address = esc_html( preg_replace( '#<br\s*/?>#i', ', ', $order->get_formatted_shipping_address() ) );
		if ( empty( $shipping_address ) ) {
			$shipping_address = $billing_address;
		}

		$order->save();

		$line_items = $order->get_items();
		$products   = array_map(
            function ( $item ) {
                return sprintf(
                    '%s (Qty: %d)',
                    $item->get_name(),
                    $item->get_quantity()
                );
            },
            $line_items
		);

		$products = implode( ' | ', $products );

        $metadata = [
			'custom_fields' => [
				[
					'display_name' => __( 'Order ID', 'dokan' ),
					'variable_name' => 'order_id',
					'value' => $order->get_id(),
				],
				[
					'display_name' => __( 'Platform Fee', 'dokan' ),
					'variable_name' => 'platform_fee',
					'value' => self::get_total_admin_commission( $order ),
				],
				[
					'display_name' => __( 'Customer Name', 'dokan' ),
					'variable_name' => 'customer_name',
					'value' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
				],
				[
					'display_name' => __( 'Customer Email', 'dokan' ),
					'variable_name' => 'customer_email',
					'value' => $order->get_billing_email(),
				],
				[
					'display_name' => __( 'Shipping Address', 'dokan' ),
					'variable_name' => 'shipping_address',
					'value' => $shipping_address,
				],
				[
					'display_name' => __( 'Products', 'dokan' ),
					'variable_name' => 'products',
					'value' => $products,
				],
			],
			'cancel_action' => wc_get_cart_url(),
        ];
        $subsidy_meta = self::add_admin_subsidy_meta( $order );
        $metadata['custom_fields'] = array_merge( $metadata['custom_fields'], $subsidy_meta );
        return apply_filters( 'dokan_paystack_payment_metadata', $metadata, $order );
    }

    /**
     * Maybe split payment for the order.
     *
     * @param array $args
     * @param WC_Order $order
     *
     * @return array|Exception
     * @throws Exception
     */
    public static function maybe_split_payment( array $args, WC_Order $order ) {
        // check the order is eligible for split payment
        if ( Helper::should_skip_payment_split( $order ) ) {
            return $args;
        }

        try {
            $split_payment = self::prepare_split_payment_data( $order );
            $split = SplitPayment::create( $split_payment );
            $args['split_code'] = $split['data']['split_code'];
            return $args;
        } catch ( Exception $e ) {
            return $args;
        }
    }

    /**
     * Distribute Paystack total fees proportionally to subaccounts based on their amounts.
     * ---------------------------------------------------------------------
     * PURPOSE:
     * ---------------------------------------------------------------------
     * Paystack's transaction "split" response provides:
     *   - The total fee Paystack charges for the transaction (`split.shares.paystack`)
     *   - Each subaccount's share amount and original fees
     *
     * However, the original per-subaccount fees might not be proportional to the subaccount's
     * transaction amount. This function recalculates the per-subaccount fees proportionally,
     * so that:
     *   1. Each subaccount's `adjusted_fees` is proportional to its `amount`.
     *   2. The sum of all `adjusted_fees` exactly equals `split.shares.paystack`.
     *   3. The difference between original and adjusted fees is tracked for reconciliation.
     *
     * @params array $split The Paystack split response containing fee details.
     * @params string $seller_account_code The subaccount code for the seller.
     *
     * @since 4.1.1
     * @returns array|false An associative array containing:
     *
     * @see https://paystack.com/docs/payments/multi-split-payments/#fees-on-multi-split
     */
	public static function get_distributed_fees( array $split, string $seller_account_code ) {
		try {
            $paystack_fee_total = $split['shares']['paystack'];
            $subaccounts = $split['shares']['subaccounts'];
            $sub_account_key = array_search( $seller_account_code, array_column( $subaccounts, 'subaccount_code' ), true );
            $subaccount = $subaccounts[ $sub_account_key ] ?? null;
            if ( ! $subaccount ) {
                // If the subaccount is not found, return an empty array
                return false;
            }
            // Step 1: Calculate total transaction amount across all subaccounts
            $total_amount = array_sum( array_column( $subaccounts, 'amount' ) );

            // Step 2: Calculate proportional fee for each subaccount
            // If total amount is zero, proportion becomes 0 to avoid division by zero
            $proportion = $total_amount > 0 ? ( $subaccount['amount'] / $total_amount ) : 0;

            // Adjusted fee = proportion of total paystack fee
            $adjusted_fee = $proportion * $paystack_fee_total;

            // Difference from original fee
            $admin_fee = $adjusted_fee - $subaccount['fees'];

            return [
                'amount'        => $subaccount['amount'],
                'vendor_fee'    => wc_format_decimal( $subaccount['fees'] ),
                'admin_fee'     => wc_format_decimal( $admin_fee ),
            ];
        } catch ( Exception $e ) {
            // Log the error if unable to distribute fees
            dokan_log( sprintf( 'Error distributing fees for seller account %s: %s', $seller_account_code, $e->getMessage() ) );
            return false;
        }
	}

	public static function get_total_admin_commission( WC_Order $order ) {
		if ( ! $order->get_meta( 'has_sub_order' ) ) {
            return wc_format_decimal( dokan()->commission->get_earning_by_order( $order, 'admin' ), 2 );
        }

        $orders = dokan()->order->get_child_orders( $order );

        $total_commission = 0;
        foreach ( $orders as $sub_order ) {
            $total_commission += wc_format_decimal( dokan()->commission->get_earning_by_order( $sub_order, 'admin' ), 2 );
        }

        return $total_commission;
	}

    /**
     * Add admin commission subsidy meta to the payment metadata.
     *
     * @param WC_Order $order
     *
     * @since 4.1.1
     *
     * @return array
     */
    public static function add_admin_subsidy_meta( WC_Order $order ): array {
        // Check if the order has suborders
        $orders = dokan()->commission->get_all_order_to_be_processed( $order );

        $subsidy_meta = [];
        foreach ( $orders as $sub_order ) {
            $admin_commission = dokan()->commission->get_earning_by_order( $sub_order, 'admin' );
            if ( $admin_commission >= 0 ) {
                continue; // Skip if admin commission is not negative
            }
            $subsidy_meta[] = [
                // translators: %s: Order id
				'display_name' => sprintf( __( 'Marketplace Admin — please provide the subsidy amount for Order #%d', 'dokan' ), $sub_order->get_id() ),
				'variable_name' => 'admin_commission_subsidy_' . $sub_order->get_id(),
				'value' => wc_format_decimal( abs( $admin_commission ), 2 ),
			];

            // add order note for negative admin commission like vendor collect the fee from marketplace admin
            $order->add_order_note(
                sprintf(
                    // translators: %s: Commission, %s: Order ID
                    __( 'Subsidy granted: %1$s for Order #%2$d. This amount will be collected by the vendor from the marketplace admin.', 'dokan' ),
                    wc_format_decimal( abs( $admin_commission ), 2 ),
                    $sub_order->get_id()
                )
            );
        }
        return $subsidy_meta;
    }
}
