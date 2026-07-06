<?php
// 1. Add a new settings tab in Dokan Seller Dashboard
add_filter('dokan_get_dashboard_settings_nav', function ($settings) {
    $settings['amazon_mcf'] = array(
        'title' => __('Amazon Fulfillment', 'dokan'),
        'icon' => '<i class="fas fa-box"></i>',
        'url' => dokan_get_navigation_url('settings/amazon-mcf'),
        'pos' => 55,
    );
    return $settings;
});

// 2. Load the settings page content
add_action('dokan_render_settings_content', function ($query_var) {
    // Check if we're on the amazon-mcf settings page
    if (!isset($query_var['settings']) || $query_var['settings'] !== 'amazon-mcf') {
        return;
    }
    get_template_part('template-parts/amazon/amazon-mcf');
});

// ========================================================================
// AMAZON OAUTH CALLBACK HANDLER
// ========================================================================

/**
 * Register /amazon-callback/ rewrite endpoint
 */
add_action('init', function () {
    add_rewrite_rule('^amazon-callback/?$', 'index.php?amazon_oauth_callback=1', 'top');
});

add_filter('query_vars', function ($vars) {
    $vars[] = 'amazon_oauth_callback';
    $vars[] = 'spapi_oauth_code';
    $vars[] = 'selling_partner_id';
    $vars[] = 'state';
    return $vars;
});

/**
 * Handle Amazon OAuth callback
 */
add_action('template_redirect', function () {
    global $wp_query;

    if (!get_query_var('amazon_oauth_callback')) {
        return;
    }

    // Get OAuth parameters from URL
    $oauth_code = isset($_GET['spapi_oauth_code']) ? sanitize_text_field($_GET['spapi_oauth_code']) : '';
    $selling_partner_id = isset($_GET['selling_partner_id']) ? sanitize_text_field($_GET['selling_partner_id']) : '';
    $state = isset($_GET['state']) ? sanitize_text_field($_GET['state']) : '';

    // Verify state nonce
    if (!wp_verify_nonce($state, 'brags_sp_api_oauth')) {
        wp_die(__('Invalid OAuth state. Please try again.', 'dokan'));
    }

    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_redirect(wp_login_url(dokan_get_navigation_url('settings/amazon-mcf')));
        exit;
    }

    $user_id = get_current_user_id();

    // Check if user is a seller
    if (!dokan_is_seller_enabled($user_id)) {
        wp_die(__('Only sellers can connect Amazon accounts.', 'dokan'));
    }

    // Exchange authorization code for refresh token
    $creds = brags_get_sp_api_credentials();

    if (empty($creds['client_id']) || empty($creds['client_secret'])) {
        wp_die(__('SP-API credentials not configured. Please contact admin.', 'dokan'));
    }

    // Call Amazon LWA to exchange code for tokens
    $token_response = wp_remote_post('https://api.amazon.co.uk/auth/o2/token', [
        'body' => [
            'grant_type' => 'authorization_code',
            'code' => $oauth_code,
            'redirect_uri' => home_url('/amazon-callback/'),
            'client_id' => $creds['client_id'],
            'client_secret' => $creds['client_secret'],
        ],
        'timeout' => 30,
    ]);

    if (is_wp_error($token_response)) {
        error_log('Amazon OAuth Error: ' . $token_response->get_error_message());
        wc_add_notice(__('Failed to connect Amazon account. Please try again.', 'dokan'), 'error');
        wp_redirect(dokan_get_navigation_url('settings/amazon-mcf'));
        exit;
    }

    $token_body = json_decode(wp_remote_retrieve_body($token_response), true);

    if (isset($token_body['error'])) {
        error_log('Amazon OAuth Error: ' . $token_body['error'] . ' - ' . ($token_body['error_description'] ?? ''));
        wc_add_notice(__('Amazon authorization failed: ', 'dokan') . ($token_body['error_description'] ?? $token_body['error']), 'error');
        wp_redirect(dokan_get_navigation_url('settings/amazon-mcf'));
        exit;
    }

    $refresh_token = isset($token_body['refresh_token']) ? $token_body['refresh_token'] : '';

    if (empty($refresh_token)) {
        wc_add_notice(__('Failed to get refresh token from Amazon.', 'dokan'), 'error');
        wp_redirect(dokan_get_navigation_url('settings/amazon-mcf'));
        exit;
    }

    // Save seller data
    update_user_meta($user_id, 'amazon_seller_id', $selling_partner_id);
    update_user_meta($user_id, 'amazon_marketplace_id', 'A1F83G8C2ARO7P'); // UK
    update_user_meta($user_id, 'amazon_refresh_token', $refresh_token);

    // Sync with WP-Lister Amazon Accounts table
    global $wpdb;
    $table_accounts = $wpdb->prefix . 'amazon_accounts';
    $account_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_accounts WHERE vendor_id = %d", $user_id));

    $account_data = array(
        'title' => 'Amazon Integration - ' . get_userdata($user_id)->display_name,
        'merchant_id' => $selling_partner_id,
        'marketplace_id' => 'A1F83G8C2ARO7P',
        'sp_refresh_token' => $refresh_token,
        'active' => 1,
        'market_id' => 1,
        'market_code' => 'UK',
    );

    if ($account_id) {
        $wpdb->update($table_accounts, $account_data, array('id' => $account_id));
    } else {
        $account_data['vendor_id'] = $user_id;
        $wpdb->insert($table_accounts, $account_data);
    }

    wc_add_notice(__('🎉 Amazon account connected successfully! You can now list products on Amazon.', 'dokan'), 'success');
    wp_redirect(dokan_get_navigation_url('settings/amazon-mcf'));
    exit;
});


