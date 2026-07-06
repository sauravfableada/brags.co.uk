<?php
/**
 * Amazon Product Mapping for Dokan
 * Path: inc/amazon/amazon-product-mapping.php
 */

// 1. Add Amazon Fields to Dokan Product Edit Page
add_action('dokan_product_edit_after_inventory_variants', 'wpla_add_amazon_mapping_fields', 15);
function wpla_add_amazon_mapping_fields($post)
{
    if (is_object($post)) {
        $post_id = $post->ID;
    } else {
        $post_id = $post;
    }

    if (!class_exists('WPLA_ListingsModel'))
        return;

    $asin = get_post_meta($post_id, '_wpla_asin', true);
    $sku = get_post_meta($post_id, '_wpla_amazon_sku', true);

    $listingsModel = new WPLA_ListingsModel();
    $status = $listingsModel->getStatusFromPostID($post_id);
    ?>
    <div class="amazon-mapping-section">
        <hr>
        <h3>
            <?php _e('Amazon Mapping (FBA/MCF)', 'dokan'); ?>
        </h3>
        <input type="hidden" name="post_ID" value="<?php echo esc_attr($post_id); ?>">

        <div class="dokan-form-group">
            <label for="_wpla_asin">
                <?php _e('Amazon ASIN', 'dokan'); ?>
            </label>
            <input type="text" name="_wpla_asin" id="_wpla_asin" class="dokan-form-control"
                data-post-id="<?php echo esc_attr($post_id); ?>"
                value="<?php echo esc_attr($asin); ?>" placeholder="e.g. B0XXXXXXXX">
            <small>
                <?php _e('Enter the Amazon ASIN to link this product for FBA/MCF fulfillment.', 'dokan'); ?>
            </small>
        </div>

        <div class="dokan-form-group">
            <label for="_wpla_amazon_sku">
                <?php _e('Amazon SKU (Optional)', 'dokan'); ?>
            </label>
            <input type="text" name="_wpla_amazon_sku" id="_wpla_amazon_sku" class="dokan-form-control"
                value="<?php echo esc_attr($sku); ?>" placeholder="Leave empty to use Brags SKU">
            <small>
                <?php _e('Only fill this if your SKU on Amazon is different from your SKU on Brags.', 'dokan'); ?>
            </small>
        </div>

        <div class="dokan-form-group">
            <?php if ($status && !in_array($status, array('unlisted'))): ?>
                <label><?php _e('Amazon Status:', 'dokan'); ?> <strong><?php echo ucfirst($status); ?></strong></label>
                <br>
                <small><?php _e('This product is already linked to Amazon.', 'dokan'); ?></small>
                <input type="hidden" name="_wpla_list_on_amazon_already_active" value="yes">
            <?php else: ?>
                <label>
                    <input type="checkbox" name="wpla_list_on_amazon" value="yes"
                        onchange="jQuery('#wpla_profile_selection').toggle(this.checked);">
                    <?php _e('List on Amazon', 'dokan'); ?>
                </label>
                <div id="wpla_profile_selection"
                    style="display:none; margin-top: 10px; padding: 10px; background: #f9f9f9; border-left: 3px solid #7ed321;">
                    <label for="wpla_list_profile"><?php _e('Select Amazon Profile', 'dokan'); ?></label>
                    <select name="wpla_list_profile" id="wpla_list_profile" class="dokan-form-control">
                        <?php
                        $pm = new WPLA_AmazonProfile();
                        $profiles = $pm->getAll();
                        $vendor_id = get_current_user_id();

                        // Filter profiles to only show those belonging to the current vendor's account
                        $account_id = function_exists('brags_get_seller_amazon_account_id') ? brags_get_seller_amazon_account_id($vendor_id) : 0;

                        if ($profiles):
                            foreach ($profiles as $profile):
                                if ($account_id && intval($profile->account_id) !== intval($account_id))
                                    continue;
                                echo '<option value="' . esc_attr($profile->profile_id) . '">' . esc_html($profile->profile_name) . '</option>';
                            endforeach;
                        else:
                            echo '<option value="">' . __('No profiles found', 'dokan') . '</option>';
                        endif;
                        ?>
                    </select>
                    <small><?php _e('Choose the template to use for this Amazon listing.', 'dokan'); ?></small>
                </div>
            <?php endif; ?>
        </div>

        <div class="dokan-form-group">
            <label>
                <input type="checkbox" name="_wpla_list_on_amazon_sync" value="yes" <?php checked(get_post_meta($post_id, '_wpla_list_on_amazon', true), 'yes'); ?>>
                <?php _e('Enable Inventory Sync', 'dokan'); ?>
            </label>
            <br>
            <small><?php _e('Allow automatic stock updates from Amazon for this product.', 'dokan'); ?></small>
        </div>
    </div> <!-- .amazon-mapping-section -->

    <!-- Amazon Options Section -->
    <hr>
    <h3><?php _e('Amazon Options', 'dokan'); ?></h3>

    <div class="dokan-form-group">
        <label for="wpl_amazon_product_id"><?php _e('Product ID', 'dokan'); ?></label>
        <input type="text" name="wpl_amazon_product_id" id="wpl_amazon_product_id" class="dokan-form-control"
            value="<?php echo esc_attr(get_post_meta($post_id, '_amazon_product_id', true)); ?>" placeholder="UPC or EAN">
        <small><?php _e('A standard, alphanumeric string that uniquely identifies the product (UPC or EAN).', 'dokan'); ?></small>
    </div>

    <div class="dokan-form-group">
        <label for="wpl_amazon_id_type"><?php _e('Product ID Type', 'dokan'); ?></label>
        <select name="wpl_amazon_id_type" id="wpl_amazon_id_type" class="dokan-form-control">
            <option value=""><?php _e('-- use profile setting --', 'dokan'); ?></option>
            <option value="UPC" <?php selected(get_post_meta($post_id, '_amazon_id_type', true), 'UPC'); ?>>UPC</option>
            <option value="EAN" <?php selected(get_post_meta($post_id, '_amazon_id_type', true), 'EAN'); ?>>EAN</option>
        </select>
    </div>

    <div class="dokan-form-group">
        <label for="wpl_amazon_title"><?php _e('Listing title', 'dokan'); ?></label>
        <input type="text" name="wpl_amazon_title" id="wpl_amazon_title" class="dokan-form-control"
            value="<?php echo esc_attr(get_post_meta($post_id, '_amazon_title', true)); ?>"
            placeholder="Custom listing title">
    </div>

    <div class="dokan-form-group">
        <label for="wpl_amazon_price"><?php _e('Amazon Price', 'dokan'); ?></label>
        <input type="text" name="wpl_amazon_price" id="wpl_amazon_price" class="dokan-form-control"
            value="<?php echo esc_attr(get_post_meta($post_id, '_amazon_price', true)); ?>" placeholder="Custom Price">
    </div>

    <div class="dokan-form-group">
        <label for="wpl_amazon_external_repricer"><?php _e('External Repricer', 'dokan'); ?></label>
        <select name="wpl_amazon_external_repricer" id="wpl_amazon_external_repricer" class="dokan-form-control">
            <option value="0" <?php selected(get_post_meta($post_id, '_amazon_external_repricer', true), '0'); ?>>
                <?php _e('-- no external repricer --', 'dokan'); ?>
            </option>
            <option value="1" <?php selected(get_post_meta($post_id, '_amazon_external_repricer', true), '1'); ?>>
                <?php _e('Use external repricer', 'dokan'); ?>
            </option>
        </select>
    </div>

    <div class="dokan-form-group">
        <label for="wpl_amazon_restock_date"><?php _e('Restock Date', 'dokan'); ?></label>
        <input type="text" name="wpl_amazon_restock_date" id="wpl_amazon_restock_date" class="dokan-form-control"
            value="<?php echo esc_attr(get_post_meta($post_id, '_amazon_restock_date', true)); ?>" placeholder="MM/DD/YYYY">
    </div>

    <div class="dokan-form-group">
        <label for="wpl_amazon_condition_type"><?php _e('Item Condition', 'dokan'); ?></label>
        <select name="wpl_amazon_condition_type" id="wpl_amazon_condition_type" class="dokan-form-control">
            <option value=""><?php _e('-- use profile setting --', 'dokan'); ?></option>
            <option value="new_new" <?php selected(get_post_meta($post_id, '_amazon_condition_type', true), 'new_new'); ?>>
                <?php _e('New', 'dokan'); ?>
            </option>
            <option value="used_like_new" <?php selected(get_post_meta($post_id, '_amazon_condition_type', true), 'used_like_new'); ?>><?php _e('Used - Like New', 'dokan'); ?></option>
            <option value="used_very_good" <?php selected(get_post_meta($post_id, '_amazon_condition_type', true), 'used_very_good'); ?>><?php _e('Used - Very Good', 'dokan'); ?></option>
            <option value="used_good" <?php selected(get_post_meta($post_id, '_amazon_condition_type', true), 'used_good'); ?>><?php _e('Used - Good', 'dokan'); ?></option>
            <option value="used_acceptable" <?php selected(get_post_meta($post_id, '_amazon_condition_type', true), 'used_acceptable'); ?>><?php _e('Used - Acceptable', 'dokan'); ?></option>
            <option value="refurbished_refurbished" <?php selected(get_post_meta($post_id, '_amazon_condition_type', true), 'refurbished_refurbished'); ?>><?php _e('Refurbished', 'dokan'); ?></option>
        </select>
    </div>

    <div class="dokan-form-group">
        <label for="wpl_amazon_condition_note"><?php _e('Condition Note', 'dokan'); ?></label>
        <textarea name="wpl_amazon_condition_note" id="wpl_amazon_condition_note" class="dokan-form-control"
            rows="3"><?php echo esc_textarea(get_post_meta($post_id, '_amazon_condition_note', true)); ?></textarea>
    </div>

    <?php for ($i = 1; $i <= 5; $i++): ?>
        <div class="dokan-form-group">
            <label for="wpl_amazon_bullet_point<?php echo $i; ?>"><?php printf(__('Bullet Point %d', 'dokan'), $i); ?></label>
            <input type="text" name="wpl_amazon_bullet_point<?php echo $i; ?>" id="wpl_amazon_bullet_point<?php echo $i; ?>"
                class="dokan-form-control"
                value="<?php echo esc_attr(get_post_meta($post_id, "_amazon_bullet_point$i", true)); ?>">
        </div>
    <?php endfor; ?>

    <!-- Advanced Amazon Options Section -->
    <hr>
    <h3><?php _e('Advanced Amazon Options', 'dokan'); ?></h3>

    <div class="dokan-form-group">
        <label for="wpl_amazon_b2b_price"><?php _e('Amazon B2B Price', 'dokan'); ?></label>
        <input type="text" name="wpl_amazon_b2b_price" id="wpl_amazon_b2b_price" class="dokan-form-control"
            value="<?php echo esc_attr(get_post_meta($post_id, '_amazon_b2b_price', true)); ?>"
            placeholder="B2B Price or shortcode">
    </div>

    <div class="dokan-form-group">
        <label for="wpl_amazon_minimum_price"><?php _e('Minimum Price', 'dokan'); ?></label>
        <input type="text" name="wpl_amazon_minimum_price" id="wpl_amazon_minimum_price" class="dokan-form-control"
            value="<?php echo esc_attr(get_post_meta($post_id, '_amazon_minimum_price', true)); ?>" placeholder="Min Price">
    </div>

    <div class="dokan-form-group">
        <label for="wpl_amazon_maximum_price"><?php _e('Maximum Price', 'dokan'); ?></label>
        <input type="text" name="wpl_amazon_maximum_price" id="wpl_amazon_maximum_price" class="dokan-form-control"
            value="<?php echo esc_attr(get_post_meta($post_id, '_amazon_maximum_price', true)); ?>" placeholder="Max Price">
    </div>

    <div class="dokan-form-group">
        <label for="wpl_amazon_handling_time"><?php _e('Handling Time', 'dokan'); ?></label>
        <input type="number" name="wpl_amazon_handling_time" id="wpl_amazon_handling_time" class="dokan-form-control"
            value="<?php echo esc_attr(get_post_meta($post_id, '_amazon_handling_time', true)); ?>" placeholder="e.g. 2">
    </div>

    <div class="dokan-form-group">
        <label for="wpl_amazon_fba_overwrite"><?php _e('FBA mode', 'dokan'); ?></label>
        <select name="wpl_amazon_fba_overwrite" id="wpl_amazon_fba_overwrite" class="dokan-form-control">
            <option value=""><?php _e('-- set automatically --', 'dokan'); ?></option>
            <option value="FBA" <?php selected(get_post_meta($post_id, '_amazon_fba_overwrite', true), 'FBA'); ?>>
                <?php _e('Fulfilled by Amazon (FBA)', 'dokan'); ?>
            </option>
            <option value="FBM" <?php selected(get_post_meta($post_id, '_amazon_fba_overwrite', true), 'FBM'); ?>>
                <?php _e('Fulfilled by Merchant (FBM)', 'dokan'); ?>
            </option>
        </select>
    </div>

    <div class="dokan-form-group">
        <label for="wpl_amazon_search_term"><?php _e('Search Terms', 'dokan'); ?></label>
        <input type="text" name="wpl_amazon_search_term" id="wpl_amazon_search_term" class="dokan-form-control"
            value="<?php echo esc_attr(get_post_meta($post_id, '_amazon_search_term', true)); ?>">
    </div>

    <div class="dokan-form-group">
        <label for="wpl_amazon_product_description"><?php _e('Custom Product Description', 'dokan'); ?></label>
        <?php
        $description = get_post_meta($post_id, '_amazon_product_description', true);
        wp_editor($description, 'wpl_amazon_product_description', [
            'wpautop' => false,
            'media_buttons' => false,
            'tinymce' => false,
            'quicktags' => true,
            'editor_height' => 150
        ]);
        ?>
    </div>

    <!-- Amazon Images Section -->
    <hr>
    <h3><?php _e('Amazon Images', 'dokan'); ?></h3>
    <div id="wpla-amazon-images">
        <style type="text/css">
            #wpla-amazon-images .wpla_gallery_thumb_link {
                float: left;
                margin-right: 10px;
                margin-bottom: 10px;
                border: 2px solid transparent;
                transition: opacity 0.2s;
            }

            #wpla-amazon-images .wpla_gallery_thumb_link.disabled {
                border-color: #ff0000;
            }

            #wpla-amazon-images .wpla_gallery_thumb_link.disabled img {
                opacity: 0.3;
            }

            #wpla-amazon-images .wpla_gallery_thumb_link img {
                width: 80px;
                height: 80px;
                object-fit: cover;
                border: 1px solid #ddd;
            }
        </style>
        <div class="dokan-form-group">
            <?php
            $disabled_images = explode(',', get_post_meta($post_id, '_wpla_disabled_gallery_images', true));
            $featured_image_id = get_post_thumbnail_id($post_id);
            $gallery_ids = get_post_meta($post_id, '_product_image_gallery', true);
            $attachment_ids = array_filter(explode(',', $gallery_ids));
            if ($featured_image_id)
                array_unshift($attachment_ids, $featured_image_id);
            $attachment_ids = array_unique($attachment_ids);

            foreach ($attachment_ids as $attachment_id) {
                $src = wp_get_attachment_image_src($attachment_id, 'thumbnail');
                if (!$src)
                    continue;
                $is_disabled = in_array($attachment_id, $disabled_images);
                ?>
                <a href="#" class="wpla_gallery_thumb_link <?php echo $is_disabled ? 'disabled' : ''; ?>"
                    data-attachment_id="<?php echo $attachment_id; ?>">
                    <img src="<?php echo $src[0]; ?>" alt="">
                </a>
                <?php
            }
            ?>
            <input type="hidden" name="_wpla_disabled_gallery_images" id="_wpla_disabled_gallery_images"
                value="<?php echo esc_attr(implode(',', $disabled_images)); ?>">
            <p style="clear:both;">
                <small><?php _e('Click an image to disable / enable it to be used on Amazon (Red border = disabled).', 'dokan'); ?></small>
            </p>
        </div>
    </div>
    <script type="text/javascript">
        jQuery(document).ready(function ($) {
            $('.wpla_gallery_thumb_link').on('click', function (e) {
                e.preventDefault();
                $(this).toggleClass('disabled');
                var disabled = [];
                $('.wpla_gallery_thumb_link.disabled').each(function () {
                    disabled.push($(this).data('attachment_id'));
                });
                $('#_wpla_disabled_gallery_images').val(disabled.join(','));
            });
        });
    </script>

    <!-- Amazon Product Type Section -->
    <hr>
    <h3><?php _e('Amazon Product Type', 'dokan'); ?></h3>
    <div class="dokan-form-group">
        <label for="wpla_marketplace_id"><?php _e('Marketplace', 'dokan'); ?></label>
        <select id="wpla_marketplace_id" name="wpla_marketplace_id" class="dokan-form-control">
            <option value=""><?php _e('-- use profile setting --', 'dokan'); ?></option>
            <?php
            if (class_exists('WPLA_AmazonMarket')) {
                $marketplaces = WPLA_AmazonMarket::getAllFromAccounts();
                foreach ($marketplaces as $marketplace_id => $marketplace) {
                    echo '<option value="' . esc_attr($marketplace_id) . '" ' . selected(get_post_meta($post_id, '_wpla_custom_marketplace_id', true), $marketplace_id, false) . '>' . esc_html($marketplace) . '</option>';
                }
            }
            ?>
        </select>
    </div>

    <div class="dokan-form-group">
        <label for="wpla_product_type"><?php _e('Product Type', 'dokan'); ?></label>
        <select id="wpla_product_type" name="wpla_product_type" class="dokan-form-control">
            <option value=""><?php _e('-- use profile setting --', 'dokan'); ?></option>
            <?php
            $custom_marketplace_id = get_post_meta($post_id, '_wpla_custom_marketplace_id', true);
            $custom_product_type = get_post_meta($post_id, '_wpla_custom_product_type', true);
            if ($custom_marketplace_id && class_exists('\WPLab\Amazon\Models\AmazonProductTypesModel')) {
                $product_types = \WPLab\Amazon\Models\AmazonProductTypesModel::getByMarketplace($custom_marketplace_id);
                foreach ($product_types as $type) {
                    echo '<option value="' . esc_attr($type->product_type) . '" ' . selected($custom_product_type, $type->product_type, false) . '>' . esc_html($type->display_name) . '</option>';
                }
            }
            ?>
        </select>
        <small><?php _e('Note: Full attribute mapping is managed primarily in the WordPress admin.', 'dokan'); ?></small>
    </div>

    <?php
}

