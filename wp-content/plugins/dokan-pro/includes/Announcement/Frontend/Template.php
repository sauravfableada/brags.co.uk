<?php

namespace WeDevs\DokanPro\Announcement\Frontend;

use WeDevs\DokanPro\Announcement\Manager;
use WeDevs\DokanPro\Announcement\Single;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Dokan Announcement class for Vendor
 *
 * @since   3.9.4
 *
 * @package DokanPro\Announcement\Frontend
 */
class Template {
    /**
     * Constructor method
     *
     * @since 3.9.4
     */
    public function __construct() {
        add_action( 'dokan_load_custom_template', [ $this, 'load_announcement_template' ], 10 );
        add_action( 'dokan_announcement_content_area_header', [ $this, 'load_header_template' ] );
        add_action( 'dokan_announcement_content', [ $this, 'load_announcement_content' ], 10 );
        add_action( 'dokan_single_announcement_content', [ $this, 'load_single_announcement_content' ], 10 );
        add_filter( 'dokan_get_dashboard_nav', [ $this, 'add_announcement_page' ], 15 );
        add_filter( 'dokan_dashboard_nav_active', [ $this, 'active_announcement_nav_menu' ], 11, 3 );

        // Announcement ajax handling
        add_action( 'wp_ajax_dokan_announcement_remove_row', [ $this, 'remove_announcement' ] );
        add_filter( 'dokan_get_dashboard_nav_template_dependency', [ $this, 'announcement_template_dependency' ] );

        // Localize announcement modal data for vendor dashboard
        add_action( 'wp_enqueue_scripts', [ $this, 'localize_announcement_modal_data' ], 30 );
    }

    /**
     * Render announcement template
     *
     * @since  2.2
     * @since  3.9.4 moved this method from Announcement class
     *
     * @param array $query_vars
     *
     * @return void
     */
    public function load_announcement_template( $query_vars ) {
        if ( isset( $query_vars['announcement'] ) ) {
            dokan_get_template_part(
                'announcement/announcement', '', [
                    'pro'          => true,
                    'announcement' => $this,
                ]
            );

            return;
        }
        if ( isset( $query_vars['single-announcement'] ) ) {
            dokan_get_template_part( 'announcement/single-announcement', '', [ 'pro' => true ] );

            return;
        }
    }

    /**
     * Render Announcement listing template header
     *
     * @since 2.2
     *
     * @return void
     */
    public function load_header_template() {
        dokan_get_template_part( 'announcement/header', '', [ 'pro' => true ] );
    }

    /**
     * Load announcement Content
     *
     * @since  2.4
     * @since  3.9.4 moved this method from Announcement class
     *
     * @return void
     */
    public function load_announcement_content() {
        $pagenum  = isset( $_GET['pagenum'] ) ? absint( $_GET['pagenum'] ) : 1; //phpcs:ignore
        $per_page = apply_filters( 'dokan_announcement_list_number', 10 );

        $args = [
            'vendor_id' => dokan_get_current_user_id(),
            'page'      => $pagenum,
            'per_page'  => $per_page,
            'status'    => 'publish',
            'return'    => 'all',
        ];

        $manager         = dokan_pro()->announcement->manager;
        $announcements   = $manager->all( $args );
        $pagination_data = $manager->get_pagination_data( $args );

        dokan_get_template_part(
            'announcement/listing-announcement', '', array_merge(
                [
                    'pro'          => true,
                    'notices'      => $announcements,
                    'current_page' => $pagenum,
                ],
                $pagination_data
            )
        );
    }

    /**
     * Load Single announcement content
     *
     * @since  2.4
     * @since  3.9.4 moved this method from Announcement class
     *
     * @return void
     */
    public function load_single_announcement_content() {
        $notice_id = get_query_var( 'single-announcement' );

        $manager = new Manager();
        $notice  = $manager->get_notice( $notice_id );

        if ( ! $notice instanceof Single ) {
            dokan_get_template_part( 'announcement/no-announcement', '', [ 'pro' => true ] );

            return;
        }

        if ( 'unread' === $notice->get_read_status() ) {
            $manager->update_read_status( $notice_id, 'read' );
            $notice = $notice->set_read_status( 'read' );
        }

        dokan_get_template_part(
            'announcement/single-notice', '', [
                'pro'    => true,
                'notice' => $notice,
            ]
        );
    }

    /**
     * Add announcement page in seller dashboard
     *
     * @since  3.9.4 moved this method from Announcement class
     *
     * @param array $urls
     *
     * @return array $urls
     */
    public function add_announcement_page( $urls ) {
        if ( current_user_can( 'dokandar' ) ) {
            $urls['announcement'] = [
                'title'       => esc_html__( 'Announcements', 'dokan' ),
                'icon'        => '<i class="fas fa-bell"></i>',
                'url'         => dokan_get_navigation_url( 'announcement' ),
                'pos'         => 181,
                'icon_name'   => 'Megaphone',
                'react_route' => 'announcement',
                'permission'  => 'dokan_view_announcement',
                'counts'      => $this->get_unread_count(),
            ];
        }

        return $urls;
    }

