<?php
do_action( 'dokan_dashboard_wrap_start' );

use WeDevs\DokanPro\Products;
use WeDevs\Dokan\ProductCategory\Helper;
use WeDevs\Dokan\Walkers\TaxonomyDropdown;

?>
<div class="dokan-dashboard-wrap">
    <?php
    do_action( 'dokan_dashboard_content_before' );
    do_action( 'dokan_new_auction_product_content_before' );

    global $post;
    $auction_id              = $post->ID;
    $_downloadable           = get_post_meta( $auction_id, '_downloadable', true );
    $_virtual                = get_post_meta( $auction_id, '_virtual', true );
    $is_downloadable         = 'yes' === $_downloadable;
    $is_virtual              = 'yes' === $_virtual;
    $digital_mode            = dokan_get_option( 'global_digital_mode', 'dokan_general', 'sell_both' );
    $user_id                 = dokan_get_current_user_id();
    $processing_time         = dokan_get_shipping_processing_times();
    $_required_tax           = get_post_meta( $auction_id, '_required_tax', true );
    $_disable_shipping       = ( get_post_meta( $auction_id, '_disable_shipping', 'yes' ) ) ? get_post_meta( $auction_id, '_disable_shipping', 'yes' ) : 'no';
    $_additional_price       = get_post_meta( $auction_id, '_additional_price', true );
    $_additional_qty         = get_post_meta( $auction_id, '_additional_qty', true );
    $classes_options         = wc_get_product_tax_class_options();
    $is_proxy_auction_on     = get_option( 'simple_auctions_proxy_auction_on', 'no' );
    ?>

    <div class="dokan-dashboard-content">
        <?php

            /**
             *  dokan_auction_content_inside_before hook
             *
             *  @since 2.4
             */
            do_action( 'dokan_auction_content_inside_before' );
        ?>
        <header class="dokan-dashboard-header dokan-clearfix">
            <h1 class="entry-title">
                <?php _e( 'Add New Auction Product', 'dokan' ); ?>
            </h1>
        </header><!-- .entry-header -->
        <?php
        /**
         * Hook for `dokan_new_product_before_product_area`
         *
         * @since 3.5.2
         */
        do_action( 'dokan_new_product_before_product_area' );
        ?>
        <div class="dokan-new-product-area">
            <?php if ( Dokan_Template_Auction::$errors ) { ?>
                <div class="dokan-alert dokan-alert-danger">
                    <a class="dokan-close" data-dismiss="alert">&times;</a>
                    <?php foreach ( Dokan_Template_Auction::$errors as $error) { ?>
                        <strong><?php _e( 'Error!', 'dokan' ); ?></strong> <?php echo $error ?>.<br>
                    <?php } ?>
                </div>
            <?php } ?>

            <?php

            $can_sell = apply_filters( 'dokan_can_post', true );

            if ( $can_sell ) {

                if ( dokan_is_seller_enabled( get_current_user_id() ) ) { ?>

                    <form class="dokan-form-container dokan-auction-product-form" method="post">

                        <div class="product-edit-container dokan-clearfix">
                            <div class="content-half-part featured-image">
                                <div class="dokan-feat-image-upload dokan-new-product-featured-img">
                                    <div class="instruction-inside">
                                        <input type="hidden" name="feat_image_id" class="dokan-feat-image-id" value="0">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        <a href="#" class="dokan-feat-image-btn dokan-btn"><?php esc_html_e( 'Upload Product Image', 'dokan' ); ?></a>
                                    </div>

                                    <div class="image-wrap dokan-hide">
                                        <a class="close dokan-remove-feat-image">&times;</a>
                                            <img src="" alt="">
                                    </div>
                                </div>

                                <div class="dokan-product-gallery">
                                    <div class="dokan-side-body" id="dokan-product-images">
                                        <div id="product_images_container">
                                            <ul class="product_images dokan-clearfix">
                                                <li class="add-image add-product-images tips" data-title="<?php _e( 'Add gallery image', 'dokan' ); ?>">
                                                    <a href="#" class="add-product-images"><i class="fas fa-plus" aria-hidden="true"></i></a>
                                                </li>
                                            </ul>
                                            <input type="hidden" id="product_image_gallery" name="product_image_gallery" value="">
                                        </div>
                                    </div>
                                </div> <!-- .product-gallery -->
                                <?php

                                /**
                                 * Fires after the product gallery images section
                                 *
                                 * @since 3.12.2
                                 */
                                do_action( 'dokan_product_gallery_image_count' );
                                ?>
                            </div>

                            <div class="content-half-part dokan-product-meta">
                                <div class="dokan-form-group dokan-auction-post-title">
                                    <input required="required" class="dokan-form-control" name="post_title" id="post-title" type="text" placeholder="<?php esc_attr_e( 'Product name..', 'dokan' ); ?>" value="">
                                </div>

                                <div class="dokan-form-group dokan-auction-post-excerpt">
                                    <textarea name="post_excerpt" id="post-excerpt" rows="5" class="dokan-form-control" placeholder="<?php esc_attr_e( 'Short description about the product...', 'dokan' ); ?>"></textarea>
                                </div>

                                <div class="dokan-form-group dokan-auction-category">
                                    <?php
                                        $data = Helper::get_saved_products_category();
                                        $data['from'] = 'new_booking_product';

                                        dokan_get_template_part('products/dokan-category-header-ui', '', $data );
                                    ?>
                                </div>

                                <?php do_action( 'dokan_product_edit_after_pricing_fields', $post, $auction_id ); ?>

                                <div class="dokan-form-group dokan-auction-tags">
                                    <?php
                                    $drop_down_tags = wp_dropdown_categories( array(
                                        'show_option_none' => __( '', 'dokan' ),
                                        'hierarchical'     => 1,
                                        'hide_empty'       => 0,
                                        'name'             => 'product_tag[]',
                                        'id'               => 'product_tag',
                                        'taxonomy'         => 'product_tag',
                                        'title_li'         => '',
                                        'class'            => 'product_tags dokan-form-control dokan-select2',
                                        'exclude'          => '',
                                        'selected'         => array(),
                                        'echo'             => 0,
                                        'walker'           => new TaxonomyDropdown()
                                    ) );

                                    echo str_replace( '<select', '<select data-placeholder="'.__( 'Select product tags', 'dokan' ).'" multiple="multiple" ', $drop_down_tags );
                                    ?>
                                </div>

                                <?php do_action( 'dokan_auction_new_product_after_product_tags' ); ?>
                            </div>
                        </div>

                        <div class="product-edit-new-container">
                            <?php
                            dokan_get_template_part(
                                'products/download-virtual',
                                '',
                                [
                                    'post_id'         => $auction_id,
                                    'post'            => $post,
                                    'is_downloadable' => $is_downloadable,
                                    'is_virtual'      => $is_virtual,
                                    'digital_mode'    => $digital_mode,
                                    'class'           => 'show_if_subscription show_if_variable-subscription show_if_simple',
                                ]
                            );
                            ?>
                        </div>

                        <div class="product-edit-new-container">
                            <div class="dokan-edit-row dokan-auction-general-sections dokan-clearfix">

                                <div class="dokan-section-heading" data-togglehandler="dokan_product_inventory">
                                    <h2><i class="fas fa-cubes" aria-hidden="true"></i> <?php _e( 'General Options', 'dokan' ) ?></h2>
                                    <p><?php _e( 'Manage your auction product data', 'dokan' ); ?></p>
                                    <div class="dokan-clearfix"></div>
                                </div>

                                <div class="dokan-section-content">
                                    <div class="content-half-part dokan-auction-item-condition">
                                        <label class="dokan-control-label" for="_auction_item_condition"><?php _e( 'Item condition', 'dokan' ); ?></label>
                                        <div class="dokan-form-group">
                                            <select name="_auction_item_condition" class="dokan-form-control" id="_auction_item_condition">
                                                <option value="new"><?php _e( 'New', 'dokan' ) ?></option>
                                                <option value="used"><?php _e( 'Used', 'dokan' ) ?></option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="content-half-part dokan-auction-type">
                                        <label class="dokan-control-label" for="_auction_type"><?php _e( 'Auction type', 'dokan' ); ?></label>
                                        <div class="dokan-form-group">
                                            <select name="_auction_type" class="dokan-form-control" id="_auction_type">
                                                <option value="normal"><?php _e( 'Normal', 'dokan' ) ?></option>
                                                <option value="reverse"><?php _e( 'Reverse', 'dokan' ) ?></option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="dokan-clearfix"></div>

                                    <div class="dokan-form-group dokan-auction-proxy-bid">
                                        <div class="checkbox">
                                            <label for="_auction_proxy">
                                                <input type="checkbox" name="_auction_proxy" id="_auction_proxy" value="yes" <?php checked( $is_proxy_auction_on, 'no' ); ?> />
                                                <?php _e( 'Enable proxy bidding for this auction product', 'dokan' );?>
                                            </label>
                                        </div>
                                    </div>

                                    <?php if( get_option( 'simple_auctions_sealed_on', 'no' ) == 'yes') : ?>
                                        <div class="dokan-form-group dokan-auction-sealed-bid">
                                            <div class="checkbox">
                                                <label for="_auction_sealed">
                                                    <input type="checkbox" name="_auction_sealed" value="yes" id="_auction_sealed">
                                                    <?php _e( 'Enable sealed bidding for this auction product', 'dokan' );?>
                                                    <i class="fas fa-question-circle tips" data-title="<?php _e( 'In this type of auction all bidders simultaneously submit sealed bids so that no bidder knows the bid of any other participant. The highest bidder pays the price they submitted. If two bids with same value are placed for auction the one which was placed first wins the auction.', 'dokan' ); ?>"></i>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <div class="content-half-part dokan-auction-start-price">
                                        <label class="dokan-control-label" for="_auction_start_price"><?php _e( 'Start Price', 'dokan' ); ?></label>
                                        <div class="dokan-form-group">
                                            <div class="dokan-input-group">
                                                <span class="dokan-input-group-addon"><?php echo get_woocommerce_currency_symbol(); ?></span>
                                                <input required="required" class="wc_input_price dokan-form-control" name="_auction_start_price" id="_auction_start_price" type="text" placeholder="<?php echo wc_format_localized_price('9.99'); ?>" value="" style="width: 97%;">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="content-half-part dokan-auction-bid-increment">
                                        <label class="dokan-control-label" for="_auction_bid_increment"><?php _e( 'Bid increment', 'dokan' ); ?></label>
                                        <div class="dokan-form-group">
                                            <div class="dokan-input-group">
                                                <span class="dokan-input-group-addon"><?php echo get_woocommerce_currency_symbol(); ?></span>
                                                <input required="required" class="wc_input_price dokan-form-control" name="_auction_bid_increment" id="_auction_bid_increment" type="text" placeholder="<?php echo wc_format_localized_price( '9.99' ); ?>" value="">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="dokan-clearfix"></div>

                                    <div class="content-half-part dokan-auction-reserved-price">
                                        <label class="dokan-control-label" for="_auction_reserved_price"><?php _e( 'Reserved price', 'dokan' ); ?></label>
                                        <div class="dokan-form-group">
                                            <div class="dokan-input-group">
                                                <span class="dokan-input-group-addon"><?php echo get_woocommerce_currency_symbol(); ?></span>
                                                <input class="wc_input_price dokan-form-control" name="_auction_reserved_price" id="_auction_reserved_price" type="text" placeholder="<?php echo wc_format_localized_price('9.99'); ?>" value="" style="width: 97%;">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="content-half-part dokan-auction-regular-price">
                                        <label class="dokan-control-label" for="_regular_price"><?php _e( 'Buy it now price', 'dokan' ); ?></label>
                                        <div class="dokan-form-group">
                                            <div class="dokan-input-group">
                                                <span class="dokan-input-group-addon"><?php echo get_woocommerce_currency_symbol(); ?></span>
                                                <input class="wc_input_price dokan-form-control" name="_regular_price" id="_regular_price" type="text" placeholder="<?php echo wc_format_localized_price('9.99'); ?>" value="">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="dokan-clearfix"></div>

                                    <div class="content-half-part dokan-auction-dates-from">
                                        <label class="dokan-control-label" for="_auction_dates_from"><?php _e( 'Auction Start date', 'dokan' ); ?></label>
                                        <div class="dokan-form-group">
                                            <input required="required" class="dokan-form-control auction-datepicker" name="_auction_dates_from" id="_auction_dates_from" type="text" value="" style="width: 97%;" readonly>
                                        </div>
                                    </div>

                                    <div class="content-half-part dokan-auction-dates-to">
                                        <label class="dokan-control-label" for="_auction_dates_to"><?php _e( 'Auction End date', 'dokan' ); ?></label>
                                        <div class="dokan-form-group">
                                            <input required="required" class="dokan-form-control auction-datepicker" name="_auction_dates_to" id="_auction_dates_to" type="text" value="" readonly>
                                        </div>
                                    </div>

                                    <div class="dokan-clearfix"></div>

                                    <div class="dokan-form-group dokan-auction-extend-section">
                                        <div class="checkbox">
                                            <label for="_auction_extend_enable">
                                                <input type="hidden" name="_auction_extend_enable" value="no">
                                                <input type="checkbox" name="_auction_extend_enable" id="_auction_extend_enable" value="yes">
                                                <?php esc_html_e( 'Extend auction on bid?', 'dokan' ); ?>
                                                <span class="dokan-tooltips-help tips" title="<?php esc_html_e( 'If a bid is placed during the final moments of an auction then the auction can be extended for a specified amount of time. This gives other bidders a chance to compete.', 'dokan' ); ?>">
                                                    <i class="fas fa-question-circle"></i>
                                                </span>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="auction_extend_options" style="display: none">
                                        <div class="content-half-part dokan-auction-extend-in-time">
                                            <label class="dokan-control-label" for="_auction_extend_in_time"><?php esc_html_e( 'Extend auctions in last * seconds.', 'dokan' ); ?></label>
                                            <span class="dokan-tooltips-help tips" title="<?php esc_html_e( 'Extend auction in last N seconds. Enter 0 to extend always.', 'dokan' ); ?>">
                                                <i class="fas fa-question-circle"></i>
                                            </span>
                                            <div class="dokan-form-group">
	                                            <?php
	                                            dokan_post_input_box(
		                                            $auction_id,
		                                            '_auction_extend_in_time',
		                                            array(
			                                            'min'   => 0,
			                                            'step'  => 'any',
			                                            'id'    => '_auction_extend_in_time',
			                                            'name'  => '_auction_extend_in_time',
			                                            'class' => 'dokan-form-control',
		                                            ),
		                                            'number'
	                                            );
	                                            ?>
                                            </div>
                                        </div>
                                        <div class="content-half-part dokan-auction-extend-for-time">
                                            <label class="dokan-control-label" for="_auction_extend_for_time"><?php esc_html_e( 'Extend auctions for * seconds.', 'dokan' ); ?></label>
                                            <span class="dokan-tooltips-help tips" title="<?php esc_html_e( 'Extend auction for number of seconds.', 'dokan' ); ?>">
                                                <i class="fas fa-question-circle"></i>
                                            </span>
                                            <div class="dokan-form-group">
	                                            <?php
	                                            dokan_post_input_box(
		                                            $auction_id,
		                                            '_auction_extend_for_time',
		                                            array(
			                                            'min'   => 0,
			                                            'step'  => 'any',
			                                            'id'    => '_auction_extend_for_time',
			                                            'name'  => '_auction_extend_for_time',
			                                            'class' => 'dokan-form-control',
		                                            ),
		                                            'number'
	                                            );
	                                            ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="auction_relist_section">
                                        <div class="dokan-form-group dokan-auction-automatic-relist">
                                            <div class="dokan-text-left">
                                                <div class="checkbox">
                                                    <label for="_auction_automatic_relist">
                                                        <input type="hidden" name="_auction_automatic_relist" value="no">
                                                        <input type="checkbox" name="_auction_automatic_relist" id="_auction_automatic_relist" value="yes">
                                                        <?php _e( 'Enable automatic relisting for this auction', 'dokan' );?>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="relist_options" style="display: none">
                                            <div class="dokan-w3 dokan-auction-relist-fail-time">
                                                <label class="dokan-control-label" for="_auction_relist_fail_time"><?php _e( 'Relist if fail after n hours', 'dokan' ); ?></label>
                                                <div class="dokan-form-group">
                                                    <input class="dokan-form-control" name="_auction_relist_fail_time" id="_auction_relist_fail_time" type="number">
                                                </div>
                                            </div>
                                            <div class="dokan-w3 dokan-auction-relist-not-paid-time">
                                                <label class="dokan-control-label" for="_auction_relist_not_paid_time"><?php _e( 'Relist if not paid after n hours', 'dokan' ); ?></label>
                                                <div class="dokan-form-group">
                                                    <input class="dokan-form-control" name="_auction_relist_not_paid_time" id="_auction_relist_not_paid_time" type="number">
                                                </div>
                                            </div>
                                            <div class="dokan-w3 dokan-auction-relist-duration">
                                                <label class="dokan-control-label" for="_auction_relist_duration"><?php _e( 'Relist auction duration in h', 'dokan' ); ?></label>
                                                <div class="dokan-form-group">
                                                    <input class="dokan-form-control" name="_auction_relist_duration" id="_auction_relist_duration" type="number">
                                                </div>
                                            </div>
                                            <div class="dokan-clearfix"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="product-edit-new-container auction-product-downloadable product-edit-container dokan-hide">
                            <?php
                            dokan_get_template_part(
                                'products/downloadable',
                                '',
                                [
                                    'post_id' => $auction_id,
                                    'post'    => $post,
                                    'class'   => 'show_if_downloadable',
                                ]
                            );
                            ?>
                        </div>

                        <div class="product-edit-new-container dokan-product-shipping-tax" data-togglehandler="dokan_product_shipping_tax">
                            <?php
                            $is_shipping_disabled = 'sell_digital' === dokan_pro()->digital_product->get_selling_product_type();

                            dokan_get_template_part(
                                'products/product-shipping-content',
                                '',
                                [
                                    'pro'                     => true,
                                    'post'                    => $post,
                                    'post_id'                 => $auction_id,
                                    'user_id'                 => $user_id,
                                    'processing_time'         => $processing_time,
                                    '_required_tax'           => $_required_tax,
                                    '_disable_shipping'       => $_disable_shipping,
                                    '_additional_price'       => $_additional_price,
                                    '_additional_qty'         => $_additional_qty,
                                    'classes_options'         => $classes_options,
                                    'is_shipping_disabled'    => $is_shipping_disabled,
                                ]
                            );
                            ?>
                        </div>

                        <div class="dokan-form-group dokan-auction-post-content">
                            <?php
                            wp_editor(
                                Dokan_Template_Auction::$post_content,
                                'post_content',
                                array(
                                    'editor_height' => 50,
                                    'quicktags'     => false,
                                    'media_buttons' => false,
                                    'teeny'         => true,
                                    'editor_class'  => 'post_content',
                                )
                            );
                            ?>
                        </div>

                        <?php do_action( 'dokan_new_auction_product_form' ); ?>

                        <div class="dokan-form-group">
                            <input type="hidden" name="product-type" value="auction">
                            <?php wp_nonce_field( 'dokan_add_new_auction_product', 'dokan_add_new_auction_product_nonce' ); ?>
                            <input type="submit" name="add_auction_product" class="dokan-btn dokan-btn-theme dokan-btn-lg dokan-right" value="<?php esc_attr_e( 'Add auction Product', 'dokan' ); ?>"/>
                        </div>

                    </form>

                <?php } else { ?>

                    <?php dokan_seller_not_enabled_notice(); ?>

                <?php } ?>

            <?php } else { ?>

                <?php do_action( 'dokan_can_post_notice' ); ?>

            <?php } ?>
        <?php

            /**
             *  dokan_auction_content_inside_after hook
             *
             *  @since 2.4
             */
            do_action( 'dokan_auction_content_inside_after' );
        ?>
    </div> <!-- #primary .content-area -->

     <?php
        /**
         * Hook dokan_dashboard_content_after
         * Hook dokan_withdraw_content_after
         *
         * @since 2.4
         */
        do_action( 'dokan_dashboard_content_after' );
        do_action( 'dokan_new_auction_product_content_after' );
    ?>
