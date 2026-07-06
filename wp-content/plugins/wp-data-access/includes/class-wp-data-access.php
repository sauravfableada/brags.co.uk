<?php

/**
 * Suppress "error - 0 - No summary was found for this file" on phpdoc generation
 *
 * @package plugin\includes
 */
use WPDataAccess\Connection\WPDADB;
use WPDataAccess\Cookies\WPDA_Cookies;
use WPDataAccess\Data_Dictionary\WPDA_Dictionary_Lists;
use WPDataAccess\Data_Tables\WPDA_Data_Tables;
use WPDataAccess\Plugin_Table_Models\WPDA_Table_Settings_Model;
use WPDataAccess\Utilities\WPDA_Table_Actions;
use WPDataAccess\Utilities\WPDA_Export;
use WPDataAccess\Utilities\WPDA_Favourites;
use WPDataAccess\WPDA;
use WPDataProjects\Utilities\WPDP_Export_Project;
use WPDataAccess\Backup\WPDA_Data_Export;
use WPDataAccess\Plugin_Table_Models\WPDA_CSV_Uploads_Model;
use WPDataRoles\WPDA_Roles;
use WPDataAccess\Utilities\WPDA_Autocomplete;
use WPDataAccess\Premium\WPDAPRO_Geo_Location\WPDAPRO_Geo_Location_WS;
use WPDataAccess\Premium\WPDAPRO_Geo_Location\WPDAPRO_Geo_Location;
use WPDataAccess\Query_Builder\WPDA_Query_Builder;
use WPDataAccess\Dashboard\WPDA_Dashboard;
use WPDataAccess\Dashboard\WPDA_Widget_Code;
use WPDataAccess\Dashboard\WPDA_Widget_Publication;
use WPDataAccess\Dashboard\WPDA_Widget_Dbms;
use WPDataAccess\Dashboard\WPDA_Widget_Google_Chart;
use WPDataAccess\Premium\WPDAPRO_Dashboard\WPDAPRO_Widget_Project;
/**
 * Class WP_Data_Access
 *
 * Core plugin class used to define:
 * + admin specific functionality {@see WP_Data_Access_Admin}
 * + public specific functionality {@see WP_Data_Access_Public}
 * + internationalization {@see WP_Data_Access_I18n}
 * + plugin activation and deactivation {@see WP_Data_Access_Loader}
 *
 * @author  Peter Schulz
 * @since   1.0.0
 *
 * @see WP_Data_Access_Admin
 * @see WP_Data_Access_Public
 * @see WP_Data_Access_I18n
 * @see WP_Data_Access_Loader
 */
class WP_Data_Access {
    /**
     * Reference to plugin loader
     *
     * @var WP_Data_Access_Loader
     *
     * @since   1.0.0
     *
     * @see WP_Data_Access_Loader
     */
    protected $loader;

    /**
     * Menu slug or null
     *
     * @var null
     */
    protected $page = null;

    /**
     * WP_Data_Access constructor
     *
     * Calls method the following methods to setup plugin:
     * + {@see WP_Data_Access::load_dependencies()}
     * + {@see WP_Data_Access::set_locale()}
     * + {@see WP_Data_Access::define_admin_hooks()}
     * + {@see WP_Data_Access::define_public_hooks()}
     *
     * @since   1.0.0
     *
     * @see WP_Data_Access::load_dependencies()
     * @see WP_Data_Access::set_locale()
     * @see WP_Data_Access::define_admin_hooks()
     * @see WP_Data_Access::define_public_hooks()
     */
    public function __construct() {
        if ( isset( $_REQUEST['page'] ) ) {
            // phpcs:ignore WordPress.Security.NonceVerification
            $this->page = sanitize_text_field( wp_unslash( $_REQUEST['page'] ) );
            // phpcs:ignore WordPress.Security.NonceVerification
        }
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        // WP Data Access REST API.
        $this->api();
    }

