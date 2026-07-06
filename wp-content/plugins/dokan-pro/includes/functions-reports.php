<?php

use WeDevs\Dokan\Cache;
use WeDevs\DokanPro\Reports\ReportStatement;
use WeDevs\DokanPro\Reports\Manager as ReportManager;

/**
 * Seller sales statement
 *
 * @since 4.0.0
 *
 * @return void
 */
function dokan_seller_sales_statement() {
    $args = [];
    if ( isset( $_GET['dokan_report_filter'] ) && isset( $_GET['dokan_report_filter_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['dokan_report_filter_nonce'] ) ), 'dokan_report_filter' ) && isset( $_GET['start_date_alt'] ) && isset( $_GET['end_date_alt'] ) ) {
        $args['start_date'] = sanitize_text_field( wp_unslash( $_GET['start_date_alt'] ) );
        $args['end_date']   = sanitize_text_field( wp_unslash( $_GET['end_date_alt'] ) );
    }

    $report_statement = new ReportStatement();
    $data             = $report_statement->get_statement_data( $args );

    if ( is_wp_error( $data ) ) {
        dokan_get_template_part( 'global/dokan-error', '', [ 'message' => $data->get_error_message() ] );
        return;
    }

    $export_error = $_GET['export_error'] ?? false; // phpcs:ignore
    if ( $export_error ) {
        dokan_get_template_part(
            'global/dokan-error',
            '',
            [
                'deleted' => true,
                'message' => esc_html__( 'Unable to export statement data. Please try again.', 'dokan' ),
            ]
        );
    }

    $opening_balance = $data->get_opening_balance() ?? 0;
    $statements      = $data->get_entries() ?? [];
    $start_date      = $data->get_start_date();
    $end_date        = $data->get_end_date();
    ?>
    <form method="get" class="dokan-form-inline report-filter dokan-clearfix" action="" id="dokan-v-dashboard-reports">
        <div class="dokan-form-group">
            <input type="text" class="dokan-form-control dokan-daterangepicker" placeholder="<?php esc_attr_e( 'Select Date Range', 'dokan' ); ?>" value="<?php echo dokan_format_date( $start_date ) . ' - ' . dokan_format_date( $end_date ); ?>" autocomplete="off">
            <input type="hidden" name="start_date_alt" class="dokan-daterangepicker-start-date" value="<?php echo esc_attr( $start_date ); ?>" />
            <input type="hidden" name="end_date_alt" class="dokan-daterangepicker-end-date" value="<?php echo esc_attr( $end_date ); ?>" />

            <?php wp_nonce_field( 'dokan_report_filter', 'dokan_report_filter_nonce' ); ?>
        </div>

        <div class="dokan-form-group">
            <input type="hidden" name="chart" value="sales_statement">
            <input type="submit" name="dokan_report_filter" class="dokan-btn dokan-btn-success dokan-btn-sm dokan-theme" value="<?php esc_attr_e( 'Show', 'dokan' ); ?>" />
        </div>
        <input type="submit" name="dokan_statement_export_all" class="dokan-btn dokan-right dokan-btn-sm dokan-btn-danger dokan-btn-theme" <?php echo empty( $statements ) ? 'disabled' : ''; ?> value="<?php esc_attr_e( 'Export All', 'dokan' ); ?>">
    </form>
    <table class="table table-striped">
        <thead>
        <tr>
            <th><?php esc_html_e( 'Balance Date', 'dokan' ); ?></th>
            <th><?php esc_html_e( 'Trn Date', 'dokan' ); ?></th>
            <th><?php esc_html_e( 'ID', 'dokan' ); ?></th>
            <th><?php esc_html_e( 'Type', 'dokan' ); ?></th>
            <th><?php esc_html_e( 'Debit', 'dokan' ); ?></th>
            <th><?php esc_html_e( 'Credit', 'dokan' ); ?></th>
            <th><?php esc_html_e( 'Balance', 'dokan' ); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php if ( $opening_balance ) : ?>
            <tr>
                <td><?php echo esc_html( dokan_format_date( $start_date ) ); ?></td>
                <td><?php esc_html_e( '--', 'dokan' ); ?></td>
                <td><?php esc_html_e( '--', 'dokan' ); ?></td>
                <td><?php esc_html_e( 'Opening Balance', 'dokan' ); ?></td>
                <td><?php esc_html_e( '--', 'dokan' ); ?></td>
                <td><?php esc_html_e( '--', 'dokan' ); ?></td>
                <td><?php echo wp_kses_post( wc_price( $opening_balance ) ); ?></td>
            </tr>
        <?php endif; ?>
        <?php if ( empty( $statements ) ) : ?>
            <tr>
                <td colspan="6"><?php esc_html_e( 'No Result found!', 'dokan' ); ?></td>
            </tr>
			<?php
        else :
            $total_debit  = 0;
            $total_credit = 0;
            $balance      = $opening_balance;
            foreach ( $statements as $statement ) :
                if ( $statement['trn_type'] === 'opening_balance' ) {
                    continue; // Skip opening balance entry since we've already shown it.
                }

                $url           = '#';
                $type          = '';
                $total_debit  += $statement['debit'];
                $total_credit += $statement['credit'];
                $balance      += ( $statement['debit'] - $statement['credit'] );
                switch ( $statement['trn_type'] ) {
                    case 'dokan_orders':
                        $type = __( 'Order', 'dokan' );
                        $url  = wp_nonce_url( add_query_arg( [ 'order_id' => $statement['trn_id'] ], dokan_get_navigation_url( 'orders' ) ), 'dokan_view_order' );
                        break;
                    case 'dokan_withdraw':
                        $type = __( 'Withdraw', 'dokan' );
                        $url  = add_query_arg( [ 'type' => 'approved' ], dokan_get_navigation_url( 'withdraw' ) );
                        break;
                    case 'dokan_refund':
                        $type = __( 'Refund', 'dokan' );
                        $url  = wp_nonce_url( add_query_arg( [ 'order_id' => $statement['trn_id'] ], dokan_get_navigation_url( 'orders' ) ), 'dokan_view_order' );
                        break;
                }
                ?>
                <tr>
                    <td><?php echo esc_html( dokan_format_date( $statement['balance_date'] ) ); ?></td>
                    <td><?php echo esc_html( dokan_format_date( $statement['trn_date'] ) ); ?></td>
                    <td>
                        <a href="<?php echo esc_url( $url ); ?>">
                            <?php
                            printf(
                            /* translators: %s: transaction id */
                                esc_html__( '#%s', 'dokan' ),
                                esc_html( $statement['trn_id'] )
                            );
                            ?>
                        </a>
                    </td>
                    <td><?php echo esc_html( $type ); ?></td>
                    <td><?php echo wp_kses_post( wc_price( $statement['debit'] ) ); ?></td>
                    <td><?php echo wp_kses_post( wc_price( $statement['credit'] ) ); ?></td>
                    <td><?php echo wp_kses_post( wc_price( $balance ) ); ?></td>
                </tr>
            <?php endforeach; ?>
            <tr>
                <td></td>
                <td></td>
                <td></td>
                <td><b><?php esc_html_e( 'Total :', 'dokan' ); ?></b></td>
                <td><b><?php echo wc_price( $total_debit ); ?></b></td>
                <td><b><?php echo wc_price( $total_credit ); ?></b></td>
                <td><b><?php echo wc_price( $balance ); ?></b></td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
    <?php
}

/**
 * Generate SQL query and fetch the report data based on the arguments passed
 *
 * This function was cloned from WC_Admin_Report class.
 *
 * @since 1.0
 *
 * @global WPDB $wpdb
 * @global WP_User $current_user
 *
 * @param array  $args
 * @param string $start_date
 * @param string $end_date
 *
 * @return obj
 */
function dokan_get_order_report_data( $args, $start_date, $end_date, $current_user = false ) {
    global $wpdb;
    if ( ! $current_user ) {
        $current_user = dokan_get_current_user_id();
    }
    $defaults = [
        'data'         => [],
        'where'        => [],
        'where_meta'   => [],
        'query_type'   => 'get_row',
        'group_by'     => '',
        'order_by'     => '',
        'limit'        => '',
        'filter_range' => false,
        'nocache'      => false,
        'debug'        => false,
    ];
    $args = wp_parse_args( $args, $defaults );
    extract( $args );
    if ( empty( $data ) ) {
        return false;
    }
    $select = [];
    foreach ( $data as $key => $value ) {
        $distinct = '';
        if ( isset( $value['distinct'] ) ) {
            $distinct = 'DISTINCT';
        }
        if ( $value['type'] === 'meta' ) {
            $get_key = "meta_{$key}.meta_value";
        } elseif ( $value['type'] === 'post_data' ) {
            $get_key = "posts.{$key}";
        } elseif ( $value['type'] === 'order_item_meta' ) {
            $get_key = "order_item_meta_{$key}.meta_value";
        } elseif ( $value['type'] === 'order_item' ) {
            $get_key = "order_items.{$key}";
        } elseif ( $value['type'] === 'dokan_orders' ) {
            $get_key = "do.{$key}";
        }
        if ( $value['function'] ) {
            $get = "{$value['function']}({$distinct} {$get_key})";
        } else {
            $get = "{$distinct} {$get_key}";
        }
        $select[] = "{$get} as {$value['name']}";
    }
    $query['select'] = 'SELECT ' . implode( ',', $select );
    $query['from']   = "FROM {$wpdb->posts} AS posts";
    // Joins
    $joins       = [];
    $joins['do'] = "LEFT JOIN {$wpdb->prefix}dokan_orders AS do ON posts.ID = do.order_id";
    foreach ( $data as $key => $value ) {
        if ( $value['type'] === 'meta' ) {
            $joins[ "meta_{$key}" ] = "LEFT JOIN {$wpdb->postmeta} AS meta_{$key} ON posts.ID = meta_{$key}.post_id";
        } elseif ( $value['type'] === 'order_item_meta' ) {
            $joins['order_items']            = "LEFT JOIN {$wpdb->prefix}woocommerce_order_items AS order_items ON posts.ID = order_items.order_id";
            $joins[ "order_item_meta_{$key}" ] = "LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta_{$key} ON order_items.order_item_id = order_item_meta_{$key}.order_item_id";
        } elseif ( $value['type'] === 'order_item' ) {
            $joins['order_items'] = "LEFT JOIN {$wpdb->prefix}woocommerce_order_items AS order_items ON posts.ID = order_items.order_id";
        }
    }
    if ( ! empty( $where_meta ) ) {
        foreach ( $where_meta as $value ) {
            if ( ! is_array( $value ) ) {
                continue;
            }
            $key = is_array( $value['meta_key'] ) ? $value['meta_key'][0] : $value['meta_key'];
            if ( isset( $value['type'] ) && $value['type'] === 'order_item_meta' ) {
                $joins['order_items']            = "LEFT JOIN {$wpdb->prefix}woocommerce_order_items AS order_items ON posts.ID = order_id";
                $joins[ "order_item_meta_{$key}" ] = "LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta_{$key} ON order_items.order_item_id = order_item_meta_{$key}.order_item_id";
            } else {
                // If we have a where clause for meta, join the postmeta table
                $joins[ "meta_{$key}" ] = "LEFT JOIN {$wpdb->postmeta} AS meta_{$key} ON posts.ID = meta_{$key}.post_id";
            }
        }
    }
    $query['join'] = implode( ' ', $joins );
    $query['where'] = "
        WHERE   posts.post_type     = 'shop_order'
        AND     posts.post_status   != 'trash'
        AND     do.seller_id = {$current_user}
        AND     do.order_status IN ('" . implode( "','", apply_filters( 'woocommerce_reports_order_statuses', [ 'wc-completed', 'wc-processing', 'wc-on-hold', 'wc-refunded' ] ) ) . "')
        AND     do.order_status NOT IN ('wc-cancelled','wc-failed')
        ";
    if ( $filter_range ) {
        $query['where'] .= "
            AND     DATE(post_date) >= '" . $start_date . "'
            AND     DATE(post_date) <= '" . $end_date . "'
        ";
    }
    foreach ( $data as $key => $value ) {
        if ( $value['type'] === 'meta' ) {
            $query['where'] .= " AND meta_{$key}.meta_key = '{$key}'";
        } elseif ( $value['type'] === 'order_item_meta' ) {
            $query['where'] .= " AND order_items.order_item_type = '{$value['order_item_type']}'";
            $query['where'] .= " AND order_item_meta_{$key}.meta_key = '{$key}'";
        }
    }
    if ( ! empty( $where_meta ) ) {
        $relation = isset( $where_meta['relation'] ) ? $where_meta['relation'] : 'AND';
        $query['where'] .= ' AND (';
        foreach ( $where_meta as $index => $value ) {
            if ( ! is_array( $value ) ) {
                continue;
            }
            $key = is_array( $value['meta_key'] ) ? $value['meta_key'][0] : $value['meta_key'];
            if ( strtolower( $value['operator'] ) === 'in' ) {
                if ( is_array( $value['meta_value'] ) ) {
                    $value['meta_value'] = implode( "','", $value['meta_value'] ); // phpcs:ignore
                }
                if ( ! empty( $value['meta_value'] ) ) {
                    $where_value = "IN ('{$value['meta_value']}')";
                }
            } else {
                $where_value = "{$value['operator']} '{$value['meta_value']}'";
            }
            if ( ! empty( $where_value ) ) {
                if ( $index > 0 ) {
                    $query['where'] .= ' ' . $relation;
                }
                if ( isset( $value['type'] ) && $value['type'] === 'order_item_meta' ) {
                    if ( is_array( $value['meta_key'] ) ) {
                        $query['where'] .= " ( order_item_meta_{$key}.meta_key   IN ('" . implode( "','", $value['meta_key'] ) . "')";
                    } else {
                        $query['where'] .= " ( order_item_meta_{$key}.meta_key   = '{$value['meta_key']}'";
                    }
                    $query['where'] .= " AND order_item_meta_{$key}.meta_value {$where_value} )";
                } else {
                    if ( is_array( $value['meta_key'] ) ) {
                        $query['where'] .= " ( meta_{$key}.meta_key   IN ('" . implode( "','", $value['meta_key'] ) . "')";
                    } else {
                        $query['where'] .= " ( meta_{$key}.meta_key   = '{$value['meta_key']}'";
                    }
                    $query['where'] .= " AND meta_{$key}.meta_value {$where_value} )";
                }
            }
        }
        $query['where'] .= ')';
    }
    if ( ! empty( $where ) ) {
        foreach ( $where as $value ) {
            if ( strtolower( $value['operator'] ) === 'in' ) {
                if ( is_array( $value['value'] ) ) {
                    $value['value'] = implode( "','", $value['value'] );
                }
                if ( ! empty( $value['value'] ) ) {
                    $where_value = "IN ('{$value['value']}')";
                }
            } else {
                $where_value = "{$value['operator']} '{$value['value']}'";
            }
            if ( ! empty( $where_value ) ) {
                $query['where'] .= " AND {$value['key']} {$where_value}";
            }
        }
    }
    if ( $group_by ) {
        $query['group_by'] = "GROUP BY {$group_by}";
    }
    if ( $order_by ) {
        $query['order_by'] = "ORDER BY {$order_by}";
    }
    if ( $limit ) {
        $query['limit'] = "LIMIT {$limit}";
    }
    $query      = apply_filters( 'dokan_reports_get_order_report_query', $query );
    $query      = implode( ' ', $query );
    $query_hash = md5( $query_type . $query );
    if ( $debug ) {
        printf( '<pre>%s</pre>', print_r( $query, true ) );
    }
    $cache_group = "report_data_seller_{$current_user}";
    $cache_key   = 'wc_report_' . $query_hash;
    $result = Cache::get_transient( $cache_key, $cache_group );
    if ( $debug || $nocache || ( false === $result ) ) {
        $result = apply_filters( 'dokan_reports_get_order_report_data', $wpdb->$query_type( $query ), $data );
        if ( $filter_range ) {
            if ( $end_date === dokan_current_datetime()->format( 'Y-m-d' ) ) {
                $expiration = 60 * 60 * 1; // 1 hour
            } else {
                $expiration = 60 * 60 * 24; // 24 hour
            }
        } else {
            $expiration = 60 * 60 * 24; // 24 hour
        }
        Cache::set_transient( $cache_key, $result, $cache_group, $expiration );
    }
    return $result;
}
/**
 * Generate sales overview report chart in report area
 *
 * @since 1.0
 * @since 3.8.0 Rewritten the function
 *
 * @return void
 */
function dokan_sales_overview() {
    $sales_by_date = new \WeDevs\DokanPro\Reports\SalesByDate();
    $sales_by_date->current_range = 'month';
    $sales_by_date->heading    = __( 'This month\'s sales', 'dokan' );
    $sales_by_date->output_report();
}
/**
 * Generate seller dashboard overview chart
 *
 * @since 1.0
 * @since 3.8.0 Rewritten the function
 *
 * @return void
 */
function dokan_dashboard_sales_overview() {
    $sales_by_date = new \WeDevs\DokanPro\Reports\SalesByDate();
    $sales_by_date->hide_sidebar = true;
    $sales_by_date->current_range = 'month';
    $sales_by_date->output_report();
}
/**
 * Generates daily sales report
 *
 * @since 1.0
 * @since 3.8.0 Rewritten the function
 *
 * @global WPDB $wpdb
 */
function dokan_daily_sales() {
    $start_date = dokan_current_datetime()->modify( 'first day of this month' );
    $end_date   = dokan_current_datetime()->modify( 'midnight' );
    $sales_by_date = new \WeDevs\DokanPro\Reports\SalesByDate();
    $sales_by_date->heading    = __( 'Daily Sales', 'dokan' );
    $sales_by_date->current_range = 'month';
    if ( isset( $_POST['dokan_report_filter'] ) && isset( $_POST['dokan_report_filter_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['dokan_report_filter_nonce'] ) ), 'custom_range' ) && isset( $_POST['start_date_alt'] ) && isset( $_POST['end_date_alt'] ) ) {
        $sales_by_date->current_range = 'custom';
        $start_date = dokan_current_datetime()->modify( sanitize_text_field( wp_unslash( $_POST['start_date_alt'] ) ) );
        $end_date   = dokan_current_datetime()->modify( sanitize_text_field( wp_unslash( $_POST['end_date_alt'] ) ) );
    }
    ?>
    <form method="post" class="dokan-form-inline report-filter dokan-clearfix" action="" id="dokan-v-dashboard-reports">
        <div class="dokan-form-group">
            <input type="text" class="dokan-form-control dokan-daterangepicker" placeholder="<?php esc_attr_e( 'Select Date Range', 'dokan' ); ?>" value="<?php echo dokan_format_date( $start_date ) . ' - ' . dokan_format_date( $end_date ); ?>" autocomplete="off">
            <input type="hidden" name="start_date_alt" class="dokan-daterangepicker-start-date" value="<?php echo esc_attr( $start_date->format( 'Y-m-d' ) ); ?>" />
            <input type="hidden" name="end_date_alt" class="dokan-daterangepicker-end-date" value="<?php echo esc_attr( $end_date->format( 'Y-m-d' ) ); ?>" />

            <?php wp_nonce_field( 'custom_range', 'dokan_report_filter_nonce' ); ?>
        </div>

        <div class="dokan-form-group">
            <input type="submit" name="dokan_report_filter" class="dokan-btn dokan-btn-success dokan-btn-sm dokan-theme" value="<?php esc_attr_e( 'Show', 'dokan' ); ?>" />
        </div>
    </form>
    <?php
    $sales_by_date->output_report();
}
/**
 * Output the top sellers chart.
 *
 * @since 3.8.0 Rewritten the function
 *
 * @return void
 */
function dokan_top_sellers() {
    $report_manager = new ReportManager();
    $current_user   = dokan_get_current_user_id();
    $start_date     = dokan_current_datetime()->modify( '- 29 days' )->format( 'Y-m-d' );
    $end_date       = dokan_current_datetime()->format( 'Y-m-d' );
    if ( isset( $_POST['dokan_report_filter_top_seller'] ) && isset( $_POST['dokan_report_filter_top_seller_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['dokan_report_filter_top_seller_nonce'] ) ), 'dokan_report_filter_top_seller' ) && isset( $_POST['start_date_alt'] ) && isset( $_POST['end_date_alt'] ) ) {
        $start_date = dokan_current_datetime()
            ->modify( sanitize_text_field( wp_unslash( $_POST['start_date_alt'] ) ) )
            ->format( 'Y-m-d' );
        $end_date   = dokan_current_datetime()
            ->modify( sanitize_text_field( wp_unslash( $_POST['end_date_alt'] ) ) )
            ->format( 'Y-m-d' );
    }
    $data = $report_manager->get_top_selling_data( $current_user, $start_date, $end_date );
    ?>
    <form method="post" action="" class="report-filter dokan-form-inline dokan-clearfix" id="dokan-v-dashboard-reports">
        <div class="dokan-form-group">
            <input type="text" class="dokan-form-control dokan-daterangepicker" placeholder="<?php esc_attr_e( 'Select Date Range', 'dokan' ); ?>" value="<?php echo dokan_format_date( $start_date ) . ' - ' . dokan_format_date( $end_date ); ?>" autocomplete="off">
            <input type="hidden" name="start_date_alt" class="dokan-daterangepicker-start-date" value="<?php echo esc_attr( $start_date ); ?>" />
            <input type="hidden" name="end_date_alt" class="dokan-daterangepicker-end-date" value="<?php echo esc_attr( $end_date ); ?>" />

            <?php wp_nonce_field( 'dokan_report_filter_top_seller', 'dokan_report_filter_top_seller_nonce' ); ?>
        </div>

        <div class="dokan-form-group">
            <input type="submit" name="dokan_report_filter_top_seller" class="dokan-btn dokan-btn-success dokan-btn-sm dokan-theme" value="<?php esc_attr_e( 'Show', 'dokan' ); ?>" />
        </div>
    </form>
    <table class="table table-striped">
        <thead>
        <tr>
            <th><?php esc_html_e( 'Product', 'dokan' ); ?></th>
            <th><?php esc_html_e( 'Sales', 'dokan' ); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php
        if ( empty( $data ) ) :
            printf( '<tr><td colspan="2">%s</td></tr>', __( 'No products found in given range.', 'dokan' ) );
        else :
            foreach ( $data as $order_item ) {
                $product_name = sprintf( '<a href="%1$s" title="%2$s">%3$s</a>', $order_item['url'], $order_item['title'], $order_item['title'] );
                echo '<tr><th style="width: 60%;">' . $product_name . '</th><td style="width: 1%;"><span>' . esc_html( $order_item['sold_qty'] ) . '</span></td></tr>';
            }
        endif;
        ?>
        </tbody>
    </table>
    <?php
}
/**
 * Output the top earners chart.
 *
 * @since 3.8.0 Rewritten the function
 *
 * @return void
 */
function dokan_top_earners() {
    $report_manager = new ReportManager();
    $current_user   = dokan_get_current_user_id();
    $start_date     = dokan_current_datetime()->modify( '- 29 days' )->format( 'Y-m-d' );
    $end_date       = dokan_current_datetime()->format( 'Y-m-d' );
    if ( isset( $_POST['dokan_report_filter_top_earners'] ) && isset( $_POST['dokan_report_filter_top_earners_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['dokan_report_filter_top_earners_nonce'] ) ), 'dokan_report_filter_top_earners' ) && isset( $_POST['start_date_alt'] ) && isset( $_POST['end_date_alt'] ) ) {
        $start_date = dokan_current_datetime()
            ->modify( sanitize_text_field( wp_unslash( $_POST['start_date_alt'] ) ) )
            ->format( 'Y-m-d' );
        $end_date   = dokan_current_datetime()
            ->modify( sanitize_text_field( wp_unslash( $_POST['end_date_alt'] ) ) )
            ->format( 'Y-m-d' );
    }
    $data = $report_manager->get_top_earners_data( $current_user, $start_date, $end_date );
    ?>
    <form method="post" action="" class="report-filter dokan-form-inline dokan-clearfix" id="dokan-v-dashboard-reports">
        <div class="dokan-form-group">
            <input type="text" class="dokan-form-control dokan-daterangepicker" placeholder="<?php esc_attr_e( 'Select Date Range', 'dokan' ); ?>" value="<?php echo dokan_format_date( $start_date ) . ' - ' . dokan_format_date( $end_date ); ?>" autocomplete="off">
            <input type="hidden" name="start_date_alt" class="dokan-daterangepicker-start-date" value="<?php echo esc_attr( $start_date ); ?>" />
            <input type="hidden" name="end_date_alt" class="dokan-daterangepicker-end-date" value="<?php echo esc_attr( $end_date ); ?>" />

            <?php wp_nonce_field( 'dokan_report_filter_top_earners', 'dokan_report_filter_top_earners_nonce' ); ?>
        </div>

        <div class="dokan-form-group">
            <input type="submit" name="dokan_report_filter_top_earners" class="dokan-btn dokan-btn-success dokan-btn-sm dokan-theme" value="<?php esc_attr_e( 'Show', 'dokan' ); ?>" />
        </div>

    </form>
    <table class="table table-striped">
        <thead>
        <tr>
            <th><?php esc_html_e( 'Product', 'dokan' ); ?></th>
            <th><?php esc_html_e( 'Sales', 'dokan' ); ?></th>
            <th><?php esc_html_e( 'Earning', 'dokan' ); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php
        if ( empty( $data ) ) :
            printf( '<tr><td colspan="3">%s</td></tr>', __( 'No products found in given range.', 'dokan' ) );
        else :
            foreach ( $data as $order_item ) {
                $product_name = sprintf( '<a href="%1$s" title="%2$s">%3$s</a>', $order_item['url'], $order_item['title'], $order_item['title'] );
                echo '<tr>
                        <th style="width: 60%;">' . $product_name . '</th>
                        <td style="width: 20%;"><span>' . wc_price( $order_item['sales'] ) . '</span></td>
                        <td style="width: 20%;"><span>' . wc_price( $order_item['total_earning'] ) . '</span></td>
                    </tr>';
            }
        endif;
        ?>
        </tbody>
    </table>
    <?php
}
