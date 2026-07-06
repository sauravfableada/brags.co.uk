<?php
defined( 'ABSPATH' ) || exit;

if ( ! empty( $quote_counts ) ) :
    /**
     * Actions for render rfq list status filter.
     *
     * @param array $quote_quotes
     *
     * @hooked dokan_request_for_quote_status_filter
     * @since 3.12.3
     */
    do_action( 'dokan_request_for_quote_status_filter', $quote_counts );

    do_action( 'dokan_request_quote_list', (object) $vendor_all_quotes, $account_endpoint, $pagination_html, $quote_counts );
    ?>

<?php else : ?>

	<div class="woocommerce_account_subscriptions">
		<div class="woocommerce-notices-wrapper"></div>
		<div class="woocommerce-message woocommerce-message--info woocommerce-Message woocommerce-Message--info woocommerce-info">
			<a class="woocommerce-Button button" href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>"><?php echo esc_html__( 'Go to shop', 'dokan' ); ?></a><?php echo esc_html__( 'No quote has been made yet.', 'dokan' ); ?></div>
	</div>
	<?php
endif;
