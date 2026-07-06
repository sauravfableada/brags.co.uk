<?php
/**
 * The mobile search form template.
 *
 * @package woodmart
 */

woodmart_enqueue_inline_style( 'header-search' );

if ( 'form' === $params['display'] && ! empty( $params['bg_overlay'] ) ) {
	woodmart_enqueue_js_script( 'menu-overlay' );
}

if (
	'form' === $params['display'] ||
	(
		'full-screen' === $params['display'] &&
		! empty( $params['full_screen_opener'] ) &&
		'form' === $params['full_screen_opener']
	) ||
	(
		'full-screen-2' === $params['display'] &&
		(
			empty( $params['full_screen_opener'] ) ||
			'form' === $params['full_screen_opener']
		)
	)
) {
	woodmart_enqueue_inline_style( 'header-search-form' );

	woodmart_search_form( $params );

	return;
}

if ( '8' === $params['icon_design'] ) {
	woodmart_enqueue_inline_style( 'mod-tools-design-8' );
}

woodmart_enqueue_js_script( 'mobile-search' );
?>
<div class="wd-header-search wd-tools-element wd-header-search-mobile<?php echo esc_attr( $params['extra_class'] ); ?>">
	<a href="#" rel="nofollow noopener" aria-label="<?php esc_attr_e( 'Search', 'woodmart' ); ?>">
		<?php if ( $params['icon_wrapper'] ) : ?>
			<span class="wd-tools-inner">
		<?php endif; ?>

			<?php if ( 'text-only' !== $params['style'] ) : ?>
				<span class="wd-tools-icon">
					<?php if ( 'custom' === $params['icon_type'] ) : ?>
						<?php echo whb_get_custom_icon( $params['custom_icon'] ); // phpcs:ignore. ?>
					<?php endif; ?>
				</span>
			<?php endif; ?>

			<?php if ( 'icon' !== $params['style'] ) : ?>
				<span class="wd-tools-text">
					<?php echo esc_html__( 'Search', 'woodmart' ); ?>
				</span>
			<?php endif; ?>

		<?php if ( $params['icon_wrapper'] ) : ?>
			</span>
		<?php endif; ?>
	</a>

	<?php woodmart_search_form( $params ); ?>
</div>
