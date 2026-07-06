<?php

namespace WeDevs\DokanPro\Modules\ProductQA;

use WeDevs\DokanPro\Modules\ProductQA\Models\Answer;
use WeDevs\DokanPro\Modules\ProductQA\Models\Question;

defined( 'ABSPATH' ) || exit;

/**
 * Product QA Cache Class.
 *
 * @since 3.11.0
 */
class Cache {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'dokan_pro_product_qa_question_created', [ $this, 'clear_question_cache' ] );
        add_action( 'dokan_pro_product_qa_question_updated', [ $this, 'clear_question_cache' ] );
        add_action( 'dokan_pro_product_qa_question_deleted', [ $this, 'clear_question_cache' ] );
        add_action( 'dokan_pro_product_qa_answer_created', [ $this, 'clear_answer_cache' ] );
        add_action( 'dokan_pro_product_qa_answer_updated', [ $this, 'clear_answer_cache' ] );
        add_action( 'dokan_pro_product_qa_answer_deleted', [ $this, 'clear_answer_cache' ] );
    }

    /**
     * Clear question caches.
     *
     * @since 3.11.0
     *
     * @return void
     */
    public function clear_question_cache() {
        $cache_group = ( new Question() )->get_cache_group() . '_query';
        \WeDevs\Dokan\Cache::invalidate_group( $cache_group );
    }

    /**
     * Clear question caches.
     *
     * @since 3.11.0
     *
     * @return void
     */
    public function clear_answer_cache() {
        $cache_group = ( new Answer() )->get_cache_group() . '_query';
        \WeDevs\Dokan\Cache::invalidate_group( $cache_group );

        $this->clear_question_cache();
    }
}
