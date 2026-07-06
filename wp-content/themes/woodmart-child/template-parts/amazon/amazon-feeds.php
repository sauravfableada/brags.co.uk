<?php
/**
 * Amazon Feeds & API Logs Page for Sellers (Dokan Dashboard)
 * 
 * Displays detailed feed submissions and individual API logs from WP-Lister
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

// 1. Get vendor's Amazon account ID
$account_id = function_exists('brags_get_seller_amazon_account_id') ? brags_get_seller_amazon_account_id($current_user_id) : 0;


// 2. Setup Filters
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'all';
$search_query = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$acc_filter = isset($_GET['account_id']) ? intval($_GET['account_id']) : $account_id;

// 3. Get available accounts for filter - restricted to vendor if not admin
$table_accounts = $wpdb->prefix . 'amazon_accounts';
if (current_user_can('manage_options')) {
    $accounts = $wpdb->get_results("SELECT id, title, marketplace_id FROM $table_accounts WHERE active = 1");
} else {
    $accounts = $wpdb->get_results($wpdb->prepare(
        "SELECT id, title, marketplace_id FROM $table_accounts WHERE (vendor_id = %d OR id = %d) AND active = 1",
        $current_user_id,
        $account_id
    ));

    // Safety: If not admin, force the account filter to be one of the user's accounts
    $user_account_ids = wp_list_pluck($accounts, 'id');
    if ($acc_filter > 0 && !in_array($acc_filter, $user_account_ids)) {
        $acc_filter = $account_id;
    }
}

// 4. Build WHERE clauses
$where_feeds = "WHERE 1=1";
$where_logs = "WHERE callname = 'putListingItem'";

if ($acc_filter > 0) {
    $where_feeds .= $wpdb->prepare(" AND account_id = %d", $acc_filter);
    $where_logs .= $wpdb->prepare(" AND account_id = %d", $acc_filter);
} elseif ($acc_filter == 0 && !current_user_can('manage_options') && !empty($accounts)) {
    // Non-admins can't see "All accounts", so narrow down to their specific IDs
    $user_account_ids = wp_list_pluck($accounts, 'id');
    $ids_string = implode(',', array_map('intval', $user_account_ids));
    $where_feeds .= " AND account_id IN ($ids_string)";
    $where_logs .= " AND account_id IN ($ids_string)";
} elseif ($acc_filter == 0 && !current_user_can('manage_options')) {
    // No accounts found for this user
    $where_feeds .= " AND 1=0";
    $where_logs .= " AND 1=0";
}

if ($status_filter !== 'all') {
    if ($status_filter === 'processed') {
        $where_feeds .= " AND (status = 'processed' OR status = 'done')";
        $where_logs .= " AND (success = 'Success' OR success = 'processed')"; // logs use success column mostly
    } else {
        $where_feeds .= $wpdb->prepare(" AND status = %s", $status_filter);
        $where_logs .= $wpdb->prepare(" AND success LIKE %s", '%' . $status_filter . '%');
    }
}

if ($search_query) {
    $search_like = '%' . $wpdb->esc_like($search_query) . '%';
    $where_feeds .= $wpdb->prepare(" AND (FeedSubmissionId LIKE %s OR results LIKE %s)", $search_like, $search_like);
    $where_logs .= $wpdb->prepare(" AND (response LIKE %s)", $search_like);
}

// 4. Setup Pagination
$per_page = 20;
$paged = isset($_GET['pagenum']) ? max(1, intval($_GET['pagenum'])) : 1;
$offset = ($paged - 1) * $per_page;

// 5. Fetch Items (Feeds + API Logs like WP-Lister)
$feeds_table = $wpdb->prefix . 'amazon_feeds';
$log_table = $wpdb->prefix . 'amazon_log';

$sql = "
    (SELECT 
        id, 
        account_id,
        'feed' as source_type,
        FeedSubmissionId as batch_id, 
        FeedType as type, 
        template_name, 
        date_created, 
        SubmittedDate as submitted_at,
        CompletedProcessingDate as completed_at,
        status,
        success,
        line_count,
        results
    FROM $feeds_table
    $where_feeds)
    
    UNION ALL
    
    (SELECT 
        id, 
        account_id,
        'api_log' as source_type,
        '' as batch_id,
        'Individual Listing' as type,
        'putListingItem' as template_name,
        timestamp as date_created,
        timestamp as submitted_at,
        timestamp as completed_at,
        'processed' as status,
        success,
        1 as line_count,
        response as results
    FROM $log_table
    $where_logs)
    
    ORDER BY date_created DESC
    LIMIT $per_page OFFSET $offset
";

$items = $wpdb->get_results($sql);

// Get total for pagination
$total_items = $wpdb->get_var("
    SELECT (
        (SELECT COUNT(*) FROM $feeds_table $where_feeds) + 
        (SELECT COUNT(*) FROM $log_table $where_logs)
    )
");

// Get counts for tabs
$acc_where_counts = ($acc_filter > 0) ? $wpdb->prepare("account_id = %d", $acc_filter) : "1=1";

$count_all = $total_items;
$count_processed = $wpdb->get_var("SELECT (
    (SELECT COUNT(*) FROM $feeds_table WHERE $acc_where_counts AND (status = 'processed' OR status = 'done')) + 
    (SELECT COUNT(*) FROM $log_table WHERE $acc_where_counts AND callname = 'putListingItem' AND (success = 'Success' OR success = 'processed'))
)");
$count_pending = $wpdb->get_var("SELECT COUNT(*) FROM $feeds_table WHERE $acc_where_counts AND status = 'pending'");
$count_submitted = $wpdb->get_var("SELECT COUNT(*) FROM $feeds_table WHERE $acc_where_counts AND status = 'submitted'");

$total_pages = ceil($total_items / $per_page);

// Get available accounts for filter - restricted to vendor if not admin
$table_accounts = $wpdb->prefix . 'amazon_accounts';
if (current_user_can('manage_options')) {
    $accounts = $wpdb->get_results("SELECT id, title, marketplace_id FROM $table_accounts WHERE active = 1");
} else {
    $accounts = $wpdb->get_results($wpdb->prepare(
        "SELECT id, title, marketplace_id FROM $table_accounts WHERE (vendor_id = %d OR id = %d) AND active = 1",
        $current_user_id,
        $account_id
    ));

    // Safety: If not admin, force the account filter to be one of the user's accounts
    $user_account_ids = wp_list_pluck($accounts, 'id');
    if ($acc_filter > 0 && !in_array($acc_filter, $user_account_ids)) {
        $acc_filter = $account_id; // Reset to their default if they try to access someone else's
        // Re-generate WHERE clauses if Reset happened
        $where_feeds = $wpdb->prepare("WHERE account_id = %d", $acc_filter);
        $where_logs = $wpdb->prepare("WHERE account_id = %d AND callname = 'putListingItem'", $acc_filter);
        // ... (We could re-run the items query here, but easier to just ensure selection logic is solid)
    } elseif ($acc_filter == 0 && !current_user_can('manage_options')) {
        // Non-admins can't see "All accounts", so narrow down to their specific IDs
        $ids_string = implode(',', array_map('intval', $user_account_ids));
        $where_feeds = "WHERE account_id IN ($ids_string)";
        $where_logs = "WHERE callname = 'putListingItem' AND account_id IN ($ids_string)";
    }
}

?>

<div class="dokan-dashboard-wrap">
    <?php dokan_get_template_part('global/dashboard', 'nav'); ?>

    <div class="dokan-dashboard-content dokan-amazon-feeds-content">

        <?php do_action('dokan_dashboard_content_inside_before'); ?>

        <header class="dokan-dashboard-header dokan-clearfix">
            <h1 class="entry-title">
                <i class="fas fa-rss"></i>
                <?php _e('Amazon Feeds & Submission Logs', 'dokan'); ?>
            </h1>
        </header>

        <!-- Filter Tabs -->
        <ul class="subsubsub" style="margin: 10px 0; padding: 0; list-style: none;">
            <li class="all"><a href="<?php echo add_query_arg('status', 'all'); ?>"
                    class="<?php echo $status_filter == 'all' ? 'current' : ''; ?>"><?php _e('All', 'dokan'); ?> <span
                        class="count">(<?php echo $count_all; ?>)</span></a> |</li>
            <li class="processed"><a href="<?php echo add_query_arg('status', 'processed'); ?>"
                    class="<?php echo $status_filter == 'processed' ? 'current' : ''; ?>"><?php _e('Processed', 'dokan'); ?>
                    <span class="count">(<?php echo $count_processed; ?>)</span></a> |</li>
            <li class="pending"><a href="<?php echo add_query_arg('status', 'pending'); ?>"
                    class="<?php echo $status_filter == 'pending' ? 'current' : ''; ?>"><?php _e('Pending', 'dokan'); ?>
                    <span class="count">(<?php echo $count_pending; ?>)</span></a> |</li>
            <li class="submitted"><a href="<?php echo add_query_arg('status', 'submitted'); ?>"
                    class="<?php echo $status_filter == 'submitted' ? 'current' : ''; ?>"><?php _e('Submitted', 'dokan'); ?>
                    <span class="count">(<?php echo $count_submitted; ?>)</span></a></li>
        </ul>

        <!-- Search and Filters Form -->
        <form method="get" action="" class="dokan-form-inline dokan-w12 dokan-amazon-feeds-filter-form"
            style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <div class="left-filters" style="display: flex; gap: 10px; align-items: center;">
                <select name="action" class="dokan-form-control" style="width: 150px;">
                    <option value="-1"><?php _e('Bulk actions', 'dokan'); ?></option>
                    <option value="delete"><?php _e('Delete', 'dokan'); ?></option>
                </select>
                <button type="submit" class="dokan-btn dokan-btn-default"><?php _e('Apply', 'dokan'); ?></button>

                <?php if (count($accounts) > 1 || current_user_can('manage_options')): ?>
                    <select name="account_id" class="dokan-form-control" style="width: 200px;">
                        <option value="0"><?php _e('All accounts', 'woodmart-child'); ?></option>
                        <?php foreach ($accounts as $acc): ?>
                            <option value="<?php echo $acc->id; ?>" <?php selected($acc_filter, $acc->id); ?>>
                                <?php echo esc_html($acc->title); ?> (<?php echo esc_html($acc->marketplace_id); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="dokan-btn dokan-btn-default"><?php _e('Filter', 'dokan'); ?></button>
                <?php endif; ?>
            </div>

            <div class="right-search" style="display: flex; gap: 5px;">
                <input type="text" name="s" class="dokan-form-control" placeholder="<?php _e('Search...', 'dokan'); ?>"
                    value="<?php echo esc_attr($search_query); ?>">
                <button type="submit" class="dokan-btn dokan-btn-default"><?php _e('Search', 'dokan'); ?></button>
                <span style="align-self: center; margin-left: 10px; color: #777;">
                    <?php printf(__('%d items', 'dokan'), $total_items); ?>
                </span>
            </div>

            <!-- Hidden fields to keep current page/status -->
            <input type="hidden" name="status" value="<?php echo esc_attr($status_filter); ?>">
        </form>

        <?php if (empty($items)): ?>
            <div class="dokan-alert dokan-alert-info">
                <?php
                if (!$account_id) {
                    _e('Please connect your Amazon account first.', 'woodmart-child');
                } else {
                    _e('No submissions or feeds found yet.', 'dokan');
                }
                ?>
            </div>
        <?php else: ?>
            <form method="post" action="">
                <div class="dokan-table-responsive">
                    <table class="dokan-table dokan-table-striped">
                        <thead>
                            <tr>
                                <th class="manage-column column-cb check-column">
                                    <input type="checkbox" id="cb-select-all-1">
                                </th>
                                <th>
                                    <?php _e('Batch ID', 'dokan'); ?>
                                </th>
                                <th>
                                    <?php _e('Type', 'dokan'); ?>
                                </th>
                                <th>
                                    <?php _e('Submitted At', 'dokan'); ?>
                                </th>
                                <th>
                                    <?php _e('Status', 'dokan'); ?>
                                </th>
                                <th>
                                    <?php _e('Account', 'woodmart-child'); ?>
                                </th>
                                <th>
                                    <?php _e('Details', 'dokan'); ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item):
                                $is_api_log = ($item->source_type === 'api_log');
                                $status_class = 'dokan-label';

                                if (strpos($item->success, 'Error') !== false || $item->status == 'error')
                                    $status_class .= ' dokan-label-danger';
                                elseif ($item->success == 'Success' || $item->status == 'processed' || $item->status == 'done')
                                    $status_class .= ' dokan-label-success';
                                elseif ($item->status == 'pending' || $item->status == 'submitted')
                                    $status_class .= ' dokan-label-warning';
                                else
                                    $status_class .= ' dokan-label-default';

                                // Format type
                                $type_label = $item->type;
                                if (!$is_api_log) {
                                    $type_label = str_replace(['POST_FLAT_FILE_', '_DATA_'], '', $item->type);
                                    $type_label = str_replace('_', ' ', $type_label);
                                }
                                ?>
                                <tr>
                                    <th class="check-column">
                                        <input type="checkbox" name="item[]" value="<?php echo esc_attr($item->id); ?>">
                                    </th>
                                    <td>
                                        <?php if ($item->batch_id): ?>
                                            <code>#<?php echo esc_html($item->batch_id); ?></code>
                                        <?php else: ?>
                                            <span class="text-muted">
                                                <?php _e('Direct API Call', 'dokan'); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong>
                                            <?php echo esc_html($type_label); ?>
                                        </strong>
                                        <?php if ($item->template_name && !$is_api_log): ?>
                                            <br><small class="text-muted">
                                                <?php echo esc_html($item->template_name); ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo date_i18n(get_option('date_format') . ' H:i', strtotime($item->submitted_at)); ?>
                                        <br><small class="text-muted">
                                            <?php printf(__('%s ago', 'dokan'), human_time_diff(strtotime($item->submitted_at))); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="<?php echo esc_attr($status_class); ?>">
                                            <?php echo esc_html($is_api_log ? $item->success : $item->status); ?>
                                        </span>
                                        <?php if (!$is_api_log && $item->line_count > 1): ?>
                                            <br><small>
                                                <?php printf(__('%d items', 'dokan'), $item->line_count); ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-size: 11px;">
                                        <?php
                                        foreach ($accounts as $acc) {
                                            if ($acc->id == $item->account_id) {
                                                echo esc_html($acc->title);
                                                break;
                                            }
                                        }
                                        ?>
                                    </td>
                                    <td style="max-width:300px;">
                                        <?php if (!empty($item->results)): ?>
                                            <button type="button" class="dokan-btn dokan-btn-default dokan-btn-sm wpla-view-details"
                                                data-id="<?php echo esc_attr($item->id); ?>">
                                                <i class="fas fa-eye"></i> <?php _e('View Details', 'woodmart-child'); ?>
                                            </button>

                                            <div id="wpla-details-content-<?php echo esc_attr($item->id); ?>" style="display:none;">
                                                <?php
                                                $results_text = $item->results;

                                                // Try to make it readable
                                                if (is_serialized($results_text)) {
                                                    $unserialized = maybe_unserialize($results_text);
                                                    $results_text = print_r($unserialized, true);
                                                } elseif ($json = json_decode($results_text, true)) {
                                                    $results_text = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                                                }

                                                echo esc_html($results_text);
                                                ?>
                                            </div>

                                        <?php else: ?>
                                            <span class="text-muted">
                                                <?php _e('No results yet.', 'dokan'); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </form>

            <?php if ($total_pages > 1): ?>
                <div class="dokan-pagination-container">
                    <ul class="dokan-pagination">
                        <?php
                        echo paginate_links(array(
                            'base' => add_query_arg('pagenum', '%#%'),
                            'format' => '',
                            'prev_text' => __('&laquo;', 'dokan'),
                            'next_text' => __('&raquo;', 'dokan'),
                            'total' => $total_pages,
                            'current' => $paged,
                            'type' => 'plain',
                        ));
                        ?>
                    </ul>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php do_action('dokan_dashboard_content_inside_after'); ?>

    </div>
</div>

<style>
    .dokan-amazon-feeds-content .dokan-table th {
        background: #f9f9f9;
    }

    .dokan-amazon-feeds-content .dokan-label {
        display: inline-block;
        padding: 2px 8px;
        font-size: 11px;
        font-weight: 600;
        border-radius: 3px;
        text-transform: uppercase;
    }

    .dokan-amazon-feeds-content .dokan-label-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .dokan-amazon-feeds-content .dokan-label-danger {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .dokan-amazon-feeds-content .dokan-label-warning {
        background: #fff3cd;
        color: #856404;
        border: 1px solid #ffeeba;
    }

    .dokan-amazon-feeds-content .dokan-label-default {
        background: #e2e3e5;
        color: #383d41;
        border: 1px solid #d6d8db;
    }

    /* Subsubsub style for tabs */
    .subsubsub {
        color: #666;
        font-size: 13px;
    }

    .subsubsub li {
        display: inline-block;
        margin: 0;
        padding: 0;
    }

    .subsubsub a {
        padding: 2px;
        line-height: 2;
        text-decoration: none;
        color: #0073aa;
    }

    .subsubsub a.current {
        font-weight: 600;
        color: #000;
    }

    .subsubsub .count {
        color: #666;
        font-weight: 400;
    }

    .dokan-amazon-feeds-filter-form select,
    .dokan-amazon-feeds-filter-form input {
        height: 32px !important;
        font-size: 13px !important;
        padding: 0 10px !important;
    }

    .dokan-amazon-feeds-filter-form .dokan-btn {
        height: 32px !important;
        padding: 0 12px !important;
        line-height: 30px !important;
        font-size: 13px !important;
    }

    .check-column {
        width: 30px;
        padding: 10px 0 10px 10px !important;
    }

    /* Modal Styles */
    #wpla-modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        z-index: 999999;
        justify-content: center;
        align-items: center;
    }

    #wpla-modal-container {
        background: #fff;
        width: 90%;
        max-width: 800px;
        max-height: 80vh;
        border-radius: 8px;
        position: relative;
        display: flex;
        flex-direction: column;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    }

    #wpla-modal-header {
        padding: 15px 20px;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    #wpla-modal-header h3 {
        margin: 0;
        font-size: 18px;
    }

    #wpla-modal-close {
        cursor: pointer;
        font-size: 24px;
        color: #999;
        line-height: 1;
    }

    #wpla-modal-close:hover {
        color: #333;
    }

    #wpla-modal-body {
        padding: 20px;
        overflow-y: auto;
        background: #f8f9fa;
        font-family: monospace;
        font-size: 13px;
        white-space: pre-wrap;
        word-break: break-all;
    }
