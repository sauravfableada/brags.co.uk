<?php
/**
 * Loop Product Labels block render.
 *
 * @package woodmart
 */

use XTS\Modules\Layouts\Loop_Item;

if ( ! function_exists( 'wd_gutenberg_loop_builder_product_labels' ) ) {
	/**
	 * Render Loop Product Labels block.
	 *
	 * @param array $block_attributes Block attributes.
	 * @return string
	 */
	function wd_gutenberg_loop_builder_product_labels( $block_attributes ) {
		if ( ! woodmart_woocommerce_installed() ) {
			return '';
		}

		$classes  = wd_get_gutenberg_element_classes( $block_attributes );
		$classes .= ' wd-orient-' . ( 'horizontal' === $block_attributes['orientation'] ? 'hor' : 'ver' );

		if ( ! empty( $block_attributes['textAlign'] ) || ! empty( $block_attributes['textAlignTablet'] ) || ! empty( $block_attributes['textAlignMobile'] ) ) {
			$classes .= ' wd-align';
		}

		Loop_Item::setup_postdata();

		global $product;

		$output = array();

		$shape         = woodmart_get_opt( 'label_shape', 'rounded' );
		$label_classes = ' product-label';

		if ( 'rounded-sm' === $shape ) {
			$label_classes .= ' wd-shape-round-sm';
		} elseif ( 'rectangular' === $shape ) {
			$label_classes .= ' wd-shape-rect-sm';
		} elseif ( 'rounded' === $shape ) {
			$label_classes .= ' wd-shape-round';
		}

		$product_attributes = woodmart_get_product_attributes_label( $label_classes );
		$percentage_label   = woodmart_get_opt( 'percentage_label' );

		if ( $product->is_on_sale() ) {
			$percentage = '';

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
			} elseif ( ( 'simple' === $product->get_type() || 'external' === $product->get_type() || 'variation' === $product->get_type() ) && $percentage_label ) {
				$percentage = round( ( ( (float) $product->get_regular_price() - (float) $product->get_sale_price() ) / (float) $product->get_regular_price() ) * 100 );
			}

			if ( $percentage ) {
				/* translators: %d: sale percentage value */
				$output[] = '<span class="onsale' . $label_classes . '">' . sprintf( _x( '-%d%%', 'sale percentage', 'woodmart' ), $percentage ) . '</span>';
			} else {
				$output[] = '<span class="onsale' . $label_classes . '">' . esc_html__( 'Sale', 'woodmart' ) . '</span>';
			}
		}

		if ( ! $product->is_in_stock() && 'thumbnail' === woodmart_get_opt( 'stock_status_position', 'thumbnail' ) ) {
			$output[] = '<span class="out-of-stock' . $label_classes . '">' . esc_html__( 'Sold out', 'woodmart' ) . '</span>';
		}

		if ( $product->is_featured() && woodmart_get_opt( 'hot_label' ) ) {
			$output[] = '<span class="featured' . $label_classes . '">' . esc_html__( 'Hot', 'woodmart' ) . '</span>';
		}

		if ( woodmart_get_opt( 'new_label' ) && woodmart_is_new_label_needed( get_the_ID() ) ) {
			$output[] = '<span class="new' . $label_classes . '">' . esc_html__( 'New', 'woodmart' ) . '</span>';
		}

		if ( $product_attributes ) {
			foreach ( $product_attributes as $attribute ) {
				$output[] = $attribute;
			}
		}

		$output = apply_filters( 'woodmart_product_label_output', $output );

		if ( ! $output ) {
			Loop_Item::reset_postdata();
			return '';
		}

		ob_start();

		woodmart_enqueue_inline_style( 'woo-mod-product-labels' );

		if ( 'rounded' === $shape ) {
			woodmart_enqueue_inline_style( 'woo-mod-product-labels-round' );
		}

		?>
		<div class="wd-loop-prod-labels product-labels<?php echo esc_attr( $classes ); ?>">
			<?php echo implode( ' ', $output ); // phpcs:ignore ?>
		</div>
		<?php

		Loop_Item::reset_postdata();

		return ob_get_clean();
	}
}