// 3. Save the settings
add_action('template_redirect', function () {
    if (!is_user_logged_in())
        return;
    if (!dokan_is_seller_dashboard())
        return;
    if (!isset($_POST['dokan_amazon_mcf_nonce']))
        return;
    if (!wp_verify_nonce($_POST['dokan_amazon_mcf_nonce'], 'dokan_amazon_mcf_settings'))
        return;

    $user_id = get_current_user_id();
    $seller_id = sanitize_text_field($_POST['amazon_seller_id']);
    $marketplace_id = sanitize_text_field($_POST['amazon_marketplace_id']);
    $refresh_token = sanitize_text_field($_POST['amazon_refresh_token']);

    // Save as user meta for reference
    update_user_meta($user_id, 'amazon_seller_id', $seller_id);
    update_user_meta($user_id, 'amazon_marketplace_id', $marketplace_id);
    update_user_meta($user_id, 'amazon_refresh_token', $refresh_token);

    // Sync with WP-Lister Amazon Accounts table
    global $wpdb;
    $table_accounts = $wpdb->prefix . 'amazon_accounts';

    // Check if account already exists for this vendor
    $account_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_accounts WHERE vendor_id = %d", array($user_id)));

    $account_data = array(
        'title' => 'Amazon Integration - ' . get_userdata($user_id)->display_name,
        'merchant_id' => $seller_id,
        'marketplace_id' => $marketplace_id,
        'sp_refresh_token' => $refresh_token,
        'active' => 1,
        'market_id' => 1, // UK
        'market_code' => 'UK',
    );

    if ($account_id) {
        $wpdb->update($table_accounts, $account_data, array('id' => $account_id));
    } else {
        $account_data['vendor_id'] = $user_id;
        $res = $wpdb->insert($table_accounts, $account_data);
        if ($res === false) {
            error_log("Amazon Integration: Failed to insert account for vendor $user_id. Error: " . $wpdb->last_error);
            // Try to add the column again just in case
            if (function_exists('wpla_add_vendor_id_to_accounts_table')) {
                wpla_add_vendor_id_to_accounts_table();
            }
        }
        $account_id = $wpdb->insert_id;
    }

    wc_add_notice(__('Amazon account connected successfully!', 'dokan'));
    wp_safe_redirect(dokan_get_navigation_url('settings/amazon-mcf'));
    exit;
});


