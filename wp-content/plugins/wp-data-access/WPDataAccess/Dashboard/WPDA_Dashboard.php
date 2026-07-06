<?php

// phpcs:ignore Standard.Category.SniffName.ErrorCode
namespace WPDataAccess\Dashboard;

use WP_Data_Access_Admin;
use WPDataAccess\Data_Dictionary\WPDA_Dictionary_Lists;
use WPDataAccess\Plugin_Table_Models\WPDA_App_Model;
use WPDataAccess\Plugin_Table_Models\WPDA_Design_Table_Model;
use WPDataAccess\Plugin_Table_Models\WPDA_Publisher_Model;
use WPDataAccess\Plugin_Table_Models\WPDP_Project_Model;
use WPDataAccess\Premium\WPDAPRO_Dashboard\WPDAPRO_Dashboard;
use WPDataAccess\Premium\WPDAPRO_Dashboard\WPDAPRO_Widget_Project;
use WPDataAccess\Premium\WPDAPRO_Data_Forms\WPDAPRO_Data_Forms_Init;
use WPDataAccess\Utilities\WPDA_Message_Box;
use WPDataAccess\WPDA;
use WPDataAccess\Connection\WPDADB;
use WPDataProjects\WPDP;
use WPDataAccess\Settings\WPDA_Settings_Legacy_Dashboard;
/**
 * Dashboard class
 */
class WPDA_Dashboard {
    /**
     * Nonce seed
     */
    const DASHBOARD_SAVE = 'WPDA_DASHBOARD_SAVE';

    /**
     * Nonce seed
     */
    const USER_DASHBOARD = 'wpda-user-dashboard-widgets';

    /**
     * Meta key
     */
    const USER_NEW_MESSAGE = 'wpda_new_dashboard_message';

    /**
     * Available dashboards
     *
     * @var array|null
     */
    protected $dashboards = null;

    /**
     * Number of dashboard columns
     *
     * @var int
     */
    protected $number_of_columns = 2;

    /**
     * Nonce for adding objects
     *
     * @var null
     */
    protected $wp_nonce_add = null;

    /**
     * Nonce for save actions
     *
     * @var null
     */
    protected $wp_nonce_save = null;

    /**
     * Current dashboard
     *
     * @var WPDAPRO_Dashboard|null
     */
    protected $dashboard = null;

    /**
     * Widgets on current dashboard
     *
     * @var array|null
     */
    protected $dashboard_widgets = null;

    /**
     * Dashboard positions of all visible objects
     *
     * @var array|null
     */
    protected $dashboard_positions = null;

    /**
     * Shared dashboards
     *
     * @var array
     */
    protected $shared_dashboards = array();

    /**
     * Dashboard access
     *
     * @var array
     */
    protected $shared_access = array();

    /**
     * Locked dashboards
     *
     * @var array
     */
    protected $shared_locked = array();

    /**
     * Locked dashboards
     *
     * @var array
     */
    protected $locked_dashboards = array();

    /**
     * Indicates if current user can create dashboards
     *
     * @var bool
     */
    protected $cannot_create_dashboard = false;

    /**
     * Available tabs on current dashboard
     *
     * @var string[]
     */
    protected $tabs = array('Default');

    /**
     * Current tabs
     *
     * @var int|mixed|string|null
     */
    protected $tab = 'Default';

    /**
     * Current tab label
     *
     * @var string
     */
    protected $tab_name = '';

    /**
     * Current tab index
     *
     * @var false|int|string
     */
    protected $tab_index = 0;

    /**
     * Indicates if default tab show be hidden
     *
     * @var bool
     */
    protected $hide_default_tab = false;

    /**
     * Indicates if dashboard is locked
     *
     * @var bool
     */
    protected $is_locked = false;

    /**
     * Message
     *
     * @var null
     */
    protected $message = null;

    /**
     * Message type
     *
     * @var string|null
     */
    protected $message_type = null;

    private $current_version;

    private $option_legacy_tools;

    /**
     * Constructor
     *
     * @param boolean $widget_mode True = allowed to manage dashboards and widgets.
     */
    public function __construct( $widget_mode = false ) {
        if ( $widget_mode ) {
            // Prepare nonces.
            $this->wp_nonce_add = wp_create_nonce( WPDA_Widget::WIDGET_ADD . WPDA::get_current_user_login() );
            $this->wp_nonce_save = wp_create_nonce( static::DASHBOARD_SAVE . WPDA::get_current_user_login() );
            // Load Data Tables resources.
            \WPDataAccess\Data_Tables\WPDA_Data_Tables::enqueue_styles_and_script();
        }
        $this->current_version = get_user_meta( WPDA::get_current_user_id(), 'wpda_data_explorer', true );
        // Start with empty array.
        if ( $widget_mode ) {
            update_user_meta( WPDA::get_current_user_id(), self::USER_DASHBOARD, array() );
        }
        $this->option_legacy_tools = WPDA::get_option( WPDA::OPTION_PLUGIN_LEGACY_TOOLS );
    }

    /**
     * Add dashboard
     *
     * @param boolean $widget_mode True = user is allowed to manage dashboards.
     *
     * @return void
     */
    public static function add_dashboard( $widget_mode = false ) {
        $dashboard = new WPDA_Dashboard($widget_mode);
        $dashboard->dashboard();
    }

    /**
     * Construct plugin dashboard
     *
     * @return void
     */
    public function dashboard() {
        wp_enqueue_style( 'wpdataaccess_dashboard' );
        wp_enqueue_script( 'wpdataaccess_dashboard' );
        if ( WPDA::current_user_is_admin() ) {
            $this->dashboard_default();
            $this->dashboard_mobile();
        }
        if ( isset( $_REQUEST['page'] ) ) {
            // phpcs:ignore WordPress.Security.NonceVerification
            switch ( $_REQUEST['page'] ) {
                // phpcs:ignore WordPress.Security.NonceVerification
                case 'wpda_dashboard':
                    $this->toolbar();
                    $this->add_forms();
                    $this->tabs();
                    $this->columns();
                    $this->add_panels();
                    $this->dashboard_js();
                    break;
                case WP_Data_Access_Admin::PAGE_MAIN:
                    if ( !isset( $_REQUEST['page_action'] ) ) {
                        // phpcs:ignore WordPress.Security.NonceVerification
                        if ( !isset( $_REQUEST['table_name'] ) ) {
                            // phpcs:ignore WordPress.Security.NonceVerification
                            $this->toolbar_wpda();
                        } else {
                            if ( !isset( $_REQUEST['action'] ) || 'new' !== $_REQUEST['action'] && 'edit' !== $_REQUEST['action'] ) {
                                $this->toolbar_wpda_table();
                            } else {
                                $this->toolbar_wpda_row();
                            }
                        }
                    } elseif ( 'wpda_backup' === $_REQUEST['page_action'] && (!isset( $_REQUEST['action'] ) || 'remove' === $_REQUEST['action'] || 'update' === $_REQUEST['action'] || 'add' === $_REQUEST['action']) ) {
                        // phpcs:ignore WordPress.Security.NonceVerification
                        $this->toolbar_backup();
                    } elseif ( 'wpda_import_csv' === $_REQUEST['page_action'] ) {
                        // phpcs:ignore WordPress.Security.NonceVerification
                        $this->toolbar_import_csv();
                    }
                    break;
                case WP_Data_Access_Admin::PAGE_APPS:
                    $this->toolbar_apps();
                    break;
                case WP_Data_Access_Admin::PAGE_QUERY_BUILDER:
                    $this->toolbar_sql();
                    break;
                case WP_Data_Access_Admin::PAGE_DESIGNER:
                    if ( !isset( $_REQUEST['action'] ) || 'new' !== $_REQUEST['action'] && 'edit' !== $_REQUEST['action'] ) {
                        $this->toolbar_designer();
                    }
                    break;
                case WP_Data_Access_Admin::PAGE_PUBLISHER:
                    if ( (!isset( $_REQUEST['action'] ) || 'new' !== $_REQUEST['action'] && 'edit' !== $_REQUEST['action'] || isset( $_REQUEST['postaction'] ) && 'list' === $_REQUEST['postaction']) && !(isset( $_REQUEST['page_action'] ) && 'wpda_mngstl' === $_REQUEST['page_action']) ) {
                        $this->toolbar_publisher();
                    }
                    break;
                case WP_Data_Access_Admin::PAGE_CHARTS:
                    $this->toolbar_charts();
                    break;
                case WPDP::PAGE_MAIN:
                    if ( !isset( $_REQUEST['action'] ) || 'new' !== $_REQUEST['action'] && 'edit' !== $_REQUEST['action'] ) {
                        $this->toolbar_projects();
                    }
                    break;
                case WPDP::PAGE_TEMPLATES:
                    if ( !isset( $_REQUEST['action'] ) || 'new' !== $_REQUEST['action'] && 'edit' !== $_REQUEST['action'] ) {
                        $this->toolbar_templates();
                    }
                    break;
            }
        }
        if ( isset( $_GET['page_iaction'] ) ) {
            // Perform interactive action to auto start user selected feature.
            ?>
				<script>
					jQuery(function() {
						const iaction = "<?php 
            echo esc_attr( sanitize_text_field( $_GET['page_iaction'] ) );
            ?>";
						switch (iaction) {
							case "create_new_app":
								var interval = setInterval(
									function() {
										if (window.ppActionCreateApp !== undefined) {
											window.ppActionCreateApp();
											clearInterval(interval);
										}
									}, 100);
								break;
							case "wpda_import_sql":
								var interval = setInterval(
									function() {
										if (window.ppActionEnableImport !== undefined) {
											window.ppActionEnableImport();
											clearInterval(interval);
										}
									}, 100);
								break;
							case "manage_databases":
								jQuery('#wpda_manage_databases').show();
								jQuery('#local_database').focus();
								break;
						}
					});
				</script>
				<?php 
        }
    }

