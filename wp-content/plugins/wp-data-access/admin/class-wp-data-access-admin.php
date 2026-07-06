<?php

/**
 * Suppress "error - 0 - No summary was found for this file" on phpdoc generation
 *
 * @package plugin\admin
 */
use WPDataAccess\API\WPDA_API;
use WPDataAccess\Backup\WPDA_Data_Export;
use WPDataAccess\CSV_Files\WPDA_CSV_Import;
use WPDataAccess\Dashboard\WPDA_Dashboard;
use WPDataAccess\Data_Apps\WPDA_App_Builder;
use WPDataAccess\Data_Apps\WPDA_Data_Explorer;
use WPDataAccess\Global_Search\WPDA_Global_Search;
use WPDataAccess\List_Table\WPDA_List_View;
use WPDataAccess\Plugin_Table_Models\WPDA_App_Model;
use WPDataAccess\Plugin_Table_Models\WPDA_Design_Table_Model;
use WPDataAccess\Plugin_Table_Models\WPDA_Publisher_Model;
use WPDataAccess\Plugin_Table_Models\WPDA_User_Menus_Model;
use WPDataAccess\Premium\WPDAPRO_Data_Publisher\WPDAPRO_Data_Publisher_Manage_Styles;
use WPDataAccess\Settings\WPDA_Settings;
use WPDataAccess\Utilities\WPDA_Add_App_To_Menu;
use WPDataAccess\Utilities\WPDA_Repository;
use WPDataAccess\WPDA;
use WPDataAccess\WPDA_Navi\WPDA_Navi;
use WPDataProjects\WPDP;
/**
 * Class WP_Data_Access_Admin
 *
 * Defines admin specific functionality for plugin WP Data Access.
 *
 * @author  Peter Schulz
 * @since   1.0.0
 */
class WP_Data_Access_Admin {
    /**
     * Menu slug for main page
     */
    const PAGE_MAIN = 'wpda';

    /**
     * Menu slug for navigator page
     */
    const PAGE_NAVI = 'wpda_navi';

    /**
     * Menu slug for apps page
     */
    const PAGE_APPS = 'wpda_apps';

    /**
     * Menu slug for dashboard page
     */
    const PAGE_DASHBOARD = 'wpda_dashboard';

    /**
     * Menu slug for setting page
     */
    const PAGE_SETTINGS = 'wpdataaccess';

    /**
     * Menu slug for explorer page
     */
    const PAGE_EXPLORER = 'wpda_explorer';

    /**
     * Menu slug for query builder page
     */
    const PAGE_QUERY_BUILDER = 'wpda_query_builder';

    /**
     * Menu slug for Data Tables page
     */
    const PAGE_PUBLISHER = 'wpda_publisher';

    /**
     * Menu slug for charts page
     */
    const PAGE_CHARTS = 'wpda_charts';

    /**
     * Menu slug for designer page
     */
    const PAGE_DESIGNER = 'wpda_designer';

    /**
     * Plugin main page slug
     */
    const MAIN_PAGE_SLUG = self::PAGE_NAVI;

    /**
     * Menu slug for my tables page
     */
    const PAGE_MY_TABLES = 'wpda_my_tables';

    /**
     * Page hook suffix to Data Explorer page or false
     *
     * @var string|false
     */
    protected $wpda_data_explorer_menu;

    /**
     * Page hook suffix to Data Designer page or false
     *
     * @var string|false
     */
    protected $wpda_data_designer_menu;

    /**
     * Page hook suffix to Data Tables page or false
     *
     * @var string|false
     */
    protected $wpda_data_publisher_menu;

    /**
     * Page hook suffix to Charts page or false
     *
     * @var string|false
     */
    protected $wpda_charts_menu;

    /**
     * Reference to list view for Data Explorer page
     *
     * @var WPDA_List_View
     */
    protected $wpda_data_explorer_view;

    /**
     * Reference to list view for Data Designer page
     *
     * @var WPDA_List_View
     */
    protected $wpda_data_designer_view;

    /**
     * Reference to list view for Data Tables page
     *
     * @var WPDA_List_View
     */
    protected $wpda_data_publisher_view;

    /**
     * Reference to list view for Charts page
     *
     * @var WPDA_List_View
     */
    protected $wpda_charts_view;

    /**
     * Array of page hook suffixes to user defined sub menus
     *
     * @var array
     */
    protected $wpda_my_table_list_menu = array();

    /**
     * Array of list view for user defined sub menus
     *
     * @var array
     */
    protected $wpda_my_table_list_view = array();

    /**
     * Page hook suffix help page or false
     *
     * @var string|false
     */
    protected $wpda_help;

    /**
     * Menu slug or null
     *
     * @var null
     */
    protected $page = null;

    /**
     * Status loading indicator
     *
     * @var bool
     */
    protected $loaded_user_main_menu = false;

    /**
     * Main menu page
     *
     * @var null
     */
    protected $first_page = null;

    /**
     * WP_Data_Access_Admin constructor
     *
     * @since   1.0.0
     */
    public function __construct() {
        if ( isset( $_REQUEST['page'] ) ) {
            // phpcs:ignore WordPress.Security.NonceVerification
            $this->page = sanitize_text_field( wp_unslash( $_REQUEST['page'] ) );
            // phpcs:ignore WordPress.Security.NonceVerification
        }
    }

    private function load_app_css() {
        // WPDataAccess apps CSS.
        wp_register_style(
            'wpda_apps',
            plugins_url( '../assets/css/wpda_apps.css', __FILE__ ),
            array(),
            WPDA::get_option( WPDA::OPTION_WPDA_VERSION )
        );
    }

