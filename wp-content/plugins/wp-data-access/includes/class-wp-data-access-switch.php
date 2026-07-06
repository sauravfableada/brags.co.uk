<?php

/**
 * Suppress "error - 0 - No summary was found for this file" on phpdoc generation
 *
 * @package plugin\includes
 */
use WPDataAccess\Plugin_Table_Models\WPDA_Design_Table_Model;
use WPDataAccess\Plugin_Table_Models\WPDA_Logging_Model;
use WPDataAccess\Plugin_Table_Models\WPDA_Media_Model;
use WPDataAccess\Plugin_Table_Models\WPDA_Publisher_Model;
use WPDataAccess\Plugin_Table_Models\WPDA_Table_Settings_Model;
use WPDataAccess\Plugin_Table_Models\WPDA_User_Menus_Model;
use WPDataAccess\Plugin_Table_Models\WPDP_Page_Model;
use WPDataAccess\Plugin_Table_Models\WPDP_Project_Model;
use WPDataAccess\Plugin_Table_Models\WPDP_Project_Design_Table_Model;
use WPDataAccess\Premium\WPDAPRO_Dashboard\WPDAPRO_Dashboard;
use WPDataAccess\Utilities\WPDA_Repository;
use WPDataAccess\WPDA;
/**
 * Class WP_Data_Access_Switch
 *
 * Switch to:
 * + activate plugin {@see WP_Data_Access_Switch::activate()}
 * + deactive plugin {@see WP_Data_Access_Switch::deactivate()}
 *
 * @author  Peter Schulz
 * @since   1.0.0
 *
 * @see WP_Data_Access_Switch::activate()
 * @see WP_Data_Access_Switch::deactivate()
 */
class WP_Data_Access_Switch {
    /**
     * Activate plugin WP Data Access
     *
     * The user must have the appropriate privileges to perform this operation.
     *
     * For single site installation {@see WP_Data_Access_Switch::activate_blog()} will be called. For multi site
     * installations {@see WP_Data_Access_Switch::activate_blog()} must be called for every blog.
     *
     * IMPORTANT!!!
     *
     * For blogs installed on multi site installations after activation of the plugin, activation of the plugin for
     * that blog will not be performed if the plugin is network activated. In that case the admin user of the blog
     * will receive a message when viewing a plugin page with an option to follow these steps manually.
     *
     * @since   1.0.0
     *
     * @see WP_Data_Access_Switch::activate_blog()
     */
    public static function activate() {
        if ( current_user_can( 'activate_plugins' ) ) {
            // Activate plugin.
            if ( is_multisite() ) {
                global $wpdb;
                // Multisite installation.
                $blogids = $wpdb->get_col( "select blog_id from {$wpdb->blogs}" );
                // db call ok; no-cache ok.
                foreach ( $blogids as $blog_id ) {
                    // Activate blog.
                    switch_to_blog( $blog_id );
                    self::activate_blog();
                    restore_current_blog();
                }
            } else {
                // Single site installation.
                self::activate_blog();
            }
        }
    }

    /**
     * Activate blog
     *
     * The user must have the appropriate privileges to perform this operation.
     *
     * On activation this method checks whether there has previously been a version of the plugin installed. For this
     * purpose the wp_options table read directly (usually done via class WPDA). If a value is found, this method
     * checks if the version number in wp_options is the same as the plugin version. If these are equal, no action is
     * needed. If they are not equal, this method will check if there is an upgrade or downgrade for the delta
     * between these releases.
     *
     * This action is performed on the 'active WordPress blog'. On single site there is only one blog. On multisite
     * installations it must be executed for every blog.
     *
     * On a fresh installation the following actions are performed:
     * + save plugin version number in wp_options {@see WPDA::set_option()}
     * + (re)create plugin repository {@see WPDA_Repository::recreate()}
     *
     * @since   1.0.0
     *
     * @see WPDA::set_option()
     * @see WPDA_Repository::create()
     */
    protected static function activate_blog() {
        if ( current_user_can( 'activate_plugins' ) ) {
            if ( get_option( WPDataAccess\WPDA::OPTION_WPDA_VERSION[0] ) !== WPDataAccess\WPDA::OPTION_WPDA_VERSION[1] ) {
                self::recreate_repository();
            } elseif ( !WPDA_User_Menus_Model::table_exists() || !WPDA_Design_Table_Model::table_exists() || !WPDP_Project_Design_Table_Model::table_exists() || !WPDA_Publisher_Model::table_exists() || !WPDA_Logging_Model::table_exists() || !WPDA_Media_Model::table_exists() || !WPDP_Project_Model::table_exists() || !WPDP_Page_Model::table_exists() || !WPDA_Table_Settings_Model::table_exists() ) {
                self::recreate_repository();
            }
            if ( wpda_freemius()->is_free_plan() ) {
                update_option( 'wpda_fulltext_support', 'off' );
            }
            self::flush_rewrite_rules();
            self::set_legacy_tools();
        }
    }

