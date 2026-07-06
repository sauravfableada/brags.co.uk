<?php

namespace WeDevs\DokanPro\Refund;

use Exception;
use WP_Error;
use WC_Order;
use WC_Order_Refund;
use WeDevs\Dokan\Abstracts\DokanModel;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Refund extends DokanModel {

    /**
     * The model data
     *
     * @since 3.0.0
     *
     * @var array
     */
    protected $data = [];

    /**
     * Class constructor
     *
     * @since 3.0.0
     *
     * @param array $data
     */
    public function __construct( $data = [] ) {
        $defaults = [
            'id'              => 0,
            'order_id'        => 0,
            'seller_id'       => 0,
            'refund_amount'   => 0,
            'refund_reason'   => '',
            'item_qtys'       => null,
            'item_totals'     => null,
            'item_tax_totals' => null,
            'restock_items'   => null,
            'date'            => current_time( 'mysql' ),
            'status'          => 0,
            'method'          => '0',
        ];

        $data = wp_parse_args( $data, $defaults );

        $this->set_data( $data );
    }

    /**
     * Set model data
     *
     * @since 3.0.0
     *
     * @param array $data
     *
     * @return void
     */
    protected function set_data( $data ) {
        $data = wp_unslash( $data );

        $this->set_id( $data['id'] )
            ->set_order_id( $data['order_id'] )
            ->set_seller_id( $data['seller_id'] )
            ->set_refund_amount( $data['refund_amount'] )
            ->set_refund_reason( $data['refund_reason'] )
            ->set_item_qtys( $data['item_qtys'] )
            ->set_item_totals( $data['item_totals'] )
            ->set_item_tax_totals( $data['item_tax_totals'] )
            ->set_restock_items( $data['restock_items'] )
            ->set_date( $data['date'] )
            ->set_status( $data['status'] )
            ->set_method( $data['method'] );
    }

    /**
     * Set `id` property
     *
     * @since 3.0.0
     *
     * @param int $id
     *
     * @return Refund
     */
    public function set_id( $id ) {
        $this->data['id'] = $id;
        return $this;
    }

    /**
     * Set `order_id` property
     *
     * @since 3.0.0
     *
     * @param int $order_id
     *
     * @return Refund
     */
    public function set_order_id( $order_id ) {
        $this->data['order_id'] = $order_id;
        return $this;
    }

    /**
     * Set `seller_id` property
     *
     * @since 3.0.0
     *
     * @param int $seller_id
     *
     * @return Refund
     */
    public function set_seller_id( $seller_id ) {
        $this->data['seller_id'] = $seller_id;
        return $this;
    }

    /**
     * Set `refund_amount` property
     *
     * @since 3.0.0
     *
     * @param string $refund_amount
     *
     * @return Refund
     */
    public function set_refund_amount( $refund_amount ) {
        $this->data['refund_amount'] = $refund_amount;
        return $this;
    }

    /**
     * Set `refund_reason` property
     *
     * @since 3.0.0
     *
     * @param string $refund_reason
     *
     * @return Refund
     */
    public function set_refund_reason( $refund_reason ) {
        $this->data['refund_reason'] = $refund_reason;
        return $this;
    }

    /**
     * Set `item_qtys` property
     *
     * @since 3.0.0
     *
     * @param array $item_qtys
     *
     * @return Refund
     */
    public function set_item_qtys( $item_qtys ) {
        $this->data['item_qtys'] = $item_qtys;
        return $this;
    }

    /**
     * Set `item_totals` property
     *
     * @since 3.0.0
     *
     * @param array $item_totals
     *
     * @return Refund
     */
    public function set_item_totals( $item_totals ) {
        $this->data['item_totals'] = $item_totals;
        return $this;
    }

    /**
     * Set `item_tax_totals` property
     *
     * @since 3.0.0
     *
     * @param array $item_tax_totals
     *
     * @return Refund
     */
    public function set_item_tax_totals( $item_tax_totals ) {
        $this->data['item_tax_totals'] = $item_tax_totals;
        return $this;
    }

    /**
     * Set `restock_items` property
     *
     * @since 3.0.0
     *
     * @param array $restock_items
     *
     * @return Refund
     */
    public function set_restock_items( $restock_items ) {
        $this->data['restock_items'] = $restock_items;
        return $this;
    }

    /**
     * Set `date` property
     *
     * @since 3.0.0
     *
     * @param string $set_date
     *
     * @return Refund
     */
    public function set_date( $date ) {
        $this->data['date'] = $date;
        return $this;
    }

    /**
     * Set `status` property
     *
     * @since 3.0.0
     *
     * @param string $set_status
     *
     * @return Refund
     */
    public function set_status( $status ) {
        $this->data['status'] = $status;
        return $this;
    }

    /**
     * Set `method` property
     *
     * @since 3.0.0
     *
     * @param string $set_method
     *
     * @return Refund
     */
    public function set_method( $method ) {
        $this->data['method'] = $method;
        return $this;
    }

    /**
     * Get `id` property
     *
     * @since 3.0.0
     *
     * @return int
     */
    public function get_id() {
        return $this->data['id'];
    }

    /**
     * Get `order_id` property
     *
     * @since 3.0.0
     *
     * @return int
     */
    public function get_order_id() {
        return $this->data['order_id'];
    }

    /**
     * Get `seller_id` property
     *
     * @since 3.0.0
     *
     * @return int
     */
    public function get_seller_id() {
        return $this->data['seller_id'];
    }

    /**
     * Get `refund_amount` property
     *
     * @since 3.0.0
     *
     * @return string
     */
    public function get_refund_amount() {
        return $this->data['refund_amount'];
    }

    /**
     * Get `refund_reason` property
     *
     * @since 3.0.0
     *
     * @return string
     */
    public function get_refund_reason() {
        return $this->data['refund_reason'];
    }

    /**
     * Get `item_qtys` property
     *
     * @since 3.0.0
     *
     * @return array
     */
    public function get_item_qtys() {
        return $this->data['item_qtys'];
    }

    /**
     * Get `item_totals` property
     *
     * @since 3.0.0
     *
     * @return array
     */
    public function get_item_totals() {
        return $this->data['item_totals'];
    }

    /**
     * Get `item_tax_totals` property
     *
     * @since 3.0.0
     *
     * @return array
     */
    public function get_item_tax_totals() {
        return $this->data['item_tax_totals'];
    }

    /**
     * Get `restock_items` property
     *
     * @since 3.0.0
     *
     * @return array
     */
    public function get_restock_items() {
        return $this->data['restock_items'];
    }

    /**
     * Get `date` property
     *
     * @since 3.0.0
     *
     * @return string
     */
    public function get_date() {
        return $this->data['date'];
    }

    /**
     * Get `status` property
     *
     * @since 3.0.0
     *
     * @return string
     */
    public function get_status() {
        return $this->data['status'];
    }

    /**
     * Get `status_name` property
     *
     * @since 3.0.0
     *
     * @return string
     */
    public function get_status_name() {
        $status_name = dokan_pro()->refund->get_status_names();
        return $status_name[ $this->get_status() ];
    }

    /**
     * Get `method` property
     *
     * @since 3.0.0
     *
     * @return string
     */
    public function get_method() {
        return $this->data['method'];
    }

    /**
     * Prepare model for DB insertion
     *
     * @since 3.0.0
     * @since 3.4.2 Refund method changed to `1` for API, `0` for manual.
     *
     * @return array
     */
    protected function prepare_for_db() {
        $data = $this->get_data();

        $data['item_qtys']       = is_array( $data['item_qtys'] ) ? wp_json_encode( $data['item_qtys'] ) : null;
        $data['item_totals']     = is_array( $data['item_totals'] ) ? wp_json_encode( $data['item_totals'] ) : null;
        $data['item_tax_totals'] = is_array( $data['item_tax_totals'] ) ? wp_json_encode( $data['item_tax_totals'] ) : null;

        // we are setting WC provided method `true` or `false` to `1` or `0`
        $data['method'] = dokan_validate_boolean( $data['method'] ) ? '1' : '0';

        return $data;
    }

    /**
     * Save a model
     *
     * @since 3.0.0
     *
     * @return Refund
     */
    public function save() {
        if ( ! $this->get_id() ) {
            return $this->create();
        } else {
            return $this->update();
        }
    }

    /**
     * Create a model
     *
     * @since 3.0.0
     *
     * @return Refund|WP_Error
     */
    protected function create() {
        global $wpdb;

        unset( $this->data['id'] );

        $data = $this->prepare_for_db();

        $inserted = $wpdb->insert(
            $wpdb->dokan_refund,
            $data,
            [ '%d', '%d', '%f', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s' ]
        );

        if ( $inserted !== 1 ) {
            return new WP_Error( 'dokan_refund_create_error', __( 'Could not create new refund', 'dokan' ) );
        }

        $refund = dokan_pro()->refund->get( $wpdb->insert_id );

        /**
         * Fires after created a refund request
         *
         * @since 3.0.0
         *
         * @param Refund $refund
         */
        do_action( 'dokan_refund_request_created', $refund );

        return $refund;
    }

    /**
     * Update a model
     *
     * @since 3.0.0
     *
     * @return Refund|WP_Error
     */
    protected function update() {
        global $wpdb;

        $data = $this->prepare_for_db();

        $updated = $wpdb->update(
            $wpdb->dokan_refund,
            [
                'order_id'        => $data['order_id'],
                'seller_id'       => $data['seller_id'],
                'refund_amount'   => $data['refund_amount'],
                'refund_reason'   => $data['refund_reason'],
                'item_qtys'       => $data['item_qtys'],
                'item_totals'     => $data['item_totals'],
                'item_tax_totals' => $data['item_tax_totals'],
                'restock_items'   => $data['restock_items'],
                'date'            => $data['date'],
                'status'          => $data['status'],
                'method'          => $data['method'],

            ],
            [ 'id' => $this->get_id() ],
            [ '%d', '%d', '%f', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s' ],
            [ '%d' ]
        );

        if ( false === $updated ) {
            return new WP_Error( 'dokan_refund_update_error', __( 'Could not update refund', 'dokan' ) );
        }

        /**
         * Action based on refund status
         *
         * @since 3.0.0
         *
         * @param Refund $this
         */
        do_action( 'dokan_refund_request_' . dokan_pro()->refund->get_status_name( $this->get_status() ), $this );

        /**
         * Fires after update a refund
         *
         * @since 3.0.0
         *
         * @param Refund $this
         */
        do_action( 'dokan_refund_updated', $this );

        return $this;
    }

    /**
     * Delete a model
     *
     * @since 3.0.0
     *
     * @return Refund|WP_Error
     */
    public function delete() {
        global $wpdb;

        $deleted = $wpdb->delete(
            $wpdb->dokan_refund,
            [ 'id' => $this->data['id'] ],
            [ '%d' ]
        );

        if ( ! $deleted ) {
            return new WP_Error( 'dokan_pro_refund_error_delete', __( 'Could not delete refund request', 'dokan' ) );
        }

        /**
         * Fires after delete a refund
         *
         * @since 3.0.0
         *
         * @param Refund $this
         */
        do_action( 'dokan_pro_refund_deleted', $this );

        return $this;
    }

    /**
     * Approve a refund
     *
     * @since 3.0.0
     *
     * @param array $args
     *
     * @throws Exception
     *
     * @return Refund|WP_Error
     */
	public function approve( $args = [] ) {
		$order = wc_get_order( $this->get_order_id() );

		// Validate refund can be approved
		$validation_result = $this->validate_refund_approval( $order );

		if ( is_wp_error( $validation_result ) ) {
			return $validation_result;
		}

		// Prepare refund parameters
		$refund_params = $this->prepare_refund_params( $order );

		// Process the refund through WooCommerce
		$refund = $this->process_wc_refund( $refund_params );

		if ( is_wp_error( $refund ) ) {
			$this->cancel();
			return new WP_Error( 'dokan_pro_refund_error_processing', __( 'This refund failed to process.', 'dokan' ) );
		}

		// Handle parent order refund if this is a sub-order
		if ( dokan_is_sub_order( $order->get_id() ) ) {
			$parent_refund_result = $this->process_parent_order_refund( $order, $refund_params );
			if ( is_wp_error( $parent_refund_result ) ) {
				$refund->delete();
				$this->cancel();
				return new WP_Error( 'dokan_pro_refund_error_processing', __( 'This refund failed to process.', 'dokan' ) );
			}
		}

        // add vendor refund id to current refund
        $args['refund_order_id'] = $refund->get_id();

		// Add refund note to the order
		$this->add_refund_note( $order, $refund, $refund_params['api_refund'] );

		// Calculate vendor refund amount
		$vendor_refund = apply_filters( 'dokan_vendor_earning_in_refund', $refund, $order );

		/**
		 * @since 3.3.0 add filter dokan_refund_approve_vendor_refund_amount
		 *
		 * @param float $vendor_refund vendor refund amount
		 * @param Refund $this
		 * @param array $args
		 */
		$vendor_refund = apply_filters( 'dokan_refund_approve_vendor_refund_amount', $vendor_refund, $args, $this );

		/**
		 * @since 3.3.0
		 *
		 * @param Refund $this
		 * @param array $args
		 * @param float $vendor_refund
		 */
		do_action( 'dokan_refund_approve_before_insert', $this, $args, $vendor_refund );

        // Update vendor balance
        $this->update_vendor_balance( $vendor_refund, $args, $refund, $order );

		// Update order table with new refund amount
		$this->update_order_amounts( $vendor_refund, $refund, $order );

		// Add approval note
		$approved_by = $this->get_approver_name();
		$order->add_order_note(
            sprintf(
                // translators: %1$s is the name of the user who approved the refund.
                __( 'Refund request approved by %1$s', 'dokan' ),
                $approved_by
            )
		);

		// Update refund status and save
		$this->set_status( dokan_pro()->refund->get_status_code( 'completed' ) );
		$refund_result = $this->save();

		if ( is_wp_error( $refund_result ) ) {
			return $refund_result;
		}

		/**
		 * Fires after approve a refund request
		 *
		 * @since 3.0.0
		 * @since 3.3.0 added $args and $vendor_refund param
		 *
		 * @param Refund $refund
		 * @param array $args
		 * @param float $vendor_refund
		 */
		do_action( 'dokan_pro_refund_approved', $this, $args, $vendor_refund );

		return $this;
	}

	/**
	 * Validate if refund can be approved
	 *
     * @param \WC_Order $order
     *
	 * @return true|WP_Error
	 */
	private function validate_refund_approval( $order ) {
		if ( ! dokan_pro()->refund->is_approvable( $this->get_order_id() ) ) {
			return new WP_Error( 'dokan_pro_refund_error_approve', __( 'This refund is not allowed to approve', 'dokan' ) );
		}

		if ( ! $order ) {
			return new WP_Error( 'dokan_pro_refund_error_approve', __( 'Could not find order', 'dokan' ) );
		}

		return true;
	}

	/**
	 * Prepare parameters for refund processing
	 *
	 * @param WC_Order $order
	 * @return array
	 */
	private function prepare_refund_params( $order ) {
		$api_refund = dokan_validate_boolean( $this->get_method() );
		$restock_refunded_items = dokan_validate_boolean( $this->get_restock_items() );

		// Prepare line items for refund
		$line_items = $this->prepare_line_items( $order );

		/**
		 * Set auto process API refund for the main order.
		 *
		 * @since 3.3.7
		 *
		 * @param bool $api_refund
		 * @param Refund $this
		 */
		$api_refund = apply_filters( 'dokan_pro_auto_process_api_refund', $api_refund, $this );

		return [
			'amount' => $this->get_refund_amount(),
			'reason' => $this->get_refund_reason(),
			'order_id' => $order->get_id(),
			'line_items' => $line_items,
			'refund_payment' => $order->get_parent_id() ? false : $api_refund,
			'restock_items' => $restock_refunded_items,
			'api_refund' => $api_refund,
		];
	}

	/**
	 * Prepare line items for refund
	 *
	 * @param WC_Order $order
	 * @return array
	 */
	private function prepare_line_items( $order ) {
		$line_items = [];
		$item_ids = array_unique( array_merge( array_keys( $this->get_item_qtys(), $this->get_item_totals(), true ) ) );

		// Initialize line items
		foreach ( $item_ids as $item_id ) {
			$line_items[ $item_id ] = [
				'qty' => 0,
				'refund_total' => 0,
				'refund_tax' => [],
			];
		}

		// Set quantities
		foreach ( $this->get_item_qtys() as $item_id => $qty ) {
			$line_items[ $item_id ]['qty'] = max( $qty, 0 );
		}

		// Set refund totals
		foreach ( $this->get_item_totals() as $item_id => $total ) {
			$item = $order->get_item( $item_id );
			$line_items[ $item_id ]['refund_total'] = wc_format_decimal( $total );
		}

		// Set tax totals
		foreach ( $this->get_item_tax_totals() as $item_id => $tax_totals ) {
			foreach ( $tax_totals as $total_tax_key => $total_tax ) {
				$line_items[ $item_id ]['refund_tax'][ $total_tax_key ] = wc_format_decimal( $total_tax );
			}
		}

		return $line_items;
	}

	/**
	 * Process the refund through WooCommerce
	 *
	 * @param array $params
	 * @return \WC_Order_Refund|WP_Error
	 */
	private function process_wc_refund( $params ) {
		$refund_args = [
			'amount' => $params['amount'],
			'reason' => $params['reason'],
			'order_id' => $params['order_id'],
			'line_items' => $params['line_items'],
			'refund_payment' => $params['refund_payment'],
			'restock_items' => $params['restock_items'],
		];

		$refund = wc_create_refund( $refund_args );

		if ( is_wp_error( $refund ) ) {
            // translators: %d is the order ID for which the refund processing failed.
            dokan_log( sprintf( __( 'Refund processing for the order #%d failed.', 'dokan' ), $params['order_id'] ) );
		}

		return $refund;
	}

	/**
	 * Process parent order refund for sub-orders
	 *
	 * @param WC_Order $order
	 * @param array $params
	 * @return WC_Order_Refund|WP_Error
	 */
	private function process_parent_order_refund( $order, $params ) {
		// Remove hooks to prevent unnecessary recalculation
		remove_action( 'woocommerce_order_status_changed', [ dokan()->fees, 'calculate_gateway_fee' ], 100 );

		// Map line items from sub-order to parent order
		$parent_line_items = $this->map_parent_order_line_items( $order, $params['line_items'] );

		// Create parent order refund
		$parent_refund = wc_create_refund(
            [
				'amount' => $params['amount'],
				'reason' => $params['reason'],
				'order_id' => $order->get_parent_id(),
				'line_items' => $parent_line_items,
				'refund_payment' => $params['api_refund'],
				'restock_items' => true,
            ]
        );

		// Restore hooks
		add_action( 'woocommerce_order_status_changed', [ dokan()->fees, 'calculate_gateway_fee' ], 100 );

		if ( is_wp_error( $parent_refund ) ) {
            // translators: %d is the suborder ID for which the refund processing failed.
            dokan_log( sprintf( __( 'Refund processing for the suborder #%d failed.', 'dokan' ), $order->get_id() ) );
			return $parent_refund;
		}

		// Add parent order note
		$parent_order = wc_get_order( $order->get_parent_id() );
		$approved_by = $this->get_approver_name();
		$payment_method_title = $parent_order->get_payment_method_title();

		$parent_order->add_order_note(
            sprintf(
                // translators: %1$s is the payment method title, %2$s is the refund reason, %3$d is the suborder ID, %4$s is the name of the user who approved the refund.
                __( 'Refund Processed via %1$s – Reason: %2$s - Suborder %3$d - Approved by %4$s', 'dokan' ),
                $params['api_refund'] ? $payment_method_title : __( 'Manual Processing', 'dokan' ),
                $parent_refund->get_reason(),
                $order->get_id(),
                $approved_by
            )
		);

		return $parent_refund;
	}

	/**
	 * Map line items from sub-order to parent order
	 *
	 * @param WC_Order $order
	 * @param array $line_items
	 * @return array
	 */
	private function map_parent_order_line_items( $order, $line_items ) {
		$line_items_product_map = [];
		foreach ( $order->get_items( 'line_item' ) as $item ) {
			if ( ! array_key_exists( $item->get_id(), $line_items ) ) {
				continue;
			}
			$line_items_product_map[ $item['product_id'] ] = $item->get_id();
		}

		$parent_order = wc_get_order( $order->get_parent_id() );
		$parent_line_items = [];

		foreach ( $parent_order->get_items( 'line_item' ) as $item ) {
			if ( ! array_key_exists( $item['product_id'], $line_items_product_map ) ) {
				continue;
			}

			$line_item_id = $line_items_product_map[ $item['product_id'] ];
			$parent_line_items[ $item->get_id() ] = $line_items[ $line_item_id ];
		}

		return $parent_line_items;
	}

	/**
	 * Add refund note to the order
	 *
	 * @param WC_Order $order
	 * @param WC_Order_Refund $refund
	 * @param bool $api_refund
	 */
	private function add_refund_note( $order, $refund, $api_refund ) {
		$payment_method_title = $order->get_payment_method_title();

		$order->add_order_note(
            sprintf(
                // translators: %1$s is the refunded amount, %2$s is the payment method, %3$s is the refund reason.
                __( 'Refunded %1$s via %2$s – Reason: %3$s ', 'dokan' ),
                $refund->get_formatted_refund_amount(),
                $api_refund ? $payment_method_title : __( 'Manual Processing', 'dokan' ),
                $refund->get_reason()
            )
		);
	}

	/**
	 * Update vendor balance with refund amount
	 *
	 * @param float $vendor_refund
	 * @param array $args
	 */
	private function update_vendor_balance( $vendor_refund, $args, $refund_order, $order ) {
		/**
		 * @since 3.3.2 filter dokan_refund_insert_into_vendor_balance added
		 *
		 * @param bool true return false if you don't want to insert into vendor balance table
		 * @param Refund $this
		 * @param array $args
		 * @param float $vendor_refund
		 */
		if ( apply_filters( 'dokan_refund_insert_into_vendor_balance', true, $this, $args, $vendor_refund ) ) {
            do_action( 'dokan_refund_adjust_vendor_balance', $vendor_refund, $refund_order, $order );
		}
	}

	/**
	 * Update order table with new refund amount
	 *
     * @param float $vendor_refund
	 * @param \WC_Order_Refund $refund_order
	 * @param \WC_Order $order
	 */
	private function update_order_amounts( $vendor_refund, $refund_order, $order ) {
        do_action( 'dokan_refund_adjust_dokan_orders', $vendor_refund, $refund_order, $order );
	}

	/**
	 * Get name of user who approved the refund
	 *
	 * @return string
	 */
	private function get_approver_name() {
		$current_user = is_user_logged_in() ? wp_get_current_user() : '';
		return ! empty( $current_user ) ? $current_user->get( 'user_nicename' ) : 'admin';
	}


	/**
	 * Calculate shipping refund amount
	 *
	 * @param WC_Order $order
	 * @param array $line_items
	 * @return float
	 */
	private function calculate_shipping_refund( $order, $line_items ) {
		$shipping_refund = 0;

		foreach ( $this->get_item_totals() as $item_id => $requested_refund ) {
			$item = $order->get_item( $item_id );

			if ( 'shipping' === $item->get_type() ) {
				$shipping_refund += $requested_refund;
			}
		}

		return $shipping_refund;
	}

    /**
     * Cancel a refund request
     *
     * @since 3.0.0
     * @since 3.3.6 Adding Order note to suborder and parent order.
     *
     * @return Refund|WP_Error
     */
    public function cancel() {
        $this->set_status( dokan_pro()->refund->get_status_code( 'cancelled' ) );

        $refund = $this->save();
        if ( is_wp_error( $refund ) ) {
            return $refund;
        }

        $order = wc_get_order( $refund->get_order_id() );
        if ( ! $order ) {
            return new WP_Error( 'dokan_refund_order_not_found', __( 'Order not found', 'dokan' ) );
        }

        $order_id = $order->get_id();

        $order->add_order_note(
            sprintf(
            // translators: 1: Refund amount 2: Refund reason.
                __( 'Refund Request for the amount: %1$s – Reason: %2$s - Got canceled.', 'dokan' ),
                $refund->get_refund_amount(),
                $refund->get_refund_reason()
            )
        );

        if ( $order->get_parent_id() ) {
            $parent_order = wc_get_order( $order->get_parent_id() );

            if ( $parent_order ) {
                $parent_order->add_order_note(
                    sprintf(
                    // translators: 1: Suborder ID 2: Refund amount 2: Refund reason.
                        __( 'Refund Request for the Suborder #%1$s - Amount %2$s – Reason: %3$s - Got canceled.', 'dokan' ),
                        $order_id,
                        $refund->get_refund_amount(),
                        $refund->get_refund_reason()
                    )
                );
            }
        }

        /**
         * Fires after cancel a refund request
         *
         * @since 3.0.0
         *
         * @param Refund $this
         */
        do_action( 'dokan_pro_refund_cancelled', $this );

        return $this;
    }

    /**
     * Check if refund is via API.
     *
     * @since 3.4.2
     *
     * @return bool
     */
    public function is_via_api() {
        return wc_string_to_bool( $this->get_method() );
    }

    /**
     * Check if refund is manual.
     *
     * @since 3.4.2
     *
     * @return bool
     */
    public function is_manual() {
        return wc_string_to_bool( $this->get_method() ) === false;
    }
}