</style>

<!-- Modal HTML -->
<div id="wpla-modal-overlay">
    <div id="wpla-modal-container">
        <div id="wpla-modal-header">
            <h3><?php _e('Result Details', 'woodmart-child'); ?></h3>
            <span id="wpla-modal-close">&times;</span>
        </div>
        <div id="wpla-modal-body"></div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const overlay = document.getElementById('wpla-modal-overlay');
        const body = document.getElementById('wpla-modal-body');
        const closeBtn = document.getElementById('wpla-modal-close');

        document.querySelectorAll('.wpla-view-details').forEach(button => {
            button.addEventListener('click', function () {
                const id = this.getAttribute('data-id');
                const content = document.getElementById(`wpla-details-content-${id}`).innerHTML;
                body.textContent = content.trim();
                overlay.style.display = 'flex';
            });
        });

        closeBtn.addEventListener('click', function () {
            overlay.style.display = 'none';
        });

        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) {
                overlay.style.display = 'none';
            }
        });

        // Select All Checkbox
        const selectAll = document.getElementById('cb-select-all-1');
        const checkboxes = document.querySelectorAll('.check-column input[type="checkbox"]');
        if (selectAll) {
            selectAll.addEventListener('change', function () {
                checkboxes.forEach(cb => {
                    cb.checked = this.checked;
                });
            });
        }
    });
</script>
<?php
