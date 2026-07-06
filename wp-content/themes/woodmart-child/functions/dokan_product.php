<?php

// Add a custom textarea field in the product edit page
function add_product_key_selling_points()
{
    woocommerce_wp_textarea_input(array(
        'id' => '_key_selling_points',
        'label' => __('Key Selling Points (One per line)', 'woodmart'),
        'desc_tip' => true,
        'description' => __('Enter up to 4 key selling points, one per line. Max 250 characters each.', 'woodmart')
    ));
}
add_action('woocommerce_product_options_general_product_data', 'add_product_key_selling_points');

// Save the key selling points in post meta
function save_product_key_selling_points($post_id)
{
    if (isset($_POST['_key_selling_points'])) {
        $key_selling_points = sanitize_textarea_field($_POST['_key_selling_points']);
        update_post_meta($post_id, '_key_selling_points', $key_selling_points);
    }

    $fields = [
        '_manufacturer',
        '_country_of_origin',
        '_product_dimensions',
        '_item_weight',
        '_unit_count',
        '_included_in_packaging',
        '_age_range',
        '_material',
        '_batteries_required',
        '_batteries_included',
        '_legal_disclaimer',
        '_ingredients_allergens',
        '_important_seller_info'
    ];

    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
        } else {
            delete_post_meta($post_id, $field);
        }
    }

    // Save age restriction checkbox (special case)
    $age_restriction_value = isset($_POST['_age_restriction']) && $_POST['_age_restriction'] === 'yes' ? '1' : '0';
    update_post_meta($post_id, '_age_restriction', $age_restriction_value);

}
add_action('woocommerce_process_product_meta', 'save_product_key_selling_points');




function save_dokan_product_key_selling_points($post_id)
{
    if (isset($_POST['_key_selling_points'])) {
        $key_selling_points = sanitize_textarea_field($_POST['_key_selling_points']);
        update_post_meta($post_id, '_key_selling_points', $key_selling_points);
    }

    // Required fields
    if (isset($_POST['_manufacturer'])) {
        update_post_meta($post_id, '_manufacturer', sanitize_text_field($_POST['_manufacturer']));
    }

    if (isset($_POST['_country_of_origin'])) {
        update_post_meta($post_id, '_country_of_origin', sanitize_text_field($_POST['_country_of_origin']));
    }

    if (isset($_POST['_product_dimensions'])) {
        update_post_meta($post_id, '_product_dimensions', sanitize_text_field($_POST['_product_dimensions']));
    }

    if (isset($_POST['_item_weight'])) {
        update_post_meta($post_id, '_item_weight', sanitize_text_field($_POST['_item_weight']));
    }

    if (isset($_POST['_unit_count'])) {
        update_post_meta($post_id, '_unit_count', intval($_POST['_unit_count']));
    }

    if (isset($_POST['_included_in_packaging'])) {
        update_post_meta($post_id, '_included_in_packaging', sanitize_textarea_field($_POST['_included_in_packaging']));
    }

    if (isset($_POST['_age_range'])) {
        update_post_meta($post_id, '_age_range', sanitize_text_field($_POST['_age_range']));
    }

    if (isset($_POST['_material'])) {
        update_post_meta($post_id, '_material', sanitize_text_field($_POST['_material']));
    }


    // Batteries Required
    if (isset($_POST['_batteries_required'])) {
        update_post_meta($post_id, '_batteries_required', sanitize_text_field($_POST['_batteries_required']));
    }

    // Batteries Required
    if (isset($_POST['batteries_required'])) {
        update_post_meta($post_id, '_batteries_required', sanitize_text_field($_POST['batteries_required']));
    }

    // Batteries Included
    if (isset($_POST['batteries_included'])) {
        update_post_meta($post_id, '_batteries_included', sanitize_text_field($_POST['batteries_included']));
    }

    // Age Restriction
    $age_restriction = isset($_POST['age_restriction']) ? '1' : '';
    update_post_meta($post_id, '_age_restriction', $age_restriction);

    // Age Restriction Acknowledgment
    if (isset($_POST['age_restriction_ack'])) {
        update_post_meta($post_id, '_age_restriction_ack', '1');
    } else {
        delete_post_meta($post_id, '_age_restriction_ack');
    }

    // Legal Disclaimer
    if (isset($_POST['legal_disclaimer'])) {
        update_post_meta($post_id, '_legal_disclaimer', sanitize_textarea_field($_POST['legal_disclaimer']));
    }

    // Ingredients and Allergen Info
    if (isset($_POST['ingredients_allergens'])) {
        update_post_meta($post_id, '_ingredients_allergens', sanitize_textarea_field($_POST['ingredients_allergens']));
    }

    // Important Seller Info
    if (isset($_POST['important_seller_info'])) {
        update_post_meta($post_id, '_important_seller_info', sanitize_textarea_field($_POST['important_seller_info']));
    }
}
add_action('dokan_process_product_meta', 'save_dokan_product_key_selling_points');



