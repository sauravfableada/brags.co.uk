<?php

namespace WeDevs\DokanPro\Modules\StoreReviews\Admin;

use WeDevs\Dokan\Admin\Dashboard\Pages\AbstractPage;

class StoreReview extends AbstractPage {

    public function get_id(): string {
        return 'store-reviews';
    }

    public function menu( string $capability, string $position ): array {
        return [
            'page_title' => __( 'Store Reviews', 'dokan' ),
            'menu_title' => __( 'Store Reviews', 'dokan' ),
            'route'      => 'store-reviews',
            'capability' => $capability,
            'position'   => 10,
        ];
    }

    public function settings(): array {
        return [];
    }

    public function scripts(): array {
        return [ 'dokan-store-review' ];
    }

    public function styles(): array {
        return [];
    }

    public function register(): void {
        $asset = require_once DOKAN_SELLER_RATINGS_DIR . '/assets/js/store-reviews.asset.php';
        wp_register_script(
            'dokan-store-review',
            DOKAN_SELLER_RATINGS_PLUGIN_ASSEST . '/js/store-reviews.js',
            $asset['dependencies'],
            $asset['version'],
            true
        );
    }
}
