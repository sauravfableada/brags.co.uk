<?php

namespace WeDevs\DokanPro\Modules\ProductQA;

use WeDevs\DokanPro\Modules\ProductQA\Emails\Admin;
use WeDevs\DokanPro\Modules\ProductQA\Emails\Vendor;
use WeDevs\DokanPro\Modules\ProductQA\Emails\Customer;

defined( 'ABSPATH' ) || exit;

/**
 * Dokan Product QA Emails class.
 *
 * @since 3.11.0
 */
class Emails {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'dokan_email_classes', [ $this, 'register' ] );
		add_filter( 'dokan_email_list', [ $this, 'register_templates' ] );
		add_filter( 'dokan_email_actions', [ $this, 'register_actions' ] );
	}

	/**
	 * Register email classes.
	 *
	 * @since @3.11.0
	 *
	 * @param array $emails Emails.
	 *
	 * @return array
	 */
	public function register( array $emails ): array {
		$emails['Dokan_Email_Product_QA_Admin'] = new Admin();
		$emails['Dokan_Email_Product_QA_Vendor'] = new Vendor();
		$emails['Dokan_Email_Product_QA_Customer'] = new Customer();

		return $emails;
	}

	/**
	 * Register email templates.
	 *
	 * @since @3.11.0
	 *
	 * @param array $templates Email Templates.
	 *
	 * @return array
	 */
	public function register_templates( array $templates ): array {
		$templates[] = 'dokan-product-qa-admin.php';
		$templates[] = 'dokan-product-qa-vendor.php';
		$templates[] = 'dokan-product-qa-customer.php';

		return $templates;
	}

	/**
	 * Register email actions.
	 *
	 * @since @3.11.0
	 *
	 * @param array $actions Email actions.
	 *
	 * @return array
	 */
	public function register_actions( array $actions ): array {
		$actions[] = 'dokan_pro_product_qa_question_created';
		$actions[] = 'dokan_pro_product_qa_answer_created';

		return $actions;
	}
}