    /**
     * Add stylesheets to back-end
     *
     * The following stylesheets are added:
     * + Plugin stylesheet
     * + Visual editor stylesheet
     *
     * The plugin stylesheet is used to style the setting forms {@see WPDA_Settings}, simple forms
     * {@see \WPDataAccess\Simple_Form\WPDA_Simple_Form}.
     *
     * @since   1.0.0
     *
     * @see WPDA_Settings
     * @see \WPDataAccess\Simple_Form\WPDA_Simple_Form
     * @see WP_Data_Access_Public
     */
    public function enqueue_styles() {
        if ( !WPDA::is_plugin_page( $this->page ) ) {
            // Admin styles are only added to plugin admin pages.
            // App pages still require the apps.css file.
            $apps = WPDA_App_Model::add_to_dashboard_menu();
            $css_added = false;
            for ($i = 0; $i < count( $apps ) && !$css_added; $i++) {
                $settings = json_decode( $apps[$i]['app_settings'], true );
                if ( isset( $settings['settings']['app_menu_title'] ) && $this->page === $settings['settings']['app_menu_title'] ) {
                    $this->load_app_css();
                    $css_added = true;
                }
            }
            return;
        }
        if ( self::PAGE_NAVI === $this->page ) {
            // WPDataAccess CSS.
            wp_enqueue_style(
                'wpdanavi',
                plugins_url( '../assets/css/wpda_navi.css', __FILE__ ),
                array(),
                WPDA::get_option( WPDA::OPTION_WPDA_VERSION )
            );
        }
        wp_enqueue_style( 'wp-jquery-ui-core' );
        wp_enqueue_style( 'wp-jquery-ui-dialog' );
        wp_enqueue_style( 'wp-jquery-ui-sortable' );
        wp_enqueue_style( 'wp-jquery-ui-tabs' );
        $this->load_app_css();
        // WPDataAccess CSS.
        wp_enqueue_style(
            'wpdataaccess',
            plugins_url( '../assets/css/wpda_style.css', __FILE__ ),
            array(),
            WPDA::get_option( WPDA::OPTION_WPDA_VERSION )
        );
        // WPDataAccess dashboard.
        wp_register_style(
            'wpdataaccess_dashboard',
            plugins_url( '../assets/css/wpda_dashboard.css', __FILE__ ),
            array(),
            WPDA::get_option( WPDA::OPTION_WPDA_VERSION )
        );
        // Add WP Data Projects stylesheet.
        wp_enqueue_style(
            'wpdataprojects',
            plugins_url( '../WPDataProjects/assets/css/wpdp_style.css', __FILE__ ),
            array(),
            WPDA::get_option( WPDA::OPTION_WPDA_VERSION )
        );
        // Register datetimepicker external library.
        wp_register_style(
            'datetimepicker',
            plugins_url( '../assets/css/jquery.datetimepicker.min.css', __FILE__ ),
            array(),
            WPDA::get_option( WPDA::OPTION_WPDA_VERSION )
        );
        // Register JQuery DataTables to use data tables in the dashboard.
        wp_register_style(
            'jquery_datatables',
            plugins_url( '../assets/css/jquery.dataTables.min.css', __FILE__ ),
            array(),
            WPDA::get_option( WPDA::OPTION_WPDA_VERSION )
        );
        // Register JQuery DataTables Responsive to use data tables in the dashboard.
        wp_register_style(
            'jquery_datatables_responsive',
            plugins_url( '../assets/css/responsive.dataTables.min.css', __FILE__ ),
            array(),
            WPDA::get_option( WPDA::OPTION_WPDA_VERSION )
        );
        if ( self::PAGE_MAIN === $this->page || self::PAGE_APPS === $this->page || self::PAGE_DASHBOARD === $this->page || self::PAGE_QUERY_BUILDER === $this->page || self::PAGE_PUBLISHER === $this->page || self::PAGE_CHARTS === $this->page ) {
            // Load UI smoothness theme.
            wp_enqueue_style(
                'wpda_ui_smoothness',
                plugins_url( '../assets/css/jquery-ui.smoothness.min.css', __FILE__ ),
                array(),
                WPDA::get_option( WPDA::OPTION_WPDA_VERSION )
            );
        } else {
            // Load UI darkness theme.
            wp_enqueue_style(
                'wpda_ui_darkness',
                plugins_url( '../assets/css/jquery-ui.min.css', __FILE__ ),
                array(),
                WPDA::get_option( WPDA::OPTION_WPDA_VERSION )
            );
        }
        $hide_button_icons = WPDA::get_option( WPDA::OPTION_BE_HIDE_BUTTON_ICONS );
        if ( false !== $hide_button_icons && 'on' !== $hide_button_icons ) {
            // SAVING SPACE - According to the plugin guidelines it is allowed to include external fonts:
            // https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/#8-plugins-may-not-send-executable-code-via-third-party-systems .
            // Load fontawesome icons.
            wp_enqueue_style(
                // phpcs:ignore WordPress.WP.EnqueuedResourceParameters
                'wpda_fontawesome_icons',
                WPDA::CDN_FONTAWESOME . 'all.min.css',
                array(),
                null,
                false
            );
        }
        if ( self::PAGE_PUBLISHER === $this->page ) {
            wp_register_style(
                'wpda_datatables_default',
                plugins_url( '../assets/css/wpda_datatables_default.css', __FILE__ ),
                array(),
                WPDA::get_option( WPDA::OPTION_WPDA_VERSION )
            );
            // Add jQuery multiselect resources.
            wp_enqueue_style(
                'wpda_jquery_multiselect',
                plugins_url( '../assets/css/jquery.multiselect.sortable.js.css', __FILE__ ),
                array(),
                WPDA::get_option( WPDA::OPTION_WPDA_VERSION ),
                false
            );
        }
        if ( self::PAGE_QUERY_BUILDER === $this->page ) {
            // Add Query Builder resources.
            wp_enqueue_style(
                'wpda_query_builder',
                plugins_url( '../assets/css/wpda_query_builder.css', __FILE__ ),
                array(),
                WPDA::get_option( WPDA::OPTION_WPDA_VERSION )
            );
            wp_enqueue_style(
                'wpda_jquery_json_viewer',
                plugins_url( '../assets/css/jquery.json-viewer.css', __FILE__ ),
                array(),
                WPDA::get_option( WPDA::OPTION_WPDA_VERSION )
            );
        }
        if ( !WPDA::current_user_is_admin() ) {
            wp_enqueue_style(
                'wpda_non_admin',
                plugins_url( '../assets/css/wpda_non_admin.css', __FILE__ ),
                array(),
                WPDA::get_option( WPDA::OPTION_WPDA_VERSION )
            );
        }
    }