// 1.1 Add Amazon Fields to Dokan Product Variation section
add_action('dokan_variation_options_inventory', 'wpla_add_amazon_variation_mapping_fields', 15, 3);
function wpla_add_amazon_variation_mapping_fields($loop, $variation_data, $variation)
{
    $variation_id = $variation->ID;
    if (!class_exists('WPLA_ListingsModel'))
        return;

    $asin = get_post_meta($variation_id, '_wpla_asin', true);
    $sku = get_post_meta($variation_id, '_wpla_amazon_sku', true);

    $listingsModel = new WPLA_ListingsModel();
    $status = $listingsModel->getStatusFromPostID($variation_id);
    ?>
    <div class="dokan-form-group">
        <label for="_wpla_asin_<?php echo $loop; ?>">
            <?php _e('Amazon ASIN', 'dokan'); ?>
        </label>
        <input type="text" name="_wpla_asin_variation[<?php echo $loop; ?>]" id="_wpla_asin_<?php echo $loop; ?>"
            class="dokan-form-control" value="<?php echo esc_attr($asin); ?>" placeholder="e.g. B0XXXXXXXX">
    </div>
    <div class="dokan-form-group">
        <label for="_wpla_amazon_sku_<?php echo $loop; ?>">
            <?php _e('Amazon SKU (Optional)', 'dokan'); ?>
        </label>
        <input type="text" name="_wpla_amazon_sku_variation[<?php echo $loop; ?>]"
            id="_wpla_amazon_sku_<?php echo $loop; ?>" class="dokan-form-control" value="<?php echo esc_attr($sku); ?>"
            placeholder="Leave empty to use Brags SKU">
        <input type="hidden" name="_wpla_variation_ids[<?php echo $loop; ?>]" value="<?php echo $variation_id; ?>">
    </div>
    <div class="dokan-form-group">
        <?php if ($status && !in_array($status, array('unlisted'))): ?>
            <span class="dokan-label dokan-label-success"><?php echo ucfirst($status); ?></span>
        <?php endif; ?>
        <label>
            <input type="checkbox" name="_wpla_list_on_amazon_sync_variation[<?php echo $loop; ?>]" value="yes" <?php checked(get_post_meta($variation_id, '_wpla_list_on_amazon', true), 'yes'); ?>>
            <?php _e('Sync Stock', 'dokan'); ?>
        </label>
    </div>
    <?php
}

