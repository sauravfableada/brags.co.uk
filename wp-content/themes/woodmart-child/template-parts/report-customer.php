<?php
/**
 *  Template Name: Report a Customer
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


<div class="site-content col-lg-12 col-12 col-md-12" role="main">
    <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
        <div class="entry-content">
                <div class="dokan-dashboard-wrap">
                    <?php do_action('dokan_dashboard_content_before'); ?>
                    <div class="dokan-dashboard-content">
                    <?php if ( isset($_GET['report_customer_status']) && $_GET['report_customer_status'] === 'success' ) : ?>
                            <div class="report-confirmation-message" style="padding: 10px; background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; margin-bottom: 20px;">
                                <?php esc_html_e('Thank you for Reporting a Customer, we will review the information provided as soon as possible.', 'your-textdomain'); ?>
                            </div>
                        <?php endif; ?>

	                <h2><?php _e( 'Report a Customer', 'your-textdomain' ); ?></h2>
                        <div class="report-customer-form">
                            <form id="report-customer-form" method="post" enctype="multipart/form-data">
                                <?php wp_nonce_field( 'report_customer_action', 'report_customer_nonce' ); ?>

                                <div class="dokan-form-group">
                                    <label><?php _e( 'Your Name', 'your-textdomain' ); ?></label>
                                    <input type="text" name="seller_name" required>
                                </div>
                                <div class="dokan-form-group">
                                    <label><?php _e( 'Your Seller Email Address', 'your-textdomain' ); ?></label>
                                    <input type="email" name="seller_email" required>
                                </div>
                                <div class="dokan-form-group">
                                    <label><?php _e( 'Your Company Name', 'your-textdomain' ); ?></label>
                                    <input type="text" name="company_name">
                                </div>
                                <div class="dokan-form-group">
                                    <label><?php _e( 'Customer Name', 'your-textdomain' ); ?> *</label>
                                    <input type="text" name="customer_name" required>
                                </div>
                                <div class="dokan-form-group">
                                    <label><?php _e( 'Order ID', 'your-textdomain' ); ?></label>
                                    <input type="text" name="order_id">
                                </div>
                                <div class="dokan-form-group">
                                    <label><?php _e( 'Your Message', 'your-textdomain' ); ?> *</label>
                                    <textarea name="message" required></textarea>
                                </div>
                                <div class="dokan-form-group">
                                    <label><?php _e( 'Upload Supporting Evidence', 'your-textdomain' ); ?></label>
                                    <input type="file" name="evidence_files[]" multiple accept=".pdf,.doc,.docx,.jpeg,.png">
                                </div>

                                <button type="submit" class="btn btn-report" name="submit_report_customer"><?php _e( 'Submit Report', 'your-textdomain' ); ?></button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </article>
</div>

<?php

// do_action('custom_add_display');

?>

<style>
    @media only screen and (max-width: 1200px) {
    .role-seller .main-page-wrapper .breadcrumbs {
        position: absolute;
        width: 100%;
        top: 110%;
        left: 0;
    }
}
</style>



<script>

jQuery(document).ready(function($) {
    var msgBox = $('.report-confirmation-message');
    if (msgBox.length) {
        $('html, body').animate({
            scrollTop: msgBox.offset().top
        }, 800);
    }
});
</script>



<?php get_footer(); ?>