// 4. Handle Disconnection
add_action('template_redirect', function () {
    if (!is_user_logged_in())
        return;
    if (!dokan_is_seller_dashboard())
        return;
    if (!isset($_POST['brags_amazon_disconnect_nonce']))
        return;
    if (!wp_verify_nonce($_POST['brags_amazon_disconnect_nonce'], 'brags_amazon_disconnect'))
        return;

    $user_id = get_current_user_id();

    // Clear user meta
    delete_user_meta($user_id, 'amazon_seller_id');
    delete_user_meta($user_id, 'amazon_marketplace_id');
    delete_user_meta($user_id, 'amazon_refresh_token');

    // Remove from WP-Lister Amazon Accounts table
    global $wpdb;
    $table_accounts = $wpdb->prefix . 'amazon_accounts';
    $wpdb->delete($table_accounts, array('vendor_id' => $user_id));

    wc_add_notice(__('Amazon account disconnected successfully.', 'dokan'), 'success');
    wp_safe_redirect(dokan_get_navigation_url('settings/amazon-mcf'));
    exit;
});



function chkt_fix_amazon_menu_dropdown($urls)
{
    // Only show for sellers
    if (!current_user_can('seller') && !current_user_can('dokandar')) {
        return $urls;
    }

    // Safely get base dashboard URL without triggering recursion
    $page_id = (int) dokan_get_option('dashboard', 'dokan_pages', 0);
    $base_url = $page_id ? rtrim(get_permalink($page_id), '/') . '/' : home_url('/dashboard/');

    $urls['amazon'] = array(
        'title' => __('Amazon', 'dokan'),
        'icon' => '<i class="fab fa-amazon"></i>',
        'url' => $base_url . 'amazon-listings/',
        'pos' => 80, // Before Settings
        'submenu' => array(
            'amazon-listings' => array(
                'title' => __('Listings', 'dokan'),
                'url' => $base_url . 'amazon-listings/',
                'icon' => '<i class="fas fa-list"></i>',
            ),
            'amazon-orders' => array(
                'title' => __('Orders', 'dokan'),
                'url' => $base_url . 'amazon-orders/',
                'icon' => '<i class="fas fa-shopping-cart"></i>',
            ),
            'amazon-reports' => array(
                'title' => __('Reports & Logs', 'dokan'),
                'url' => $base_url . 'amazon-reports/',
                'icon' => '<i class="fas fa-file-alt"></i>',
            ),
            // 'amazon-tutorial' => array(
            //     'title' => __('Tutorial', 'dokan'),
            //     'url' => $base_url . 'amazon-tutorial/',
            //     'icon' => '<i class="fas fa-book"></i>',
            // ),
            'amazon-feeds' => array(
                'title' => __('Feeds', 'dokan'),
                'url' => $base_url . 'amazon-feeds/',
                'icon' => '<i class="fas fa-rss"></i>',
            ),
        ),
    );

    return $urls;
}

// Enable Amazon menu for sellers
add_filter('dokan_get_dashboard_nav', 'chkt_fix_amazon_menu_dropdown', 20);

// 1. Register endpoints for Amazon submenus
function chkt_register_amazon_endpoints()
{
    add_rewrite_endpoint('amazon-listings', EP_PAGES);
    add_rewrite_endpoint('amazon-orders', EP_PAGES);
    add_rewrite_endpoint('amazon-settings', EP_PAGES);
    add_rewrite_endpoint('amazon-reports', EP_PAGES);
    add_rewrite_endpoint('amazon-feeds', EP_PAGES);
    add_rewrite_endpoint('amazon-tutorial', EP_PAGES);
}
add_action('init', 'chkt_register_amazon_endpoints');

