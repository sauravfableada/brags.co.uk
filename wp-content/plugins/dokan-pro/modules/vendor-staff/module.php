<?php

namespace WeDevs\DokanPro\Modules\VendorStaff;

class Module {

    /**
     * Class constructor
     *
     * Sets up all the appropriate hooks and actions
     * within our plugin.
     *
     * @uses is_admin()
     * @uses add_action()
     */
    public function __construct() {
        $this->define_constant();
        $this->includes();
        $this->initiate();

        add_action( 'init', [ $this, 'register_scripts' ] );
        add_action( 'dokan_loaded', [ $this, 'load_emails' ], 20 );
        add_filter( 'dokan_get_dashboard_nav', array( $this, 'add_staffs_page' ), 15 );
        add_filter( 'dokan_query_var_filter', array( $this, 'add_endpoint' ) );
        add_action( 'dokan_load_custom_template', array( $this, 'load_staff_template' ), 16 );
        add_filter( 'dokan_set_template_path', array( $this, 'load_vendor_staff_templates' ), 11, 3 );
        add_action( 'admin_init', array( $this, 'disable_backend_access' ) );
        add_filter( 'show_admin_bar', array( $this, 'disable_admin_bar' ) );
        add_filter( 'woocommerce_email_classes', array( $this, 'load_staff_emails' ), 40 );

        add_action( 'dokan_activated_module_vendor_staff', array( $this, 'activate' ) );
        add_action( 'dokan_deactivated_module_vendor_staff', array( $this, 'deactivate' ) );
        add_filter( 'dokan_email_list', array( $this, 'set_email_template_directory' ) );
        // flush rewrite rules
        add_action( 'woocommerce_flush_rewrite_rules', [ $this, 'flush_rewrite_rules' ] );

        add_filter( 'dokan_rest_api_class_map', [ $this, 'rest_api_class_map' ] );

        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
        add_filter( 'dokan_get_dashboard_nav_template_dependency', [ $this, 'nav_template_dependency' ] );
        // Add custom capabilities to vendor staff
        add_filter( 'dokan_manual_orders_is_enabled', [ $this, 'is_manual_order_enabled' ], 10, 2 );
    }

    /**
     * Load emails
     *
     * @return void
     */
    public function load_emails() {
        add_filter( 'dokan_email_classes', [ $this, 'load_vendor_staff_email_classes' ] );
        add_filter( 'dokan_email_actions', [ $this, 'register_vendor_staff_email_actions' ] );
    }

    /**
     * Load all email class related with Vendor Staff
     *
     * @param $wc_emails
     *
     * @return array
     */
    public function load_vendor_staff_email_classes( $wc_emails ): array {
        include DOKAN_VENDOR_STAFF_INC_DIR . '/emails/class-add-staff-notification-email.php';

        $wc_emails['Dokan_Staff_Add_Notification'] = new \Dokan_Staff_Add_Notification();

        return $wc_emails;
    }

    /**
     * Register all email actions
     *
     * @return array
     */
    public function register_vendor_staff_email_actions( $actions ): array {
        $actions[] = 'dokan_staff_add_notification';

        return $actions;
    }

    /**
     * Define all constant
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function define_constant() {
        define( 'DOKAN_VENDOR_STAFF_DIR', __DIR__ );
        define( 'DOKAN_VENDOR_STAFF_INC_DIR', DOKAN_VENDOR_STAFF_DIR . '/includes' );
        define( 'DOKAN_VENDOR_STAFF_ASSET', plugins_url( 'assets', __FILE__ ) );
    }

    /**
     * Includes all files
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function includes() {
        require_once DOKAN_VENDOR_STAFF_INC_DIR . '/functions.php';
        require_once DOKAN_VENDOR_STAFF_INC_DIR . '/class-staffs.php';
        require_once DOKAN_VENDOR_STAFF_INC_DIR . '/VendorStaffCache.php';
    }

    /**
     * Inistantiate all class
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function initiate() {
        new \Dokan_Staffs();
        new \DokanPro\Modules\VendorStaff\VendorStaffCache();
    }

    /**
     * Register scripts
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function register_scripts() {

        $script_assets = plugin_dir_path( __FILE__ ) . 'assets/js/dokan-vendor-staff.asset.php';

        if ( file_exists( $script_assets ) ) {
            $assets                   = include $script_assets;
            $component_handler        = 'dokan-react-frontend';
            $assets['dependencies'][] = $component_handler;

            wp_register_script(
                'dokan-vendor-staff', DOKAN_VENDOR_STAFF_ASSET . '/js/dokan-vendor-staff.js',
                $assets['dependencies'],
                $assets['version'],
                true
            );
        }
    }


    /**
     * Enqueue admin scripts
     *
     * Allows plugin assets to be loaded.
     *
     * @uses wp_enqueue_script()
     * @uses wp_localize_script()
     * @uses wp_enqueue_style
     */
    public function enqueue_scripts() {
        if ( dokan_is_seller_dashboard() ) {
            wp_enqueue_script( 'dokan-vendor-staff' );
            wp_set_script_translations( 'dokan-vendor-staff', 'dokan' );
        }
    }

