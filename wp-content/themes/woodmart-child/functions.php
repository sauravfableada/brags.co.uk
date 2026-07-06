<?php
require_once get_stylesheet_directory() . '/functions/Optimization.php';
require_once get_stylesheet_directory() . '/functions/dev_wpk.php';
require_once get_stylesheet_directory() . '/functions/cookie-popup.php';
require_once get_stylesheet_directory() . '/functions/dev_brand_owner.php';
require_once get_stylesheet_directory() . '/functions/authorise_sellers.php';
require_once get_stylesheet_directory() . '/functions/brand.php';
require_once get_stylesheet_directory() . '/functions/top-seller.php';
require_once get_stylesheet_directory() . '/functions/brags_customer_support.php';
require_once get_stylesheet_directory() . '/functions/searched_words.php';
require_once get_stylesheet_directory() . '/functions/dokan_product.php';
require_once get_stylesheet_directory() . '/functions/bargassy_plan.php';
require_once get_stylesheet_directory() . '/functions/restrict-categories.php';
require_once get_stylesheet_directory() . '/functions/brags_seller_support.php';
require_once get_stylesheet_directory() . '/functions/seo.php';
require_once get_stylesheet_directory() . '/functions/VAT-invoice.php';
require_once get_stylesheet_directory() . '/functions/all-report.php';
require_once get_stylesheet_directory() . '/functions/dev_seller_subscription_plan.php';
require_once get_stylesheet_directory() . '/functions/send_mail_register.php';
require_once get_stylesheet_directory() . '/functions/vendor_registration_approval_process.php';
require_once get_stylesheet_directory() . '/functions/Finance.php';
require_once get_stylesheet_directory() . '/functions/login-otp.php';
require_once get_stylesheet_directory() . '/functions/CustomerQuestionsAnswers.php';
require_once get_stylesheet_directory() . '/API/authorization/customer/customer_auth.php';




require_once get_stylesheet_directory() . '/Twilio/autoload.php';

require_once get_stylesheet_directory() . '/inc/amazon/amazon-saller-account.php';
require_once get_stylesheet_directory() . '/inc/amazon/amazon-fulfillment.php';
require_once get_stylesheet_directory() . '/inc/amazon/amazon-product-mapping.php';
require_once get_stylesheet_directory() . '/inc/amazon/amazon-sp-api-settings.php';
require_once get_stylesheet_directory() . '/inc/amazon/fix-fulfillment-availability.php';
//require_once get_stylesheet_directory() . '/inc/amazon/amazon-analytics-dashboard.php';
require_once get_stylesheet_directory() . '/inc/amazon/amazon-inventory-sync.php';

function auto_include_amazon_files()
{
    // Define the directory and prefix
    $directory = get_stylesheet_directory() . '/inc/amazon/';
    $prefix = 'amazon-';

    // Check if directory exists
    if (!is_dir($directory)) {
        error_log("AutoInclude: Directory does not exist - " . $directory);
        return;
    }

    // Get all PHP files with the specified prefix
    $files = glob($directory . $prefix . '*.php');

    if (empty($files)) {
        error_log("AutoInclude: No files found with prefix '" . $prefix . "' in " . $directory);
        return;
    }

    // Include each file
    foreach ($files as $file) {
        if (is_file($file) && is_readable($file)) {
            require_once $file;
        }
    }
}

// Hook into WordPress initialization
 //add_action('init', 'auto_include_amazon_files'); // Temporarily disabled for debugging 504 timeout



/**
 * Enqueue script and styles for child theme
 */
function woodmart_child_enqueue_styles()
{
    // Enqueue child theme style, dependent on parent theme style
    wp_enqueue_style(
        'child-style',
        get_stylesheet_directory_uri() . '/style.min.css',
        array('woodmart-style'),
        woodmart_get_theme_info('Version')
    );


    if (
        is_shop() ||
        is_front_page() ||
        is_category() ||
        is_product_category() ||
        is_search() ||
        is_page(17444)
    ) {
        wp_enqueue_style(
            'shop-style',
            get_stylesheet_directory_uri() . '/assets/css/shop.min.css',
            array(),
            woodmart_get_theme_info('Version')
        );
    }




    // Enqueue main theme CSS
    wp_enqueue_style(
        'main-theme-style',
        get_stylesheet_directory_uri() . '/assets/css/theme-main.min.css',
        array(),
        woodmart_get_theme_info('Version')
    );

    // Enqueue JS file (theme-main.js)
    wp_enqueue_script(
        'main-theme-js',
        get_stylesheet_directory_uri() . '/assets/js/theme-main.min.js',
        array('jquery'), // Add dependencies if needed
        woodmart_get_theme_info('Version'),
        true // Load in footer
    );


    $js_path = get_stylesheet_directory() . '/assets/js/general.js'; // Adjusted path
    $js_url = get_stylesheet_directory_uri() . '/assets/js/general.js';

    // Auto-version based on file last modified time
    wp_enqueue_script(
        'general-js',
        $js_url,
        ['jquery'],
        filemtime($js_path), // Dynamic version
        true
    );


}
add_action('wp_enqueue_scripts', 'woodmart_child_enqueue_styles', 10010);

add_filter('wp_mail_from', function () {
    return defined('WP_MAIL_FROM') ? WP_MAIL_FROM : get_bloginfo('admin_email');
});
add_filter('wp_mail_from_name', function () {
    return defined('WP_MAIL_FROM_NAME') ? WP_MAIL_FROM_NAME : get_bloginfo('name');
});




add_filter('new_user_approve_approve_user_subject', 'custom_seller_approval_subject');

function custom_seller_approval_subject($subject)
{
    //return '[Brags] Seller Registration Approved';
    return 'Your Approved to Sell on Brags!';
}

add_filter('new_user_approve_email_header', 'custom_nua_email_headers');

function custom_nua_email_headers($headers)
{
    // Make sure it's an array
    if (!is_array($headers)) {
        $headers = array($headers);
    }

    // Add HTML content-type if not already present
    $headers[] = 'Content-Type: text/html; charset=UTF-8';

    return $headers;
}



function custom_nua_approve_user_message($message)
{
    // Customize the email subject (if needed, handled separately)
    // Customize the email body
    // $message = "Hi there!\n\n";
    // $message .= "You have been approved to access & Sell on Brags\n\n";
    // $message .= "Your Username: {username}\n\n";
    // $message .= "To Login, visit: {login_url}\n\n";
    // $message .= "To set or reset your password at any time, click the following link: {reset_password_url}";


    // Get user data
    //$username = $user->user_login;
    $login_url = site_url('/my-account/');//wp_login_url();
    $reset_url = site_url('my-account/lost-password/');//wp_lostpassword_url();
    $dashboard_url = site_url('/dashboard'); // Update to your actual dashboard URL
    $plans_url = site_url('/dashboard/subscription/'); // Update as needed
    $policy_url = site_url('/seller-policy'); // Update if different

    // Build the HTML message
    $message = "
        <p>Hi there!</p>

        <p><strong>Congratulations!</strong> You have been approved to access &amp; sell on Brags.</p>

        <p><strong>Your Username is:</strong> {username}</p>

        <p><strong>To login &amp; start selling, visit:</strong>
        <a href='{$login_url}'>{$login_url}</a></p>

        <p><strong>Important:</strong> To start listing products, you will need a Brags Selling Plan.
        You can select a plan by heading to your Seller Dashboard &gt; Brags Selling Plans or click below:<br>
        <a href='{$plans_url}'>Brags Seller Dashboard - Brags</a></p>

        <p><strong>To set or reset your password at any time, click:</strong>
        <a href='{$reset_url}'>{$reset_url}</a></p>

        <p>Happy Selling!</p>

        <p><strong>Any questions?</strong> Contact the Brags Seller Team from your Seller Dashboard at any time.</p>

        <p>Many thanks,<br><strong>Brags</strong></p>

        <p><em>P.S.<br>
        By selling on Brags, you are agreeing to our Seller Policy:</em>
        <a href='{$policy_url}'>Brags Seller Policy - Brags</a></p>
    ";


    return $message;
}
add_filter('new_user_approve_approve_user_message_default', 'custom_nua_approve_user_message');

function registration_custom_filed_add()
{
    ?>
        <p class="form-row form-group form-row-wide">
            <input class="tc_check_box" type="checkbox" id="custom_filed" name="custom_filed" required="required">
            <label for="custom_filed" style="margin-left: 25px; margin-top: -20px;">
                <?php _e('I confirm that I will only sell products on Brags.co.uk to customers based in the United Kingdom only and will offer UK-wide shipping.', 'woocommerce'); ?>
            </label>
            <span class="error-message" style="color: red; display: none;"></span>
        </p>
        <p class="form-row form-group form-row-wide">
            <input class="tc_check_box" type="checkbox" id="brand_ownership_tc_agree" name="brand_ownership_tc_agree"
                require="require" <?php checked($is_checked, 'yes'); ?>>
            <label for="brand_ownership_tc_agree" style="margin-left: 25px; margin-top: -20px;">
                <?php _e('I understand that Brags & Partners Ltd takes a 10% +VAT (12% inc VAT) commission on each successful product sale. I also understand that successful sales through the ‘Braggers’ Programme may result in an additional 5% commission to referral partners. By signing up to sell on Brags, I give Brags & Partners permission to promote my products through their own communication channels, as well as other online selling platforms such as Google Shopping, Facebook Shop, Instagram Shop, TikTok Shop, and more.', 'woocommerce'); ?>
            </label>
            <span class="error-message" style="color: red; display: none;"></span>
        </p>
        <?php
}

add_action('dokan_seller_registration_custom', 'registration_custom_filed_add', 20);

// --------------------------------------- footer js ---------------------------------------------------------

function custom_seller_script_add()
{

    ?>

        <script>



            jQuery(document).ready(function ($) {
                function handleFileUpload(input, maxFiles) {
                    $(input).on('change', function (e) {
                        let files = e.target.files;
                        let allowedTypes = ['image/jpeg', 'image/png'];

                        if (files.length > maxFiles) {
                            alert('You can upload up to ' + maxFiles + ' file(s) only.');
                            $(this).val('');
                            return;
                        }

                        let invalidFiles = [];
                        let validFiles = [];

                        for (let i = 0; i < files.length; i++) {
                            if (!allowedTypes.includes(files[i].type)) {
                                invalidFiles.push(files[i].name);
                            } else {
                                validFiles.push(files[i].name);
                            }
                        }

                        if (invalidFiles.length > 0) {
                            alert('Invalid file(s): ' + invalidFiles.join(', ') + '\nAllowed formats: JPEG, PNG.');
                            $(this).val('');
                            return;
                        }

                        //alert('Files selected: ' + validFiles.join(', '));
                    });
                }

                handleFileUpload('input[name="feat_image_id"]', 1);
            });

            jQuery(document).ready(function ($) {
                $('.instruction-inside .dokan-feat-image-id').attr('accept', '.jpeg,.jpg,.png');
                $('.dokan-spmv-add-new-product-search-box-area .info-section .sub-header').html("Duplication of branded products is not allowed on Brags.co.uk. Please search here (by Barcode, Brand Name, or Product Name) to see if the product already exists and if you have the rights to sell it. If so, just click Add to Store <br> If your product doesn’t already exist on Brags.co.uk, create a new listing below!")
                $('.role-seller .content-half-part.featured-image .dokan-product-gallery')
                    .after('<div class="custom"><p>“Upload your first image on a clear white background showing the product only.”</p><p style="font-size: 12px; color: #ff9800; margin-top: 5px;">“Your first image must be on a clear white background showing the product only. You can upload a further 9 images of your product, we recommend close-ups, lifestyles shots, packaging shots & Infographics outlining your key selling points! Our suggested image size for all images is 2000 x 2000 pixels”</p></div>');


                $('.bragsy-membership-plans .bragsy-plan a.plan-button').text('Join Bragsy');


            });
        </script>


        <?php
}

add_action('wp_footer', 'custom_seller_script_add', 20);

// --------------------------------------- product cat seller add ---------------------------------------------------------

