<?php

namespace WeDevs\DokanPro\Modules\ProductQA\Admin;

use WeDevs\Dokan\Admin\Dashboard\Pages\AbstractPage;
use WeDevs\DokanPro\Modules\ProductQA\Models\Question;

class AdminMenu extends AbstractPage {

    public function __construct() {
        $this->register_hooks();
    }

    /**
     * Get the ID of the page.
     *
     * @return string
     */
    public function get_id(): string {
        return 'product-qa';
    }

    /**
     * Define the menu configuration for Product Questions & Answers.
     *
     * @param string $capability Required capability to access the page.
     * @param string $position  Menu position.
     *
     * @return array
     */
    public function menu( string $capability, string $position ): array {
        $unanswered_count = $this->get_unanswered_question_count();
        $menu_title = __( 'Product Q&A', 'dokan' );

        if ( $unanswered_count > 0 ) {
            // translators: %s is replaced with the unanswered questions count.
            $menu_title .= sprintf(
                ' <span class="awaiting-mod count-1"><span class="pending-count">%s</span></span>',
                number_format_i18n( $unanswered_count )
            );
        }

        return [
            'page_title' => __( 'Product Questions & Answers', 'dokan' ),
            'menu_title' => $menu_title,
            'route'     => 'product-qa',
            'capability' => $capability,
            'position'  => 11,
        ];
    }

    /**
     * Get the number of unanswered product questions.
     *
     * @return int
     */
    private function get_unanswered_question_count(): int {
        $transient_key = 'dokan_admin_product_qa_unanswered_count';
        $cached        = get_transient( $transient_key );

        if ( false !== $cached ) {
            return (int) $cached;
        }

        // Use the Question model's count method - same as Vue admin
        $unanswered_count = ( new Question() )->count( [ 'answered' => Question::STATUS_UNANSWERED ] );

        // Cache for 2 minutes
        set_transient( $transient_key, $unanswered_count, 2 * MINUTE_IN_SECONDS );

        return $unanswered_count;
    }

    /**
     * Clear the unanswered count transient when questions are answered.
     *
     * @return void
     */
    public static function clear_unanswered_count_cache() {
        delete_transient( 'dokan_admin_product_qa_unanswered_count' );
    }

    /**
     * Get the settings for the page.
     *
     * @return array
     */
    public function settings(): array {
        return [];
    }

    /**
     * Get the scripts required for the page.
     *
     * @return array
     */
    public function scripts(): array {
        return [
            'dokan-admin-product-qa',
        ];
    }

    /**
     * Get the styles required for the page.
     *
     * @return array
     */
    public function styles(): array {
        return [
            'dokan-product-qa-react',
        ];
    }

    /**
     * Register the required scripts and styles for the page.
     *
     * @return void
     */
    public function register(): void {
        $asset_file = DOKAN_PRODUCT_QA_DIR . '/assets/js/dokan-admin-product-qa.asset.php';

        if ( file_exists( $asset_file ) ) {
            $asset = require_once $asset_file;

            wp_register_script(
                'dokan-admin-product-qa',
                DOKAN_PRODUCT_QA_ASSETS . '/js/dokan-admin-product-qa.js',
                $asset['dependencies'],
                $asset['version'],
                true
            );
        }
    }
}