    /**
     * Remove icons from buttons (if configured)
     *
     * @return void
     */
    public function remove_icons() {
        if ( !WPDA::is_plugin_page( $this->page ) ) {
            // Only executed on plugin pages.
            return;
        }
        if ( 'on' === WPDA::get_option( WPDA::OPTION_BE_HIDE_BUTTON_ICONS ) ) {
            echo '
				<style>
					i.fas.wpda_icon_on_button {
						display: none;
					}
				</style>
			';
        }
    }

    /**
     * Add scripts to back-end
     *
     * @since   1.0.0
     *
     * @see WP_Data_Access_Public
     */
    public function enqueue_scripts() {
        if ( !WPDA::is_plugin_page( $this->page ) ) {
            // Admin styles are only added to plugin admin pages.
            return;
        }
        wp_enqueue_script( 'jquery-ui-core' );
        wp_enqueue_script( 'jquery-ui-dialog' );
        wp_enqueue_script( 'jquery-ui-tabs' );
        wp_enqueue_script( 'jquery-ui-sortable' );
        wp_enqueue_script( 'jquery-ui-tooltip' );
        wp_enqueue_script( 'jquery-ui-autocomplete' );
        // Register wpda rest api.
        wp_enqueue_script(
            'wpda_rest_api',
            plugins_url( '../assets/js/wpda_rest_api.js', __FILE__ ),
            array('wp-api'),
            WPDA::get_option( WPDA::OPTION_WPDA_VERSION ),
            false
        );
        wp_localize_script( 'wpda_rest_api', 'wpdaApiSettings', array(
            'path' => WPDA_API::WPDA_NAMESPACE,
        ) );
        // Register wpda admin functions.
        wp_enqueue_script(
            'wpda_admin_scripts',
            plugins_url( '../assets/js/wpda_admin.js', __FILE__ ),
            array(),
            WPDA::get_option( WPDA::OPTION_WPDA_VERSION ),
            false
        );
        wp_localize_script( 'wpda_admin_scripts', 'wpda_admin_vars', array(
            'wpda_ajaxurl' => admin_url( 'admin-ajax.php' ),
        ) );
        // Register dashboard.
        wp_register_script(
            'wpdataaccess_dashboard',
            plugins_url( '../assets/js/wpda_dashboard.js', __FILE__ ),
            array(),
            WPDA::get_option( WPDA::OPTION_WPDA_VERSION ),
            false
        );
        wp_localize_script( 'wpdataaccess_dashboard', 'wpda_dashboard_vars', array(
            'wpda_ajaxurl' => admin_url( 'admin-ajax.php' ),
        ) );
        // Add WP Data Projects JS functions.RESEARCH.
        wp_enqueue_script(
            'wpdataprojects',
            plugins_url( '../WPDataProjects/assets/js/wpdp_admin.js', __FILE__ ),
            array(),
            WPDA::get_option( WPDA::OPTION_WPDA_VERSION ),
            false
        );
        // Register jQuery DataTables to use data tables in the dashboard.
        wp_register_script(
            'jquery_datatables',
            plugins_url( '../assets/js/jquery.dataTables.min.js', __FILE__ ),
            array(),
            WPDA::get_option( WPDA::OPTION_WPDA_VERSION ),
            false
        );
        // Register jQuery DataTables Responsive to use data tables in the dashboard.
        wp_register_script(
            'jquery_datatables_responsive',
            plugins_url( '../assets/js/dataTables.responsive.min.js', __FILE__ ),
            array(),
            WPDA::get_option( WPDA::OPTION_WPDA_VERSION ),
            false
        );
        // Ajax call to WPDA datatables implementation to use data tables in the dashboard.
        wp_register_script(
            'wpda_datatables',
            plugins_url( '../assets/js/wpda_datatables.js', __FILE__ ),
            array(),
            WPDA::get_option( WPDA::OPTION_WPDA_VERSION ),
            false
        );
        wp_localize_script( 'wpda_datatables', 'wpda_publication_vars', array(
            'wpda_ajaxurl' => admin_url( 'admin-ajax.php' ),
        ) );
        // Register notify external library.
        wp_enqueue_script(
            'wpda_notify',
            plugins_url( '../assets/js/notify.min.js', __FILE__ ),
            array(),
            WPDA::get_option( WPDA::OPTION_WPDA_VERSION ),
            false
        );
        // Register datetimepicker external library.
        wp_register_script(
            'datetimepicker',
            plugins_url( '../assets/js/jquery.datetimepicker.full.min.js', __FILE__ ),
            array(),
            WPDA::get_option( WPDA::OPTION_WPDA_VERSION ),
            false
        );
        // Register clipboard.js.
        wp_enqueue_script( 'clipboard' );
        if ( self::PAGE_PUBLISHER === $this->page ) {
            if ( isset( $_REQUEST['action'] ) && in_array( $_REQUEST['action'], array('view', 'new', 'edit'), true ) ) {
                $json_editing = WPDA::get_option( WPDA::OPTION_DP_JSON_EDITING );
                if ( WPDA::OPTION_DP_JSON_EDITING[1] === $json_editing ) {
                    // Register codeEditor to support JSON editing in Data Tables (table options advanced).
                    $cm_settings['codeEditor'] = wp_enqueue_code_editor( array(
                        'type'       => 'application/json',
                        'codemirror' => array(
                            'autoRefresh' => true,
                        ),
                    ) );
                    wp_localize_script( 'wp-theme-plugin-editor', 'cm_settings', $cm_settings );
                    wp_enqueue_script( 'wp-theme-plugin-editor' );
                }
                // Add jQuery multiselect resources.
                wp_enqueue_script(
                    'wpda_jquery_multiselect',
                    plugins_url( '../assets/js/jquery.multiselect.sortable.js', __FILE__ ),
                    array(),
                    WPDA::get_option( WPDA::OPTION_WPDA_VERSION ),
                    false
                );
            }
        }
        if ( self::PAGE_CHARTS === $this->page ) {
            $this->load_google_charts();
        }
        if ( self::PAGE_QUERY_BUILDER === $this->page ) {
            // Add Query Builder resources.
            wp_enqueue_script(
                'wpda_query_builder',
                plugins_url( '../assets/js/wpda_query_builder.js', __FILE__ ),
                array(),
                WPDA::get_option( WPDA::OPTION_WPDA_VERSION ),
                false
            );
            wp_enqueue_script(
                'wpda_jquery_xml2json',
                plugins_url( '../assets/js/jquery.xml2json.js', __FILE__ ),
                array(),
                WPDA::get_option( WPDA::OPTION_WPDA_VERSION ),
                false
            );
            wp_enqueue_script(
                'wpda_jquery_json_viewer',
                plugins_url( '../assets/js/jquery.json-viewer.js', __FILE__ ),
                array(),
                WPDA::get_option( WPDA::OPTION_WPDA_VERSION ),
                false
            );
            // Add codeEditor to query builder.
            $cm_settings['codeEditor'] = wp_enqueue_code_editor( array(
                'type'       => 'text/x-sql',
                'codemirror' => array(
                    'mode'            => 'sql',
                    'lineNumbers'     => true,
                    'autoRefresh'     => true,
                    'lineWrapping'    => true,
                    'styleActiveLine' => true,
                ),
            ) );
            wp_enqueue_script( 'wp-theme-plugin-editor' );
            wp_localize_script( 'wp-theme-plugin-editor', 'cm_settings', $cm_settings );
            wp_enqueue_style( 'wp-codemirror' );
        }
        if ( self::PAGE_DASHBOARD === $this->page ) {
            $this->load_google_charts();
            // Load DBMS panels.
            wp_enqueue_script(
                'wpda_dbms',
                plugins_url( '../assets/js/wpda_dbms.js', __FILE__ ),
                array(),
                WPDA::get_option( WPDA::OPTION_WPDA_VERSION ),
                false
            );
            wp_localize_script( 'wpda_dbms', 'wpda_dbms_vars', array(
                'wpda_ajaxurl' => admin_url( 'admin-ajax.php' ),
            ) );
        }
        wp_enqueue_media();
    }