// 2. Save Amazon Mapping and Sync with WP-Lister
add_action('dokan_process_product_meta', 'wpla_save_amazon_mapping', 20);
function wpla_save_amazon_mapping($post_id)
{
    if (!class_exists('WPLA_ListingsModel'))
        return;

    // Handle Metadata
    if (isset($_POST['_wpla_asin'])) {
        update_post_meta($post_id, '_wpla_asin', sanitize_text_field($_POST['_wpla_asin']));
        update_post_meta($post_id, '_wpla_amazon_sku', sanitize_text_field($_POST['_wpla_amazon_sku']));
        update_post_meta($post_id, '_wpla_list_on_amazon', (isset($_POST['_wpla_list_on_amazon_sync']) ? 'yes' : 'no'));
    }

    // Handle New Amazon Options
    $amazon_fields = [
        'wpl_amazon_product_id' => '_amazon_product_id',
        'wpl_amazon_id_type' => '_amazon_id_type',
        'wpl_amazon_title' => '_amazon_title',
        'wpl_amazon_price' => '_amazon_price',
        'wpl_amazon_external_repricer' => '_amazon_external_repricer',
        'wpl_amazon_restock_date' => '_amazon_restock_date',
        'wpl_amazon_condition_type' => '_amazon_condition_type',
        'wpl_amazon_condition_note' => '_amazon_condition_note',
        'wpl_amazon_bullet_point1' => '_amazon_bullet_point1',
        'wpl_amazon_bullet_point2' => '_amazon_bullet_point2',
        'wpl_amazon_bullet_point3' => '_amazon_bullet_point3',
        'wpl_amazon_bullet_point4' => '_amazon_bullet_point4',
        'wpl_amazon_bullet_point5' => '_amazon_bullet_point5',
    ];

    foreach ($amazon_fields as $post_key => $meta_key) {
        if (isset($_POST[$post_key])) {
            $value = sanitize_text_field($_POST[$post_key]);

            // Format price if it's the price field
            if ($post_key === 'wpl_amazon_price' && !empty($value)) {
                $value = wc_format_decimal($value);
            }

            update_post_meta($post_id, $meta_key, $value);
        }
    }

    // Handle Phase 2 Advanced Options
    $advanced_fields = [
        'wpl_amazon_b2b_price' => '_amazon_b2b_price',
        'wpl_amazon_minimum_price' => '_amazon_minimum_price',
        'wpl_amazon_maximum_price' => '_amazon_maximum_price',
        'wpl_amazon_handling_time' => '_amazon_handling_time',
        'wpl_amazon_fba_overwrite' => '_amazon_fba_overwrite',
        'wpl_amazon_search_term' => '_amazon_search_term',
        'wpl_amazon_product_description' => '_amazon_product_description',
    ];

    foreach ($advanced_fields as $post_key => $meta_key) {
        if (isset($_POST[$post_key])) {
            $value = ($post_key === 'wpl_amazon_product_description') ? wp_kses_post($_POST[$post_key]) : sanitize_text_field($_POST[$post_key]);

            if (in_array($post_key, ['wpl_amazon_minimum_price', 'wpl_amazon_maximum_price', 'wpl_amazon_b2b_price']) && !empty($value)) {
                $value = wc_format_decimal($value);
            }

            update_post_meta($post_id, $meta_key, $value);
        }
    }

    // Handle Amazon Images
    if (isset($_POST['_wpla_disabled_gallery_images'])) {
        update_post_meta($post_id, '_wpla_disabled_gallery_images', sanitize_text_field($_POST['_wpla_disabled_gallery_images']));
    }

    // Handle Product Type / Marketplace
    if (isset($_POST['wpla_marketplace_id'])) {
        update_post_meta($post_id, '_wpla_custom_marketplace_id', sanitize_text_field($_POST['wpla_marketplace_id']));
    }
    if (isset($_POST['wpla_product_type'])) {
        update_post_meta($post_id, '_wpla_custom_product_type', sanitize_text_field($_POST['wpla_product_type']));
    }

    // Handle "List on Amazon" native trigger
    if (!empty($_POST['wpla_list_on_amazon']) && !empty($_POST['wpla_list_profile'])) {
        $listingsModel = new WPLA_ListingsModel();
        $listingsModel->prepareProductForListing($post_id, sanitize_text_field($_POST['wpla_list_profile']));
    }

    // Handle Variations metadata
    if (isset($_POST['_wpla_variation_ids']) && is_array($_POST['_wpla_variation_ids'])) {
        foreach ($_POST['_wpla_variation_ids'] as $loop => $variation_id) {
            $variation_id = intval($variation_id);
            if (isset($_POST['_wpla_asin_variation'][$loop])) {
                update_post_meta($variation_id, '_wpla_asin', sanitize_text_field($_POST['_wpla_asin_variation'][$loop]));
                update_post_meta($variation_id, '_wpla_amazon_sku', sanitize_text_field($_POST['_wpla_amazon_sku_variation'][$loop]));
                update_post_meta($variation_id, '_wpla_list_on_amazon', (isset($_POST['_wpla_list_on_amazon_sync_variation'][$loop]) ? 'yes' : 'no'));
            }
        }
    }
}