    /**
     * Get correct help url for current page
     *
     * @return string
     */
    protected function get_help_url() {
        $help_url = 'https://docs.wpdataaccess.com/';
        if ( isset( $_REQUEST['page'] ) ) {
            // phpcs:ignore WordPress.Security.NonceVerification
            switch ( $_REQUEST['page'] ) {
                // phpcs:ignore WordPress.Security.NonceVerification
                case \WP_Data_Access_Admin::PAGE_MAIN:
                    $help_url = 'https://docs.legacy.wpdataaccess.com/docs/data-explorer-getting-started/';
                    break;
                case \WP_Data_Access_Admin::PAGE_APPS:
                    $help_url = 'https://docs.rad.wpdataaccess.com/';
                    break;
                case \WP_Data_Access_Admin::PAGE_QUERY_BUILDER:
                    $help_url = 'https://docs.sql.wpdataaccess.com/';
                    break;
                case \WP_Data_Access_Admin::PAGE_DESIGNER:
                    $help_url = 'https://docs.legacy.wpdataaccess.com/docs/data-designer-getting-started/';
                    break;
                case \WP_Data_Access_Admin::PAGE_PUBLISHER:
                    $help_url = 'https://docs.legacy.wpdataaccess.com/docs/data-tables-getting-started/';
                    break;
                case \WP_Data_Access_Admin::PAGE_DASHBOARD:
                    $help_url = 'https://docs.legacy.wpdataaccess.com/docs/bi-getting-started/';
                    break;
                case \WP_Data_Access_Admin::PAGE_CHARTS:
                    $help_url = 'https://docs.legacy.wpdataaccess.com/docs/chart-widgets/';
                    break;
                case WPDP::PAGE_MAIN:
                    $help_url = 'https://docs.legacy.wpdataaccess.com/docs/data-projects/';
                    break;
                case WPDP::PAGE_TEMPLATES:
                    $help_url = 'https://docs.legacy.wpdataaccess.com/docs/project-templates/';
                    break;
                case 'wpdataaccess':
                    $current_tab = ( isset( $_REQUEST['tab'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['tab'] ) ) : 'plugin' );
                    // phpcs:ignore WordPress.Security.NonceVerification
                    switch ( $current_tab ) {
                        case 'plugin':
                            $help_url = 'https://docs.settings.wpdataaccess.com/plugin.html';
                            break;
                        case 'pds':
                            $help_url = 'https://docs.settings.wpdataaccess.com/pds.html';
                            break;
                        case 'backend':
                            $help_url = 'https://docs.settings.wpdataaccess.com/back-end.html';
                            break;
                        case 'frontend':
                            $help_url = 'https://docs.settings.wpdataaccess.com/frond-end.html';
                            break;
                        case 'uninstall':
                            $help_url = 'https://docs.settings.wpdataaccess.com/uninstall.html';
                            break;
                        case 'repository':
                            $help_url = 'https://docs.settings.wpdataaccess.com/repository.html';
                            break;
                        case 'mail':
                            $help_url = 'https://docs.settings.wpdataaccess.com/mail.html';
                            break;
                        case 'drives':
                            $help_url = 'https://docs.settings.wpdataaccess.com/drives.html';
                            break;
                        case 'system':
                            $help_url = 'https://docs.settings.wpdataaccess.com/system-info.html';
                            break;
                        case 'legacy':
                            $vtab = ( isset( $_REQUEST['vtab'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['vtab'] ) ) : 'plugin' );
                            // phpcs:ignore WordPress.Security.NonceVerification
                            switch ( $vtab ) {
                                case 'backend':
                                    $help_url = 'https://docs.settings.wpdataaccess.com/legacy-tools-back-end.html';
                                    break;
                                case 'frontend':
                                    $help_url = 'https://docs.settings.wpdataaccess.com/legacy-tools-front-end.html';
                                    break;
                                case 'datatables':
                                    $help_url = 'https://docs.settings.wpdataaccess.com/legacy-tools-data-tables.html';
                                    break;
                                case 'dataforms':
                                    $help_url = 'https://docs.settings.wpdataaccess.com/legacy-tools-data-forms.html';
                                    break;
                                case 'dashboard':
                                    $help_url = 'https://docs.settings.wpdataaccess.com/legacy-tools-dashboard.html';
                                    break;
                                case 'databackup':
                                    $help_url = 'https://docs.settings.wpdataaccess.com/legacy-tools-data-backup.html';
                                    break;
                                case 'roles':
                                    $help_url = 'https://docs.settings.wpdataaccess.com/legacy-tools-roles.html';
                                    break;
                                default:
                                    $help_url = 'https://docs.settings.wpdataaccess.com/legacy-tools-plugin.html';
                            }
                            break;
                    }
                    break;
            }
        }
        return $help_url;
    }

    /**
     * Construct default dashboard
     *
     * @return void
     */
    protected function dashboard_default() {
        ?>
			<div id="wpda-dashboard" style="display:none">
				<div class="wpda-dashboard">
					<div class="wpda-dashboard-group wpda-dashboard-group-administration">
						<div class="icons">
							<a class="wpda-dashboard-item wpda_tooltip_icons" href="<?php 
        echo admin_url( 'admin.php' );
        // phpcs:ignore WordPress.Security.EscapeOutput
        ?>?page=wpda_navi" title="Quick links and frequently asked questions">
								<div class="fa-solid fa-house"></div>
								<div class="label">Tool Guide</div>
							</a>
							<a class="wpda-dashboard-item wpda_tooltip_icons" href="<?php 
        echo admin_url( 'admin.php' );
        // phpcs:ignore WordPress.Security.EscapeOutput
        ?>?page=wpda_apps" title="Data-driven Rapid Application Development">
								<div class="fa-solid">
									<svg xmlns="http://www.w3.org/2000/svg" height="26px" width="26px" viewBox="4 4 16 16" fill="inherit">
										<path d="M4 8h4V4H4v4zm6 12h4v-4h-4v4zm-6 0h4v-4H4v4zm0-6h4v-4H4v4zm6 0h4v-4h-4v4zm6-10v4h4V4h-4zm-6 4h4V4h-4v4zm6 6h4v-4h-4v4zm0 6h4v-4h-4v4z"/>
									</svg>
								</div>
								<div class="label">App Builder</div>
							</a>
							<a class="wpda-dashboard-item wpda_tooltip_icons" href="<?php 
        echo admin_url( 'admin.php' );
        // phpcs:ignore WordPress.Security.EscapeOutput
        ?>?page=wpda" title="Manage local and remote data and databases">
								<div class="fa-solid fa-database"></div>
								<div class="label">Explorer</div>
							</a>
							<a class="wpda-dashboard-item wpda_tooltip_icons" href="<?php 
        echo admin_url( 'admin.php' );
        // phpcs:ignore WordPress.Security.EscapeOutput
        ?>?page=wpda_query_builder" title="SQL Query Builder">
								<div class="fa-solid fa-code"></div>
								<div class="label">SQL</div>
							</a>
						</div>
						<div class="subject">Data(base) Administration & App Development</div>
					</div>
					<div class="wpda-dashboard-group wpda-dashboard-group-projects"
                        <?php 
        if ( !$this->option_legacy_tools['tables'][0] && !$this->option_legacy_tools['forms'][0] && !$this->option_legacy_tools['templates'][0] && !$this->option_legacy_tools['designer'][0] && !$this->option_legacy_tools['dashboards'][0] && !$this->option_legacy_tools['charts'][0] ) {
            echo 'style="display: none"';
        }
        ?>
                    >
						<div class="icons">
							<a class="wpda-dashboard-item wpda_tooltip_icons"
                               href="<?php 
        echo admin_url( 'admin.php' );
        // phpcs:ignore WordPress.Security.EscapeOutput
        ?>?page=wpda_publisher"
                               title="OLD Table Builder

Use App Builder to access the NEW Table Builder
"
                                <?php 
        if ( !$this->option_legacy_tools['tables'][0] ) {
            echo 'style="display: none"';
        }
        ?>
                            >
								<div class="fa-solid fa-table"></div>
								<div class="label">Tables</div>
							</a>
							<a class="wpda-dashboard-item wpda_tooltip_icons"
                               href="<?php 
        echo admin_url( 'admin.php' );
        // phpcs:ignore WordPress.Security.EscapeOutput
        ?>?page=wpda_wpdp"
                               title="OLD Form Builder

Use App Builder to access the NEW Form Builder
"
                                <?php 
        if ( !$this->option_legacy_tools['forms'][0] ) {
            echo 'style="display: none"';
        }
        ?>
                            >
								<div class="fa-solid fa-wand-magic-sparkles"></div>
								<div class="label">Forms</div>
							</a>
							<a class="wpda-dashboard-item wpda_tooltip_icons"
                               href="<?php 
        echo admin_url( 'admin.php' );
        // phpcs:ignore WordPress.Security.EscapeOutput
        ?>?page=wpda_templates"
                               title="OLD Form Templates

Customize forms using templates"
                                <?php 
        if ( !$this->option_legacy_tools['templates'][0] ) {
            echo 'style="display: none"';
        }
        ?>
                            >
								<div class="fa-solid fa-desktop"></div>
								<div class="label">Templates</div>
							</a>
							<a class="wpda-dashboard-item wpda_tooltip_icons"
                               href="<?php 
        echo admin_url( 'admin.php' );
        // phpcs:ignore WordPress.Security.EscapeOutput
        ?>?page=wpda_designer"
                               title="Create database tables and indexes"
                                <?php 
        if ( !$this->option_legacy_tools['designer'][0] ) {
            echo 'style="display: none"';
        }
        ?>
                            >
								<div class="fa-solid fa-drafting-compass"></div>
								<div class="label">Designer</div>
							</a>
                            <?php 
        ?>
						</div>
						<div class="subject">Legacy Tools</div>
					</div>
					<div class="wpda-dashboard-group wpda-dashboard-group-settings">
						<div class="icons">
							<a class="wpda-dashboard-item wpda_tooltip_icons" href="<?php 
        echo admin_url( 'options-general.php' );
        // phpcs:ignore WordPress.Security.EscapeOutput
        ?>?page=wpdataaccess" title="Manage plugin user interface and behavior">
								<div class="fa-solid fa-cog"></div>
								<div class="label">Settings</div>
							</a>
							<?php 
        if ( wpda_freemius()->is_registered() ) {
            ?>
								<a class="wpda-dashboard-item wpda_tooltip_icons" href="<?php 
            echo admin_url( 'admin.php' );
            // phpcs:ignore WordPress.Security.EscapeOutput
            ?>?page=<?php 
            echo esc_attr( WP_Data_Access_Admin::MAIN_PAGE_SLUG );
            ?>-account" title="Manage your WP Data Access account">
									<div class="fa-solid fa-user"></div>
									<div class="label">Account</div>
								</a>
								<?php 
        }
        ?>
							<a class="wpda-dashboard-item wpda_tooltip_icons" target="_blank" href="https://wpdataaccess.com/pricing/" title="Pricing, licensing and ordering">
								<div class="fa-solid fa-hand-holding-usd"></div>
								<div class="label">Pricing</div>
							</a>
							<a class="wpda-dashboard-item wpda_tooltip_icons" href="<?php 
        echo admin_url( 'admin.php' );
        // phpcs:ignore WordPress.Security.EscapeOutput
        ?>?page=<?php 
        echo esc_attr( WP_Data_Access_Admin::MAIN_PAGE_SLUG );
        ?>-pricing" title="Upgrade your WP Data Access account">
								<div class="fa-solid fa-gem"></div>
								<div class="label">Upgrade</div>
							</a>
                            <?php 
        if ( wpda_freemius()->is_free_plan() && !wpda_freemius()->is_trial() && !wpda_freemius()->is_trial_utilized() ) {
            ?>
                                <a class="wpda-dashboard-item wpda_tooltip_icons" href="https://wpdataaccess.com/order/?l=0&b=trial" title="Get your 14-day free trial" target="_blank">
                                    <div class="fa-solid fa-star"></div>
                                    <div class="label">Free Trial</div>
                                </a>
                                <?php 
        }
        ?>
                        </div>
						<div class="subject">Plugin & User Management</div>
					</div>
					<div class="wpda-dashboard-group wpda-dashboard-group-support">
						<div class="icons">
							<a class="wpda-dashboard-item wpda_tooltip_icons" target="_blank" href="<?php 
        echo $this->get_help_url();
        // phpcs:ignore WordPress.Security.EscapeOutput
        ?>" title="Context sensitive help">
								<div class="fa-solid fa-question-circle"></div>
								<div class="label">Docs</div>
							</a>
							<a class="wpda-dashboard-item wpda_tooltip_icons" target="_blank" href="https://wordpress.org/support/plugin/wp-data-access/" title="Public support forum">
								<div class="fa-solid fa-life-ring"></div>
								<div class="label">Forum</div>
							</a>
							<?php 
        ?>
						</div>
						<div class="subject">Help</div>
					</div>
				</div>
			</div>
			<?php 
    }