// 2. Load template for each Amazon submenu
add_action('dokan_load_custom_template', function ($query_vars) {

    // Listings page
    if (isset($query_vars['amazon-listings'])) {
        get_template_part('template-parts/amazon/amazon-listings');
    }

    // Orders page
    if (isset($query_vars['amazon-orders'])) {
        get_template_part('template-parts/amazon/amazon-orders');
    }

    // Settings page
    if (isset($query_vars['amazon-settings'])) {
        get_template_part('template-parts/amazon/amazon-settings');
    }

    // Reports page
    if (isset($query_vars['amazon-reports'])) {
        get_template_part('template-parts/amazon/amazon-reports');
    }

    // Feeds page
    if (isset($query_vars['amazon-feeds'])) {
        get_template_part('template-parts/amazon/amazon-feeds');
    }

    // Tutorial page
    if (isset($query_vars['amazon-tutorial'])) {
        get_template_part('template-parts/amazon/amazon-tutorial');
    }
});


// ========================================================================
// BULK ACTION: "List on Amazon" for Seller Products Page
// ========================================================================

/**
 * Check if seller has connected their Amazon account
 */
function brags_seller_has_amazon_account($user_id = null)
{
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    $refresh_token = get_user_meta($user_id, 'amazon_refresh_token', true);
    return !empty($refresh_token);
}

/**
 * Get seller's Amazon account ID from WP-Lister accounts table
 */
function brags_get_seller_amazon_account_id($user_id = null)
{
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    global $wpdb;
    $table_accounts = $wpdb->prefix . 'amazon_accounts';

    // 1. Try searching by vendor_id
    $account_id = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table_accounts WHERE vendor_id = %d AND active = 1",
        $user_id
    ));

    if ($account_id) {
        return $account_id;
    }

    // 2. Fallback: search by merchant_id stored in user meta
    $merchant_id = get_user_meta($user_id, 'amazon_seller_id', true);
    if ($merchant_id) {
        $account_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_accounts WHERE merchant_id = %s AND active = 1",
            $merchant_id
        ));

        if ($account_id) {
            // Auto-link vendor_id for future efficient lookup
            $wpdb->update($table_accounts, array('vendor_id' => $user_id), array('id' => $account_id));
            error_log("Amazon Integration: Auto-linked account $account_id to vendor $user_id");
            return $account_id;
        }
    }

    error_log("Amazon Integration: No active account found for vendor $user_id (Merchant ID: $merchant_id)");

    // 3. Admin Fallback: If user is an admin, return the first active account
    if (user_can($user_id, 'manage_options')) {
        $account_id = $wpdb->get_var("SELECT id FROM $table_accounts WHERE active = 1 ORDER BY id ASC LIMIT 1");
        if ($account_id) {
            error_log("Amazon Integration: Admin fallback used account $account_id for user $user_id");
            return $account_id;
        }
    }

    return null;
}

/**
 * Show notification on Products page if Amazon account not connected
 */
add_action('dokan_dashboard_content_inside_before', function () {
    // Print WooCommerce notices (errors/success messages)
    if (function_exists('wc_print_notices')) {
        wc_print_notices();
    }

    // Only show on products page
    global $wp;
    if (!isset($wp->query_vars['products'])) {
        return;
    }

    // Only for sellers
    if (!current_user_can('seller') && !current_user_can('dokandar')) {
        return;
    }

    // Check if Amazon account is connected
    if (!brags_seller_has_amazon_account()) {
        $settings_url = dokan_get_navigation_url('settings/amazon-mcf');
        ?>
        <div class="dokan-alert dokan-alert-warning"
            style="margin-bottom: 20px; display: flex; align-items: center; gap: 15px;">
            <i class="fab fa-amazon" style="font-size: 24px;"></i>
            <div>
                <strong><?php _e('Amazon Integration Available!', 'dokan'); ?></strong><br>
                <?php _e('Connect your Amazon Seller Central account to list your products on Amazon and sync inventory.', 'dokan'); ?>
                <a href="<?php echo esc_url($settings_url); ?>" class="dokan-btn dokan-btn-sm dokan-btn-theme"
                    style="margin-left: 15px;">
                    <?php _e('Connect Amazon Account', 'dokan'); ?>
                </a>
            </div>
        </div>
        <?php
    }
});

