<?php

namespace WeDevs\DokanPro\Announcement\Admin;

use WeDevs\Dokan\Admin\Dashboard\Pages\AbstractPage;

class Announcements extends AbstractPage {

    public function __construct() {
        $this->register_hooks();
    }

    public function get_id(): string {
        return 'announcements';
    }

    public function menu( string $capability, string $position ): array {
        return [
            'page_title' => __( 'Announcements', 'dokan' ),
            'menu_title' => __( 'Announcements', 'dokan' ),
            'route'      => 'announcement',
            'capability' => $capability,
            'position'   => 10,
        ];
    }

    public function settings(): array {
        $time_format = wc_time_format();

        return [
            'is_twelve_hour_format' => strpos( $time_format, 'a' ) !== false || strpos( $time_format, 'A' ) !== false,
        ];
    }

    public function scripts(): array {
        return [ 'dokan-announcement-admin' ];
    }

    public function styles(): array {
        return [];
    }

    public function register(): void {
        $admin = plugin_dir_path( DOKAN_PRO_FILE ) . 'assets/js/dokan-announcement-admin.asset.php';
        if ( file_exists( $admin ) ) {
            $admin = include $admin;

            wp_register_script(
                'dokan-announcement-admin',
                DOKAN_PRO_PLUGIN_ASSEST . '/js/dokan-announcement-admin.js',
                $admin['dependencies'],
                $admin['version'],
                true
            );
        }
    }
}
