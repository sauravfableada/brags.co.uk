<?php
/**
 *  Template Name: Report Product
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
                        <div class="report-Product-form">
                            <form id="report-product-form" method="post" enctype="multipart/form-data">
                                <?php wp_nonce_field( 'report_product_form_action', 'report_product_form_nonce' ); ?>

                                <div class="dokan-form-group">
                                    <label for="report_product_your_name"><?php _e( 'Your Name', 'your-textdomain' ); ?></label>
                                    <input type="text" id="report_product_your_name" name="report_product_your_name">
                                </div>
                                <div class="dokan-form-group">
                                    <label for="report_product_your_email"><?php _e( 'Your Customer Email Address', 'your-textdomain' ); ?></label>
                                    <input type="email" id="report_product_your_email" name="report_product_your_email">
                                </div>
                                <div class="dokan-form-group">
                                    <label for="report_product_your_company"><?php _e( 'Your Company Name', 'your-textdomain' ); ?></label>
                                    <input type="text" id="report_product_your_company" name="report_product_your_company">
                                </div>
                                <div class="dokan-form-group">
                                    <label for="report_product_seller_name"><?php _e( 'Seller Name', 'your-textdomain' ); ?>  *</label>
                                    <input type="text" id="report_product_seller_name" name="report_product_seller_name" required>
                                </div>
                                <div class="dokan-form-group">
                                    <label for="report_product_seller_url"><?php _e( 'Seller Store URL', 'your-textdomain' ); ?></label>
                                    <input type="url" id="report_product_seller_url" name="report_product_seller_url">
                                </div>
                                <div class="dokan-form-group">
                                    <label for="report_product_Link_Product_Listing"><?php _e( 'Link to Product Listing', 'your-textdomain' ); ?></label>
                                    <input type="text" id="report_product_Link_Product_Listing" name="report_product_Link_Product_Listing">
                                </div>
                                <div class="dokan-form-group">
                                    <label for="report_product_order_id"><?php _e( 'Your Order ID', 'your-textdomain' ); ?></label>
                                    <input type="text" id="report_product_order_id" name="report_product_order_id">
                                </div>
                                <div class="dokan-form-group">
                                    <label for="report_product_your_message"><?php _e( 'Your Message', 'your-textdomain' ); ?> *</label>
                                   <textarea id="report_product_your_message" name="report_product_your_message" required></textarea>
                                </div>
                                <div class="dokan-form-group">
                                    <label for="report_product_supporting_files"><?php _e( 'Upload Supporting Evidence', 'your-textdomain' ); ?></label>
                                    <input type="file" id="report_product_supporting_files" name="report_product_supporting_files[]" multiple accept=".pdf,.doc,.docx,.jpeg,.png">
                                </div>
                                <button type="submit" class="btn btn-report-product" name="submit_report_product"><?php _e( 'Submit Report', 'your-textdomain' ); ?></button>
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


<?php get_footer(); ?>