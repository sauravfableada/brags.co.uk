<?php
/**
 * Loop Product Label block render.
 *
 * @package woodmart
 */

use XTS\Modules\Layouts\Loop_Item;

if ( ! function_exists( 'wd_gutenberg_loop_builder_product_label' ) ) {
	/**
	 * Render Loop Product Label block.
	 *
	 * @param array $block_attributes Block attributes.
	 * @return false|string
	 */
	function wd_gutenberg_loop_builder_product_label( $block_attributes ) {
		if ( ! woodmart_woocommerce_installed() ) {
			return '';
		}

		Loop_Item::setup_postdata();

		global $product;
		$content       = '';
		$shape         = woodmart_get_opt( 'label_shape' );
		$is_preview    = wp_is_serving_rest_request();
		$label_classes = ' product-label';

		if ( 'rounded-sm' === $shape ) {
			$label_classes .= ' wd-shape-round-sm';
		} elseif ( 'rectangular' === $shape ) {
			$label_classes .= ' wd-shape-rect-sm';
		} elseif ( 'rounded' === $shape ) {
			$label_classes .= ' wd-shape-round';
		}

		if ( 'sale' === $block_attributes['type'] && ( $is_preview || $product->is_on_sale() ) ) {
			$percentage       = '';
			$percentage_label = woodmart_get_opt( 'percentage_label' );

			if ( 'variable' === $product->get_type() && $percentage_label ) {
				$available_variations = $product->get_variation_prices();
				$max_percentage       = 0;

				foreach ( $available_variations['regular_price'] as $key => $regular_price ) {
					$sale_price = $available_variations['sale_price'][ $key ];

					if ( $sale_price < $regular_price ) {
						$percentage = round( ( ( (float) $regular_price - (float) $sale_price ) / (float) $regular_price ) * 100 );

						if ( $percentage > $max_percentage ) {
							$max_percentage = $percentage;
						}
					}
				}

				$percentage = $max_percentage;
			} elseif ( in_array( $product->get_type(), array( 'simple', 'external', 'variation' ), true ) && $percentage_label ) {
				$percentage = round( ( ( (float) $product->get_regular_price() - (float) $product->get_sale_price() ) / (float) $product->get_regular_price() ) * 100 );
			}

			if ( $percentage ) {
				if ( $is_preview ) {
					$percentage = 10;
				}

				// translators: %d is the percentage value.
				$content = '<span class="onsale' . $label_classes . '">' . sprintf( _x( '-%d%%', 'sale percentage', 'woodmart' ), $percentage ) . '</span>';
			} else {
				$content = '<span class="onsale' . $label_classes . '">' . esc_html__( 'Sale', 'woodmart' ) . '</span>';
			}
		}

		if ( 'out-of-stock' === $block_attributes['type'] && ( $is_preview || ! $product->is_in_stock() ) ) {
			$content = '<span class="out-of-stock' . $label_classes . '">' . esc_html__( 'Sold out', 'woodmart' ) . '</span>';
		}

		if ( 'hot' === $block_attributes['type'] && ( $is_preview || $product->is_featured() ) ) {
			$content = '<span class="featured' . $label_classes . '">' . esc_html__( 'Hot', 'woodmart' ) . '</span>';
		}

		if ( 'new' === $block_attributes['type'] && ( $is_preview || woodmart_is_new_label_needed( get_the_ID() ) ) ) {
			$content = '<span class="new' . $label_classes . '">' . esc_html__( 'New', 'woodmart' ) . '</span>';
		}

		if ( ! $content ) {
			Loop_Item::reset_postdata();

			return '';
		}

		ob_start();

		woodmart_enqueue_inline_style( 'woo-mod-product-labels' );

		if ( 'rounded' === $shape ) {
			woodmart_enqueue_inline_style( 'woo-mod-product-labels-round' );
		}

		$classes = wd_get_gutenberg_element_classes( $block_attributes );

		if ( ! empty( $block_attributes['textAlign'] ) || ! empty( $block_attributes['textAlignTablet'] ) || ! empty( $block_attributes['textAlignMobile'] ) ) {
			$classes .= ' wd-align';
		}

		?>
		<div class="wd-loop-prod-label<?php echo esc_attr( $classes ); ?>">
			<?php echo wp_kses( $content, true ); ?>
		</div>
		<?php

		Loop_Item::reset_postdata();

		return ob_get_clean();
	}
}