// 2.1 Also hook into specific variation save for AJAX updates
add_action('dokan_save_product_variation', 'wpla_save_amazon_variation_mapping', 20, 2);
function wpla_save_amazon_variation_mapping($variation_id, $i)
{
    if (isset($_POST['_wpla_asin_variation'][$i])) {
        $asin = sanitize_text_field($_POST['_wpla_asin_variation'][$i]);
        $sku = sanitize_text_field($_POST['_wpla_amazon_sku_variation'][$i]);
        $list_on_amazon = isset($_POST['_wpla_list_on_amazon_variation'][$i]) ? 'yes' : 'no';

        update_post_meta($variation_id, '_wpla_asin', $asin);
        update_post_meta($variation_id, '_wpla_amazon_sku', $sku);
        update_post_meta($variation_id, '_wpla_list_on_amazon', $list_on_amazon);

        wpla_sync_product_to_amazon_listing($variation_id, $asin, $sku);
    }
}

/**
 * Sync Brags product to WP-Lister amazon_listings table
 */
function wpla_sync_product_to_amazon_listing($post_id, $asin, $sku_override)
{
    global $wpdb;

    // 1. Get vendor account linked to this product's author
    $vendor_id = get_post_field('post_author', $post_id);
    $table_accounts = $wpdb->prefix . 'amazon_accounts';
    $account_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_accounts WHERE vendor_id = %d", array($vendor_id)));

    if (!$account_id) {
        // Fallback: Check if the current user is the vendor (sometimes post_author isn't set yet during creation)
        $vendor_id = get_current_user_id();
        $account_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_accounts WHERE vendor_id = %d", array($vendor_id)));
    }

    if (!$account_id) {
        return; // No Amazon account for this vendor
    }

    // 2. Determine SKU to use
    if (empty($sku_override)) {
        $product = wc_get_product($post_id);
        $sku = $product ? $product->get_sku() : '';
    } else {
        $sku = $sku_override;
    }

    if (empty($sku)) {
        return; // Cannot sync without SKU
    }

    // 3. Check for existing listing in amazon_listings for this product and account
    $table_listings = $wpdb->prefix . 'amazon_listings';
    $existing_id = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table_listings WHERE post_id = %d AND account_id = %d",
        array($post_id, $account_id)
    ));

    if ($existing_id) {
        // Update existing listing
        $wpdb->update($table_listings, array(
            'asin' => $asin,
            'sku' => $sku,
            'status' => 'matched',
        ), array('id' => $existing_id));
    } elseif (!empty($asin)) {
        // Create new matched listing
        if (class_exists('WPLA_ListingsModel')) {
            $listings_model = new WPLA_ListingsModel();
            $listings_model->insertMatchedProduct($post_id, $asin, $account_id);

            // If SKU was overridden, we need to ensure the listing table has it
            if (!empty($sku_override)) {
                $wpdb->update($table_listings, array('sku' => $sku), array('post_id' => $post_id, 'account_id' => $account_id));
            }
        } else {
            // Manual fallback if model not available
            $product = wc_get_product($post_id);
            $wpdb->insert($table_listings, array(
                'post_id' => $post_id,
                'asin' => $asin,
                'sku' => $sku,
                'account_id' => $account_id,
                'status' => 'matched',
                'source' => 'matched',
                'date_created' => current_time('mysql', 1),
                'listing_title' => $product ? $product->get_title() : '',
                'quantity' => $product ? $product->get_stock_quantity() : 0,
                'price' => $product ? $product->get_price() : 0,
            ));
        }
    }
}