    /**
     * Construct mobile dashboard
     *
     * @return void
     */
    protected function dashboard_mobile() {
        $premium_separator = 'wpda-separator';
        ?>
			<div id="wpda-dashboard-mobile" style="display:none">
				<div id="wpda-dashboard-drop-down">
					<div class="wpda_nav_toggle" onclick="toggleMenu()"><i class="fas fa-bars"></i></div>
					<div class="wpda_nav_title">WP Data Access</div>
				</div>
				<ul>
					<li class="menu-item"><a href="<?php 
        echo admin_url( 'admin.php' );
        // phpcs:ignore WordPress.Security.EscapeOutput
        ?>?page=wpda"><i class="fas fa-database"></i> Explorer</a></li>
					<li class="menu-item"><a href="<?php 
        echo admin_url( 'admin.php' );
        // phpcs:ignore WordPress.Security.EscapeOutput
        ?>?page=wpda_query_builder"><i class="fas fa-code"></i> SQL</a></li>
					<li class="menu-item wpda-separator"><a href="<?php 
        echo admin_url( 'admin.php' );
        // phpcs:ignore WordPress.Security.EscapeOutput
        ?>?page=wpda_designer"><i class="fas fa-drafting-compass"></i> Designer</a></li>
					<li class="menu-item wpda-separator"><a href="<?php 
        echo admin_url( 'admin.php' );
        // phpcs:ignore WordPress.Security.EscapeOutput
        ?>?page=wpda_apps"><span style="display: inline-block; width: 20px"><svg xmlns="http://www.w3.org/2000/svg" height="12px" width="12px" viewBox="4 4 16 16" fill="inherit">
								<path d="M4 8h4V4H4v4zm6 12h4v-4h-4v4zm-6 0h4v-4H4v4zm0-6h4v-4H4v4zm6 0h4v-4h-4v4zm6-10v4h4V4h-4zm-6 4h4V4h-4v4zm6 6h4v-4h-4v4zm0 6h4v-4h-4v4z"/>
							</svg></span> Apps</a></li>
					<?php 
        ?>
					<li class="menu-item <?php 
        echo esc_attr( $premium_separator );
        ?>"><a href="<?php 
        echo admin_url( 'admin.php' );
        // phpcs:ignore WordPress.Security.EscapeOutput
        ?>?page=wpda_publisher"><i class="fas fa-address-card"></i> Tables</a></li>
					<?php 
        ?>
					<li class="menu-item"><a href="<?php 
        echo admin_url( 'admin.php' );
        // phpcs:ignore WordPress.Security.EscapeOutput
        ?>?page=wpda_wpdp"><i class="fas fa-magic"></i> Forms</a></li>
					<li class="menu-item wpda-separator"><a href="<?php 
        echo admin_url( 'admin.php' );
        // phpcs:ignore WordPress.Security.EscapeOutput
        ?>?page=wpda_templates"><i class="fas fa-desktop"></i> Templates</a></li>
					<li class="menu-item"><a href="<?php 
        echo admin_url( 'options-general.php' );
        // phpcs:ignore WordPress.Security.EscapeOutput
        ?>?page=wpdataaccess"><i class="fas fa-cog"></i> Settings</a></li>
					<?php 
        if ( wpda_freemius()->is_registered() ) {
            ?>
						<li class="menu-item"><a href="<?php 
            echo admin_url( 'admin.php' );
            // phpcs:ignore WordPress.Security.EscapeOutput
            ?>?page=<?php 
            echo esc_attr( WP_Data_Access_Admin::MAIN_PAGE_SLUG );
            ?>-account"><i class="fas fa-user"></i> Account</a></li>
						<?php 
        }
        ?>
					<?php 
        $menufound = false;
        global $submenu;
        if ( isset( $submenu[WPDA::get_option( WP_Data_Access_Admin::PAGE_MAIN )] ) ) {
            foreach ( $submenu[WPDA::get_option( WP_Data_Access_Admin::PAGE_MAIN )] as $pluginmenu ) {
                if ( WP_Data_Access_Admin::MAIN_PAGE_SLUG . '-pricing' === $pluginmenu[2] ) {
                    $menufound = true;
                    break;
                }
            }
        }
        ?>
					<li class="menu-item <?php 
        echo ( $menufound ? '' : 'wpda-separator' );
        ?>"><a href="https://wpdataaccess.com/pricing/" target="_blank"><i class="fas fa-hand-holding-usd"></i> Pricing</a></li>
					<?php 
        if ( $menufound ) {
            ?>
						<li class="menu-item wpda-separator"><a href="<?php 
            echo admin_url( 'admin.php' );
            // phpcs:ignore WordPress.Security.EscapeOutput
            ?>?page=<?php 
            echo esc_attr( WP_Data_Access_Admin::MAIN_PAGE_SLUG );
            ?>-pricing"><i class="fas fa-gem"></i> Upgrade</a></li>
						<?php 
        }
        ?>
					<li class="menu-item"><a target="_blank" href="https://docs.wpdataaccess.com/"><i class="fas fa-question"></i> Online Documentation</a></li>
					<li class="menu-item"><a target="_blank" href="https://wordpress.org/support/plugin/wp-data-access/"><i class="fas fa-life-ring"></i> Support Forum</a></li>
					<?php 
        ?>
				</ul>
			</div>
			<?php 
    }

    /**
     * Add dashboard tabs
     *
     * @return void
     */
    protected function tabs() {
    }