add_filter('woocommerce_product_tabs', 'bragsy_custom_additional_info_tab', 98);
function bragsy_custom_additional_info_tab($tabs)
{
    global $product;

    $custom_fields = [
        '_manufacturer',
        '_country_of_origin',
        '_product_dimensions',
        '_item_weight',
        '_unit_count',
        '_included_in_packaging',
        '_age_range',
        '_material',
        '_batteries_required',
        '_batteries_included',
        '_age_restriction',
        '_legal_disclaimer',
        '_ingredients_allergens',
        '_important_seller_info'
    ];

    $has_custom = false;
    foreach ($custom_fields as $field) {
        if (!empty(get_post_meta($product->get_id(), $field, true))) {
            $has_custom = true;
            break;
        }
    }

    $attributes = $product->get_attributes();
    $has_attributes = !empty($attributes);

    // Show the tab if there are either attributes or custom fields
    if ($has_custom || $has_attributes) {
        $tabs['additional_information'] = [
            'title' => __('Additional Information', 'woocommerce'),
            'priority' => 20,
            'callback' => 'bragsy_combined_additional_info',
        ];
    }

    return $tabs;
}

function bragsy_combined_additional_info()
{
    global $product;

    // Display the default WooCommerce attribute table
    wc_display_product_attributes($product);

    // Display custom fields
    $post_id = $product->get_id();

    $fields = [
        '_manufacturer' => 'Manufacturer',
        '_country_of_origin' => 'Country of Origin',
        '_product_dimensions' => 'Product Dimensions (cm)',
        '_item_weight' => 'Item Weight (kg)',
        '_unit_count' => 'Unit Count',
        '_included_in_packaging' => 'Included in Packaging',
        '_age_range' => 'Age Range',
        '_material' => 'Material',
        '_batteries_required' => 'Are Batteries Required?',
        '_batteries_included' => 'Are Batteries Included?',
        '_age_restriction' => '18+ Age Restriction Required?',
        '_legal_disclaimer' => 'Seller’s Legal Disclaimer',
        '_ingredients_allergens' => 'Ingredients and Allergen Information',
        '_important_seller_info' => 'Important Information from the Seller'
    ];

    echo '<table class="woocommerce-product-attributes shop_attributes">';

    foreach ($fields as $key => $label) {
        $value = get_post_meta($post_id, $key, true);

        // Format checkbox
        if ($key === '_age_restriction') {
            $value = $value ? 'Yes (18+)' : '';
        }

        if (!empty($value)) {
            echo '<tr class="woocommerce-product-attributes-item">';
            echo '<th class="woocommerce-product-attributes-item__label">' . esc_html($label) . '</th>';
            echo '<td class="woocommerce-product-attributes-item__value">' . nl2br(esc_html($value)) . '</td>';
            echo '</tr>';
        }
    }

    echo '</table>';


}


