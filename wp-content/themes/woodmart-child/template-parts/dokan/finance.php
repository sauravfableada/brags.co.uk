<?php

/**
 * Dokan Category Approval Template
 */

if (!defined('ABSPATH')) {
    exit;
}

global $current_user, $wpdb;
get_header();

if (!dokan_is_user_seller($current_user->ID)) {
    dokan_get_template_part('global/account-denied');
    return;
}

?>
<div class="dokan-dashboard-wrap">
    <?php do_action('dokan_dashboard_content_before'); ?>

    <div class="dokan-dashboard-content">
        <div class="dokan-category-approval">


            <div class="header-div">
                <div class="widget-title">
                    <i class="fas fa-file-invoice-dollar" aria-hidden="true"></i> <?php esc_html_e( 'Finance - Monthly VAT Invoices', 'dokan' ); ?>
                    <span class="pull-right">
                        <!-- <button id="requestCategoryApproval">Category Evaluation Request </button> -->
                    </span>
                </div>

            </div>
            <div>
                <h3>Monthly Seller Plan VAT Invoices from Brags & Partners Ltd</h3>

                <ul class="dokan-finance-invoice-list">
                    <table class="dokan-table dokan-table-striped">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Invoice</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $seller_id = get_current_user_id();
                    
                    $subscription_data = get_dokan_subscription_orders_by_vendor( $seller_id );
                    if (isset($subscription_data['orders'])) {
                        foreach ($subscription_data['orders'] as $order) {
                            $order_id = $order->get_id();

                            // Get the invoice number
                            $bragsy_invoice_number = get_post_meta($order_id, '_bragsy_invoice_number', true);
                            $title = ($bragsy_invoice_number != '') ? $bragsy_invoice_number : $order_id;

                            // Build public URL for download
                            $upload_dir = wp_upload_dir();
                            $pdf_filename = "bragsy-invoice-{$order_id}.pdf";
                            $pdf_url = $upload_dir['baseurl'] . '/bragsy-invoices/' . $pdf_filename;

                            // Check if the file exists before showing download
                            $pdf_path = $upload_dir['basedir'] . '/bragsy-invoices/' . $pdf_filename;
                            $download_button = file_exists($pdf_path)
                                ? "<a href='{$pdf_url}' class='dokan-btn dokan-btn-theme' target='_blank'>
                                        <i class='dashicons dashicons-media-document'></i> Download Invoice
                                </a>"
                                : "<span style='color: #aaa;'>Not available</span>";

                            echo "<tr>
                                <td>#{$title}</td>
                                <td>{$download_button}</td>
                            </tr>";
                        }
                    }

                    ?>
                    </tbody>
                    </table>
                </ul>


                <h3>Your Customer Invoices</h3>
                <ul class="dokan-customer-invoice-list">
                
                    <?php 
                    $args = [
                        'limit'  => 10,  // Number of orders to fetch
                        'paged'  => 1,   // Page number
                        'status' => ['wc-completed', 'wc-processing'], // Order statuses
                    ];

                    $seller_orders = dokan_get_seller_orders(get_current_user_id(), $args);

                    if (!empty($seller_orders)) :
                    ?>
                        <table class="dokan-table dokan-table-striped">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Invoice</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($seller_orders as $order) : 
                                    $order_id = $order->get_id();
                                    $_vat_invoice = get_post_meta($order_id, '_vat_invoice', true);
                                    
                                    if (!empty($_vat_invoice)) :
                                        $invoice_url = wp_upload_dir()['baseurl'] . '/' . basename($_vat_invoice);
                                ?>
                                    <tr>
                                        <td>#<?php echo esc_html($order_id); ?></td>
                                        <td>
                                            <a href="<?php echo esc_url($invoice_url); ?>" class="dokan-btn dokan-btn-theme" target="_blank">
                                                <i class="dashicons dashicons-media-document"></i> Download Invoice
                                            </a>
                                        </td>
                                    </tr>
                                <?php 
                                    endif;
                                endforeach; ?>
                            </tbody>
                        </table>
                    <?php else : ?>
                        <div class="dokan-alert dokan-alert-info">
                            <p>No VAT invoices available yet.</p>
                        </div>
                    <?php endif; ?>
                </ul>

                <h3>Order Transaction Reports</h3>
                <ul class="dokan-customer-invoice-list">
                    <!-- Filter Form -->
                    <form method="get" id="date-filter-form">
                        <label>From: <input type="date" name="from" value="<?php echo esc_attr($_GET['from'] ?? ''); ?>"></label>
                        <label>To: <input type="date" name="to" value="<?php echo esc_attr($_GET['to'] ?? ''); ?>"></label>
                        <button type="submit">Filter</button>
                        <button id="export-csv" type="button">Export CSV</button>
                    </form>

                    <!-- Invoice Table -->
                    <table id="invoice-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Get filters from GET parameters
                            $from = sanitize_text_field($_GET['from'] ?? '');
                            $to = sanitize_text_field($_GET['to'] ?? '');
                            $seller_id = get_current_user_id();

                            // Arguments to get seller orders
                            $args = [
                                'limit' => -1, // Get all orders
                                'paged' => 1,
                                'status' => ['wc-completed', 'wc-processing'],
                            ];

                            // Fetch orders using Dokan's function
                            $orders = dokan_get_seller_orders($seller_id, $args);
                            $csv_data = [];

                            foreach ($orders as $order_id) {
                                $order = wc_get_order($order_id);
                                if (!$order) continue;

                                $order_date = $order->get_date_created() ? $order->get_date_created()->format('Y-m-d') : '';

                                // Apply date filter
                                if ($from && $order_date < $from) continue;
                                if ($to && $order_date > $to) continue;

                                // Display order data in the table
                                echo "<tr>
                                    <td>#" . esc_html($order->get_id()) . "</td>
                                    <td>" . esc_html($order_date) . "</td>
                                    <td>" . wc_price($order->get_total()) . "</td>
                                    <td>" . esc_html(wc_get_order_status_name($order->get_status())) . "</td>
                                </tr>";

                                // Collect data for CSV
                                $order_total = $order->get_total(); // Product Charges

                                // Sample static calculations (replace with actual logic)
                                $seller_fee_ex_vat = $order_total * 0.10; // 10%
                                $seller_fee_vat = $seller_fee_ex_vat * 0.20;
                                $seller_fee_inc = $seller_fee_ex_vat + $seller_fee_vat;

                                $other_fee_ex_vat = $order_total * 0.03; // 3%
                                $other_fee_vat = $other_fee_ex_vat * 0.20;
                                $other_fee_inc = $other_fee_ex_vat + $other_fee_vat;

                                // Add row to CSV data
                                $csv_data[] = [
                                    'Order ID' => $order->get_id(),
                                    'Product Charges' => '£' . number_format($order_total, 2),
                                    'Seller Fees (ex VAT)' => '£' . number_format($seller_fee_ex_vat, 2),
                                    'VAT on Seller Fees (20%)' => '£' . number_format($seller_fee_vat, 2),
                                    'Seller Fees (inc VAT)' => '£' . number_format($seller_fee_inc, 2),
                                    'Other Fees (ex VAT)' => '£' . number_format($other_fee_ex_vat, 2),
                                    'VAT on Other Fees (20%)' => '£' . number_format($other_fee_vat, 2),
                                    'Other Fees (inc VAT)' => '£' . number_format($other_fee_inc, 2),
                                ];
                            }

                            ?>
                        </tbody>
                    </table>
                </ul>

                

            </div>
        </div>
    </div>

    <?php do_action('dokan_dashboard_content_after'); ?>
</div>

<?php get_footer(); ?>
