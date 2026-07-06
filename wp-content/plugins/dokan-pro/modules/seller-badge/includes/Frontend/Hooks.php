<?php

namespace WeDevs\DokanPro\Modules\SellerBadge\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // exit if accessed directly
}

/**
 * Class Feature product count badge
 *
 * @since   3.7.14
 *
 * @package WeDevs\DokanPro\Modules\SellerBadge\Events
 */
class Hooks {

    /**
     * Endpoint for vendor dashboard.
     *
     * @var string $vendor_endpoint
     */
    protected $vendor_endpoint = 'seller-badge';

    /**
     * Load automatically when class initiate.
     *
     * @since 3.7.14
     */
    public function __construct() {
        // For vendor
        add_filter( 'dokan_get_dashboard_nav', [ $this, 'add_menu' ], 10 );
        add_filter( 'dokan_query_var_filter', [ $this, 'badge_endpoints' ] );
        add_action( 'dokan_load_custom_template', [ $this, 'load_badge_template' ] );
        add_action( 'dokan_seller_badge_content', [ $this, 'load_vue_root_template' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_script' ] );
        add_filter( 'dokan_get_dashboard_nav_template_dependency', [ $this, 'get_dashboard_nav_template_dependency' ] );
    }

    /**
     * Add menu to dashboard
     *
     * @param array $menu_items
     *
     * @return array
     */
    public function add_menu( $menu_items ) {
        $menu = [
            'title'       => __( 'Badge', 'dokan' ),
            'icon'        => '<i class="fas fa-award"></i>',
            'url'         => dokan_get_navigation_url( $this->vendor_endpoint ),
            'pos'         => 73,
            'icon_name'   => 'BadgeCheck',
            'permission'  => 'dokan_view_badge_menu',
            'react_route' => $this->vendor_endpoint,
        ];

        if ( dokan_is_seller_enabled( dokan_get_current_user_id() ) ) {
            $menu_items[ $this->vendor_endpoint ] = $menu;
        }

        return $menu_items;
    }

    /**
     * Add badge endpoint
     *
     * @param array $query_vars
     *
     * @return array
     */
    public function badge_endpoints( $query_vars ) {
        $query_vars[] = $this->vendor_endpoint;

        return $query_vars;
    }

    /**
     * Load badge template
     *
     * @param array $query_vars
     *
     * @return void
     */
    public function load_badge_template( $query_vars ) {
        if ( ! isset( $query_vars[ $this->vendor_endpoint ] ) ) {
            return;
        }

        if ( ! is_user_logged_in() || ! dokan_is_seller_enabled( dokan_get_current_user_id() ) ) {
            dokan_get_template_part(
                'global/dokan-error', '', [
                    'deleted' => false,
                    'message' => __( 'You have no permission to view this requests page', 'dokan' ),
                ]
            );

            return;
        }

        dokan_get_template_part(
            'seller_badge_list_table', '', [
                'seller_badge_list_template' => true,
            ]
        );
    }

    /**
     * Load vue root component
     *
     * @since 3.7.14
     *
     * @return void
     */
    public function load_vue_root_template() {
        $html = <<<EOD
<!--Initial point to load frontend seller badge list table in vue js-->
<div id='dokan-vue-seller-badge'></div>
EOD;
        echo $html;
    }

    /**
     * Enqueue script
     *
     * @since 3.7.14
     *
     * @return void
     */
    public function enqueue_script() {
        global $wp;

        if ( isset( $wp->query_vars[ $this->vendor_endpoint ] ) ) {
            wp_enqueue_script( 'dokan-seller-badge-frontend' );
            wp_enqueue_style( 'dokan-seller-badge-frontend' );
        }
    }

    /**
     * Get dashboard nav template dependency.
     *
     * @since 5.0.0
     *
     * @param array $dependencies
     *
     * @return array
     */
    public function get_dashboard_nav_template_dependency( array $dependencies ): array {
        $dependencies[ $this->vendor_endpoint ] = [
            [
                'slug' => 'seller_badge_list_table',
                'name' => '',
                'args' => [
                    'seller_badge_list_template' => true,
                ],
            ],
        ];

        return $dependencies;
    }

}