    /**
     * Construct toolbar
     *
     * @return void
     */
    protected function toolbar() {
        if ( $this->cannot_create_dashboard ) {
            return;
        }
        $remove_columns_message = __( 'Remove columns outside of range? Widgets will no longer be available!', 'wp-data-access' );
        ?>
			<div id="wpda-dashboard-toolbar" class="wpda-dashboard-toolbar" style="display:none">
				<div class="wpda-nowrap">
					<div>
					<?php 
        if ( WPDA::current_user_is_admin() ) {
            ?>
							<div>
								<a href="javascript:addPanel()"
								   class="wpda-dashboard-item wpda_tooltip"
								   title="Create new widget"
								>
									<i class="fas fa-folder-plus"></i>
									<div>
										Create widget
									</div>
								</a>
							</div>
							<?php 
        }
        ?>
					</div>
				</div>
				<div>
					<?php 
        $this->get_promotions( 'toolbar' );
        ?>
				</div>
				<div style="white-space:nowrap">
					<div>
						<div>
							<a href="javascript:removeColumns(1)"
							   class="wpda-dashboard-item wpda_tooltip"
							   title="One column"
							>
								<i class="fas fa-dice-one"></i>
								<div>
									1 column
								</div>
							</a>
						</div><div>
							<a href="javascript:removeColumns(2); addColumns(2)"
							   class="wpda-dashboard-item wpda_tooltip"
							   title="Switch to two column layout"
							>
								<i class="fas fa-dice-two"></i>
								<div>
									2 columns
								</div>
							</a>
						</div><div>
							<a href="javascript:removeColumns(3); addColumns(3)"
							   class="wpda-dashboard-item wpda_tooltip"
							   title="Switch to three column layout"
							>
								<i class="fas fa-dice-three"></i>
								<div>
									3 columns
								</div>
							</a>
						</div><div>
							<a href="javascript:addColumns(4)"
							   class="wpda-dashboard-item wpda_tooltip"
							   title="Switch to four column layout"
							>
								<i class="fas fa-dice-four"></i>
								<div>
									4 columns
								</div>
							</a>
						</div>
					</div>
				</div>
			</div>
			<?php 
        if ( wpda_freemius()->is_free_plan() ) {
            ?>
				<div class="wpda_dashboard_free_message">
					<table>
						<tr>
							<td style="text-align:left">
								The free version of WP Data Access has limited dashboard support.
								Data Widgets can be added and moved, but not saved or shared.
								Update to premium to analyse, report and share your data.
							</td>
							<td style="white-space:nowrap">
								<a href="https://wpdataaccess.com/pricing/" target="_blank" class="button button-primary">UPGRADE TO PREMIUM</a>
								<a href="https://docs.legacy.wpdataaccess.com/docs/bi-getting-started/" target="_blank" class="button">READ MORE</a>
							</td>
						</tr>
					</table>
				</div>
				<?php 
        }
        ?>
			<script type="application/javascript">
				function manageTabs() {
					closePanel();
					jQuery("#wpda-manage-tabs").show();
				}
				function noColumns() {
					return jQuery(".wpda-dashboard-column").length;
				}
				function addColumns(colNum) {
					if (noColumns()<colNum) {
						for (var i=noColumns()+1; i<=colNum; i++) {
							jQuery("#wpda-dashboard-content").append('<div id="wpda-dashboard-column-' + i + '" class="wpda-dashboard-column wpda-dashboard-column-' + i + '"></div>');
						}
						refreshPanels(colNum);
						resetColumnSelection();
						makeSortable();
						saveDashBoard();
					}
				}
				function removeColumns(colNum) {
					if (noColumns()>colNum) {
						if (confirm("<?php 
        echo esc_html( $remove_columns_message );
        ?>")) {
							noCols = noColumns();
							for (var i=colNum+1; i<=noCols; i++) {
								jQuery("#wpda-dashboard-column-" + i).remove();
							}
							refreshPanels(colNum);
							makeSortable();
							saveDashBoard();
						}
					}
				}
				function refreshPanels(colNum) {
					for (var i=1; i<=4; i++) {
						jQuery(".wpda-dashboard-column").removeClass("wpda-dashboard-column-" + i);
					}
					jQuery(".wpda-dashboard-column").addClass("wpda-dashboard-column-" + colNum);
					refreshAllPanels();
				}
				jQuery(function(){
					jQuery("#wpda_delete_new_dashboard_message").on("click", function() {
						if (confirm("Remove message and link? This cannot be undone!")) {
							jQuery.ajax({
								type: "POST",
								url: wpda_ajaxurl + "?action=wpda_remove_new_dashboard_message",
								data: {
									wp_nonce: wpda_wpnonce_save
								}
							}).done(
								function(data) {
									if (data==="OK") {
										jQuery("#wpda_delete_new_dashboard_message_container").hide();
									}
								}
							);
						}
					});
				});
			</script>
			<?php 
    }

    /**
     * Data Explorer main page toolbar
     *
     * @return void
     */
    protected function toolbar_wpda() {
        $schema_name = ( isset( $_REQUEST['wpdaschema_name'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_REQUEST['wpdaschema_name'] ) ) ) : '' );
        // phpcs:ignore WordPress.Security.NonceVerification
        ?>
			<form id="wpda_new_design" style="display: none" method="post" action="?page=<?php 
        echo esc_attr( WP_Data_Access_Admin::PAGE_DESIGNER );
        ?>">
				<input type="hidden" name="action" value="create_table">
				<input type="hidden" name="caller" value="dataexplorer">
			</form>
			<form id="wpda_goto_backup" style="display: none" method="post" action="?page=<?php 
        echo esc_attr( WP_Data_Access_Admin::PAGE_MAIN );
        ?>&page_action=wpda_backup">
				<input type="hidden" name="wpdaschema_name" value="<?php 
        echo esc_attr( $schema_name );
        ?>">
			</form>
			<div id="wpda-dashboard-toolbar" class="wpda-dashboard-toolbar" style="display:none">
				<div class="wpda-nowrap">
					<div>
						<div>
							<a href="<?php 
        echo admin_url( 'admin.php' );
        // phpcs:ignore WordPress.Security.EscapeOutput
        ?>?page=<?php 
        echo esc_attr( WP_Data_Access_Admin::PAGE_MAIN );
        ?>&page_action=wpda_global_search"
							   class="wpda-dashboard-item wpda_tooltip"
							   title="Search and replace text in multiple databases and tables"
							>
								<i class="fas fa-search-plus"></i>
								<div>
									Search & Replace
								</div>
							</a>
						</div><div>
							<a href="javascript:jQuery('#wpda_new_design').submit()"
							   class="wpda-dashboard-item wpda_tooltip"
							   title="Create new table design"
							>
								<i class="fas fa-plus-circle"></i>
								<div>
									Create table design
								</div>
							</a>
						</div>
					</div><div>
						<div>
							<?php 
        if ( 'new' !== $this->current_version && '' !== $this->current_version ) {
            ?>
								<a onclick="jQuery('#upload_file_container_multi').show();"
								   href="javascript:void(0)"
								   class="wpda-dashboard-item wpda_tooltip"
								   title="Import and execute SQL script files"
								>
									<i class="fas fa-file-code"></i>
									<div>
										Import SQL files
									</div>
								</a>
								<?php 
        } else {
            ?>
								<a onClick="window.ppActionEnableImport();"
								   href="javascript:void(0)"
								   class="wpda-dashboard-item wpda_tooltip"
								   title="Import and execute SQL script files"
								>
									<i class="fas fa-file-code"></i>
									<div>
										Import SQL files
									</div>
								</a>
								<?php 
        }
        ?>
						</div><div>
							<a href="<?php 
        echo admin_url( 'admin.php' );
        // phpcs:ignore WordPress.Security.EscapeOutput
        ?>?page=<?php 
        echo esc_attr( WP_Data_Access_Admin::PAGE_MAIN );
        ?>&page_action=wpda_import_csv"
							   class="wpda-dashboard-item wpda_tooltip"
							   title="Import CSV files"
						   	>
								<i class="fas fa-file-csv"></i>
								<div>
									Import CSV files
								</div>
							</a>
						</div><div>
                            <a onclick="window.ppActionEnableExport();"
                               href="javascript:void(0)"
                               class="wpda-dashboard-item wpda_tooltip"
                               title="Unattended scheduled exports"
                            >
                                <i class="fas fa-clock"></i>
                                <div>
                                    Export
                                </div>
                            </a>
                        </div><?php 
        $wpda_db_options_activated = get_option( 'wpda_db_options_activated' );
        if ( is_array( $wpda_db_options_activated ) && 0 < count( $wpda_db_options_activated ) ) {
            // Show old Data Backup feature only if still in use
            ?><div>
                            <a href="javascript:void(0)"
                               id="wpda_toolbar_icon_go_backup"
                               class="wpda-dashboard-item wpda_tooltip"
                               title="Data Backup - unattended exports"
                            >
                                <i class="fas fa-file-archive"></i>
                                <div>
                                    Data Backup
                                </div>
                            </a>
                        </div><?php 
        }
        ?>
					</div><div>
						<?php 
        if ( 'new' !== $this->current_version && '' !== $this->current_version ) {
            ?>
							<div>
								<a onclick="jQuery('#wpda_db_container').show(); jQuery('#local_database').focus();"
								   href="javascript:void(0)"
								   class="wpda-dashboard-item wpda_tooltip"
								   title="Add remote database or create local database"
								>
									<i id="wpda_toolbar_icon_add_database" class="fas fa-database"></i>
									<div>
										Add database
									</div>
								</a>
							</div>
							<?php 
        } else {
            ?>
							<div>
								<a onclick="jQuery('#wpda_manage_databases').show(); jQuery('#local_database').focus();"
								   href="javascript:void(0)"
								   class="wpda-dashboard-item wpda_tooltip"
								   title="Manage remote database connections and local databases"
								>
									<i id="wpda_toolbar_icon_add_database" class="fas fa-server"></i>
									<div>
										Databases
									</div>
								</a>
							</div><?php 
        }
        ?>
						<?php 
        ?>
					</div>
				</div>
				<div class="wpda-promotion" style="font-size: 16px">
					<?php 
        if ( 'new' !== $this->current_version && '' !== $this->current_version ) {
            ?>
						<a href="?page=<?php 
            echo esc_attr( WP_Data_Access_Admin::PAGE_MAIN );
            ?>&de=new"
						   style="display: flex; align-items: center; gap: 5px;"
						>
							<i class="fas fa-toggle-off"></i>
							<span>Switch to new Data Explorer</span>
						</a>
						<?php 
        } else {
            ?>
						<span
							style="display: flex; align-items: center; gap: 5px; white-space: nowrap;"
						>
							<a href="?page=<?php 
            echo esc_attr( WP_Data_Access_Admin::PAGE_MAIN );
            ?>&de=old"
							   style="display: flex; align-items: center; gap: 5px; white-space: nowrap;"
							>
								<i class="fas fa-toggle-on"></i>
								<span>Switch to old Data Explorer</span>
							</a>
						</span>
						<?php 
        }
        ?>
				</div>
				<?php 
        //$this->get_promotions('wpda');
        ?>
			</div>
			<?php 
    }

