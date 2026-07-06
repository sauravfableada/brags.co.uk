<?php

namespace WeDevs\DokanPro\Dashboard;

use WeDevs\Dokan\Cache;
use WeDevs\Dokan\Dashboard\Templates\Dashboard as DokanDashboard;
use WeDevs\Dokan\Utilities\ReportUtil;

/**
 * Dashboard Template Class.
 *
 * A template for frontend dashboard rendering items
 *
 * @since 2.4
 *
 * @author weDevs <info@wedevs.com>
 */
class Dashboard extends DokanDashboard {

    /**
     * Constructor for the WeDevs_Dokan class
     *
     * Sets up all the appropriate hooks and actions
     * within our plugin.
     *
     * @uses add_action()
     */
    public function __construct() {
        $this->user_id = dokan_get_current_user_id();

        add_action( 'dokan_dashboard_before_widgets', array( $this, 'show_profile_progressbar' ) );

        if ( ! ( class_exists( ReportUtil::class ) && ReportUtil::is_analytics_enabled() ) ) {
            add_action( 'dokan_dashboard_left_widgets', array( $this, 'get_review_widget' ), 16 );
            add_action( 'dokan_dashboard_right_widgets', array( $this, 'get_announcement_widget' ), 12 );
        }
    }

    /**
     * Show Profile progressbar
     *
     * @return void
     */
    public function show_profile_progressbar() {
        $transient_key = 'dokan_dashboard_closed_progressbar_' . get_current_user_id();
        $transient     = get_transient( $transient_key );
        if ( $transient ) {
            return;
        }
        if ( current_user_can( 'dokan_view_overview_menu' ) ) {
            echo dokan_get_profile_progressbar();
        }
    }

    /**
     * Get Review Widget
     *
     * @return void
     */
    public function get_review_widget() {
        if ( ! apply_filters( 'dokan_dashboard_widget_applicable', true, 'reviews' ) ) {
            return;
        }

        if ( ! current_user_can( 'dokan_view_overview_menu' ) ) {
            return;
        }

        if ( ! current_user_can( 'dokan_view_review_reports' ) ) {
            return;
        }

        // Check if product review is disabled from WooCommerce's setting.
        if ( 'yes' !== get_option( 'woocommerce_enable_reviews' ) ) {
            return;
        }

        dokan_get_template_part(
            'dashboard/review-widget', '', array(
				'pro'            => true,
				'comment_counts' => $this->get_comment_counts(),
				'reviews_url'    => dokan_get_navigation_url( 'reviews' ),
			)
        );
    }

    /**
     * Get announcement widget
     *
     * @return void
     */
    public function get_announcement_widget() {
        if ( ! apply_filters( 'dokan_dashboard_widget_applicable', true, 'announcement' ) ) {
            return;
        }

        if ( ! current_user_can( 'dokan_view_overview_menu' ) ) {
            return;
        }

        if ( ! current_user_can( 'dokan_view_announcement' ) ) {
            return;
        }

        $announcement = dokan_pro()->announcement->manager;
        $args         = [
            'per_page'  => apply_filters( 'dokan_dashboard_widget_announcement_list_number', 3 ),
            'vendor_id' => dokan_get_current_user_id(),
        ];
        $notices      = $announcement->all( $args );

        dokan_get_template_part(
            'dashboard/announcement-widget', '', array(
				'pro'              => true,
				'notices'          => $notices,
				'announcement_url' => dokan_get_navigation_url( 'announcement' ),
			)
        );
    }
}
