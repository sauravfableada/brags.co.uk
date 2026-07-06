<?php
/**
 * Amazon Listings Page for Sellers (Dokan Dashboard)
 * 
 * Displays WP-Lister Amazon listings filtered by current vendor
 */

if (!defined('ABSPATH')) {
    exit;
}

// Check if user is logged in and is a seller
if (!is_user_logged_in() || !dokan_is_seller_enabled(get_current_user_id())) {
    echo '<div class="dokan-alert dokan-alert-warning">' . __('You do not have permission to access this page.', 'dokan') . '</div>';
    return;
}

$current_user_id = get_current_user_id();
global $wpdb;

// Get listings from WP-Lister Amazon table filtered by current vendor's products
$amazon_listings_table = $wpdb->prefix . 'amazon_listings';
$posts_table = $wpdb->prefix . 'posts';

// Check if WP-Lister Amazon table exists
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$amazon_listings_table'") === $amazon_listings_table;

?>

<div class="dokan-dashboard-wrap">
    <?php
    /**
     * Include Dokan dashboard sidebar
     */
    dokan_get_template_part('global/dashboard', 'nav');
    ?>

    <div class="dokan-dashboard-content dokan-amazon-listings-content">

        <?php do_action('dokan_dashboard_content_inside_before'); ?>

        <header class="dokan-dashboard-header">
            <h1 class="entry-title"><?php _e('Amazon Listings', 'dokan'); ?></h1>
        </header>

        <style>
            .subsubsub {
                list-style: none;
                margin: 8px 0 0;
                padding: 0;
                font-size: 13px;
                float: left;
                color: #666;
            }

            .subsubsub li {
                display: inline-block;
                margin: 0;
                padding: 0;
                white-space: nowrap;
            }

            .subsubsub a {
                line-height: 2;
                padding: .2em;
                text-decoration: none;
            }

            .subsubsub a.current {
                font-weight: 600;
                border: none;
                color: #000;
            }

            .subsubsub .count {
                color: #555;
                font-weight: 400;
            }

            .dokan-bulk-action-wrapper {
                background: #fdfdfd;
                padding: 10px;
                border: 1px solid #efefef;
                border-radius: 4px;
                margin-bottom: 15px !important;
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .dokan-bulk-action-wrapper select {
                min-width: 200px;
            }

            .dokan-label-warning {
                background-color: #f0ad4e;
            }

            .dokan-label-danger {
                background-color: #d9534f;
            }

            .dokan-label-success {
                background-color: #5cb85c;
            }

            .dokan-label-info {
                background-color: #5bc0de;
            }

            .dokan-table thead th {
                background-color: #f9f9f9;
                font-weight: 600;
            }

            .dokan-table tbody tr:hover {
                background-color: #fcfcfc;
            }

            .check-column {
                width: 2.2em;
                padding: 11px 10px 0 10px !important;
                text-align: center;
            }

            .dokan-product-listing-filter {
                background: #fff;
                padding: 15px;
                border: 1px solid #efefef;
                border-radius: 4px;
                margin-bottom: 20px;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
            }

            .dokan-form-inline .dokan-form-group {
                margin-right: 10px;
            }

            .text-muted {
                color: #888;
            }

            .listing-error-box {
                font-size: 11px;
                color: #d9534f;
                padding: 5px 8px;
                background: #fff5f5;
                border-left: 2px solid #d9534f;
                margin-top: 8px;
                border-radius: 2px;
                max-width: 250px;
                word-wrap: break-word;
            }
        </style>

        <?php
        // Get filter values
        $listing_status = isset($_GET['listing_status']) ? sanitize_text_field($_GET['listing_status']) : 'all';
        $search_term = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $profile_id = isset($_GET['profile_id']) ? intval($_GET['profile_id']) : 0;
        $account_id_filter = isset($_GET['account_id']) ? intval($_GET['account_id']) : 0;

        // Initialize counts
        $status_counts = [];
        $total_count = 0;
        $total_locked = 0;
        $total_unlocked = 0;
        $total_instock = 0;
        $total_outstock = 0;

        if ($table_exists) {
            // Optimized: Single query for all status counts
            $status_data = $wpdb->get_results($wpdb->prepare("
                SELECT 
                    al.status,
                    SUM(CASE WHEN al.locked = 1 THEN 1 ELSE 0 END) as locked_count,
                    SUM(CASE WHEN (al.locked = 0 OR al.locked IS NULL) THEN 1 ELSE 0 END) as unlocked_count,
                    SUM(CASE WHEN al.quantity > 0 THEN 1 ELSE 0 END) as instock_count,
                    SUM(CASE WHEN al.quantity <= 0 THEN 1 ELSE 0 END) as outstock_count,
                    COUNT(*) as count
                FROM {$amazon_listings_table} al
                INNER JOIN {$posts_table} p ON al.post_id = p.ID 
                WHERE p.post_author = %d 
                GROUP BY al.status
            ", $current_user_id));

            if ($status_data) {
                foreach ($status_data as $row) {
                    $status_counts[$row->status] = $row->count;
                    $total_count += $row->count;
                    $total_locked += $row->locked_count;
                    $total_unlocked += $row->unlocked_count;
                    $total_instock += $row->instock_count;
                    $total_outstock += $row->outstock_count;
                }
            }
        }

        // Build list of statuses for filters
        $statuses = [
            'all' => ['label' => __('All', 'dokan'), 'count' => $total_count],
            'prepared' => ['label' => __('Prepared', 'dokan')],
            'online' => ['label' => __('Online', 'dokan')],
            'changed' => ['label' => __('Changed', 'dokan')],
            'matched' => ['label' => __('Matched', 'dokan')],
            'failed' => ['label' => __('Failed', 'dokan')],
            'imported' => ['label' => __('Import Queue', 'dokan')],
            'ended' => ['label' => __('Ended', 'dokan')],
            'trash' => ['label' => __('Trash', 'dokan')],
            'instock' => ['label' => __('In Stock', 'dokan'), 'count' => $total_instock],
            'outstock' => ['label' => __('No Stock', 'dokan'), 'count' => $total_outstock],
            'locked' => ['label' => __('Locked', 'dokan'), 'count' => $total_locked],
            'unlocked' => ['label' => __('Unlocked', 'dokan'), 'count' => $total_unlocked],
        ];

        // Populate counts for standard statuses
        foreach ($statuses as $slug => &$data) {
            if (!isset($data['count']) && isset($status_counts[$slug])) {
                $data['count'] = $status_counts[$slug];
            } elseif (!isset($data['count'])) {
                $data['count'] = 0;
            }
        }

        $base_listings_url = dokan_get_navigation_url('amazon-listings');
        ?>

        <ul class="subsubsub" style="margin-bottom: 20px; width: 100%;">
            <?php
            $i = 0;
            foreach ($statuses as $slug => $data):
                if ($data['count'] === 0 && !in_array($slug, ['all', 'instock', 'outstock']) && $listing_status !== $slug)
                    continue;
                $i++;
                $class = ($listing_status === $slug) ? 'current' : '';
                $url = add_query_arg(['listing_status' => $slug], $base_listings_url);
                ?>
                    <li class="<?php echo $slug; ?>">
                        <?php if ($i > 1)
                            echo ' | '; ?>
                        <a href="<?php echo esc_url($url); ?>" class="<?php echo $class; ?>">
                            <?php echo esc_html($data['label']); ?> <span class="count">(<?php echo $data['count']; ?>)</span>
                        </a>
                    </li>
            <?php endforeach; ?>
        </ul>

        <div class="dokan-product-listing-filter dokan-clearfix" style="margin-bottom: 20px;">
            <form method="get" class="dokan-form-inline">
                <div class="dokan-form-group">
                    <select name="profile_id" class="dokan-form-control">
                        <option value="0"><?php _e('All profiles', 'dokan'); ?></option>
                        <?php
                        $profiles_table = $wpdb->prefix . 'amazon_profiles';
                        $profiles_exist = $wpdb->get_var("SHOW TABLES LIKE '$profiles_table'") === $profiles_table;
                        if ($profiles_exist) {
                            $profiles = $wpdb->get_results("SELECT id, profile_name FROM {$profiles_table} ORDER BY profile_name ASC");
                            if ($profiles) {
                                foreach ($profiles as $profile) {
                                    echo '<option value="' . $profile->id . '" ' . selected($profile_id, $profile->id, false) . '>' . esc_html($profile->profile_name) . '</option>';
                                }
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="dokan-form-group">
                    <input type="text" name="s" class="dokan-form-control"
                        placeholder="<?php _e('Search listings...', 'dokan'); ?>"
                        value="<?php echo esc_attr($search_term); ?>">
                </div>

                <input type="hidden" name="listing_status" value="<?php echo esc_attr($listing_status); ?>">
                <button type="submit" class="dokan-btn dokan-btn-theme"><?php _e('Filter', 'dokan'); ?></button>
            </form>
        </div>

        <?php if (!$table_exists): ?>
                <div class="dokan-alert dokan-alert-warning">
                    <?php _e('Amazon Listings feature is not available. Please contact support.', 'dokan'); ?>
                </div>
        <?php else:
            // Build Query
            $where = ["p.post_author = %d"];
            $params = [$current_user_id];

            if ($listing_status !== 'all') {
                if ($listing_status === 'locked') {
                    $where[] = "al.locked = 1";
                } elseif ($listing_status === 'unlocked') {
                    $where[] = "(al.locked = 0 OR al.locked IS NULL)";
                } elseif ($listing_status === 'instock') {
                    $where[] = "al.quantity > 0";
                } elseif ($listing_status === 'outstock') {
                    $where[] = "al.quantity <= 0";
                } else {
                    $where[] = "al.status = %s";
                    $params[] = $listing_status;
                }
            }

            if ($profile_id) {
                $where[] = "al.profile_id = %d";
                $params[] = $profile_id;
            }

            if ($search_term) {
                $where[] = "(al.sku LIKE %s OR al.asin LIKE %s OR p.post_title LIKE %s OR al.listing_title LIKE %s)";
                $like = '%' . $wpdb->esc_like($search_term) . '%';
                $params[] = $like;
                $params[] = $like;
                $params[] = $like;
                $params[] = $like;
            }

            $where_sql = implode(' AND ', $where);

            $listings = $wpdb->get_results($wpdb->prepare("
                SELECT al.*, p.post_title as product_title
                FROM {$amazon_listings_table} al
                LEFT JOIN {$posts_table} p ON al.post_id = p.ID
                WHERE {$where_sql}
                ORDER BY al.id DESC
            ", $params));
            ?>

                <?php if (empty($listings)): ?>
                        <div class="dokan-alert dokan-alert-info">
                            <?php _e('No listings found matching your criteria.', 'dokan'); ?>
                        </div>
                <?php else: ?>

                        <form method="post" id="amazon-listings-bulk-action">
                            <?php wp_nonce_field('bulk_amazon_listing_action', 'security'); ?>

                            <div class="dokan-table-responsive">
                                <div class="dokan-bulk-action-wrapper" style="margin-bottom: 10px;">
                                    <select name="action" class="dokan-form-control" style="width: auto; display: inline-block;">
                                        <option value="-1"><?php _e('Bulk actions', 'dokan'); ?></option>
                                        <option value="wpla_resubmit"><?php _e('Submit again', 'dokan'); ?></option>
                                        <option value="wpla_trash_listing"><?php _e('Remove from Amazon', 'dokan'); ?></option>
                                        <option value="wpla_lock"><?php _e('Lock listings', 'dokan'); ?></option>
                                        <option value="wpla_unlock"><?php _e('Unlock listings', 'dokan'); ?></option>
                                        <option value="wpla_delete"><?php _e('Delete from local list', 'dokan'); ?></option>
                                    </select>
                                    <button type="submit" name="bulk_amazon_listing_action"
                                        class="dokan-btn dokan-btn-default"><?php _e('Apply', 'dokan'); ?></button>
                                </div>

                                <table class="dokan-table dokan-table-striped">
                                    <thead>
                                        <tr>
                                            <th id="cb" class="manage-column column-cb check-column">
                                                <input type="checkbox" id="cb-select-all">
                                            </th>
                                            <th><?php _e('Image', 'dokan'); ?></th>
                                            <th><?php _e('Title', 'dokan'); ?></th>
                                            <th><?php _e('SKU', 'dokan'); ?></th>
                                            <th><?php _e('ASIN', 'dokan'); ?></th>
                                            <th><?php _e('Price', 'dokan'); ?></th>
                                            <th><?php _e('Stock', 'dokan'); ?></th>
                                            <th><?php _e('Status', 'dokan'); ?></th>
                                            <th><?php _e('Date', 'dokan'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($listings as $listing):
                                            $product = wc_get_product($listing->post_id);
                                            $image_url = $product ? wp_get_attachment_image_url($product->get_image_id(), 'thumbnail') : '';

                                            // Status badge styling
                                            $status_class = 'dokan-label';
                                            switch ($listing->status) {
                                                case 'online':
                                                    $status_class .= ' dokan-label-success';
                                                    break;
                                                case 'failed':
                                                    $status_class .= ' dokan-label-danger';
                                                    break;
                                                case 'prepared':
                                                case 'changed':
                                                case 'matched':
                                                case 'submitted':
                                                    $status_class .= ' dokan-label-warning';
                                                    break;
                                                default:
                                                    $status_class .= ' dokan-label-default';
                                            }
                                            ?>
                                                <tr>
                                                    <th class="check-column">
                                                        <input type="checkbox" name="listing_ids[]" value="<?php echo $listing->id; ?>">
                                                    </th>
                                                    <td>
                                                        <?php if ($image_url): ?>
                                                                <img src="<?php echo esc_url($image_url); ?>" alt=""
                                                                    style="max-width: 50px; max-height: 50px;">
                                                        <?php else: ?>
                                                                <span class="dashicons dashicons-format-image"
                                                                    style="font-size: 40px; color: #ddd;"></span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <strong><?php echo esc_html($listing->product_title ?: $listing->listing_title); ?></strong>
                                                        <?php if ($listing->locked): ?>
                                                                <i class="fas fa-lock" style="color: #666; font-size: 10px; margin-left: 5px;"
                                                                    title="Locked"></i>
                                                        <?php endif; ?>
                                                        <?php if (!empty($listing->pstatus)): ?>
                                                                <br><small class="text-muted"><?php echo esc_html($listing->pstatus); ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo esc_html($listing->sku); ?></td>
                                                    <td>
                                                        <?php if (!empty($listing->asin)): ?>
                                                                <a href="https://www.amazon.co.uk/dp/<?php echo esc_attr($listing->asin); ?>"
                                                                    target="_blank">
                                                                    <?php echo esc_html($listing->asin); ?>
                                                                </a>
                                                        <?php else: ?>
                                                                —
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo $listing->price ? wc_price($listing->price) : '—'; ?></td>
                                                    <td><?php echo $listing->quantity !== '' ? intval($listing->quantity) : '—'; ?></td>
                                                    <td>
                                                        <span class="<?php echo esc_attr($status_class); ?>">
                                                            <?php echo esc_html(ucfirst($listing->status)); ?>
                                                        </span>
                                                        <?php if ($listing->status == 'failed' && !empty($listing->history)):
                                                            $history = maybe_unserialize($listing->history);
                                                            $error_msg = '';
                                                            if (is_array($history) && isset($history['errors']) && !empty($history['errors'])) {
                                                                $error_msg = $history['errors'][0]['error-message'] ?? '';
                                                            } elseif (is_string($history)) {
                                                                $error_msg = $history;
                                                            }

                                                            if ($error_msg): ?>
                                                                        <div
                                                                            style="font-size: 10px; color: #dc3545; margin-top: 5px; line-height: 1.2; max-width: 150px;">
                                                                            <i class="fas fa-exclamation-triangle"></i> <?php echo esc_html($error_msg); ?>
                                                                        </div>
                                                                <?php endif; ?>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $date = !empty($listing->date_published) ? $listing->date_published : $listing->date_created;
                                                        echo $date ? date_i18n(get_option('date_format'), strtotime($date)) : '—';
                                                        ?>
                                                    </td>
                                                </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </form>

                        <script>
                            jQuery(document).ready(function ($) {
                                $('#cb-select-all').on('change', function () {
                                    $('input[name="listing_ids[]"]').prop('checked', $(this).prop('checked'));
                                });
                            });
                        </script>

                <?php endif; ?>
        <?php endif; ?>

        <?php do_action('dokan_dashboard_content_inside_after'); ?>

    </div><!-- .dokan-dashboard-content -->
</div><!-- .dokan-dashboard-wrap -->

<style>
    .dokan-amazon-listings-content .dokan-table img {
        border-radius: 4px;
        border: 1px solid #eee;
    }

    .dokan-amazon-listings-content .dokan-label {
        display: inline-block;
        padding: 3px 8px;
        font-size: 11px;
        font-weight: 600;
        border-radius: 3px;
        text-transform: uppercase;
    }

    .dokan-amazon-listings-content .dokan-label-success {
        background: #28a745;
        color: #fff;
    }

    .dokan-amazon-listings-content .dokan-label-danger {
        background: #dc3545;
        color: #fff;
    }

    .dokan-amazon-listings-content .dokan-label-warning {
        background: #ffc107;
        color: #212529;
    }

    .dokan-amazon-listings-content .dokan-label-default {
        background: #6c757d;
        color: #fff;
    }
</style>