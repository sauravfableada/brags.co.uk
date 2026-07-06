<?php
$args = [
    'post_type'      => 'ticket',
    //'post_status'    => 'any',
    'posts_per_page' => 20,
    'orderby'        => 'date',
    'order'          => 'DESC',
];

$tickets = new WP_Query($args);



if (!$tickets->have_posts()) {
    return '<p>No tickets found.</p>';
}
?>

<table class="wp-list-table widefat fixed striped">
    <thead>
        <tr>
            <th>ID</th>
            <th>Title</th>
            <th>Status</th>
            <th>Author</th>
            <th>Date</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($tickets->have_posts()) : $tickets->the_post(); ?>
            <?php ?>
            <tr>
                <td>#<?php the_ID(); ?></td>
                <td><?php echo esc_html($post->post_title); ?></td>
                <td><?php echo get_post_status(); ?></td>
                <td><?php the_author(); ?></td>
                <td><?php echo get_the_date('Y-m-d H:i'); ?></td>
                <td>
                    <a href="<?php echo site_url('/my-account/brags-seller-support-tickets/?ticket_id=' . get_the_ID()); ?>" target="_blank">View</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<?php wp_reset_postdata(); ?>