function custom_woocommerce_register_fields()
{

    $args = array(
        'taxonomy' => 'product_cat',
        'orderby' => 'name',
        'hide_empty' => false,
    );
    $categories = get_terms($args);


    ?>
        <div class="vender-form">
            <p class="form-row form-row-wide">
            </p>
            <p class="form-row form-row-wide" id="account_type_wrapper" style="display:none;">
                <label for="account_type">
                    <span style="color: red; font-weight: bold;">
                        <?php _e('Are you an Individual or a Business? Please select an Account Type:', 'woocommerce'); ?>
                    </span>
                </label>

                <select name="account_type" id="account_type">
                    <!-- <option value="">Select</option> -->
                    <option value="individual"><?php _e('Individual (representing yourself)', 'woocommerce'); ?></option>
                    <option value="business"><?php _e('Business (representing a Business)', 'woocommerce'); ?></option>
                </select>
            </p>

            <div id="individual_fields" style="display: none;">

                <div class="field-row">
                    <div class="form-row form-row-wide half-width passport_upload">
                        <label for="passport_upload_individual"><span
                                style="color: red; font-weight: bold;"><?php esc_html_e('Passport Verification for all Sellers: Upload copies of passports for all owners, directors, or partners of the business. Accepted file types: PDF, JPEG, PNG, or Word documents. (Required)', 'dokan'); ?></span></label>
                        <small><?php esc_html_e('Upload Scanned/valid copes of your Passport. We will review all information provided and store privately according to our Privacy Policy.', 'dokan'); ?></small>
                        <br>
                        <p><?php esc_html_e('Files:', 'dokan'); ?></p>
                        <div class="custom-file-upload">
                            <input type="file" name="passport_upload_individual[]" id="passport_upload_individual"
                                accept=".pdf,.doc,.docx,.jpeg,.jpg,.png" data-max-files="10" multiple hidden>
                            <label for="passport_upload_individual" type="button" id="upload_passport_button_individual"
                                class="dokan-btn dokan-btn-theme passport_upload_button"><i
                                    class="fas fa-cloud-arrow-up"></i>&nbsp;
                                <?php esc_html_e('Upload Files', 'dokan'); ?></label>
                        </div>

                    </div>
                </div>
            </div>
            <div id="business_fields" style="display:none;">
                <h3><?php _e('Business Information', 'woocommerce'); ?></h3>

                <div class="field-row">
                    <p class="form-row form-row-wide half-width">
                        <label for="business_name" class="vendor-label"><?php _e('Business Name', 'woocommerce'); ?>
                            <!-- <span class="required">*</span> -->
                        </label>
                        <input type="text" class="input-text" name="business_name" id="business_name"
                            placeholder="Business Name" />
                    </p>
                    <p class="form-row form-row-wide half-width">
                        <label for="business_type" class="vendor-label"><?php _e('Business Type', 'woocommerce'); ?>
                            <!-- <span class="required">*</span> -->
                        </label>
                        <select name="business_type" id="business_type">
                            <option value="">Select Business Type</option>
                            <option value="private_company">Private Company</option>
                            <option value="public_company">Public Company</option>
                            <option value="partnership">Partnership</option>
                            <option value="self_employed">Self-Employed</option>
                            <option value="other">Other</option>
                        </select>

                        <!-- <input type="text" class="input-text" name="business_type" id="business_type" placeholder="Business Type" /> -->
                    </p>
                </div>
                <div class="field-row">
                    <p class="form-row form-row-wide half-width">
                        <label for="tax_id" class="vendor-label"><?php _e('Tax ID/VAT Information', 'woocommerce'); ?>
                            <!-- <span class="required">*</span> -->
                        </label>
                        <input type="text" class="input-text" name="tax_id" id="tax_id" placeholder="Tax ID/VAT Information" />
                    </p>
                    <p class="form-row form-row-wide half-width">
                        <label for="product_categories" class="vendor-label"><?php _e('Product Categories', 'woocommerce'); ?>
                            <!-- <span class="required">*</span> -->
                        </label>
                        <select name="product_categories" id="product_categories">
                            <option value=""><?php _e('Select Product category', 'woocommerce'); ?></option>
                            <?php
                            // Loop through all categories and display them in the dropdown
                            if (!empty($categories) && !is_wp_error($categories)) {
                                foreach ($categories as $category) {
                                    echo '<option value="' . esc_attr($category->term_id) . '">' . esc_html($category->name) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </p>
                </div>
                <p class="form-row form-row-wide half-width">
                    <label
                        for="business_registration_proof"><?php _e('Proof of Ownership / Business Registration', 'woocommerce'); ?>
                        <!-- <span class="required">*</span> -->
                    </label>
                    <input type="file" class="input-text" name="business_registration_proof" id="business_registration_proof" />
                </p>

                <p class="form-row form-row-wide half-width">
                    <input class="tc_check_box" type="checkbox" id="product_liability_insurance"
                        name="product_liability_insurance">
                    <label for="product_liability_insurance" style="margin-left: 25px; margin-top: -20px;">
                        <?php _e('I confirm that I have the appropriate product & public liabilities insurances to sell all of my products to customers in the UK.', 'woocommerce'); ?>
                    </label>
                    <span class="error-message" style="color: red; display: none;"></span>
                </p>

                <div class="form-row form-row-wide half-width passport_upload">
                    <label for="passport_upload_business"><?php esc_html_e('Passport', 'dokan'); ?> <span
                            class="required">(Required)</span></label>

                    <small><?php esc_html_e('Upload Scanned/valid copes of all Owners/Partners/Directors related to your Company. We will review all information provided and store privately according to our Privacy Policy.', 'dokan'); ?></small>
                    <br>
                    <p><?php esc_html_e('Files:', 'dokan'); ?></p>
                    <div class="custom-file-upload">
                        <input type="file" name="passport_upload_business" id="passport_upload_business"
                            accept=".pdf,.doc,.docx,.jpeg,.jpg,.png" multiple hidden>
                        <button type="button" id="upload_passport_button_business"
                            class="dokan-btn dokan-btn-theme passport_upload_button"><i class="fas fa-cloud-arrow-up"></i>&nbsp;
                            <?php esc_html_e('Upload Files', 'dokan'); ?></button>
                    </div>
                </div>


                <script>
                    jQuery(document).ready(function ($) {


                        $(document).ready(function () {
                            function handleFileUpload(input, maxFiles) {
                                $(input).on('change', function (e) {
                                    let files = e.target.files;
                                    let allowedTypes = [
                                        'image/jpeg', 'image/png', 'application/pdf',
                                        'application/msword',
                                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                                    ];

                                    if (files.length > maxFiles) {
                                        alert('You can upload up to ' + maxFiles + ' files only.');
                                        $(this).val('');
                                        return;
                                    }

                                    let invalidFiles = [];
                                    let validFiles = [];

                                    for (let i = 0; i < files.length; i++) {
                                        if (!allowedTypes.includes(files[i].type)) {
                                            invalidFiles.push(files[i].name);
                                        } else {
                                            validFiles.push(files[i].name);
                                        }
                                    }

                                    if (invalidFiles.length > 0) {
                                        alert('Invalid file(s): ' + invalidFiles.join(', ') + '\nAllowed formats: PDF, Word (DOC, DOCX), JPEG, PNG.');
                                        $(this).val('');
                                        return;
                                    }

                                    //alert('Files selected: ' + validFiles.join(', '));
                                });
                            }

                            handleFileUpload('#passport_upload_business', 10);
                            handleFileUpload('#passport_upload_individual', 10);
                        });


                        $('#upload_passport_button').click(function () {
                            $('#passport_upload').click();
                        });

                        $('#passport_upload').change(function () {
                            var fileName = $(this).val().split('\\').pop();
                        });


                        $('#upload_passport_button_business').click(function () {
                            $('#passport_upload_business').click();
                        });

                        $('#passport_upload_business').change(function () {
                            var fileName = $(this).val().split('\\').pop();
                            // $('#passport_file_name').text(fileName || 'No file selected');
                        });
                    });
                </script>

                <style>
                    .custom-file-upload {
                        display: flex;
                        align-items: center;
                        gap: 10px;
                    }

                    #passport_file_name {
                        font-size: 14px;
                        color: #555;
                    }
                </style>

                <?php
                add_action('save_post', 'save_product_liability_insurance', 10, 2);

                function save_product_liability_insurance($post_id, $post)
                {
                    // Check if this is a product post
                    if ($post->post_type == 'product') {
                        // Save the checkbox value (if checked or not)
                        if (isset($_POST['product_liability_insurance'])) {
                            update_post_meta($post_id, '_product_liability_insurance', 'yes');
                        } else {
                            update_post_meta($post_id, '_product_liability_insurance', 'no');
                        }
                    }
                }
                ?>


                <!-- <p class="form-row form-row-wide">
            <label for="product_categories"><?php _e('Product Categories', 'woocommerce'); ?><span class="required">*</span></label>
            <input type="text" class="input-text" name="product_categories" id="product_categories" />
        </p> -->
            </div>

            <!-- Additional Fields for Branded Products -->
            <div id="branded_product_fields" style="display: none;">
                <h3><?php _e('Branded Product Information', 'woocommerce'); ?></h3>

                <!-- Branded Products Checkbox -->
                <p class="form-row form-row-wide d-flex" style="display: flex; align-items: center;">
                    <input type="radio" name="selling_branded_products" id="selling_branded_products" value="branded_products"
                        style="margin-right: 10px;margin-bottom: 27px !important;" require="require" />
                    <!-- <label for="selling_branded_products"><?php _e('I will be selling Branded products on Brags and I/my company has the rights to sell these products in the UK', 'woocommerce'); ?></label> -->
                    <label
                        for="selling_branded_products"><?php _e('I will be selling &nbsp;‘Branded’ products on Brags.co.uk and I/my company have the legal rights to sell these products in the UK.', 'woocommerce'); ?></label>

                </p>

                <p class="form-row form-row-wide d-flex" style="display: flex; align-items: center;">
                    <input type="radio" name="selling_branded_products" value="unbranded_products"
                        id="selling_unbranded_products" style="margin-right: 10px;margin-bottom: 27px !important;" />
                    <label
                        for="selling_unbranded_products"><?php _e('I will be selling &nbsp;‘Unbranded’ products on Brags.co.uk and I/my company have the legal rights to sell these products in the UK.', 'woocommerce'); ?></label>
                </p>



                <!-- Brands Input -->
                <p class="form-row form-row-wide brandedproduct_fields" style="display: none;">
                    <label for="brands_to_sell">Brands you intend to sell on Brags</label>
                    <textarea name="brands_to_sell" id="brands_to_sell" placeholder="Enter brand names" rows="3"
                        style="min-height: 86px !important;"></textarea>
                </p>

                <!-- Proof of Brand Ownership Upload -->
                <p class="form-row form-row-wide brandedproduct_fields" style="display: none;">
                    <label
                        for="brand_ownership_proof"><?php _e('Proof of Brand Ownership/Authorisation from the Brand Owner', 'woocommerce'); ?></label>
                    <!-- <input type="file" class="input-text" name="brand_ownership_proof[]" id="brand_ownership_proof" multiple /> -->
                    <input type="file" class="input-text" name="brand_ownership_proof[]" id="brand_ownership_proof" multiple
                        accept=".pdf, .jpeg, .png, .doc, .docx" />
                    <span class="error-message" style="color: red; display: none;"></span>
                </p>
                <?php
                $is_checked = '';//get_post_meta($post->ID, '_brand_ownership_tc_agree', true);

                ?>
                <!-- <p class="form-row form-row-wide brandedproduct_fields" style="display: none;">
                <input class="tc_check_box" type="checkbox" id="brand_ownership_tc_agree" name="brand_ownership_tc_agree" require="require" <?php //checked($is_checked, 'yes');  ?>>
                <label for="brand_ownership_tc_agree" style="margin-left: 25px; margin-top: -20px;">
                    <?php //_e('I understand that Brags & Partners Ltd takes a 12% commission on each successful product sale. I also understand that successful sales through the ‘Braggers’ Programme may result in an additional 5% commission to referral partners. By signing up to sell on Brags, I give Brags & Partners permission to promote my products through their own communication channels, as well as on platforms such as Google Shopping, Facebook Shop, Instagram Shop, TikTok Shop, and more.', 'woocommerce'); ?>
                </label>
                <span class="error-message" style="color: red; display: none;"></span>
            </p> -->

            </div>

        </div>
        <script>
            jQuery(function ($) {
                $('.dokan-form').on('submit', function (e) {
                    let isValid = true;

                    // File upload validation
                    let fileInput = $('#business_registration_proof');
                    if (fileInput.length && fileInput[0].files.length === 0) {
                        $('.error-message.file-error').text('Please upload a valid proof of business registration.').show();
                        isValid = false;
                    } else {
                        $('.error-message.file-error').hide();
                    }

                    // Checkbox validation
                    let checkbox = $('#product_liability_insurance');
                    if (checkbox.length && !checkbox.is(':checked')) {
                        $('.error-message.checkbox-error').text('You must confirm product liability insurance.').show();
                        isValid = false;
                    } else {
                        $('.error-message.checkbox-error').hide();
                    }

                    // Prevent form submission if validation fails
                    if (!isValid) {
                        e.preventDefault();
                    }
                });
            });

            jQuery(document).ready(function ($) {

                $('input[name="role"]').first().prop('checked', true);
                var selectedRole = $('input[name="role"]:checked').val();

                if (selectedRole === 'seller') {

                    $('#business_fields').show();
                    $('#individual_fields').show();
                    $('#account_type_wrapper').show();
                    $('#branded_product_fields').show();
                    // $("#tc_agree").parent().show();
                } else {
                    $('#account_type_wrapper').hide();
                    $('#branded_product_fields').hide();
                    // $("#tc_agree").parent().hide();
                    $('#business_fields').hide();
                    $('#individual_fields').hide();
                }

                $('input[name="role"]').change(function () {
                    if ($('input[name="role"]:checked').val() === 'seller') {
                        // $('#business_fields').show();
                        $('#account_type_wrapper').show();
                        $('#individual_fields').show();
                        $('#branded_product_fields').show();
                        $('#brags-brand-network').show();
                        // $("#tc_agree").parent().show();
                    } else {
                        $('#account_type_wrapper').hide();
                        $('#branded_product_fields').hide();
                        $('#brags-brand-network').hide();
                        $('#individual_fields').hide();
                        // $("#tc_agree").parent().hide();
                        // $('#business_fields').hide();
                    }
                });





                $(document).ready(function () {
                    // Initially show/hide based on the current value of account_type
                    if ($('#account_type').val() == 'business') {
                        $('#business_fields').show();
                        $('#individual_fields').hide();
                    } else if ($('#account_type').val() == 'individual') {
                        $('#individual_fields').hide();
                        $('#business_fields').hide();
                    }

                    // On change of account_type, toggle the fields accordingly
                    $('#account_type').change(function () {
                        if ($(this).val() == 'business') {
                            $('#business_fields').show();
                            $('#individual_fields').hide();
                        } else if ($(this).val() == 'individual') {
                            $('#individual_fields').show();
                            $('#business_fields').hide();
                        } else {
                            $('#business_fields').hide();
                            $('#individual_fields').hide();
                        }
                    });
                });





                // Branded Product Checkbox Handler
                $('#selling_branded_products').change(function () {
                    if ($(this).is(':checked')) {
                        $('.brandedproduct_fields').show();
                    } else {
                        $('.brandedproduct_fields').hide();
                    }
                });

                // Unbranded Product Checkbox Handler
                $('#selling_unbranded_products').change(function () {
                    if ($(this).is(':checked')) {

                        $('.brandedproduct_fields').hide();
                        $('#unbranded_product_fields').show();
                        // $('#brandedproduct_fields').hide();
                    } else {
                        $('#unbranded_product_fields').hide();
                        // $('#brandedproduct_fields').show();
                    }
                });

                // Trigger initial visibility check on page load for the account type and product selection
                // if ($('#account_type').val() == 'business') {
                // 	$('#business_fields').show();
                // }

                if ($('#selling_branded_products').is(':checked')) {
                    $('#branded_product_fields').show();
                }
                if ($('#selling_unbranded_products').is(':checked')) {
                    $('#unbranded_product_fields').show();
                }
                //  $('input[name="role"]:checked').trigger('change');

                document.getElementById('brand_ownership_proof').addEventListener('change', function () {
                    var fileInput = this;
                    var errorMessage = fileInput.nextElementSibling; // The error message span

                    // Clear any previous error message
                    errorMessage.style.display = 'none';

                    // Check if files are selected
                    if (fileInput.files.length > 0) {
                        var validExtensions = ['.jpg', '.jpeg', '.doc', '.docx', '.pdf'];
                        var valid = true;

                        // Loop through each selected file
                        for (var i = 0; i < fileInput.files.length; i++) {
                            var file = fileInput.files[i];
                            var fileExtension = file.name.split('.').pop().toLowerCase();

                            // Check if the file extension is valid
                            if (!validExtensions.includes('.' + fileExtension)) {
                                valid = false;
                                break;
                            }
                        }

                        // Show error message if file type is invalid
                        if (!valid) {
                            errorMessage.textContent = 'Please upload only JPG, JPEG, DOC, DOCX, or PDF files.';
                            errorMessage.style.display = 'block';
                            jQuery('button[name="register"]').attr('disabled', 'disabled');
                        } else {
                            // Optionally, hide error message if files are valid
                            errorMessage.style.display = 'none';
                            jQuery('button[name="register"]').removeAttr('disabled');
                        }

                        // Check for file count (limit to 10 files)
                        if (fileInput.files.length > 5) {
                            errorMessage.textContent = 'You can upload a maximum of 5 files.';
                            errorMessage.style.display = 'block';
                            jQuery('button[name="register"]').attr('disabled', 'disabled');
                        }
                    }
                });

            });
        </script>




        <?php
}
add_action('woocommerce_register_form', 'custom_woocommerce_register_fields');



function dokan_custom_new_seller_created($vendor_id, $dokan_settings)
{
    $post_data = wp_unslash($_POST);

    $account_type = $post_data['account_type'];
    $business_name = $post_data['business_name'];
    $business_type = $post_data['business_type'];
    $tax_id = $post_data['tax_id'];
    $business_registration_proof = $post_data['business_registration_proof'];
    $product_categories = $post_data['product_categories'];

    $business_registration_proof_url = '';

    $passport_upload_individual = $post_data['passport_upload_individual'];

    $passport_upload_individual_registration_proof_url = '';

    $selling_branded_products = isset($post_data['selling_branded_products']) ? $post_data['selling_branded_products'] : '';
    $brands_to_sell = isset($post_data['brands_to_sell']) ? $post_data['brands_to_sell'] : '';
    $brand_ownership_proof_url = '';
    if (isset($_FILES['passport_upload_individual']) && !empty($_FILES['passport_upload_individual']['name'][0])) { // Check first file exists

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $files = $_FILES['passport_upload_individual'];
        $uploaded_attachment_ids = [];

        // Loop through each file
        foreach ($files['name'] as $key => $value) {
            if ($files['error'][$key] === UPLOAD_ERR_OK) {
                $file = [
                    'name' => $files['name'][$key],
                    'type' => $files['type'][$key],
                    'tmp_name' => $files['tmp_name'][$key],
                    'error' => $files['error'][$key],
                    'size' => $files['size'][$key]
                ];

                // Validate file type and size
                $allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
                $max_size = 2 * 1024 * 1024; // 2MB

                if (!in_array($file['type'], $allowed_types)) {
                    error_log('Invalid file type: ' . $file['type']);
                    continue; // Skip this file
                }

                if ($file['size'] > $max_size) {
                    error_log('File too large: ' . $file['name']);
                    continue;
                }

                // Handle upload
                $upload_overrides = ['test_form' => false];
                $uploaded_file = wp_handle_upload($file, $upload_overrides);

                if ($uploaded_file && !isset($uploaded_file['error'])) {
                    $filetype = wp_check_filetype(basename($uploaded_file['file']), null);

                    $attachment = [
                        'post_mime_type' => $filetype['type'],
                        'post_title' => sanitize_file_name(basename($uploaded_file['file'])),
                        'post_content' => '',
                        'post_status' => 'inherit',
                        'post_author' => $vendor_id
                    ];

                    $attach_id = wp_insert_attachment($attachment, $uploaded_file['file']);
                    $attach_data = wp_generate_attachment_metadata($attach_id, $uploaded_file['file']);
                    wp_update_attachment_metadata($attach_id, $attach_data);

                    $uploaded_attachment_ids[] = $attach_id;
                }
            }
        }

        // If files were uploaded, create verification request
        if (!empty($uploaded_attachment_ids)) {
            global $wpdb;
            $method_id = 1; // Your method ID
            $current_time = current_time('mysql');

            $wpdb->insert(
                $wpdb->prefix . 'dokan_vendor_verification_requests', // CORRECT TABLE
                [
                    'vendor_id' => $vendor_id,
                    'method_id' => $method_id,
                    'documents' => maybe_serialize($uploaded_attachment_ids), // All file IDs
                    'status' => 'pending',
                    'created_at' => $current_time,
                    'updated_at' => $current_time
                ],
                [
                    '%d',
                    '%d',
                    '%s',
                    '%s',
                    '%s',
                    '%s'
                ]
            );
        }
    }



    if (isset($_FILES['business_registration_proof']) && !empty($_FILES['business_registration_proof']['name'])) {
        // Get the uploaded file details


        $uploaded_file = $_FILES['business_registration_proof'];

        // Get the upload directory path using WordPress function
        $upload_dir = wp_upload_dir();
        $target_dir = $upload_dir['basedir'] . '/business_registration_proofs/'; // Folder to store the files

        // Ensure the directory exists, if not, create it
        if (!file_exists($target_dir)) {
            wp_mkdir_p($target_dir); // Creates the directory recursively
        }

        $timestamp = time();
        $file_extension = pathinfo($uploaded_file['name'], PATHINFO_EXTENSION); // Get the file extension
        $new_file_name = $timestamp . '.' . $file_extension;
        // Set the target file path (you can rename the file if needed)
        $target_file = $target_dir . $new_file_name;
        // $target_file = $target_dir . basename($uploaded_file['name']);

        // Check if the file already exists
        if (file_exists($target_file)) {
            wp_die(__('Sorry, file already exists.', 'woocommerce'));
        }

        // Move the uploaded file to the target directory
        if (move_uploaded_file($uploaded_file['tmp_name'], $target_file)) {

            // Get the URL of the uploaded file
            $business_registration_proof_url = $upload_dir['baseurl'] . '/business_registration_proofs/' . $new_file_name;

            // Save the URL of the uploaded file to user meta
            update_user_meta($vendor_id, 'dokan_custom_business_registration_proof', $business_registration_proof_url);
        } else {
            // Handle error if file cannot be moved
            wp_die(__('There was an error moving the uploaded file. Please try again.', 'woocommerce'));
        }
    }

    if ($selling_branded_products == "branded_products") {
        // if (isset($_FILES['brand_ownership_proof']) && !empty($_FILES['brand_ownership_proof']['name'])) {
        if (isset($_FILES['brand_ownership_proof']) && !empty(array_filter($_FILES['brand_ownership_proof']['name']))) {
            $uploaded_files = $_FILES['brand_ownership_proof'];

            // Get the upload directory path using WordPress function
            $upload_dir = wp_upload_dir();
            $target_dir = $upload_dir['basedir'] . '/brand_ownership_proofs/'; // Folder to store the files

            // Ensure the directory exists, if not, create it
            if (!file_exists($target_dir)) {
                wp_mkdir_p($target_dir);
            }

            $brand_ownership_proof_urls = [];

            // Process each uploaded file
            foreach ($uploaded_files['name'] as $key => $file_name) {
                $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
                $new_file_name = time() . '-' . $key . '.' . $file_extension;
                $target_file = $target_dir . $new_file_name;

                // Move the uploaded file to the target directory
                if (move_uploaded_file($uploaded_files['tmp_name'][$key], $target_file)) {
                    $file_url = $upload_dir['baseurl'] . '/brand_ownership_proofs/' . $new_file_name;
                    $brand_ownership_proof_urls[] = $file_url;
                } else {
                    wp_die(__('Error uploading file. Please try again.', 'woocommerce'));
                }
            }

            // Save the proof of ownership URLs in user meta
            if (!empty($brand_ownership_proof_urls)) {
                // Convert the array of URLs into a JSON string
                $brand_ownership_proof_json = json_encode($brand_ownership_proof_urls);

                // Save the JSON string to user meta
                update_user_meta($vendor_id, 'dokan_custom_brand_ownership_proof', $brand_ownership_proof_json);
            }
        }
    }

    if (isset($post_data['tc_agree'])) {
        update_user_meta($vendor_id, '_tc_agree', isset($post_data['tc_agree']) ? 'yes' : 'no');
    }
    if (isset($post_data['custom_filed'])) {
        update_user_meta($vendor_id, '_uk_only_shipping', isset($post_data['custom_filed']) ? 'yes' : 'no');
    }
    if (isset($post_data['brand_ownership_tc_agree'])) {
        update_user_meta($vendor_id, '_brand_ownership_tc_agree', isset($post_data['brand_ownership_tc_agree']) ? 'yes' : 'no');
    }

    update_user_meta($vendor_id, 'dokan_custom_account_type', $account_type);
    update_user_meta($vendor_id, 'dokan_custom_business_name', $business_name);
    update_user_meta($vendor_id, 'dokan_custom_business_type', $business_type);
    update_user_meta($vendor_id, 'dokan_custom_tax_id', $tax_id);
    // update_user_meta($vendor_id, 'dokan_custom_business_registration_proof', $business_registration_proof_url);
    update_user_meta($vendor_id, 'dokan_custom_product_categories', $product_categories);

    update_user_meta($vendor_id, 'dokan_custom_selling_branded_products', $selling_branded_products);
    update_user_meta($vendor_id, 'dokan_custom_brands_to_sell', $brands_to_sell);

    if (!empty($business_registration_proof_url)) {
        update_user_meta($vendor_id, 'dokan_custom_business_registration_proof', $business_registration_proof_url);
    }
}

add_action('dokan_new_seller_created', 'dokan_custom_new_seller_created', 10, 2);


function dokan_custom_vendor_dashboard_fields($vendor)
{
    // Get the custom fields saved for the vendor

    $account_type = get_user_meta($vendor->ID, 'dokan_custom_account_type', true);
    $business_name = get_user_meta($vendor->ID, 'dokan_custom_business_name', true);
    $business_type = get_user_meta($vendor->ID, 'dokan_custom_business_type', true);
    $tax_id = get_user_meta($vendor->ID, 'dokan_custom_tax_id', true);
    $business_registration_proof_url = get_user_meta($vendor->ID, 'dokan_custom_business_registration_proof', true);

    $product_categories = get_user_meta($vendor->ID, 'dokan_custom_product_categories', true);

    ?>
        <h2><?php esc_html_e('Business Information', 'dokan'); ?></h2>

        <table class="form-table">
            <tr>
                <th><label for="account_type"><?php esc_html_e('Account Type', 'dokan'); ?></label></th>
                <td><select name="account_type" id="account_type">
                        <option value="individual" <?php echo (esc_attr($account_type) && $account_type == 'individual') ? "selected" : ''; ?>>
                            <?php _e('Individual', 'woocommerce'); ?>
                        </option>
                        <option value="business" <?php echo (esc_attr($account_type) && $account_type == 'business') ? "selected" : ''; ?>>
                            <?php _e('Business', 'woocommerce'); ?>
                        </option>
                    </select></td>
            </tr>
            <tr>
                <th><label for="business_name"><?php esc_html_e('Business Name', 'dokan'); ?></label></th>
                <td><input type="text" name="business_name" id="business_name" value="<?php echo esc_attr($business_name); ?>"
                        class="regular-text" /></td>
            </tr>

            <tr>
                <th><label for="business_type"><?php esc_html_e('Business Type', 'dokan'); ?></label></th>
                <td><input type="text" name="business_type" id="business_type" value="<?php echo esc_attr($business_type); ?>"
                        class="regular-text" /></td>
            </tr>

            <tr>
                <th><label for="tax_id"><?php esc_html_e('Tax ID', 'dokan'); ?></label></th>
                <td><input type="text" name="tax_id" id="tax_id" value="<?php echo esc_attr($tax_id); ?>"
                        class="regular-text" /></td>
            </tr>

            <tr>
                <th><label
                        for="business_registration_proof"><?php esc_html_e('Business Registration Proof', 'dokan'); ?></label>
                </th>
                <td>
                    <input type="text" name="business_registration_proof" id="business_registration_proof"
                        value="<?php //echo esc_attr($business_registration_proof); ?>" class="regular-text" />
                    <?php if ($business_registration_proof_url): ?>
                            <a href="<?php echo esc_url($business_registration_proof_url); ?>"
                                target="_blank"><?php esc_html_e('View Proof', 'dokan'); ?></a>
                    <?php endif; ?>
                </td>
            </tr>

            <tr>
                <th><label for="product_categories"><?php esc_html_e('Product Categories', 'dokan'); ?></label></th>
                <td>
                    <input type="text" name="product_categories" id="product_categories"
                        value="<?php echo esc_attr($product_categories); ?>" class="regular-text" />
                </td>
            </tr>
        </table>



        <?php
}
add_action('dokan_edit_profile_after', 'dokan_custom_vendor_dashboard_fields');


function dokan_custom_vendor_save_profile($user_id)
{
    if (isset($_POST['business_name'])) {
        update_user_meta($user_id, 'dokan_custom_business_name', sanitize_text_field($_POST['business_name']));
    }
    if (isset($_POST['business_type'])) {
        update_user_meta($user_id, 'dokan_custom_business_type', sanitize_text_field($_POST['business_type']));
    }
    if (isset($_POST['tax_id'])) {
        update_user_meta($user_id, 'dokan_custom_tax_id', sanitize_text_field($_POST['tax_id']));
    }
    if (isset($_POST['business_registration_proof'])) {
        update_user_meta($user_id, 'dokan_custom_business_registration_proof', sanitize_text_field($_POST['business_registration_proof']));
    }
    if (isset($_POST['product_categories'])) {
        update_user_meta($user_id, 'dokan_custom_product_categories', sanitize_text_field($_POST['product_categories']));
    }
}
add_action('dokan_edit_profile_after_save', 'dokan_custom_vendor_save_profile');

function display_custom_account_fields($user)
{
    // Check if the current user is a vendor (using Dokan's function)
    if (dokan_is_seller($user->ID)) {
        // Get custom fields data

        $business_name = get_user_meta($user->ID, 'dokan_custom_business_name', true);
        // echo $business_name;
        // die;
        $business_type = get_user_meta($user->ID, 'dokan_custom_business_type', true);
        $tax_id = get_user_meta($user->ID, 'dokan_custom_tax_id', true);
        $product_categories = get_user_meta($user->ID, 'dokan_custom_product_categories', true);

        ?>
                <h2><?php esc_html_e('Custom Business Information', 'woocommerce'); ?></h2>
                <table class="woocommerce-EditAccount-form-table">
                    <tr>
                        <th><label for="business_name"><?php esc_html_e('Business Name', 'woocommerce'); ?></label></th>
                        <td><input type="text" name="business_name" id="business_name" value="<?php echo esc_attr($business_name); ?>"
                                class="input-text" /></td>
                    </tr>

                    <tr>
                        <th><label for="business_type"><?php esc_html_e('Business Type', 'woocommerce'); ?></label></th>
                        <td><input type="text" name="business_type" id="business_type" value="<?php echo esc_attr($business_type); ?>"
                                class="input-text" /></td>
                    </tr>

                    <tr>
                        <th><label for="tax_id"><?php esc_html_e('Tax ID', 'woocommerce'); ?></label></th>
                        <td><input type="text" name="tax_id" id="tax_id" value="<?php echo esc_attr($tax_id); ?>" class="input-text" />
                        </td>
                    </tr>

                    <tr>
                        <th><label for="product_categories"><?php esc_html_e('Product Categories', 'woocommerce'); ?></label></th>
                        <td><input type="text" name="product_categories" id="product_categories"
                                value="<?php echo esc_attr($product_categories); ?>" class="input-text" /></td>
                    </tr>
                </table>


                <?php
    }
}
// add_action('woocommerce_edit_account_form_end', 'display_custom_account_fields');

add_action('woocommerce_edit_account_form', 'add_custom_business_info_fields', 10);

function add_custom_business_info_fields($user)
{
    $user = wp_get_current_user();
    // print_r(get_user_meta($user->ID));
    // die;

    if (in_array('seller', (array) $user->roles)) {
        // Get custom fields data
        // echo "as";
        // die;
        $account_type = get_user_meta($user->ID, 'dokan_custom_account_type', true);
        $business_name = get_user_meta($user->ID, 'dokan_custom_business_name', true);


        $business_type = get_user_meta($user->ID, 'dokan_custom_business_type', true);
        $tax_id = get_user_meta($user->ID, 'dokan_custom_tax_id', true);
        $business_registration_proof_url = get_user_meta($user->ID, 'dokan_custom_business_registration_proof', true);
        // print_r($business_registration_proof_url);
        $product_categories = get_user_meta($user->ID, 'dokan_custom_product_categories', true);

        $selling_branded_products = get_user_meta($user->ID, 'dokan_custom_selling_branded_products', true);
        $brands_to_sell = get_user_meta($user->ID, 'dokan_custom_brands_to_sell', true);
        // $business_type = get_user_meta($user->ID, 'dokan_custom_business_type', true);
        $brand_ownership_proof = get_user_meta($user->ID, 'dokan_custom_brand_ownership_proof', true);

        if (!empty($brand_ownership_proof) && is_string($brand_ownership_proof)) {
            $decoded_proof = json_decode($brand_ownership_proof, true);

            // Ensure it's properly decoded into an array
            if (json_last_error() === JSON_ERROR_NONE) {
                $brand_ownership_proof = $decoded_proof;
            } else {
                $brand_ownership_proof = []; // Set to an empty array if JSON is invalid
            }
        } else {
            $brand_ownership_proof = []; // Default to an empty array if no data is found
        }

        // if ($selling_branded_products == "unbranded_products") {
        // 	update_user_meta($user->ID, 'dokan_custom_selling_branded_products', '');
        // 	update_user_meta($user->ID, 'dokan_custom_brand_ownership_proof', []);
        // }

        //  $user_meta = get_user_meta($user->ID); // Retrieve all meta data

        // echo '<pre>';
        // print_r($user_meta); // Display all user meta values
        // echo '</pre>';


        $args = array(
            'taxonomy' => 'product_cat',
            'orderby' => 'name',
            'hide_empty' => false,
        );
        $categories = get_terms($args);

        ?>
                <fieldset>
                    <legend><?php _e('Additional information', 'woocommerce'); ?></legend>

                    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                        <label for="tc_agree">
                            <input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox" name="tc_agree"
                                id="tc_agree" value="yes" <?php checked(get_user_meta($user->ID, '_tc_agree', true), 'yes'); ?> />
                            <?php
                            printf(
                                __('I confirm that I have read and agree to the <a target="_blank" href="%s">Terms & Conditions</a> and <a target="_blank" href="%s">Brags Seller Policy</a>. I understand that I/my company must hold the legal rights to sell my products in the UK, maintain my own Product Liability Insurance and acknowledge that Brags & Partners Ltd holds no responsibility for the products I choose to sell on Brags.co.uk.', 'woocommerce'),
                                esc_url('https://brags.co.uk/terms-and-conditions/'),
                                esc_url('https://brags.co.uk/seller-policy/')
                            );
                            ?>
                        </label>
                    </p>

                    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                        <label for="custom_filed">
                            <input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox" name="custom_filed"
                                id="custom_filed" value="yes" <?php checked(get_user_meta($user->ID, '_uk_only_shipping', true), 'yes'); ?> />
                            <?php _e('I confirm that I will only sell products on Brags.co.uk to customers based in the United Kingdom only and will offer UK-wide shipping.', 'woocommerce'); ?>
                        </label>
                    </p>

                    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                        <label for="brand_ownership_tc_agree">
                            <input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox"
                                name="brand_ownership_tc_agree" id="brand_ownership_tc_agree" value="yes" <?php checked(get_user_meta($user->ID, '_brand_ownership_tc_agree', true), 'yes'); ?> />
                            <?php _e('I understand that Brags & Partners Ltd takes a 10% +VAT (12% inc VAT) commission on each successful product sale. I also understand that successful sales through the ‘Braggers’ Programme may result in an additional 5% commission to referral partners. By signing up to sell on Brags, I give Brags & Partners permission to promote my products through their own communication channels, as well as other online selling platforms such as Google Shopping, Facebook Shop, Instagram Shop, TikTok Shop, and more.', 'woocommerce'); ?>
                        </label>
                    </p>
                </fieldset>
                <h2><?php esc_html_e('Business Information', 'woocommerce'); ?></h2>
                <table class="woocommerce-EditAccount-form-table">
                    <tr>
                        <th><label for="account_type"><?php esc_html_e('Account Type', 'dokan'); ?></label></th>
                        <td><select name="account_type" id="account_type">
                                <option value="individual" <?php echo (esc_attr($account_type) && $account_type == 'individual') ? "selected" : ''; ?>>
                                    <?php _e('Individual', 'woocommerce'); ?>
                                </option>
                                <option value="business" <?php echo (esc_attr($account_type) && $account_type == 'business') ? "selected" : ''; ?>>
                                    <?php _e('Business', 'woocommerce'); ?>
                                </option>
                            </select></td>
                    </tr>
                    <tr class="business_fields">
                        <th><label for="business_name"><?php esc_html_e('Business Name', 'woocommerce'); ?></label></th>
                        <td><input type="text" name="business_name" id="business_name" value="<?php echo esc_attr($business_name); ?>"
                                class="input-text" /></td>
                    </tr>

                    <tr class="business_fields">
                        <th><label for="business_type"><?php esc_html_e('Business Type', 'woocommerce'); ?></label></th>
                        <td><select name="business_type" id="business_select">
                                <option value="">Select Business Type</option>
                                <option value="private_company" <?php selected($business_type, 'private_company'); ?>>Private Company
                                </option>
                                <option value="public_company" <?php selected($business_type, 'public_company'); ?>>Public Company
                                </option>
                                <option value="partnership" <?php selected($business_type, 'partnership'); ?>>Partnership</option>
                                <option value="self_employed" <?php selected($business_type, 'self_employed'); ?>>Self-Employed</option>
                                <option value="other" <?php selected($business_type, 'other'); ?>>Other</option>
                            </select>
                            <!-- <input type="text" name="business_type" id="business_type" value="<?php //echo esc_attr($business_type);
                                    ?>" class="input-text" /> -->
                        </td>
                    </tr>

                    <tr class="business_fields">
                        <th><label for="tax_id"><?php esc_html_e('Tax ID', 'woocommerce'); ?></label></th>
                        <td><input type="text" name="tax_id" id="tax_id" value="<?php echo esc_attr($tax_id); ?>" class="input-text" />
                        </td>
                    </tr>

                    <tr class="business_fields">
                        <th><label
                                for="business_registration_proof"><?php esc_html_e('Proof of Ownership / Business Registration', 'woocommerce'); ?></label>
                        </th>
                        <td>
                            <input type="file" class="input-text" name="business_registration_proof" id="business_registration_proof" />
                            <?php if ($business_registration_proof_url): ?>
                                    <p><a href="<?php echo esc_url($business_registration_proof_url); ?>"
                                            target="_blank"><?php esc_html_e('View Proof', 'woocommerce'); ?></a></p>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <tr class="business_fields">
                        <th><label for="product_categories"><?php esc_html_e('Product Categories', 'woocommerce'); ?></label></th>
                        <!-- <td><input type="text" name="product_categories" id="product_categories" value="<?php echo esc_attr($product_categories); ?>" class="input-text" /></td> -->
                        <td><select name="product_categories" id="product_categories">
                                <option value=""><?php _e('Select a category', 'woocommerce'); ?></option>
                                <?php
                                // Loop through all categories and display them in the dropdown
                                if (!empty($categories) && !is_wp_error($categories)) {
                                    foreach ($categories as $category) {
                                        $selected = (esc_attr($product_categories) && $product_categories == $category->term_id) ? 'selected' : '';
                                        echo '<option value="' . esc_attr($category->term_id) . '" ' . $selected . '>' . esc_html($category->name) . '</option>';
                                    }
                                }
                                ?>
                            </select></td>
                    </tr>
                </table>
                <script type="text/javascript">
                    jQuery(document).ready(function ($) {
                        // Add enctype="multipart/form-data" if it's the Edit Account form
                        $('form.edit-account').attr('enctype', 'multipart/form-data');
                    });
                </script>

                <h2 style="padding-top:25px;"><?php esc_html_e('Branded Products Information', 'dokan'); ?></h2>

                <table class="form-table">
                    <tr id="selling_branded_products_row">
                        <th style="font-weight: 500 !important;"><?php _e('Selling Branded Products', 'woocommerce'); ?></th>
                        <td>
                            <input type="radio" name="selling_branded_products" id="selling_branded_products" value="branded_products"
                                style="margin-right: 10px;" <?php checked($selling_branded_products, 'branded_products'); ?> />

                            <label
                                for="selling_branded_products"><?php _e('I will be selling Branded products on Brags and I/my company has the rights to sell these products in the UK', 'woocommerce'); ?></label>
                        </td>
                    </tr>

                    <tr id="selling_unbranded_products_row">
                        <th style="font-weight: 500 !important;"><?php _e('Selling Unbranded Products', 'woocommerce'); ?></th>
                        <td>
                            <input type="radio" name="selling_branded_products" value="unbranded_products"
                                id="selling_unbranded_products" style="margin-right: 10px;" <?php checked($selling_branded_products, 'unbranded_products'); ?> />
                            <label
                                for="selling_unbranded_products"><?php _e('I will be selling unbranded products on Brags and I/my company has the rights to sell these products in the UK', 'woocommerce'); ?></label>
                        </td>
                    </tr>

                    <!-- Brands Input -->
                    <tr id="brands_to_sell_row" class="brandedproduct_fields">
                        <th><label for="brands_to_sell"><?php _e('Brands to Sell', 'woocommerce'); ?></label></th>
                        <td>
                            <textarea name="brands_to_sell" id="brands_to_sell" placeholder="Enter brand names" rows="3"
                                style="min-height: 86px;margin-top: 15px !important;"><?php echo esc_attr($brands_to_sell); ?></textarea>
                        </td>
                    </tr>

                    <!-- Proof of Brand Ownership Upload -->
                    <tr id=" brand_ownership_proof_row" class="brandedproduct_fields">
                        <th><label for="brand_ownership_proof"><?php _e('Proof of Brand Ownership', 'woocommerce'); ?></label></th>
                        <td>
                            <input type="file" class="input-text" name="brand_ownership_proof[]" id="brand_ownership_proof"
                                style="padding-top: 15px !important;" multiple />
                            <span class="error-message" style="color: red; display: none;"></span>
                            <?php if (!empty($brand_ownership_proof)): ?>
                                    <div style="padding-top: 20px !important; display: flex; flex-wrap: wrap; gap: 10px;">
                                        <?php foreach ($brand_ownership_proof as $index => $proof): ?>
                                                <?php
                                                // Check if the file is an image
                                                $file_extension = pathinfo($proof, PATHINFO_EXTENSION);
                                                $is_image = in_array(strtolower($file_extension), ['jpg', 'jpeg', 'png', 'gif']);
                                                ?>

                                                <?php if ($is_image): ?>
                                                        <div style="width: 14%; text-align: center;">
                                                            <a href="<?php echo esc_url($proof); ?>" target="_blank">
                                                                <img src="<?php echo esc_url($proof); ?>" width="100" height="100"
                                                                    style="border: 1px solid #ddd; padding: 5px;" />
                                                            </a>
                                                        </div>
                                                <?php else: ?>
                                                        <!-- For non-image files, display each on a new line -->
                                                        <div style="width: 100%;">
                                                            <a href="<?php echo esc_url($proof); ?>" target="_blank"
                                                                style="color:#00f;"><?php echo esc_html(pathinfo($proof, PATHINFO_BASENAME)); ?></a>
                                                        </div>
                                                <?php endif; ?>

                                                <!-- Creates a new row after 4 items for images -->
                                                <?php if (($index + 1) % 4 == 0 && $is_image): ?>
                                                        <div style="width: 100%;"></div> <!-- New row for images after every 4th one -->
                                                <?php endif; ?>

                                        <?php endforeach; ?>
                                    </div>
                            <?php endif; ?>


                        </td>

                    </tr>

                </table>

                <script type="text/javascript">
                    jQuery(document).ready(function ($) {
                        var selectedValue = $('input[name="selling_branded_products"]:checked').val();
                        var account_type = $('select[name="account_type"] option:selected').val();

                        if (account_type == 'business') {
                            $('.business_fields').show();
                        } else {
                            $('.business_fields').hide();

                        }

                        if (selectedValue == "unbranded_products") {
                            jQuery('.brandedproduct_fields').hide();
                        } else {
                            jQuery('.brandedproduct_fields').show();
                        }

                        jQuery('#selling_branded_products').change(function () {
                            // if ($(this).is(':checked')) {
                            if ($(this).val() == "unbranded_products") {
                                $('.brandedproduct_fields').hide();
                                // $("#brands_to_sell").text('');
                                // $("#brands_to_sell").text('');
                            } else {
                                $('.brandedproduct_fields').show();
                                $("#brands_to_sell").text('');
                            }
                        });

                        // Unbranded Product Checkbox Handler
                        jQuery('#selling_unbranded_products').change(function () {
                            if ($(this).is(':checked')) {

                                $('.brandedproduct_fields').hide();
                                $('#unbranded_product_fields').show();
                                // $('#brandedproduct_fields').hide();
                            } else {
                                $('#unbranded_product_fields').hide();
                                // $('#brandedproduct_fields').show();
                            }
                        });

                        jQuery('#account_type').change(function () {
                            if ($(this).val() == 'business') {
                                $('.business_fields').show();
                                $("#business_name").val('');
                                $("#business_type").val('');
                                $("#tax_id").val('');
                                $("#product_categories").val('');
                                $('#business_registration_proof').siblings('p').remove();
                            } else {
                                $('.business_fields').hide();

                            }
                        });

                        document.getElementById('brand_ownership_proof').addEventListener('change', function () {
                            var fileInput = this;
                            var errorMessage = fileInput.nextElementSibling; // The error message span

                            // Clear any previous error message
                            errorMessage.style.display = 'none';

                            // Check if files are selected
                            if (fileInput.files.length > 0) {
                                var validExtensions = ['.jpg', '.jpeg', '.doc', '.docx', '.pdf'];
                                var valid = true;

                                // Loop through each selected file
                                for (var i = 0; i < fileInput.files.length; i++) {
                                    var file = fileInput.files[i];
                                    var fileExtension = file.name.split('.').pop().toLowerCase();

                                    // Check if the file extension is valid
                                    if (!validExtensions.includes('.' + fileExtension)) {
                                        valid = false;
                                        break;
                                    }
                                }

                                // Show error message if file type is invalid
                                if (!valid) {
                                    errorMessage.textContent = 'Please upload only JPG, JPEG, DOC, DOCX, or PDF files.';
                                    errorMessage.style.display = 'block';
                                    jQuery('input[name="dokan_save_account_details"]').attr('disabled', 'disabled');
                                } else {
                                    // Optionally, hide error message if files are valid
                                    errorMessage.style.display = 'none';
                                    jQuery('input[name="dokan_save_account_details"]').removeAttr('disabled');
                                }

                                // Check for file count (limit to 10 files)
                                if (fileInput.files.length > 10) {
                                    errorMessage.textContent = 'You can upload a maximum of 10 files.';
                                    errorMessage.style.display = 'block';
                                    jQuery('input[name="dokan_save_account_details"]').attr('disabled', 'disabled');
                                }
                            }
                        });
                    });
                </script>
        <?php }
}


// Save custom business info when updating account details
function save_custom_business_info_fields($user_id)
{
    // Check if the user has the 'seller' role
    $user = get_userdata($user_id);

    if (in_array('seller', (array) $user->roles)) {

        // Save text fields
        if (isset($_POST['account_type'])) {
            update_user_meta($user_id, 'dokan_custom_account_type', sanitize_text_field($_POST['account_type']));
        }
        if (isset($_POST['business_name'])) {
            update_user_meta($user_id, 'dokan_custom_business_name', sanitize_text_field($_POST['business_name']));
        }
        if (isset($_POST['business_type'])) {
            update_user_meta($user_id, 'dokan_custom_business_type', sanitize_text_field($_POST['business_type']));
        }
        if (isset($_POST['tax_id'])) {
            update_user_meta($user_id, 'dokan_custom_tax_id', sanitize_text_field($_POST['tax_id']));
        }
        if (isset($_POST['product_categories'])) {
            update_user_meta($user_id, 'dokan_custom_product_categories', sanitize_text_field($_POST['product_categories']));
        }

        // Handle file upload for business registration proof
        if (isset($_FILES['business_registration_proof']) && !empty($_FILES['business_registration_proof']['name'])) {
            // Handle the file upload
            $uploaded_file = $_FILES['business_registration_proof'];
            $upload_dir = wp_upload_dir();
            $target_dir = $upload_dir['basedir'] . '/business_registration_proofs/';

            $timestamp = time();
            $file_extension = pathinfo($uploaded_file['name'], PATHINFO_EXTENSION); // Get the file extension
            $new_file_name = $timestamp . '.' . $file_extension;
            // Set the target file path (you can rename the file if needed)

            $target_file = $target_dir . $new_file_name;


            // Ensure the directory exists
            if (!file_exists($target_dir)) {
                wp_mkdir_p($target_dir);
            }

            // Set the target file path and move the file
            // $target_file = $target_dir . basename($uploaded_file['name']);
            if (move_uploaded_file($uploaded_file['tmp_name'], $target_file)) {
                $business_registration_proof_url = $upload_dir['baseurl'] . '/business_registration_proofs/' . $new_file_name;
                update_user_meta($user_id, 'dokan_custom_business_registration_proof', $business_registration_proof_url);
            }
        }



        if (isset($_POST['selling_branded_products'])) {
            update_user_meta($user_id, 'dokan_custom_selling_branded_products', sanitize_text_field($_POST['selling_branded_products']));

            if ($_POST['selling_branded_products'] == "unbranded_products") {

                delete_user_meta($user_id, 'dokan_custom_brands_to_sell');

                // Remove the brand ownership proof meta data
                delete_user_meta($user_id, 'dokan_custom_brand_ownership_proof');
            }
        }

        if (isset($_POST['brands_to_sell'])) {
            update_user_meta($user_id, 'dokan_custom_brands_to_sell', sanitize_textarea_field($_POST['brands_to_sell']));
        }



        if (isset($_FILES['brand_ownership_proof']) && !empty($_FILES['brand_ownership_proof']['name'][0])) {
            // Get existing brand ownership proof URLs from user meta
            $existing_urls = get_user_meta($user_id, 'dokan_custom_brand_ownership_proof', true);
            $existing_urls = !empty($existing_urls) ? json_decode($existing_urls, true) : [];

            $uploaded_files = $_FILES['brand_ownership_proof'];
            $upload_dir = wp_upload_dir();
            $target_dir = $upload_dir['basedir'] . '/brand_ownership_proofs/';
            $new_uploaded_urls = [];

            // Ensure the target directory exists
            if (!file_exists($target_dir)) {
                wp_mkdir_p($target_dir);
            }

            // Process the uploaded files
            foreach ($uploaded_files['name'] as $key => $file_name) {
                if ($uploaded_files['error'][$key] == 0) {
                    $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
                    $new_file_name = time() . '-' . $key . '.' . $file_extension;
                    $target_file = $target_dir . $new_file_name;

                    // Move the file to the target directory
                    if (move_uploaded_file($uploaded_files['tmp_name'][$key], $target_file)) {
                        $file_url = $upload_dir['baseurl'] . '/brand_ownership_proofs/' . $new_file_name;
                        $new_uploaded_urls[] = $file_url;
                    }
                }
            }

            // Merge the existing file URLs with the newly uploaded ones
            $all_uploaded_urls = array_merge($existing_urls, $new_uploaded_urls);

            // Update the user meta with the merged file URLs
            if (!empty($all_uploaded_urls)) {
                update_user_meta($user_id, 'dokan_custom_brand_ownership_proof', json_encode($all_uploaded_urls));
            }
        }
    }
}
add_action('woocommerce_save_account_details', 'save_custom_business_info_fields', 10, 1);



function remove_dokan_dashboard_sidebar()
{
    // Remove the sidebar from the Dokan dashboard
    remove_action('dokan_dashboard_sidebar', 'dokan_get_sidebar', 10);
}
add_action('wp', 'remove_dokan_dashboard_sidebar');




function add_enctype_to_woocommerce_registration_form()
{
    // Check if this is the WooCommerce registration page
    if (is_account_page() && !is_user_logged_in()) {
        ?>
                <script>
                    jQuery(document).ready(function ($) {
                        // Add enctype="multipart/form-data" to the form if it's not already there
                        $('form.woocommerce-form').attr('enctype', 'multipart/form-data');




                    });
                </script>



                <?php
    }
}
add_action('wp_footer', 'add_enctype_to_woocommerce_registration_form');

function add_enctype_to_edit_account_form($form)
{
    // Only add enctype for the edit account form
    if (is_account_page() && is_user_logged_in()) {
        // Check if the form already has 'enctype' attribute, and if not, add it
        if (strpos($form, 'enctype="multipart/form-data"') === false) {
            $form = str_replace('<form', '<form enctype="multipart/form-data"', $form);
        }
    }

    return $form;
}

add_filter('woocommerce_edit_account_form', 'add_enctype_to_edit_account_form');




function destroy_and_reinitialize_select2()
{
    echo '<script type="text/javascript">
            document.addEventListener("DOMContentLoaded", function() {
                setTimeout(function() {
                    var billingCountry = jQuery("#billing_country");
                    var billingstate = jQuery("#billing_state");
                    if (billingCountry.hasClass("select2-hidden-accessible")) {

                        billingCountry.select2("destroy");
                    }

					 if (billingstate.hasClass("select2-hidden-accessible")) {

                        billingstate.select2("destroy");
                    }

                }, 2000);

                	jQuery("#billing_country").on("change",function(){
				   		setTimeout(function() {
							jQuery("#billing_country").select2("destroy");
							jQuery("#billing_state").select2("destroy");
						}, 500);
					});

					jQuery("#billing_state").on("change",function(){
				   		setTimeout(function() {
							jQuery("#billing_state").select2("destroy");
						}, 500);
					});
            });
          </script>';
}
add_action('wp_footer', 'destroy_and_reinitialize_select2');




add_filter('new_user_approve_default_status', 'custom_user_approval_status', 10, 2);

function custom_user_approval_status($status, $user_id)
{
    $user = get_userdata($user_id);

    // Check if user role is 'seller'
    if (isset($user->roles) && !in_array('seller', $user->roles)) {
        $status = 'approved';
    }

    return $status;
}



function change_vendor_text($translated_text, $text, $domain)
{
    // Check if it's the specific string from Dokan plugin (or other context)
    if ($domain == 'dokan-lite' && $text == 'I am a vendor') {
        $translated_text = 'I am a seller'; // Change to your desired text
    }

    if ($domain == 'dokan-lite' && $text == 'Vendor Dashboard') {
        $translated_text = 'Seller Dashboard'; // Change to your desired text
    }

    if ($domain == 'dokan-lite' && $text == 'Vendor') {
        $translated_text = 'Seller'; // Change to your desired text
    }

    if ($text == 'Vendor dashboard') {
        $translated_text = 'Seller dashboard'; // Change to your desired text
    }

    if ($domain == 'dokan-lite' && $text == 'Become a Vendor') {
        $translated_text = 'Become a Seller'; // Change to your desired text
    }

    if ($domain == 'dokan-lite' && $text == 'Vendors can sell products and manage a store with a vendor dashboard.') {
        $translated_text = 'Sellers can sell products and manage a store with a seller dashboard.'; // Change to your desired text
    }
    if ($domain == 'dokan-lite' && $text == 'Update account to Vendor') {
        $translated_text = 'Update account to Seller'; // Change to your desired text
    }

    if ($domain == 'dokan-lite' && $text == 'Go to Vendor Dashboard') {
        $translated_text = 'Go to Seller Dashboard'; // Change to your desired text
    }



    return $translated_text;
}
add_filter('gettext', 'change_vendor_text', 20, 3);

// Add 'Brand Name' field and 'Unbranded' checkbox to WooCommerce Product Data > General
function add_brand_name_field_to_general_product_data()
{
    global $post;

    // Get current product meta
    $brand_name = get_post_meta($post->ID, '_brand_name', true);
    $unbranded = get_post_meta($post->ID, '_unbranded', true);

    ?>
        <div class="options_group">
            <p class="form-field">
                <label for="brand_name"><?php _e('Brand Name', 'woodmart-child'); ?></label>
                <input type="text" name="brand_name" id="brand_name" class="short"
                    value="<?php echo esc_attr($brand_name); ?>" />
            </p>
            <p class="form-field">
                <label for="unbranded">
                    <input type="checkbox" name="unbranded" id="unbranded" value="1" <?php checked($unbranded, 'yes'); ?> />
                    <?php _e('This product is Unbranded', 'woodmart-child'); ?>
                </label>
            </p>
        </div>
        <?php
}
//add_action('woocommerce_product_options_general_product_data', 'add_brand_name_field_to_general_product_data');


function custom_page_load_action()
{
    ?>
        <script>
            // jQuery(document).ready(function() {

            // });
            jQuery("div.col-register-text h2.wd-login-title").text('');
            jQuery("div.col-register-text h2.wd-login-title").text('REGISTER (as a Customer or a Seller)');
            jQuery("div.col-register-text div.registration-info").html(
                'If you are looking to purchase products on Brags, please select &nbsp;&nbsp;‘<b>I am a Customer</b>’&nbsp;to access your order status and history. If you are a Seller, please select &nbsp;&nbsp;‘<b>I am Seller</b>’&nbsp;to apply to sell.'
            );



            setInterval(function () {
                if (jQuery('.dokan-stripe-intent').length === 0) {
                    jQuery('div#payment ul li.payment_method_dokan-stripe-connect').remove();
                }
            }, 100);
        </script>
        <?php
}

add_action('wp_footer', 'custom_page_load_action'); // Use wp hook to check on page load



if (!function_exists('woodmart_show_categories_dropdown')) {
    function woodmart_show_categories_dropdown()
    {
        woodmart_enqueue_inline_style('wd-search-cat'); // Enqueue the necessary styles

        $args = array(
            'hide_empty' => 0, // Show categories even if empty (no products).
        );
        $terms = get_terms('product_cat', apply_filters('woodmart_header_search_categories_dropdown_args', $args));

        if (!empty($terms) && !is_wp_error($terms)) {
            $dropdown_classes = '';

            // Optional: Customize the dropdown appearance based on the color scheme
            if ('light' === whb_get_dropdowns_color()) {
                $dropdown_classes .= ' color-scheme-light';
            }

            $dropdown_classes .= woodmart_get_old_classes(' list-wrapper');

            // Enqueue JS script for dropdown
            woodmart_enqueue_js_script('simple-dropdown');
            ?>
                        <div class="wd-search-cat wd-scroll<?php echo woodmart_get_old_classes(' search-by-category'); ?>">
                            <input type="hidden" name="product_cat" value="0">
                            <a href="#" rel="nofollow" data-val="0">
                                <span>
                                    <?php esc_html_e('Select category', 'woodmart'); ?>
                                </span>
                            </a>
                            <div
                                class="wd-dropdown wd-dropdown-search-cat wd-dropdown-menu wd-scroll-content wd-design-default<?php echo esc_attr($dropdown_classes); ?>">
                                <ul class="wd-sub-menu<?php echo woodmart_get_old_classes(' sub-menu'); ?>">
                                    <li style="display:none;"><a href="#" data-val="0"><?php esc_html_e('Select category', 'woodmart'); ?></a>
                                    </li>
                                    <?php
                                    // Group categories by parent
                                    $parent_categories = [];

                                    // Group categories by parent ID
                                    foreach ($terms as $term) {
                                        $parent_categories[$term->parent][] = $term;
                                    }

                                    // Loop through parent categories (ID 0 is for top-level categories)
                                    foreach ($parent_categories[0] as $parent) {
                                        ?>
                                            <li>
                                                <a href="#" data-val="<?php echo esc_attr($parent->slug); ?>"><?php echo esc_html($parent->name); ?></a>
                                                <?php
                                                // Check if the parent has child categories
                                                if (isset($parent_categories[$parent->term_id])) {
                                                    echo '<ul class="child-new-categories" style="margin-left: 20px;">';
                                                    // Loop through the child categories of this parent category
                                                    foreach ($parent_categories[$parent->term_id] as $child) {
                                                        ?>
                                                            <li><a href="#" data-val="<?php echo esc_attr($child->slug); ?>"><?php echo esc_html($child->name); ?></a>
                                                            </li>
                                                            <?php
                                                    }
                                                    echo '</ul>';
                                                }
                                                ?>
                                            </li>
                                            <?php
                                    }
                                    ?>
                                </ul>
                            </div>
                        </div>
                        <?php
        }
    }
}

// To make sure the function is available when you need it, you can hook it into the WordPress `wp_footer` or a custom hook, if required. For example:
add_action('my_custom_hook', 'woodmart_show_categories_dropdown', 20); // You can change the hook as needed




function replace_uap_affiliate_text_with_jquery()
{
    ?>
        <script>
            jQuery(document).ready(function ($) {
                // Function to replace text
                function replaceText() {
                    $('body').find('*').contents().filter(function () {
                        return this.nodeType === 3; // Only text nodes
                    }).each(function () {
                        // Replace "BRAGSAffiliate" with "Braggers Programme"
                        this.nodeValue = this.nodeValue.replace(/BRAGSAffiliate/gi, 'Braggers Programme');

                        // Replace "affiliate" with "Braggers Programme" or "Braggers"
                        this.nodeValue = this.nodeValue.replace(/affiliate/gi, function (match, offset,
                            originalText) {
                            // Check the text preceding the match
                            var before = originalText.substring(Math.max(0, offset - 15),
                                offset); // Grab 15 characters before match

                            // If the preceding text contains "BRAGSAffiliate" (case-insensitive)
                            if (/BRAGSAffiliate/i.test(before)) {
                                return 'Braggers Programme';
                            }

                            // Get the text immediately after the matched word
                            var after = originalText.substring(offset + match.length);

                            // If "affiliate" is followed by "program", replace with "Braggers"
                            if (/^\s+program\b/i.test(after)) {
                                return 'Braggers';
                            } else {
                                // For all other cases, replace "affiliate" with "Braggers Programme"
                                return 'Braggers Programme';
                            }
                        });
                    });
                }

                // Perform text replacement on page load
                replaceText();
            });

            jQuery(document).ready(function ($) {

                // When the product form is submitted
                $('form.dokan-product-edit-form').submit(function (e) {
                    var sku = jQuery('input#_sku').val(); // Get the value of the SKU field
                    jQuery('span.sku-error-message').remove();
                    if (sku.trim() === '') { // Check if SKU is empty
                        e.preventDefault(); // Prevent form submission
                        if (jQuery('input#_sku').next('span.sku-error-message').length === 0) {
                            // Append the error message only if it doesn't exist
                            jQuery('input#_sku').after(
                                "<span class='sku-error-message' style='color:#f05025;'>SKU is required.</span>"
                            );
                        }
                        jQuery('input#_sku').focus(); // Focus on the SKU field
                        return false; // Stop further form submission
                    }
                });

                $('form.dokan-product-edit-form').submit(function (e) {
                    var isValid = true;

                    // Loop through all dynamic SKU input fields
                    $('input[name^="variable_sku"]').each(function () {
                        var sku = jQuery(this).val(); // Get the value of the SKU field
                        jQuery(this).next('span.sku-error-message').remove(); // Remove any existing error messages

                        if (sku.trim() === '') { // Check if SKU is empty
                            e.preventDefault(); // Prevent form submission
                            if (jQuery(this).next('span.sku-error-message').length === 0) {
                                // Append the error message only if it doesn't exist
                                jQuery(this).after(
                                    "<span class='sku-error-message' style='color:#f05025;'>SKU is required.</span>"
                                );
                            }
                            jQuery(this).focus(); // Focus on the SKU field
                            isValid = false; // Mark the form as invalid
                        }
                    });

                    if (!isValid) {
                        return false; // Stop further form submission if there are errors
                    }
                });

            });
        </script>




        <?php
}
add_action('wp_footer', 'replace_uap_affiliate_text_with_jquery');


// Add Brand Name field to product edit page in Dokan frontend
function add_brand_name_field_to_product_page()
{
    global $post;

    // Get the current brand name if already set
    $brand_name = get_post_meta($post->ID, '_brand_name', true);
    $custom_brand = get_post_meta($post->ID, '_custom_brand_name', true);
    $no_brand = get_post_meta($post->ID, '_no_brand', true);


    global $wpdb;
    $user_id = get_current_user_id();
    $taxonomy = 'product_brand';


    // Fetch approved brand IDs for the current user from the seller_requests table

    $brand_ids = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT brand_id FROM {$wpdb->prefix}seller_requests
            WHERE seller_id = %d AND status = 'approved'",
            $user_id
        )
    );

    // Get brand terms if they exist
    $brands = [];
    //if (!empty($brand_ids)) {
    $brands = get_terms([
        'taxonomy' => $taxonomy,
        //'include'    => $brand_ids,
        'hide_empty' => false
    ]);
    //}

    ?>
        <div class="dokan-form-group" style="padding-top:13px;">
            <label for="brand_name"><?php _e('Brand Name', 'dokan'); ?></label>
            <select name="brand_name" id="brand_name" class="dokan-form-control">
                <option value=""><?php _e('Select a brand', 'dokan'); ?></option>
                <?php if (!empty($brands)): ?>
                        <?php foreach ($brands as $brand): ?>
                                <option value="<?php echo esc_attr($brand->term_id); ?>" <?php selected($brand_name, $brand->term_id); ?>>
                                    <?php echo esc_html($brand->name); ?>
                                </option>
                        <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>


        <div class="dokan-form-group">
            <label for="custom_brand"><?php _e('Or Enter Custom Brand Name', 'dokan'); ?></label>
            <p style="font-size: 12px; color: #ff9800; margin-top: 5px;">
                <strong>Note:</strong> If the brand name is not registered in Brags Brand Network, processing times may be
                longer.
                We strongly recommend ensuring the brand owner has registered their trademark in the Network and granted
                authorization for your business.
                You can then simply select the approved brand from the dropdown.
            </p>
            <input type="text" name="custom_brand" id="custom_brand" class="dokan-form-control"
                value="<?php echo esc_attr($custom_brand); ?>" placeholder="Enter custom brand name">
        </div>

        <div class="dokan-form-group">
            <label>
                <input type="checkbox" name="no_brand" id="no_brand" value="1" <?php checked($no_brand, '1'); ?>>
                <?php _e("This Product Doesn't Have a Brand Name", 'dokan'); ?>
            </label>
        </div>
        <script>
            jQuery(document).ready(function ($) {
                function toggleBrandFields() {
                    if ($('#no_brand').is(':checked')) {
                        $('#brand_name, #custom_brand').prop('disabled', true).val('');
                    } else {
                        $('#brand_name, #custom_brand').prop('disabled', false);
                    }
                }
                $('#no_brand').on('change', toggleBrandFields);
                toggleBrandFields(); // Run on page load
            });
        </script>
        <?php
}

