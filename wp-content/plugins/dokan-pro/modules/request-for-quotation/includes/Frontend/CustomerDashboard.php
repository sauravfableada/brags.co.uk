<?php

namespace WeDevs\DokanPro\Modules\RequestForQuotation\Frontend;

use WeDevs\DokanPro\Modules\RequestForQuotation\Helper;
use WeDevs\DokanPro\Modules\RequestForQuotation\Model\Quote;

/**
 * RFQ Customer Responsibility Class.
 *
 * @since 3.12.3
 */
class CustomerDashboard {

    /**
     * Class constructor for hooks class.
     */
    public function __construct() {
        // Add endpoint of quote and process its content.
        add_filter( 'the_title', [ $this, 'dokan_endpoint_title' ] );
        add_filter( 'woocommerce_account_menu_items', [ $this, 'dokan_new_menu_items' ] );
        add_action( 'woocommerce_account_' . DOKAN_MY_ACCOUNT_ENDPOINT . '_endpoint', [ $this, 'dokan_endpoint_content' ] );
        add_action( 'dokan_my_account_request_quote_heading', [ $this, 'my_account_request_quote_heading' ] );
        add_action( 'dokan_my_account_request_quote_details', [ $this, 'request_quote_details' ], 10, 4 );
    }

    /**
     * Dokan new menu items.
     *
     * @since 3.12.3
     *
     * @param array $items
     *
     * @return array
     */
    public function dokan_new_menu_items( $items ): array {
        $menu_items = [
            DOKAN_MY_ACCOUNT_ENDPOINT => esc_html__( 'Request Quotes', 'dokan' ),
        ];

        return array_slice( $items, 0, 2, true ) + $menu_items + array_slice( $items, 1, count( $items ), true );
    }

    /**
     * Dokan endpoint title.
     *
     * @since 3.6.0
     *
     * @param $title
     *
     * @return mixed|string
     */
    public function dokan_endpoint_title( $title ) {
        global $wp_query;
        if ( isset( $wp_query->query_vars[ DOKAN_MY_ACCOUNT_ENDPOINT ] ) && ! is_admin() && is_main_query() && in_the_loop() && is_account_page() ) {
            // New page title.
            $title = esc_html__( 'Requested Quotes', 'dokan' );
            remove_filter( 'the_title', [ $this, 'endpoint_title' ] );
        }

        return $title;
    }

    /**
     * Dokan endpoint content.
     *
     * @since 3.12.3
     *
     * @return void
     */
    public function dokan_endpoint_content() {
        if ( ! is_user_logged_in() ) {
            return;
        }

        $quote_id = get_query_var( DOKAN_MY_ACCOUNT_ENDPOINT );
        if ( ! empty( $quote_id ) ) {
            $data['quote'] = Helper::get_request_quote_by_id( $quote_id );

            if ( empty( $data['quote'] ) ) {
                return;
            }

            $customer_info = ! empty( $data['quote']->customer_info ) ? maybe_unserialize( $data['quote']->customer_info ) : [];
            $customer_id   = (int) ( $customer_info['customer_id'] ?? 0 );

            // Handle customer quote permission before single quote details render.
            if ( ! empty( $customer_info['customer_id'] ) && $customer_id !== dokan_get_current_user_id() ) {
                dokan_get_template_part(
                    'global/dokan-error', '', [
                        'deleted' => false,
                        'message' => __( 'You have no permission to view this requests page', 'dokan' ),
                    ]
                );

                return;
            }

            $data['quote_details'] = Helper::get_request_quote_details_by_quote_id( $quote_id );
            dokan_get_template_part(
                'customer/quote-details-my-account', '', [
                    'quote'                => $data['quote'],
                    'quote_details'        => $data['quote_details'],
                    'request_quote_vendor' => true,
                ]
            );

            return;
        }

        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        $limit        = empty( $_REQUEST['per_page'] ) ? 8 : sanitize_text_field( wp_unslash( $_REQUEST['per_page'] ) );
        $page_no      = isset( $_REQUEST['page_no'] ) ? absint( sanitize_text_field( wp_unslash( $_REQUEST['page_no'] ) ) ) : 1;
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
            'order'          => empty( $_REQUEST['order'] ) ? 'DESC' : sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ),
            'orderby'        => empty( $_REQUEST['orderby'] ) ? 'id' : sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ),
            'user_id'        => dokan_get_current_user_id(),
            'page_no'        => $page_no,
        ];

        $vendor_all_quotes = Helper::get_request_quote( $args );
        $total_count       = Helper::count_request_quote_for_frontend( $args );

        // Filtering the values where all quotes required.
        $status_count = array_filter(
            $total_count, function ( $item ) use ( $get_all, $query_status ) {
                return $get_all ? $item->status !== Quote::STATUS_TRASH : $query_status === $item->status;
            }
        );

        $total_page      = ceil( count( $status_count ) / $args['posts_per_page'] );
        $pagination_html = Hooks::get_pagination( $total_page, $args['page_no'] );

        dokan_get_template_part(
            'customer/quote-list-table', '', [
                'vendor_all_quotes'    => $vendor_all_quotes,
                'pagination_html'      => $pagination_html,
                'account_endpoint'     => DOKAN_ACCOUNT_ENDPOINT,
                'quote_counts'         => $total_count,
                'request_quote_vendor' => true,
            ]
        );
        // phpcs:enable
    }

    /**
     * Customer request quote heading.
     *
     * @since 3.12.3
     *
     * @param $quote
     *
     * @return void
     */
    public function my_account_request_quote_heading( $quote ) {
        if ( ! is_user_logged_in() ) {
            return;
        }

        dokan_get_template_part(
            'quote-heading', '', [
                'quote'                => $quote,
                'updated_by'           => 'Customer',
                'converted_by'         => 'Customer',
                'back_to_quotes'       => wc_get_endpoint_url( 'request-a-quote', '', wc_get_page_permalink( 'myaccount' ) ),
                'request_quote_vendor' => true,
            ]
        );
    }

    /**
     * Customer request quote details.
     *
     * @since 3.12.3
     *
     * @param array     $quote_details
     * @param \stdClass $quote
     *
     * @return void
     */
    public function request_quote_details( $quote_details, $quote ) {
        if ( ! is_user_logged_in() ) {
            return;
        }

        $updated_by        = 'Customer';
        $enable_hide_price = Helper::enable_quote_hide_price_rule();

        dokan_get_template_part(
            'quote_details', '', [
                'quote'                => $quote,
                'quote_details'        => $quote_details,
                'converted_by'         => 'Customer',
                'updated_by'           => $updated_by,
                'hide_price'           => $enable_hide_price,
                'request_quote_vendor' => true,
            ]
        );
    }
}
