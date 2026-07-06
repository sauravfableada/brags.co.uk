<?php
/**
 *  Dokan Dashboard Template
 *
 *  Dokan Dahsboard Announcement widget template
 *
 * @since   2.4
 *
 * @package dokan
 *
 * @var $notices          \WeDevs\DokanPro\Announcement\Single[]|WP_Error
 * @var $announcement_url string
 */
?>
<div class="dashboard-widget dokan-announcement-widget">
    <div class="widget-title">
        <i class="fas fa-bullhorn" aria-hidden="true"></i> <?php esc_html_e( 'Latest Announcement', 'dokan' ); ?>

        <span class="pull-right">
            <a href="<?php echo $announcement_url; ?>"><?php esc_html_e( 'See All', 'dokan' ); ?></a>
        </span>
    </div>
    <?php if ( is_wp_error( $notices ) || empty( $notices ) ) : ?>
        <div class="dokan-no-announcement">
            <div class="annoument-no-wrapper">
                <i class="fas fa-bell dokan-announcement-icon"></i>
                <p><?php esc_html_e( 'No announcement found', 'dokan' ); ?></p>
            </div>
        </div>
    <?php elseif ( $notices ) : ?>
        <ul class="list-unstyled">
            <?php foreach ( $notices as $notice ) : ?>
                <?php
                $notice_url = trailingslashit( dokan_get_navigation_url( 'single-announcement' ) . $notice->get_notice_id() );
                ?>
                <li>
                    <div class="dokan-dashboard-announce-content dokan-left">
                        <a href="<?php echo $notice_url; ?>"><h3><?php echo $notice->get_title(); ?></h3></a>
                        <?php echo wp_trim_words( $notice->get_content(), 6, '...' ); ?>
                    </div>
                    <div class="dokan-dashboard-announce-date dokan-right <?php echo ( $notice->get_read_status() === 'unread' ) ? 'dokan-dashboard-announce-unread' : 'dokan-dashboard-announce-read'; ?>">
                        <div class="announce-day"><?php echo dokan_format_date( $notice->get_date(), 'd' ); ?></div>
                        <div class="announce-month"><?php echo dokan_format_date( $notice->get_date(), 'F' ); ?></div>
                        <div class="announce-year"><?php echo dokan_format_date( $notice->get_date(), 'Y' ); ?></div>
                    </div>
                    <div class="dokan-clearfix"></div>
                </li>
            <?php endforeach ?>
        </ul>
    <?php endif ?>
</div> <!-- .products -->