    /**
     * (re)create plugin repository
     *
     * If no repository is found, a new one is created
     * If a repository is found, the table structures are update and the data transferred
     */
    protected static function recreate_repository() {
        $wpda_repository = new WPDA_Repository();
        $wpda_repository->recreate();
        // Save (new) plugin version.
        WPDA::set_option( WPDA::OPTION_WPDA_VERSION );
        // Create content folder to store CSV and cache files.
        WPDA::wpda_create_content_folder();
        // Generate new sonce seed on every update.
        WPDA::set_option( WPDA::OPTION_PLUGIN_SONCE_SEED, bin2hex( openssl_random_pseudo_bytes( wp_rand( 40, 60 ) ) ) );
        WPDA::set_option( WPDA::OPTION_WPDA_UPGRADED, true );
    }

    /**
     * Deactivate plugin WP Data Access
     *
     * On deactivation we leave the repository and options as they are in case the user wants to reactivate the
     * plugin later again. Tables and options are deleted when the plugin is uninstalled. To keep tables and options
     * on uninstall change plugin settings (see uninstall settings).
     *
     * @since   1.0.0
     */
    public static function deactivate() {
        if ( current_user_can( 'activate_plugins' ) ) {
            // Deactivate plugin.
            if ( is_multisite() ) {
                global $wpdb;
                // Multisite installation.
                $blogids = $wpdb->get_col( "select blog_id from {$wpdb->blogs}" );
                // db call ok; no-cache ok.
                foreach ( $blogids as $blog_id ) {
                    // Deactivate blog.
                    switch_to_blog( $blog_id );
                    self::flush_rewrite_rules();
                    restore_current_blog();
                }
            } else {
                // Single site installation.
                self::flush_rewrite_rules();
            }
        }
    }

    private static function flush_rewrite_rules() {
        // Unscheduled all WP Data Access events
        self::unschedule_event( 'wpda_data_backup' );
        self::unschedule_event( \WPDataAccess\Query_Builder\WPDA_Query_Builder_Scheduler::SCHEDULER_HOOK_NAME );
        self::unschedule_event( \WPDataAccess\Utilities\WPDA_Export_Scheduler::SCHEDULER_HOOK_NAME );
    }

    private static function unschedule_event( $hook ) {
        $crons = _get_cron_array();
        foreach ( $crons as $timestamp => $cron ) {
            if ( isset( $cron[$hook] ) ) {
                foreach ( $cron[$hook] as $event ) {
                    if ( isset( $event['args'] ) ) {
                        wp_unschedule_event( $timestamp, $hook, $event['args'] );
                    }
                }
            }
        }
    }

    private static function set_legacy_tools() {
        // Get current legacy tool settings
        $stored_legacy_tools = get_option( 'wpda_plugin_legacy_tools' );
        if ( false === $stored_legacy_tools ) {
            // Use default for fresh installation
            // Update if legacy tool is actively used
            $option_legacy_tools = \WPDataAccess\Utilities\WPDA_Legacy_Tool_Visibility::get();
            $option_updated = false;
            if ( 0 < $option_legacy_tools['tables'][1] ) {
                $option_legacy_tools['tables'] = array(true, $option_legacy_tools['tables'][1]);
                $option_updated = true;
            }
            if ( 0 < $option_legacy_tools['forms'][1] ) {
                $option_legacy_tools['forms'] = array(true, $option_legacy_tools['forms'][1]);
                $option_updated = true;
            }
            if ( 0 < $option_legacy_tools['templates'][1] ) {
                $option_legacy_tools['templates'] = array(true, $option_legacy_tools['templates'][1]);
                $option_updated = true;
            }
            if ( 0 < $option_legacy_tools['designer'][1] ) {
                $option_legacy_tools['designer'] = array(true, $option_legacy_tools['designer'][1]);
                $option_updated = true;
            }
            if ( 0 < $option_legacy_tools['dashboards'][1] ) {
                $option_legacy_tools['dashboards'] = array(true, $option_legacy_tools['dashboards'][1]);
                $option_updated = true;
            }
            if ( 0 < $option_legacy_tools['charts'][1] ) {
                $option_legacy_tools['charts'] = array(true, $option_legacy_tools['charts'][1]);
                $option_updated = true;
            }
            if ( $option_updated ) {
                // Save legacy tool settings.
                WPDA::set_option( WPDA::OPTION_PLUGIN_LEGACY_TOOLS, $option_legacy_tools );
            }
        }
    }

}