    /**
     * Disable backend access of vendor_staff
     *
     * @since 2.7.6
     *
     * @return void
     */
    public function disable_backend_access() {
        if ( is_super_admin() ) {
            return;
        }

        if ( ! current_user_can( 'vendor_staff' ) ) {
            return;
        }

        if ( is_admin() && ! wp_doing_ajax() ) {
            wp_safe_redirect( dokan_get_navigation_url( 'dashboard' ) );
            exit;
        }
    }

    /**
     * Disable admin bar when the user role is vendor_staff
     * @since 2.7.6
     *
     * @return bool
     */
    public function disable_admin_bar( $show_admin_bar ) {
        if ( is_super_admin() ) {
            return $show_admin_bar;
        }

        if ( current_user_can( 'vendor_staff' ) ) {
            return false;
        }

        return $show_admin_bar;
    }

    /**
     * Activate functions
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function activate() {
        global $wp_roles;

        if ( class_exists( 'WP_Roles' ) && ! isset( $wp_roles ) ) {
            $wp_roles = new \WP_Roles(); // phpcs:ignore
        }

        add_role(
            'vendor_staff', __( 'Vendor Staff', 'dokan' ), array(
                'read'     => true,
            )
        );

        $users_query = new \WP_User_Query(
            array(
                'role' => 'vendor_staff',
            )
        );

        $staffs = $users_query->get_results();
        $staff_caps = dokan_get_staff_capabilities();

        if ( count( $staffs ) > 0 ) {
            foreach ( $staffs as $staff ) {
                $staff->add_cap( 'dokandar' );
                $staff->add_cap( 'delete_pages' );
                $staff->add_cap( 'publish_posts' );
                $staff->add_cap( 'edit_posts' );
                $staff->add_cap( 'delete_published_posts' );
                $staff->add_cap( 'edit_published_posts' );
                $staff->add_cap( 'delete_posts' );
                $staff->add_cap( 'manage_categories' );
                $staff->add_cap( 'moderate_comments' );
                $staff->add_cap( 'upload_files' );
                $staff->add_cap( 'edit_shop_orders' );
                $staff->add_cap( 'edit_product' );

                foreach ( $staff_caps as $staff_cap ) {
                    $staff->add_cap( $staff_cap );
                }
            }
        }
        // flush rewrite rules after plugin is activated
        $this->flush_rewrite_rules();
    }

    /**
     * Flush rewrite rules
     *
     * @since 3.3.1
     *
     * @return void
     */
    public function flush_rewrite_rules() {
        add_filter( 'dokan_query_var_filter', array( $this, 'add_endpoint' ) );
        dokan()->rewrite->register_rule();
        flush_rewrite_rules( true );
    }

    /**
     * Deactivate functions
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function deactivate() {
        $users_query = new \WP_User_Query(
            array(
                'role' => 'vendor_staff',
            )
        );

        $staffs = $users_query->get_results();
        $staff_caps = dokan_get_staff_capabilities();

        if ( count( $staffs ) > 0 ) {
            foreach ( $staffs as $staff ) {
                $staff->remove_cap( 'dokandar' );
                $staff->remove_cap( 'delete_pages' );
                $staff->remove_cap( 'publish_posts' );
                $staff->remove_cap( 'edit_posts' );
                $staff->remove_cap( 'delete_published_posts' );
                $staff->remove_cap( 'edit_published_posts' );
                $staff->remove_cap( 'delete_posts' );
                $staff->remove_cap( 'manage_categories' );
                $staff->remove_cap( 'moderate_comments' );
                $staff->remove_cap( 'upload_files' );
                $staff->remove_cap( 'edit_shop_orders' );
                $staff->remove_cap( 'edit_product' );

                foreach ( $staff_caps as $staff_cap ) {
                    $staff->remove_cap( $staff_cap );
                }
            }
        }
    }

    /**
     * Add staffs endpoint to the end of Dashboard
     *
     * @param array $query_var
     */
    public function add_endpoint( $query_var ) {
        $query_var['staffs'] = 'staffs';

        return $query_var;
    }

    /**
    * Get plugin path
    *
    * @since 2.8
    *
    * @return string
    */
    public function plugin_path() {
        return untrailingslashit( plugin_dir_path( __FILE__ ) );
    }

    /**
    * Load Dokan vendor_staff templates
    *
    * @since 2.8
    *
    * @return string
    */
    public function load_vendor_staff_templates( $template_path, $template, $args ) {
        if ( isset( $args['is_vendor_staff'] ) && $args['is_vendor_staff'] ) {
            return $this->plugin_path() . '/templates';
        }

        return $template_path;
    }

