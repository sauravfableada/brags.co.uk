<?php

namespace WeDevs\DokanPro\Modules\VendorVerification;

use WeDevs\DokanPro\Modules\VendorVerification\Widgets\VerifiedMethodsList;

defined( 'ABSPATH' ) || exit;

/**
 * Widget Class.
 *
 * @since 3.11.1
 */
class Widget {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'dokan_widgets', [ $this, 'register_widgets' ] );
        add_action( 'dokan_sidebar_store_after', [ $this, 'show_verification_widget' ] );
    }

    /**
     * Register widgets
     *
     * @since 2.8
     * @since 3.10.2 Updated to comply with `dokan-lite` widget registration process
     *
     * @param array $widgets List of widgets to be registered
     *
     * @return array
     */
    public function register_widgets( array $widgets ): array {
        $widgets[ VerifiedMethodsList::INSTANCE_KEY ] = VerifiedMethodsList::class;
        return $widgets;
    }

    /**
     * Show verification widgets.
     *
     * @since unknown
     * @since 3.11.1 Migrated to the class.
     *
     * @return void
     */
    public function show_verification_widget() {
        if ( ! is_active_sidebar( 'sidebar-store' ) ) {
            $args = [
                'before_widget' => '<aside class="widget">',
                'after_widget'  => '</aside>',
                'before_title'  => '<h3 class="widget-title">',
                'after_title'   => '</h3>',
            ];
            the_widget( VerifiedMethodsList::class, [ 'title' => __( 'Verifications', 'dokan' ) ], $args );
        }
    }
}
