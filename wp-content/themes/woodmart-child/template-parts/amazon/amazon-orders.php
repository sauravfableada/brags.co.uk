<?php
/**
 * Amazon Orders Page for Sellers (Dokan Dashboard)
 * 
 * Displays WooCommerce orders and their Amazon fulfillment status
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_user_id = get_current_user_id();

// Check if user is logged in and is a seller
if (!is_user_logged_in() || !dokan_is_seller_enabled($current_user_id)) {
    echo '<div class="dokan-alert dokan-alert-warning">' . __('You do not have permission to access this page.', 'dokan') . '</div>';
    return;
}

global $wpdb;

// 1. Get all orders that belong to this seller and have been sent to Amazon
// We use the dokan_orders table to identify vendor-specific orders
$dokan_orders_table = $wpdb->prefix . 'dokan_orders';
$posts_table = $wpdb->prefix . 'posts';

$orders = $wpdb->get_results($wpdb->prepare("
    SELECT do.order_id, p.post_date, p.post_status
    FROM {$dokan_orders_table} do
    JOIN {$posts_table} p ON do.order_id = p.ID
    WHERE do.seller_id = %d
    AND EXISTS (
        SELECT 1 FROM {$wpdb->postmeta} pm 
        WHERE pm.post_id = do.order_id 
        AND pm.meta_key = '_wpla_fba_submission_status'
    )
    ORDER BY p.post_date DESC
", $current_user_id));

?>

<div class="dokan-dashboard-wrap">
    <?php dokan_get_template_part('global/dashboard', 'nav'); ?>

    <div class="dokan-dashboard-content dokan-amazon-orders-content">

        <?php do_action('dokan_dashboard_content_inside_before'); ?>

        <header class="dokan-dashboard-header dokan-clearfix">
            <h1 class="entry-title">
                <i class="fas fa-shopping-cart"></i>
                <?php _e('Amazon Fulfillment Orders', 'dokan'); ?>
            </h1>
        </header>

        <?php if (empty($orders)): ?>
            <div class="dokan-alert dokan-alert-info">
                <?php _e('No orders have been sent to Amazon for fulfillment yet.', 'dokan'); ?>
            </div>
        <?php else: ?>
            <div class="dokan-table-responsive">
                <table class="dokan-table dokan-table-striped">
                    <thead>
                        <tr>
                            <th>
                                <?php _e('Order', 'dokan'); ?>
                            </th>
                            <th>
                                <?php _e('Date', 'dokan'); ?>
                            </th>
                            <th>
                                <?php _e('Items', 'dokan'); ?>
                            </th>
                            <th>
                                <?php _e('Delivery Status', 'dokan'); ?>
                            </th>
                            <th>
                                <?php _e('Amazon Status', 'dokan'); ?>
                            </th>
                            <th class="dokan-text-right">
                                <?php _e('Action', 'dokan'); ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order_res):
                            $order = wc_get_order($order_res->order_id);
                            if (!$order)
                                continue;

                            $submission_status = $order->get_meta('_wpla_fba_submission_status', true);

                            // Amazon Status Styling
                            $amz_status_class = 'dokan-label';
                            switch ($submission_status) {
                                case 'success':
                                case 'shipped':
                                    $amz_status_class .= ' dokan-label-success';
                                    $amz_status_text = __('Fulfilled', 'dokan');
                                    break;
                                case 'pending':
                                    $amz_status_class .= ' dokan-label-warning';
                                    $amz_status_text = __('Queued', 'dokan');
                                    break;
                                case 'failed':
                                    $amz_status_class .= ' dokan-label-danger';
                                    $amz_status_text = __('Failed', 'dokan');
                                    break;
                                default:
                                    $amz_status_class .= ' dokan-label-default';
                                    $amz_status_text = ucfirst($submission_status);
                            }
                            ?>
                            <tr>
                                <td class="dokan-order-id">
                                    <a
                                        href="<?php echo esc_url(wp_nonce_url(add_query_arg(['order_id' => $order->get_id()], dokan_get_navigation_url('orders')), 'dokan_view_order')); ?>">
                                        <strong>#
                                            <?php echo $order->get_order_number(); ?>
                                        </strong>
                                    </a>
                                    <br>
                                    <small>
                                        <?php echo esc_html($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()); ?>
                                    </small>
                                </td>
                                <td>
                                    <?php echo date_i18n(get_option('date_format'), strtotime($order->get_date_created())); ?>
                                </td>
                                <td>
                                    <?php
                                    $items = $order->get_items();
                                    foreach ($items as $item) {
                                        $product = $item->get_product();
                                        if ($product && $product->get_meta('_wpla_asin')) {
                                            echo '<small><i class="fab fa-amazon"></i> ' . esc_html($item->get_name()) . ' x ' . $item->get_quantity() . '</small><br>';
                                        }
                                    }
                                    ?>
                                </td>
                                <td>
                                    <span class="dokan-label dokan-label-default">
                                        <?php echo dokan_get_order_status_class($order->get_status()); ?>
                                        <?php echo esc_html(dokan_get_order_status_translated($order->get_status())); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="<?php echo esc_attr($amz_status_class); ?>">
                                        <?php echo esc_html($amz_status_text); ?>
                                    </span>
                                </td>
                                <td class="dokan-text-right">
                                    <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(['order_id' => $order->get_id()], dokan_get_navigation_url('orders')), 'dokan_view_order')); ?>"
                                        class="dokan-btn dokan-btn-default dokan-btn-sm"
                                        title="<?php _e('View Order', 'dokan'); ?>">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php do_action('dokan_dashboard_content_inside_after'); ?>

    </div>
</div>

<style>
    .dokan-amazon-orders-content .dokan-label {
        display: inline-block;
        padding: 2px 8px;
        font-size: 11px;
        font-weight: 600;
        border-radius: 3px;
        text-transform: uppercase;
    }

    .dokan-amazon-orders-content .dokan-label-success {
        background: #28a745;
        color: #fff;
    }

    .dokan-amazon-orders-content .dokan-label-danger {
        background: #dc3545;
        color: #fff;
    }

    .dokan-amazon-orders-content .dokan-label-warning {
        background: #ffc107;
        color: #212529;
    }

    .dokan-amazon-orders-content .dokan-label-default {
        background: #6c757d;
        color: #fff;
    }

    .dokan-amazon-orders-content .dokan-label-info {
        background: #17a2b8;
        color: #fff;
    }

    .dokan-amazon-orders-content .fab.fa-amazon {
        color: #FF9900;
        margin-right: 5px;
    }
</style>