// 3. AJAX Handlers for UI Enhancements

// 3.1 ASIN Validation via API
add_action('wp_ajax_wpla_validate_asin_api', 'wpla_validate_asin_api_handler');
function wpla_validate_asin_api_handler()
{
    check_ajax_referer('dokan_reviews_nonce', 'security');

    $asin = isset($_POST['asin']) ? sanitize_text_field($_POST['asin']) : '';
    $vendor_id = get_current_user_id();

    if (empty($asin)) {
        wp_send_json_error(array('message' => __('ASIN is required', 'dokan')));
    }

    if (!class_exists('WPLA_Amazon_SP_API')) {
        wp_send_json_error(array('message' => __('Amazon API class not found', 'dokan')));
    }

    $account_id = function_exists('brags_get_seller_amazon_account_id') ? brags_get_seller_amazon_account_id($vendor_id) : 0;
    if (!$account_id) {
        wp_send_json_error(array('message' => __('Amazon account not connected', 'dokan')));
    }

    try {
        $api = new WPLA_Amazon_SP_API($account_id);
        $result = $api->getCatalogItem($asin);

        if (is_object($result) && isset($result->ErrorMessage)) {
            wp_send_json_error(array('message' => $result->ErrorMessage));
        }

        if ($result) {
            // Get title and image from payload
            $summaries = $result->getSummaries();
            $title = '';
            $image_url = '';

            if (!empty($summaries)) {
                $title = $summaries[0]->getItemName();
                $image_url = WPLA_Amazon_SP_API::getPrimaryImageFromCatalog($result);
            }

            wp_send_json_success(array(
                'valid' => true,
                'title' => $title,
                'image' => $image_url,
                'asin' => $asin
            ));
        } else {
            wp_send_json_error(array('message' => __('ASIN not found on Amazon', 'dokan')));
        }
    } catch (Exception $e) {
        wp_send_json_error(array('message' => $e->getMessage()));
    }
}

