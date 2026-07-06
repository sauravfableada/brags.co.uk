<?php
namespace WeDevs\DokanPro\Modules\ProductAdvertisement\Admin;

use WeDevs\DokanPro\Modules\ProductAdvertisement\Helper;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Class Install
 *
 * @package WeDevs\DokanPro\Modules\ProductAdvertisement
 *
 * @since 3.5.0
 */
class Install {

    /**
     * Install constructor.
     *
     * @since 3.5.0
     *
     * @return void
     */
    public function __construct() {
        $this->create_table();
        $this->create_advertisement_product();
        if ( $this->schedule_cron() ) {
            //early call expire cron
            do_action( 'dokan_product_advertisement_daily_at_midnight_cron' );
        }
    }

    /**
     * This method will create required table
     *
     * @since 3.5.0
     *
     * @return void
     */
    private function create_table() {
        global $wpdb;

        $sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}dokan_advertised_products` (
                    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `product_id` bigint(20) UNSIGNED NOT NULL,
                    `created_via` ENUM('order','admin','subscription','free') NOT NULL DEFAULT 'admin',
                    `order_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
                    `price` decimal(19,4) NOT NULL DEFAULT 0.0000,
                    `expires_at` int(10) UNSIGNED NOT NULL DEFAULT 0,
                    `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
                    `added` int(10) UNSIGNED NOT NULL DEFAULT 0,
                    `updated` int(10) UNSIGNED NOT NULL DEFAULT 0,
                    PRIMARY KEY (`id`),
                    KEY product_id (product_id),
                    KEY order_id (order_id),
                    KEY expires_at (expires_at),
                    KEY status (status),
                    KEY expires_at_status (expires_at,status)
                ) ENGINE=InnoDB {$wpdb->get_charset_collate()};
                ";

        include_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta( $sql );
    }

    /**
     * This method will create advertisement base product
     *
     * @since 3.5.0
     *
     * @return void
     */
    private function create_advertisement_product() {
        Helper::create_advertisement_base_product();
    }

    /**
     * Schedule crom for midnight
     *
     * @since 3.5.0
     *
     * @return bool
     */
    public function schedule_cron() {
        if ( ! wp_next_scheduled( 'dokan_product_advertisement_daily_at_midnight_cron' ) ) {
            // schedule cron at midnight local time
            $timestamp = dokan_current_datetime()->modify( 'midnight' )->getTimestamp();
            wp_schedule_event(
                $timestamp,
                'daily',
                'dokan_product_advertisement_daily_at_midnight_cron'
            );
        }
        return true;
    }
}
