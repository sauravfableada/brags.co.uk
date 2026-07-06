<?php
/**
 * Amazon MCF Settings Page for Sellers (Dokan Dashboard Settings)
 * 
 * Provides two options for connecting Amazon account:
 * 1. OAuth "Connect with Amazon" button (simple)
 * 2. Manual credential entry (advanced)
 */

if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();

// Check if user is logged in and is a seller
if (!is_user_logged_in() || !dokan_is_seller_enabled($user_id)) {
    echo '<div class="dokan-alert dokan-alert-warning">' . __('You do not have permission to access this page.', 'dokan') . '</div>';
    return;
}

$amazon_seller_id = get_user_meta($user_id, 'amazon_seller_id', true);
$amazon_marketplace = get_user_meta($user_id, 'amazon_marketplace_id', true);
$amazon_refresh_token = get_user_meta($user_id, 'amazon_refresh_token', true);
$is_connected = !empty($amazon_refresh_token);

// Check if OAuth is available
$oauth_available = function_exists('brags_is_sp_api_configured') && brags_is_sp_api_configured();
$oauth_url = $oauth_available && function_exists('brags_get_sp_api_oauth_url') ? brags_get_sp_api_oauth_url() : '';
?>

<article class="dokan-settings-area">

    <header class="dokan-dashboard-header dokan-clearfix">
        <h1 class="entry-title">
            <i class="fab fa-amazon"></i>
            <?php _e('Amazon Integration', 'dokan'); ?>
        </h1>
    </header>

    <?php if ($is_connected): ?>
        <div class="dokan-alert dokan-alert-success" style="margin-bottom: 25px;">
            <i class="fas fa-check-circle" style="font-size: 20px; margin-right: 10px;"></i>
            <div style="display: inline-block; width: 100%;">
                <div style="display: flex; justify-content: space-between; align-items: start;">
                    <div>
                        <strong><?php _e('Amazon Account Connected!', 'dokan'); ?></strong>
                        <p style="margin: 5px 0 0 0;">
                            <?php _e('Your Amazon Seller Central account is linked. You can now list products on Amazon from your Products page.', 'dokan'); ?>
                            <br>
                            <small><strong><?php _e('Seller ID:', 'dokan'); ?></strong>
                                <?php echo esc_html($amazon_seller_id); ?></small>
                            <?php
                            if (function_exists('brags_get_seller_amazon_account_id')) {
                                $wpla_id = brags_get_seller_amazon_account_id($user_id);
                                if ($wpla_id): ?>
                                    <br><small><strong><?php _e('WP-Lister Account:', 'dokan'); ?></strong>
                                        <?php _e('Verified (ID:', 'dokan'); ?>             <?php echo esc_html($wpla_id); ?>)</small>
                                    <?php
                                    // Get last sync time
                                    global $wpdb;
                                    $table_reports = $wpdb->prefix . 'amazon_reports';
                                    $last_sync = $wpdb->get_var($wpdb->prepare("
                                        SELECT CompletedDate FROM $table_reports 
                                        WHERE account_id = %d AND ReportType IN ('_GET_FBA_MYI_UNSUPPRESSED_INVENTORY_DATA_', 'GET_FBA_MYI_UNSUPPRESSED_INVENTORY_DATA')
                                        AND success = 'yes'
                                        ORDER BY CompletedDate DESC LIMIT 1
                                    ", $wpla_id));

                                    if ($last_sync): ?>
                                        <br><small><strong><?php _e('Last Inventory Sync:', 'dokan'); ?></strong>
                                            <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_sync)); ?></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <br><small style="color:#d9534f;"><strong><?php _e('WP-Lister Status:', 'dokan'); ?></strong>
                                        <?php _e('Account not found in sync table. Please click "Update Credentials" below or Reconnect.', 'dokan'); ?></small>
                                <?php endif;
                            } ?>
                        </p>
                    </div>
                    <div>
                        <form method="post" style="margin: 0;">
                            <?php wp_nonce_field('brags_amazon_sync_now', 'brags_amazon_sync_nonce'); ?>
                            <button type="submit" name="brags_amazon_sync_now" class="dokan-btn dokan-btn-theme"
                                style="background: #232F3E; border-color: #232F3E;">
                                <i class="fas fa-sync-alt"></i> <?php _e('Sync Inventory Now', 'dokan'); ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- OPTION 1: Simple OAuth Connect Button -->
    <?php if ($oauth_available && !$is_connected): ?>
        <div class="dokan-panel dokan-panel-default"
            style="background: linear-gradient(135deg, #FF9900 0%, #FFB84D 100%); border: none;">
            <div class="dokan-panel-body" style="text-align: center; padding: 40px;">
                <h2 style="color: #232F3E; margin-bottom: 15px;">
                    <i class="fab fa-amazon"></i> <?php _e('Connect Your Amazon Account', 'dokan'); ?>
                </h2>
                <p style="color: #232F3E; font-size: 16px; margin-bottom: 25px;">
                    <?php _e('Click the button below to securely connect your Amazon Seller Central account. You\'ll be redirected to Amazon to authorize access.', 'dokan'); ?>
                </p>
                <a href="<?php echo esc_url($oauth_url); ?>" class="dokan-btn dokan-btn-lg"
                    style="background: #232F3E; color: #FF9900; font-size: 18px; padding: 15px 40px; border-radius: 8px; display: inline-flex; align-items: center; gap: 10px;">
                    <i class="fab fa-amazon" style="font-size: 24px;"></i>
                    <?php _e('Connect with Amazon', 'dokan'); ?>
                </a>
                <p style="color: #666; font-size: 12px; margin-top: 20px;">
                    <?php _e('This will redirect you to Amazon Seller Central to authorize Brags to access your account.', 'dokan'); ?>
                </p>
            </div>
        </div>

        <div style="text-align: center; margin: 30px 0;">
            <span style="display: inline-block; padding: 10px 20px; background: #f5f5f5; border-radius: 20px; color: #666;">
                <?php _e('OR enter your credentials manually below', 'dokan'); ?>
            </span>
        </div>
    <?php endif; ?>

    <!-- If already connected, show reconnect option -->
    <?php if ($oauth_available && $is_connected): ?>
        <div class="dokan-panel dokan-panel-default" style="margin-bottom: 20px;">
            <div class="dokan-panel-heading">
                <strong><?php _e('Reconnect Account', 'dokan'); ?></strong>
            </div>
            <div class="dokan-panel-body">
                <p><?php _e('If you need to reconnect or update your Amazon authorization:', 'dokan'); ?></p>
                <a href="<?php echo esc_url($oauth_url); ?>" class="dokan-btn dokan-btn-theme">
                    <i class="fab fa-amazon"></i> <?php _e('Reconnect with Amazon', 'dokan'); ?>
                </a>

                <form method="post" style="display: inline-block; margin-left: 10px;" onsubmit="return confirm('<?php _e('Are you sure you want to disconnect your Amazon account? This will remove all local credentials and you will need to re-authorize to list products.', 'dokan'); ?>');">
                    <?php wp_nonce_field('brags_amazon_disconnect', 'brags_amazon_disconnect_nonce'); ?>
                    <button type="submit" name="brags_amazon_disconnect" class="dokan-btn dokan-btn-danger">
                        <i class="fas fa-times-circle"></i> <?php _e('Disconnect Account', 'dokan'); ?>
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- OPTION 2: Manual Entry (Advanced) -->
    <div class="dokan-panel dokan-panel-default">
        <div class="dokan-panel-heading">
            <strong>
                <?php echo $is_connected ? __('Update Credentials Manually', 'dokan') : __('Manual Setup (Advanced)', 'dokan'); ?>
            </strong>
        </div>
        <div class="dokan-panel-body">

            <?php if (!$is_connected): ?>
                <div class="dokan-alert dokan-alert-info" style="margin-bottom: 20px;">
                    <strong><?php _e('Where to find these details?', 'dokan'); ?></strong>
                    <ul style="margin-top: 10px; padding-left: 20px; margin-bottom: 0;">
                        <li><strong>Merchant ID:</strong> In Seller Central → Settings → Account Info → Merchant Token</li>
                        <li><strong>Marketplace ID:</strong> For UK, it is always <code>A1F83G8C2ARO7P</code></li>
                        <li><strong>Refresh Token:</strong> Contact <a
                                href="mailto:support@brags.co.uk">support@brags.co.uk</a> for assistance</li>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post">
                <?php wp_nonce_field('dokan_amazon_mcf_settings', 'dokan_amazon_mcf_nonce'); ?>

                <div class="dokan-form-group">
                    <label for="amazon_seller_id">
                        <?php _e('Amazon Merchant ID (Seller ID)', 'dokan'); ?>
                    </label>
                    <input type="text" id="amazon_seller_id" class="dokan-form-control" name="amazon_seller_id"
                        value="<?php echo esc_attr($amazon_seller_id); ?>" required placeholder="e.g. A123456789BCDE" />
                </div>

                <div class="dokan-form-group">
                    <label for="amazon_marketplace_id">
                        <?php _e('Marketplace ID', 'dokan'); ?>
                    </label>
                    <input type="text" id="amazon_marketplace_id" class="dokan-form-control"
                        name="amazon_marketplace_id"
                        value="<?php echo esc_attr($amazon_marketplace ? $amazon_marketplace : 'A1F83G8C2ARO7P'); ?>"
                        required />
                </div>

                <div class="dokan-form-group">
                    <label for="amazon_refresh_token">
                        <?php _e('SP-API Refresh Token', 'dokan'); ?>
                    </label>
                    <textarea id="amazon_refresh_token" class="dokan-form-control" name="amazon_refresh_token" rows="3"
                        required placeholder="Atnr|..."><?php echo esc_textarea($amazon_refresh_token); ?></textarea>
                    <?php if ($is_connected): ?>
                        <small
                            class="text-muted"><?php _e('Leave unchanged unless you have a new token.', 'dokan'); ?></small>
                    <?php endif; ?>
                </div>

                <div class="dokan-form-group">
                    <button type="submit" class="dokan-btn dokan-btn-theme">
                        <i class="fas fa-save"></i>
                        <?php echo $is_connected ? __('Update Credentials', 'dokan') : __('Save & Connect', 'dokan'); ?>
                    </button>
                </div>
            </form>

        </div>
    </div>

    <?php if (!$is_connected): ?>
        <div class="dokan-panel dokan-panel-default">
            <div class="dokan-panel-heading">
                <strong><?php _e('Need Help?', 'dokan'); ?></strong>
            </div>
            <div class="dokan-panel-body">
                <p><?php _e('Having trouble connecting your Amazon account? Contact our support team:', 'dokan'); ?></p>
                <a href="mailto:support@brags.co.uk" class="dokan-btn dokan-btn-default">
                    <i class="fas fa-envelope"></i> support@brags.co.uk
                </a>
            </div>
        </div>
    <?php endif; ?>

</article>

<style>
    .dokan-settings-area .dokan-panel {
        border: 1px solid #ddd;
        border-radius: 4px;
        margin-bottom: 20px;
    }

    .dokan-settings-area .dokan-panel-heading {
        background: #f9f9f9;
        padding: 15px 20px;
        border-bottom: 1px solid #ddd;
    }

    .dokan-settings-area .dokan-panel-body {
        padding: 20px;
    }

    .dokan-settings-area .dokan-form-group label {
        font-weight: 600;
        margin-bottom: 8px;
        display: block;
    }
</style>