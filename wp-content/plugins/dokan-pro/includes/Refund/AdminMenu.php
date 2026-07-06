<?php

namespace WeDevs\DokanPro\Refund;

use WeDevs\Dokan\Admin\Dashboard\Pages\AbstractPage;

class AdminMenu extends AbstractPage {
    public function __construct() {
        $this->register_hooks();
    }
    /**
     * Get the ID of the page.
     *
     * @since 4.2.0
     *
     * @return string
     */
    public function get_id(): string {
        return 'refund';
    }

    /**
     * @inheritDoc
     */
    public function menu( string $capability, string $position ): array {
        $refund = dokan_get_refund_count();
        $pending_count = absint( $refund['pending'] ?? 0 );
        $menu_title = __( 'Refunds ', 'dokan' );
        if ( $pending_count > 0 ) {
            // translators: %s is replaced with the pending refund count.
            $menu_title .= sprintf( '<span class="awaiting-mod count-1"><span class="pending-count">%s</span></span>', $pending_count );
        }
        return [
            'page_title' => __( 'Refunds ', 'dokan' ),
            'menu_title' => $menu_title,
            'route'      => 'refund',
            'capability' => $capability,
            'position'   => 99,
        ];
    }

    /**
     * @inheritDoc
     */
    public function settings(): array {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function scripts(): array {
        return [
            'dokan-admin-refund',
        ];
    }

    /**
     * Get the styles.
     *
     * @since 4.2.0
     *
     * @return array<string>
     */
    public function styles(): array {
        return [];
    }

    /**
     * Register the page scripts and styles.
     *
     * @since 4.2.0
     *
     * @return void
     */
    public function register(): void {
        $asset = require_once DOKAN_PRO_DIR . '/assets/js/dokan-admin-refund.asset.php';
        wp_register_script(
            'dokan-admin-refund',
            DOKAN_PRO_PLUGIN_ASSEST . '/js/dokan-admin-refund.js',
            $asset['dependencies'],
            $asset['version'],
            true
        );

    }
}
