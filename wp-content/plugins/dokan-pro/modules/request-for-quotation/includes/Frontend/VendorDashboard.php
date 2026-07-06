<?php

namespace WeDevs\DokanPro\Modules\RequestForQuotation\Frontend;

use WeDevs\DokanPro\Modules\RequestForQuotation\Helper;
use WeDevs\DokanPro\Modules\RequestForQuotation\Model\Quote;

/**
 * RFQ Vendor Responsibility Class.
 *
 * @since 3.12.3
 */
class VendorDashboard {

    /**
     * Class constructor for hooks class.
     */
    public function __construct() {
        add_filter( 'dokan_get_dashboard_nav', [ $this, 'add_quote_menu' ] );
        add_action( 'dokan_load_custom_template', [ $this, 'load_quote_template' ] );
        add_action( 'dokan_vendor_request_quote_heading', [ $this, 'vendor_request_quote_heading' ] );
        add_action( 'dokan_vendor_request_quote_details', [ $this, 'vendor_request_quote_details' ], 10, 2 );
        add_filter( 'dokan_dashboard_settings_heading_title', [ $this, 'load_settings_header' ], 12, 2 );
        add_action( 'dokan_request_for_quote_status_filter', [ $this, 'dokan_quote_status_filter' ] );
        add_action( 'template_redirect', [ $this, 'handle_quote_bulk_actions' ] );
        add_action( 'dokan_request_quote_list', [ $this, 'dokan_request_quote_list' ], 10, 4 );
    }

    /**
     * Add vendor rma menu
     *
     * @since 3.12.3
     *
     * @return void
     */
    public function add_quote_menu( $urls ) {
        $menu = [
            'title'       => __( 'Request Quotes', 'dokan' ),
            'icon'        => '<i class="fa fa-list" aria-hidden="true"></i>',
            'url'         => dokan_get_navigation_url( DOKAN_VENDOR_ENDPOINT ),
            'pos'         => 53,
            'icon_name'   => 'ShoppingBag',
            'permission'  => 'dokan_view_request_quote_menu',
            'react_route' => DOKAN_VENDOR_ENDPOINT,
        ];

        if ( dokan_is_seller_enabled( dokan_get_current_user_id() ) ) {
            $urls[ DOKAN_VENDOR_ENDPOINT ] = $menu;
        }

        return $urls;
    }

    /**
     * Load quote template for vendor
     *
     * @since 3.12.3
     *
     * @return void
     */
    public function load_quote_template( $query_vars ) {
        if ( ! isset( $query_vars[ DOKAN_VENDOR_ENDPOINT ] ) ) {
            return;
        }

        if ( ! is_user_logged_in() ) {
            dokan_get_template_part(
                'global/dokan-error', '', [
                    'deleted' => false,
                    'message' => __( 'You have no permission to view this requests page', 'dokan' ),
                ]
            );

            return;
        }

        $quote_id = get_query_var( DOKAN_VENDOR_ENDPOINT );
        if ( ! empty( $quote_id ) ) {
            $data['quote'] = Helper::get_request_quote_vendor_by_id( $quote_id, dokan_get_current_user_id() );
            if ( empty( $data['quote'] ) ) {
                dokan_get_template_part(
                    'global/dokan-error', '', [
                        'deleted' => false,
                        'message' => __( 'You have no permission to view this requests page', 'dokan' ),
                    ]
                );

                return;
            }
            $data['quote_details'] = Helper::get_request_quote_details_by_vendor_id( $quote_id, dokan_get_current_user_id() );
            dokan_get_template_part(
                'vendor-dashboard/vendor-quote-details', '', [
                    'vendor_endpoint'      => DOKAN_VENDOR_ENDPOINT,
                    'quote_id'             => $quote_id,
                    'data'                 => $data,
                    'request_quote_vendor' => true,
                ]
            );

            return;
        }

        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        $limit        = empty( $_REQUEST['per_page'] ) ? 8 : sanitize_text_field( wp_unslash( $_REQUEST['per_page'] ) );
        $page_no      = isset( $_REQUEST['page_no'] ) ? absint( wp_unslash( $_REQUEST['page_no'] ) ) : 1;
        $get_all      = ( empty( $_REQUEST['quote_status'] ) || $_REQUEST['quote_status'] === 'all' );
        $offset       = ( $page_no * $limit ) - $limit;
        $query_status = '';

        if ( isset( $_REQUEST['seller_quote_filter_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['seller_quote_filter_nonce'] ) ), 'seller-quote-filter-nonce' ) ) {
            $query_status = ! $get_all ? sanitize_text_field( wp_unslash( $_REQUEST['quote_status'] ) ) : $query_status;
        }

        $args = [
            'posts_per_page' => $limit,
            'offset'         => $offset,
            'status'         => $query_status,
            'author_id'      => dokan_get_current_user_id(),
            'order'          => empty( $_REQUEST['order'] ) ? 'DESC' : sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ),
            'orderby'        => empty( $_REQUEST['orderby'] ) ? 'id' : sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ),
            'page_no'        => $page_no,
        ];

        if ( isset( $_REQUEST['quote_listing_search'] ) && ! empty( $_REQUEST['quote_search_name'] ) ) {
            $args['s'] = sanitize_text_field( wp_unslash( $_REQUEST['quote_search_name'] ) );
        }

        $vendor_all_quotes = Helper::get_request_quote_for_vendor( $args );
        $total_count       = Helper::count_request_quote_for_vendor( $args );

        // Filtering the values where all quotes required.
        $status_count = array_filter(
            $total_count, function ( $item ) use ( $get_all, $query_status ) {
                return $get_all ? $item->status !== Quote::STATUS_TRASH : $query_status === $item->status;
            }
        );

        $total_page = ceil( count( $status_count ) / $args['posts_per_page'] );

        $pagination_html = Hooks::get_pagination( $total_page, $args['page_no'] );

        dokan_get_template_part(
            'vendor-dashboard/vendor-quote-list-table', '', [
                'vendor_endpoint'      => DOKAN_VENDOR_ENDPOINT,
                'vendor_all_quotes'    => $vendor_all_quotes,
                'pagination_html'      => $pagination_html,
                'quote_status'         => $query_status,
                'quote_counts'         => $total_count,
                'request_quote_vendor' => true,
            ]
        );

        // phpcs:enable
    }