add_action('dokan_product_edit_after_main', 'add_brand_name_field_to_product_page');
function add_brand_name_field_to_admin_product_page()
{
    global $post;

    // Get current values
    $brand_name = get_post_meta($post->ID, '_brand_name', true);
    $custom_brand = get_post_meta($post->ID, '_custom_brand_name', true);
    $no_brand = get_post_meta($post->ID, '_no_brand', true);
    $taxonomy = 'product_brand';

    // Admin gets all brands
    $brands = get_terms([
        'taxonomy' => $taxonomy,
        'hide_empty' => false
    ]);

    echo '<div class="options_group">';

    // Brand Select Field
    woocommerce_wp_select(array(
        'id' => 'brand_name',
        'label' => __('Brand Name', 'woocommerce'),
        'options' => array_reduce($brands, function ($carry, $brand) {
            $carry[$brand->term_id] = $brand->name;
            return $carry;
        }, ['' => __('Select a brand', 'woocommerce')]),
        'value' => $brand_name,
        'description' => __('Select a brand from the list', 'woocommerce'),
        'desc_tip' => true,
    ));

    // Custom Brand Field
    woocommerce_wp_text_input(array(
        'id' => 'custom_brand',
        'label' => __('Custom Brand Name', 'woocommerce'),
        'placeholder' => __('Enter custom brand name', 'woocommerce'),
        'value' => $custom_brand,
        'description' => __('Only use if brand not in dropdown', 'woocommerce'),
        'desc_tip' => true,
    ));

    // No Brand Checkbox
    woocommerce_wp_checkbox(array(
        'id' => 'no_brand',
        'label' => __('No Brand Name', 'woocommerce'),
        'description' => __('Check if this product has no brand', 'woocommerce'),
        'value' => $no_brand,
    ));

    echo '</div>';

    // Admin JS for toggling fields
    ?>
        <script>
            jQuery(document).ready(function ($) {
                function toggleAdminBrandFields() {
                    if ($('#no_brand').is(':checked')) {
                        $('#brand_name, #custom_brand').val('').closest('.form-field').hide();
                    } else {
                        $('#brand_name, #custom_brand').closest('.form-field').show();
                    }
                }
                $('#no_brand').on('change', toggleAdminBrandFields);
                toggleAdminBrandFields();
            });
        </script>
        <?php
}
add_action('woocommerce_product_options_general_product_data', 'add_brand_name_field_to_admin_product_page');