    /**
     * Load Google Charts resources
     *
     * @return void
     */
    private function load_google_charts() {
        // Load Google Charts.
        wp_enqueue_script(
            'wpda_google_charts',
            WPDA::GOOGLE_CHARTS,
            array(),
            null,
            false
        );
        wp_enqueue_script(
            'wpda_google_charts_fnc',
            plugins_url( '../assets/js/wpda_google_charts.js', __FILE__ ),
            array(),
            WPDA::get_option( WPDA::OPTION_WPDA_VERSION ),
            false
        );
        wp_localize_script( 'wpda_google_charts_fnc', 'wpda_chart_vars', array(
            'wpda_ajaxurl'  => admin_url( 'admin-ajax.php' ),
            'wpda_chartdir' => plugin_dir_url( __FILE__ ) . '../assets/images/google_chart_types/',
            'wpda_premium'  => ( wpda_freemius()->can_use_premium_code__premium_only() ? 'true' : 'false' ),
        ) );
    }

    /**
     * Hide admin notices
     *
     * @return void
     */
    public function user_admin_notices() {
        if ( WPDA::is_plugin_page( $this->page ) && 'on' === WPDA::get_option( WPDA::OPTION_PLUGIN_HIDE_NOTICES ) ) {
            remove_all_actions( 'admin_notices' );
            remove_all_actions( 'all_admin_notices' );
        }
    }