    /**
     * Load tools template
     *
     * @since  1.0
     *
     * @param  array $query_vars
     *
     * @return void
     */
    public function load_staff_template( $query_vars ) {
        if ( isset( $query_vars['staffs'] ) ) {
            if ( ! current_user_can( 'dokandar' ) || current_user_can( 'vendor_staff' ) ) {
                dokan_get_template_part(
                    'global/dokan-error', '', array(
                        'deleted' => false,
                        'message' => __( 'You have no permission to view this page', 'dokan' ),
                    )
                );
            } else {
                $get_data = wp_unslash( $_GET ); // phpcs:ignore
                if ( isset( $get_data['view'] ) && $get_data['view'] === 'add_staffs' ) {
                    dokan_get_template_part( 'vendor-staff/add-staffs', '', array( 'is_vendor_staff' => true ) );
                } elseif ( isset( $get_data['view'] ) && $get_data['view'] === 'manage_permissions' ) {
                    dokan_get_template_part( 'vendor-staff/permissions', '', array( 'is_vendor_staff' => true ) );
                } else {
                    dokan_get_template_part( 'vendor-staff/staffs', '', array( 'is_vendor_staff' => true ) );
                }
            }
        }
    }

    /**
     * Add staffs page in seller dashboard
     *
     * @param array $urls
     *
     * @return array $urls
     */
    public function add_staffs_page( $urls ) {
        $menu = [
            'title'       => esc_html__( 'Staff', 'dokan' ),
            'icon'        => '<i class="fas fa-users"></i>',
            'url'         => dokan_get_navigation_url( 'staffs' ),
            'pos'         => 172,
            'icon_name'   => 'Users',
            'react_route' => 'staffs',
        ];

        if ( dokan_is_seller_enabled( get_current_user_id() ) && current_user_can( 'dokandar' ) && ! current_user_can( 'vendor_staff' ) ) {
            $urls['staffs'] = $menu;
        }

        return $urls;
    }

    /**
     * Load staff email class
     *
     * @param  array $emails
     *
     * @return array
     */
    public function load_staff_emails( $emails ) {
        $emails['Dokan_Staff_New_Order']       = include DOKAN_VENDOR_STAFF_INC_DIR . '/class-staff-new-order-email.php';
        $emails['Dokan_Staff_Password_Update'] = include DOKAN_VENDOR_STAFF_INC_DIR . '/class-staff-password-update-email.php';

        return $emails;
    }

    /**
     * Set Proper template directory.
     *
     * @param array $template_array
     *
     * @return array
     */
    public function set_email_template_directory( $template_array ) {
        array_push( $template_array, 'staff-new-order.php' );
        array_push( $template_array, 'staff-password-update.php' );

        return $template_array;
    }

    /**
     * Rest api class map
     *
     * @param array $classes
     *
     * @since 3.9.0
     *
     * @return array
     */
    public function rest_api_class_map( $classes ) {
        $class[ DOKAN_VENDOR_STAFF_INC_DIR . '/REST/VendorStaff.php' ] = '\WeDevs\DokanPro\Modules\VendorStaff\REST\VendorStaff';

        return array_merge( $classes, $class );
    }

    /**
     * Add template dependency
     *
     * @param $dependencies
     *
     * @return array
     */
    public function nav_template_dependency( $dependencies ): array {
        $dependencies['staffs'] = [
            [
                'slug' => 'vendor-staff/add-staffs',
                'name' => '',
                'args' => [
                    'is_vendor_staff' => true,
                ],
            ],
            [
                'slug' => 'vendor-staff/permissions',
                'name' => '',
                'args' => [
                    'is_vendor_staff' => true,
                ],
            ],
            [
                'slug' => 'vendor-staff/staffs',
                'name' => '',
                'args' => [
                    'is_vendor_staff' => true,
                ],
            ],
        ];

        return $dependencies;
    }

    /**
     * Checks if manual order creation is enabled for vendor staff.
     *
     * This method verifies if a vendor staff can create manual orders based on:
     * 1. Staff permissions to create manual orders
     * 2. Current user being a vendor staff
     *
     * @since 4.0.0
     *
     * @param bool $is_enabled  Default status of whether manual ordering is enabled
     * @param int  $vendor_id   The ID of the vendor to check permissions for
     *
     * @return bool True if manual orders are enabled for the vendor staff, false otherwise
     */
    public function is_manual_order_enabled( bool $is_enabled, int $vendor_id ): bool {
        $current_user_id     = get_current_user_id();
        $vendor_id_for_staff = dokan_get_current_user_id();
        $staff_user          = get_userdata( $current_user_id );

        return $is_enabled && $vendor_id_for_staff === $vendor_id && $staff_user->has_cap( 'dokan_manage_manual_order' );
    }
}
