<?php

use WeDevs\DokanPro\Modules\RequestForQuotation\SettingsHelper;

defined( 'ABSPATH' ) || exit;

do_action( 'dokan_dashboard_wrap_start' );

?>

<div class="dokan-dashboard-wrap">
    <?php do_action( 'dokan_dashboard_content_before' ); ?>

    <div class="dokan-dashboard-content">
        <h3 class="entry-title"><?php esc_html_e( 'Request Quotes', 'dokan' ); ?></h3>

        <article class="dashboard-content-area">
            <?php
            $customer_info = maybe_unserialize( $data['quote']->customer_info );
            $customer_name = ! empty( $customer_info['name_field'] ) ? $customer_info['name_field'] : '';
            $customer_email = ! empty( $customer_info['email_field'] ) ? $customer_info['email_field'] : '';
            ?>
            <div class='woocommerce'>
                <form method='post'>
                    <?php do_action( 'dokan_vendor_request_quote_heading', (object) $data['quote'] ); ?>
                    <?php do_action( 'dokan_vendor_request_quote_details', (object) $data['quote_details'], (object) $data['quote'] ); ?>
                </form>
            </div>
        </article>
    </div>
</div>

<?php do_action( 'dokan_dashboard_wrap_end' ); ?>