// show this fild in admin 
// Show custom fields in Product Data > General tab
add_action('woocommerce_product_options_general_product_data', 'bragsy_add_admin_custom_fields');
function bragsy_add_admin_custom_fields()
{
    global $post;

    echo '<div class="options_group">';

    // Retrieve saved values
    $manufacturer = get_post_meta($post->ID, '_manufacturer', true);
    $country_of_origin = get_post_meta($post->ID, '_country_of_origin', true);
    $product_dimensions = get_post_meta($post->ID, '_product_dimensions', true);
    $item_weight = get_post_meta($post->ID, '_item_weight', true);
    $unit_count = get_post_meta($post->ID, '_unit_count', true);
    $included_in_packaging = get_post_meta($post->ID, '_included_in_packaging', true);
    $age_range = get_post_meta($post->ID, '_age_range', true);
    $material = get_post_meta($post->ID, '_material', true);
    $batteries_required = get_post_meta($post->ID, '_batteries_required', true);
    $batteries_included = get_post_meta($post->ID, '_batteries_included', true);
    $age_restriction = get_post_meta($post->ID, '_age_restriction', true);
    $legal_disclaimer = get_post_meta($post->ID, '_legal_disclaimer', true);
    $ingredients_allergens = get_post_meta($post->ID, '_ingredients_allergens', true);
    $important_seller_info = get_post_meta($post->ID, '_important_seller_info', true);

    // Text fields
    woocommerce_wp_text_input([
        'id' => '_manufacturer',
        'label' => __('Manufacturer', 'woocommerce'),
        'desc_tip' => true,
        'value' => $manufacturer, // Populate with saved value
    ]);

    woocommerce_wp_text_input([
        'id' => '_country_of_origin',
        'label' => __('Country of Origin', 'woocommerce'),
        'desc_tip' => true,
        'value' => $country_of_origin, // Populate with saved value
    ]);

    woocommerce_wp_text_input([
        'id' => '_product_dimensions',
        'label' => __('Product Dimensions (cm)', 'woocommerce'),
        'desc_tip' => true,
        'value' => $product_dimensions, // Populate with saved value
    ]);

    woocommerce_wp_text_input([
        'id' => '_item_weight',
        'label' => __('Item Weight (kg)', 'woocommerce'),
        'desc_tip' => true,
        'value' => $item_weight, // Populate with saved value
    ]);

    woocommerce_wp_text_input([
        'id' => '_unit_count',
        'label' => __('Unit Count', 'woocommerce'),
        'desc_tip' => true,
        'value' => $unit_count, // Populate with saved value
    ]);

    woocommerce_wp_text_input([
        'id' => '_included_in_packaging',
        'label' => __('Included in Packaging', 'woocommerce'),
        'desc_tip' => true,
        'value' => $included_in_packaging, // Populate with saved value
    ]);

    woocommerce_wp_text_input([
        'id' => '_age_range',
        'label' => __('Age Range', 'woocommerce'),
        'desc_tip' => true,
        'value' => $age_range, // Populate with saved value
    ]);

    woocommerce_wp_text_input([
        'id' => '_material',
        'label' => __('Material', 'woocommerce'),
        'desc_tip' => true,
        'value' => $material, // Populate with saved value
    ]);

    // Dropdown fields
    woocommerce_wp_select([
        'id' => '_batteries_required',
        'label' => __('Are Batteries Required?', 'woocommerce'),
        'options' => [
            '' => __('Select an option', 'woocommerce'),
            'Yes' => 'Yes',
            'No' => 'No',
            'Not Applicable' => 'Not Applicable'
        ],
        'value' => $batteries_required, // Populate with saved value
    ]);

    woocommerce_wp_select([
        'id' => '_batteries_included',
        'label' => __('Are Batteries Included?', 'woocommerce'),
        'options' => [
            '' => __('Select an option', 'woocommerce'),
            'Yes' => 'Yes',
            'No' => 'No',
            'Not Applicable' => 'Not Applicable'
        ],
        'value' => $batteries_included, // Populate with saved value
    ]);

    // Age restriction checkbox
    woocommerce_wp_checkbox([
        'id' => '_age_restriction',
        'label' => __('18+ Age Restricted Product?', 'woocommerce'),
        'desc_tip' => true,
        'value' => ($age_restriction === '1') ? 'yes' : '', // Populate with saved value
    ]);

    // Textareas
    woocommerce_wp_textarea_input([
        'id' => '_legal_disclaimer',
        'label' => __('Legal Disclaimer', 'woocommerce'),
        'value' => $legal_disclaimer, // Populate with saved value
    ]);

    woocommerce_wp_textarea_input([
        'id' => '_ingredients_allergens',
        'label' => __('Ingredients & Allergen Info (Food/Drink)', 'woocommerce'),
        'value' => $ingredients_allergens, // Populate with saved value
    ]);

    woocommerce_wp_textarea_input([
        'id' => '_important_seller_info',
        'label' => __('Important Seller Information', 'woocommerce'),
        'value' => $important_seller_info, // Populate with saved value
    ]);

    echo '</div>';
}



