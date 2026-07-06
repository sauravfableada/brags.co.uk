<?php

namespace WeDevs\DokanPro\Modules\VendorSupport;

use WeDevs\Dokan\Traits\ChainableContainer;
use WeDevs\DokanPro\Modules\VendorSupport\APIs\ConversationsController;
use WeDevs\DokanPro\Modules\VendorSupport\APIs\TicketsController;
use WeDevs\DokanPro\Modules\VendorSupport\Emails\VendorSupportEmail;
use WeDevs\DokanPro\Modules\VendorSupport\Emails\VendorSupportReplyEmail;
use WeDevs\DokanPro\Modules\VendorSupport\Controllers\ModelDeletionController;

defined( 'ABSPATH' ) || exit;

/**
 * Class Module.
 * Dokan Pro Vendor Support Module.
 *
 * @since 4.1.2
 *
 * @package WeDevs\DokanPro\Modules\VendorSupport
 */
class Module {
    use ChainableContainer;

    /**
     * Module ID.
     *
     * @since 4.1.2
     */
    protected string $id = 'vendor_support';

    /**
     * Constructor.
     */
    public function __construct() {
        $this->define_constants();
        $this->set_controllers();
        $this->init_hooks();

        // Activation and Deactivation hook
        add_action( 'dokan_activated_module_' . $this->id, [ $this, 'activate' ] );
        add_action( 'dokan_deactivated_module_' . $this->id, [ $this, 'deactivate' ] );
        add_filter( 'woocommerce_email_classes', [ $this, 'register_email_classes' ] );
    }

    /**
     * Define constants for the module.
     *
     * @since 4.1.2
     *
     * @return void
     */
    private function define_constants() {
        define( 'DOKAN_VENDOR_SUPPORT_FILE', __FILE__ );
        define( 'DOKAN_VENDOR_SUPPORT_DIR', dirname( DOKAN_VENDOR_SUPPORT_FILE ) );
        define( 'DOKAN_VENDOR_SUPPORT_INC', DOKAN_VENDOR_SUPPORT_DIR . '/includes/' );
        define( 'DOKAN_VENDOR_SUPPORT_ASSETS', plugins_url( 'assets', DOKAN_VENDOR_SUPPORT_FILE ) );
        define( 'DOKAN_VENDOR_SUPPORT_TEMPLATE_PATH', DOKAN_VENDOR_SUPPORT_DIR . '/templates/' );
    }

    /**
     * Set controllers for the module.
     *
     * @since 4.1.2
     *
     * @return void
     */
    private function set_controllers() {
        $controllers = [
            new ModelDeletionController(),
        ];

        foreach ( $controllers as $controller ) {
            if ( $controller instanceof \WeDevs\Dokan\Contracts\Hookable ) {
                $controller->register_hooks();
            }
        }
    }

    /**
     * Initialize hooks for the module.
     *
     * @since 4.1.2
     *
     * @return void
     */
    public function init_hooks() {
        add_action( 'init', [ $this, 'register_scripts' ] );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_filter( 'dokan_set_template_path', [ $this, 'load_templates' ], 10, 3 );
        add_action( 'dokan_rest_api_class_map', [ $this, 'register_rest_routes' ] );
        add_filter( 'dokan_get_dashboard_nav', [ $this, 'add_vendor_support_page' ], 15 );
    }

    /**
     * Register REST API routes.
     *
     * @since 4.1.2
     *
     * @param array $classes Array of API classes to register.
     *
     * @return array
     */
    public function register_rest_routes( array $classes ): array {
        $classes[ DOKAN_VENDOR_SUPPORT_INC . '/APIs/TicketsController.php' ]       = TicketsController::class;
        $classes[ DOKAN_VENDOR_SUPPORT_INC . '/APIs/ConversationsController.php' ] = ConversationsController::class;

        return $classes;
    }

    /**
     * Register email classes
     */
    public function register_email_classes( $email_classes ) {
        $email_classes['Dokan_Vendor_Support_Ticket'] = new VendorSupportEmail();
        $email_classes['Dokan_Vendor_Support_Reply']  = new VendorSupportReplyEmail();
        return $email_classes;
    }
    /**
     * Add vendor support page in seller dashboard
     *
     * @since 4.1.2
     *
     * @param array $urls
     *
     * @return array $urls
     */
    public function add_vendor_support_page( $urls ) {
        if ( current_user_can( 'dokandar' ) ) {
            $urls['vendor-support'] = [
                'title'         => __( 'Admin Support', 'dokan' ),
                'icon'          => '<i class="fa-solid fa-comment-dots"></i>',
                'url'           => '',
                'pos'           => 182,
                'icon_name'     => 'MessagesSquare',
                'react_route'   => 'vendor-support',
                'permission'    => 'dokan_view_announcement',
            ];
        }

        return $urls;
    }

    /**
     * Load templates for the module.
     *
     * @since 4.1.2
     *
     * @param string $template_path The current template path.
     * @param string $template      The template being loaded.
     * @param array  $args          Additional arguments.
     *
     * @return string The modified template path.
     */
    public function load_templates( string $template_path, string $template, array $args ): string {
        if ( ! empty( $args['is_vendor_support'] ) ) {
            return untrailingslashit( DOKAN_VENDOR_SUPPORT_TEMPLATE_PATH );
        }

        return $template_path;
    }

    /**
     * Activate the module.
     *
     * @since 4.1.2
     */
    public function activate() {
        Installer::install();
        $this->flush_rewrite_rules();
    }

    /**
     * Deactivate the module.
     *
     * @since 4.1.2
     */
    public function deactivate() {
        $this->flush_rewrite_rules();
    }

    /**
     * Flush rewrite rules.
     *
     * @since 4.1.2
     *
     * @return void
     */
    public function flush_rewrite_rules() {
        dokan()->rewrite->register_rule();
        flush_rewrite_rules();
    }

    /**
     * Register scripts
     *
     * @since 4.1.2
     *
     * @return void
     */
    public function register_scripts() {
        $menu = new AdminMenu();
        $menu->register_hooks();

        $script_assets = plugin_dir_path( __FILE__ ) . 'assets/js/vendor-vendor-support.asset.php';

        if ( file_exists( $script_assets ) ) {
            $assets = include $script_assets;

            wp_register_style(
                'dokan-frontend-vendor-support',
                DOKAN_VENDOR_SUPPORT_ASSETS . '/js/vendor-vendor-support.css',
                [ 'wp-components', 'wc-components', 'dokan-react-components' ],
                $assets['version'],
                'all'
            );

            wp_register_script(
                'dokan-frontend-vendor-support',
                DOKAN_VENDOR_SUPPORT_ASSETS . '/js/vendor-vendor-support.js',
                array_merge( $assets['dependencies'], [ 'moment', 'dokan-util-helper', 'dokan-accounting', 'dokan-react-components', 'wc-components' ] ),
                $assets['version'],
                true
            );
        }
    }

    /**
     * Enqueue admin scripts
     *
     * @since 4.1.2
     *
     * @return void
     */
    public function enqueue_scripts() {
        if ( dokan_is_seller_dashboard() ) {
            wp_enqueue_script( 'dokan-frontend-vendor-support' );
            wp_enqueue_style( 'dokan-frontend-vendor-support' );
            wp_set_script_translations( 'dokan-frontend-vendor-support', 'dokan' );
        }
    }
}