// Save the Brand Name field when the product is saved
function save_brand_name_field_on_product_save($post_id)
{
    // Make sure we're dealing with a product and not an autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return $post_id;
    }

    // Check if this is a product
    if ('product' !== get_post_type($post_id)) {
        return $post_id;
    }

    $brand_name = sanitize_text_field($_POST['brand_name']);
    $custom_brand = sanitize_text_field($_POST['custom_brand']);
    $no_brand = isset($_POST['no_brand']) ? '1' : '0';

    // Check if the 'brand_name' field is set in the $_POST array
    if (!empty($brand_name)) {
        // Sanitize the brand name as a string
        $brand_name = sanitize_text_field($_POST['brand_name']);
        // Check if the term exists

        if ($brand_name) {
            wp_set_post_terms($post_id, array($brand_name), 'product_brand');
        }



        // Save the sanitized brand name as post meta
        update_post_meta($post_id, '_brand_name', $brand_name);
        delete_post_meta($post_id, '_custom_brand_name');
        delete_post_meta($post_id, '_no_brand');
    } else if (!empty($custom_brand)) {
        update_post_meta($post_id, '_custom_brand_name', $custom_brand);
        delete_post_meta($post_id, '_brand_name');
        delete_post_meta($post_id, '_no_brand');
    } elseif ($no_brand === '1') {
        update_post_meta($post_id, '_no_brand', '1');
        delete_post_meta($post_id, '_brand_name');
        delete_post_meta($post_id, '_custom_brand_name');
    }

    return $post_id;
}
add_action('save_post', 'save_brand_name_field_on_product_save');




