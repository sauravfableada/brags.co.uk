<?php

namespace WeDevs\DokanPro\Modules\ProductQA;

use WeDevs\DokanPro\Modules\ProductQA\Models\Question;

defined( 'ABSPATH' ) || exit;

/**
 * Vendor class
 *
 * @since 3.11.0
 */
class Vendor {

    public const QUERY_VAR = 'product-questions-answers';

    /**
     * Constructor.
     */
    public function __construct() {
        add_filter( 'dokan_get_dashboard_nav', [ $this, 'register_menu' ] );
        add_filter( 'dokan_query_var_filter', [ $this, 'register_endpoints' ] );
        add_action( 'dokan_load_custom_template', [ $this, 'load_main_template' ] );
        add_action( 'dokan_product_qa_content_inside_before', [ $this, 'load_inside_before_content' ] );
        add_action( 'dokan_product_qa_inside_content', [ $this, 'load_filter_content' ] );
        add_action( 'dokan_product_qa_inside_content', [ $this, 'load_list_content' ], 11 );
        add_action( 'dokan_product_qa_inside_content', [ $this, 'load_single_content' ], 11 );
        add_action( 'dokan_product_qa_inside_header_content', [ $this, 'add_back_button_on_single_page' ], 8 );
        add_filter( 'dokan_product_qa_vendor_dashboard_title', [ $this, 'add_unread_question_count' ] );
    }

    /**
     * Register dashboard menu.
     *
     * @since 3.11.0
     *
     * @param array $urls URLs.
     *
     * @return array
     */
    public function register_menu( $urls ): array {
        if ( ! dokan_is_seller_enabled( dokan_get_current_user_id() ) ) {
            return $urls;
        }
        $urls[ self::QUERY_VAR ] = array(
            'title'       => apply_filters( 'dokan_product_qa_vendor_dashboard_title', __( 'Product Q&A', 'dokan' ) ),
            'icon'        => '<i class="far fa-question-circle" aria-hidden="true"></i>',
            'url'         => dokan_get_navigation_url( self::QUERY_VAR ),
            'pos'         => 80,
            'icon_name'   => 'MessageCircleQuestionMark',
            'permission'  => 'dokandar',
            'react_route' => self::QUERY_VAR,
        );

        return $urls;
    }


    /**
     * Return request endpoind
     *
     * @since 1.0.0
     *
     * @return array
     */
    public function register_endpoints( $query_var ) {
        $query_var[] = self::QUERY_VAR;

        return $query_var;
    }

    /**
     * Load rma template for vendor
     *
     * @since 1.0.0
     * @return void
     */
    public function load_main_template( $query_vars ) {
        if ( ! isset( $query_vars[ self::QUERY_VAR ] ) ) {
            return;
        }

        if ( ! current_user_can( 'dokandar' ) ) {
            dokan_get_template_part(
                'global/dokan-error', '', [
                    'deleted' => false,
                    'message' => __( 'You have no permission to view this requests page', 'dokan' ),
                ]
            );

            return;
        }
        dokan_get_template_part(
            'vendor-questions', '', [
                'is_product_qa' => true,
                'pro'           => true,
            ]
        );
    }

    /**
     * Load Inside before content.
     *
     * @return void
     */
    public function load_inside_before_content() {
        dokan_get_template_part(
            'vendor',
            'header',
            [
                'pro'           => true,
                'is_product_qa' => true,
            ]
        );

        if ( ! dokan_is_seller_enabled( dokan_get_current_user_id() ) ) {
            dokan_seller_not_enabled_notice();
        }
    }

    /**
     * Load list content.
     *
     * @since 3.11.0
     * @return void
     */
    public function load_list_content() {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        if ( ! empty( $_GET['question_id'] ) ) {
            return;
        }

        $args            = [];
        $pagination_html = '';
        $item_per_page   = 20;

        if ( ! empty( $_GET['status'] ) ) {
            $args['status'] = sanitize_text_field( wp_unslash( $_GET['status'] ) );
        }

        if ( ! empty( $_GET['read'] ) ) {
            $args['read'] = sanitize_text_field( wp_unslash( $_GET['read'] ) );
        }

        if ( ! empty( $_GET['search'] ) ) {
            $args['search'] = sanitize_text_field( wp_unslash( $_GET['search'] ) );
        }

        if ( ! empty( $_GET['product_id'] ) ) {
            $args['product_id'] = absint( wp_unslash( $_GET['product_id'] ) );
        }

        if ( ! empty( $_GET['answered'] ) ) {
            $args['answered'] = sanitize_text_field( wp_unslash( $_GET['answered'] ) ) === 'answered' ?
                Question::STATUS_ANSWERED : Question::STATUS_UNANSWERED;
        }

        $args['vendor_id'] = dokan_get_current_user_id();
        $total_count       = ( new Question() )->count( $args );
        $page              = isset( $_GET['pagenum'] ) ? absint( wp_unslash( $_GET['pagenum'] ) ) : 1;
        $offset            = ( $page - 1 ) * $item_per_page;
        $total_page        = ceil( $total_count / $item_per_page );
        $args['limit']     = $item_per_page;
        $args['offset']    = $offset;
        $args['order']     = 'DESC';
        $args['order_by']  = 'q.id'; // q is table short name here. the usage of q is essential.

        if ( $total_page > 1 ) {
            $pagination_html = '<div class="pagination-wrap">';
            $page_links      = paginate_links(
                array(
                    'base'      => add_query_arg( 'pagenum', '%#%' ),
                    'format'    => '',
                    'type'      => 'array',
                    'prev_text' => __( '&laquo; Previous', 'dokan' ),
                    'next_text' => __( 'Next &raquo;', 'dokan' ),
                    'total'     => $total_page,
                    'current'   => $page,
                )
            );
            $pagination_html .= '<ul class="pagination"><li>';
            $pagination_html .= join( "</li>\n\t<li>", $page_links );
            $pagination_html .= "</li>\n</ul>\n";
            $pagination_html .= '</div>';
        }

        try {
            $question_args = apply_filters( 'dokan_product_qa_question_args', $args );
            $questions     = ( new Question() )->query( $question_args );
        } catch ( \Exception $e ) {
            dokan_get_template_part(
                'global/dokan-error', '', [
                    'deleted' => false,
                    'message' => __( 'Invalid question query.', 'dokan' ),
                ]
            );

            return;
        }
        dokan_get_template_part(
            'vendor-questions', 'list', [
                'is_product_qa'   => true,
                'questions'       => $questions,
                'total_count'     => $total_count,
                'pagination_html' => $pagination_html,
            ]
        );
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
    }

