<?php
/**
 * Dokan Single Announement Content Template
 *
 * @since   2.4
 *
 * @package dokan
 *
 * @var $notice Single
 */

use WeDevs\DokanPro\Announcement\Single;

?>
<article class="dokan-notice-single-notice-area">
    <header class="dokan-dashboard-header dokan-clearfix">
        <span class="left-header-content">
            <h2 class="entry-title"><?php echo $notice->get_title(); ?></h2>
        </span>
    </header>
    <span class="dokan-single-announcement-date"><i class="far fa-calendar-alt"></i> <?php echo dokan_format_date( $notice->get_date() ); ?></span>

    <div class="entry-content">
        <?php echo wp_kses_post( wpautop( $notice->get_content() ) ); ?>
    </div>

    <div class="dokan-announcement-link">
        <a href="<?php echo esc_url( dokan_get_navigation_url( 'announcement' ) ); ?>" class="dokan-btn dokan-btn-theme"><?php esc_html_e( 'Back to all Notice', 'dokan' ); ?></a>
    </div>
</article>
