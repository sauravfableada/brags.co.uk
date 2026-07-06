<?php

namespace WeDevs\DokanPro\Modules\VendorSupport;

defined( 'ABSPATH' ) || exit;

/**
 * Class Installer
 *
 * Handles database table creation and updates for Vendor Support module.
 *
 * @since 4.1.2
 *
 * @package WeDevs\DokanPro\Modules\VendorSupport
 */
class Installer {

    /**
     * Run the installer.
     *
     * @since 4.1.2
     *
     * @return void
     */
    public static function install() {
        self::create_tables();
    }

    /**
     * Create database tables.
     *
     * @since 4.1.2
     *
     * @return void
     */
    private static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Create tickets table
        $tickets_table = $wpdb->prefix . 'dokan_vendor_support_tickets';
        $tickets_sql = "CREATE TABLE IF NOT EXISTS `{$tickets_table}` (
            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `vendor_id` bigint(20) unsigned NOT NULL,
            `subject` varchar(255) NOT NULL,
            `status` varchar(20) NOT NULL DEFAULT 'open',
            `priority` varchar(20) DEFAULT 'normal',
            `last_reply_by` varchar(20) DEFAULT NULL,
            `last_reply_at` datetime DEFAULT NULL,
            `is_read_by_admin` tinyint(1) DEFAULT 0,
            `is_read_by_vendor` tinyint(1) DEFAULT 1,
            `date_created` datetime NOT NULL,
            `date_updated` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `vendor_id` (`vendor_id`),
            KEY `status` (`status`),
            KEY `last_reply_at` (`last_reply_at`),
            KEY `date_created` (`date_created`)
        ) ENGINE=InnoDB {$charset_collate};";

        // Create conversations table
        $conversations_table = $wpdb->prefix . 'dokan_vendor_support_conversations';
        $conversations_sql = "CREATE TABLE IF NOT EXISTS `{$conversations_table}` (
            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `ticket_id` bigint(20) unsigned NOT NULL,
            `message` longtext NOT NULL,
            `sender_type` varchar(20) NOT NULL,
            `sender_id` bigint(20) unsigned NOT NULL,
            `is_read` tinyint(1) DEFAULT 0,
            `date_created` datetime NOT NULL,
            PRIMARY KEY (`id`),
            KEY `ticket_id` (`ticket_id`),
            KEY `sender_type` (`sender_type`),
            KEY `date_created` (`date_created`)
        ) ENGINE=InnoDB {$charset_collate};";

        // Create status logs table
        $status_logs_table = $wpdb->prefix . 'dokan_vendor_support_status_logs';
        $status_logs_sql = "CREATE TABLE IF NOT EXISTS `{$status_logs_table}` (
            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `ticket_id` bigint(20) unsigned NOT NULL,
            `old_status` varchar(20) DEFAULT NULL,
            `new_status` varchar(20) NOT NULL,
            `changed_by_type` varchar(20) NOT NULL,
            `changed_by_id` bigint(20) unsigned NOT NULL,
            `note` text DEFAULT NULL,
            `date_created` datetime NOT NULL,
            PRIMARY KEY (`id`),
            KEY `ticket_id` (`ticket_id`),
            KEY `date_created` (`date_created`)
        ) ENGINE=InnoDB {$charset_collate};";

        if ( ! function_exists( 'dbDelta' ) ) {
            // Execute table creation queries
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }

        dbDelta( $tickets_sql );
        dbDelta( $conversations_sql );
        dbDelta( $status_logs_sql );
    }
}
