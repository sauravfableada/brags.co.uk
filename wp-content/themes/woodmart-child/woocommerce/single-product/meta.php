<?php
/**
 * Single Product Meta
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/meta.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product, $wpdb;

$dangerous_good = get_post_meta($product->get_id(), '_is_dangerous_good', true);

$product_id = $product->get_id();
$has_multivendor = get_post_meta($product_id, '_has_multi_vendor', true);

$product = get_lowest_price_product($product_id);


?>
<div class="product_meta">

	<?php do_action( 'woocommerce_product_meta_start' ); ?>
	<?php 
	//$bragtag =  get_post_field( 'post_name', $product_id );
	$bragtag =  get_post_meta( $product_id, '_bragtag', true);
	if($bragtag){
		?>
		<span class="tag_wrapper"><?php esc_html_e( 'Brag Tag:', 'woocommerce' ); ?> <span class="tag" style="text-transform: uppercase;"><?php echo $bragtag; ?></span></span>

		<?php
	}
	?>

	<?php if ( wc_product_sku_enabled() && ( $product->get_sku() || $product->is_type( 'variable' ) ) ) : ?>

		<span class="sku_wrapper"><?php esc_html_e( 'SKU:', 'woocommerce' ); ?> <span class="sku"><?php echo ( $sku = $product->get_sku() ) ? $sku : esc_html__( 'N/A', 'woocommerce' ); ?></span></span>

	<?php endif; ?>

	<?php echo wc_get_product_category_list( $product->get_id(), ', ', '<span class="posted_in">' . _n( 'Category:', 'Categories:', count( $product->get_category_ids() ), 'woocommerce' ) . ' ', '</span>' ); ?>

	<?php echo wc_get_product_tag_list( $product->get_id(), ', ', '<span class="tagged_as">' . _n( 'Tag:', 'Tags:', count( $product->get_tag_ids() ), 'woocommerce' ) . ' ', '</span>' ); ?>
	
	<?php
	
	$is_dangerous_good = get_post_meta( $product->get_id(), '_is_dangerous_good', true );
    $sds_document = get_post_meta( $product->get_id(), '_sds_document', true );
	
	
	
	if ($is_dangerous_good === 'yes') {
		echo '<p class="danger-warning" style="color: #FF9800;">This product has been marked as a Dangerous Good by the Seller. Seller is entirely responsible for safe shipments.<a target="_blank" href="/dangerous-goods/"> Click here to Learn More about Dangerous Goods.</a> </p>';
	}else if ($is_dangerous_good === 'no'){
		echo '<p class="danger-warning" style="color: #FF9800;">This product has been marked a Non-Dangerous Good by the Seller.</p>';
	}
	?>
	<?php
		$age_restricted = get_post_meta($product->get_id(), '_age_restriction', true);

		if ($age_restricted === '1') : ?>
			<div class="bragsy-age-restriction-banner" style="border: 1px solid #e00000; background: #fff0f0; color: #a00000; padding: 10px; margin-top: 15px;">
				<strong>18+ Age Restricted Item</strong><br>
				This item is only available to buyers aged 18 or over. The seller is solely responsible for verifying age and ID in accordance with local laws.<br>
				<strong>Brags & Partners Ltd</strong> does not conduct age checks and accepts no liability for non-compliance.
			</div>
		<?php endif; ?>
	<?php do_action( 'woocommerce_product_meta_end' ); ?>

	


</div>
