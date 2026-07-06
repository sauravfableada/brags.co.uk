<?php
/**
 *  Template Name: Report a seller
 */


 if (!defined('ABSPATH')) {
    exit;
}

global $current_user, $wpdb;
get_header();

if (!in_array('customer', (array) $current_user->roles)) {
    dokan_get_template_part('global/account-denied');
    return;
}

 ?>


<div class="site-content col-lg-12 col-12 col-md-12" role="main">
    <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
        <div class="entry-content">
                <div class="dokan-dashboard-wrap">
                    <div class="dokan-dashboard-content">
                        <?php the_content(); ?>
                        <?php if ( isset($_GET['report_all_status']) && $_GET['report_all_status'] === 'success' ) : ?>
                            <div class="report-confirmation-message" style="padding: 10px; background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; margin-bottom: 20px;">
                                <?php esc_html_e('Thank you for Reporting a Seller, we will review the information provided as soon as possible.', 'your-textdomain'); ?>
                            </div>
                        <?php endif; ?>
                        <div class="report-seller-form">
                            <form id="report-seller-form" method="post" enctype="multipart/form-data">
                                <?php wp_nonce_field( 'seller_form_action', 'seller_form_nonce' ); ?>

                                <div class="dokan-form-group">
                                    <label for="report_seller_your_name"><?php _e( 'Your Name', 'your-textdomain' ); ?> *</label>
                                    <input type="text" id="report_seller_your_name" name="report_seller_your_name" required>
                                </div>
                                <div class="dokan-form-group">
                                    <label for="report_seller_your_email"><?php _e( 'Your Customer Email Address', 'your-textdomain' ); ?> *</label>
                                    <input type="email" id="report_seller_your_email" name="report_seller_your_email" required>
                                </div>
                                <div class="dokan-form-group">
                                    <label for="report_seller_your_company"><?php _e( 'Your Company Name', 'your-textdomain' ); ?></label>
                                    <input type="text" id="report_seller_your_company" name="report_seller_your_company">
                                </div>
                                <div class="dokan-form-group">
                                    <label for="report_seller_seller_name"><?php _e( 'Seller Name', 'your-textdomain' ); ?>  *</label>
                                    <input type="text" id="report_seller_seller_name" name="report_seller_seller_name" required>
                                </div>
                                <div class="dokan-form-group">
                                    <label for="report_seller_seller_url"><?php _e( 'Seller Store URL', 'your-textdomain' ); ?></label>
                                    <input type="url" id="report_seller_seller_url" name="report_seller_seller_url">
                                </div>
                                <div class="dokan-form-group">
                                    <label for="report_seller_order_id"><?php _e( 'Your Order ID', 'your-textdomain' ); ?></label>
                                    <input type="text" id="report_seller_order_id" name="report_seller_order_id">
                                </div>
                                <div class="dokan-form-group">
                                    <label for="report_seller_your_message"><?php _e( 'Your Message', 'your-textdomain' ); ?> *</label>
                                   <textarea id="report_seller_your_message" name="report_seller_your_message" required></textarea>
                                </div>
                                <div class="dokan-form-group">
                                    <label for="report_seller_sellering_files"><?php _e( 'Upload sellering Evidence', 'your-textdomain' ); ?></label>
                                    <input type="file" id="report_seller_sellering_files" name="report_seller_sellering_files[]" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                </div>
                                <button type="submit" class="btn btn-seller" name="submit_seller_form"><?php _e( 'Submit Report', 'your-textdomain' ); ?></button>

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