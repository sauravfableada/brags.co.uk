<?php

namespace WeDevs\DokanPro\Modules\ProductQA;

use WeDevs\DokanPro\Modules\ProductQA\Models\Question;

defined( 'ABSPATH' ) || exit;

/**
 * Product QA Ajax Class.
 *
 * @since 3.11.0
 */
class Ajax {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'wp_ajax_dokan_product_qa_answer_question', [ $this, 'answer_question' ] );
        add_action( 'wp_ajax_dokan_product_qa_delete_question_answer', [ $this, 'delete_answer' ] );
        add_action( 'wp_ajax_dokan_product_qa_delete_question', [ $this, 'delete_question' ] );
    }

    /**
     * Question answering ajax handler.
     *
     * @since 3.11.0
     */
    public function answer_question() {
        if ( ! isset( $_POST['_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_nonce'] ), 'dokan-product-qa-answer-save' ) ) {
            wp_send_json_error( __( 'Security verification failed.', 'dokan' ) );
        }

        $current_user = dokan_get_current_user_id();

        $question_id = isset( $_POST['question_id'] ) ? absint( wp_unslash( $_POST['question_id'] ) ) : 0;

        if ( empty( $question_id ) ) {
            wp_send_json_error( __( 'Question ID required.', 'dokan' ) );
        }

        try {
            $question = new Question( $question_id );
        } catch ( \Exception $e ) {
            wp_send_json_error( $e->getMessage() );
        }

        if ( ! ( current_user_can( 'manage_options' ) || ( current_user_can( 'dokandar' ) && dokan_is_product_author( $question->get_product_id() ) ) ) ) {
            wp_send_json_error( __( 'You are not permitted to do current action.', 'dokan' ) );
        }
        kses_remove_filters();
        $answer_text = isset( $_POST['answer'] ) ? sanitize_post_field( 'post_content', wp_kses_post( wp_unslash( $_POST['answer'] ) ),0, 'db' ) : '';
        kses_init_filters();
        if ( empty( $answer_text ) ) {
            wp_send_json_error( __( 'Please provide answer text.', 'dokan' ) );
        }

        try {
            $answer = $question->get_answer();
            $answer
                ->set_user_id( $current_user )
                ->set_answer( $answer_text );

            if ( $answer->get_id() ) {
                $answer->update();
            } else {
                $answer->create();
            }

        } catch ( \Exception $e ) {
            wp_send_json_error( $e->getMessage() );
        }

        wp_send_json_success( __( 'Answer saved successfully.', 'dokan' ) );
    }

    /**
     * Answer delete ajax handler.
     *
     * @since 3.11.0
     */
    public function delete_answer() {
        if ( ! isset( $_POST['_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_nonce'] ), 'dokan-product-qa-answer-delete' ) ) {
            wp_send_json_error( __( 'Security verification failed.', 'dokan' ) );
        }

        $question_id = isset( $_POST['question_id'] ) ? absint( wp_unslash( $_POST['question_id'] ) ) : 0;

        if ( empty( $question_id ) ) {
            wp_send_json_error( __( 'Question ID required.', 'dokan' ) );
        }

        try {
            $question = new Question( $question_id );
        } catch ( \Exception $e ) {
            wp_send_json_error( $e->getMessage() );
        }

        if ( ! ( current_user_can( 'manage_options' ) || ( current_user_can( 'dokandar' ) && dokan_is_product_author( $question->get_product_id() ) ) ) ) {
            wp_send_json_error( __( 'You are not permitted to do current action.', 'dokan' ) );
        }

        $answer = $question->get_answer();

        if ( ! $answer->get_id() ) {
            wp_send_json_error( __( 'No answer found.', 'dokan' ) );
        }

        try {
            $answer->delete();
        } catch ( \Exception $e ) {
            wp_send_json_error( $e->getMessage() );
        }

        wp_send_json_success( __( 'Answer deleted successfully.', 'dokan' ) );
    }

    /**
     * Question delete ajax handler.
     *
     * @since 3.11.0
     */
    public function delete_question() {
        if ( ! isset( $_POST['_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_nonce'] ), 'dokan-product-qa-delete-question' ) ) {
            wp_send_json_error( __( 'Security verification failed.', 'dokan' ) );
        }

        $question_id = isset( $_POST['question_id'] ) ? absint( wp_unslash( $_POST['question_id'] ) ) : 0;

        if ( empty( $question_id ) ) {
            wp_send_json_error( __( 'Question ID required.', 'dokan' ) );
        }

        try {
            $question = new Question( $question_id );
        } catch ( \Exception $e ) {
            wp_send_json_error( $e->getMessage() );
        }

        if ( ! ( current_user_can( 'manage_options' ) || ( current_user_can( 'dokandar' ) && dokan_is_product_author( $question->get_product_id() ) ) || $question->get_user_id() === dokan_get_current_user_id() ) ) {
            wp_send_json_error( __( 'You are not permitted to do current action.', 'dokan' ) );
        }

        try {
            $question->delete();
        } catch ( \Exception $e ) {
            wp_send_json_error( $e->getMessage() );
        }

        wp_send_json_success( __( 'Question deleted successfully.', 'dokan' ) );
    }
}