    /**
     * Get unread announcement count for the current vendor.
     *
     * @since 5.0.0
     *
     * @return int
     */
    protected function get_unread_count(): int {
        $vendor_id = dokan_get_current_user_id();

        if ( ! $vendor_id ) {
            return 0;
        }

        $count = dokan_pro()->announcement->manager->all(
            [
                'vendor_id'   => $vendor_id,
                'read_status' => 'unread',
                'status'      => 'publish',
                'return'      => 'count',
            ]
        );

        return is_wp_error( $count ) ? 0 : (int) $count;
    }

    /**
     * Localize announcement modal data for the vendor dashboard.
     *
     * Passes the latest unread announcement and dismissal state
     * so the frontend modal can determine whether to auto-open.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function localize_announcement_modal_data() {
        if ( ! dokan_is_seller_dashboard() ) {
            return;
        }

        if ( ! current_user_can( 'dokan_view_announcement' ) ) {
            return;
        }

        // dokan_get_current_user_id() returns the parent vendor ID for staff members.
        $vendor_id = dokan_get_current_user_id();

        if ( ! $vendor_id ) {
            return;
        }

        // Get the latest unread announcement for this vendor.
        $latest_unread = dokan_pro()->announcement->manager->all(
            [
                'vendor_id'   => $vendor_id,
                'read_status' => 'unread',
                'status'      => 'publish',
                'per_page'    => 1,
                'page'        => 1,
            ]
        );

        $latest_data       = null;
        $unread_count      = $this->get_unread_count();
        $last_dismissed_id = (int) get_user_meta( $vendor_id, 'dokan_last_dismissed_announcement_id', true );

        if ( $latest_unread instanceof Single ) {
            $latest_data = $latest_unread->get_data();
        }

        wp_add_inline_script(
            'dokan-pro-announcement',
            'window.dokanAnnouncementModal = ' . wp_json_encode(
                [
                    'latestUnread'    => $latest_data,
                    'unreadCount'     => $unread_count,
                    'lastDismissedId' => $last_dismissed_id,
                    'announcementUrl' => dokan_get_navigation_url( 'announcement', true ),
                ]
            ) . ';',
            'before'
        );
    }

    /**
     * Set announcement template dependency
     *
     * @param array $dependencies
     *
     * @return array
     */
    public function announcement_template_dependency( array $dependencies ): array {
		$dependencies['announcement'] = [
            [
                'slug' => 'announcement/announcement',
                'name' => '',
                'args' => [],
            ],
			[
				'slug' => 'announcement/listing-announcement',
				'name' => '',
                'args' => [],
			],
            [
                'slug' => 'announcement/single-announcement',
                'name' => '',
                'args' => [],
            ],
            [
                'slug' => 'announcement/no-announcement',
                'name' => '',
                'args' => [],
            ],
            [
                'slug' => 'announcement/header',
                'name' => '',
                'args' => [],
            ],
            [
                'slug' => 'announcement/single-notice',
                'name' => '',
                'args' => [],
            ],
		];

		return $dependencies;
	}

    /**
     * Set announcement menu as active.
     *
     * @since  3.7.18
     * @since  3.9.4 moved this method from Announcement class
     *
     * @param string $active_menu Currently active menu slug.
     * @param string $request_uri Request URI.
     * @param array  $query_vars  Currently active query vars.
     *
     * @return string
     */
    public function active_announcement_nav_menu( string $active_menu, $request_uri, array $query_vars ): string {
        if ( ! in_array( 'single-announcement', $query_vars, true ) ) {
            return $active_menu;
        }

        return 'announcement';
    }

    /**
     * Remove Announcement ajax
     *
     * @since  2.4
     * @since  3.9.4 moved this method from Announcement class
     *
     * @return void
     */
    public function remove_announcement() {
        check_ajax_referer( 'dokan_reviews' );

        $notice_id = isset( $_POST['row_id'] ) ? absint( $_POST['row_id'] ) : 0;
        if ( ! $notice_id ) {
            wp_send_json_error();
        }
        $result = dokan_pro()->announcement->manager->delete_notice( $notice_id );

        ob_start();
        ?>
        <div class="dokan-no-announcement">
            <div class="annoument-no-wrapper">
                <i class="fas fa-bell dokan-announcement-icon"></i>
                <p><?php esc_html_e( 'No Announcement found', 'dokan' ); ?></p>
            </div>
        </div>
        <?php
        $content = ob_get_clean();

        if ( $result ) {
            wp_send_json_success( $content );
        } else {
            wp_send_json_error();
        }
    }
}