    /**
     * Add WP Data Access JSON REST API
     *
     * @return void
     */
    private function api() {
        $api = new \WPDataAccess\API\WPDA_API();
        $this->loader->add_action( 'init', $api, 'hide' );
        $this->loader->add_action( 'rest_api_init', $api, 'init' );
    }

    /**
     * Load required dependencies
     *
     * Loads required plugin files and initiates the plugin loader.
     *
     * @since   1.0.0
     *
     * @see WP_Data_Access_Loader
     */
    private function load_dependencies() {
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-data-access-loader.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-data-access-i18n.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wp-data-access-admin.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-wp-data-access-public.php';
        $this->loader = new WP_Data_Access_Loader();
    }

    /**
     * Set locale for internationalization
     *
     * @since   1.0.0
     *
     * @see WP_Data_Access_I18n
     */
    private function set_locale() {
        $wpda_i18n = new WP_Data_Access_I18n();
        $this->loader->add_action( 'init', $wpda_i18n, 'load_plugin_textdomain' );
    }

    /**
     * Add admin hooks
     *
     * Initiates {@see WP_Data_Access_Admin} (admin functionality) and {@see WPDA_Export} (export functionality).
     * Adds the appropriate actions to the loader.
     *
     * @since   1.0.0
     *
     * @see WP_Data_Access_Admin
     * @see WPDA_Export
     */
    private function define_admin_hooks() {
        $plugin_admin = new WP_Data_Access_Admin();
        if ( WPDA::is_plugin_page( $this->page ) ) {
            // Handle plugin cookies.
            $wpda_cookies = new WPDA_Cookies();
            $this->loader->add_action( 'admin_init', $wpda_cookies, 'handle_plugin_cookies' );
        }
        // Admin menu.
        $this->loader->add_action( 'admin_menu', $plugin_admin, 'add_menu_items' );
        $this->loader->add_action(
            'admin_menu',
            $plugin_admin,
            'add_menu_my_tables',
            11
        );
        $this->loader->add_filter( 'submenu_file', $plugin_admin, 'wpda_submenu_filter' );
        // Admin scripts.
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
        $this->loader->add_action( 'in_admin_header', $plugin_admin, 'user_admin_notices' );
        $this->loader->add_action( 'admin_head', $plugin_admin, 'remove_icons' );
        // Add settings page.
        $this->loader->add_action( 'admin_menu', $this, 'wpdataaccess_register_settings_page' );
        // Query Builder.
        $query_builder = new WPDA_Query_Builder();
        $this->loader->add_action( 'admin_action_wpda_query_builder_execute_sql', $query_builder, 'execute' );
        $this->loader->add_action( 'admin_action_wpda_query_builder_save_sql', $query_builder, 'save' );
        $this->loader->add_action( 'admin_action_wpda_query_builder_open_sql', $query_builder, 'open' );
        $this->loader->add_action( 'admin_action_wpda_query_builder_delete_sql', $query_builder, 'delete' );
        $this->loader->add_action( 'admin_action_wpda_query_builder_get_db_hints', $query_builder, 'get_db_hints' );
        $this->loader->add_action( 'admin_action_wpda_query_builder_set_db_hints', $query_builder, 'set_db_hints' );
        $this->loader->add_action( 'admin_action_wpda_query_builder_get_vqb', $query_builder, 'get_visual_query_ajax' );
        // Run unattended scheduled export
        $this->loader->add_action( \WPDataAccess\Utilities\WPDA_Export_Scheduler::SCHEDULER_HOOK_NAME, \WPDataAccess\Utilities\WPDA_Export_Scheduler::class, 'run_scheduled_export' );
        // Mail service.
        $wpda_mail_server = \WPDataAccess\Utilities\WPDA_Mail::get_option();
        if ( isset( 
            $wpda_mail_server['host'],
            $wpda_mail_server['port'],
            $wpda_mail_server['authenticate'],
            $wpda_mail_server['encryption'],
            $wpda_mail_server['skip_verify'],
            $wpda_mail_server['username'],
            $wpda_mail_server['password']
         ) ) {
            add_action(
                'phpmailer_init',
                function ( $phpmailer ) use($wpda_mail_server) {
                    // Only run if mail WPDA requests.
                    if ( !did_action( 'wpda_sending_mail' ) ) {
                        return;
                        // Allow other plugins/themes to overwrite.
                    }
                    $phpmailer->isSMTP();
                    $phpmailer->isHTML( false );
                    $phpmailer->SMTPDebug = $wpda_mail_server['debug'] ?? 0;
                    $phpmailer->Host = $wpda_mail_server['host'];
                    $phpmailer->Port = $wpda_mail_server['port'];
                    $phpmailer->SMTPAuth = 'on' === $wpda_mail_server['authenticate'];
                    $phpmailer->SMTPSecure = $wpda_mail_server['encryption'];
                    if ( 'on' === $wpda_mail_server['skip_verify'] ) {
                        $phpmailer->SMTPOptions = array(
                            'ssl' => array(
                                'verify_peer'       => false,
                                'verify_peer_name'  => false,
                                'allow_self_signed' => true,
                            ),
                        );
                    }
                    $phpmailer->Username = $wpda_mail_server['username'];
                    $phpmailer->Password = $wpda_mail_server['password'];
                    $phpmailer->From = $wpda_mail_server['username'];
                },
                10,
                1
            );
        }
        // Export action.
        $this->loader->add_action( 'admin_action_wpda_export', WPDA_Export::class, 'export' );
        // Dashboard and widgets.
        $this->loader->add_action( 'wp_ajax_wpda_save_dashboard', WPDA_Dashboard::class, 'save' );
        $this->loader->add_action( 'wp_ajax_wpda_dashboard_list', WPDA_Dashboard::class, 'get_list' );
        $this->loader->add_action( 'wp_ajax_wpda_widget_load_panel', WPDA_Dashboard::class, 'load_widget' );
        $this->loader->add_action( 'wp_ajax_wpda_widget_delete', WPDA_Dashboard::class, 'delete_widget' );
        $this->loader->add_action( 'wp_ajax_wpda_widget_code_add', WPDA_Widget_Code::class, 'ajax_widget' );
        $this->loader->add_action( 'wp_ajax_wpda_widget_dbms_add', WPDA_Widget_Dbms::class, 'ajax_widget' );
        $this->loader->add_action( 'wp_ajax_wpda_widget_dbms_refresh', WPDA_Widget_Dbms::class, 'ajax_refresh' );
        $this->loader->add_action( 'wp_ajax_wpda_remove_new_dashboard_message', WPDA_Dashboard::class, 'remove_new_dashboard_message' );
        $this->loader->add_action( 'wp_ajax_wpda_widget_pub_add', WPDA_Widget_Publication::class, 'ajax_widget' );
        $this->loader->add_action( 'wp_ajax_wpda_widget_chart_add', WPDA_Widget_Google_Chart::class, 'ajax_widget' );
        $this->loader->add_action( 'wp_ajax_wpda_widget_chart_refresh', WPDA_Widget_Google_Chart::class, 'ajax_refresh' );
        // Add/remove favourites.
        $plugin_favourites = new WPDA_Favourites();
        $this->loader->add_action( 'admin_action_wpda_add_favourite', $plugin_favourites, 'add' );
        $this->loader->add_action( 'admin_action_wpda_rem_favourite', $plugin_favourites, 'rem' );
        // Show tables actions.
        $plugin_table_actions = new WPDA_Table_Actions();
        $this->loader->add_action( 'admin_action_wpda_show_table_actions', $plugin_table_actions, 'show' );
        $plugin_dictionary_list = new WPDA_Dictionary_Lists();
        // Get tables for a specific database.
        $this->loader->add_action( 'admin_action_wpda_get_tables', $plugin_dictionary_list, 'get_tables_ajax' );
        // Get columns for a specific table.
        $this->loader->add_action( 'admin_action_wpda_get_columns', $plugin_dictionary_list, 'get_columns' );
        // Get row count for a specific table.
        $this->loader->add_action( 'admin_action_wpda_get_table_row_count', $plugin_dictionary_list, 'get_table_row_count_ajax' );
        // Get table widget info.
        $this->loader->add_action( 'admin_action_wpda_get_table_widget_info', $plugin_dictionary_list, 'get_table_widget_info_ajax' );
        // Global database search and replace.
        $this->loader->add_action( 'wp_ajax_wpda_global_search', \WPDataAccess\Global_Search\WPDA_Global_Search::class, 'search' );
        $this->loader->add_action( 'wp_ajax_wpda_global_replace', \WPDataAccess\Global_Search\WPDA_Global_Search::class, 'replace' );
        // Export project.
        $plugin_export_project = new WPDP_Export_Project();
        $this->loader->add_action( 'admin_action_wpda_export_project', $plugin_export_project, 'export' );
        // Data backup.
        $wpda_data_backup = new WPDA_Data_Export();
        $this->loader->add_action( 'wpda_data_backup', $wpda_data_backup, 'wpda_data_backup' );
        // Allow to add multiple user roles.
        $wpda_roles = new WPDA_Roles();
        $this->loader->add_action( 'user_new_form', $wpda_roles, 'multiple_roles_selection' );
        $this->loader->add_action( 'edit_user_profile', $wpda_roles, 'multiple_roles_selection' );
        $this->loader->add_action( 'profile_update', $wpda_roles, 'multiple_roles_update' );
        $this->loader->add_filter( 'manage_users_columns', $wpda_roles, 'multiple_roles_label' );
        // Check if a remote db connection can be established via ajax.
        $wpdadb = new WPDADB();
        $this->loader->add_action( 'admin_action_wpda_check_remote_database_connection', $wpdadb, 'check_remote_database_connection' );
        // Make WordPress user ID available to database connections.
        add_action(
            'wpda_dbinit',
            function ( $wpdadb ) {
                if ( null !== $wpdadb ) {
                    $suppress_errors = $wpdadb->suppress_errors( true );
                    $wpdadb->query( 'set @wpda_wp_user_id = ' . get_current_user_id() );
                    $wpdadb->suppress_errors( $suppress_errors );
                }
            },
            10,
            1
        );
        add_action(
            'wp_ajax_wpda_dbinit_admin',
            function () {
                if ( !isset( $_POST['wpnonce'], $_POST['wpdaschema_name'] ) ) {
                    WPDA::sent_header( 'application/json' );
                    echo WPDA::sent_msg( 'ERROR', 'Invalid arguments' );
                    // phpcs:ignore WordPress.Security.EscapeOutput
                    wp_die();
                }
                $wpnonce = sanitize_text_field( wp_unslash( $_REQUEST['wpnonce'] ) );
                // input var okay.
                if ( !WPDA::current_user_is_admin() || !wp_verify_nonce( $wpnonce, 'wpda_dbinit_admin_' . WPDA::get_current_user_login() ) ) {
                    WPDA::sent_header( 'application/json' );
                    echo WPDA::sent_msg( 'ERROR', 'Not authorized' );
                    // phpcs:ignore WordPress.Security.EscapeOutput
                    wp_die();
                }
                $wpdaschema_name = sanitize_text_field( wp_unslash( $_REQUEST['wpdaschema_name'] ) );
                // input var okay.
                $wpdadb = WPDADB::get_db_connection( $wpdaschema_name );
                if ( null === $wpdadb ) {
                    WPDA::sent_header( 'application/json' );
                    echo WPDA::sent_msg( 'ERROR', 'Cannot connect to database' );
                    // phpcs:ignore WordPress.Security.EscapeOutput
                    wp_die();
                }
                $suppress_errors = $wpdadb->suppress_errors( true );
                $wpdadb->query( 'create function wpda_get_wp_user_id() returns integer deterministic no sql return @wpda_wp_user_id' );
                $error = $wpdadb->last_error;
                $wpdadb->suppress_errors( $suppress_errors );
                if ( '' === $error ) {
                    //phpcs:ignore - 8.1 proof
                    echo WPDA::sent_msg( 'OK', '' );
                    // phpcs:ignore WordPress.Security.EscapeOutput
                } else {
                    echo WPDA::sent_msg( 'ERROR', "Function not created [{$error}]" );
                    // phpcs:ignore WordPress.Security.EscapeOutput
                }
                wp_die();
            },
            10,
            1
        );
        // Add id to wpda_datatables.js (for IE).
        $this->loader->add_filter(
            'script_loader_tag',
            $this,
            'add_id_to_script',
            10,
            3
        );
        // Add CSV mapping calls.
        $wpda_csv_uploads_model = new WPDA_CSV_Uploads_Model();
        $this->loader->add_action( 'admin_action_wpda_save_csv_mapping', $wpda_csv_uploads_model, 'save_mapping' );
        $this->loader->add_action( 'admin_action_wpda_csv_preview_mapping', $wpda_csv_uploads_model, 'preview_mapping' );
        // Data Tables services.
        $this->loader->add_action( 'wp_ajax_wpda_test_publication', \WPDataAccess\Data_Publisher\WPDA_Publisher_Form::class, 'test_publication' );
        // Add custom CSS to freemius pages.
        add_action(
            'admin_footer',
            function () {
                if ( WP_Data_Access_Admin::MAIN_PAGE_SLUG . '-account' === $this->page || WP_Data_Access_Admin::MAIN_PAGE_SLUG . '-pricing' === $this->page ) {
                    ?>
				<script type="application/javascript">
					jQuery(function() {
						jQuery.each(document.styleSheets, function (index, cssFile) {
							if (cssFile.href!==null && cssFile.href.toString().includes("load-styles.php")) {
								var classes = cssFile.rules || cssFile.cssRules;
								for (var x=0; x<classes.length; x++) {
									if (
										classes[x].selectorText!==undefined &&
										classes[x].selectorText!==null &&
										classes[x].selectorText.includes("#adminmenu li.current a.menu-top")
									) {
										jQuery("#adminmenu #toplevel_page_wpda a.menu-top").attr("style", classes[x].style.cssText);
									}
								}
							}
						});
					});
				</script>
					<?php 
                }
            },
            10,
            1
        );
        add_action(
            'wpda_set_hard_row_count',
            function ( $dbs, $tbl ) {
                // Only admins and super admins.
                if ( WPDA::current_user_is_admin() ) {
                    // Get actual row count.
                    $count = \WPDataAccess\API\WPDA_Actions::get_row_count( $dbs, $tbl );
                    // Get table settings.
                    $settings_db = WPDA_Table_Settings_Model::query( $tbl, $dbs );
                    if ( isset( $settings_db[0]['wpda_table_settings'] ) ) {
                        // Convert string to JSON.
                        $settings = json_decode( $settings_db[0]['wpda_table_settings'], true );
                        if ( isset( $settings['table_settings']['row_count_estimate_value_hard'] ) ) {
                            // Store actual row count as hard estimate.
                            $settings['table_settings']['row_count_estimate_value_hard'] = $count;
                            // Save new hard row count.
                            WPDA_Table_Settings_Model::update( $tbl, json_encode( $settings ), $dbs );
                        }
                    }
                }
            },
            10,
            2
        );
    }