    /**
     * Data Explorer table page toolbar
     *
     * @return void
     */
    protected function toolbar_wpda_table() {
        ?>
			<form id="wpda_new_row" style="display: none" method="post" action="?page=<?php 
        echo esc_attr( WP_Data_Access_Admin::PAGE_MAIN );
        ?>">
				<?php 
        if ( isset( $_REQUEST['wpdaschema_name'] ) ) {
            // phpcs:ignore WordPress.Security.NonceVerification
            $schema_name = esc_attr( sanitize_text_field( wp_unslash( $_REQUEST['wpdaschema_name'] ) ) );
            // phpcs:ignore WordPress.Security.NonceVerification
            echo "<input type='hidden' name='wpdaschema_name' value='{$schema_name}'>";
            // phpcs:ignore WordPress.Security.EscapeOutput
        }
        ?>
				<input type="hidden" id="wpda_new_row_table_name" name="table_name" value="">
				<input type="hidden" name="action" value="new">
			</form>
			<div id="wpda-dashboard-toolbar" class="wpda-dashboard-toolbar" style="display:none">
				<div class="wpda-nowrap">
					<div>
						<div>
							<a href="javascript:void(0)"
							   id="wpda_toolbar_icon_add_row"
							   class="wpda-dashboard-item wpda_tooltip"
							   title="Add new row to table"
							>
								<i class="fas fa-plus-circle"></i>
								<div>
									Add row
								</div>
							</a>
						</div><div>
							<a onclick="jQuery('#upload_file_container').show()"
							   href="javascript:void(0)"
							   class="wpda-dashboard-item wpda_tooltip"
							   title="Allows only imports into table authors"
							>
								<i class="fas fa-code"></i>
								<div>
									Import rows
								</div>
							</a>
						</div>
					</div>
				</div>
				<?php 
        $this->get_promotions( 'table' );
        ?>
			</div>
			<?php 
    }

    /**
     * Data Explorer data entry form toolbar
     *
     * @return void
     */
    protected function toolbar_wpda_row() {
        ?>
			<form id="wpda_new_row" style="display: none" method="post" action="?page=<?php 
        echo esc_attr( WP_Data_Access_Admin::PAGE_MAIN );
        ?>">
				<?php 
        if ( isset( $_REQUEST['wpdaschema_name'] ) ) {
            // phpcs:ignore WordPress.Security.NonceVerification
            $schema_name = esc_attr( sanitize_text_field( wp_unslash( $_REQUEST['wpdaschema_name'] ) ) );
            // phpcs:ignore WordPress.Security.NonceVerification
            echo "<input type='hidden' name='wpdaschema_name' value='{$schema_name}'>";
            // phpcs:ignore WordPress.Security.EscapeOutput
        }
        ?>
				<input type="hidden" id="wpda_new_row_table_name" name="table_name" value="">
				<input type="hidden" name="action" value="new">
			</form>
			<div id="wpda-dashboard-toolbar" class="wpda-dashboard-toolbar" style="display:none">
				<div class="wpda-nowrap">
					<div>
						<div>
							<a href="javascript:void(0)"
							   id="wpda_toolbar_icon_add_row"
							   class="wpda-dashboard-item wpda_tooltip"
							   title="Add new row to table"
							>
								<i class="fas fa-plus-circle"></i>
								<div>
									Add row
								</div>
							</a>
						</div>
					</div>
				</div>
				<?php 
        $this->get_promotions( 'row' );
        ?>
			</div>
			<?php 
    }

    /**
     * Data Backup toolbar
     *
     * @return void
     */
    protected function toolbar_backup() {
        ?>
			<form id="wpda_new_backup" style="display: none" method="post" action="?page=<?php 
        echo esc_attr( WP_Data_Access_Admin::PAGE_MAIN );
        ?>&page_action=wpda_backup">
				<input type="hidden" id="wpda_new_backup_wpdaschema_name" name="wpdaschema_name" value="">
				<input type="hidden" name="action" value="new">
				<input type="hidden" name="wp_nonce" value="<?php 
        echo esc_attr( wp_create_nonce( 'wpda-backup-' . WPDA::get_current_user_login() ) );
        ?>"/>
			</form>
			<div id="wpda-dashboard-toolbar" class="wpda-dashboard-toolbar" style="display:none">
				<div class="wpda-nowrap">
					<div>
						<div>
							<a href="javascript:void(0)"
							   id="wpda_toolbar_icon_add_backup"
							   class="wpda-dashboard-item wpda_tooltip"
							   title="Create new data backup"
							>
								<i  class="fas fa-plus-circle"></i>
								<div>
									Create backup
								</div>
							</a>
						</div>
					</div>
				</div>
				<?php 
        $this->get_promotions( 'backup' );
        ?>
			</div>
			<?php 
    }

    /**
     * CSV import toolbar
     *
     * @return void
     */
    protected function toolbar_import_csv() {
        if ( !isset( $_REQUEST['action'] ) || '-1' === $_REQUEST['action'] || 'bulk-delete' === $_REQUEST['action'] || 'delete' === $_REQUEST['action'] ) {
            ?>
				<form id="wpda_upload_csv" style="display:none" method="post" action="?page=wpda&page_action=wpda_import_csv">
					<input type="hidden" name="action" value="upload">
				</form>
				<div id="wpda-dashboard-toolbar" class="wpda-dashboard-toolbar" style="display:none">
					<div class="wpda-nowrap">
						<div>
							<div>
								<a href="javascript:jQuery('#wpda_upload_csv').submit()"
								   class="wpda-dashboard-item wpda_tooltip"
								   title="Upload new CSV File"
								>
									<i class="fas fa-plus-circle"></i>
									<div>
										Upload CSV file
									</div>
								</a>
							</div>
						</div>
					</div>
					<?php 
            $this->get_promotions( 'csv' );
            ?>
				</div>
				<?php 
        }
    }

    /**
     * Data Apps toolbar.
     *
     * @return void
     */
    protected function toolbar_apps() {
        $has_apps = WPDA_App_Model::has_any();
        ?>
			<div id="wpda-dashboard-toolbar" class="wpda-dashboard-toolbar" style="display:none">
				<div class="wpda-nowrap">
					<div>
						<div>
							<a href="javascript:window.ppActionCreateApp()"
							   class="wpda-dashboard-item wpda_tooltip wpda-create-new-app"
							   title="Create new app"
							>
								<i class="fas fa-plus-circle"></i>
								<div>
									Create New App
								</div>
							</a>
						</div><div>
                            <a href="javascript:window.ppActionRenameDatabase()"
                               class="wpda-dashboard-item wpda_tooltip wpda-rename-database"
                               title="Rename Database"
                            >
                                <svg stroke="currentColor" fill="none" stroke-width="2" viewBox="0 2 24 24" stroke-linecap="round" stroke-linejoin="round" height="32px" width="32px" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                    <path d="M4 6c0 1.657 3.582 3 8 3s8 -1.343 8 -3s-3.582 -3 -8 -3s-8 1.343 -8 3"></path>
                                    <path d="M4 6v6c0 1.657 3.582 3 8 3c.478 0 .947 -.016 1.402 -.046"></path>
                                    <path d="M20 12v-6"></path>
                                    <path d="M4 12v6c0 1.526 3.04 2.786 6.972 2.975"></path>
                                    <path d="M18.42 15.61a2.1 2.1 0 0 1 2.97 2.97l-3.39 3.42h-3v-3l3.42 -3.39z"></path>
                                </svg>
                                <div>
                                    Rename Database
                                </div>
                            </a>
                        </div><?php 
        ?>
					</div>
				</div>
				<div class="wpda-promotion" style="display: flex; align-items: center; gap: 5px; font-size: 16px;">
                    <?php 
        if ( !$has_apps ) {
            ?>
                    <span>
                        <strong>
                            New to the App Builder? Watch the Getting Started tutorial! 👉
                        </strong>
                    </span><?php 
        }
        ?><span>
						<a href="https://www.youtube.com/watch?v=j0MJvuMG7k8"
                           target="_blank"
                           class="wpda_tooltip"
                           title="App Builder Getting Started tutorial"
                        >
							<i class="fas fab fa-square-youtube"></i>
						</a>
					</span><span>
						<a href="https://docs.rad.wpdataaccess.com/"
						   target="_blank"
						   class="wpda_tooltip"
						   title="Online App Builder documentation"
						>
							<i class="fas fa-circle-info"></i>
						</a>
					</span>
				</div>
				<?php 
        // $this->get_promotions('apps');
        ?>
			</div>
			<?php 
    }

