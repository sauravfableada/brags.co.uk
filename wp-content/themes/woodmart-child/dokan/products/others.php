<?php
$post_statuses = dokan_get_available_post_status( $post->ID );
?>

<div class="dokan-other-options dokan-edit-row dokan-clearfix <?php echo esc_attr( $class ); ?>">
    <div class="dokan-section-heading" data-togglehandler="dokan_other_options">
        <h2><i class="fas fa-cog" aria-hidden="true"></i> <?php esc_html_e( 'Other Options', 'dokan-lite' ); ?></h2>
        <p><?php esc_html_e( 'Set your extra product options', 'dokan-lite' ); ?></p>
        <a href="#" class="dokan-section-toggle">
            <i class="fas fa-sort-down fa-flip-vertical" aria-hidden="true"></i>
        </a>
        <div class="dokan-clearfix"></div>
    </div>

    <div class="dokan-section-content">
        <div class="dokan-form-group content-half-part">
            <label for="post_status" class="form-label"><?php esc_html_e( 'Product Status', 'dokan-lite' ); ?></label>
            <select id="post_status" class="dokan-form-control" name="post_status">
                <?php foreach ( $post_statuses as $status => $label ) : // phpcs:ignore ?>
                    <option value="<?php echo esc_attr( $status ); ?>" <?php selected( $status, $post_status ); ?>>
                        <?php echo esc_html( $label ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

    

        <div class="dokan-clearfix"></div>

        <div class="dokan-form-group">
            <label for="_purchase_note" class="form-label"><?php esc_html_e( 'Purchase Note', 'dokan-lite' ); ?></label>
            <?php dokan_post_input_box( $post_id, '_purchase_note', array( 'placeholder' => __( 'Customer will get this info in their order email', 'dokan-lite' ) ), 'textarea' ); ?>
        </div>


        <div class="dokan-form-group">
            <?php  $seo_keywords = get_post_meta( $post->ID, '_seo_keywords', true ); ?>
            <label for="_seo_keywords" class="form-label"><?php esc_html_e( 'SEO Keywords', 'your-text-domain' ); ?></label>
            <input type="text" name="_seo_keywords" id="_seo_keywords" value="<?php echo esc_attr( $seo_keywords ); ?>" class="dokan-form-control" />
            <p class="help"><?php esc_html_e( 'Enter SEO keywords separated by commas. This field is not visible to customers.', 'your-text-domain' ); ?></p>
        </div>

            <!-- Add Required Product Listing Fields -->

            <div class="dokan-form-group">
                <label for="_manufacturer" class="form-label"><?php esc_html_e( 'Manufacturer', 'dokan-lite' ); ?> <span class="required">*</span></label>
                <input type="text" class="dokan-form-control" name="_manufacturer" id="_manufacturer" value="<?php echo esc_attr( get_post_meta( $post->ID, '_manufacturer', true ) ); ?>" required>
            </div>

            <div class="dokan-form-group">
                <label for="_country_of_origin" class="form-label"><?php esc_html_e( 'Country of Origin', 'dokan-lite' ); ?> <span class="required">*</span></label>
                <input type="text" class="dokan-form-control" name="_country_of_origin" id="_country_of_origin" value="<?php echo esc_attr( get_post_meta( $post->ID, '_country_of_origin', true ) ); ?>" required>
            </div>

            <div class="dokan-form-group">
                <label for="_product_dimensions" class="form-label"><?php esc_html_e( 'Product Dimensions (cm)', 'dokan-lite' ); ?> <span class="required">*</span></label>
                <input type="text" class="dokan-form-control" name="_product_dimensions" id="_product_dimensions" value="<?php echo esc_attr( get_post_meta( $post->ID, '_product_dimensions', true ) ); ?>" required>
            </div>

            <div class="dokan-form-group">
                <label for="_item_weight" class="form-label"><?php esc_html_e( 'Item Weight (kg)', 'dokan-lite' ); ?> <span class="required">*</span></label>
                <input type="number" step="0.01" class="dokan-form-control" name="_item_weight" id="_item_weight" value="<?php echo esc_attr( get_post_meta( $post->ID, '_item_weight', true ) ); ?>" style="text-align: left;" required>
            </div>

            <div class="dokan-form-group">
                <label for="_unit_count" class="form-label"><?php esc_html_e( 'Unit Count', 'dokan-lite' ); ?> <span class="required">*</span></label>
                <input type="number" class="dokan-form-control" name="_unit_count" id="_unit_count" value="<?php echo esc_attr( get_post_meta( $post->ID, '_unit_count', true ) ); ?>" style="text-align: left;" required>
            </div>

            <div class="dokan-form-group">
                <label for="_included_in_packaging" class="form-label"><?php esc_html_e( 'Included in Packaging', 'dokan-lite' ); ?> <span class="required">*</span></label>
                <textarea name="_included_in_packaging" id="_included_in_packaging" class="dokan-form-control" required><?php echo esc_textarea( get_post_meta( $post->ID, '_included_in_packaging', true ) ); ?></textarea>
            </div>

            <div class="dokan-form-group">
                <label for="_age_range" class="form-label"><?php esc_html_e( 'Age Range', 'dokan-lite' ); ?> <span class="required">*</span></label>
                <input type="text" class="dokan-form-control" name="_age_range" id="_age_range" value="<?php echo esc_attr( get_post_meta( $post->ID, '_age_range', true ) ); ?>" required>
            </div>

            <div class="dokan-form-group">
                <label for="_material" class="form-label"><?php esc_html_e( 'Material', 'dokan-lite' ); ?> <span class="required">*</span></label>
                <input type="text" class="dokan-form-control" name="_material" id="_material" value="<?php echo esc_attr( get_post_meta( $post->ID, '_material', true ) ); ?>" required>
            </div>

            <!-- Batteries Required -->
            <div class="dokan-form-group">
                <label for="batteries_required" class="form-label"><?php esc_html_e('Are Batteries Required?', 'dokan-lite'); ?> <span style="color:red;">*</span></label>
                <select name="batteries_required" id="batteries_required" class="dokan-form-control" required>
                    <?php
                    $options = ['Yes', 'No', 'Not Applicable'];
                    $selected = get_post_meta($post->ID, '_batteries_required', true);
                    foreach ( $options as $opt ) {
                        echo '<option value="' . esc_attr($opt) . '" ' . selected($selected, $opt, false) . '>' . esc_html($opt) . '</option>';
                    }
                    ?>
                </select>
            </div>

            <!-- Batteries Included -->
            <div class="dokan-form-group">
                <label for="batteries_included" class="form-label"><?php esc_html_e('Are Batteries Included?', 'dokan-lite'); ?> <span style="color:red;">*</span></label>
                <select name="batteries_included" id="batteries_included" class="dokan-form-control" required>
                    <?php
                    $selected = get_post_meta($post->ID, '_batteries_included', true);
                    foreach ( $options as $opt ) {
                        echo '<option value="' . esc_attr($opt) . '" ' . selected($selected, $opt, false) . '>' . esc_html($opt) . '</option>';
                    }
                    ?>
                </select>
            </div>

            <!-- Age Restriction -->
            <div class="dokan-form-group">
                <?php $age_restriction = get_post_meta( $post->ID, '_age_restriction', true ); ?>
                <label class="form-label">
                    <input type="checkbox" name="age_restriction" value="1" <?php checked( $age_restriction, '1' ); ?> />
                    <?php esc_html_e('This product is legally restricted to buyers 18 years or older.', 'dokan-lite'); ?>
                </label>
                <p class="help" style="margin-top: 5px;">
                    ⚠ <?php esc_html_e('Important: By selecting this option, you confirm that this product is age-restricted and that you, the seller, are solely responsible for verifying the buyer’s age and ID upon delivery or as required by law. Brags & Partners Ltd does not verify age on your behalf and assumes no responsibility for legal compliance regarding age-restricted sales.', 'dokan-lite'); ?>
                </p>
                <?php $age_ack = get_post_meta( $post->ID, '_age_restriction_ack', true ); ?>
                <label>
                    <input type="checkbox" name="age_restriction_ack" value="1" <?php checked( $age_ack, '1' ); ?> required />
                    <?php esc_html_e('I understand and accept full responsibility for all age verification requirements related to this listing.', 'dokan-lite'); ?>
                </label>
            </div>


            <div class="dokan-form-group">
                <label for="legal_disclaimer" class="form-label"><?php esc_html_e('Seller’s Legal Disclaimer', 'dokan-lite'); ?></label>
                <textarea name="legal_disclaimer" id="legal_disclaimer" class="dokan-form-control"><?php echo esc_textarea( get_post_meta( $post->ID, '_legal_disclaimer', true ) ); ?></textarea>
            </div>

            <div class="dokan-form-group">
                <label for="ingredients_allergens" class="form-label"><?php esc_html_e('Ingredients and Allergen Information (For Food/Drink)', 'dokan-lite'); ?></label>
                <textarea name="ingredients_allergens" id="ingredients_allergens" class="dokan-form-control"><?php echo esc_textarea( get_post_meta( $post->ID, '_ingredients_allergens', true ) ); ?></textarea>
            </div>

            <div class="dokan-form-group">
                <label for="important_seller_info" class="form-label"><?php esc_html_e('Important Information from the Seller', 'dokan-lite'); ?></label>
                <textarea name="important_seller_info" id="important_seller_info" class="dokan-form-control"><?php echo esc_textarea( get_post_meta( $post->ID, '_important_seller_info', true ) ); ?></textarea>
            </div>

        <!-- Legal Confirmation Checkbox -->
        <div class="dokan-form-group">
            <?php $legal_confirmation = get_post_meta($post->ID, '_legal_confirmation', true); ?>
            <label for="legal_confirmation" class="form-label">
                <input type="checkbox" id="legal_confirmation" name="legal_confirmation" value="1" <?php checked( $legal_confirmation, '1' ); ?> required>
                <?php esc_html_e( 'I confirm that I/my company have the legal rights to sell this product in the UK, possess the necessary supporting documentation for selling the product, hold our own Product Liability Insurance, will respond promptly to customer messages, handle returns fairly & efficiently, and acknowledge that Brags & Partners Ltd holds no responsibility for this product.', 'dokan-lite' ); ?>
            </label>
        </div>
        

    </div>


    
</div><!-- .dokan-other-options -->
