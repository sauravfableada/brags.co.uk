<?php

namespace WeDevs\DokanPro\Modules\ProductQA;

use WeDevs\DokanPro\Modules\ProductQA\Models\Question;

defined( 'ABSPATH' ) || exit;

/**
 * Admin class.
 *
 * @since 3.11.0
 */
class Admin {

    /**
     * Constructor.
     */
    public function __construct() {
        add_filter( 'dokan-admin-routes', [ $this, 'register_vue_routes' ] );
        add_action( 'dokan_admin_menu', [ $this, 'register_submenu' ], 11, 2 );
    }


    /**
     * Register Admin route for vue.
     *
     * @since 3.11.0
     *
     * @param array $routes Admin routes.
     *
     * @return array
     */
    public function register_vue_routes( $routes ): array {
        $routes[] = array(
            'path'      => '/product-qa',
            'name'      => 'ProductQuestionsAnswers',
            'component' => 'ProductQuestionsAnswers',
        );
        $routes[] = array(
            'path'      => '/product-qa/:id',
            'name'      => 'ProductQuestionsAnswersSingle',
            'component' => 'ProductQuestionsAnswersSingle',
        );
        return $routes;
    }

    /**
     * Load Admin menu
     *
     * @since 3.11.0
     *
     * @param  string $capability Capability.
     * @param  integer $menu_position Menu Position.
     *
     * @return void
     */
    public function register_submenu( $capability, $menu_position ) {
        global $submenu;

        if ( ! current_user_can( $capability ) ) {
            return;
        }

        $unanswered_count = ( new Question() )->count( [ 'answered' => Question::STATUS_UNANSWERED ] );
        $menu_text                  = __( 'Product Q&A', 'dokan' );
        $slug                       = 'dokan';

        if ( $unanswered_count ) {
            // translators: %s: number of unanswered Questions.
            $menu_text = sprintf( __( 'Product Q&A %s', 'dokan' ), '<span class="awaiting-mod count-1"><span class="pending-count">' . number_format_i18n( $unanswered_count ) . '</span></span>' );
        }

        $submenu[ $slug ][] = [ $menu_text, $capability, 'admin.php?page=' . $slug . '#/product-qa' ];
    }

}
