<?php

/**
 * Dokan Category Approval Template
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

<div class="dokan-dashboard-wrap">
    <?php do_action('dokan_dashboard_content_before'); ?>

    <div class="dokan-dashboard-content">
        <div class="dokan-category-approval">


            <div class="header-div">
                <div class="widget-title">
                    <i class="fas fa-bullhorn" aria-hidden="true"></i> Category Evaluation
                    <span class="pull-right">
                        <button id="requestCategoryApproval">Category Evaluation Request </button>
                    </span>
                </div>

            </div>
            <div>

                <div id="categoryApprovalOverlay" class="overlay"></div>

                <div id="categoryApprovalPopup" class="popup">
                    <div class="popup-header">
                        <h3>Request Category Evaluation</h3>
                        <button id="closePopup" class="close-btn">&times;</button>
                    </div>
                    <div>
                        <p><?php _e('If you are unsure on specific documentation that may be required to gain Approval,<a href="/category-evaluation-programme">please click here to learn more about Brags Category Evaluation Programme</a>  You can upload up to 10 Documents/Images in the following formats: PNG, JPEG, PDF, Word.', 'dokan'); ?></p>
                    </div>
                    <?php
                    $categories = get_terms(array('taxonomy' => 'product_cat', 'hide_empty' => false));
                    $filtered_categories = array();

                    if (!empty($categories) && !is_wp_error($categories)) {
                        foreach ($categories as $category) {
                            $requires_approval = get_term_meta($category->term_id, 'requires_approval', true);
                            if ($requires_approval === 'yes') {



                                $exists = $wpdb->get_var($wpdb->prepare(
                                    "SELECT COUNT(*) FROM {$wpdb->prefix}category_approval_requests WHERE user_id = %d AND category_id = %d",
                                    $current_user->ID,
                                    $category->term_id
                                ));



                                if (!$exists) {
                                    $filtered_categories[] = $category;
                                }
                            }
                        }
                    }

                    ?>

                    <form id="categoryApprovalForm" method="post" enctype="multipart/form-data">
                        <label for="category">Select Category:</label>
                        <select name="category" id="category" required>
                            <?php

                            foreach ($filtered_categories as $category) {
                                echo '<option value="' . $category->term_id . '">' . $category->name . '</option>';
                            }
                            ?>
                        </select>

                        <label for="category_docs">Upload Documents:</label>
                        <input type="file" name="category_docs[]" multiple required>

                        <div class="popup-footer">
                            <button type="submit" id="submitCategoryRequest" class="primary-btn">Submit Request</button>
                            <button type="button" id="closePopupSecondary" class="secondary-btn">Cancel</button>
                        </div>
                    </form>
                </div>


                <div id="categoryRequestsList">
                    <!-- <h3>Category Approval Requests</h3> -->
                    <div id="requestsListContent">
                        <?php include(get_stylesheet_directory() . '/template-parts/dokan/category-approval-requests-list.php'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php do_action('dokan_dashboard_content_after'); ?>
</div>

<?php get_footer(); ?>