    /**
     * Add plugin menu and sub menus
     *
     * Adds the following menu and sub menus to the back-end menu:
     * + WP Data Access
     *   + Data Explorer
     *   + Data Designer
     *   + Data Projects
     *   + Manage Plugin
     *
     * @since   1.0.0
     */
    public function add_menu_items() {
        if ( WPDA::current_user_is_admin() ) {
            if ( 'on' === WPDA::get_option( WPDA::OPTION_PLUGIN_HIDE_ADMIN_MENU ) ) {
                // Show Data Projects.
                $this->add_data_projects();
                // Hide admin menu.
                return;
            }
            if ( self::PAGE_MAIN === $this->page && isset( $_REQUEST['de'] ) ) {
                if ( 'new' === $_REQUEST['de'] ) {
                    update_user_meta( WPDA::get_current_user_id(), 'wpda_data_explorer', 'new' );
                } elseif ( 'old' === $_REQUEST['de'] ) {
                    update_user_meta( WPDA::get_current_user_id(), 'wpda_data_explorer', 'old' );
                }
            }
            if ( self::PAGE_QUERY_BUILDER === $this->page && isset( $_REQUEST['qb'] ) ) {
                if ( 'new' === $_REQUEST['qb'] ) {
                    update_user_meta( WPDA::get_current_user_id(), 'wpda_query_builder_version', 'new' );
                } elseif ( 'old' === $_REQUEST['qb'] ) {
                    update_user_meta( WPDA::get_current_user_id(), 'wpda_query_builder_version', 'old' );
                }
            }
            // Specific list tables (and forms) can be made available for specific capabilities:
            // managed in method add_menu_my_tables.
            // Main menu and items are only available to admin users (set capability to 'manage_options').
            add_menu_page(
                'WP Data Access',
                'WP Data Access',
                'manage_options',
                self::MAIN_PAGE_SLUG,
                null,
                'dashicons-database',
                999999999
            );
            // Add navigator page to WPDA menu.
            add_submenu_page(
                self::MAIN_PAGE_SLUG,
                'WP Data Access',
                'Tool Guide',
                'manage_options',
                self::PAGE_NAVI,
                array($this, 'data_navi')
            );
            // Add apps to menu.
            add_submenu_page(
                self::MAIN_PAGE_SLUG,
                'WP Data Access',
                'App Builder',
                'manage_options',
                self::PAGE_APPS,
                array($this, 'data_apps')
            );
            // Add data explorer to WPDA menu.
            $current_data_explorer_version = get_user_meta( WPDA::get_current_user_id(), 'wpda_data_explorer', true );
            if ( isset( $_POST['explorer'] ) && 'OLD' === $_POST['explorer'] ) {
                $current_data_explorer_version = true;
            }
            $this->wpda_data_explorer_menu = add_submenu_page(
                self::MAIN_PAGE_SLUG,
                'WP Data Access',
                'Data Explorer',
                'manage_options',
                self::PAGE_MAIN,
                array($this, ( 'new' !== $current_data_explorer_version && '' !== $current_data_explorer_version ? 'data_explorer_page' : 'data_explorer_page_new' ))
            );
            if ( self::PAGE_MAIN === $this->page ) {
                if ( 'new' !== $current_data_explorer_version && '' !== $current_data_explorer_version ) {
                    $args = array(
                        'page_hook_suffix' => $this->wpda_data_explorer_menu,
                    );
                    $this->wpda_data_explorer_view = new WPDA_List_View($args);
                }
            }
            // Add submenu for Query Builder.
            $current_query_builder_version = get_user_meta( WPDA::get_current_user_id(), 'wpda_query_builder_version', true );
            $this->wpda_data_publisher_menu = add_submenu_page(
                self::MAIN_PAGE_SLUG,
                'WP Data Access',
                'SQL Query Builder',
                'manage_options',
                self::PAGE_QUERY_BUILDER,
                array($this, ( $current_query_builder_version === 'old' ? 'query_builder_old' : 'query_builder' ))
            );
            // Add submenu for Data Tables.
            $this->wpda_data_publisher_menu = add_submenu_page(
                self::MAIN_PAGE_SLUG,
                'WP Data Access',
                'Table Builder',
                'manage_options',
                self::PAGE_PUBLISHER,
                array($this, 'data_publisher_page')
            );
            if ( self::PAGE_PUBLISHER === $this->page ) {
                $this->wpda_data_publisher_view = new WPDA_List_View(array(
                    'page_hook_suffix' => $this->wpda_data_publisher_menu,
                    'table_name'       => WPDA_Publisher_Model::get_base_table_name(),
                    'list_table_class' => 'WPDataAccess\\Data_Publisher\\WPDA_Publisher_List_Table',
                    'edit_form_class'  => 'WPDataAccess\\Data_Publisher\\WPDA_Publisher_Form',
                ));
            }
            // Add Data Projects menu.
            $wpdp = new WPDP(self::MAIN_PAGE_SLUG);
            $wpdp->add_menu_items();
            // Add data designer to WPDA menu.
            $this->wpda_data_designer_menu = add_submenu_page(
                self::MAIN_PAGE_SLUG,
                'WP Data Access',
                'Data Designer',
                'manage_options',
                self::PAGE_DESIGNER,
                array($this, 'data_designer_page')
            );
            if ( self::PAGE_DESIGNER === $this->page ) {
                $this->wpda_data_designer_view = new WPDA_List_View(array(
                    'page_hook_suffix' => $this->wpda_data_designer_menu,
                    'table_name'       => WPDA_Design_Table_Model::get_base_table_name(),
                    'list_table_class' => 'WPDataAccess\\Design_Table\\WPDA_Design_Table_List_Table',
                    'edit_form_class'  => 'WPDataAccess\\Design_Table\\WPDA_Design_Table_Form',
                    'subtitle'         => '',
                ));
            }
        } else {
            $this->grant_access_to_dashboard();
            $this->grant_access_to_data_publications();
        }
        $this->add_data_projects();
        $this->add_apps();
    }

    /**
     * Remove plugin sub menu items (when in dashboard mode)
     *
     * @param mixed $submenu_file Dashboard menu items.
     * @return mixed
     */
    public function wpda_submenu_filter( $submenu_file ) {
        if ( WPDA::current_user_is_admin() ) {
            $hidden_submenus = array(
                self::PAGE_DASHBOARD,
                self::PAGE_CHARTS,
                self::PAGE_PUBLISHER,
                WPDP::PAGE_MAIN,
                self::PAGE_DESIGNER,
                WPDP::PAGE_TEMPLATES,
                self::MAIN_PAGE_SLUG . '-account',
                self::MAIN_PAGE_SLUG . '-wp-support-forum'
            );
            foreach ( $hidden_submenus as $submenu ) {
                remove_submenu_page( self::MAIN_PAGE_SLUG, $submenu );
            }
        } else {
            global $submenu;
            $submenu[self::PAGE_MAIN][0][2] = self::PAGE_DASHBOARD;
            // phpcs:ignore WordPress.WP.GlobalVariablesOverride
        }
        return $submenu_file;
    }

