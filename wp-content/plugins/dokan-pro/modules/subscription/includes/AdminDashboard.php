<?php

namespace DokanPro\Modules\Subscription;

use WeDevs\Dokan\Admin\Dashboard\Pages\AbstractPage;

class AdminDashboard extends AbstractPage {

    /**
     * Get the ID of the page.
     *
     * @since 4.0.0
     *
     * @return string
     */
	public function get_id(): string {
		return 'subscriptions';
	}

	/**
	 * @inheritDoc
	 */
	public function menu( string $capability, string $position ): array {
		return [
            'page_title' => __( 'Dokan Subscriptions', 'dokan' ),
            'menu_title' => __( 'Subscriptions', 'dokan' ),
            'route'      => 'subscriptions',
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
        return [ 'dokan-admin-subscriptions' ];
	}

    /**
     * Get the styles.
     *
     * @since 4.0.0
     *
     * @return array<string> An array of style handles.
     */
    public function styles(): array {
        return [];
    }

    /**
     * Register the page scripts and styles.
     *
     * @since 4.0.0
     *
     * @return void
     */
    public function register(): void {
        $asset_file = include DPS_PATH . '/assets/js/admin/dashboard/index.asset.php';

        wp_register_script(
            'dokan-admin-subscriptions',
            DPS_URL . '/assets/js/admin/dashboard/index.js',
            $asset_file['dependencies'],
            $asset_file['version'],
            [
                'strategy' => 'defer',
                'in_footer' => true,
            ]
        );

    }
}
