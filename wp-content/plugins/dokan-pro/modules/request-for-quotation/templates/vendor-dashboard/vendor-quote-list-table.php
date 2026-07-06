<?php
defined( 'ABSPATH' ) || exit;

use WeDevs\DokanPro\Modules\RequestForQuotation\Model\Quote;

do_action( 'dokan_dashboard_wrap_start' );

$search_name = ! empty( $_REQUEST['quote_search_name'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['quote_search_name'] ) ) : ''; // phpcs:ignore
?>

<div class="dokan-dashboard-wrap">
    <?php
    do_action( 'dokan_dashboard_content_before' );
    ?>
    <div class="dokan-dashboard-content">
        <article class="dashboard-content-area">
            <header class="dokan-dashboard-header">
                <h1 class="entry-title"><?php esc_html_e( 'Request for Quotation', 'dokan' ); ?></h1>
            </header>

            <div class="dokan-comments-wrap">
                <form id="dokan-quote-form" action="" method="post">
                    <div class="dokan-form-group dokan-quote-status-filter dokan-w12">
                        <?php
                        /**
                         * Actions for render rfq list status filter.
                         *
                         * @since 3.12.3
                         *
                         * @param array $quote_quotes
                         *
                         * @hooked dokan_request_for_quote_status_filter
                         */
                        do_action( 'dokan_request_for_quote_status_filter', $quote_counts );
                        ?>
                    </div>
                    <div class="dokan-form-group dokan-w12 quote-actions">
                        <div class="dokan-quote-actions">
                            <select name="quote_status" class="dokan-form-control">
                                <option value="none"><?php esc_html_e( 'Bulk Actions', 'dokan' ); ?></option>
                                <?php if ( $quote_status === 'trash' ) : ?>
                                    <option value='<?php echo esc_attr( Quote::STATUS_PENDING ); ?>'>
                                        <?php esc_html_e( 'Pending', 'dokan' ); ?>
                                    </option>
                                <?php else : ?>
                                    <option value='<?php echo esc_attr( Quote::STATUS_TRASH ); ?>'>
                                        <?php esc_html_e( 'Move to Trash', 'dokan' ); ?>
                                    </option>
                                <?php endif; ?>
                            </select>

                            <?php wp_nonce_field( 'dokan_quote_nonce_action', 'dokan_quote_nonce' ); ?>

                            <input type="submit" value="<?php esc_html_e( 'Apply', 'dokan' ); ?>" class="dokan-btn dokan-btn-theme" name="quote_action_submit" />
                        </div>
                        <div class="dokan-quote-search dokan-form-inline dokan-w4">
                            <input type="text" class="dokan-form-control" name="quote_search_name" placeholder="<?php esc_attr_e( 'Search Quotes', 'dokan' ); ?>" value="<?php echo esc_attr( $search_name ); ?>" />
                            <button type="submit" name="quote_listing_search" class="dokan-btn dokan-btn-theme">
                                <?php esc_html_e( 'Search', 'dokan' ); ?>
                            </button>
                        </div>
                    </div>
                    <?php
                    if ( ! empty( $quote_counts ) ) :
                        do_action( 'dokan_request_quote_list', (object) $vendor_all_quotes, $vendor_endpoint, $pagination_html, $quote_counts );
                    else :
                        ?>

                        <div class="woocommerce-MyAccount-content">
                            <div class="woocommerce-notices-wrapper"></div>
                            <div class="woocommerce-message woocommerce-message--info woocommerce-Message woocommerce-Message--info woocommerce-info">
                                <a class="woocommerce-Button button" href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>">
                                    <?php esc_html_e( 'Go to shop', 'dokan' ); ?>
                                </a>
                                <?php echo esc_html__( 'No quote has been made yet.', 'dokan' ); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </article>
    </div>
</div>

<?php do_action( 'dokan_dashboard_wrap_end' ); ?>