add_action('woocommerce_before_shop_loop_item_title', 'bragsy_add_age_restriction_badge', 10);

function bragsy_add_age_restriction_badge()
{
    global $product;

    // Check if the product has the age restriction meta set to 'yes'
    $age_restricted = get_post_meta($product->get_id(), '_age_restriction', true);

    if ($age_restricted) {
        echo '<div class="bragsy-age-badge" title="This product is age-restricted. The seller has confirmed that ID/Age verification will be required upon delivery or as required by law. Brags & Partners Ltd assumes no responsibility for legal compliance regarding age-restricted sales.">18+</div>';
    }
}


add_action('woocommerce_single_product_summary', 'bragsy_add_age_restriction_disclaimer_single', 60);

function bragsy_add_age_restriction_disclaimer_single()
{
    global $product;

    $age_restricted = get_post_meta($product->get_id(), '_age_restriction', true);

    if ($age_restricted) {
        echo '<div class="bragsy-age-disclaimer" style="font-size: 12px; color: #555; margin-top: 10px;">
            Disclaimer: Brags & Partners Ltd is a neutral online marketplace platform. All sellers are independent and are solely responsible for ensuring legal compliance, including verifying the age of the buyer for any restricted products. Brags & Partners Ltd does not perform or enforce age verification. By purchasing this product, you agree that the seller is fully responsible for all legal obligations concerning age-restricted sales.
        </div>';
    }
}




// Display the key selling points on the product page dynamically
function add_key_selling_points()
{
    global $post;
    $selling_points = get_post_meta($post->ID, '_key_selling_points', true);

    if (!empty($selling_points)) {
        $points_array = array_filter(array_map('trim', explode("\n", $selling_points))); // Split into lines & remove empty ones
        $points_array = array_slice($points_array, 0, 4); // Limit to 4 points

        if (!empty($points_array)) {
            echo '<ul style="list-style:none; padding:0;">';
            foreach ($points_array as $point) {
                echo '<li style="color:#5c879b; font-weight:bold;">✔ ' . esc_html($point) . '</li>';
            }
            echo '</ul>';
        }
    }
}

add_action('woocommerce_single_product_summary', 'add_key_selling_points', 20);




// 6 Dangerous Goods Checkbox
function add_dokan_dg_field_old($post)
{

    $is_dangerous_good = get_post_meta($post->ID, '_is_dangerous_good', true);
    $sds_document = get_post_meta($post->ID, '_sds_document', true);
    ?>
    <div class="dokan-form-simple-barcode-field">
        <div class="dokan-form-group">
            <label class="dokan-checkbox-inline">
                <input type="checkbox" name="is_dangerous_good" id="is_dangerous_good" value="yes" <?php checked($is_dangerous_good, 'yes'); ?>>
                <?php _e('This product is a Dangerous Good', 'dokan-lite'); ?>
            </label>
        </div>

        <div class="dokan-form-group" id="sds_document_field"
            style="display: <?php echo ($is_dangerous_good === 'yes') ? 'block' : 'none'; ?>;">
            <label for="sds_document"><?php _e('Upload SDS Document (PDF Only)', 'dokan-lite'); ?></label>
            <input type="file" name="sds_document" id="sds_document" accept=".pdf" class="dokan-form-control">

            <?php if (!empty($sds_document)): ?>
                <p><strong>Current SDS:</strong> <a href="<?php echo esc_url($sds_document); ?>" target="_blank">View
                        Document</a></p>
            <?php else: ?>
                <p><em>No SDS Document Uploaded</em></p>
            <?php endif; ?>
        </div>
    </div>

    <script type="text/javascript">
        jQuery(document).ready(function ($) {
            function toggleSDSField() {
                if ($('#is_dangerous_good').is(':checked')) {
                    $('#sds_document_field').show();
                } else {
                    $('#sds_document_field').hide();
                }
            }
            toggleSDSField();
            $('#is_dangerous_good').on('change', function () {
                toggleSDSField();
            });
        });
    </script>
    <?php
}
//add_action('dokan_product_edit_after_inventory_variants', 'add_dokan_dg_field_old');