    /**
     * Query Builder toolbar
     *
     * @return void
     */
    protected function toolbar_sql() {
        $current_query_builder_version = get_user_meta( WPDA::get_current_user_id(), 'wpda_query_builder_version', true );
        ?>
			<div id="wpda-dashboard-toolbar" class="wpda-dashboard-toolbar" style="display:none">
				<div class="wpda-nowrap">
					<div>
                        <?php 
        if ( 'old' === $current_query_builder_version ) {
            ?>
                            <div>
                                <a href="javascript:tabNew()"
                                   class="wpda-dashboard-item wpda_tooltip"
                                   title="Create new query"
                                >
                                    <i class="fas fa-plus-circle"></i>
                                    <div>
                                        Create new query
                                    </div>
                                </a>
                            </div><div>
                                <a href="javascript:openQuery()"
                                   class="wpda-dashboard-item wpda_tooltip"
                                   title="Open existing query"
                                >
                                    <i class="fas fa-folder-open"></i>
                                    <div>
                                        Open existing query
                                    </div>
                                </a>
                            </div>
                            <?php 
        } else {
            ?>
                            <div>
                                <a href="javascript:void(0)"
                                   onclick="ppActionOpenQueriesMenu(event)"
                                   class="wpda-dashboard-item wpda_tooltip"
                                   title="Create new query"
                                >
                                    <i class="fas fa-bars"></i>
                                    <div>
                                        Menu
                                    </div>
                                </a>
                            </div><div>
                                <a href="javascript:void(0)"
                                   onclick="ppActionFullScreen()"
                                   class="wpda-dashboard-item wpda_tooltip"
                                   title="Switch to full screen mode"
                                >
                                    <i class="fas fa-expand"></i>
                                    <div>
                                        Full Screen
                                    </div>
                                </a>
                            </div>
                        <?php 
        }
        ?>
					</div>
				</div>
                <div class="wpda-promotion" style="font-size: 16px">
                    <?php 
        if ( 'old' !== $current_query_builder_version ) {
            ?>
                        <span
                            style="display: flex; align-items: center; gap: 5px; white-space: nowrap;"
                        >
							<a href="?page=<?php 
            echo esc_attr( WP_Data_Access_Admin::PAGE_QUERY_BUILDER );
            ?>&qb=old"
                               style="display: flex; align-items: center; gap: 5px; white-space: nowrap;"
                            >
								<i class="fas fa-toggle-on"></i>
								<span>Switch to old Query Builder</span>
							</a>
						</span>
                        <?php 
        } else {
            ?>
                        <a href="?page=<?php 
            echo esc_attr( WP_Data_Access_Admin::PAGE_QUERY_BUILDER );
            ?>&qb=new"
                           style="display: flex; align-items: center; gap: 5px;"
                        >
                            <i class="fas fa-toggle-off"></i>
                            <span>Switch to new Query Builder</span>
                        </a>
                        <?php 
        }
        ?>
                </div>
			</div>
			<?php 
    }

    /**
     * Data Designer toolbar
     *
     * @return void
     */
    protected function toolbar_designer() {
        ?>
			<form id="wpda_new_design" style="display: none"  method="post" action="?page=<?php 
        echo esc_attr( WP_Data_Access_Admin::PAGE_DESIGNER );
        ?>">
				<input type="hidden" name="action" value="edit">
				<input type="hidden" name="table_name" value="<?php 
        echo esc_attr( WPDA_Design_Table_Model::get_base_table_name() );
        ?>">
			</form>
			<div id="wpda-dashboard-toolbar" class="wpda-dashboard-toolbar" style="display:none">
				<div class="wpda-nowrap">
					<div>
						<div>
							<a href="javascript:jQuery('#wpda_new_design').submit()"
							   class="wpda-dashboard-item wpda_tooltip"
							   title="Create new table design"
							>
								<i class="fas fa-plus-circle"></i>
								<div>
									Create table design
								</div>
							</a>
						</div><div>
							<a onclick="jQuery('#upload_file_container').show()"
							   href="javascript:void(0)"
							   class="wpda-dashboard-item wpda_tooltip"
							   title="Import table designs"
							>
								<i class="fas fa-code"></i>
								<div>
									Import table designs
								</div>
							</a>
						</div>
					</div>
				</div>
				<?php 
        $this->get_promotions( 'designer' );
        ?>
			</div>
			<?php 
    }

    /**
     * Data Tables toolbar
     *
     * @return void
     */
    protected function toolbar_publisher() {
        ?>
			<form id="wpda_new_publication" style="display: none" method="post" action="?page=<?php 
        echo esc_attr( WP_Data_Access_Admin::PAGE_PUBLISHER );
        ?>">
				<input type="hidden" id="wpda_new_publication_table_name" name="table_name" value="">
				<input type="hidden" name="action" value="new">
			</form>
			<div id="wpda-dashboard-toolbar" class="wpda-dashboard-toolbar" style="display:none">
				<div class="wpda-nowrap">
					<div>
						<div>
							<a href="javascript:void(0)"
							   id="wpda_toolbar_icon_add_publication"
							   class="wpda-dashboard-item wpda_tooltip"
							   title="Create new data table"
							>
								<i class="fas fa-plus-circle"></i>
								<div>
									Create data table
								</div>
							</a>
						</div><div>
							<a onclick="jQuery('#upload_file_container').show()"
							   href="javascript:void(0)"
							   class="wpda-dashboard-item wpda_tooltip"
							   title="Import data tables"
							>
								<i class="fas fa-code"></i>
								<div>
									Import data tables
								</div>
							</a>
						</div><?php 
        ?>
					</div>
				</div>
				<?php 
        $this->get_promotions( 'publisher' );
        ?>
			</div>
			<?php 
    }

    /**
     * Charts toolbar
     *
     * @return void
     */
    protected function toolbar_charts() {
    }

    /**
     * Data Projects toolbar
     *
     * @return void
     */
    protected function toolbar_projects() {
        ?>
			<form id="wpda_new_project" style="display: none" method="post" action="?page=<?php 
        echo esc_attr( WPDP::PAGE_MAIN );
        ?>">
				<input type="hidden" name="action" value="new">
				<input type="hidden" name="mode" value="edit">
				<input type="hidden" name="table_name" value="<?php 
        echo esc_attr( WPDP_Project_Model::get_base_table_name() );
        ?>">
			</form>
			<div id="wpda-dashboard-toolbar" class="wpda-dashboard-toolbar" style="display:none">
				<div class="wpda-nowrap">
					<div>
						<div>
							<a href="javascript:jQuery('#wpda_new_project').submit()"
							   class="wpda-dashboard-item wpda_tooltip"
							   title="Create new project"
							>
								<i class="fas fa-plus-circle"></i>
								<div>
									Create project
								</div>
							</a>
						</div><div>
							<a onclick="jQuery('#upload_file_container_multi').show()"
							   href="javascript:void(0)"
							   class="wpda-dashboard-item wpda_tooltip"
							   title="Import projects"
							>
								<i class="fas fa-code"></i>
								<div>
									Import projects
								</div>
							</a>
						</div>
					</div>
				</div>
				<?php 
        $this->get_promotions( 'projects' );
        ?>
			</div>
			<?php 
    }

    /**
     * Project Templates toolbar
     *
     * @return void
     */
    protected function toolbar_templates() {
        ?>
			<div id="wpda-dashboard-toolbar" class="wpda-dashboard-toolbar" style="display:none">
				<div class="wpda-nowrap">
					<div>
						<div>
							<a onclick="jQuery('#no_repository_buttons').hide(); jQuery('#add_table_to_repository').show()"
							   href="javascript:void(0)"
							   class="wpda-dashboard-item wpda_tooltip"
							   title="Create new project template"
							>
								<i class="fas fa-plus-circle"></i>
								<div>
									Create project template
								</div>
							</a>
						</div>
					</div>
				</div>
				<?php 
        $this->get_promotions( 'templates' );
        ?>
			</div>
			<?php 
    }