function create_custom_role()
{
    add_role(
        'brand_owner',
        'Brand Owner',
        array(
            'read' => true,
            'edit_posts' => false,
            'delete_posts' => false,
            'publish_posts' => false,
        )
    );
}
add_action('init', 'create_custom_role');


function add_user_role_to_body_class($classes)
{
    if (is_user_logged_in()) {
        $user = wp_get_current_user();

        if (!empty($user->roles)) {
            $role = $user->roles[0];
            $classes[] = 'role-' . $role;
        }
    }

    return $classes;
}
add_filter('body_class', 'add_user_role_to_body_class');


function custom_dokan_seller_dashboard_message()
{
    if (dokan_is_seller_dashboard()) {
        $user = wp_get_current_user();
        $seller_name = !empty($user->first_name) ? esc_html($user->first_name) : esc_html__('Seller', 'text-domain');

        echo '<div class="dokan-dashboard-message">
                <p><strong>Thanks for Selling on Brags, ' . $seller_name . '!</strong></p>
                <p>Any queries? Contact the Brags Seller Team!</p>
              </div>';
    }
}

add_action('woodmart_page_title_after_title', 'custom_dokan_seller_dashboard_message');

function custom_register_brags_brand_network()
{ ?>
        <p class="form-row form-group" id="brags-brand-network" style="display: none;">
            <label for="brags_brand_network">
                <?php _e('The Brands I intend to sell on Brags.co.uk are registered in Brags Brand Network. <a href="/brags-brand-network-account/" target="_blank" style="color: #000;font-weight: 600;letter-spacing: 0.2px;text-decoration: underline;">Learn more about Brags Brand Network here.</a>', 'dokan'); ?>
            </label>
            <span>
                <label for="brags_brand_network_yes" style="width: fit-content;">
                    <input type="checkbox" name="brags_brand_network[]" id="brags_brand_network_yes" value="Yes">
                    Yes
                </label>
            </span>
            <span>
                <label for="brags_brand_network_no" style="width: fit-content;">
                    <input type="checkbox" name="brags_brand_network[]" id="brags_brand_network_no" value="No">
                    No
                </label>
            </span>
            <span>
                <label for="brags_brand_network_unsure" style="width: fit-content;">
                    <input type="checkbox" name="brags_brand_network[]" id="brags_brand_network_unsure" value="Unsure">
                    Unsure
                </label>
            </span>
            <span>
                <label for="brags_brand_network_intend" style="width: fit-content;">
                    <input type="checkbox" name="brags_brand_network[]" id="brags_brand_network_intend"
                        value="Not yet, but I intend to register once I’m approved to sell on Brags.co.uk">
                    Not yet, but I intend to register once I’m approved to sell on Brags.co.uk
                </label>
            </span>
        </p>
<?php }

