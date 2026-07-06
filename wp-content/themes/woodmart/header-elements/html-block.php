<?php
/**
 * HTML block element
 *
 * @package WoodMart
 */

woodmart_enqueue_inline_style( 'header-elements-base' );

$classes = ' whb-' . $id;
?>
<div class="wd-header-html wd-entry-content<?php echo esc_attr( $classes ); ?>">
	<?php echo woodmart_get_html_block( $params['block_id'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
