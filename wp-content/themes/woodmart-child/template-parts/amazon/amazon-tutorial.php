<?php
/**
 * Amazon Tutorial Page for Sellers (Dokan Dashboard)
 * 
 * Provides a guide for vendors on how to use the Amazon integration
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="dokan-dashboard-wrap">
    <?php dokan_get_template_part('global/dashboard', 'nav'); ?>

    <div class="dokan-dashboard-content dokan-amazon-tutorial-content">

        <?php do_action('dokan_dashboard_content_inside_before'); ?>

        <header class="dokan-dashboard-header dokan-clearfix">
            <h1 class="entry-title">
                <i class="fas fa-book"></i>
                <?php _e('Amazon Integration Guide', 'dokan'); ?>
            </h1>
        </header>

        <div class="dokan-panel dokan-panel-default">
            <div class="dokan-panel-body" style="padding: 40px; line-height: 1.6;">

                <h2 style="color: #232F3E; margin-top: 0;">
                    <?php _e('Getting Started with Amazon MCF', 'dokan'); ?>
                </h2>
                <p>
                    <?php _e('Brags allows you to sync your products with Amazon FBA and have Amazon ship your orders directly to customers. Follow these steps to set up your integration.', 'dokan'); ?>
                </p>

                <hr style="margin: 30px 0; border-top: 1px solid #eee;">

                <div class="tutorial-step">
                    <h3><span class="step-number">1</span>
                        <?php _e('Connect Your Account', 'dokan'); ?>
                    </h3>
                    <p>
                        <?php _e('Go to <strong>Settings → Amazon Fulfillment</strong>. Click the <strong>"Connect with Amazon"</strong> button to authorize Brags to access your Amazon Seller Central account. You will be redirected to Amazon to confirm.', 'dokan'); ?>
                    </p>
                </div>

                <div class="tutorial-step">
                    <h3><span class="step-number">2</span>
                        <?php _e('Link Your Products', 'dokan'); ?>
                    </h3>
                    <p>
                        <?php _e('Edit any product on your dashboard. You will see a new <strong>Amazon Integration</strong> section. Enter the <strong>ASIN</strong> and <strong>SKU</strong> for that product as it appears in your Amazon Seller Central.', 'dokan'); ?>
                    </p>
                    <div class="dokan-alert dokan-alert-info">
                        <strong>
                            <?php _e('Tip:', 'dokan'); ?>
                        </strong>
                        <?php _e('Ensure the SKU on Brags matches exactly with the SKU in Amazon Seller Central.', 'dokan'); ?>
                    </div>
                </div>

                <div class="tutorial-step">
                    <h3><span class="step-number">3</span>
                        <?php _e('Automatic Fulfillment', 'dokan'); ?>
                    </h3>
                    <p>
                        <?php _e('Once your products are linked and your account is connected, any order containing those products will be automatically sent to Amazon for fulfillment (MCF) as soon as the order status changes to "Processing".', 'dokan'); ?>
                    </p>
                </div>

                <div class="tutorial-step">
                    <h3><span class="step-number">4</span>
                        <?php _e('Monitor Sync & Stock', 'dokan'); ?>
                    </h3>
                    <p>
                        <?php _e('Under the <strong>Amazon → Listings</strong> menu, you can see the current stock levels from Amazon FBA. The <strong>Reports & Logs</strong> section will show you if there were any errors during the sync process.', 'dokan'); ?>
                    </p>
                </div>

                <div class="help-section"
                    style="background: #f9f9f9; padding: 25px; border-radius: 8px; margin-top: 40px; border-left: 5px solid #FF9900;">
                    <h4 style="margin-top: 0;"><i class="fas fa-question-circle"></i>
                        <?php _e('Need Advanced Help?', 'dokan'); ?>
                    </h4>
                    <p>
                        <?php _e('If you see "SKU Mismatch" or "Authorisation Error" in your reports, please ensure your SP-API refresh token is still valid. For complex issues, contact our support team.', 'dokan'); ?>
                    </p>
                    <a href="mailto:support@brags.co.uk" class="dokan-btn dokan-btn-theme">
                        <?php _e('Contact Support', 'dokan'); ?>
                    </a>
                </div>

            </div>
        </div>

        <?php do_action('dokan_dashboard_content_inside_after'); ?>

    </div>
</div>

<style>
    .dokan-amazon-tutorial-content .dokan-panel {
        border: 1px solid #eee;
        background: #fff;
    }

    .tutorial-step {
        margin-bottom: 35px;
    }

    .tutorial-step h3 {
        font-size: 18px;
        color: #333;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .step-number {
        background: #FF9900;
        color: #fff;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        font-weight: bold;
        flex-shrink: 0;
    }

    .help-section i {
        color: #FF9900;
        margin-right: 5px;
    }
</style>