// 3.2 Sync from Amazon via API
add_action('wp_ajax_wpla_sync_data_from_amazon', 'wpla_sync_data_from_amazon_handler');
function wpla_sync_data_from_amazon_handler()
{
    check_ajax_referer('dokan_reviews_nonce', 'security');

    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $asin = isset($_POST['asin']) ? sanitize_text_field($_POST['asin']) : '';
    $vendor_id = get_current_user_id();

    if (!$post_id || empty($asin)) {
        wp_send_json_error(array('message' => __('Product ID and ASIN are required', 'dokan')));
    }

    $account_id = function_exists('brags_get_seller_amazon_account_id') ? brags_get_seller_amazon_account_id($vendor_id) : 0;
    if (!$account_id) {
        wp_send_json_error(array('message' => __('Amazon account not connected', 'dokan')));
    }

    try {
        $api = new WPLA_Amazon_SP_API($account_id);

        // 1. Get Catalog Data (Title, Images, Description)
        $catalog_result = $api->getCatalogItem($asin);

        // 2. Get Pricing Data
        $pricing_result = $api->getCompetitivePricing(array($asin));

        $updates = array();

        if ($catalog_result && !isset($catalog_result->ErrorMessage)) {
            $summaries = $catalog_result->getSummaries();
            if (!empty($summaries)) {
                $title = $summaries[0]->getItemName();
                update_post_meta($post_id, '_amazon_title', $title);
                $updates['title'] = $title;
            }
        }

        if (isset($pricing_result[$asin]) && !empty($pricing_result[$asin])) {
            $prices = $pricing_result[$asin];
            // Get the first competitive price
            if (isset($prices[0])) {
                $price = $prices[0]->getLandedPrice()->getAmount();
                update_post_meta($post_id, '_amazon_price', $price);
                $updates['price'] = $price;
            }
        }

        // 3. Get Listing Status/SKU if exists
        $sku = get_post_meta($post_id, '_wpla_amazon_sku', true);
        if (empty($sku)) {
            $product = wc_get_product($post_id);
            $sku = $product ? $product->get_sku() : '';
        }

        if ($sku) {
            $listing_result = $api->getListingsItem($sku);
            if ($listing_result && !isset($listing_result->ErrorMessage)) {
                $status = $listing_result->getSummaries()[0]->getStatus();
                // We don't necessarily want to update the local status here as it's managed by WP-Lister,
                // but we can return it.
                $updates['amazon_status'] = $status;
            }
        }

        wp_send_json_success(array(
            'message' => __('Product data synced from Amazon.', 'dokan'),
            'updates' => $updates
        ));
    } catch (Exception $e) {
        wp_send_json_error(array('message' => $e->getMessage()));
    }
}