    /**
     * Needed for JDT to support IE
     *
     * @param string $tag Tag.
     * @param string $handle Handle.
     * @param string $src Source.
     * @return mixed|string
     */
    public function add_id_to_script( $tag, $handle, $src ) {
        if ( 'wpda_datatables' === $handle ) {
            $tag = '<script id="wpda_datatables" src="' . $src . '"></script>';
            // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript
        }
        return $tag;
    }

    /**
     * Add public hooks
     *
     * Initiates {@see WP_Data_Access_Public} (public functionality), {@see WPDA_Data_Tables} (ajax call to support
     * server side jQuery DataTables functionality). Adds the appropriate actions to
     * the loader.
     *
     * @since   1.0.0
     *
     * @see WP_Data_Access_Public
     * @see WPDA_Data_Tables
     * @see WPDA_Dictionary_Lists
     */
    private function define_public_hooks() {
        $plugin_public = new WP_Data_Access_Public();
        // Shortcodes.
        $this->loader->add_action( 'init', $plugin_public, 'register_shortcodes' );
        // Public scripts.
        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
        $this->loader->add_action(
            'admin_bar_menu',
            $plugin_public,
            'add_apps_to_admin_toolbar',
            9999
        );
        $this->loader->add_action(
            'admin_bar_menu',
            $plugin_public,
            'add_data_projects_to_admin_toolbar',
            9999
        );
        // Ajax calls.
        $plugin_datatables = new WPDA_Data_Tables();
        $this->loader->add_action( 'wp_ajax_wpda_datatables', $plugin_datatables, 'get_data' );
        $this->loader->add_action( 'wp_ajax_nopriv_wpda_datatables', $plugin_datatables, 'get_data' );
        // Export action.
        $this->loader->add_action( 'wp_ajax_wpda_export', WPDA_Export::class, 'export_ajax' );
        $this->loader->add_action( 'wp_ajax_nopriv_wpda_export', WPDA_Export::class, 'export_ajax' );
        // Add id to wpda_datatables.js (for IE).
        $this->loader->add_filter(
            'script_loader_tag',
            $this,
            'add_id_to_script',
            10,
            3
        );
        // Autocomplete.
        $autocomplete_service = new WPDA_Autocomplete();
        $this->loader->add_action( 'wp_ajax_wpda_autocomplete', $autocomplete_service, 'autocomplete' );
        $this->loader->add_action( 'wp_ajax_nopriv_wpda_autocomplete', $autocomplete_service, 'autocomplete_anonymous' );
    }

