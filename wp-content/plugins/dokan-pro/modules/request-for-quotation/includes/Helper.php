<?php

namespace WeDevs\DokanPro\Modules\RequestForQuotation;

use WC_Customer;
use WeDevs\DokanPro\Modules\RequestForQuotation\Model\Quote;
use function PHPUnit\Framework\stringStartsWith;

/**
 * Request A Quote Helper Class.
 */
class Helper {

    public static $retry = 0;

    /**
     * Allow-list of sortable columns for the request-quotes table's ORDER BY clause.
     *
     * @since 5.0.5
     *
     * @var string[]
     */
    const QUOTE_SORTABLE_COLUMNS = [
        'id',
        'user_id',
        'order_id',
        'quote_title',
        'shipping_cost',
        'status',
        'expiry_date',
        'expected_date',
        'created_at',
        'updated_at',
        'converted_by',
    ];

    /**
     * Allow-list of sortable columns for the request-quote-rules table's ORDER BY clause.
     *
     * @since 5.0.5
     *
     * @var string[]
     */
    const QUOTE_RULE_SORTABLE_COLUMNS = [
        'id',
        'vendor_id',
        'rule_name',
        'hide_price',
        'hide_cart_button',
        'apply_on_all_product',
        'rule_priority',
        'status',
        'created_at',
    ];

    /**
     * Resolve a safe ORDER BY column from an allow-list (identifiers can't be bound as placeholders).
     *
     * @since 5.0.5
     *
     * @param mixed    $orderby  Requested column name.
     * @param string[] $allowed  Allow-list of sortable column names.
     * @param string   $fallback Fallback column when the request is not allowed.
     *
     * @return string
     */
    public static function sanitize_orderby( $orderby, array $allowed, string $fallback = 'id' ): string {
        return in_array( $orderby, $allowed, true ) ? $orderby : $fallback;
    }

    /**
     * Resolve a safe ORDER BY direction — only ASC/DESC survive, anything else falls back to the default.
     *
     * @since 5.0.5
     *
     * @param mixed  $order    Requested sort direction.
     * @param string $fallback Fallback direction when the request is invalid.
     *
     * @return string
     */
    public static function sanitize_order( $order, string $fallback = 'DESC' ): string {
        $order = is_string( $order ) ? strtoupper( trim( $order ) ) : '';

        return in_array( $order, [ 'ASC', 'DESC' ], true ) ? $order : $fallback;
    }

    /**
     * Dokan get all request quotes.
     *
     * @param $args
     *
     * @return array|object|null
     *
     * @return array|null|object|stdClass[]
     * @since 3.6.0
     */
    public static function get_request_quote( $args ) {
        global $wpdb;

        $defaults = [
            'posts_per_page' => 20,
            'offset'         => 0,
            'status'         => '',
            'user_id'        => 0,
            'order'          => 'ASC',
            'orderby'        => 'id',
        ];

        $args = wp_parse_args( $args, $defaults );

        $where[] = 'status LIKE %s';
        $data[] = $wpdb->esc_like( $args['status'] );
        if ( '' === $args['status'] ) {
            $where   = [];
            $data    = [];
            $where[] = 'status NOT LIKE %s';
            $data[]  = $wpdb->esc_like( Quote::STATUS_TRASH );
        }

        if ( ! empty( $args['user_id'] ) ) {
            $where[] = 'user_id = %d';
            $data[]  = $args['user_id'];
        }

        if ( ! empty( $args['customer_name'] ) ) {
            $where[] = 'customer_info LIKE %s';
            $data[]  = '%' . $wpdb->esc_like( $args['customer_name'] ) . '%';
        }

        if ( ! empty( $args['date_from'] ) && ! empty( $args['date_to'] ) ) {
            $start_date = strtotime( "{$args['date_from']} 00:00:00" );
            $end_date   = strtotime( "{$args['date_to']} 23:59:59" );

            $where[] = 'created_at >= %s';
            $where[] = 'created_at <= %s';
            $data[]  = $start_date;
            $data[]  = $end_date;
        }

        $limit  = 'LIMIT %d, %d';
        $data[] = $args['offset'];
        $data[] = $args['posts_per_page'];

        /**
         * Filters the WHERE clauses used to fetch request quotes.
         *
         * This filter allows modifying the SQL WHERE clauses that are used
         * to retrieve request quotes based on custom conditions.
         *
         * @since 3.12.3
         *
         * @param string $where The current WHERE clause.
         * @param array  $data  The data used in the WHERE clause.
         * @param array  $args  Additional arguments passed to the query.
         *
         * @return string The filtered WHERE clause.
         */
        $where = apply_filters( 'dokan_quote_get_request_quote_where_clauses', $where, $data, $args );
        $where = implode( ' AND ', $where );

        /**
         * Filters the data used in the request quote query.
         *
         * This filter allows modifying the data array that is used
         * in the SQL query for retrieving request quotes.
         *
         * @since 3.12.3
         *
         * @param array  $data  The current data array.
         * @param string $where The WHERE clause used in the query.
         * @param array  $args  Additional arguments passed to the query.
         *
         * @return array The filtered data array.
         */
        $data = apply_filters( 'dokan_quote_get_request_quote_where_clauses_data', $data, $where, $args );

        // ORDER BY takes column identifiers, not bindable values, so allowlist them.
        $orderby = self::sanitize_orderby( $args['orderby'], self::QUOTE_SORTABLE_COLUMNS );
        $order   = self::sanitize_order( $args['order'] );

        // Prepare and execute the SQL query to get the requested quotes.
        $requested_quotes = $wpdb->get_results(
            $wpdb->prepare(
                // phpcs:ignore
                "SELECT * FROM {$wpdb->prefix}dokan_request_quotes WHERE {$where} ORDER BY {$orderby} {$order} $limit",
                $data
            )
        );

        /**
         * Getting the requested quotes.
         *
         * Fires after the requested quotes have been retrieved from the database.
         *
         * @since 3.12.3
         *
         * @param array $requested_quotes The requested quotes retrieved from the database.
         */
        return apply_filters( 'dokan_qoute_get_request_quote', $requested_quotes, $args );
    }