    /**
     * Add apps to dashboard menu.
     *
     * @return void
     */
    protected function add_apps() {
        // Add apps to dashbaord menu
        $apps = new WPDA_Add_App_To_Menu();
        $apps->add_apps_to_menu();
    }

    /**
     * Add Data Projects and Project Templates to plugin navigation
     *
     * @return void
     */
    protected function add_data_projects() {
        // Add Data Projects.
        $wpdp = new WPDP();
        $wpdp->add_projects();
    }

    /**
     * Allow authorized users to access WP Data Access dashboard
     *
     * @return void
     */
    protected function grant_access_to_dashboard() {
        // Check user role.
        $user_roles = WPDA::get_current_user_roles();
        if ( false === $user_roles || !is_array( $user_roles ) ) {
            // Cannot determine the user roles (not able to show menus).
            return;
        }
        // Check dashboard role access.
        $dashboard_roles = get_option( \WPDataAccess\Settings\WPDA_Settings_Legacy_Dashboard::DASHBOARD_ROLES );
        $user_has_role = false;
        foreach ( $user_roles as $user_role ) {
            if ( false !== strpos( $dashboard_roles, $user_role ) ) {
                $user_has_role = true;
                break;
            }
        }
        // Check dashboard user access.
        $dashboard_users = get_option( \WPDataAccess\Settings\WPDA_Settings_Legacy_Dashboard::DASHBOARD_USERS );
        $user_has_access = false !== strpos( $dashboard_users, WPDA::get_current_user_login() );
        if ( !$user_has_role && !$user_has_access ) {
            return;
        }
        // User has dashboard access: add menu item.
        $this->create_non_admin_menu( self::PAGE_DASHBOARD );
        add_submenu_page(
            $this->first_page,
            'Dashboard',
            'Dashboard',
            WPDA::get_current_user_capability(),
            self::PAGE_DASHBOARD,
            array($this, 'data_dashboard_page')
        );
    }

    /**
     * Allow authorized users to access Data Tables
     *
     * @return void
     */
    protected function grant_access_to_data_publications() {
        // Check user role.
        $user_roles = WPDA::get_current_user_roles();
        if ( false === $user_roles || !is_array( $user_roles ) ) {
            // Cannot determine the user roles (not able to show menus).
            return;
        }
        $publication_roles = WPDA::get_option( WPDA::OPTION_DP_PUBLICATION_ROLES );
        if ( '' === $publication_roles || 'administrator' === $publication_roles ) {
            // No access.
            return;
        }
        $user_has_role = false;
        foreach ( $user_roles as $user_role ) {
            if ( false !== stripos( $publication_roles, $user_role ) ) {
                $user_has_role = true;
            }
        }
        if ( !$user_has_role ) {
            // No access.
            return;
        }
        // Grant access to main menu.
        $this->create_non_admin_menu( self::PAGE_PUBLISHER );
        // Add submenu for Data Tables.
        $this->wpda_data_publisher_menu = add_submenu_page(
            $this->first_page,
            'WP Data Access',
            'Data Tables',
            WPDA::get_current_user_capability(),
            self::PAGE_PUBLISHER,
            array($this, 'data_publisher_page')
        );
        if ( self::PAGE_PUBLISHER === $this->page ) {
            global $wpdb;
            $this->wpda_data_publisher_view = new WPDA_List_View(array(
                'page_hook_suffix' => $this->wpda_data_publisher_menu,
                'table_name'       => $wpdb->prefix . 'wpda_publisher',
                'list_table_class' => 'WPDataAccess\\Data_Publisher\\WPDA_Publisher_List_Table',
                'edit_form_class'  => 'WPDataAccess\\Data_Publisher\\WPDA_Publisher_Form',
            ));
        }
    }

    /**
     * Add dashboard page
     *
     * @return void
     */
    public function data_dashboard_page() {
        WPDA_Dashboard::add_dashboard( true );
    }

    /**
     * Show data explorer main page
     *
     * Initialization of $this->wpda_data_explorer_view is done earlier in
     * {@see WP_Data_Access_Admin::add_menu_items()} to support screen options. This method just shows the page
     * containing the list table.
     *
     * @since   1.0.0
     *
     * @see WP_Data_Access_Admin::add_menu_items()
     */
    public function data_explorer_page() {
        WPDA_Dashboard::add_dashboard();
        if ( isset( $_REQUEST['page_action'] ) && 'wpda_backup' === $_REQUEST['page_action'] ) {
            // phpcs:ignore WordPress.Security.NonceVerification
            $this->backup_page();
        } elseif ( isset( $_REQUEST['page_action'] ) && 'wpda_import_csv' === $_REQUEST['page_action'] ) {
            // phpcs:ignore WordPress.Security.NonceVerification
            $this->import_csv();
        } elseif ( isset( $_REQUEST['page_action'] ) && 'wpda_global_search' === $_REQUEST['page_action'] ) {
            // phpcs:ignore WordPress.Security.NonceVerification
            $this->advanced_search();
        } else {
            $this->wpda_data_explorer_view->show();
        }
    }

    public function data_explorer_page_new() {
        WPDA_Dashboard::add_dashboard();
        if ( isset( $_REQUEST['page_action'] ) && 'wpda_backup' === $_REQUEST['page_action'] ) {
            // phpcs:ignore WordPress.Security.NonceVerification
            $this->backup_page();
        } elseif ( isset( $_REQUEST['page_action'] ) && 'wpda_import_csv' === $_REQUEST['page_action'] ) {
            // phpcs:ignore WordPress.Security.NonceVerification
            $this->import_csv();
        } elseif ( isset( $_REQUEST['page_action'] ) && 'wpda_global_search' === $_REQUEST['page_action'] ) {
            // phpcs:ignore WordPress.Security.NonceVerification
            $this->advanced_search();
        } else {
            $explorer = new WPDA_Data_Explorer();
            $explorer->show();
        }
    }

