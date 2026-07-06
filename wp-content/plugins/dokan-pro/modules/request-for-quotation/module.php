<?php
namespace WeDevs\DokanPro\Modules\RequestForQuotation;

use WeDevs\Dokan\Traits\ChainableContainer;
use WeDevs\DokanPro\Modules\RequestForQuotation\Admin\Hooks;
use WeDevs\DokanPro\Modules\RequestForQuotation\Admin\Settings;
use WeDevs\DokanPro\Modules\RequestForQuotation\Emails\Manager;
use WeDevs\DokanPro\Modules\RequestForQuotation\Helper as QuoteHelper;
use WeDevs\DokanPro\Modules\RequestForQuotation\Frontend\CustomerDashboard;
use WeDevs\DokanPro\Modules\RequestForQuotation\Frontend\Hooks as FrontendHooks;
use WeDevs\DokanPro\Modules\RequestForQuotation\Frontend\VendorDashboard;

defined( 'ABSPATH' ) || exit;

/**
 * Class for Request A Quote module integration.
 *
 * @since 3.6.0
 */
final class Module {

    use ChainableContainer;

    /**
     * Class constructor.
     *
     * @since 3.6.0
     */
    public function __construct() {
        $this->define_constants();
        $this->initiate();
        add_filter( 'dokan_rest_api_class_map', [ $this, 'add_rest_controller' ] );
        add_filter( 'dokan_set_template_path', [ $this, 'load_templates' ], 10, 3 );
        add_action( 'dokan_activated_module_request_for_quotation', [ $this, 'activate' ] );
        add_action( 'dokan_deactivated_module_request_for_quotation', [ $this, 'deactivate' ] );

        // add rewrite rules for vendor dashboard Request for Quotation menu
        add_filter( 'dokan_query_var_filter', [ $this, 'vendor_dashboard_endpoint' ] );

        // Session must be instantiated on template redirect hook. It's safe.
        add_action( 'template_redirect', [ Session::class, 'init' ] );
        add_filter( 'dokan_button_shortcodes', array( $this, 'add_to_dokan_shortcode_menu' ) );

        // Load the pending quotes in admin dashboard to-do data
        add_filter( 'dokan_rest_admin_dashboard_todo_data', [ $this, 'load_pending_quotes' ] );
    }

    /**
     * Add rfq shortocde to Dokan shortcode menu
     *
     * @since 3.9.0
     *
     * @param array $shortcodes
     *
     * @return array
     */
    public function add_to_dokan_shortcode_menu( $shortcodes ) {
        $shortcodes['dokan-request-quote'] = array(
            'title'   => __( 'Request for quotation', 'dokan' ),
            'content' => '[dokan-request-quote]',
        );

        return $shortcodes;
    }

    /**
     * Activates the module
     *
     * @since 3.6.0
     *
     * @return void
     */
    public function activate() {
        new Installer();

        // flush rewrite rules
        $this->flush_rewrite_rules();
    }

    /**
     * Deactivates the module
     *
     * @since 3.6.0
     *
     * @return void
     */
    public function deactivate() {
        //will add required later
    }

    /**
     * Module constants
     *
     * @since 3.6.0
     *
     * @return void
     */
    private function define_constants() {
        define( 'DOKAN_RAQ_ASSETS_DIR', plugins_url( 'assets', __FILE__ ) );
        define( 'DOKAN_RAQ_FILE', __FILE__ );
        define( 'DOKAN_RAQ_PATH', dirname( DOKAN_RAQ_FILE ) );
        define( 'DOKAN_RAQ_INCLUDES', DOKAN_RAQ_PATH . '/includes' );
        define( 'DOKAN_RAQ_URL', plugins_url( '', DOKAN_RAQ_FILE ) );
        define( 'DOKAN_RAQ_ASSETS', DOKAN_RAQ_URL . '/assets' );
        define( 'DOKAN_RAQ_VIEWS', DOKAN_RAQ_PATH . '/views' );
        define( 'DOKAN_RAQ_TEMPLATE_PATH', dirname( DOKAN_RAQ_FILE ) . '/templates/' );
        define( 'DOKAN_SESSION_QUOTE_KEY', 'dokan_quote' );
        define( 'DOKAN_ACCOUNT_ENDPOINT', 'request-a-quote' );
        define( 'DOKAN_VENDOR_ENDPOINT', 'requested-quotes' );
        define( 'DOKAN_MY_ACCOUNT_ENDPOINT', DOKAN_ACCOUNT_ENDPOINT );
    }