</div><!-- .dokan-dashboard-wrap -->

<?php do_action( 'dokan_dashboard_wrap_end' ); ?>

<script>
    ;(function($){
        $(document).ready(function(){
            let requiredInputs = [
                $('input[name="post_title"]'),
                $('input[name="_auction_start_price"]'),
                $('input[name="_auction_bid_increment"]'),
                $('input[name="_auction_dates_from"]'),
                $('input[name="_auction_dates_to"]'),
            ];

            $('.dokan-form-container.dokan-auction-product-form').submit( function (e) {
                let validated = true;

                $.each( requiredInputs, ( index, item ) => {
                    validated && ! item.val() ? validated = false : '';
                    item.val() ? item.css( 'border', '0px solid transparent' ) : item.css( 'border', '1px solid red' );
                    item.attr( 'required', 'required' );
                } )

                if ( validated ) {
                    e.target.submit();
                }

                return validated;
            });

            $('.auction-datepicker').datetimepicker({
                dateFormat : 'yy-mm-dd',
                currentText: dokan.datepicker.now,
                closeText: dokan.datepicker.done,
                timeText: dokan.datepicker.time,
                hourText: dokan.datepicker.hour,
                minuteText: dokan.datepicker.minute
            });

            $('#_auction_automatic_relist').on( 'click', function(){
              if($(this).prop('checked')){
                  $('.relist_options').show();
              }else{
                  $('.relist_options').hide();
              }
            });

            $('.dokan-auction-proxy-bid').on('change', 'input#_auction_proxy', function() {
                if( $(this).prop('checked') ) {
                    $('.dokan-auction-sealed-bid').hide();
                } else {
                    $('.dokan-auction-sealed-bid').show();
                }
            });

            $('.dokan-auction-sealed-bid').on('change', 'input#_auction_sealed', function() {
                if ( $(this).prop('checked') ) {
                    $('.dokan-auction-proxy-bid').hide();
                } else {
                    $('.dokan-auction-proxy-bid').show();
                }
            });
            $('input#_auction_proxy').trigger('change');
            $('input#_auction_sealed').trigger('change');

            // Handle extend auction checkbox
            $('#_auction_extend_enable').on('change', function() {
                if ($(this).prop('checked')) {
                    $('.auction_extend_options').show();
                } else {
                    $('.auction_extend_options').hide();
                }
            });
        });
    })(jQuery)

</script>