    public function data_navi() {
        if ( isset( $_POST['wpda-legacy-tool-status'] ) ) {
            // Legacy tools update
            $update_status = json_decode( sanitize_text_field( wp_unslash( $_POST['wpda-legacy-tool-status'] ) ), true );
            if ( null !== $update_status ) {
                if ( isset( 
                    $update_status['tables'],
                    $update_status['forms'],
                    $update_status['templates'],
                    $update_status['designer'],
                    $update_status['dashboards'],
                    $update_status['charts']
                 ) ) {
                    // Get current legacy tool settings
                    $option_legacy_tools = \WPDataAccess\Utilities\WPDA_Legacy_Tool_Visibility::get();
                    // Update legacy tool settings
                    $option_legacy_tools['tables'][0] = $update_status['tables'];
                    $option_legacy_tools['forms'][0] = $update_status['forms'];
                    $option_legacy_tools['templates'][0] = $update_status['templates'];
                    $option_legacy_tools['designer'][0] = $update_status['designer'];
                    $option_legacy_tools['dashboards'][0] = $update_status['dashboards'];
                    $option_legacy_tools['charts'][0] = $update_status['charts'];
                    // Save legacy tool settings
                    WPDA::set_option( WPDA::OPTION_PLUGIN_LEGACY_TOOLS, $option_legacy_tools );
                }
            }
        }
        WPDA_Dashboard::add_dashboard();
        $navi = new WPDA_Navi();
        $navi->show();
    }

    public function data_apps() {
        WPDA_Dashboard::add_dashboard();
        $wpda_app_builder = new WPDA_App_Builder();
        $wpda_app_builder->show();
    }

    public function advanced_search() {
        $advanced_search = new WPDA_Global_Search();
        $advanced_search->show();
    }

    /**
     * Add Query Builder
     *
     * @return void
     */
    public function query_builder() {
        WPDA_Dashboard::add_dashboard();
        $query_builder = new \WPDataAccess\Data_Apps\WPDA_Query_Builder();
        $query_builder->show();
    }

    public function query_builder_old() {
        WPDA_Dashboard::add_dashboard();
        $query_builder = new \WPDataAccess\Query_Builder\WPDA_Query_Builder();
        $query_builder->show();
    }

    /**
     * Add CSV page
     *
     * @return void
     */
    public function import_csv() {
        $csv_import = new WPDA_CSV_Import();
        $csv_import->show();
    }

    /**
     * Show data designer main page
     *
     * Initialization of $this->wpda_data_designer_view is done earlier in
     * {@see WP_Data_Access_Admin::add_menu_items()} to support screen options. This method just shows the page
     * containing the list table.
     *
     * @since   1.0.0
     *
     * @see WP_Data_Access_Admin::add_menu_items()
     */
    public function data_designer_page() {
        WPDA_Dashboard::add_dashboard();
        $data_designer_table_found = WPDA_Design_Table_Model::table_exists();
        if ( $data_designer_table_found ) {
            $this->wpda_data_designer_view->show();
        } else {
            $this->data_designer_page_not_found();
        }
    }

    /**
     * Data Designer repository table not found
     */
    public function data_designer_page_not_found() {
        WPDA_Dashboard::add_dashboard();
        $wpda_repository = new WPDA_Repository();
        $wpda_repository->inform_user();
        ?>
		<div class="wrap">
			<h1 class="wp-heading-inline">
				<span>Data Designer</span>
				<a href="https://docs.legacy.wpdataaccess.com/docs/data-designer-getting-started/" target="_blank" class="wpda_tooltip" title="Plugin Help - opens in a new tab or window">
					<span class="dashicons dashicons-editor-help" style="text-decoration:none;vertical-align:top;font-size:30px;">
					</span>
				</a>
			</h1>
			<p>
				<?php 
        echo __( 'ERROR: Repository table not found!', 'wp-data-access' );
        // phpcs:ignore WordPress.Security.EscapeOutput
        ?>
			</p>
		</div>
		<?php 
    }

    /**
     * Show Data Tables main page
     */
    public function data_publisher_page() {
        if ( WPDA::current_user_is_admin() ) {
            WPDA_Dashboard::add_dashboard();
        }
        $data_publisher_table_found = WPDA_Publisher_Model::table_exists();
        if ( $data_publisher_table_found ) {
            $this->wpda_data_publisher_view->show();
        } else {
            $this->data_publisher_page_not_found();
        }
    }

    public function data_publisher_mngstl() {
        $mngstl = new WPDAPRO_Data_Publisher_Manage_Styles();
        $mngstl->show();
    }

    /**
     * Add Charts page
     *
     * @return void
     */
    public function data_charts_page() {
    }

    /**
     * Data Tables repository table not found
     */
    public function data_publisher_page_not_found() {
        if ( WPDA::current_user_is_admin() ) {
            WPDA_Dashboard::add_dashboard();
        }
        $wpda_repository = new WPDA_Repository();
        $wpda_repository->inform_user();
        ?>
		<div class="wrap">
			<h1 class="wp-heading-inline">
				<span>Data Tables</span>
				<a href="https://docs.legacy.wpdataaccess.com/docs/data-tables-getting-started/" target="_blank" class="wpda_tooltip" title="Plugin Help - opens in a new tab or window">
					<span class="dashicons dashicons-editor-help" style="text-decoration:none;vertical-align:top;font-size:30px;">
					</span>
				</a>
			</h1>
			<p>
				<?php 
        echo __( 'ERROR: Repository table not found!', 'wp-data-access' );
        // phpcs:ignore WordPress.Security.EscapeOutput
        ?>
			</p>
		</div>
		<?php 
    }

