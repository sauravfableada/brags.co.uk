<?php
/**
 * Amazon Analytics Dashboard Widget
 * Path: inc/amazon/amazon-analytics-dashboard.php
 * 
 * Displays Amazon listing statistics in the Dokan vendor dashboard
 */

// Add Amazon Analytics Widget to Dokan Dashboard
//add_action('dokan_dashboard_content_inside_before', 'brags_amazon_analytics_dashboard_widget', 15);

function brags_amazon_analytics_dashboard_widget()
{
    if (!class_exists('WPLA_ListingsModel')) {
        return;
    }

    $vendor_id = dokan_get_current_user_id();

    // Get vendor's Amazon account ID
    $account_id = get_user_meta($vendor_id, '_wpla_amazon_account_id', true);

    if (!$account_id) {
        // Show setup message if no Amazon account connected
        brags_amazon_show_setup_message();
        return;
    }

    // Get statistics
    $stats = brags_get_amazon_statistics($vendor_id, $account_id);

    // Display dashboard widget
    brags_render_amazon_dashboard($stats);
}

/**
 * Get Amazon statistics for vendor
 */
function brags_get_amazon_statistics($vendor_id, $account_id)
{
    global $wpdb;

    $listings_table = $wpdb->prefix . 'amazon_listings';
    $orders_table = $wpdb->prefix . 'amazon_orders';

    // Get vendor's product IDs
    $product_ids = get_posts(array(
        'post_type' => 'product',
        'author' => $vendor_id,
        'posts_per_page' => -1,
        'fields' => 'ids',
        'post_status' => 'any'
    ));

    if (empty($product_ids)) {
        return array(
            'total_listings' => 0,
            'fba_count' => 0,
            'fbm_count' => 0,
            'error_count' => 0,
            'recent_orders' => array()
        );
    }

    $product_ids_str = implode(',', $product_ids);

    // Total listings
    $total_listings = $wpdb->get_var("
        SELECT COUNT(*) 
        FROM $listings_table 
        WHERE post_id IN ($product_ids_str) 
        AND account_id = $account_id
    ");

    // FBA count
    $fba_count = $wpdb->get_var("
        SELECT COUNT(*) 
        FROM $listings_table 
        WHERE post_id IN ($product_ids_str) 
        AND account_id = $account_id
        AND (fba_fcid IS NOT NULL AND fba_fcid != '' AND fba_fcid != 'DEFAULT')
    ");

    // FBM count
    $fbm_count = $total_listings - $fba_count;

    // Error count (listings with errors or failed status)
    $error_count = $wpdb->get_var("
        SELECT COUNT(*) 
        FROM $listings_table 
        WHERE post_id IN ($product_ids_str) 
        AND account_id = $account_id
        AND (status = 'failed' OR status = 'error')
    ");

    // Recent orders (last 30 days)
    $thirty_days_ago = date('Y-m-d H:i:s', strtotime('-30 days'));
    $recent_orders_count = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(DISTINCT o.id) 
        FROM $orders_table o
        INNER JOIN {$wpdb->prefix}posts p ON o.post_id = p.ID
        WHERE p.post_author = %d
        AND o.account_id = %d
        AND o.date_created >= %s
    ", $vendor_id, $account_id, $thirty_days_ago));

    // Get listing status breakdown
    $status_breakdown = $wpdb->get_results("
        SELECT status, COUNT(*) as count 
        FROM $listings_table 
        WHERE post_id IN ($product_ids_str) 
        AND account_id = $account_id
        GROUP BY status
    ", ARRAY_A);

    return array(
        'total_listings' => (int) $total_listings,
        'fba_count' => (int) $fba_count,
        'fbm_count' => (int) $fbm_count,
        'error_count' => (int) $error_count,
        'recent_orders' => (int) $recent_orders_count,
        'status_breakdown' => $status_breakdown
    );
}

/**
 * Render Amazon Analytics Dashboard
 */
function brags_render_amazon_dashboard($stats)
{
    ?>
    <div class="brags-amazon-analytics-dashboard">
        <div class="dashboard-header">
            <h2>
                <i class="fab fa-amazon"></i>
                <?php _e('Amazon Listings Overview', 'dokan'); ?>
            </h2>
        </div>

        <div class="amazon-stats-grid">
            <!-- Total Listings Card -->
            <div class="stat-card stat-card-primary">
                <div class="stat-icon">
                    <i class="fas fa-boxes"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value">
                        <?php echo number_format($stats['total_listings']); ?>
                    </div>
                    <div class="stat-label">
                        <?php _e('Total Listings', 'dokan'); ?>
                    </div>
                </div>
            </div>

            <!-- FBA Count Card -->
            <div class="stat-card stat-card-success">
                <div class="stat-icon">
                    <i class="fas fa-warehouse"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value">
                        <?php echo number_format($stats['fba_count']); ?>
                    </div>
                    <div class="stat-label">
                        <?php _e('FBA Products', 'dokan'); ?>
                    </div>
                    <?php if ($stats['total_listings'] > 0): ?>
                        <div class="stat-percentage">
                            <?php echo round(($stats['fba_count'] / $stats['total_listings']) * 100); ?>%
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- FBM Count Card -->
            <div class="stat-card stat-card-info">
                <div class="stat-icon">
                    <i class="fas fa-shipping-fast"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value">
                        <?php echo number_format($stats['fbm_count']); ?>
                    </div>
                    <div class="stat-label">
                        <?php _e('Merchant Fulfilled', 'dokan'); ?>
                    </div>
                    <?php if ($stats['total_listings'] > 0): ?>
                        <div class="stat-percentage">
                            <?php echo round(($stats['fbm_count'] / $stats['total_listings']) * 100); ?>%
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Errors Card -->
            <div class="stat-card stat-card-danger">
                <div class="stat-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value">
                        <?php echo number_format($stats['error_count']); ?>
                    </div>
                    <div class="stat-label">
                        <?php _e('Listing Errors', 'dokan'); ?>
                    </div>
                    <?php if ($stats['error_count'] > 0): ?>
                        <a href="<?php echo admin_url('admin.php?page=wpla&tab=listings&status=error'); ?>" class="stat-link">
                            <?php _e('View Errors', 'dokan'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Orders Card -->
            <div class="stat-card stat-card-warning">
                <div class="stat-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value">
                        <?php echo number_format($stats['recent_orders']); ?>
                    </div>
                    <div class="stat-label">
                        <?php _e('Orders (30 Days)', 'dokan'); ?>
                    </div>
                </div>
            </div>

            <!-- Status Breakdown Card -->
            <div class="stat-card stat-card-secondary">
                <div class="stat-icon">
                    <i class="fas fa-chart-pie"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">
                        <?php _e('Listing Status', 'dokan'); ?>
                    </div>
                    <div class="status-breakdown">
                        <?php if (!empty($stats['status_breakdown'])): ?>
                            <?php foreach ($stats['status_breakdown'] as $status): ?>
                                <div class="status-item">
                                    <span class="status-badge status-<?php echo esc_attr($status['status']); ?>">
                                        <?php echo ucfirst($status['status']); ?>
                                    </span>
                                    <span class="status-count">
                                        <?php echo $status['count']; ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="no-data">
                                <?php _e('No listings yet', 'dokan'); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="amazon-quick-actions">
            <a href="<?php echo dokan_get_navigation_url('products'); ?>" class="btn btn-primary">
                <i class="fas fa-plus"></i>
                <?php _e('Add Products to Amazon', 'dokan'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=wpla'); ?>" class="btn btn-secondary">
                <i class="fas fa-cog"></i>
                <?php _e('Manage Amazon Settings', 'dokan'); ?>
            </a>
        </div>
    </div>
    <?php
}

/**
 * Show setup message if Amazon not connected
 */
function brags_amazon_show_setup_message()
{
    ?>
    <div class="brags-amazon-setup-message">
        <div class="setup-card">
            <div class="setup-icon">
                <i class="fab fa-amazon"></i>
            </div>
            <h3>
                <?php _e('Connect Your Amazon Seller Account', 'dokan'); ?>
            </h3>
            <p>
                <?php _e('Start selling on Amazon by connecting your seller account. Manage your listings, inventory, and orders all from one place.', 'dokan'); ?>
            </p>
            <a href="<?php echo admin_url('admin.php?page=wpla-settings&tab=account'); ?>" class="btn btn-primary btn-lg">
                <i class="fas fa-link"></i>
                <?php _e('Connect Amazon Account', 'dokan'); ?>
            </a>
        </div>
    </div>
    <?php
}

// Enqueue Dashboard Styles
add_action('wp_enqueue_scripts', 'brags_amazon_dashboard_styles');

function brags_amazon_dashboard_styles()
{
    if (dokan_is_seller_dashboard()) {
        wp_enqueue_style(
            'brags-amazon-dashboard',
            get_stylesheet_directory_uri() . '/inc/amazon/assets/amazon-dashboard.css',
            array(),
            '1.0.0'
        );
    }
}
