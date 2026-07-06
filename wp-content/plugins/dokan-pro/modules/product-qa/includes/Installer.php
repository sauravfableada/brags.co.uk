<?php

namespace WeDevs\DokanPro\Modules\ProductQA;

use WeDevs\DokanPro\Modules\ProductQA\Models\Answer;
use WeDevs\DokanPro\Modules\ProductQA\Models\Question;

defined( 'ABSPATH' ) || exit;

/**
 * Product QA Installer
 *
 * @since 3.11.0
 */
class Installer {

    /**
     * Constructor.
     */
    public function __construct() {
        $this->create_tables();
    }

    /**
     * Create tables.
     *
     * @since 3.11.0
     *
     * @return void
     */
    private function create_tables() {
        global $wpdb;

        $questions_table = ( new Question() )->get_table();
        $answers_table = ( new Answer() )->get_table();

        $questions_sql = "CREATE TABLE IF NOT EXISTS `{$questions_table}` (
            `id` bigint unsigned NOT NULL AUTO_INCREMENT,
            `product_id` bigint unsigned NOT NULL,
            `question` text NOT NULL,
            `user_id` bigint unsigned NOT NULL DEFAULT 0,
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `read` tinyint NOT NULL DEFAULT 0,
            `status` varchar(50) DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `product_id` (`product_id`),
            KEY `user_id` (`user_id`),
            FULLTEXT KEY `question` (`question`),
            FOREIGN KEY (`product_id`) REFERENCES `{$wpdb->posts}` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE
        ) {$wpdb->get_charset_collate()};";

        $answers_sql = "CREATE TABLE IF NOT EXISTS `{$answers_table}` (
            `id` bigint unsigned NOT NULL AUTO_INCREMENT,
            `question_id` bigint unsigned NOT NULL,
            `answer` text,
            `user_id` bigint unsigned DEFAULT 0,
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `question_id` (`question_id`),
            KEY `user_id` (`user_id`),
            FULLTEXT KEY `answer` (`answer`),
            FOREIGN KEY (`question_id`) REFERENCES `{$questions_table}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
        ){$wpdb->get_charset_collate()};";

        include_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta( $questions_sql );
        dbDelta( $answers_sql );
    }
}