    /**
     * Start plugin loader
     *
     * @since   1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * Add plugin settings page
     */
    public function wpdataaccess_register_settings_page() {
        add_options_page(
            'WP Data Access',
            'WP Data Access',
            'manage_options',
            WP_Data_Access_Admin::PAGE_SETTINGS,
            array($this, 'wpdataaccess_settings_page')
        );
    }

    /**
     * Show settings page
     */
    public function wpdataaccess_settings_page() {
        WPDA_Dashboard::add_dashboard();
        $current_tab = ( isset( $_REQUEST['tab'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['tab'] ) ) : 'plugin' );
        // phpcs:ignore WordPress.Security.NonceVerification
        switch ( $current_tab ) {
            case 'apps':
                $wpda_settings_class_name = 'WPDA_Settings_Apps';
                break;
            case 'backend':
                $wpda_settings_class_name = 'WPDA_Settings_BackEnd';
                break;
            case 'frontend':
                $wpda_settings_class_name = 'WPDA_Settings_FrontEnd';
                break;
            case 'pds':
                $wpda_settings_class_name = 'WPDA_Settings_PDS';
                break;
            case 'uninstall':
                $wpda_settings_class_name = 'WPDA_Settings_Uninstall';
                break;
            case 'repository':
                $wpda_settings_class_name = 'WPDA_Settings_ManageRepository';
                break;
            case 'system':
                $wpda_settings_class_name = 'WPDA_Settings_SystemInfo';
                break;
            case 'mail':
                $wpda_settings_class_name = 'WPDA_Settings_Mail';
                break;
            case 'drives':
                $wpda_settings_class_name = 'WPDA_Settings_Drives';
                break;
            case 'legacy':
                $wpda_settings_class_name = 'WPDA_Settings_Legacy';
                break;
            default:
                $wpda_settings_class_name = 'WPDA_Settings_Plugin';
        }
        $wpda_settings_class_name = '\\WPDataAccess\\Settings\\' . $wpda_settings_class_name;
        $wpda_settings = new $wpda_settings_class_name($current_tab);
        $wpda_settings->show();
    }

}