    /**
     * Load list filter content.
     *
     * @since 3.11.0
     *
     * @return void
     */
    public function load_filter_content() {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        if ( ! empty( $_GET['question_id'] ) ) {
            return;
        }

        $answered      = '';
        $product_id    = 0;
        $product_title = '';

        if ( ! empty( $_GET['status'] ) ) {
            $args['status'] = sanitize_text_field( wp_unslash( $_GET['status'] ) );
        }

        if ( ! empty( $_GET['read'] ) ) {
            $args['read'] = sanitize_text_field( wp_unslash( $_GET['read'] ) );
        }

        if ( ! empty( $_GET['search'] ) ) {
            $args['search'] = sanitize_text_field( wp_unslash( $_GET['search'] ) );
        }

        if ( ! empty( $_GET['product_id'] ) ) {
            $args['product_id'] = absint( wp_unslash( $_GET['product_id'] ) );
            $product_id         = $args['product_id'];

            $product = wc_get_product( $product_id );
            if ( $product ) {
                $product_title = $product->get_title();
            }
        }

        if ( ! empty( $_GET['answered'] ) ) {
            $answered = sanitize_text_field( wp_unslash( $_GET['answered'] ) );
        }

        $args['vendor_id']       = dokan_get_current_user_id();
        $question_filter_options = apply_filters(
            'dokan_product_qa_vendor_question_filter_options',
            [
                'answered'   => esc_html__( 'Answered', 'dokan' ),
                'unanswered' => esc_html__( 'Unanswered', 'dokan' ),
            ]
        );

        dokan_get_template_part(
            'vendor-questions', 'filters', [
                'pro'                     => true,
                'is_product_qa'           => true,
                'filters'                 => $args,
                'answered'                => $answered,
                'product_id'              => $product_id,
                'product_title'           => $product_title,
                'vendor_id'               => $args['vendor_id'],
                'question_filter_options' => $question_filter_options,
            ]
        );
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
    }

    /**
     * Load single question content.
     *
     * @since 3.11.0
     *
     * @return void
     */
    public function load_single_content() {
        if ( empty( $_GET['question_id'] ) || isset( $_GET['action'] ) ) { // phpcs:ignore
            return;
        }

        try {
            $question = new Question( absint( wp_unslash( $_GET['question_id'] ) ) ); // phpcs:ignore
        } catch ( \Exception $e ) {
            dokan_get_template_part(
                'global/dokan-error',
                '',
                [
                    'deleted' => false,
                    'message' => esc_html__( 'Invalid question id.', 'dokan' ),
                ]
            );

            return;
        }

        if ( ! dokan_is_product_author( $question->get_product_id() ) ) {
            dokan_get_template_part(
                'global/dokan-error',
                '',
                [
                    'deleted' => false,
                    'message' => esc_html__( 'Error! this is not your product related question.', 'dokan' ),
                ]
            );

            return;
        }

        $product = wc_get_product( $question->get_product_id() );
        $answer  = $question->get_answer();
        dokan_get_template_part(
            'vendor-questions', 'single', [
                'pro'           => true,
                'is_product_qa' => true,
                'question'      => $question,
                'product'       => $product,
                'answer'        => $answer,
            ]
        );
    }

    /**
     * Add back button on single page.
     *
     * @since 3.11.0
     *
     * @return void
     */
    public function add_back_button_on_single_page() {
        global $wp;

        if ( ! isset( $wp->query_vars[ self::QUERY_VAR ] ) ) {
            return;
        }

        if ( empty( $_GET['question_id'] ) || isset( $_GET['action'] ) ) { // phpcs:ignore
            return;
        }

        dokan_get_template_part(
            'vendor-header', 'single', [
                'pro'           => true,
                'is_product_qa' => true,
                'url'           => dokan_get_navigation_url( self::QUERY_VAR ),
            ]
        );
    }

    /**
     * Add unread question count.
     *
     * @since 3.11.0
     *
     * @param string $title Title.
     *
     * @return string
     */
    public function add_unread_question_count( string $title ): string {
        $count = ( new Question() )->count(
            [
                'vendor_id' => dokan_get_current_user_id(),
                'answered'  => Question::STATUS_UNANSWERED,
            ]
        );

        if ( ! empty( $count ) ) {
            // translators: %1$s is the title of the menu, %2$s number of unanswered questions.
            $title = sprintf( __( '%1$s (%2$s)', 'dokan' ), $title, number_format_i18n( $count ) );
        }

        return $title;
    }
}
