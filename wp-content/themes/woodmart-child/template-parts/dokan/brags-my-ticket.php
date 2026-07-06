<?php
/**
 * Dokan My Ticket Page - Show Tickets Created by Customers
 */

if (!defined('ABSPATH')) {
    exit;
}

global $current_user;
get_header();

if (!dokan_is_user_seller($current_user->ID)) {
    dokan_get_template_part('global/account-denied');
    return;
}

// Fetch tickets where the current seller is the post author
$args = [
    'post_type'      => 'ticket',
    //'post_status'    => 'publish',
    'posts_per_page' => 20,
    'author'         => $current_user->ID, // Show only tickets where seller is the post author
    'orderby'        => 'date',
    'order'          => 'DESC',
];

$tickets = new WP_Query($args);
?>
<style>
    div#wpas_ticketlist_filters {
        display: none;
    }
</style>
<div class="dokan-dashboard-wrap">
    <?php do_action('dokan_dashboard_content_before'); ?>

    <div class="dokan-dashboard-content">
        <div class="dokan-my-ticket">
            <div class="header-div">
                <div class="widget-title">
                    <i class="fas fa-ticket-alt"></i> My Tickets
                    <span class="pull-right">
                        <a href="<?php echo home_url('/dashboard/brags-seller-team/'); ?>" class="wpas-btn wpas-btn-default">Brags Seller Team</a>
                    </span>
                </div>
            </div>

            <div id="ticketRequestsList">
                <!-- <h3>Customer Support Tickets</h3> -->
                <div id="ticketListContent">
                    <?php if ($tickets->have_posts()) : ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Status</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($tickets->have_posts()) : $tickets->the_post(); ?>
                                    <tr>
                                        <td>#<?php the_ID(); ?></td>
                                        <td><?php the_title(); ?></td>
                                        <td><?php echo get_post_status(); ?></td>
                                        <td><?php echo get_the_author_meta('display_name', get_post_field('post_author', get_the_ID())); ?></td>
                                        <td><?php echo get_the_date('Y-m-d H:i'); ?></td>
                                        <td>
                                            <a href="<?php echo site_url('/dashboard/brags-my-ticket/?ticket_id=' . get_the_ID()); ?>" class="wpas-btn wpas-btn-primary" target="_blank">View</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                        <?php wp_reset_postdata(); ?>
                    <?php else : ?>
                        <p>No customer tickets found.</p>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

    <?php do_action('dokan_dashboard_content_after'); ?>
</div>

<?php get_footer(); ?>
