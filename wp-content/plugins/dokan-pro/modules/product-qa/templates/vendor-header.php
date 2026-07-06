<?php
/**
 * Vendor Questions & Answers Template Header.
 *
 * @since 3.11.0
 */

defined( 'ABSPATH' ) || exit;
?>
<header class='dokan-dashboard-header'>
    <h1 class='entry-title'>
        <?php esc_html_e( 'Product Questions & Answers', 'dokan' ); ?>
        <?php do_action('dokan_product_qa_inside_header_content' ); ?>
    </h1>
</header><!-- .entry-header -->