/**
 * Add "List on Amazon" to bulk actions dropdown
 */
add_filter('dokan_bulk_product_statuses', function ($statuses) {
    // Only for sellers with connected Amazon account
    if (!current_user_can('seller') && !current_user_can('dokandar')) {
        return $statuses;
    }

    // Only show option if Amazon account is connected
    if (brags_seller_has_amazon_account()) {
        $statuses['list_on_amazon'] = __('List on Amazon', 'dokan');
    }

    return $statuses;
});

/**
 * Handle "List on Amazon" bulk action
 * Hooked early into template_redirect to bypass Dokan's potential capability restrictions
 */
add_action('template_redirect', function () {
    if (!isset($_POST['bulk_product_status_change']) || !isset($_POST['status']) || $_POST['status'] !== 'list_on_amazon') {
        return;
    }

    if (!isset($_POST['security']) || !wp_verify_nonce(sanitize_key($_POST['security']), 'bulk_product_status_change')) {
        return;
    }

    if (!isset($_POST['bulk_products']) || !is_array($_POST['bulk_products'])) {
        return;
    }

    $products = array_map('absint', $_POST['bulk_products']);
    $user_id = get_current_user_id();

    // Verify user is logged in and is a seller
    if (!$user_id || (!current_user_can('seller') && !current_user_can('dokandar'))) {
        return;
    }

    // Verify seller has Amazon account
    if (!brags_seller_has_amazon_account($user_id)) {
        wc_add_notice(__('Please connect your Amazon account first in Settings → Amazon Fulfillment', 'dokan'), 'error');
        wp_safe_redirect(dokan_get_navigation_url('products'));
        exit;
    }

    // Get seller's Amazon account ID
    $account_id = brags_get_seller_amazon_account_id($user_id);
    if (!$account_id) {
        wc_add_notice(__('Amazon account not found. Please reconnect in Settings → Amazon Fulfillment.', 'dokan'), 'error');
        wp_safe_redirect(dokan_get_navigation_url('products'));
        exit;
    }

    // Enable WP-Lister to prepare products without profile
    add_filter('wpla_prepare_product_without_profile', '__return_true');

    $prepared_count = 0;
    $skipped_count = 0;
    $errors = array();
    $lm = new WPLA_ListingsModel();

    foreach ($products as $product_id) {
        // Verify product belongs to this seller
        $product_author = get_post_field('post_author', $product_id);
        if ($product_author != $user_id) {
            $skipped_count++;
            continue;
        }

        // Check if product is valid
        $product = wc_get_product($product_id);
        if (!$product) {
            $skipped_count++;
            continue;
        }

        $product_name = $product->get_name();

        // Check if product has SKU (required for Amazon)
        if (empty($product->get_sku())) {
            $errors[] = sprintf(__('"%s" skipped: SKU is required for Amazon listing.', 'dokan'), $product_name);
            $skipped_count++;
            continue;
        }

        // Check if listing already exists for this account
        if ($lm->productExistsInAccount($product_id, $account_id)) {
            $errors[] = sprintf(__('"%s" is already in your Amazon listings.', 'dokan'), $product_name);
            $skipped_count++;
            continue;
        }

        // Prepare listing using WP-Lister
        $res = $lm->prepareProductForListing($product_id, 0, false);

        if ($res && $res !== -1) {
            $listing_id = $res;

            // Manually set account_id and status to ensure correct vendor association
            global $wpdb;
            $wpdb->update(
                $wpdb->prefix . 'amazon_listings',
                array(
                    'account_id' => $account_id,
                    'status' => 'prepared'
                ),
                array('id' => $listing_id)
            );

            $prepared_count++;
        } else {
            $errors[] = sprintf(__('Failed to prepare "%s" for Amazon listing.', 'dokan'), $product_name);
            $skipped_count++;
        }
    }

    // Update pending feeds once after loop
    if (class_exists('WPLA_AmazonFeed')) {
        WPLA_AmazonFeed::updatePendingFeeds();
    }

    // Show results
    if ($prepared_count > 0) {
        wc_add_notice(
            sprintf(
                __('%d product(s) queued for Amazon listing. They will sync on the next update cycle.', 'dokan'),
                $prepared_count
            ),
            'success'
        );
    }

    if ($skipped_count > 0 && empty($errors)) {
        wc_add_notice(
            sprintf(__('%d product(s) were skipped.', 'dokan'), $skipped_count),
            'notice'
        );
    }

    foreach ($errors as $error) {
        wc_add_notice($error, 'notice');
    }

    wp_safe_redirect(dokan_get_navigation_url('products'));
    exit;
}, 5);