    /**
     * Vendor request quote heading.
     *
     * @since 3.12.3
     *
     * @param \stdClass $quote
     *
     * @return void
     */
    public function vendor_request_quote_heading( $quote ) {
        if ( ! is_user_logged_in() ) {
            return;
        }

        $customer_info = ! empty( $quote->customer_info ) ? maybe_unserialize( $quote->customer_info ) : [];
        dokan_get_template_part(
            'vendor-dashboard/vendor_request_quote_heading', '', [
                'quote'                => $quote,
                'customer_info'        => $customer_info,
                'request_quote_vendor' => true,
            ]
        );
    }

    /**
     * Vendor request quote details.
     *
     * @since 3.12.3
     *
     * @param array     $quote_details
     * @param \stdClass $quote
     *
     * @return void
     */
    public function vendor_request_quote_details( $quote_details, $quote ) {
        if ( ! is_user_logged_in() ) {
            return;
        }

        $updated_by = 'Vendor';
        dokan_get_template_part(
            'quote_details', '', [
                'quote'                => $quote,
                'quote_details'        => $quote_details,
                'approved_by_vendor'   => true,
                'converted_by'         => 'Vendor',
                'updated_by'           => $updated_by,
                'request_quote_vendor' => true,
            ]
        );
    }

    /**
     * Load Settings Header
     *
     * @since 3.12.3
     *
     * @param string $header
     * @param string $query_vars
     *
     * @return string
     */
    public function load_settings_header( string $header, string $query_vars ): string {
        if ( DOKAN_VENDOR_ENDPOINT === $query_vars ) {
            $header = __( 'Request Quote', 'dokan' );
        }

        return $header;
    }

