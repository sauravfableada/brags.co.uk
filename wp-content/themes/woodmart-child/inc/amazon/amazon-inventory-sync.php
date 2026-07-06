<?php
/**
 * Amazon Multi-Vendor Inventory Sync Orchestrator
 * Path: inc/amazon/amazon-inventory-sync.php
 * 
 * Ensures all connected vendor accounts have their inventory synced regularly.
 */

// 1. Register frequent sync cron
add_action('init', function () {
    if (!class_exists('WPLA_AmazonAccount'))
        return;

    if (!wp_next_scheduled('brags_amazon_multivendor_inventory_sync')) {
        wp_schedule_event(time(), 'hourly', 'brags_amazon_multivendor_inventory_sync');
    }
});

add_action('brags_amazon_multivendor_inventory_sync', 'brags_amazon_trigger_all_vendor_sync');

/**
 * Triggers an inventory report request for all connected Amazon accounts.
 */
/**
 * Triggers an inventory report request for all connected Amazon accounts.
 */
function brags_amazon_trigger_all_vendor_sync()
{
    if (!class_exists('WPLA_AmazonAccount') || !class_exists('WPLA_Amazon_SP_API')) {
        return;
    }

    WPLA()->logger->info('--- Brags Multi-Vendor Inventory Sync Start ---');

    $accounts = WPLA_AmazonAccount::getAll();
    foreach ($accounts as $account) {
        if (!$account->active)
            continue;
        brags_amazon_request_inventory_report($account);
    }

    WPLA()->logger->info('--- Brags Multi-Vendor Inventory Sync Scheduled ---');
}

/**
 * Handles the actual report request logic
 */
function brags_amazon_request_inventory_report($account)
{
    try {
        $api = new WPLA_Amazon_SP_API($account->id);
        // Use Manage FBA Inventory report (Reliable for FBA UK sync)
        $report_id = $api->createReport('GET_FBA_MYI_UNSUPPRESSED_INVENTORY_DATA');

        if (is_object($report_id) && isset($report_id->ErrorMessage)) {
            WPLA()->logger->error('Error requesting report for account ' . $account->id . ': ' . $report_id->ErrorMessage);
            return false;
        } else {
            $report = $api->getReport($report_id);
            // Process immediately if Amazon returned it already (unlikely but possible)
            WPLA_AmazonReport::processReport($report, $account, true);
            return true;
        }
    } catch (Exception $e) {
        WPLA()->logger->error('Exception during sync for account ' . $account->id . ': ' . $e->getMessage());
        return false;
    }
}

/**
 * Helper to trigger sync for a specific seller immediately.
 */
function brags_amazon_sync_seller_inventory($vendor_id)
{
    // 1. Get vendor's Amazon account ID
    $account_id = function_exists('brags_get_seller_amazon_account_id') ? brags_get_seller_amazon_account_id($vendor_id) : 0;

    if (!$account_id)
        return false;

    $account = new WPLA_AmazonAccount($account_id);
    return brags_amazon_request_inventory_report($account);
}

/**
 * Global Safety Filter Registration
 * Ensures that whenever WP-Lister processes a report, we catch shared SKUs.
 */
add_action('init', function () {
    if (!class_exists('WPLA_ListingsModel'))
        return;

    add_filter('wpla_disable_fba_to_wc_stock_sync', 'brags_amazon_inventory_sync_safety_filter', 10, 2);
});

function brags_amazon_inventory_sync_safety_filter($disable, $listing_item)
{
    // 1. Respect the "List on Amazon" flag
    $list_on_amazon = get_post_meta($listing_item->post_id, '_wpla_list_on_amazon', true);
    if ($list_on_amazon !== 'yes') {
        return true; // Disable sync if not explicitly enabled
    }

    $active_account_id = null;

    // Inspect backtrace to find the report being processed
    // This allows us to know which account the report belongs to
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 15);
    foreach ($backtrace as $step) {
        // Detect WP-Lister report processing functions
        if (isset($step['function']) && (strpos($step['function'], 'processManageFBAReportPage') !== false || strpos($step['function'], 'processFBAReportPage') !== false)) {
            if (isset($step['args'][0]) && is_object($step['args'][0]) && isset($step['args'][0]->account_id)) {
                $active_account_id = intval($step['args'][0]->account_id);
                break;
            }
        }
    }

    // If we've found the active account ID and it DOES NOT match the listing item's account,
    // we've encountered a shared SKU situation where getItemBySKU() found the wrong listing.
    if ($active_account_id && intval($listing_item->account_id) !== $active_account_id) {

        // Find the correct listing for the ACTIVE account
        global $wpdb;
        $table = $wpdb->prefix . 'amazon_listings';
        $correct_item = $wpdb->get_row($wpdb->prepare(
            "SELECT post_id, fba_quantity FROM $table WHERE sku = %s AND account_id = %d",
            $listing_item->sku,
            $active_account_id
        ));

        if ($correct_item && $correct_item->post_id) {
            $fba_qty = intval($correct_item->fba_quantity);
            update_post_meta($correct_item->post_id, '_stock', $fba_qty);
            $status = ($fba_qty > 0) ? 'instock' : 'outofstock';
            update_post_meta($correct_item->post_id, '_stock_status', $status);

            WPLA()->logger->info("Shared SKU Catch: Corrected stock update for SKU {$listing_item->sku} on Account #{$active_account_id} (Post ID: {$correct_item->post_id})");
        }

        return true; // Disable the default (incorrect account) sync
    }

    return $disable;
}
