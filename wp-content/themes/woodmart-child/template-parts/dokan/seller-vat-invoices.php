<?php
/**
 * Dokan vat invoice  Template
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
        <h3 class="dokan-dashboard-heading">VAT Invoices</h3>

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
    </div>

    <?php do_action('dokan_dashboard_content_after'); ?>
</div>

<?php get_footer(); ?>