// Add this to your theme or plugin where Dokan product fields are added
function add_dokan_dg_field($post)
{
    $is_dangerous_good = get_post_meta($post->ID, '_is_dangerous_good', true);
    $sds_document = get_post_meta($post->ID, '_sds_document', true);
    ?>
    <div class="dokan-form-simple-barcode-field">
        <div class="dokan-form-group">
            <label class="dokan-checkbox-inline">
                <input type="checkbox" name="is_dangerous_good" id="is_dangerous_good" value="yes" <?php checked($is_dangerous_good, 'yes'); ?>>
                <?php _e('This product is a Dangerous Good', 'dokan-lite'); ?>
            </label>
        </div>

        <div class="dokan-form-group" id="sds_document_section"
            style="display: <?php echo ($is_dangerous_good === 'yes') ? 'block' : 'none'; ?>;">
            <label for="sds_document"><?php _e('Upload SDS Document (PDF Only)', 'dokan-lite'); ?></label>
            <input type="file" name="sds_document" id="sds_document" accept="application/pdf" class="dokan-form-control">
            <div class="sds-error error" style="color: red; font-size: 13px; display: none;"></div>

            <?php if (!empty($sds_document)): ?>
                <p><strong>Current SDS:</strong> <a href="<?php echo esc_url($sds_document); ?>" target="_blank">View
                        Document</a></p>
            <?php else: ?>
                <p><em>No SDS Document Uploaded</em></p>
            <?php endif; ?>

            <label class="dokan-checkbox-inline" style="margin-top: 10px;">
                <input type="checkbox" name="sds_confirm" id="sds_confirm" value="yes">
                <?php _e('I confirm I have the required SDS documentation for this product.', 'dokan-lite'); ?>
            </label>
            <div class="sds-confirm-error error" style="color: red; font-size: 13px; display: none;"></div>
        </div>
    </div>

    <script type="text/javascript">
        jQuery(document).ready(function ($) {
            const toggleSection = () => {
                const isChecked = $('#is_dangerous_good').is(':checked');
                $('#sds_document_section').toggle(isChecked);

                if (isChecked) {
                    <?php
                    if ($sds_document == '') {
                        ?>
                        $('#sds_document').attr('required', 'required');
                        <?php
                    }
                    ?>

                } else {
                    $('#sds_document').removeAttr('required');
                    $('.sds-error, .sds-confirm-error').hide();
                }
            };

            toggleSection();
            $('#is_dangerous_good').on('change', toggleSection);

            $('form.dokan-product-edit-form').on('submit', function (e) {
                let isValid = true;
                const isDangerous = $('#is_dangerous_good').is(':checked');
                const fileInput = $('#sds_document')[0];
                const confirmCheckbox = $('#sds_confirm');
                const file = fileInput.files[0];

                $('.sds-error, .sds-confirm-error').hide();

                if (isDangerous) {
                    // File Required
                    if (!file) {
                        $('.sds-error').text('Please upload the SDS document.').show();
                        isValid = false;
                    } else {
                        // Check MIME and extension
                        const allowedMime = 'application/pdf';
                        const allowedExt = /\.pdf$/i;

                        if (file.type !== allowedMime || !allowedExt.test(file.name)) {
                            $('.sds-error').text('Only PDF files are allowed.').show();
                            isValid = false;
                        }
                    }

                    // Confirm checkbox required
                    if (!confirmCheckbox.is(':checked')) {
                        $('.sds-confirm-error').text('You must confirm you have the required SDS documentation.').show();
                        isValid = false;
                    }
                }

                if (!isValid) {
                    e.preventDefault();
                }
            });
        });
    </script>
    <?php
}
add_action('dokan_product_edit_after_inventory_variants', 'add_dokan_dg_field');





add_action('wp_footer', function () {
    ?>
    <script>
        jQuery(document).ready(function ($) {
            var dokanForm = $('form.dokan-product-edit-form');
            if (dokanForm.length) {
                dokanForm.attr('enctype', 'multipart/form-data');
            }
        });
    </script>
    <?php
});





