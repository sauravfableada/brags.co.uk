<?php

namespace WeDevs\DokanPro\Modules\VendorVerification;

use WeDevs\Dokan\Traits\ChainableContainer;
use WeDevs\DokanPro\Modules\VendorVerification\Admin\Hooks as AdminHooks;
use WeDevs\DokanPro\Modules\VendorVerification\Admin\Settings as AdminSettings;
use WeDevs\DokanPro\Modules\VendorVerification\Frontend\Dashboard;
use WeDevs\DokanPro\Modules\VendorVerification\Frontend\Hooks as FrontendHooks;
use WeDevs\DokanPro\Modules\VendorVerification\Frontend\HybridauthHooks;
use WeDevs\DokanPro\Modules\VendorVerification\Frontend\SetupWizard;
use WeDevs\DokanPro\Modules\VendorVerification\Models\VerificationRequest;
use WeDevs\DokanPro\Modules\VendorVerification\REST\VendorVerification;
use WeDevs\DokanPro\Modules\VendorVerification\REST\VerificationMethodsApi;
use WeDevs\DokanPro\Modules\VendorVerification\REST\VerificationRequestsApi;
use WeDevs\DokanPro\Modules\VendorVerification\Widgets\VerifiedMethodsList;

/**
 * Vendor Verification Module.
 *
 * @since 3.11.1
 *
 * @property Ajax                $ajax                    Ajax Class.
 * @property Assets              $assets                    Ajax Class.
 * @property AdminSettings       $admin_settings          Admin Settings Class.
 * @property AdminHooks          $admin_hooks             Admin Hooks Class.
 * @property Dashboard           $dashboard               Vendor Dashboard Class.
 * @property FrontendHooks       $frontend_hooks          Frontend Hook Class.
 * @property HybridauthHooks     $hybridauth_hooks        Frontend Hook Class.
 * @property Emails              $emails                  Emails Class.
 * @property SetupWizard         $setup_wizard            SetupWizard Class.
 * @property VerifiedMethodsList $list_widget             List Widget Class.
 * @property Installer           $installer               Installer Class.
 */
class Module {
    use ChainableContainer;

    /**
     * Constructor for the Dokan_Seller_Verification class
     *
     * Sets up all the appropriate hooks and actions
     * within our plugin.
     */
    public function __construct() {
        $this->define_constants();
        // plugin activation hook
        add_action( 'dokan_activated_module_vendor_verification', [ $this, 'activate' ] );

        // flush rewrite rules
        add_action( 'woocommerce_flush_rewrite_rules', [ $this, 'flush_rewrite_rules' ] );

        // includes required files
        $this->includes_file();

        // init rest api
        add_filter( 'dokan_rest_api_class_map', [ $this, 'register_class_map' ] );
        add_filter( 'dokan_rest_admin_dashboard_todo_data', [ $this, 'load_pending_verifications_count' ] );
        add_filter( 'dokan_rest_admin_dashboard_vendor_metrics_data', [ $this, 'load_verified_vendors_count' ] );
    }

    /**
     * Placeholder for activation function
     *
     * Nothing being called here yet.
     */
    public function activate() {
        $this->installer->run();

        // flash rewrite rules
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
        dokan()->rewrite->register_rule();
        flush_rewrite_rules();
    }

    /**
     * Rest api class map
     *
     * @since 3.11.1
     *
     * @param array $classes An array of classes.
     *
     * @return array
     */
    public function register_class_map( array $classes ): array {
        $classes[ DOKAN_VERFICATION_INC_DIR . '/REST/VerificationMethodsApi.php' ]      = VerificationMethodsApi::class;
        $classes[ DOKAN_VERFICATION_INC_DIR . '/REST/VerificationRequestsApi.php' ]     = VerificationRequestsApi::class;
        $classes[ DOKAN_VERFICATION_INC_DIR . '/REST/VendorVerification.php' ]          = VendorVerification::class;

        return $classes;
    }

	/**
	 * Load pending verifications count in the admin dashboard to-do data.
	 *
	 * @since 4.1.0
	 *
	 * @param array $data The existing to-do data.
	 *
	 * @return array The modified to-do data with pending verifications count.
	 */
	public function load_pending_verifications_count( array $data ): array {
		$verification_request = new VerificationRequest();
		$count                = $verification_request->count( [ 'status' => VerificationRequest::STATUS_PENDING ] );

		// If the count is not an object, we assume it to be zero.
		$data['pending_verifications'] = [
			'icon'         => 'BadgeCheck',
			'count'        => (int) ( $count[ VerificationRequest::STATUS_PENDING ] ?? 0 ),
			'title'        => esc_html__( 'Pending Verifications', 'dokan' ),
            'redirect_url' => admin_url( 'admin.php?page=dokan#/verifications?status=pending' ),
            'position'     => 40,
		];

		return $data;
	}

	/**
	 * Load verified vendors count in the admin dashboard vendor metrics data.
	 *
	 * @since 4.1.0
	 *
	 * @param array $data The existing vendor metrics data.
	 *
	 * @return array The modified vendor metrics data with verified vendors count.
	 */
	public function load_verified_vendors_count( array $data ): array {
		$data['verified_vendors'] = [
			'icon'     => 'UserRoundCheck',
			'count'    => Helper::get_verified_vendor_count(),
			'title'    => esc_html__( 'Verified Vendors', 'dokan' ),
			'tooltip'  => esc_html__( 'Total vendors who got verified', 'dokan' ),
			'position' => 2,
		];

		return $data;
	}

    /**
     * Define module constants
     *
     * @since 3.0.0
     *
     * @return void
     */
    public function define_constants() {
        define( 'DOKAN_VERFICATION_DIR', __DIR__ );
        define( 'DOKAN_VERFICATION_INC_DIR', __DIR__ . '/includes/' );
        define( 'DOKAN_VERFICATION_TEMPLATE_DIR', __DIR__ . '/templates/' );
        define( 'DOKAN_VERFICATION_LIB_DIR', __DIR__ . '/lib/' );
        define( 'DOKAN_VERFICATION_PLUGIN_ASSEST', plugins_url( 'assets', __FILE__ ) );
    }

    /**
     * Include all the required files
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function includes_file() {
        $this->container['installer']        = new Installer();
        $this->container['assets']           = new Assets();
        $this->container['dashboard ']       = new Dashboard();
        $this->container['frontend_hooks']   = new FrontendHooks();
        $this->container['hybridauth_hooks'] = new HybridauthHooks();
        $this->container['ajax']             = new Ajax();
        $this->container['emails']           = new Emails();
        $this->container['cache']            = new Cache();
        $this->container['widgets']          = new Widget();

        if ( is_admin() ) {
            $this->container['admin_settings'] = new AdminSettings();
            $this->container['admin_hooks']    = new AdminHooks();
        } else {
            $this->container['setup_wizard'] = new SetupWizard();
        }
    }
}
