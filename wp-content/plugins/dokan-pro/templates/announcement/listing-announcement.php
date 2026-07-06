<?php
/**
 * Dokan Announcement Listing Template
 *
 * @since   2.2
 *
 * @package dokan
 *
 * @var Single[] $notices
 * @var int      $current_page
 * @var int      $total_count
 * @var int      $total_pages
 * @var int      $per_page
 */

use WeDevs\DokanPro\Announcement\Single;

?>
<div class="dokan-announcement-wrapper">
    <?php
    if ( empty( $notices ) ) {
        ?>
        <div class="dokan-no-announcement">
            <div class="annoument-no-wrapper">
                <i class="fas fa-bell dokan-announcement-icon"></i>
                <p><?php esc_html_e( 'No announcement found.', 'dokan' ); ?></p>
            </div>
        </div>
        <?php
        return;
    }
    foreach ( $notices as $notice ) {
        $notice_url = trailingslashit( dokan_get_navigation_url( 'single-announcement' ) . $notice->get_notice_id() );
        ?>
        <div class="dokan-announcement-wrapper-item <?php echo ( $notice->get_read_status() === 'unread' ) ? 'dokan-announcement-uread' : ''; ?>">
            <div class="announcement-action">
                <a href="#" class="remove_announcement" data-notice_row= <?php echo $notice->get_notice_id(); ?>><i class="fas fa-times"></i></a>
            </div>
            <div class="dokan-annnouncement-date dokan-left">
                <div class="announcement-day"><?php echo dokan_format_date( $notice->get_date(), 'd' ); ?></div>
                <div class="announcement-month"><?php echo dokan_format_date( $notice->get_date(), 'F' ); ?></div>
                <div class="announcement-year"><?php echo dokan_format_date( $notice->get_date(), 'Y' ); ?></div>
            </div>
            <div class="dokan-announcement-content-wrap dokan-left">
                <div class="dokan-announcement-heading">
                    <a href="<?php echo esc_url( $notice_url ); ?>">
                        <h3><?php echo $notice->get_title(); ?></h3>
                    </a>
                </div>

                <div class="dokan-announcement-content">
                    <?php echo wp_trim_words( $notice->get_content(), '15', sprintf( '<p><a href="%1$s">%2$s</a></p>', esc_url( $notice_url ), __( ' See More', 'dokan' ) ) ); ?>
                </div>
            </div>
            <div class="dokan-clearfix"></div>
        </div>
        <?php
    }
    ?>
</div>

<div class="pagination-wrap">
    <?php
    $base_url   = dokan_get_navigation_url( 'announcement' );
    $page_links = paginate_links(
        [
            'current'   => $current_page,
            'total'     => $total_pages,
            'base'      => $base_url . '%_%',
            'format'    => '?pagenum=%#%',
            'add_args'  => false,
            'type'      => 'array',
            'prev_text' => __( '&laquo; Previous', 'dokan' ),
            'next_text' => __( 'Next &raquo;', 'dokan' ),
        ]
    );

    if ( $page_links ) {
        echo '<ul class="pagination"><li>';
        echo join( "</li>\n\t<li>", $page_links );
        echo "</li>\n</ul>\n";
    }
    ?>
</div>