    /**
     * Dokan get vendor specific request quotes.
     *
     * @since 3.6.0
     *
     * @param $args
     *
     * @return array|object|null
     *
     * @return array|null|object|stdClass[]
     */
    public static function get_request_quote_for_vendor( $args ) {
        global $wpdb;

        $defaults = [
            'posts_per_page' => 20,
            'offset'         => 0,
            'status'         => '',
            'author_id'      => 0,
            'order'          => 'ASC',
            'orderby'        => 'id',
        ];

        $where = [];
        $data  = [];
        $args  = wp_parse_args( $args, $defaults );

        // Add search query if 's' is not null
        if ( ! empty( $args['s'] ) ) {
            $search  = '%' . $wpdb->esc_like( $args['s'] ) . '%';
            $where[] = '(rq.quote_title LIKE %s OR rq.status LIKE %s OR rq.id LIKE %s)';
            $data[]  = $search;
            $data[]  = $search;
            $data[]  = $search;
        }

        if ( ! empty( $args['status'] ) ) {
            $where[] = 'rq.status LIKE %s';
            $data[]  = $wpdb->esc_like( $args['status'] );
        } else {
            $where[] = 'rq.status NOT LIKE %s';
            $data[]  = $wpdb->esc_like( Quote::STATUS_TRASH );
        }

        if ( 0 !== $args['author_id'] ) {
            $where[] = 'rq.id=rqd.quote_id and rqd.product_id = p.ID and p.post_author=%d';
            $data[]  = $args['author_id'];
        }

        $limit  = 'LIMIT %d, %d';
        $data[] = $args['offset'];
        $data[] = $args['posts_per_page'];

        /**
         * Filters the WHERE clauses used to fetch vendor request quotes.
         *
         * This filter allows modifying the SQL WHERE clauses that are used
         * to retrieve vendor request quotes based on custom conditions.
         *
         * @since 3.12.3
         *
         * @param string $where The current WHERE clause.
         * @param array  $data  The data used in the WHERE clause.
         * @param array  $args  Additional arguments passed to the query.
         *
         * @return string The filtered WHERE clause.
         */
        $where = apply_filters( 'dokan_quote_get_vendor_requested_quotes_where_clauses', $where, $data, $args );
        $where = implode( ' and ', $where );

        /**
         * Filters the data used in the vendor request quote query.
         *
         * This filter allows modifying the data array that is used
         * in the SQL query for retrieving vendor request quotes.
         *
         * @since 3.12.3
         *
         * @param array  $data  The current data array.
         * @param string $where The WHERE clause used in the query.
         * @param array  $args  Additional arguments passed to the query.
         *
         * @return array The filtered data array.
         */
        $data = apply_filters( 'dokan_quote_get_vendor_requested_quotes_where_clauses_data', $data, $where, $args );

        // ORDER BY takes column identifiers, not bindable values, so allow-list them.
        $orderby = self::sanitize_orderby( $args['orderby'], self::QUOTE_SORTABLE_COLUMNS );
        $order   = self::sanitize_order( $args['order'] );

        $vendor_quotes = $wpdb->get_results(
            $wpdb->prepare(
                // phpcs:ignore
                "SELECT rq.* FROM {$wpdb->prefix}dokan_request_quotes as rq, {$wpdb->prefix}dokan_request_quote_details as rqd, {$wpdb->prefix}posts as p  WHERE {$where} GROUP BY rq.id ORDER BY rq.{$orderby} {$order} {$limit}",
                $data
            )
        );

        /**
         * Getting vendor requested quotes.
         *
         * Fires after the vendor requested quotes have been retrieved from the database.
         *
         * @since 3.12.3
         *
         * @param array $vendor_quotes The vendor requested quotes retrieved from the database.
         * @param array $args          Arguments for vendor quotes.
         */
        return apply_filters( 'dokan_qoute_get_vendor_requested_quotes', $vendor_quotes, $args );
    }

    /**
     * Dokan get vendor specific request quotes total.
     *
     * @since 3.6.0
     *
     * @param $args
     *
     * @return array|object|null
     */
    public static function count_request_quote_for_vendor( $args ) {
        global $wpdb;

        $defaults = [
            'posts_per_page' => 20,
            'offset'         => 0,
            'status'         => '',
            'author_id'      => 0,
            'order'          => 'ASC',
            'orderby'        => 'id',
        ];

        $args = wp_parse_args( $args, $defaults );
        $data = [];
        $where = [];
        if ( 0 !== $args['author_id'] ) {
            $where[] = 'rq.id=rqd.quote_id and rqd.product_id = p.ID and p.post_author=%d';
            $data[]  = $args['author_id'];
        }

        /**
         * Filters the WHERE clauses used to fetch then count request quotes.
         *
         * This filter allows modifying the SQL WHERE clauses that are used
         * to retrieve the count of vendor request quotes based on custom conditions.
         *
         * @since 3.12.3
         *
         * @param string $where The current WHERE clause.
         * @param array  $data  The data used in the WHERE clause.
         * @param array  $args  Additional arguments passed to the query.
         *
         * @return string The filtered WHERE clause.
         */

        $where = apply_filters( 'dokan_quote_get_count_for_vendor_where_clauses', $where, $data, $args );
        $where = implode( ' and ', $where );

        /**
         * Filters the data used in the request quote query.
         *
         * This filter allows modifying the data array that is used
         * in the SQL query for retrieving the count of vendor request quotes.
         *
         * @since 3.12.3
         *
         * @param array  $data  The current data array.
         * @param string $where The WHERE clause used in the query.
         * @param array  $args  Additional arguments passed to the query.
         *
         * @return array The filtered data array.
         */

        $data = apply_filters( 'dokan_quote_get_count_for_vendor_where_clauses_data', $data, $where, $args );

        $vendor_quote_count = $wpdb->get_results(
            $wpdb->prepare(
                // phpcs:ignore
                "SELECT rq.status as status, count(rq.id) as total_count FROM {$wpdb->prefix}dokan_request_quotes as rq, {$wpdb->prefix}dokan_request_quote_details as rqd, {$wpdb->prefix}posts as p WHERE 1=1 and {$where} GROUP BY rq.id",
                $data
            )
        );

        /**
         * Getting vendor requested quotes.
         *
         * Fires after the vendor requested quote count have been retrieved from the database.
         *
         * @since 3.12.3
         *
         * @param array $vendor_quote_count The vendor requested quotes count from the database.
         * @param array $args               Arguments for vendor quote counts.
         */
        return apply_filters( 'dokan_qoute_get_count_for_vendor', $vendor_quote_count, $args );
    }

    /**
     * Dokan get all request quote rules.
     *
     * @since 3.6.0
     *
     * @param $args
     *
     * @return array|null|object|stdClass[]
     */
    public static function get_quote_rules( $args ) {
        global $wpdb;

        $defaults = [
            'posts_per_page' => 20,
            'offset'         => 0,
            'status'         => '',
            'order'          => 'ASC',
            'orderby'        => 'id',
        ];

        $args = wp_parse_args( $args, $defaults );

        $where[] = 'status LIKE %s';
        $data[]  = $wpdb->esc_like( $args['status'] );
        if ( '' === $args['status'] ) {
            $where   = [];
            $data    = [];
            $where[] = 'status NOT LIKE %s';
            $data[]  = $wpdb->esc_like( Quote::STATUS_TRASH );
        }

        $limit  = 'LIMIT %d, %d';
        $data[] = $args['offset'];
        $data[] = $args['posts_per_page'];

        /**
         * Filters the WHERE clauses used to fetch quote rules.
         *
         * This filter allows modifying the SQL WHERE clauses that are used
         * to retrieve quote rules based on custom conditions.
         *
         * @since 3.12.3
         *
         * @param string $where The current WHERE clause.
         * @param array  $data  The data used in the WHERE clause.
         * @param array  $args  Additional arguments passed to the query.
         *
         * @return string The filtered WHERE clause.
         */
        $where = apply_filters( 'dokan_quote_get_quote_rules_where_clauses', $where, $data, $args );
        $where = implode( ' and ', $where );

        /**
         * Filters the data used in the request quote query.
         *
         * This filter allows modifying the data array that is used
         * in the SQL query for retrieving quote rules.
         *
         * @since 3.12.3
         *
         * @param array  $data  The current data array.
         * @param string $where The WHERE clause used in the query.
         * @param array  $args  Additional arguments passed to the query.
         *
         * @return array The filtered data array.
         */
        $data = apply_filters( 'dokan_quote_get_quote_rules_where_clauses_data', $data, $where, $args );

        // ORDER BY takes column identifiers, not bindable values, so allow-list them.
        $orderby = self::sanitize_orderby( $args['orderby'], self::QUOTE_RULE_SORTABLE_COLUMNS );
        $order   = self::sanitize_order( $args['order'] );

        $quote_rules = $wpdb->get_results(
            $wpdb->prepare(
            // phpcs:ignore
                "SELECT * FROM {$wpdb->prefix}dokan_request_quote_rules WHERE {$where} ORDER BY {$orderby} {$order} {$limit}",
                $data
            )
        );

        /**
         * Getting vendor requested quotes.
         *
         * Fires after the quote rules have been retrieved from the database.
         *
         * @since 3.12.3
         *
         * @param array $quote_rules The quotes rule's from the database.
         * @param array $args        Arguments for quote rules.
         */
        return apply_filters( 'dokan_qoute_get_quote_rules', $quote_rules, $args );
    }

