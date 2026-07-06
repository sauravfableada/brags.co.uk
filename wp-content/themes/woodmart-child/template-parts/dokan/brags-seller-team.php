<?php
/**
 * Dokan Brags Seller Team Template
 */

if (!defined('ABSPATH')) {
    exit;
}

global $current_user, $wpdb;
get_header();

if (!dokan_is_user_seller($current_user->ID)) {
    dokan_get_template_part('global/account-denied');
    return;
}
?>
<style>
    .wpas-ticket-buttons-top{
        display: none;
    }
</style>
<div class="dokan-dashboard-wrap">
    <?php do_action('dokan_dashboard_content_before'); ?>

    <div class="dokan-dashboard-content">
        <div class="dokan-brags-seller-team">
            
            <div class="header-div">
                <div class="widget-title">
                    <i class="fas fa-users" aria-hidden="true"></i> Brags Seller Team Support
                    <span class="pull-right">

                        <a href="<?php echo home_url('/dashboard/brags-my-ticket/'); ?>" class="wpas-btn wpas-btn-default wpas-link-ticketlist">My Tickets</a>
                    </span>
                </div>
            </div>

            <div>

                <div id="supportRequestOverlay" class="overlay"></div>

                <div id="supportRequestPopup" class="popup">

                </div>

                <div id="supportRequestsList">
                    <h3>Your Support Requests</h3>
                    <div id="requestsListContent">
                        <?php echo do_shortcode('[ticket-submit]'); ?>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <?php do_action('dokan_dashboard_content_after'); ?>
</div>

<?php get_footer(); ?>
