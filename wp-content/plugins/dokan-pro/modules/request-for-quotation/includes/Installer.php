<?php

namespace WeDevs\DokanPro\Modules\RequestForQuotation;

class Installer {

    public function __construct() {
        $this->create_tables();
        $this->create_pages();
        $this->create_capabilities();
    }

    /**
     * Create all tables related with RAQ
     *
     * @since 3.6.0
     *
     * @return void
     */
    public function create_tables() {
        global $wpdb;

        include_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $collate = $wpdb->get_charset_collate();

        $tables['dokan_request_quotes'] = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}dokan_request_quotes` (
                  `id` bigint unsigned AUTO_INCREMENT,
                  `user_id` bigint NOT NULL DEFAULT 0,
                  `order_id` bigint NOT NULL DEFAULT 0,
                  `quote_title` varchar(156) NULL,
                  `store_info` longtext NULL,
                  `customer_info` longtext NULL,
                  `shipping_cost` decimal(10, 2) NOT NULL DEFAULT 0.00,
                  `status` varchar (26) DEFAULT 'pending',
                  `expiry_date` int(11) unsigned DEFAULT 0,
                  `expected_date` int(11) unsigned DEFAULT 0,
                  `created_at` int(11) unsigned DEFAULT 0,
                  `converted_by` varchar(26) NULL,
                  `updated_at` int(11) unsigned DEFAULT 0,
                  PRIMARY KEY  (`id`)
                ) ENGINE=InnoDB {$collate}";

        $tables['dokan_request_quote_details'] = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}dokan_request_quote_details` (
                  `id` bigint unsigned AUTO_INCREMENT,
                  `quote_id` bigint NOT NULL,
                  `product_id` bigint NOT NULL,
                  `quantity` int(10) NOT NULL,
                  `offer_price` decimal (19, 4) NOT NULL,
                  PRIMARY KEY  (`id`)
                ) ENGINE=InnoDB {$collate}";

        $tables['dokan_quote_rules'] = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}dokan_request_quote_rules` (
                  `id` bigint unsigned AUTO_INCREMENT,
                  `vendor_id` bigint NOT NULL,
                  `rule_name` varchar (256) NOT NULL,
                  `hide_price` TINYINT(1) NOT NULL DEFAULT 0,
                  `hide_price_text` varchar (256) NULL,
                  `hide_cart_button` varchar (25) NOT NULL DEFAULT 'replace',
                  `apply_on_all_product` TINYINT(1) NOT NULL DEFAULT 0,
                  `button_text` varchar(256) NOT NULL DEFAULT 'Add to quote',
                  `rule_priority` TINYINT(3) NOT NULL DEFAULT 0,
                  `rule_contents` longtext NULL,
                  `status` varchar (26) DEFAULT 'pending',
                  `created_at` int(11) unsigned DEFAULT 0,
                  PRIMARY KEY  (`id`)
                ) ENGINE=InnoDB {$collate}";

        foreach ( $tables as $table ) {
            dbDelta( $table );
        }
    }

    /**
     * Create pages.
     *
     * @since 3.6.0
     *
     * @return void
     */
    public function create_pages() {
        Helper::get_quote_page_id();
    }

    /**
     * Create capabilities for vendor
     *
     * @since 3.6.0
     *
     * @return void
     */
    public function create_capabilities() {
        global $wp_roles;

        if ( class_exists( 'WP_Roles' ) && ! isset( $wp_roles ) ) {
            $wp_roles = new \WP_Roles(); //phpcs:ignore
        }

        $wp_roles->add_cap( 'seller', 'dokan_view_request_quote_menu' );
        $wp_roles->add_cap( 'administrator', 'dokan_view_request_quote_menu' );
        $wp_roles->add_cap( 'shop_manager', 'dokan_view_request_quote_menu' );
    }
}