/**
 * Handle bulk actions for Amazon Listings
 */
add_action('template_redirect', function () {
    if (!isset($_POST['bulk_amazon_listing_action']) || !isset($_POST['action']) || $_POST['action'] === '-1') {
        return;
    }

    if (!isset($_POST['security']) || !wp_verify_nonce(sanitize_key($_POST['security']), 'bulk_amazon_listing_action')) {
        return;
    }

    if (!isset($_POST['listing_ids']) || !is_array($_POST['listing_ids'])) {
        return;
    }

    $listing_ids = array_map('absint', $_POST['listing_ids']);
    $action = $_POST['action'];
    $current_user_id = get_current_user_id();

    if (empty($listing_ids)) {
        return;
    }

    global $wpdb;
    $table_listings = $wpdb->prefix . 'amazon_listings';
    $table_posts = $wpdb->prefix . 'posts';

    // Verify each listing belongs to the current vendor
    $valid_ids = [];
    foreach ($listing_ids as $id) {
        $author = $wpdb->get_var($wpdb->prepare("
            SELECT p.post_author 
            FROM {$table_listings} al
            LEFT JOIN {$table_posts} p ON al.post_id = p.ID
            WHERE al.id = %d
        ", $id));

        if ($author == $current_user_id) {
            $valid_ids[] = $id;
        }
    }

    if (empty($valid_ids)) {
        return;
    }

    $success_count = 0;
    $lm = new WPLA_ListingsModel();

    foreach ($valid_ids as $id) {
        switch ($action) {
            case 'wpla_resubmit':
                $lm->resubmitItem($id);
                $success_count++;
                break;

            case 'wpla_trash_listing':
                $wpdb->update($table_listings, ['status' => 'trash'], ['id' => $id]);
                $lm->removeASINFromProducts($id);
                $success_count++;
                break;

            case 'wpla_lock':
                $lm->setLockedStatus($id, true);
                $success_count++;
                break;

            case 'wpla_unlock':
                $lm->setLockedStatus($id, false);
                $success_count++;
                break;

            case 'wpla_delete':
                $lm->deleteItem($id);
                $success_count++;
                break;
        }
    }

    if ($success_count > 0) {
        $message = '';
        switch ($action) {
            case 'wpla_resubmit':
                $message = sprintf(__('%d items prepared for resubmission.', 'dokan'), $success_count);
                break;
            case 'wpla_trash_listing':
                $message = sprintf(__('%d items scheduled to be removed from Amazon.', 'dokan'), $success_count);
                break;
            case 'wpla_lock':
                $message = sprintf(__('%d items locked.', 'dokan'), $success_count);
                break;
            case 'wpla_unlock':
                $message = sprintf(__('%d items unlocked.', 'dokan'), $success_count);
                break;
            case 'wpla_delete':
                $message = sprintf(__('%d items deleted from WP-Lister.', 'dokan'), $success_count);
                break;
        }
        wc_add_notice($message, 'success');
    }

    $redirect_url = dokan_get_navigation_url('amazon-listings');
    if (isset($_GET['listing_status'])) {
        $redirect_url = add_query_arg(['listing_status' => $_GET['listing_status']], $redirect_url);
    }

    wp_safe_redirect($redirect_url);
    exit;
}, 5);
