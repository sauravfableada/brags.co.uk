<?php
/**
 * Dokan vendor information template on product page
 *
 * @since   3.3.7
 *
 * @param Object $vendor
 * @param Array  $store_info
 * @param Array  $store_rating
 *
 * @package dokan
 */
?>
<style>
    .dokan-vendor-bragsy {
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 10px;
    }

    .bragsy-label {
        background: #0073aa; /* Bragsy Blue */
        color: white;
        font-weight: bold;
        padding: 15px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        width: 80px;
        height: 80px;
        text-align: center;
        box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.2);
    }
</style>
<div class="dokan-vendor-info-wrap">
    <div class="dokan-vendor-image">
        <img src="<?php echo esc_url( $vendor->get_avatar() ); ?>" alt="<?php echo esc_attr( $store_info['store_name'] ); ?>">
    </div>
    <div class="dokan-vendor-info">
        <div class="dokan-vendor-name">
            <a href="<?php echo esc_attr( $vendor->get_shop_url() ); ?>"><h5><?php echo esc_html( $store_info['store_name'] ); ?></h5></a>
            <?php do_action( 'dokan_product_single_after_store_name', $vendor ); ?>
        </div>
        <div class="dokan-vendor-rating">
            <?php if ( $store_rating['count'] ) : ?>
                <p><?php echo esc_html( $store_rating['rating'] ); ?></p>
            <?php endif; ?>
            <?php echo wp_kses_post( dokan_generate_ratings( $store_rating['rating'], 5 ) ); ?>
        </div>
        <?php if ( $store_rating['count'] ) : ?>
            <?php // translators: %d reviews count ?>
            <p class="dokan-ratings-count">(<?php echo esc_html( sprintf( _n( '%s Review', '%s Reviews', $store_rating['count'], 'dokan-lite' ), esc_html( number_format_i18n( $store_rating['count'] ) ) ) ); ?>)</p>
        <?php endif; ?>
        <div class="table-cell delivery review-delivery-time" style="width: 100%; display: flex; align-items: center;">
    <?php
        // Get the product-specific processing time
        $processing_time = dokan_get_shipping_processing_times();
        $product_id = get_the_ID(); // Get the current product ID
        $_processing_time = get_post_meta($product_id, '_dps_processing_time', true);
        $dps_pt = get_user_meta($product_id, '_dps_pt', true);
        $product_shipping_pt = !empty($_processing_time) ? $_processing_time : $dps_pt;
    ?>
    <span class="cell-title"><?php _e( 'Delivery Time', 'dokan' ); ?></span>
    <div class="woocommerce-product-rating">
        <?php
        // Ensure we have processing times and a valid product shipping processing time
        if ($processing_time && $product_shipping_pt != "") {
            $found = false; // Flag to track if we find a match
            foreach ($processing_time as $key => $p_time) {
                if ($product_shipping_pt == $key) {
                    echo esc_html($p_time); // Output the matching delivery time
                    $found = true;
                    break; // Exit loop once a match is found
                }
            }
            if (!$found) {
                echo esc_html('-'); // Display a fallback if no match found
            }
        } else {
            echo esc_html('-'); // Fallback if no processing time or product shipping time
        }
        ?>
    </div>
   
</div>
<div class="seller-view">
    <?php
        global $product;

        if ( ! function_exists( 'dokan_get_store_url' ) ) {
            return; 
        }

       $author_id   = get_post_field( 'post_author', $product->get_id() );
       $store_url   = dokan_get_store_url( $author_id );
       $store_name  = get_user_meta( $author_id, 'dokan_store_name', true );
    ?>

    <?php if ( $store_url && $store_name ) : ?>
        <div class="view-seller-store-link">
            <a href="<?php echo esc_url( $store_url ); ?>" class="button" style="text-transform: capitalize;letter-spacing: 1px;" target="_blank">View Seller's Store</a>
        </div>
    <?php endif; ?>
</div>

    </div>
    
    <?php
    
        //$is_bragsy = get_post_meta(get_the_ID(), '_is_bragsy_product', true);
        $is_bragsy = is_seller_bragsy_plan($vendor->get_id());
        $free_shipping = get_post_meta(get_the_ID(), '_bragsy_free_shipping', true);

        // if ($is_bragsy) {
        //     echo '<div class="dokan-vendor-bragsy">';
        //     echo '<div class="bragsy-label">Bragsy seller</div>';
        //     echo '</div>';
        // }

        if ($is_bragsy) {
        $logo_url = get_bragsy_seller_logo();
        
        echo '<div class="dokan-vendor-bragsy">';
        
        if ($logo_url) {
            echo '<div class="bragsy-label-logo" style="width: 60px; height: 60px;">';
            // Display the logo with alt text and proper dimensions
            echo '<img src="' . esc_url($logo_url) . '" alt="' . esc_attr__('Bragsy Seller', 'dokan-lite') . '" class="bragsy-logo" >';
            echo '</div>';
        } else {
            // Fallback to text label
             echo '<div class="bragsy-label">Bragsy seller</div>';
        }
        
        echo '</div>';
    }
    ?>
    
</div>

<?php 
if($is_bragsy){
    ?>
    <div class="bragsy-shipping-info">
        <strong>This seller has agreed to ship this order within 2 working days.</strong><br>
        <span style="color: purple; font-weight: bold;">
            If you are a Bragsy member, shipping on this product is <strong>completely free</strong>.
        </span>
        <br>
        For non-Bragsy members, shipping is <strong>£5.99</strong>.<br>
        <br>
        <span style="color: purple; font-weight: bold;">
        For unlimited free delivery on all Bragsy products for just <strong>£4.99 a month</strong>, 
        <?php 
        $signup_url = home_url('/my-account/brags-membership');
        echo sprintf(
            '<a href="%s" class="customer-signup-url brags-join-link">%s</a>',
            esc_url($signup_url),
            esc_html__('Click here to Join Today.', 'dokan-lite')
        );
        ?>
        </span>
    </div>
    <?php
}

