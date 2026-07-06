<?php
/**
 * Amazon Reports & Logs Page for Sellers (Dokan Dashboard)
 * 
 * Displays sync reports and processing results from WP-Lister
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

// 2. Get recent feeds for this account
$table_feeds = $wpdb->prefix . 'amazon_feeds';
$feeds = [];
if ($account_id) {
    $feeds = $wpdb->get_results($wpdb->prepare("
        SELECT * FROM $table_feeds 
        WHERE account_id = %d 
        ORDER BY date_created DESC 
        LIMIT 20
    ", $account_id));
}

?>

<div class="dokan-dashboard-wrap">
    <?php dokan_get_template_part('global/dashboard', 'nav'); ?>

    <div class="dokan-dashboard-content dokan-amazon-reports-content">

        <?php do_action('dokan_dashboard_content_inside_before'); ?>

        <header class="dokan-dashboard-header dokan-clearfix">
            <h1 class="entry-title">
                <i class="fas fa-file-alt"></i>
                <?php _e('Amazon Sync Reports & Logs', 'dokan'); ?>
            </h1>
        </header>

        <div class="dokan-panel dokan-panel-default">
            <div class="dokan-panel-heading">
                <strong><?php _e('Inventory Summary', 'dokan'); ?></strong>
            </div>
            <div class="dokan-panel-body">
                <?php
                $table_listings = $wpdb->prefix . 'amazon_listings';
                $posts_table = $wpdb->prefix . 'posts';
                $stats = $wpdb->get_results($wpdb->prepare("
                    SELECT al.status, COUNT(*) as count 
                    FROM $table_listings al
                    JOIN $posts_table p ON al.post_id = p.ID
                    WHERE p.post_author = %d
                    GROUP BY al.status
                ", $current_user_id));

                if ($stats): ?>
                    <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                        <?php foreach ($stats as $stat):
                            $bg = '#6c757d';
                            if ($stat->status == 'online')
                                $bg = '#28a745';
                            if ($stat->status == 'failed')
                                $bg = '#dc3545';
                            if ($stat->status == 'prepared')
                                $bg = '#ffc107';
                            ?>
                            <div
                                style="padding: 15px 25px; border-radius: 8px; background: <?php echo $bg; ?>; color: #fff; min-width: 120px; text-align: center;">
                                <div style="font-size: 24px; font-weight: bold;"><?php echo $stat->count; ?></div>
                                <div style="font-size: 12px; text-transform: uppercase;"><?php echo esc_html($stat->status); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted"><?php _e('No inventory data available.', 'dokan'); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <h2 style="font-size: 18px; margin: 30px 0 15px;"><?php _e('Recent Sync Feeds', 'dokan'); ?></h2>

        <?php if (empty($feeds)): ?>
            <div class="dokan-alert dokan-alert-info">
                <?php _e('No sync history found for your account.', 'dokan'); ?>
            </div>
        <?php else: ?>
            <div class="dokan-table-responsive">
                <table class="dokan-table dokan-table-striped">
                    <thead>
                        <tr>
                            <th><?php _e('Feed ID', 'dokan'); ?></th>
                            <th><?php _e('Type', 'dokan'); ?></th>
                            <th><?php _e('Date', 'dokan'); ?></th>
                            <th><?php _e('Status', 'dokan'); ?></th>
                            <th><?php _e('Items', 'dokan'); ?></th>
                            <th><?php _e('Result', 'dokan'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($feeds as $feed):
                            $status_class = 'dokan-label';
                            if ($feed->status == 'Success')
                                $status_class .= ' dokan-label-success';
                            elseif ($feed->status == 'Error')
                                $status_class .= ' dokan-label-danger';
                            elseif ($feed->status == 'pending')
                                $status_class .= ' dokan-label-warning';
                            else
                                $status_class .= ' dokan-label-default';

                            // Clean up feed type names
                            $type_label = str_replace(['POST_FLAT_FILE_', '_DATA_'], '', $feed->FeedType);
                            $type_label = str_replace('_', ' ', $type_label);
                            ?>
                            <tr>
                                <td><code>#<?php echo $feed->FeedSubmissionId ?: $feed->id; ?></code></td>
                                <td><small><?php echo esc_html($type_label); ?></small></td>
                                <td><?php echo date_i18n(get_option('date_format') . ' H:i', strtotime($feed->date_created)); ?>
                                </td>
                                <td>
                                    <span class="<?php echo esc_attr($status_class); ?>">
                                        <?php echo esc_html($feed->status); ?>
                                    </span>
                                </td>
                                <td><?php echo intval($feed->line_count); ?></td>
                                <td>
                                    <?php if ($feed->success == 'Error'): ?>
                                        <div class="dokan-alert dokan-alert-danger"
                                            style="margin: 0; padding: 5px 10px; font-size: 11px;">
                                            <?php _e('Check SKU mappings or Amazon permissions.', 'dokan'); ?>
                                        </div>
                                    <?php elseif (!empty($feed->results)): ?>
                                        <small
                                            class="text-muted"><?php echo esc_html(substr(strip_tags($feed->results), 0, 50)); ?>...</small>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
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
    .dokan-amazon-reports-content .dokan-panel {
        border: 1px solid #eee;
        background: #fff;
        margin-bottom: 20px;
    }

    .dokan-amazon-reports-content .dokan-panel-heading {
        padding: 15px 20px;
        border-bottom: 1px solid #eee;
        background: #fcfcfc;
    }

    .dokan-amazon-reports-content .dokan-panel-body {
        padding: 20px;
    }

    .dokan-amazon-reports-content .dokan-label {
        display: inline-block;
        padding: 2px 8px;
        font-size: 11px;
        font-weight: 600;
        border-radius: 3px;
        text-transform: uppercase;
    }

    .dokan-amazon-reports-content .dokan-label-success {
        background: #28a745;
        color: #fff;
    }

    .dokan-amazon-reports-content .dokan-label-danger {
        background: #dc3545;
        color: #fff;
    }

    .dokan-amazon-reports-content .dokan-label-warning {
        background: #ffc107;
        color: #212529;
    }

    .dokan-amazon-reports-content .dokan-label-default {
        background: #6c757d;
        color: #fff;
    }
</style>