    /**
     * Show data backup main page
     *
     * Calls a page to create automatic backups (in fact data exports) and offers possibilities to restore (in fact
     * data imports).
     *
     * @since   2.0.6
     *
     * @see WPDA_Data_Export::show_wp_cron()
     */
    public function backup_page() {
        $wpda_backup = new WPDA_Data_Export();
        $wp_nonce = ( isset( $_REQUEST['wp_nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['wp_nonce'] ) ) : '?' );
        // phpcs:ignore WordPress.Security.NonceVerification
        if ( isset( $_REQUEST['action'] ) && wp_verify_nonce( $wp_nonce, 'wpda-backup-' . WPDA::get_current_user_login() ) ) {
            // phpcs:ignore WordPress.Security.NonceVerification
            $action = sanitize_text_field( wp_unslash( $_REQUEST['action'] ) );
            // phpcs:ignore WordPress.Security.NonceVerification
            if ( 'new' === $action ) {
                $wpda_backup->create_export( 'add' );
            } elseif ( 'add' === $action ) {
                $wpda_backup->wpda_add_cron_job();
            } elseif ( 'remove' === $action ) {
                $wpda_backup->wpda_remove_cron_job();
            } elseif ( 'edit' === $action ) {
                $wpda_backup->create_export( 'update' );
            } elseif ( 'update' === $action ) {
                $wpda_backup->wpda_update_cron_job();
            }
        } else {
            $wpda_backup->show_wp_cron();
        }
    }

    /**
     * Add user defined sub menu
     *
     * WPDA allows users to create sub menu for table lists and simple forms. Sub menus can be added to the WPDA
     * menu or any other (external) menu. A sub menu is added to an external menu via the menu slug. Sub menus are
     * taken from {@see WPDA_User_Menus_Model}.
     *
     * This method is called from the admin_menu action with a lower priority to make sure other menus are available.
     * User defined menu items are added to available menus in this method. These can be WPDA menus or external menus
     * as mentioned in the according list table and edit form. WPDA menus are added to menu WP Data Tables. External
     * menus are added to the menu having the menu slug defined by the user.
     *
     * This method does not actually show the list tables! It just creates the menu items. When the user clicks on such
     * a dynamically defined menu item, method {@see WP_Data_Access_Admin::my_tables_page()} is called, which takes
     * care of showing the list table.
     *
     * @since   1.0.0
     *
     * @see WP_Data_Access_Admin::my_tables_page()
     * @see WPDA_User_Menus_Model
     */
    public function add_menu_my_tables() {
        $menus_shown_to_current_user = array();
        // Add list tables to external menus.
        foreach ( WPDA_User_Menus_Model::list_external_menus() as $menu ) {
            $user_roles = WPDA::get_current_user_roles();
            $user_has_role = false;
            if ( '' === $menu->menu_role || null === $menu->menu_role ) {
                $user_has_role = in_array( 'administrator', $user_roles, true );
            } else {
                $user_role_array = explode( ',', (string) $menu->menu_role );
                //phpcs:ignore - 8.1 proof
                foreach ( $user_role_array as $user_role_array_item ) {
                    $user_has_role = in_array( $user_role_array_item, $user_roles, true );
                    //phpcs:ignore - 8.1 proof
                    if ( $user_has_role ) {
                        break;
                    }
                }
            }
            if ( $user_has_role ) {
                if ( !isset( $menus_shown_to_current_user[$menu->menu_slug . '/' . $menu->menu_name . '/' . $menu->menu_table_name . '/' . $menu->menu_schema_name] ) ) {
                    $menu_slug = self::PAGE_EXPLORER . '_' . $menu->menu_table_name;
                    $menu_index = $menu->menu_table_name;
                    $this->create_non_admin_menu( $menu_slug );
                    $this->wpda_my_table_list_menu[$menu_index] = add_submenu_page(
                        $this->first_page,
                        'WP Data Access : ' . strtoupper( $menu->menu_table_name ),
                        $menu->menu_name,
                        WPDA::get_current_user_capability(),
                        $menu_slug,
                        array($this, 'my_tables_page')
                    );
                    $this->wpda_my_table_list_view[$menu_index] = new WPDA_List_View(array(
                        'page_hook_suffix' => $this->wpda_my_table_list_menu[$menu_index],
                        'wpdaschema_name'  => $menu->menu_schema_name,
                        'table_name'       => $menu->menu_table_name,
                    ));
                    $menus_shown_to_current_user[$menu->menu_slug . '/' . $menu->menu_name . '/' . $menu_index . '/' . $menu->menu_schema_name] = true;
                }
            }
        }
    }

    /**
     * Show user defined menus
     *
     * A user defined menu that are added to the plugin menu in {@see WP_Data_Access_Admin::add_menu_my_tables()} is
     * shown here. This method is called when the user clicks on the menu item generated in
     * {@see WP_Data_Access_Admin::add_menu_my_tables()}.
     *
     * @since   1.0.0
     *
     * @see WP_Data_Access_Admin::add_menu_my_tables()
     */
    public function my_tables_page() {
        // Grab table name from menu slug.
        if ( null !== $this->page ) {
            if ( strpos( $this->page, self::PAGE_EXPLORER ) !== false ) {
                $table = substr( $this->page, strlen( self::PAGE_EXPLORER . '_' ) );
            } else {
                $table = substr( $this->page, strlen( self::PAGE_MY_TABLES . '_' ) );
            }
            // Show list table.
            $this->wpda_my_table_list_view[$table]->show();
        }
    }

    /**
     * Add main menu for non admin user
     *
     * @param string $first_page First plugin page.
     * @return void
     */
    protected function create_non_admin_menu( $first_page ) {
        if ( !$this->loaded_user_main_menu ) {
            add_menu_page(
                'WP Data Access',
                'WP Data Access',
                WPDA::get_current_user_capability(),
                $first_page,
                function () {
                },
                'dashicons-database-view',
                999999999
            );
            $this->loaded_user_main_menu = true;
            $this->first_page = $first_page;
        }
    }

}