function save_dokan_dg_field($product_id)
{
    if (!empty($_POST['is_dangerous_good'])) {
        update_post_meta($product_id, '_is_dangerous_good', 'yes');
    } else {
        update_post_meta($product_id, '_is_dangerous_good', 'no');
    }



    // Check if an SDS document is uploaded
    if (!empty($_FILES['sds_document']['name'])) {
        require_once ABSPATH . 'wp-admin/includes/file.php';

        $file = $_FILES['sds_document'];
        $upload_overrides = array('test_form' => false);
        $uploaded_file = wp_handle_upload($file, $upload_overrides);

        if (!isset($uploaded_file['error'])) {
            update_post_meta($product_id, '_sds_document', $uploaded_file['url']); // Save file URL
        } else {
            error_log('SDS Upload Error: ' . $uploaded_file['error']); // Debug error
        }
    }
}
add_action('dokan_process_product_meta', 'save_dokan_dg_field', 10, 2);




// Generate and set the BRAGTAG ID as the product slug
// function generate_and_set_bragtag_slug( $data, $postarr ) {
//     // Only run for products (not for other post types)
//     if ( $data['post_type'] !== 'product' ) {
//         return $data;
//     }

//     // Check if it's a new product (not a revision or update)
//     if ( !isset( $postarr['ID'] ) || empty( $postarr['ID'] ) ) {
//         // Get the last used BRAGTAG from the option or set it to the starting point
//         $last_bragtag = get_option( 'last_bragtag_id', 97 ); // Default to BR000098 (starting from 98)

//         // Increment the last BRAGTAG by 1
//         $new_bragtag = 'BR' . str_pad( $last_bragtag + 1, 6, '0', STR_PAD_LEFT );

//         // Update the option with the new last BRAGTAG ID
//         update_option( 'last_bragtag_id', $last_bragtag + 1 );

//         // Set the product slug to the generated BRAGTAG
//         $data['post_name'] = $new_bragtag;

//         // Save the new BRAGTAG ID to the product meta
//         $post_id = isset( $postarr['ID'] ) ? $postarr['ID'] : 0;
//         if ( $post_id ) {
//             update_post_meta( $post_id, '_bragtag', $new_bragtag );
//         }
//     }

//     return $data;
// }

// add_filter( 'wp_insert_post_data', 'generate_and_set_bragtag_slug', 10, 2 );

function generate_and_set_bragtag_slug($data, $postarr)
{
    if ($data['post_type'] !== 'product') {
        return $data;
    }

    $post_id = isset($postarr['ID']) ? intval($postarr['ID']) : 0;
    $is_existing_post = $post_id && get_post_status($post_id);

    // Only set BRAGTAG if it's missing (new post or missing meta)
    $existing_bragtag = $is_existing_post ? get_post_meta($post_id, '_bragtag', true) : '';

    if (empty($existing_bragtag)) {
        $last_bragtag = get_option('last_bragtag_id', 97);
        $new_bragtag = 'BR' . str_pad($last_bragtag + 1, 6, '0', STR_PAD_LEFT);

        // Update option
        update_option('last_bragtag_id', $last_bragtag + 1);

        // Set slug
        $data['post_name'] = sanitize_title($new_bragtag);

        // Use `save_post` to save post meta AFTER post is created
        add_action('save_post_product', function ($new_post_id) use ($new_bragtag) {
            if (!get_post_meta($new_post_id, '_bragtag', true)) {
                update_post_meta($new_post_id, '_bragtag', $new_bragtag);
            }
        }, 10, 1);
    }

    return $data;
}
add_filter('wp_insert_post_data', 'generate_and_set_bragtag_slug', 10, 2);



function customize_credit_card_label($gateways)
{

    if (isset($gateways['stripe'])) {
        $gateways['stripe']->title = __('Credit or Debit Card Information', 'woocommerce');
        $gateways['stripe']->description = __('Enter your credit or debit card details below.', 'woocommerce');
    }
    return $gateways;
}
add_filter('woocommerce_available_payment_gateways', 'customize_credit_card_label');

