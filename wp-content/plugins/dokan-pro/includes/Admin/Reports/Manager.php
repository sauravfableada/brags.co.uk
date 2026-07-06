<?php

namespace WeDevs\DokanPro\Admin\Reports;

use DateTimeZone;
use WeDevs\Dokan\Utilities\OrderUtil;

/**
 * Class Manager
 *
 * @since 3.4.1
 * @since 3.8.0 Moved this class from includes/Reports/Manager.php to includes/Admin/Reports/Manager.php
 */
class Manager {

    /**
     * Constructor
     *
     * @since 5.0.0
     */
    public function __construct() {
        // Initialize Reports admin page
        $admin_reports = new AdminReports();
        $admin_reports->register_hooks();
    }

    /**
     * @since 3.4.1
     *
     * @param array $args
     *
     * @return array|object|string|null
     */
    public function get_logs( $args = [] ) {
        $default = [
            'id'           => [],
            'order_id'     => [],
            'vendor_id'    => [],
            'start_date'   => '',
            'end_date'     => '',
            'order_status' => '',         // possible values are wc-processing, wc-completed etc.
            'orderby'      => 'id',
            'order'        => 'DESC',
            'return'       => 'ids',     // possible values are all, ids, count
            'per_page'     => 20,
            'page'         => 1,
        ];

        $args = wp_parse_args( $args, $default );

        $query_args = [
			'id'           => $args['id'],
	        'order_id'     => $args['order_id'],
	        'seller_id'    => $args['vendor_id'],
	        'date' 	   => [
		        'from' => $args['start_date'],
		        'to'   => $args['end_date'],
	        ],
	        'status'       => explode( ',', $args['order_status'] ),
	        'orderby'      => $args['orderby'],
	        'order'        => $args['order'],
	        'return'       => $args['return'],
	        'limit'        => $args['per_page'],
	        'paged'        => $args['page'],
        ];

		$orders = dokan()->order->all( $query_args );
		if ( 'count' === $orders ) {
			return $orders->total;
		}

		return $orders;
    }

    /**
     * This will check if given var is empty or not.
     *
     * @since 3.4.1
     *
     * @param mixed $var
     *
     * @return bool
     */
    protected function is_empty( $var ) {
        if ( empty( $var ) ) {
            return true;
        }

        if ( isset( $var[0] ) && intval( $var[0] === 0 ) ) {
            return true;
        }

        return false;
    }
}