    /**
     * Widgets toolbar
     *
     * @return void
     */
    protected function add_forms() {
        ?>
			<div id="wpda-select-panel-type" class="wpda-add-panel" style="display: none">
				<form>
					<fieldset class="wpda_fieldset wpda_fieldset_dashboard">
						<legend>
							Create widget
						</legend>
						<div>
							<label>
								Widget type
							</label>
							<select id="wpda-select-panel-type-choice">
								<?php 
        if ( class_exists( 'Code_Manager\\Code_Manager_Model' ) ) {
            ?>
									<option value="code">Custom Code</option>
								<?php 
        }
        ?>
								<option value="chart" selected>Chart</option>
								<option value="pub">Data Table</option>
								<?php 
        if ( class_exists( 'WPDataAccess\\Premium\\WPDAPRO_Dashboard\\WPDAPRO_Widget_Project' ) ) {
            ?>
									<option value="project">Data Project</option>
								<?php 
        }
        ?>
								<option value="dbs">Database Info</option>
							</select>
							<?php 
        if ( !class_exists( 'Code_Manager\\Code_Manager_Model' ) || !class_exists( 'WPDataAccess\\Premium\\WPDAPRO_Dashboard\\WPDAPRO_Widget_Project' ) ) {
            ?>
								<a href="https://docs.legacy.wpdataaccess.com/docs/bi-getting-started/" target="_blank">
									<i class="fas fa-question-circle pointer wpda_tooltip"
									   style="font-size: 170%; vertical-align: middle"
									   title="Your installation does not support all available widget types! Click to learn how to install more widget types..."></i>
								</a>
							<?php 
        }
        ?>
						</div>
					</fieldset>
					<div class="wpda-panel-buttons">
						<span class="wpda-panel-buttons">
							<button id="wpda-select-panel-type-button" class="button button-primary">
								<i class="fas fa-check wpda_icon_on_button"></i>
								Next
							</button>
							<button id="wpda-select-panel-type-button-cancel" class="button button-secondary">
								<i class="fas fa-times-circle wpda_icon_on_button"></i>
								Cancel
							</button>
						</span>
					</div>
				</form>
			</div>
			<div id="wpda-add-panel" class="wpda-add-panel" style="display: none">
				<form>
					<fieldset class="wpda_fieldset wpda_fieldset_dashboard">
						<legend>
							Create widget
						</legend>
						<div>
							<label>
								Widget type
							</label>
							<input type="text" id="wpda-add-panel-type-show" readonly>
							<input type="hidden" id="wpda-add-panel-type">
						</div>
						<div>
							<label>
								Widget name
							</label>
							<input type="text" id="wpda-add-panel-name" required/>
						</div>
						<div id="wpda-panel-code" style="display: none">
							<label>
								Select shortcode
							</label>
							<select id="wpda-add-panel-code">
								<?php 
        if ( class_exists( 'Code_Manager\\Code_Manager_Model' ) ) {
            $codes = \Code_Manager\Code_Manager_Model::get_active_shortcodes();
            foreach ( $codes as $code ) {
                ?>
										<option value="<?php 
                echo esc_attr( $code['code_id'] );
                ?>"><?php 
                echo esc_attr( $code['code_name'] ) . ' (' . esc_attr( $code['code_type'] ) . ')';
                ?></option>
										<?php 
            }
        }
        ?>
							</select>
						</div>
						<div id="wpda-panel-project">
							<label>
								Select project
							</label>
							<select id="wpda-add-panel-project">
								<?php 
        $projects = WPDP_Project_Model::get_project_list();
        foreach ( $projects as $project ) {
            ?>
									<option value="<?php 
            echo esc_attr( $project['project_id'] );
            ?>"><?php 
            echo esc_attr( $project['project_name'] ) . ' (project_id=' . esc_attr( $project['project_id'] ) . ')';
            ?></option>
									<?php 
        }
        ?>
							</select>
						</div>
						<div id="wpda-panel-publication">
							<label>
								Select data table
							</label>
							<select id="wpda-add-panel-publication">
								<?php 
        $pubs = WPDA_Publisher_Model::get_publication_list();
        foreach ( $pubs as $pub ) {
            ?>
									<option value="<?php 
            echo esc_attr( $pub['pub_id'] );
            ?>"><?php 
            echo esc_attr( $pub['pub_name'] ) . ' (pub_id=' . esc_attr( $pub['pub_id'] ) . ')';
            ?></option>
									<?php 
        }
        ?>
							</select>
						</div>
						<div id="wpda-panel-dbms">
							<label>
								Select database
							</label>
							<select id="wpda-add-panel-dbms">
								<option value="wpdb" selected>WordPress database (
								<?php 
        global $wpdb;
        echo esc_attr( $wpdb->dbname );
        ?>
								)</option>
								<?php 
        $rdbs = WPDADB::get_remote_databases();
        ksort( $rdbs );
        //phpcs:ignore - 8.1 proof
        foreach ( $rdbs as $key => $rdb ) {
            ?>
									<option value="<?php 
            echo esc_attr( $key );
            ?>"><?php 
            echo esc_attr( $key );
            ?></option>
									<?php 
        }
        ?>
							</select>
						</div>
						<div>
							<label>
								Add to column
							</label>
							<select id="wpda-add-panel-column">
								<option value="1" selected>1</option>
								<option value="2">2</option>
							</select>
							<select id="wpda-add-panel-position">
								<option value="prepend" selected>Before</option>
								<option value="append">After</option>
							</select>
						</div>
					</fieldset>
					<div class="wpda-panel-buttons">
						<span class="wpda-panel-buttons">
							<button id="wpda-add-panel-button" class="button button-primary">
								<i class="fas fa-check wpda_icon_on_button"></i>
								Create
							</button>
							<button id="wpda-add-panel-button-back" class="button">
								<i class="fas fa-angle-left wpda_icon_on_button"></i>
								Back
							</button>
							<button id="wpda-add-panel-button-cancel" class="button button-secondary">
								<i class="fas fa-times-circle wpda_icon_on_button"></i>
								Cancel
							</button>
						</span>
					</div>
				</div>
			</form>
			<script type="application/javascript">
				jQuery(function() {
					jQuery("#wpda-select-panel-type-button").on("click", function() {
						jQuery("#wpda-panel-code").hide();
						jQuery("#wpda-panel-project").hide();
						jQuery("#wpda-panel-publication").hide();
						jQuery("#wpda-panel-dbms").hide();

						jQuery("#wpda-add-panel-type-show").val(jQuery("#wpda-select-panel-type-choice option:selected").text());
						jQuery("#wpda-add-panel-type").val(jQuery("#wpda-select-panel-type-choice").val());

						switch (jQuery("#wpda-select-panel-type-choice").val()) {
							case "code":
								jQuery("#wpda-panel-code").show();
								break;
							case "dbs":
								jQuery("#wpda-panel-dbms").show();
								break;
							case "project":
								jQuery("#wpda-panel-project").show();
								break;
							case "pub":
								jQuery("#wpda-panel-publication").show();
								break;
						}

						jQuery("#wpda-select-panel-type").hide();
						jQuery("#wpda-add-panel").show();

						return false;
					});

					jQuery("#wpda-select-panel-type-button-cancel").on("click", function() {
						jQuery("#wpda-select-panel-type").hide();

						return false;
					});

					jQuery("#wpda-add-panel-button").on("click", function () {
						panelName = jQuery("#wpda-add-panel-name").val();

						if (panelName=="") {
							alert("Widget name is required");
						} else {
							switch (jQuery("#wpda-add-panel-type").val()) {
								case "chart":
									addPanelChartToDashboard(
										"<?php 
        echo esc_attr( $this->wp_nonce_add );
        ?>",
										panelName,
										null,
										null,
										jQuery("#wpda-add-panel-column").val(),
										jQuery("#wpda-add-panel-position").val(),
									);
									break;
								case "code":
									addPanelCodeToDashboard(
										"<?php 
        echo esc_attr( $this->wp_nonce_add );
        ?>",
										panelName,
										jQuery("#wpda-add-panel-code").val(),
										jQuery("#wpda-add-panel-column").val(),
										jQuery("#wpda-add-panel-position").val(),
									);
									break;
								case "dbs":
									addPanelDbmsToDashboard(
										"<?php 
        echo esc_attr( $this->wp_nonce_add );
        ?>",
										panelName,
										jQuery("#wpda-add-panel-dbms").val(),
										jQuery("#wpda-add-panel-column").val(),
										jQuery("#wpda-add-panel-position").val(),
									);
									break;
								case "project":
									addPanelProjectToDashboard(
										"<?php 
        echo esc_attr( $this->wp_nonce_add );
        ?>",
										panelName,
										jQuery("#wpda-add-panel-project").val(),
										jQuery("#wpda-add-panel-column").val(),
										jQuery("#wpda-add-panel-position").val(),
									);
									break;
								case "pub":
									addPanelPublicationToDashboard(
										"<?php 
        echo esc_attr( $this->wp_nonce_add );
        ?>",
										panelName,
										jQuery("#wpda-add-panel-publication").val(),
										jQuery("#wpda-add-panel-column").val(),
										jQuery("#wpda-add-panel-position").val(),
									);
									break;
								default:
									alert("Unknown panel type");
							}
						}

						return false;
					});

					jQuery("#wpda-add-panel-button-back").on("click", function() {
						jQuery("#wpda-add-panel").hide();
						jQuery("#wpda-select-panel-type").show();

						return false;
					});

					jQuery("#wpda-add-panel-button-cancel").on("click", function () {
						closePanel();

						return false;
					});
				});
			</script>
			<?php 
    }

    /**
     * Add dashboard columns
     *
     * @return void
     */
    protected function columns() {
        ?>
			<div id="wpda-dashboard-content" class="wpda-dashboard-content">
			<?php 
        // Following line requires PHP 7.
        // $last_key = array_key_last($this->dashboard_positions[0]); .
        $last_key = 1;
        if ( is_array( $this->dashboard_positions ) && count( $this->dashboard_positions ) > 0 ) {
            //phpcs:ignore - 8.1 proof
            foreach ( $this->dashboard_positions[0] as $key => $val ) {
                $last_key = $key;
            }
        }
        $this->number_of_columns = $last_key;
        for ($i = 1; $i <= $this->number_of_columns; $i++) {
            ?>
				<div id="wpda-dashboard-column-<?php 
            echo esc_attr( $i );
            ?>" class="wpda-dashboard-column wpda-dashboard-column-<?php 
            echo esc_attr( $this->number_of_columns );
            ?>">
				</div>
				<?php 
        }
        ?>
			</div>
			<?php 
    }

    /**
     * Add panels to dashboard
     *
     * @return void
     */
    protected function add_panels() {
    }

