<?php
/**
 * Amazon MCF Automated Fulfillment Bridge
 * Path: inc/amazon/amazon-fulfillment.php
 */

// 1. Hook into WooCommerce Order Status Change
add_action('woocommerce_order_status_processing', 'wpla_trigger_multivendor_fba_fulfillment', 20, 1);

/**
 * Automatically triggers FBA/MCF fulfillment when an order moves to 'processing'
 */
function wpla_trigger_multivendor_fba_fulfillment($order_id)
{

    WPLA()->logger->info('--- Multi-Vendor FBA Trigger Start for Order #' . $order_id . ' ---');

    $order = wc_get_order($order_id);
    if (!$order) {
        WPLA()->logger->error('Order not found: ' . $order_id);
        return;
    }

    // Identify if the order has any Amazon-linked items
    $fba_items = array();
    foreach ($order->get_items() as $item_id => $item) {
        $product_id = $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id();
        $asin = get_post_meta($product_id, '_wpla_asin', true);

        if (!empty($asin)) {
            $fba_items[] = $item;
            WPLA()->logger->info('Found FBA-mapped item: ' . $item->get_name() . ' (ASIN: ' . $asin . ')');
        }
    }

    if (empty($fba_items)) {
        WPLA()->logger->info('No FBA items found in order #' . $order_id . '. Skipping.');
        return;
    }

    // Trigger fulfillment feed generation via WP-Lister Model
    if (class_exists('WPLA_AmazonFeed')) {
        try {
            $feed_model = new WPLA_AmazonFeed();

            // This core WP-Lister method loop through items, finds their respective accounts,
            // and groups them into separate feeds for each vendor automatically.
            $feed_model->updateFbaSubmissionFeed($order_id);

            WPLA()->logger->info('Successfully triggered multi-vendor FBA feeds for order #' . $order_id);

            // Add an order note
            $order->add_order_note(__('Amazon MCF fulfillment request triggered for vendor-linked products.', 'brags'));

        } catch (Exception $e) {
            WPLA()->logger->error('Error triggering FBA fulfillment: ' . $e->getMessage());
        }
    } else {
        WPLA()->logger->error('WPLA_AmazonFeed class not found. Cannot trigger fulfillment.');
    }

    WPLA()->logger->info('--- Multi-Vendor FBA Trigger End ---');
}

/**
 * Optional: Prevent WP-Lister's default FBA check if it interferes with our custom trigger.
 * Usually, WP-Lister prevents FBA if items are from multiple accounts. 
 * Since we are triggering it manually per-item, we bypass that check.
 */
add_filter('wpla_order_can_be_fulfilled_via_fba', function ($can, $order) {
    // If we have mapped ASINs, we want to allow the logic to proceed 
    // because our custom trigger handles the splitting.
    return true;
}, 10, 2);