// function disable_payment_gateways($available_gateways) {
//     unset($available_gateways['dokan-stripe-connect']);
//     unset($available_gateways['paypal']); // Remove PayPal
//     return $available_gateways;
// }
// add_filter('woocommerce_available_payment_gateways', 'disable_payment_gateways');

function custom_age_verify_scripts()
{
    if (is_product()) { // WooCommerce product page check
        wp_enqueue_script('custom-age-verify-js', get_stylesheet_directory_uri() . '/asset/js/age-verify.js', array('jquery'), null, true);
        wp_enqueue_style('custom-age-verify-css', get_stylesheet_directory_uri() . '/asset/css/age-verify.css');
    }
}
add_action('wp_enqueue_scripts', 'custom_age_verify_scripts');

// if(isset($_GET['dev']) && $_GET['dev']=='k2'){
//     global $wpdb;

// $dokan_options = $wpdb->get_results(
//     "SELECT option_name, option_value FROM {$wpdb->prefix}options WHERE option_name LIKE 'dokan_%' OR option_name LIKE '%age_verify%'"
// );
// $dokan_options = $wpdb->get_results(
//     "SELECT option_name, option_value FROM {$wpdb->prefix}options WHERE option_name LIKE 'dokan_%' OR option_name LIKE '%age_verify%'"
// );

// echo '<pre>';
// print_r( $dokan_options );
// echo '</pre>';
//     exit();
// }

if (!function_exists('woodmart_filter_age_verify_option_restricted')) {
    function woodmart_filter_age_verify_option_restricted($value, $slug)
    {
        if ($slug === 'age_verify' && is_product()) {
            global $product;
            if (is_object($product)) {
                $age_restricted = get_post_meta($product->get_id(), '_age_restriction', true);
                if ($age_restricted == 1) {
                    return $value; // Keep the original 'age_verify' value (likely true)
                } else {
                    return false; // Disable age verify for this product
                }
            } else {
                return false; // Disable if $product object is not available
            }
        } elseif ($slug === 'age_verify') {
            return false; // Disable age verify on all other pages
        }
        return $value; // Return the original value for other options
    }
    // Remove the previous filter (if it exists) to avoid conflicts
    remove_filter('woodmart_option', 'woodmart_filter_age_verify_option', 10, 2);
    // Add the new filter with the restricted logic
    add_filter('woodmart_option', 'woodmart_filter_age_verify_option_restricted', 10, 2);
}

/**
 * Bypass Dokan product subscription limits for administrators
 */
add_filter('dokan_can_add_product', function ($errors) {
    if (current_user_can('administrator')) {
        return array(); // Clear errors for admins
    }
    return $errors;
}, 99);

add_filter('dokan_can_post', function ($can_post) {
    if (current_user_can('administrator')) {
        return true;
    }
    return $can_post;
}, 99);

add_filter('dokan_can_duplicate_product', function ($can_duplicate) {
    if (current_user_can('administrator')) {
        return true;
    }
    return $can_duplicate;
}, 99);

/**
 * Remove the "Sorry! You can not add or publish any more product" notice for administrators
 * and bypass other subscription-based restrictions.
 */
add_action('init', function () {
    if (current_user_can('administrator')) {
        if (function_exists('dokan_pro') && isset(dokan_pro()->module->product_subscription)) {
            $subscription_module = dokan_pro()->module->product_subscription;

            // Remove the dashboard notice
            remove_action('dokan_before_listing_product', array($subscription_module, 'show_custom_subscription_info'));

            // Remove gallery image count restriction in the UI (JS)
            remove_action('dokan_product_edit_after_main_area', array($subscription_module, 'restrict_gallery_image_count'), 99);

            // Remove gallery image restriction on single product page display
            remove_action('woocommerce_before_single_product', array($subscription_module, 'restrict_added_image_display'));

            // Remove gallery image restriction on save
            remove_filter('dokan_product_data_before_save', array($subscription_module, 'restrict_gallery_image_on_product_edit'), 10);

            // Remove category restriction on save
            remove_filter('dokan_can_add_product', array($subscription_module, 'restrict_product_cat_on_product_create'), 10);
        }
    }
}, 20);


