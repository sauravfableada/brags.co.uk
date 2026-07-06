<?php
/*
Template Name: Seller Score Page
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
                        <?php the_content(); ?>
                    </div>
                </div>
            </div>
        </div>
    </article>
</div>

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



<?php

get_footer();
?>
