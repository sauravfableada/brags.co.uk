<?php

namespace WeDevs\DokanPro\Modules\VendorVerification\Admin\Dashboard\Pages;

use WeDevs\Dokan\Admin\Dashboard\Pages\AbstractPage;
use WeDevs\DokanPro\Modules\VendorVerification\Models\VerificationRequest;

class Verification extends AbstractPage {

    /**
     * Get the ID of the page.
     *
     * @since 4.1.4
     *
     * @return string
     */
    public function get_id(): string {
        return 'verifications';
    }

    /**
     * @inheritDoc
     */
    public function menu( string $capability, string $position ): array {
        $request_count = ( new VerificationRequest() )->count();
        $pending_count = $request_count[ VerificationRequest::STATUS_PENDING ] ?? 0;

        $menu_title = $pending_count ? sprintf(
            /* translators: %s: Pending verification request count badge */
            __( 'Verifications %s', 'dokan' ),
            '<span class="awaiting-mod count-1"><span class="pending-count">'
            . number_format_i18n( $pending_count )
            . '</span></span>'
        ) : esc_html__( 'Verifications', 'dokan' );

        return [
            'page_title' => esc_html__( 'Vendor Verifications', 'dokan' ),
            'menu_title' => $menu_title,
            'route'      => 'verifications',
            'capability' => $capability,
            'position'   => 25,
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
        return [ 'dokan-vendor-verification-admin' ];
    }

    /**
     * Get the styles.
     *
     * @since 4.1.4
     *
     * @return array<string> An array of style handles.
     */
    public function styles(): array {
        return [];
    }

    /**
     * Register the page scripts and styles.
     *
     * @since 4.1.4
     *
     * @return void
     */
    public function register(): void {
        $asset_file_path = DOKAN_VERFICATION_DIR . '/assets/js/admin/dashboard/index.asset.php';

        if ( file_exists( $asset_file_path ) ) {
            $asset_file = include $asset_file_path;

            wp_register_script(
                'dokan-vendor-verification-admin',
                DOKAN_VERFICATION_PLUGIN_ASSEST . '/js/admin/dashboard/index.js',
                $asset_file['dependencies'],
                $asset_file['version'],
                [
                    'strategy' => 'defer',
                    'in_footer' => true,
                ]
            );

            wp_set_script_translations(
                'dokan-vendor-verification-admin',
                'dokan'
            );
        }
    }
}