// 4. Register Script and Styles for validation and other features
add_action('wp_enqueue_scripts', function () {
    $is_product_edit = (isset($_GET['product_id']) || isset($_GET['post'])) && isset($_GET['action']) && $_GET['action'] == 'edit';
    $is_dashboard = function_exists('dokan_is_seller_dashboard') && dokan_is_seller_dashboard();

    if ($is_product_edit || $is_dashboard || (function_exists('dokan_is_product_edit_page') && dokan_is_product_edit_page())) {
        wp_enqueue_style('amazon-mapping-css', get_stylesheet_directory_uri() . '/inc/amazon/assets/amazon-product-mapping.css', array(), '1.2.0');
        wp_enqueue_script('amazon-mapping-js', get_stylesheet_directory_uri() . '/inc/amazon/assets/amazon-product-mapping.js', array('jquery'), '1.2.0', true);

        wp_localize_script('amazon-mapping-js', 'wpla_mapping_vars', array(
            'ajax_url' => admin_url('admin_ajax.php'),
            'nonce' => wp_create_nonce('dokan_reviews_nonce'),
            'wpla_admin_url' => admin_url()
        ));
    }
});

// 5. Amazon Status Column in Dokan Product List
add_action('dokan_product_list_table_after_status_table_header', function () {
    echo '<th>' . __('Amazon Status', 'dokan') . '</th>';
});