    /**
     * Add panel
     *
     * @param string $dashboard_widget Widget name.
     * @param string $column Column name.
     * @param string $position Position.
     * @param string $widget_id Widget ID.
     * @return void
     */
    protected static function add_panel(
        $dashboard_widget,
        $column,
        $position,
        $widget_id = null
    ) {
    }

    /**
     * Get available databases as option list
     *
     * @return string
     */
    private function get_databases() {
        // Get available databases.
        $dbs = WPDA_Dictionary_Lists::get_db_schemas();
        foreach ( $dbs as $db ) {
            $databases[] = $db['schema_name'];
        }
        global $wpdb;
        $database_options = '';
        foreach ( $databases as $database ) {
            $selected = ( WPDA::get_user_default_scheme() === $database ? 'selected' : '' );
            if ( $database === $wpdb->dbname ) {
                $database_text = "WordPress database ({$database})";
            } else {
                $database_text = $database;
            }
            $database_options .= '<option value="' . esc_attr( $database ) . '" ' . esc_attr( $selected ) . '>' . esc_attr( $database_text ) . '</option>';
        }
        // Return available databases as option list.
        return $database_options;
    }

    /**
     * Add javascript functions
     *
     * @return void
     */
    public function dashboard_js() {
        ?>
			<script type="application/javascript">
				let wpda_wpnonce_save   	= "<?php 
        echo esc_attr( wp_create_nonce( static::DASHBOARD_SAVE . WPDA::get_current_user_login() ) );
        ?>";
				let wpda_wpnonce_add    	= "<?php 
        echo esc_attr( wp_create_nonce( WPDA_Widget::WIDGET_ADD . WPDA::get_current_user_login() ) );
        ?>";
				let wpda_wpnonce_qb     	= "<?php 
        echo esc_attr( wp_create_nonce( 'wpda-query-builder-' . WPDA::get_current_user_id() ) );
        ?>";
				let wpda_wpnonce_refresh	= "<?php 
        echo esc_attr( wp_create_nonce( WPDA_Widget::WIDGET_REFRESH . WPDA::get_current_user_login() ) );
        ?>";
				let wpda_ajaxurl        	= "<?php 
        echo admin_url( 'admin-ajax.php' );
        // phpcs:ignore WordPress.Security.EscapeOutput
        ?>";
				let wpda_databases			= '<?php 
        echo $this->get_databases();
        // phpcs:ignore WordPress.Security.EscapeOutput
        ?>';
				let wpda_shared_dashboards	= <?php 
        echo json_encode( $this->shared_dashboards );
        // phpcs:ignore
        ?>;

				function saveWidgetPositions() {} // Implemented in the premium version
				function saveDashBoard(callback) {
					saveWidgetPositions();
					jQuery.ajax({
						type: "POST",
						url: wpda_ajaxurl + "?action=wpda_save_dashboard",
						data: {
							wp_nonce: wpda_wpnonce_save,
							wpda_widgets: dashboardWidgets,
							wpda_positions: dashboardWidgetPosition,
							wpda_deleted: dashboardWidgetDeleted,
							wpda_tabname: "<?php 
        echo esc_attr( $this->tab_name );
        ?>"
						}
					}).done(
						function(data) {
							dashboardWidgetDeleted = [];
							if (callback!==undefined) {
								callback();
							}
						}
					).fail(
						function (msg) {
							console.log("WP Data Access error (saveDashBoard):", msg);
						}
					);
				}
				<?php 
        ?>
			</script>
			<?php 
    }

    /**
     * Save dashboard and widgets
     *
     * @return void
     */
    public static function save() {
        $wp_nonce = ( isset( $_POST['wp_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['wp_nonce'] ) ) : '' );
        if ( !wp_verify_nonce( $wp_nonce, static::DASHBOARD_SAVE . WPDA::get_current_user_login() ) ) {
            WPDA::sent_header( 'application/json' );
            echo static::msg( 'ERROR', 'Token expired, please refresh page' );
            // phpcs:ignore WordPress.Security.EscapeOutput
            wp_die();
        }
        $widgets = ( isset( $_POST['wpda_widgets'] ) ? WPDA::sanitize_text_field_array( $_POST['wpda_widgets'] ) : array() );
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
        $save = array();
        foreach ( $widgets as $widget ) {
            $save[$widget['widgetName']] = $widget;
        }
        update_user_meta( WPDA::get_current_user_id(), self::USER_DASHBOARD, $save );
        echo self::msg( 'SUCCESS', 'Widget succesfully saved' );
        // phpcs:ignore WordPress.Security.EscapeOutput
        wp_die();
    }

    /**
     * Get list of all widgets
     *
     * @return void
     */
    public static function get_list() {
        $wp_nonce = ( isset( $_POST['wpda_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['wpda_wpnonce'] ) ) : '' );
        if ( !wp_verify_nonce( $wp_nonce, WPDA_Widget::WIDGET_REFRESH . WPDA::get_current_user_login() ) ) {
            WPDA::sent_header( 'application/json' );
            echo static::msg( 'ERROR', 'Token expired, please refresh page' );
            // phpcs:ignore WordPress.Security.EscapeOutput
            wp_die();
        }
        // Placeholder: implemented in the premium version.
        echo '';
        wp_die();
    }

    /**
     * Delete widget
     *
     * @return void
     */
    public static function delete_widget() {
        $wp_nonce = ( isset( $_POST['wpda_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['wpda_wpnonce'] ) ) : '' );
        if ( !wp_verify_nonce( $wp_nonce, self::DASHBOARD_SAVE . WPDA::get_current_user_login() ) ) {
            WPDA::sent_header( 'application/json' );
            echo static::msg( 'ERROR', 'Token expired, please refresh page' );
            // phpcs:ignore WordPress.Security.EscapeOutput
            wp_die();
        }
        // Placeholder: implemented in the premium version.
        echo '';
        wp_die();
    }

    /**
     * Edit chart
     *
     * @return void
     */
    public static function edit_chart() {
        $wp_nonce = ( isset( $_POST['wpda_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['wpda_wpnonce'] ) ) : '' );
        if ( !wp_verify_nonce( $wp_nonce, WPDA_Widget::WIDGET_REFRESH . WPDA::get_current_user_login() ) ) {
            WPDA::sent_header( 'application/json' );
            echo static::msg( 'ERROR', 'Token expired, please refresh page' );
            // phpcs:ignore WordPress.Security.EscapeOutput
            wp_die();
        }
        // Placeholder: implemented in the premium version.
        echo '';
        wp_die();
    }

    /**
     * Load widget via ajax
     *
     * @return void
     */
    public static function load_widget() {
        $wp_nonce = ( isset( $_POST['wpda_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['wpda_wpnonce'] ) ) : '' );
        if ( !wp_verify_nonce( $wp_nonce, WPDA_Widget::WIDGET_REFRESH . WPDA::get_current_user_login() ) ) {
            WPDA::sent_header( 'application/json' );
            echo static::msg( 'ERROR', 'Token expired, please refresh page' );
            // phpcs:ignore WordPress.Security.EscapeOutput
            wp_die();
        }
        // Placeholder: implemented in the premium version.
        echo '';
        wp_die();
    }

    /**
     * Construct tab name
     *
     * @param string $tab_name Original tab name.
     * @return string
     */
    public static function get_tab_name( $tab_name ) {
        return '_' . str_replace( ' ', '_', $tab_name );
    }

    /**
     * Permanently remove plugin info message from dashboard
     *
     * @return void
     */
    public static function remove_new_dashboard_message() {
        WPDA::sent_header( 'text/html; charset=UTF-8' );
        $wp_nonce = ( isset( $_POST['wp_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['wp_nonce'] ) ) : '' );
        if ( wp_verify_nonce( $wp_nonce, self::DASHBOARD_SAVE . WPDA::get_current_user_login() ) ) {
            // Permanently remove message from dashboard toolbar.
            update_user_meta( WPDA::get_current_user_id(), self::USER_NEW_MESSAGE, 'removed' );
            echo 'OK';
        } else {
            echo 'FAILED';
        }
        wp_die();
    }

    /**
     * Construct ajax message
     *
     * @param string $status Status.
     * @param string $msg Message.
     * @return false|string
     */
    public static function msg( $status, $msg ) {
        $error = array(
            'status' => $status,
            'msg'    => $msg,
        );
        return json_encode( $error );
        // phpcs:ignore
    }

    private function get_promotions( $tool ) {
        switch ( $tool ) {
            case 'publisher':
            case 'projects':
            case 'templates':
            case 'charts':
                $promotions = array(array(
                    'This tool will be transitioned to the new App Builder. Please migrate on time.' => array(null, 'fa-lightbulb'),
                ));
                break;
            default:
                $promotions = array();
        }
        if ( count( $promotions ) > 0 ) {
            //phpcs:ignore - 8.1 proof
            $promotion_index = random_int( 0, count( $promotions ) - 1 );
            $promotion = $promotions[$promotion_index];
            $promotion_text = key( $promotion );
            $promotion_url = $promotion[$promotion_text][0];
            $promotion_icon = $promotion[$promotion_text][1];
            ?>
				<div class="wpda-promotion">
					<span><i class="fas <?php 
            echo esc_attr( $promotion_icon );
            ?>"></i></span>
					<?php 
            echo esc_attr( $promotion_text );
            ?>
					<?php 
            if ( null !== $promotion_url ) {
                ?>
						<a href="<?php 
                echo esc_url( $promotion_url );
                ?>" target="_blank">Read more...</a>
						<?php 
            }
            ?>
				</div>
				<?php 
        }
    }

}
