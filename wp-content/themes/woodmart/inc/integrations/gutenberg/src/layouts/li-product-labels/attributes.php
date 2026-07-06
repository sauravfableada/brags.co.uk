<?php
/**
 * Loop Product Labels block attributes.
 *
 * @package woodmart
 */

use XTS\Gutenberg\Block_Attributes;

if ( ! function_exists( 'wd_get_loop_builder_product_labels_attrs' ) ) {
	/**
	 * Get Loop Product Labels block attributes.
	 *
	 * @return array[]
	 */
	function wd_get_loop_builder_product_labels_attrs() {
		$attr = new Block_Attributes();

		$attr->add_attr(
			array(
				'textAlign'   => array(
					'type'       => 'string',
					'responsive' => true,
				),
				'orientation' => array(
					'type'    => 'string',
					'default' => 'vertical',
				),
			)
		);

		wd_get_advanced_tab_attrs( $attr );

		return $attr->get_attr();
	}
}