    /**
     * Dokan get all request quote rules.
     *
     * @since 3.6.0
     *
     * @return array|object|null
     */
    public static function get_all_quote_rules() {
        global $wpdb;

        $quote_rules = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}dokan_request_quote_rules WHERE `status` = 'publish'"
        );

        // Apply a filter to the button text for each quote rule.
        $quote_rules = array_map(
            static function ( $rule ) {
                $rule->button_text = apply_filters( 'dokan_request_quote_button_text', $rule->button_text, $rule );
                $rule->hide_price_text = apply_filters( 'dokan_request_quote_price_hide', $rule->hide_price_text, $rule );
                return $rule;
            },
            $quote_rules
        );
        /**
         * Getting all quote rules.
         *
         * Fires after the quote rules have been retrieved from the database.
         *
         * @since 3.12.3
         *
         * @param array $quote_rules The quotes rule's from the database.
         */
        return apply_filters( 'dokan_qoute_get_all_quote_rules', $quote_rules );
    }

    /**
     * Dokan get all request quote details.
     *
     * @param int $quote_id
     *
     * @return array|object|null
     *@since 3.6.0
     */
    public static function get_request_quote_details_by_quote_id( $quote_id ) {
        global $wpdb;

        $quote_details = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT  * FROM {$wpdb->prefix}dokan_request_quote_details WHERE quote_id = %d",
                $quote_id
            )
        );

        /**
         * Getting quote details via quote id.
         *
         * Fires after the quote details have been retrieved from the database.
         *
         * @since 3.12.3
         *
         * @param array $quote_details The quotes detail's from the database.
         * @param int   $quote_id      Quotation id.
         */
        return apply_filters( 'dokan_qoute_details_by_quote_id', $quote_details, $quote_id );
    }

    /**
     * Dokan get all request quote details by vendor id.
     *
     * @since 3.6.0
     *
     * @param int $quote_id
     * @param int $vendor_id
     *
     * @return array|object|null
     */
    public static function get_request_quote_details_by_vendor_id( $quote_id, $vendor_id ) {
        global $wpdb;

        $quote_details = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT rqd.* FROM {$wpdb->prefix}dokan_request_quotes as rq, {$wpdb->prefix}dokan_request_quote_details as rqd, {$wpdb->prefix}posts as p  WHERE rqd.quote_id=%d and rq.id=rqd.quote_id and rqd.product_id = p.ID and p.post_author=%d",
                $quote_id, $vendor_id
            )
        );

        /**
         * Getting quote details via vendor quote id.
         *
         * Fires after the vendor quote details have been retrieved from the database.
         *
         * @since 3.12.3
         *
         * @param array $quote_details The vendor quotes detail's from the database.
         * @param int   $quote_id      Quotation id.
         */
        return apply_filters( 'dokan_quote_details_by_vendor_id', $quote_details, $quote_id );
    }

    /**
     * Dokan get all request quote details.
     *
     * @since 3.6.0
     *
     * @param int $quote_id
     *
     * @return array|object|null
     */
    public static function get_request_quote_by_id( $quote_id ) {
        global $wpdb;

        $quote = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}dokan_request_quotes WHERE id = %d",
                $quote_id
            )
        );

        /**
         * Getting quote info via quote id.
         *
         * Fires after the quote info have been retrieved from the database.
         *
         * @since 3.12.3
         *
         * @param object $quote    The quote info from the database.
         * @param int    $quote_id Quotation id.
         */
        return apply_filters( 'dokan_quote_get_by_id', $quote, $quote_id );
    }

    /**
     * Dokan get all request quote details.
     *
     * @since 3.6.0
     *
     * @param int $quote_id
     * @param int $vendor_id
     *
     * @return array|object|null
     */
    public static function get_request_quote_vendor_by_id( $quote_id, $vendor_id ) {
        global $wpdb;

        $where = [ 'rq.id = %d' ];

        $data = [ $quote_id ];

        if ( 0 !== $vendor_id ) {
            $where[] = 'rq.id = rqd.quote_id AND rqd.product_id = p.ID AND p.post_author = %d';
            $data[]  = $vendor_id;
        }

        /**
         * Filters the WHERE clauses used to fetch request quote.
         *
         * This filter allows modifying the SQL WHERE clauses that are used
         * to retrieve request quote based on custom conditions.
         *
         * @since 3.12.3
         *
         * @param string $where The current WHERE clause.
         * @param array  $data  The data used in the WHERE clause.
         * @param array  $args  Additional arguments passed to the query.
         *
         * @return string The filtered WHERE clause.
         */
        $where = apply_filters( 'dokan_quote_get_by_vendor_id_where_clauses', $where, $data, $quote_id );
        $where = implode( ' AND ', $where );

        /**
         * Filters the data used in the request quote query.
         *
         * This filter allows modifying the data array that is used
         * in the SQL query for retrieving request quote.
         *
         * @since 3.12.3
         *
         * @param array  $data  The current data array.
         * @param string $where The WHERE clause used in the query.
         * @param array  $args  Additional arguments passed to the query.
         *
         * @return array The filtered data array.
         */
        $data = apply_filters( 'dokan_quote_get_by_vendor_id_where_clauses_data', $data, $where, $quote_id );

        $vendor_quote = $wpdb->get_row(
            $wpdb->prepare(
                // phpcs:ignore
                "SELECT rq.* FROM {$wpdb->prefix}dokan_request_quotes as rq, {$wpdb->prefix}dokan_request_quote_details as rqd, {$wpdb->prefix}posts as p  WHERE {$where}",
                $data
            )
        );

        /**
         * Getting quote info via vendor quote id.
         *
         * Fires after the vendor quote info have been retrieved from the database.
         *
         * @since 3.12.3
         *
         * @param object $vendor_quote The vendor quote info from the database.
         * @param int    $quote_id     Quotation id.
         */
        return apply_filters( 'dokan_quote_get_by_vendor_id', $vendor_quote, $quote_id );
    }

    /**
     * Dokan get all request quote details.
     *
     * @since 3.6.0
     *
     * @param $rule_id
     *
     * @return array|object|null
     */
    public static function get_quote_rule_by_id( $rule_id ) {
        global $wpdb;

        $quote_rule = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}dokan_request_quote_rules WHERE id = %d",
                $rule_id
            )
        );

        /**
         * Getting quote rule info via rule id.
         *
         * Fires after the quote rule info have been retrieved from the database.
         *
         * @since 3.12.3
         *
         * @param object $quote_rule The quote rule info from the database.
         * @param int    $rule_id    Rule id.
         */
        return apply_filters( 'dokan_quote_get_rule_by_id', $quote_rule, $rule_id );
    }

    /**
     * Get the count of total quotes
     *
     * @since 3.6.0
     *
     * @return object
     */
    public static function get_request_quote_count() {
        global $wpdb;

        $results             = (array) $wpdb->get_results( "SELECT status, count(id) as total_count FROM {$wpdb->prefix}dokan_request_quotes GROUP BY status", ARRAY_A );
        $counts              = array_fill_keys( get_post_stati(), 0 );
        $counts['reject']    = 0;
        $counts['cancel']    = 0;
        $counts['expired']   = 0;
        $counts['updated']   = 0;
        $counts['approve']   = 0;
        $counts['accepted']  = 0;
        $counts['converted'] = 0;
        foreach ( $results as $row ) {
            $counts[ $row['status'] ] = $row['total_count'];
        }

        // Passed only request for quote accepted statuses.
        $status_list = array_merge( Quote::get_status_keys(), [ 'publish', 'future', 'draft' ] );
        foreach ( $counts as $status => $count ) {
            if ( ! in_array( $status, $status_list, true ) ) {
                unset( $counts[ $status ] ); // Remove unsupported statuses.
            }
        }

        /**
         * Getting quote status counts.
         *
         * Fires after the quote status counts have been retrieved from the database.
         *
         * @since 3.12.3
         *
         * @param object $counts      The quote counts.
         * @param array  $status_list The list of quote status.
         */
        return apply_filters( 'dokan_quote_get_status_counts', (object) $counts, $status_list );
    }

    /**
     * Dokan get user specific request quotes total.
     *
     * @since 3.12.3
     *
     * @param $args
     *
     * @return array|object|null
     *
     * @return array|null|object|stdClass[]
     */
    public static function count_request_quote_for_frontend( $args ) {
        global $wpdb;

        $defaults = [
            'posts_per_page' => 20,
            'offset'         => 0,
            'status'         => '',
            'user_id'        => 0,
            'order'          => 'ASC',
            'orderby'        => 'id',
        ];

        $args = wp_parse_args( $args, $defaults );
        $data = [];
        $where = [];
        if ( 0 !== $args['user_id'] ) {
            $where[] = 'rq.id=rqd.quote_id and rqd.product_id = p.ID and rq.user_id=%d';
            $data[]  = $args['user_id'];
        }

        /**
         * Filters the WHERE clauses used to fetch request quote counts.
         *
         * This filter allows modifying the SQL WHERE clauses that are used
         * to retrieve quote status counts based on custom conditions.
         *
         * @since 3.12.3
         *
         * @param string $where The current WHERE clause.
         * @param array  $data  The data used in the WHERE clause.
         * @param array  $args  Additional arguments passed to the query.
         *
         * @return string The filtered WHERE clause.
         */
        $where = apply_filters( 'dokan_quote_get_frontend_status_counts_where_clauses', $where, $data, $args );
        $where = implode( ' and ', $where );

        /**
         * Filters the data used in the request quote query.
         *
         * This filter allows modifying the data array that is used
         * in the SQL query for retrieving request quote counts.
         *
         * @since 3.12.3
         *
         * @param array  $data  The current data array.
         * @param string $where The WHERE clause used in the query.
         * @param array  $args  Additional arguments passed to the query.
         *
         * @return array The filtered data array.
         */
        $data = apply_filters( 'dokan_quote_get_frontend_status_counts_where_clauses_data', $data, $where, $args );

        $counts = $wpdb->get_results(
            $wpdb->prepare(
                // phpcs:ignore
                "SELECT rq.status as status, count(rq.id) as total_count FROM {$wpdb->prefix}dokan_request_quotes as rq, {$wpdb->prefix}dokan_request_quote_details as rqd, {$wpdb->prefix}posts as p WHERE 1=1 and {$where} GROUP BY rq.id",
                $data
            )
        );

        /**
         * Getting quote frontend status counts.
         *
         * Fires after the quote frontend status counts have been retrieved from the database.
         *
         * @since 3.12.3
         *
         * @param array $counts The quote frontend status counts from database.
         * @param array $args   Quotation id.
         */
        return apply_filters( 'dokan_quote_get_frontend_status_counts', $counts, $args );
    }

    /**
     * Get the count of total quotes
     *
     * @since 3.6.0
     *
     * @return object
     */
    public static function get_quote_rules_count() {
        global $wpdb;

        $results = (array) $wpdb->get_results( "SELECT `status`, count(id) as num_quotes FROM {$wpdb->prefix}dokan_request_quote_rules GROUP BY status", ARRAY_A );
        $counts  = array_fill_keys( get_post_stati(), 0 );
        foreach ( $results as $row ) {
            $counts[ $row['status'] ] = $row['num_quotes'];
        }

        /**
         * Getting quote rule counts.
         *
         * Fires after the quote rule counts have been retrieved from the database.
         *
         * @since 3.12.3
         *
         * @param array $counts  The quote rule counts from database.
         * @param array $results Rule counts result by status.
         */
        return apply_filters( 'dokan_quote_get_quote_rules_count', (object) $counts, $results );
    }

    /**
     * Create_request_quote.
     *
     * @since 3.6.0
     *
     * @return \WP_Error|int
     */
    public static function create_request_quote( $args ) {
        global $wpdb;

        if ( empty( $args['quote_title'] ) ) {
            return new \WP_Error( 'no-name', __( 'You must provide a name.', 'dokan' ) );
        }

        if ( ! empty( $args['customer_info'] && self::$retry === 0 ) ) {
            $args['customer_info'] = maybe_serialize( $args['customer_info'] );
        }

        if ( ! empty( $args['store_info'] && self::$retry === 0 ) ) {
            $args['store_info'] = maybe_serialize( $args['store_info'] );
        }

        if ( ! empty( $args['shipping_cost'] ) ) {
            $args['shipping_cost'] = sanitize_text_field( $args['shipping_cost'] );
        }

        if ( ! empty( $args['expected_delivery_date'] ) && self::$retry === 0 ) {
            $expected_date         = sanitize_text_field( wp_unslash( $args['expected_delivery_date'] ) );
            $args['expected_date'] = dokan_current_datetime()->modify( $expected_date )->getTimestamp();
        }

        $defaults = [
            'user_id'       => 0,
            'order_id'      => 0,
            'quote_title'   => '',
            'store_info'    => '',
            'customer_info' => '',
            'expected_date' => 0,
            'status'        => Quote::STATUS_PENDING,
            'shipping_cost' => 0.00,
            'created_at'    => dokan_current_datetime()->getTimestamp(),
        ];

        $data = self::trim_extra_params( $args, $defaults );

        $inserted = $wpdb->insert(
            $wpdb->prefix . 'dokan_request_quotes',
            $data,
            [
                '%d',
                '%d',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%f',
                '%s',
            ]
        );

        if ( ! $inserted ) {
            // Prevent database error if the column is not found.
            // Never remove this code, otherwise user can get DB insertion error.
            if ( str_starts_with( $wpdb->last_error, 'Unknown column' ) && self::$retry === 0 ) {
                \WeDevs\DokanPro\Upgrade\Upgraders\V_3_12_3::update_request_for_quote_table();
                ++self::$retry;
                return self::create_request_quote( $args );
            }

            return new \WP_Error( 'failed-to-insert', __( 'Failed to insert data', 'dokan' ) );
        }

        /**
         * Fires after a request quote is successfully created.
         *
         * This action allows developers to perform additional tasks
         * after a request quote has been inserted into the database.
         *
         * @since 3.12.3
         *
         * @param int   $inserted The ID of the newly created request quote.
         * @param array $args     The arguments used to create the request quote.
         */
        do_action( 'dokan_quote_request_created', $inserted, $args );

        return $wpdb->insert_id;
    }

    /**
     * Create_request_quote.
     *
     * @since 3.6.0
     *
     * @return \WP_Error|int
     */
    public static function create_quote_rule( $args ) {
        global $wpdb;

        if ( empty( $args['rule_name'] ) ) {
            return new \WP_Error( 'no-name', __( 'You must provide a name.', 'dokan' ) );
        }

        $defaults = [
            'vendor_id'            => get_current_user_id(),
            'rule_name'            => '',
            'hide_price'           => 0,
            'hide_price_text'      => '',
            'hide_cart_button'     => 'replace',
            'apply_on_all_product' => 0,
            'button_text'          => __( 'Add to quote', 'dokan' ),
            'rule_priority'        => 0,
            'rule_contents'        => [],
            'status'               => 'publish',
            'created_at'           => dokan_current_datetime()->getTimestamp(),
        ];

        $data = self::trim_extra_params( $args, $defaults );

        $inserted = $wpdb->insert(
            $wpdb->prefix . 'dokan_request_quote_rules',
            $data,
            [
                '%d',
                '%s',
                '%d',
                '%s',
                '%s',
                '%d',
                '%s',
                '%d',
                '%s',
                '%s',
                '%s',
            ]
        );

        if ( ! $inserted ) {
            return new \WP_Error( 'failed-to-insert', __( 'Failed to insert data', 'dokan' ) );
        }

        /**
         * Fires after a quote rule is successfully created.
         *
         * This action allows developers to perform additional tasks
         * after a quote rule has been inserted into the database.
         *
         * @since 3.12.3
         *
         * @param int   $inserted The ID of the newly created quote rule.
         * @param array $args     The arguments used to create the quote rule.
         * @param int  $wpdb->insert_id The ID of the newly created quote rule.
         */
        do_action( 'dokan_quote_rule_created', $inserted, $args, $wpdb->insert_id );

        return $wpdb->insert_id;
    }

    /**
     * Create_request_quote.
     *
     * @since 3.6.0
     *
     * @return \WP_Error|int
     */
    public static function create_request_quote_details( $args ) {
        global $wpdb;

        if ( empty( $args['quote_id'] ) ) {
            return new \WP_Error( 'no-name', __( 'Requested details can\'t be saved.', 'dokan' ) );
        }

        if ( empty( $args['product_id'] ) ) {
            return new \WP_Error( 'no-name', __( 'No products found to save', 'dokan' ) );
        }

        $defaults = [
            'quote_id'    => '',
            'product_id'  => [],
            'quantity'    => 0,
            'offer_price' => [],
        ];

        $data     = wp_parse_args( $args, $defaults );
        $inserted = false;
        if ( 0 !== $data['quantity'] ) {
            $inserted = $wpdb->insert(
                $wpdb->prefix . 'dokan_request_quote_details',
                $data,
                [
                    '%d',
                    '%d',
                    '%d',
                    '%f',
                ]
            );
        }

        if ( ! $inserted ) {
            return new \WP_Error( 'failed-to-insert', __( 'Failed to insert data', 'dokan' ) );
        }

        /**
         * Fires after a quote details is successfully created.
         *
         * This action allows developers to perform additional tasks
         * after a quote details has been inserted into the database.
         *
         * @since 3.12.3
         *
         * @param int   $inserted The ID of the newly created quote details.
         * @param array $args     The arguments used to create the quote details.
         */
        do_action( 'dokan_quote_details_created', $inserted, $args );

        return $wpdb->insert_id;
    }

    /**
     * Change status.
     *
     * @since 3.6.0
     *
     * @param string $table_name
     * @param int    $id
     * @param string $status
     *
     * @return bool|int|\WP_Error
     */
    public static function change_status( $table_name, $id, $status = Quote::STATUS_PENDING ) {
        global $wpdb;

        $data['status'] = $status;

        $updated = $wpdb->update(
            $wpdb->prefix . "{$table_name}",
            $data,
            [ 'id' => $id ],
            [ '%s' ],
            [ '%d' ]
        );

        if ( ! $updated ) {
            return new \WP_Error( 'failed-to-update', __( 'Failed to update data', 'dokan' ) );
        }

        /**
         * Fires after a quote status is successfully updated.
         *
         * This action allows developers to perform additional tasks
         * after a quote status has been updated into the database.
         *
         * @since 3.12.3
         *
         * @param int|bool $updated The ID of the updated quote status.
         * @param int      $id      The updated quote id.
         * @param string   $status  The updated status for quote.
         */
        do_action( 'dokan_quote_update_status', $updated, $id, $status );

        return $updated;
    }

    /**
     * Update dokan request quote converted.
     *
     * @since 3.6.0
     *
     * @param $quote_id
     * @param $converted_by
     * @param $order_id
     *
     * @return bool|int|\WP_Error
     */
    public static function update_dokan_request_quote_converted( $quote_id, $converted_by, $order_id = 0 ) {
        global $wpdb;

        $quote_info   = self::get_request_quote_by_id( $quote_id );
        $quote_status = $quote_info->status ?? '';
        $expiry_date  = $quote_info->expiry_date ?? 0;

        // Handle schedule for quote expiry and get the expiry timestamp.
        if ( $quote_status === Quote::STATUS_APPROVED && empty( $expiry_date ) && self::$retry === 0 ) {
            $expiry_date = self::handle_schedule_for_quote_expiration( $quote_id );
        }

        $updated = $wpdb->update(
            $wpdb->prefix . 'dokan_request_quotes',
            [
                'converted_by' => $converted_by,
                'expiry_date'  => $expiry_date,
                'order_id'     => $order_id,
            ],
            [ 'id' => $quote_id ],
            [ '%s', '%d', '%d' ],
            [ '%d' ]
        );

        if ( ! $updated ) {
            // Prevent database error if the column is not found.
            // Never remove this code, otherwise user can get DB insertion error.
            if ( str_starts_with( $wpdb->last_error, 'Unknown column' ) && self::$retry === 0 ) {
                \WeDevs\DokanPro\Upgrade\Upgraders\V_3_12_3::update_request_for_quote_table();
                ++self::$retry;
                return self::update_dokan_request_quote_converted( $quote_id, $converted_by, $order_id );
            }

            return new \WP_Error( 'failed-to-update', __( 'Failed to update data', 'dokan' ) );
        }

        /**
         * Fires after a quote is successfully converted.
         *
         * This action allows developers to perform additional tasks
         * after a quote has been converted.
         *
         * @since 3.12.3
         *
         * @param int|bool $updated  The ID of the converted quote.
         * @param int      $quote_id The updated quote id.
         */
        do_action( 'dokan_quote_update_status_to_converted', $updated, $quote_id );

        return $updated;
    }

    /**
     * Schedule for quotation expiration.
     *
     * @since 3.12.3
     *
     * @return int
     */
    public static function handle_schedule_for_quote_expiration( $quote_id ) {
        $expiry_date        = 0;
        $expiry_rules       = self::get_quote_expiry_rules();
        $expiry_days        = ! empty( $expiry_rules['expiry_date'] ) ? absint( $expiry_rules['expiry_date'] ) : 0;
        $enable_expiry_date = ! empty( $expiry_rules['enable_expiry_date'] ) ? filter_var( $expiry_rules['enable_expiry_date'], FILTER_VALIDATE_BOOLEAN ) : false;
        if ( $enable_expiry_date && $expiry_days > 0 ) {
            $duration           = ( $expiry_days !== 1 ) ? "+{$expiry_days} days" : '+1 day';
            $action_duration    = apply_filters( 'dokan_quote_expiry_duration', $duration, $expiry_rules );
            $duration_timestamp = strtotime( $action_duration );

            as_schedule_single_action(
                $duration_timestamp,
                'dokan_quote_expiration_date',
                [ $quote_id ],
                'dokan_quote_single_expiration_date'
            );

            $expiry_date = $duration_timestamp;
        }

        return $expiry_date;
    }

    /**
     * Create_request_quote.
     *
     * @since 3.6.0
     *
     * @return \WP_Error|int
     */
    public static function update_request_quote( $quote_id, $args ) {
        global $wpdb;

        if ( ! empty( $args['store_info'] ) && self::$retry === 0 ) {
            $args['store_info'] = maybe_serialize( $args['store_info'] );
        }
        if ( ! empty( $args['expected_delivery_date'] ) && self::$retry === 0 ) {
            $expected_date         = sanitize_text_field( wp_unslash( $args['expected_delivery_date'] ) );
            $args['expected_date'] = dokan_current_datetime()->modify( $expected_date )->getTimestamp();
        }

        // Handle schedule for quote expiry and get the expiry timestamp.
        if ( $args['status'] === Quote::STATUS_APPROVED && self::$retry === 0 ) {
            $args['expiry_date'] = self::handle_schedule_for_quote_expiration( $quote_id );
        }

        $defaults = [
            'user_id'       => 0,
            'order_id'      => 0,
            'quote_title'   => '',
            'customer_info' => '',
            'store_info'    => '',
            'shipping_cost' => 0.00,
            'expected_date' => 0,
            'expiry_date'   => 0,
            'status'        => Quote::STATUS_PENDING,
            'created_at'    => dokan_current_datetime()->getTimestamp(),
            'converted_by'  => 'Admin',
            'updated_at'    => dokan_current_datetime()->getTimestamp(),
        ];

        $data = self::trim_extra_params( $args, $defaults );

        $updated = $wpdb->update(
            $wpdb->prefix . 'dokan_request_quotes',
            $data,
            [ 'id' => $quote_id ],
            [
                '%d',
                '%d',
                '%s',
                '%s',
                '%s',
                '%f',
                '%s',
                '%d',
                '%s',
                '%s',
            ],
            [ '%d' ]
        );

        if ( ! $updated ) {
            // Prevent database error if the column is not found.
            // Never remove this code, otherwise user can get DB insertion error.
            if ( str_starts_with( $wpdb->last_error, 'Unknown column' ) && self::$retry === 0 ) {
                \WeDevs\DokanPro\Upgrade\Upgraders\V_3_12_3::update_request_for_quote_table();
                ++self::$retry;
                return self::update_request_quote( $quote_id, $args );
            }

            return new \WP_Error( 'failed-to-update', __( 'Failed to update data', 'dokan' ) );
        }

        /**
         * Fires after a quote is successfully updated.
         *
         * This action allows developers to perform additional tasks
         * after a quote has been updated.
         *
         * @since 3.12.3
         *
         * @param int|bool $updated  The ID of the updated quote.
         * @param int      $quote_id The updated quote id.
         * @param array    $args     Arguments for updated quote.
         */
        do_action( 'dokan_quote_updated', $updated, $quote_id, $args );

        return $updated;
    }

    /**
     * Create_request_quote.
     *
     * @since 3.6.0
     *
     * @return \WP_Error|int
     */
    public static function update_quote_rule( $rule_id, $args ) {
        global $wpdb;

        $defaults = [
            'rule_name'            => '',
            'hide_price'           => 0,
            'hide_price_text'      => '',
            'hide_cart_button'     => 'replace',
            'apply_on_all_product' => 0,
            'button_text'          => __( 'Add to quote', 'dokan' ),
            'rule_priority'        => 0,
            'rule_contents'        => '',
            'status'               => 'publish',
            'created_at'           => dokan_current_datetime()->getTimestamp(),
        ];

        $data = self::trim_extra_params( $args, $defaults );

        $updated = $wpdb->update(
            $wpdb->prefix . 'dokan_request_quote_rules',
            $data,
            [ 'id' => $rule_id ],
            [
                '%s',
                '%d',
                '%s',
                '%s',
                '%d',
                '%s',
                '%d',
                '%s',
                '%s',
                '%s',
            ],
            [ '%d' ]
        );

        if ( false === $updated ) {
            return new \WP_Error( 'failed-to-update', __( 'Failed to update data', 'dokan' ) );
        }

        /**
         * Fires after a quote rule is successfully updated.
         *
         * This action allows developers to perform additional tasks
         * after a quote rule has been updated.
         *
         * @since 3.12.3
         *
         * @param int|bool $updated The ID of the updated quote rule.
         * @param array    $args    The arguments used to update the quote rule.
         * @param int      $rule_id The updated rule id.
         */
        do_action( 'dokan_quote_rule_updated', $updated, $args, $rule_id );

        return $updated;
    }

    /**
     * Trash/Delete with an id form given table.
     *
     * @param string $key
     * @param int    $id
     * @param string $where
     * @param bool  $force
     *
     * @return bool|int
     *@since 3.6.0
     */
    public static function delete( $key, $id, $where, $force = false ) {
        global $wpdb;

        $table_name = '';
        if ( 'quote_rules' === $key ) {
            $table_name = 'dokan_request_quote_rules';
        } elseif ( 'quotes' === $key ) {
            $table_name = 'dokan_request_quotes';
        } elseif ( 'quote_details' === $key ) {
            $table_name = 'dokan_request_quote_details';
        }

        if ( empty( $table_name ) ) {
            return false;
        }

        if ( $force ) {
            return $wpdb->delete(
                $wpdb->prefix . "{$table_name}",
                [ "{$where}" => $id ],
                [ '%d' ]
            );
        }

        $data['status'] = Quote::STATUS_TRASH;

        return $wpdb->update(
            $wpdb->prefix . "{$table_name}",
            $data,
            [ "{$where}" => $id ],
            [
                '%s',
            ],
            [ '%d' ]
        );
    }

    /**
     * Convert quote to_order.
     *
     * @since 3.6.0
     *
     * @param mixed $quote
     * @param mixed $quote_details
     *
     * @throws \Exception
     * @return void|int
     */
    public static function convert_quote_to_order( $quote, $quote_details ) {
        if ( empty( $quote_details ) ) {
            return;
        }

        // Now we create the order
        $quote_order = new \WC_Order();

        foreach ( $quote_details as $quote_detail ) {
            $product       = wc_get_product( $quote_detail->product_id );
            $price         = $product->get_price();
            $offered_price = isset( $quote_detail->offer_price ) ? floatval( $quote_detail->offer_price ) : $price;

            $product->set_price( $offered_price );

            $quote_order->add_product( $product, $quote_detail->quantity );
        }

        if ( ! empty( intval( $quote->user_id ) ) ) {
            $customer = new WC_Customer( $quote->user_id );
        } else {
            $customer = new WC_Customer();
        }

        $customer_billing  = $customer->get_billing();
        $customer_shipping = $customer->get_shipping();

        $quote_order->set_address( $customer_billing, 'billing' );
        $quote_order->set_address( $customer_shipping, 'shipping' );

        $quote_order->set_customer_id( intval( $quote->user_id ) );
        $quote_order->set_customer_note( __( 'Created by converting quote to order.', 'dokan' ) );
        $quote_order->set_created_via( __( 'Dokan Request Quote.', 'dokan' ) );

        $shipping_cost = $quote->shipping_cost ?? 0;
        if ( $shipping_cost > 0 ) {
            $item = new \WC_Order_Item_Shipping(); // Create a new shipping item.
            $item->set_method_title( esc_html__( 'Dokan Quote Shipping Cost', 'dokan' ) );
            $item->set_method_id( 'dokan_quote_shipping_cost_' . $quote->id );
            $item->set_total( (string) ( $quote_order->get_total() + $shipping_cost ) );

            // Apply filter to the shipping item before adding to the order.
            $item = apply_filters( 'dokan_quote_shipping_item', $item, $quote_order, $quote );

            // Add the item to the order.
            $quote_order->add_item( $item );
        }

        $quote_order->calculate_totals();

        $order_id = $quote_order->save();
        dokan_sync_insert_order( $order_id );

        // Sending the new order email
        $email_new_order = WC()->mailer()->get_emails()['WC_Email_Customer_On_Hold_Order'];
        if ( $email_new_order instanceof \WC_Email_Customer_On_Hold_Order ) {
            $email_new_order->trigger( $order_id, $quote_order );
        }

        return $order_id;

        // If there is any possibility of more than one vendor then use this /do_action( 'woocommerce_checkout_update_order_meta', $order_id );/ to split orders.
    }

    /**
     * Get the quote subtotal.
     *
     * @since 3.6.0
     *
     * @param array $contents
     *
     * @return int[] formatted price
     */
    public function get_calculated_totals( $contents = [] ) {
        $quote_totals = [
            '_subtotal'      => 0,
            '_offered_total' => 0,
            '_tax_total'     => 0,
            '_total'         => 0,
        ];

        if ( empty( $contents ) ) {
            $quote_session = Session::init();
            $contents = $quote_session->get( DOKAN_SESSION_QUOTE_KEY );
        }

        if ( empty( $contents ) ) {
            return $quote_totals;
        }

        foreach ( $contents as $quote_item_key => $quote_item ) {
            if ( ! isset( $quote_item['data'] ) || ! is_object( $quote_item['data'] ) ) {
                continue;
            }

            $product       = $quote_item['data'];
            $quantity      = $quote_item['quantity'];
            $price         = empty( $quote_item['addons_price'] ) ? floatval( $product->get_price() ) : floatval( $quote_item['addons_price'] );
            $offered_price = isset( $quote_item['offered_price'] ) ? floatval( $quote_item['offered_price'] ) : $price;

            $quote_totals['_offered_total'] += $offered_price * intval( $quantity );

            if ( ! $product->is_taxable() ) {
                $product_subtotal           = $price * $quantity;
                $quote_totals['_subtotal']  += $product_subtotal;
                $quote_totals['_tax_total'] += 0;
                continue;
            }

            if ( ! wc_prices_include_tax() ) {
                $product_subtotal = wc_get_price_including_tax(
                    $product,
                    [
                        'qty'   => $quantity,
                        'price' => $price,
                    ]
                );
            } else {
                $product_subtotal = wc_get_price_excluding_tax(
                    $product, [
                        'qty'   => $quantity,
                        'price' => $price,
                    ]
                );
            }

            $difference_price = ( $price * $quantity ) - $product_subtotal;

            if ( $difference_price < 0 ) {
                $difference_price = $difference_price * - 1;
            }

            $quote_totals['_subtotal']  += $price * $quantity;
            $quote_totals['_tax_total'] += $difference_price;
        }

        $quote_totals['_total'] = $quote_totals['_subtotal'] + $quote_totals['_tax_total'];

        return $quote_totals;
    }

    /**
     * This method will check if quote is enabled for catalog mode
     *
     * @since 3.7.4
     *
     * @param int $vendor_id
     *
     * @return bool
     */
    public static function is_quote_support_disabled_for_catalog_mode( $vendor_id = 0 ) {
        if ( ! class_exists( '\WeDevs\Dokan\CatalogMode\Helper' ) ) {
            return true; // catalog mode is not available, so load the quote template
        }

        // check if admin enabled catalog mode
        if ( ! \WeDevs\Dokan\CatalogMode\Helper::is_enabled_by_admin() ) {
            return true; // catalog mode is not enabled, so load the quote template
        }

        if ( ! $vendor_id ) {
            $vendor_id = dokan_get_current_user_id();
        }

        $settings = \WeDevs\Dokan\CatalogMode\Helper::get_vendor_catalog_mode_settings( $vendor_id );

        return 'off' === $settings['request_a_quote_enabled'];
    }

    /**
     * Extra params trimmer method.
     *
     * @since 3.7.14
     *
     * @param array $args     Passed arguments
     * @param array $defaults Default arguments
     *
     * @return array
     */
    public static function trim_extra_params( $args, $defaults ) {
        $filtered_args = array_filter(
            $args, function ( $key ) use ( $defaults ) {
                return array_key_exists( $key, $defaults );
            }, ARRAY_FILTER_USE_KEY
        );

        return wp_parse_args( $filtered_args, $defaults );
    }

    /**
     * Returns site administrators id
     *
     * @since 3.9.3
     *
     * @return int
     */
    protected static function get_site_admin_id(): int {
        $administrator = current( get_users( [ 'role' => 'administrator' ] ) );
        return ! empty( $administrator ) ? $administrator->ID : 0;
    }

    /**
     * Creates add to quote page
     *
     * @since 3.9.3
     *
     * @return int|\WP_Error
     */
    protected static function create_add_to_quote_page() {
        $new_page = [
            'post_status'    => 'publish',
            'post_type'      => 'page',
            'post_author'    => static::get_site_admin_id(),
            'post_name'      => 'request-quote',
            'post_title'     => 'Request for Quote',
            'post_content'   => '[dokan-request-quote]',
            'post_parent'    => 0,
            'comment_status' => 'closed',
        ];

        $page_id = wp_insert_post( $new_page );

        if ( ! $page_id ) {
            return new \WP_Error( 'failed-to-insert', __( 'Failed to insert data', 'dokan' ) );
        }

        /**
         * Fires after add to quote page successfully created.
         *
         * This action allows developers to perform additional tasks
         * after add to quote page has been created.
         *
         * @since 3.12.3
         *
         * @param int   $page_id  The ID of the add to quote page.
         * @param array $new_page The arguments used to create the add to quote page.
         */
        do_action( 'dokan_quote_creat_add_to_quote_page', $page_id, $new_page );

        update_option( 'dokan_request_quote_page_id', $page_id );
        return $page_id;
    }

    /**
     * Returns quote page id, creates is necessary
     *
     * @since 3.9.3
     *
     * @return int
     */
    public static function get_quote_page_id(): int {
        $page_id = get_option( 'dokan_request_quote_page_id', 0 );
        $page    = get_post( $page_id );

        if ( ! $page || 'publish' !== $page->post_status ) {
            $page_id = static::create_add_to_quote_page();
        }

        return apply_filters( 'dokan_get_translated_page_id', $page_id );
    }

    /**
     * Get bulk action list for admin new quote page.
     *
     * @since 3.12.3
     *
     * @return mixed|null
     */
    public static function get_quote_bulk_action_list_for_new() {
        return apply_filters(
            'dokan_get_new_quote_bulk_actions_list',
            [
                [
                    'key'   => Quote::STATUS_PENDING,
                    'label' => __( 'Change Status to Pending', 'dokan' ),
                ],
                [
                    'key'   => Quote::STATUS_APPROVED,
                    'label' => __( 'Change Status to Approved', 'dokan' ),
                ],
                [
                    'key'   => Quote::STATUS_TRASH,
                    'label' => __( 'Move to Trash', 'dokan' ),
                ],
            ]
        );
    }

    /**
     * Get quote applicable rule.
     *
     * @since 3.12.3
     *
     * @return mixed|null
     */
    public static function get_quote_applicable_rule() {
        $quote_rules     = self::get_all_quote_rules();
        $applicable_rule = null;

        foreach ( $quote_rules as $rule ) {
            // Checking if there are no capable rule is set or current loop rule priority is less or lower than the previous rule.
            if ( null === $applicable_rule || $applicable_rule->rule_priority >= $rule->rule_priority ) {
                $applicable_rule = $rule;
            }
        }

        return $applicable_rule;
    }

    /**
     * Check quote hide price rule.
     *
     * @since 3.12.3
     *
     * @return bool
     */
    public static function enable_quote_hide_price_rule(): bool {
        $applicable_rule = self::get_quote_applicable_rule();
        return $applicable_rule->hide_price ?? false;
    }

    /**
     * Get quote expiry date rules.
     *
     * @since 3.12.3
     *
     * @return array
     */
    public static function get_quote_expiry_rules(): array {
        $applicable_rule = self::get_quote_applicable_rule();
        $rule_contents   = $applicable_rule->rule_contents ?? '';
        $rule_contents   = ! empty( $rule_contents ) ? maybe_unserialize( $rule_contents ) : [];

        $enable_expiry_date = $rule_contents['switches']['expire_switch'] ?? false;
        $expiration_date    = $rule_contents['expire_limit'] ?? 0;

        return apply_filters(
            'dokan_get_quote_available_expiration_date_rules',
            [
                'expiry_date'        => $expiration_date,
                'enable_expiry_date' => $enable_expiry_date,
            ]
        );
    }

    /**
     * Get order current quote status html.
     *
     * @param string $get_status
     *
         *@since 3.12.3
     */
    public static function get_order_quote_status_html( $get_status ) {
        $quote_statuses = Quote::get_quote_statuses();
        $status_label   = $quote_statuses[ $get_status ] ?? '';

        switch ( $get_status ) {
            case Quote::STATUS_PENDING:
                $status_label_html = sprintf( '<span class="dokan-label dokan-label-pending">%s</span>', $status_label );
                break;
            case Quote::STATUS_APPROVED:
                $status_label_html = sprintf( '<span class="dokan-label dokan-label-approve">%s</span>', $status_label );
                break;
            case Quote::STATUS_EXPIRED:
                $status_label_html = sprintf( '<span class="dokan-label dokan-label-expired">%s</span>', $status_label );
                break;
            case Quote::STATUS_REJECT:
                $status_label_html = sprintf( '<span class="dokan-label dokan-label-rejected">%s</span>', $status_label );
                break;
            case Quote::STATUS_UPDATE:
                $status_label_html = sprintf( '<span class="dokan-label dokan-label-updated">%s</span>', $status_label );
                break;
            case Quote::STATUS_ACCEPT:
                $status_label_html = sprintf( '<span class="dokan-label dokan-label-accepted">%s</span>', $status_label );
                break;
            case Quote::STATUS_CONVERTED:
                $status_label_html = sprintf( '<span class="dokan-label dokan-label-converted">%s</span>', $status_label );
                break;
            case Quote::STATUS_CANCEL:
                $status_label_html = sprintf( '<span class="dokan-label dokan-label-cancel">%s</span>', $status_label );
                break;
            case Quote::STATUS_TRASH:
                $status_label_html = sprintf( '<span class="dokan-label dokan-label-trash">%s</span>', $status_label );
                break;
            default:
                $status_label_html = apply_filters( 'dokan_quote_status_label_null', '--', $get_status );
                break;
        }

        return apply_filters( 'dokan_shipment_statuses_html', $status_label_html, $get_status );
    }

    /**
     * Check quote is available to converted by vendor or not.
     *
     * @since 3.12.3
     *
     * @param array     $data
     * @param \stdClass $quote
     *
     * @return bool
     */
    public static function compare_quote_for_update_status_availability( $data, $quote ) {
        $quote_id          = $quote->id ?? 0;
        $old_quote_details = self::get_request_quote_details_by_quote_id( $quote_id );
        $vendor_offers     = ! empty( $data['offer_price'] ) ? wc_clean( wp_unslash( $data['offer_price'] ) ) : [];
        $shipping_cost     = ! empty( $data['shipping_cost'] ) ? floatval( $data['shipping_cost'] ) : 0.00;

        if ( 0 < $shipping_cost ) {
            return true;
        }

        foreach ( $old_quote_details as $quote_details ) {
            $product_id           = $quote_details->product_id ?? 0;
            $quote_price          = (float) ( $quote_details->offer_price ?? 0 );
            $vendor_offered_price = (float) ( $vendor_offers[ $product_id ] ?? 0 );

            if ( $quote_price !== $vendor_offered_price ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the quote has a specific status.
     *
     * This function verifies if the given quote object has the specified status.
     *
     * @param string    $status The status to check against the quote's status.
     * @param \stdClass $quote  The quote object to check the status of.
     *
     * @return bool True if the quote exists and its status matches the given status, false otherwise.
     *@since 3.12.3
     */
    public static function is_qoute_status( $status, $quote ) {
        return isset( $quote->status ) && $status === $quote->status;
    }
}