    /**
     * Initiate all classes
     *
     * @since 3.6.0
     *
     * @return void
     */
    public function initiate() {
        $this->container['quote_assets'] = new Assets();
        $this->container['quote_email']  = new Manager();
        if ( wp_doing_ajax() ) {
            $this->container['quote_ajax'] = new Ajax();
        }
        if ( is_admin() ) {
            $this->container['quote_admin_hooks']    = new Hooks();
            $this->container['quote_admin_settings'] = new Settings();
        } else {
            $this->container['quote_frontend_shortcode'] = new Shortcode();
        }
        $this->container['quote_frontend_hooks'] = new FrontendHooks();
        $this->container['quote_vendor_hooks']   = new VendorDashboard();
        $this->container['quote_customer_hooks'] = new CustomerDashboard();
        $this->container['catalog_mode']         = new CatalogMode();
    }

    /**
     * Add module REST Controller
     *
     * @since 3.6.0
     *
     * @param array $class_map
     */
    public function add_rest_controller( $class_map ) {
        $class_map[ DOKAN_RAQ_INCLUDES . '/Api/RequestForQuotationController.php' ]       = 'WeDevs\\DokanPro\\Modules\\RequestForQuotation\\Api\\RequestForQuotationController';
        $class_map[ DOKAN_RAQ_INCLUDES . '/Api/VendorRequestForQuotationController.php' ] = 'WeDevs\\DokanPro\\Modules\\RequestForQuotation\\Api\\VendorRequestForQuotationController';
        $class_map[ DOKAN_RAQ_INCLUDES . '/Api/QuoteRuleController.php' ]                 = 'WeDevs\\DokanPro\\Modules\\RequestForQuotation\\Api\\QuoteRuleController';
        $class_map[ DOKAN_RAQ_INCLUDES . '/Api/CustomerController.php' ]                  = 'WeDevs\\DokanPro\\Modules\\RequestForQuotation\\Api\\CustomerController';
        $class_map[ DOKAN_RAQ_INCLUDES . '/Api/RolesController.php' ]                     = 'WeDevs\\DokanPro\\Modules\\RequestForQuotation\\Api\\RolesController';

        return $class_map;
    }

	/**
	 * Load pending quotes count in admin dashboard to-do data.
	 *
	 * @since 4.1.0
	 *
	 * @param array $data The existing to-do data.
	 *
	 * @return array The modified to-do data with pending quotes count.
	 */
	public function load_pending_quotes( array $data ): array {
		$count = QuoteHelper::get_request_quote_count();
		$data['pending_quotes'] = [
			'icon'         => 'FileText',
			'count'        => (int) ( $count->pending ?? 0 ),
			'title'        => esc_html__( 'Pending Quotes', 'dokan' ),
            'redirect_url' => admin_url( 'admin.php?page=dokan#/request-for-quote?status=pending' ),
            'position'     => 80,
		];

		return $data;
	}

    /**
     * Set template path for Request Quote
     *
     * @since 3.6.0
     *
     * @return string
     */
    public function load_templates( $template_path, $template, $args ) {
        if ( ( isset( $args['request_quote_shortcode'] ) && $args['request_quote_shortcode'] ) || ( isset( $args['request_quote_vendor'] ) && $args['request_quote_vendor'] ) || ( isset( $args['request_quote_table'] ) && $args['request_quote_table'] ) ) {
            return DOKAN_RAQ_TEMPLATE_PATH;
        }

        return $template_path;
    }

    /**
     * Add rewrite rules for vendor dashboard and my account Request for Quotation menu
     *
     * @since 3.6.0
     *
     * @return void
     */
    public function vendor_dashboard_endpoint( $query_var ) {
        // add rewrite rules for vendor dashboard Request for Quotation menu
        $query_var[] = DOKAN_VENDOR_ENDPOINT;
        // add rewrite rules for woocommerce my account page
        $query_var[] = DOKAN_MY_ACCOUNT_ENDPOINT;

        return $query_var;
    }

    /**
     * Flush rewrite rules
     *
     * @since 3.6.0
     *
     * @return void
     */
    public function flush_rewrite_rules() {
        dokan()->rewrite->register_rule();
        flush_rewrite_rules();
    }
}