add_action('woocommerce_register_form', 'custom_register_brags_brand_network', 10);


function save_brags_brand_network_registration_field_1($customer_id)
{
    if (isset($_POST['brags_brand_network'])) {
        $brags_brand_network = sanitize_text_field(implode(', ', $_POST['brags_brand_network']));
        update_user_meta($customer_id, 'brags_brand_network', $brags_brand_network);
    }
}
add_action('woocommerce_created_customer', 'save_brags_brand_network_registration_field_1');

function display_brags_brand_network_in_user_profile($user)
{
    $brags_brand_network = get_user_meta($user->ID, 'brags_brand_network', true);

    ?>
        <h3><?php _e('Brags Brand Network Status', 'dokan'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="brags_brand_network"><?php _e('Brags Brand Network', 'dokan'); ?></label></th>
                <td>
                    <input type="text" name="brags_brand_network" id="brags_brand_network"
                        value="<?php echo esc_attr($brags_brand_network); ?>" class="regular-text" disabled>
                </td>
            </tr>
        </table>
<?php }
add_action('show_user_profile', 'display_brags_brand_network_in_user_profile');
add_action('edit_user_profile', 'display_brags_brand_network_in_user_profile');

function save_brags_brand_network_registration_field($customer_id)
{
    if (current_user_can('administrator')) {
        $brags_brand_network = '';
        update_user_meta($customer_id, 'brags_brand_network', $brags_brand_network);
    } elseif (isset($_POST['brags_brand_network'])) {
        $brags_brand_network = sanitize_text_field(implode(', ', $_POST['brags_brand_network']));
        update_user_meta($customer_id, 'brags_brand_network', $brags_brand_network);
    }
}
add_action('woocommerce_created_customer', 'save_brags_brand_network_registration_field');


// ---------------------------------------- All seller dashboard left side menu body class add  ----------------------------------------

function custom_dokan_body_classes($classes)
{
    if (is_page(17575) || is_page(17491)) {
        $classes[] = 'dokan-dashboard';
        $classes[] = 'dokan-theme-your-textdomain';
    }
    return $classes;
}
add_filter('body_class', 'custom_dokan_body_classes');

function custom_dokan_dashboard_script()
{
    ?>
        <script>
            jQuery(document).ready(function () {
                function handleFileUpload(input, maxFiles) {
                    jQuery(input).on('change', function (e) {
                        let files = e.target.files;
                        let allowedTypes = [
                            'image/jpeg', 'image/png', 'application/pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                        ];

                        if (files.length > maxFiles) {
                            alert('You can upload up to ' + maxFiles + ' files only.');
                            jQuery(this).val('');
                            return;
                        }

                        let invalidFiles = [];
                        let validFiles = [];

                        for (let i = 0; i < files.length; i++) {
                            if (!allowedTypes.includes(files[i].type)) {
                                invalidFiles.push(files[i].name);
                            } else {
                                validFiles.push(files[i].name);
                            }
                        }

                        if (invalidFiles.length > 0) {
                            alert('Invalid file(s): ' + invalidFiles.join(', ') + '\nAllowed formats: PDF, Word (DOC, DOCX), JPEG, PNG.');
                            jQuery(this).val('');
                            return;
                        }

                        //alert('Files selected: ' + validFiles.join(', '));
                    });
                }

                handleFileUpload('#report_product_supporting_files', 10);
                handleFileUpload('#report_seller_sellering_files', 10);
                handleFileUpload('#_supporting_files', 10);

            });
        </script>

        <?php
        // Add sidebar menu active state handling
        if (is_page(17575)) {
            ?>
                <script type="text/javascript">
                    jQuery(document).ready(function ($) {
                        $('.dokan-dashboard .dokan-dash-sidebar #dokan-navigation ul.dokan-dashboard-menu .active.dashboard')
                            .removeClass('active');
                        $('.dokan-dashboard .dokan-dash-sidebar #dokan-navigation ul.dokan-dashboard-menu .report-a-customer')
                            .addClass('report-a-customer active');
                    });
                </script>
                <?php
        }
        if (is_page(17491)) {
            ?>
                <script type="text/javascript">
                    jQuery(document).ready(function ($) {
                        $('.dokan-dashboard .dokan-dash-sidebar #dokan-navigation ul.dokan-dashboard-menu .active.dashboard')
                            .removeClass('active');
                        $('.dokan-dashboard .dokan-dash-sidebar #dokan-navigation ul.dokan-dashboard-menu .seller-score')
                            .addClass('seller-score active');
                    });
                </script>
                <?php
        }
}
add_action('wp_footer', 'custom_dokan_dashboard_script');


function report_customer_dokan_account_menu_items($items)
{
    if (current_user_can('seller')) {
        $items['report-a-customer'] = array(
            'title' => __('Report a Customer', 'your-textdomain'),
            'url' => site_url('/report-a-customer/'),
            'icon' => '<i class="fas fa-star"></i>'
        );
    }
    return $items;
}
add_filter('dokan_get_dashboard_nav', 'report_customer_dokan_account_menu_items');


function load_jquery_block_ui()
{
    wp_enqueue_script('jquery-blockui', includes_url('js/jquery/jquery.blockUI.min.js'), array('jquery'), false, true);
}
add_action('wp_enqueue_scripts', 'load_jquery_block_ui');




function remove_browser_platform_classes_js()
{

    ?>
        <script type="text/javascript">
            document.addEventListener('DOMContentLoaded', function () {
                var html = document.documentElement;
                html.classList.remove('browser-Safari');
                html.classList.remove('platform-iOS');
            });

            jQuery(document).ready(function () {
                jQuery('input[name="s"]').mouseover(function () {
                    jQuery(this).attr('type', 'search');
                    jQuery(this).attr('autocomplete', 'on');
                });
            });

            jQuery(document).ready(function ($) {
                $('a.woocommerce-LostPassword.lost_password').attr('href', '/my-account/lost-password/');
            });
        </script>
        <?php
}
add_action('wp_footer', 'remove_browser_platform_classes_js');



add_filter('login_errors', 'custom_lost_password_link_error', 99);
function custom_lost_password_link_error($error)
{
    $old_url = 'https://brags.co.uk/brags-brand-network-account/lost-password/';
    $new_url = home_url('my-account/lost-password/'); // Replace with your custom URL

    // Replace only if it's the Lost Password link
    $error = str_replace($old_url, $new_url, $error);

    return $error;
}


add_filter('login_errors', 'custom_lost_password_link_tag', 100);
function custom_lost_password_link_tag($error)
{
    $pattern = '/<a href=".*?">Lost Your Password\?<\/a>/';
    $custom_link = '<a href="' . esc_url(home_url('my-account/lost-password/')) . '">Lost Your Password?</a>';
    return preg_replace($pattern, $custom_link, $error);
}


function order_tracking_info_validation()
{
    ?>
        <script>
            jQuery(document).ready(function ($) {

                $("#add-tracking-status-details").on("click", function () {

                    $(this).removeAttr("disabled");

                    var shipping_status_provider = $("#shipping_status_provider").val();
                    var shipped_status_date = $("#shipped_status_date").val();
                    let tracking = $('#tracking_status_number').val();
                    let notify = $('#shipped_status_is_notify').is(':checked'); // checkbox

                    // if(shipping_status_provider==""){
                    //     $("#shipping_status_provider").after("<span class='error'>Shipping  provider is required.</span>");
                    //     $(".shipping-tracking-comments").css("padding-top", "24px");
                    //     return false;
                    // }

                    // if(shipped_status_date==""){
                    //     $("#shipped_status_date").after("<span class='error'>Shipping  provider is required.</span>");
                    //     $(".shipping-tracking-comments").css("padding-top", "24px");
                    //     return false;
                    // }

                    let valid = true;



                    // Clear previous errors
                    $(".error").remove();

                    if (!shipping_status_provider) {
                        $('#shipping_status_provider').after('<span class="error" style="color:red;">Shipping Provider is required.</span>');
                        valid = false;
                    }

                    if (!shipped_status_date) {
                        $('#shipped_status_date').after('<span class="error" style="color:red;">Date Shipped is required</span>');
                        valid = false;
                    }

                    if (!tracking) {
                        $('#tracking_status_number').after('<span class="error" style="color:red;">Tracking Number is required</span>');
                        valid = false;
                    }

                    if (!notify) {
                        $('#shipped_status_is_notify').parent().after('<span class="error" style="color:red;">Please check this checkbox to notify the customer.</span>');
                        valid = false;
                    }

                    if (!valid) {
                        return false;
                    }

                    return true;

                });

            });
        </script>
        <?php
}
add_action('wp_footer', 'order_tracking_info_validation');



// regieter OTP code start
function bragsy_add_otp_modal_markup()
{
    if (is_account_page()) {
        ?>
                <div id="otp-popup-overlay" style="display:none;">
                    <div id="otp-popup">
                        <h3>Verify Phone Number</h3>
                        <p id="otp-info-text"></p>
                        <input type="text" id="otp-input" placeholder="Enter OTP" />
                        <button id="verify-otp-btn">Verify OTP</button>
                        <div id="otp-message"></div>
                        <!-- <p>Didn’t get the code? <a href="#" id="resend-otp-link">Resend OTP</a></p> -->
                    </div>
                </div>
                <style>
                    #otp-popup-overlay {
                        position: fixed;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        background: rgba(0, 0, 0, 0.6);
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        z-index: 9999;
                    }

                    #otp-popup {
                        background: #fff;
                        padding: 25px;
                        border-radius: 8px;
                        text-align: center;
                        width: 300px;
                    }

                    #otp-popup input {
                        width: 90%;
                        padding: 8px;
                    }

                    #otp-popup button {
                        margin-top: 10px;
                        padding: 10px 20px;
                        background: #0073aa;
                        color: #fff;
                        border: none;
                    }
                </style>
                <?php
    }
}
add_action('wp_footer', 'bragsy_add_otp_modal_markup');
function bragsy_enqueue_otp_script()
{
    if (is_account_page()) {
        wp_enqueue_script('bragsy-otp-handler', get_stylesheet_directory_uri() . '/assets/js/otp-handler.js', ['jquery'], null, true);
        wp_localize_script('bragsy-otp-handler', 'bragsy_otp_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bragsy_otp_nonce'),
        ]);
    }
}
add_action('wp_enqueue_scripts', 'bragsy_enqueue_otp_script');


