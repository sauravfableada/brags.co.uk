<?php

namespace WeDevs\DokanPro\Modules\RankMath;

defined( 'ABSPATH' ) || exit;

use RankMath\Helper;
use RankMath\Helpers\WordPress;
use RankMath\ContentAI\Content_AI;
use RankMath\Admin\Admin_Helper;
use RankMath\Helpers\Url;

/**
 * Schema manger class
 *
 * @since 3.5.0
 */
class ContentAi extends Content_AI {

    /**
     * Class constructor
     *
     * @since 3.5.0
     */
    public function __construct() {
        //@phpstan-ignore-next-line
        parent::__construct();
        $this->hooks();
        $this->editor_scripts();
        $this->add_content_ai_json_data();
    }

    /**
     * Registers necessary hooks.
     *
     * @since 3.7.10
     *
     * @return void
     */
    public function hooks() {
        add_action( 'dokan_product_edit_inside_after_rank_math_seo', [ $this, 'render_content_ai_section' ] );
    }


    /**
     * Check content AI is enabled in rank math
     * @return bool
     */
    public function content_ai_is_enable(): bool {
        return Helper::is_module_active( 'content-ai' );
    }

    /**
     * Renders content ai section.
     *
     * @since 3.7.10
     *
     * @param int $product_id
     *
     * @return void
     */
    public function render_content_ai_section( $product_id ) {
        if ( ! $this->should_render_content_ai() ) {
            return;
        }

        ob_start();
        require_once DOKAN_RANK_MATH_TEMPLATE_PATH . '/content-ai.php';
        ob_end_flush();
    }

    /**
     * Enqueue assets for post editors.
     *
     * @since 3.5.0
     *
     * @return void
     */
    public function editor_scripts() {
        if ( ! $this->should_render_content_ai() ) {
            return;
        }

        wp_register_style(
            'rank-math-common',
            rank_math()->plugin_url() . 'assets/admin/css/common.css',
            array(),
            rank_math()->version
        );

        wp_enqueue_style(
            'rank-math-content-ai',
            rank_math()->plugin_url() . 'includes/modules/content-ai/assets/css/content-ai.css',
            [ 'rank-math-common' ],
            rank_math()->version
        );

        wp_enqueue_script(
            'rank-math-content-ai',
            rank_math()->plugin_url() . 'includes/modules/content-ai/assets/js/content-ai.js',
            [ 'rank-math-editor' ],
            rank_math()->version,
            true
        );
    }

    /**
     * Checks whether content ai should be rendered.
     *
     * @since 3.7.10
     *
     * @return boolean
     */
    public function should_render_content_ai() {
        return Helper::is_site_connected() && in_array( WordPress::get_post_type(), (array) Helper::get_settings( 'general.content_ai_post_types' ), true ) && $this->content_ai_is_enable();
    }

    /**
     * Add Content AI JSON data.
     *
     * @since 3.11.4
     *
     * @return void
     */
    public function add_content_ai_json_data() {
        Helper::add_json(
            'contentAI',
            [
                'audience'              => (array) Helper::get_settings( 'general.content_ai_audience', 'General Audience' ),
                'tone'                  => (array) Helper::get_settings( 'general.content_ai_tone', 'Formal' ),
                'language'              => Helper::get_settings( 'general.content_ai_language', Helper::content_ai_default_language() ),
                'history'               => Helper::get_outputs(),
                'chats'                 => Helper::get_chats(),
                'recentPrompts'         => Helper::get_recent_prompts(),
                'prompts'               => Helper::get_prompts(),
                'isUserRegistered'      => Helper::is_site_connected(),
                'connectData'           => Admin_Helper::get_registration_data(),
                'connectSiteUrl'        => Admin_Helper::get_activate_url( Url::get_current_url() ),
                'credits'               => Helper::get_content_ai_credits(),
                'plan'                  => Helper::get_content_ai_plan(),
                'errors'                => Helper::get_content_ai_errors(),
                'registerWriteShortcut' => version_compare( get_bloginfo( 'version' ), '6.2', '>=' ),
                'isMigrating'           => get_site_transient( 'rank_math_content_ai_migrating_user' ),
                'url'                   => defined( 'CONTENT_AI_URL' ) ? CONTENT_AI_URL . '/ai/' : 'https://cai.rankmath.com/ai/',
                'resetDate'             => Helper::get_content_ai_refresh_date() ? wp_date( 'Y-m-d g:ia', Helper::get_content_ai_refresh_date() ) : '',
            ]
        );
    }
}