add_action('dokan_product_list_table_after_status_table_data', function ($post, $product) {
    if (!class_exists('WPLA_ListingsModel')) {
        echo '<td>-</td>';
        return;
    }

    $lm = new WPLA_ListingsModel();
    $status = $lm->getStatusFromPostID($post->ID);

    if (!$status || $status == 'unlisted') {
        $asin = get_post_meta($post->ID, '_wpla_asin', true);
        if ($asin) {
            echo '<td><span class="amazon-status-badge status-prepared">' . __('Linked', 'dokan') . '</span><br><small style="font-size: 10px; color: #666;">' . esc_html($asin) . '</small></td>';
        } else {
            echo '<td><span class="na">&ndash;</span></td>';
        }
    } else {
        $badge_class = 'status-' . strtolower(str_replace(' ', '-', $status));
        echo '<td><span class="amazon-status-badge ' . esc_attr($badge_class) . '">' . ucfirst($status) . '</span>';

        $asin = get_post_meta($post->ID, '_wpla_asin', true);
        if ($asin) {
            echo '<br><small style="font-size: 10px; color: #666;">' . esc_html($asin) . '</small>';
        }
        echo '</td>';
    }
}, 10, 2);

// 6. Bulk ASIN Assignment Action
add_filter('dokan_bulk_product_statuses', function ($statuses) {
    if (!current_user_can('seller') && !current_user_can('dokandar')) {
        return $statuses;
    }

    if (brags_seller_has_amazon_account()) {
        $statuses['assign_amazon_asin'] = __('Assign Amazon ASIN', 'dokan');
    }
    return $statuses;
}, 20);

// 7. Handle Bulk ASIN Assignment via JS and AJAX
add_action('wp_enqueue_scripts', function () {
    global $wp;
    $is_product_listing = isset($wp->query_vars['products']) || (isset($_GET['page']) && $_GET['page'] == 'dokan-products');

    if ($is_product_listing) {
        wp_enqueue_script('amazon-bulk-actions-js', get_stylesheet_directory_uri() . '/inc/amazon/assets/amazon-bulk-actions.js', array('jquery'), '1.0.5', true);
        wp_localize_script('amazon-bulk-actions-js', 'wpla_bulk_vars', array(
            'ajax_url' => admin_url('admin_ajax.php'),
            'nonce' => wp_create_nonce('dokan_reviews_nonce'),
            'confirm_title' => __('Assign Amazon ASIN', 'dokan'),
            'confirm_text' => __('Enter ASIN to assign to selected products:', 'dokan')
        ));
    }
});

// AJAX Handler for Bulk ASIN Assignment
add_action('wp_ajax_wpla_bulk_assign_asin', function () {
    check_ajax_referer('dokan_reviews_nonce', 'security');

    $product_ids = isset($_POST['product_ids']) ? array_map('intval', $_POST['product_ids']) : array();
    $asin = isset($_POST['asin']) ? sanitize_text_field($_POST['asin']) : '';
    $user_id = get_current_user_id();

    if (empty($product_ids) || empty($asin)) {
        wp_send_json_error(array('message' => __('Product IDs and ASIN are required', 'dokan')));
    }

    $updated = 0;
    foreach ($product_ids as $product_id) {
        // Verify ownership
        if (get_post_field('post_author', $product_id) != $user_id)
            continue;

        update_post_meta($product_id, '_wpla_asin', $asin);
        $updated++;
    }

    wp_send_json_success(array('message' => sprintf(__('%d products updated with ASIN %s', 'dokan'), $updated, $asin)));
});
