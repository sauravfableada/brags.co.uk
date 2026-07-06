<?php

namespace WeDevs\DokanPro\Modules\ProductQA;

use WeDevs\DokanPro\Modules\ProductQA\Models\Question;
use WeDevs\DokanPro\Modules\ProductQA\REST\AnswersApi;
use WeDevs\DokanPro\Modules\ProductQA\REST\QuestionsApi;

defined( 'ABSPATH' ) || exit;

/**
 * Rest API related class.
 *
 * @since 3.11.0
 */
class Api {

    /**
     * Constructor.
     */
    public function __construct() {
        // set action hooks
        add_filter( 'dokan_rest_api_class_map', [ $this, 'register_class_map' ] );
	    add_filter( 'dokan_rest_admin_dashboard_todo_data', [ $this, 'load_product_inquiries_count' ] );
    }

    /**
     * Rest api class map.
     *
     * @since 3.11.0
     *
     * @param array $classes API Classes.
     *
     * @return array
     */
    public function register_class_map( $classes ): array {
        $classes[ DOKAN_PRODUCT_QA_INC . '/REST/QuestionsApi.php' ] = QuestionsApi::class;
        $classes[ DOKAN_PRODUCT_QA_INC . '/REST/AnswersApi.php' ]   = AnswersApi::class;

        return $classes;
    }

	/**
	 * Load product inquiries count in the admin dashboard to-do data.
	 *
	 * @since 4.1.0
	 *
	 * @param array $data The existing to-do data.
	 *
	 * @return array The modified to-do data with product inquiries count.
	 */
	public function load_product_inquiries_count( array $data ): array {
		$question = new Question();
		$count    = $question->count_status( [ 'answered' => false ] );

		// If the count is not an object, we assume it to be zero.
		$data['product_inquiries'] = [
			'icon'         => 'MessageCircle',
			'count'        => $count->unanswered() ?? 0,
			'title'        => esc_html__( 'Product Q&A Inquiries', 'dokan' ),
            'redirect_url' => admin_url( 'admin.php?page=dokan#/product-qa?status=unanswered' ),
            'position'     => 70,
		];

		return $data;
	}
}