    /**
     * Filter and display quote statuses on the dashboard.
     *
     * @since 3.12.3
     *
     * @param mixed $quote_counts Quote counts.
     *
     * @return void
     */
    public function dokan_quote_status_filter( $quote_counts ) {
        $query_status = 'all';
        if ( isset( $_REQUEST['seller_quote_filter_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['seller_quote_filter_nonce'] ) ), 'seller-quote-filter-nonce' ) ) {
            $query_status = ! empty( $_REQUEST['quote_status'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['quote_status'] ) ) : $query_status;
        }

        $orders_url = dokan_is_seller_dashboard() ? dokan_get_navigation_url( 'requested-quotes' ) : wc_get_endpoint_url( 'request-a-quote', '', wc_get_page_permalink( 'myaccount' ) );

        // Initialize status counts
        $status_counts  = $this->calculate_status_counts( $quote_counts );
        $total_quotes   = count( (array) $quote_counts );
        $trashed_quotes = $status_counts['trash'] ?? 0;
        $all_quotes     = $total_quotes - $trashed_quotes;
        $filter_nonce   = wp_create_nonce( 'seller-quote-filter-nonce' );

        $quote_statuses = apply_filters(
            'dokan_vendor_dashboard_quote_listing_statuses',
            array_merge(
                [ 'all' => __( 'All', 'dokan' ) ],
                Quote::get_quote_statuses()
            )
        );

        $this->render_status_filter( $quote_counts, $quote_statuses, $status_counts, $all_quotes, $query_status, $orders_url, $filter_nonce );
    }

    /**
     * Process bulk action.
     *
     * @since 3.12.3
     *
     * @return void
     */
    public function handle_quote_bulk_actions() {
        // Check if the bulk action submit button is set.
        if ( ! isset( $_REQUEST['quote_action_submit'] ) ) {
            return;
        }

        // Verify the nonce and check if the user is logged in.
        if ( ! isset( $_REQUEST['dokan_quote_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['dokan_quote_nonce'] ) ), 'dokan_quote_nonce_action' ) || ! is_user_logged_in() ) {
            return;
        }

        // Set the redirection URL.
        $redirect_to = dokan_get_navigation_url( 'requested-quotes' );

        // Check if the bulk quotes request is empty and redirect if true.
        if ( empty( $_REQUEST['bulk_quotes'] ) ) {
            wp_safe_redirect( $redirect_to );
            exit;
        }

        // Sanitize the action and quote IDs.
        $action    = ! empty( $_REQUEST['quote_status'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['quote_status'] ) ) : '';
        $quote_ids = wc_clean( wp_unslash( $_REQUEST['bulk_quotes'] ) );

        // Loop through each quote ID and change the status.
        foreach ( $quote_ids as $quote_id ) {
            Helper::change_status( 'dokan_request_quotes', $quote_id, $action );
        }

        // Redirect to the specified URL.
        wp_safe_redirect( $redirect_to );
        exit;
    }

    /**
     * Request quote list table.
     *
     * @since 3.12.3
     *
     * @param \stdClass $vendor_all_quotes
     * @param string    $endpoint
     * @param string    $pagination_html
     * @param array     $quote_counts
     *
     * @return void
     */
    public function dokan_request_quote_list( $vendor_all_quotes, $endpoint, $pagination_html, $quote_counts ) {
        if ( ! is_user_logged_in() ) {
            return;
        }

        dokan_get_template_part(
            'quote-list', '', [
                'vendor_all_quotes'    => $vendor_all_quotes,
                'endpoint'             => $endpoint,
                'pagination_html'      => $pagination_html,
                'quote_counts'         => $quote_counts,
                'request_quote_vendor' => true,
            ]
        );
    }

    /**
     * Calculate status counts for quotes.
     *
     * @since 3.12.3
     *
     * @param mixed $quote_counts Quote counts.
     *
     * @return array Calculated status counts.
     */
    private function calculate_status_counts( $quote_counts ) {
        $status_counts = [];

        $quote_counts = ! empty( $quote_counts ) && ! is_array( $quote_counts ) ? [ $quote_counts ] : $quote_counts;
        foreach ( $quote_counts as $item ) {
            $status                   = $item->status ?? '';
            $status_counts[ $status ] = ( $status_counts[ $status ] ?? 0 ) + 1;
        }

        return $status_counts;
    }

    /**
     * Render the status filter.
     *
     * @since 3.12.3
     *
     * @param array  $quote_counts   Quote counts.
     * @param array  $quote_statuses Array of quote statuses.
     * @param array  $status_counts  Array of status counts.
     * @param int    $all_quotes     Total count of all quotes.
     * @param string $query_status   Current query status.
     * @param string $orders_url     Orders URL.
     * @param string $filter_nonce   Filter nonce.
     *
     * @return void
     */
    private function render_status_filter( $quote_counts, $quote_statuses, $status_counts, $all_quotes, $query_status, $orders_url, $filter_nonce ) {
        ?>
        <ul class='list-inline quote-statuses-filter subsubsub dokan-form-inline dokan-w10'>
            <?php foreach ( $quote_statuses as $status_key => $status_label ) : ?>
                <?php
                $customer_exclude_status_list = [
                    Quote::STATUS_TRASH,
                    Quote::STATUS_EXPIRED,
                    Quote::STATUS_REJECT,
                    Quote::STATUS_CANCEL,
                ];

                if ( ! dokan_is_seller_dashboard() && in_array( $status_key, $customer_exclude_status_list, true ) ) {
                    continue;
                }

                $url_args = [
                    'quote_status'              => $status_key,
                    'seller_quote_filter_nonce' => $filter_nonce,
                ];

                $status_url            = add_query_arg( $url_args, $orders_url );
                $active_class          = $query_status === $status_key ? 'active' : '';
                $status_order_count    = $status_counts[ $status_key ] ?? 0;
                $formatted_order_count = $status_key === 'all' ? number_format_i18n( $all_quotes ) : number_format_i18n( $status_order_count );
                ?>
                <li class="<?php echo esc_attr( $active_class ); ?>">
                    <a href="<?php echo esc_url( $status_url ); ?>">
                        <?php
                        printf(
                        /* translators: 1) Status label, 2) Formatted order count. */
                            esc_html__( '%1$s (%2$s)', 'dokan' ),
                            $status_label,
                            $formatted_order_count
                        );
                        ?>
                    </a>
                </li>
            <?php endforeach; ?>

            <?php do_action( 'dokan_status_listing_item', $quote_counts ); ?>
        </ul>
        <?php
    }
}