//Send staff OTP (Twilio)
add_action('wp_ajax_bragsy_send_seller_staff_otp', 'bragsy_send_seller_staff_otp');
add_action('wp_ajax_nopriv_bragsy_send_seller_staff_otp', 'bragsy_send_seller_staff_otp');

function bragsy_send_seller_staff_otp()
{
    check_ajax_referer('dokan_staff_nonce', 'nonce');
    session_start();

    $phone = sanitize_text_field($_POST['phone']);
    $country_code = sanitize_text_field($_POST['country_code']);
    $otp = rand(100000, 999999);
    $_SESSION['bragsy_staff_otp'] = $otp;

    try {
        $twilio = new Twilio\Rest\Client(TWILIO_SID, TWILIO_AUTH_TOKEN);
        $twilio->messages->create(
            $country_code . $phone,
            [
                'from' => TWILIO_PHONE_NUMBER,
                'body' => "$otp is your Brags OTP code. Do not share it with anyone."
            ]
        );
        wp_send_json_success();
    } catch (Exception $e) {
        wp_send_json_error('Twilio error: ' . $e->getMessage());
    }
}

//Verify staff OTP
add_action('wp_ajax_bragsy_verify_seller_staff_otp', 'bragsy_verify_seller_staff_otp');
add_action('wp_ajax_nopriv_bragsy_verify_seller_staff_otp', 'bragsy_verify_seller_staff_otp');

function bragsy_verify_seller_staff_otp()
{
    check_ajax_referer('dokan_staff_nonce', 'nonce');
    session_start();

    $input = sanitize_text_field($_POST['otp']);
    if (!empty($_SESSION['bragsy_staff_otp']) && $input == $_SESSION['bragsy_staff_otp']) {
        $_SESSION['bragsy_staff_verified'] = true;
        wp_send_json_success();
    } else {
        wp_send_json_error('Invalid OTP. Please try again.');
    }
}


//Send OTP (Twilio)
add_action('wp_ajax_bragsy_send_seller_otp', 'bragsy_send_seller_otp');
add_action('wp_ajax_nopriv_bragsy_send_seller_otp', 'bragsy_send_seller_otp');

function bragsy_send_seller_otp()
{
    check_ajax_referer('bragsy_otp_nonce', 'nonce');
    session_start();

    $phone = sanitize_text_field($_POST['phone']);
    $country_code = sanitize_text_field($_POST['country_code']);
    $otp = rand(100000, 999999);
    $_SESSION['bragsy_otp'] = $otp;

    try {
        $twilio = new Twilio\Rest\Client(TWILIO_SID, TWILIO_AUTH_TOKEN);
        $twilio->messages->create(
            $country_code . $phone,
            [
                'from' => TWILIO_PHONE_NUMBER,
                'body' => "$otp is your Brags OTP code. Do not share it with anyone."
            ]
        );
        wp_send_json_success();
    } catch (Exception $e) {
        wp_send_json_error('Twilio error: ' . $e->getMessage());
    }
}

//Verify OTP
add_action('wp_ajax_bragsy_verify_seller_otp', 'bragsy_verify_seller_otp');
add_action('wp_ajax_nopriv_bragsy_verify_seller_otp', 'bragsy_verify_seller_otp');

function bragsy_verify_seller_otp()
{
    check_ajax_referer('bragsy_otp_nonce', 'nonce');
    session_start();

    $input = sanitize_text_field($_POST['otp']);
    if (!empty($_SESSION['bragsy_otp']) && $input == $_SESSION['bragsy_otp']) {
        $_SESSION['bragsy_verified'] = true;
        wp_send_json_success();
    } else {
        wp_send_json_error('Invalid OTP. Please try again.');
    }
}











add_action('dokan_new_seller_created', 'send_otp_to_new_seller', 10, 2);

function send_otp_to_new_seller($user_id, $dokan_data)
{


    session_start();
    $country_code = 'GB';
    if (isset($_POST['country_code'])) {
        $country_code = sanitize_text_field($_POST['country_code']);
        add_user_meta($user_id, 'country_code', $country_code);
    }

    if ($_SESSION['bragsy_verified']) {
        update_user_meta($user_id, 'is_otp_verified', true);
    } else {
        update_user_meta($user_id, 'is_otp_verified', false);
    }

    //$country_code = get_user_meta($user_id, 'country_code', true);

    $profile = get_user_meta($user_id, 'dokan_profile_settings', true);

    if ($country_code != '' && is_array($profile)) {
        $countries = get_phone_to_country_mapping();
        $country_name = isset($countries[$country_code]) ? $countries[$country_code] : $country_code;



        if (isset($profile['address']) && is_array($profile['address'])) {
            $profile['address']['country'] = $country_name;
        } else {
            // In case address is missing, create it
            $profile['address'] = [
                'country' => $country_name,
            ];
        }

        update_user_meta($user_id, 'dokan_profile_settings', $profile);
    }

}



// Show OTP popup after seller registers

function woodmart_child_enqueue_script()
{
    // Debug line: uncomment only if you want to test output
    // echo get_stylesheet_directory_uri(); die;

    wp_enqueue_script(
        'country_code_script',
        get_stylesheet_directory_uri() . '/assets/js/countrycode.min.js',
        array(), // Dependencies (e.g., array('jquery') if needed)
        null,    // Version (or use a string like '1.0.0')
        true     // Load in footer (true) or header (false)
    );
}
add_action('wp_enqueue_scripts', 'woodmart_child_enqueue_script');



// add_action('wp_ajax_verify_seller_otp', 'handle_otp_verification_ajax');
// add_action('wp_ajax_nopriv_verify_seller_otp', 'handle_otp_verification_ajax');
function handle_otp_verification_ajax()
{
    session_start();
    check_ajax_referer('verify_otp_nonce', 'nonce');

    $user_id = get_current_user_id();
    $entered_otp = sanitize_text_field($_POST['otp']);
    // $saved_otp = get_user_meta($user_id, 'seller_otp', true);
    $saved_otp = $_SESSION['seller_otp'];

    if ($entered_otp == $saved_otp) {
        // update_user_meta($user_id, 'seller_otp_verified', 'yes');
        // update_user_meta($user_id, 'dokan_enable_selling', 'yes');
        // update_user_meta($user_id, 'dokan_verification_status', 'approved');
        unset($_SESSION['seller_otp']);
        delete_user_meta($user_id, 'seller_otp');
        update_user_meta($user_id, 'is_otp_verified', true);
        wp_send_json_success();
    } else {
        wp_send_json_error('❌ Invalid OTP. Please try again.');
    }
}

// Add country code field before phone number
add_action('wp_footer', 'insert_country_code_before_shop_phone');
function insert_country_code_before_shop_phone()
{
    if (is_account_page()) { ?>

                <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.13/css/intlTelInput.css">
                <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.13/js/intlTelInput-jquery.min.js"></script>

                <script type="text/javascript">
                    jQuery(document).ready(function ($) {
                        // Check if the phone field exists
                        if ($('#shop-phone').length) {
                            var countryCodeField = `
                    <p class="form-row form-group form-row-wide">
                        <label for="country_code">Country Code <span class="required">*</span></label>
                        <select name="country_code" id="country_code" class="input-select form-control" required>

                        </select>

                    </p>
                `;
                            // Insert just before the phone field's container
                            $('#shop-phone').closest('p.form-row').before(countryCodeField);
                        }

                        // $("#country_code").intlTelInput({
                        //     initialCountry: "in",
                        //     separateDialCode: true,
                        //     utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/11.0.4/js/utils.js"
                        // });

                        // input.addEventListener("countrychange", function() {
                        //     const dialCode = iti.getSelectedCountryData().dialCode;
                        //     $('#countrycode').val('+' + dialCode);
                        // });
                    });
                </script>
        <?php }
}

function enqueue_otp_script()
{
    // Only enqueue on the page where the OTP form is shown (e.g., WooCommerce, Dokan account page)
    if (is_page('your-page-slug') || is_account_page()) { // You can adjust this condition to fit your site structure
        wp_enqueue_script('otp-resend-script', get_stylesheet_directory_uri() . '/assets/js/otp-resend.js');

        // Localize the script to pass dynamic data (like AJAX URL and nonce)
        wp_localize_script('otp-resend-script', 'my_ajax_object', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('verify_otp_nonce'),
        ));
    }
}
add_action('wp_enqueue_scripts', 'enqueue_otp_script');


add_action('wp_ajax_resend_seller_otp', 'handle_resend_otp');
add_action('wp_ajax_nopriv_resend_seller_otp', 'handle_resend_otp');

function handle_resend_otp()
{
    session_start();
    check_ajax_referer('verify_otp_nonce', 'nonce');

    $user_id = $_POST['user_id'];
    // $phoneno = $_POST['phoneno'];
    // $countrycode = $_POST['countrycode'];



    $store_info = get_user_meta($user_id, 'dokan_profile_settings', true);
    $phone = isset($store_info['phone']) ? $store_info['phone'] : '';
    $country_code = get_user_meta($user_id, 'country_code', true);

    if (empty($phone) || empty($country_code)) {
        wp_send_json_error('Missing phone or country code.');
    }

    $sid = defined('TWILIO_SID') ? TWILIO_SID : '';
    $token = defined('TWILIO_AUTH_TOKEN') ? TWILIO_AUTH_TOKEN : '';
    $twilio_number = defined('TWILIO_PHONE_NUMBER') ? TWILIO_PHONE_NUMBER : '';

    try {
        $client = new \Twilio\Rest\Client($sid, $token);
        $otp = rand(100000, 999999);

        update_user_meta($user_id, 'seller_otp', $otp);
        $_SESSION['seller_otp'] = $otp;

        $client->messages->create(
            $country_code . $phone,
            [
                'from' => $twilio_number,
                'body' => "Your new OTP is: $otp"
            ]
        );

        wp_send_json_success();
    } catch (Exception $e) {
        error_log('Twilio resend error: ' . $e->getMessage());
        wp_send_json_error('Error sending OTP. Please try again.');
    }
}



add_filter('gettext', 'custom_woo_account_message', 20, 3);

function custom_woo_account_message($translated_text, $text, $domain)
{
    if ('woocommerce' === $domain && $text === 'Your account was created successfully. Your login details have been sent to your email address.') {
        $translated_text = 'Your Brags Account has been created successfully.  Please check your Inbox to verify your email address.';
    }
    return $translated_text;
}


function brags_redirect_www_to_non_www() {
    if (strpos($_SERVER['HTTP_HOST'], 'www.') === 0) {
        $redirect_url = 'https://' . str_replace('www.', '', $_SERVER['HTTP_HOST']) . $_SERVER['REQUEST_URI'];

        wp_redirect($redirect_url, 301);
        exit;
    }
}
add_action('template_redirect', 'brags_redirect_www_to_non_www');