<?php

// brand upoad from font form
function custom_registration_form() {
    ?>
    <div class="ur-field-item field-file">
        <div class="form-row validate-required">

            <!-- Image Preview Container -->
            <div id="brand_logo_preview_container" style="display: none; text-align: center; margin-bottom: 10px; position: relative;">
                <img id="brand_logo_preview" src="#" alt="Brand Logo Preview" style="max-width: 120px; border-radius: 10px; box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.2);"/>
                <button type="button" id="remove_logo" style="position: absolute; top: -8px; right: -8px; background: red; color: white; border: none; border-radius: 50%; width: 24px; height: 24px; font-size: 14px; cursor: pointer;">×</button>
            </div>

            <label for="brand_ownership_logo_url" class="ur-label"><?php _e('Logo Upload'); ?>
                <abbr class="required" title="required">*</abbr>
            </label>
            <span class="input-wrapper">
                <input type="file" class="input-text input-file ur-frontend-field" name="brand_ownership_logo_file" id="brand_ownership_logo_file" accept=".jpg, .jpeg, .png" required />
                <input type="hidden" name="brand_ownership_logo_url" id="brand_ownership_logo_url" value="" /> <!-- Store uploaded file URL -->
            </span>
        </div>
    </div>

    <script>
        jQuery(document).ready(function($) {
            $('#brand_ownership_logo_file').on('change', function() {
                var form_id = $("input[name='ur-user-form-id']").val();

                var file = this.files[0];
                if (file) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        $('#brand_logo_preview').attr('src', e.target.result);
                        $('#brand_logo_preview_container').fadeIn();
                    };
                    reader.readAsDataURL(file);

                    // Upload File via AJAX
                    var formData = new FormData();
                    formData.append('file', file);
                    formData.append('form_id',form_id);
                    formData.append('action', 'ajax_upload_brand_logo');

                    $.ajax({
                        url: '<?php echo admin_url("admin-ajax.php"); ?>',
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                $('#brand_ownership_logo_url').val(response.data.file_url); // Store file URL in hidden field
                            } else {
                                alert('Upload failed: ' + response.data.message);
                            }
                        }
                    });
                } else {
                    $('#brand_logo_preview_container').fadeOut();
                }
            });

            // Remove Button Click Event
            $('#remove_logo').on('click', function() {
                $('#brand_logo_preview').attr('src', '#');
                $('#brand_logo_preview_container').fadeOut();
                $('#brand_ownership_logo_file').val(''); // Clear input
                $('#brand_ownership_logo_url').val(''); // Clear hidden field
            });
        });
    </script>
    <?php
}
add_action('user_registration_after_field_row', 'custom_registration_form');

function ajax_upload_brand_logo() {
    if (!empty($_FILES['file']['name'])) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');

        // Check file type
        $allowed_types = ['image/jpeg', 'image/png'];
        if (!in_array($_FILES['file']['type'], $allowed_types)) {
            wp_send_json_error(['message' => 'Invalid file type. Only JPG and PNG allowed.']);
        }

        // Upload File
        $uploaded_file = wp_handle_upload($_FILES['file'], ['test_form' => false]);

        if (isset($uploaded_file['url'])) {

            if (!session_id()) {
                session_start();
            }
            $_SESSION['brand_ownership_logo_url'] = esc_url($uploaded_file['url']);
            wp_send_json_success(['file_url' => esc_url($uploaded_file['url'])]);
        } else {
            wp_send_json_error(['message' => 'File upload failed.']);
        }
    } else {
        wp_send_json_error(['message' => 'No file received.']);
    }
}
add_action('wp_ajax_ajax_upload_brand_logo', 'ajax_upload_brand_logo');
add_action('wp_ajax_nopriv_ajax_upload_brand_logo', 'ajax_upload_brand_logo');



function save_brand_logo_after_registration($valid_form_data, $form_id, $user_id) {


    if (!session_id()) {
        session_start();
    }

    if (!empty($_SESSION['brand_ownership_logo_url'])) {
        $logo_url = $_SESSION['brand_ownership_logo_url'];

        update_user_meta($user_id, 'brand_ownership_logo_url', esc_url($logo_url));

        // Clear the session after saving
        unset($_SESSION['brand_ownership_logo_url']);
    }

    update_user_meta( absint( $user_id ), 'pw_user_status', 'pending' );
}
add_action('user_registration_after_register_user_action', 'save_brand_logo_after_registration', 10, 3);


// action on change status.
add_action('updated_user_meta', function($meta_id, $user_id, $meta_key, $_meta_value) {
    if ($meta_key === 'pw_user_status' && $_meta_value=='approved') {
        $taxonomy='product_brand';
        $brand_name = get_user_meta($user_id,'brand_name',true);
        if (!term_exists($brand_name, $taxonomy)) {
            wp_insert_term($brand_name, $taxonomy);
        }

    }
}, 10, 4);

function upload_brand_logo_before_registration($form_data, $form_id) {
    if (isset($_FILES['brand_ownership_logo_url']) && !empty($_FILES['brand_ownership_logo_url']['name'])) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');

        if (!session_id()) {
            session_start();
        }

        $allowed_types = ['image/jpeg', 'image/png'];
        if (!in_array($_FILES['brand_ownership_logo_url']['type'], $allowed_types)) {
            return $form_data; // Stop if file type is not allowed
        }

        $uploaded_file = wp_handle_upload($_FILES['brand_ownership_logo_url'], ['test_form' => false]);

        if (isset($uploaded_file['url'])) {
            $form_data['brand_ownership_logo_url'] = (object) ['value' => esc_url($uploaded_file['url'])];
            $_SESSION['brand_ownership_logo_url'] = esc_url($uploaded_file['url']);
        }
    }

    return $form_data;
}
add_filter('user_registration_reorganize_form_data', 'upload_brand_logo_before_registration', 10, 2);



function custom_upload_size_limit( $file ) {
    $max_size = 2 * 1024 * 1024;
    if ( $file['size'] > $max_size ) {
        $file['error'] = 'File size exceeds the 2MB limit.';
    }
    return $file;
}
add_filter( 'wp_handle_upload_prefilter', 'custom_upload_size_limit' );

// Display brand logo on profile detail


function ur_add_custom_multistep_fields($form_data_array, $form_id) {
    ?>

    <div id="ur-multi-step-form">
        <!-- Step 1: Brand Information -->
        <fieldset class="ur-step">
            <h2>Step 1: Brand Information</h2>

            <label>Brand Name <abbr class="required" title="required">*</abbr></label>
            <input type="text" name="brand_name" required>

            <label>Upload Brand Logo <abbr class="required" title="required">*</abbr></label>
            <input type="file" name="brand_logo" accept=".jpg,.jpeg,.png,.gif" required>

            <label>Trademark Office <abbr class="required" title="required">*</abbr></label>
            <select name="trademark_office" required>
                <option value="">Select Trademark Office</option>
                <option value="IPO (UK)">IPO (UK)</option>
                <option value="EUIPO">EUIPO</option>
                <option value="WIPO">WIPO</option>
            </select>

            <label>Trademark Registration Number <abbr class="required" title="required">*</abbr></label>
            <input type="text" name="trademark_number" required>

            <label>Brand Description <abbr class="required" title="required">*</abbr></label>
            <textarea name="brand_description" required></textarea>

            <button type="button" class="ur-next-step">
                <span class="btn-text">Next</span>
                <span class="spinner" style="display: none;">
                    <i class="fa fa-spinner fa-spin"></i>
                </span>
            </button>
        </fieldset>

         <!-- Step 2: Business Information -->

        <fieldset class="ur-step" style="display: none;">
            <h2>Step 2: Business Information</h2>

            <label>Business Name <abbr class="required" title="required">*</abbr></label>
            <input type="text" name="business_name" required>

            <label>Business Address <abbr class="required" title="required">*</abbr></label>
            <input type="text" name="business_address" required>

            <label>Business Contact Email <abbr class="required" title="required">*</abbr></label>
            <input type="email" name="business_email" required>

            <label>Primary Contact Name <abbr class="required" title="required">*</abbr></label>
            <input type="text" name="primary_contact" required>


            <div>
            <label >Phone Number:</label>
            </div>

            <div style="display: flex;">
                <input type="text" id="country" name="country" required>
                <input type="text" id="phone" name="phone_number" placeholder="Enter phone number" required>
                <span id="phone-error" style="color: red; display: none;">Invalid phone number</span>
            </div>


            <label>Website URL (optional)</label>
            <input type="url" name="website_url">

            <label>Password<abbr class="required" title="required">*</abbr></label>
            <input type="password" name="user_pass" required>

            <label>Confirm Password <abbr class="required" title="required">*</abbr></label>
            <input type="password" name="user_confirm_password" required>

            <button type="button" class="ur-prev-step">Previous</button>
            <button type="button" class="ur-next-step">
                <span class="btn-text">Next</span>
                <span class="spinner" style="display: none;">
                    <i class="fa fa-spinner fa-spin"></i>
                </span>
            </button>
        </fieldset>

             <!-- Step 3: Manufacturing & Distribution Information -->
        <fieldset class="ur-step" style="display: none;">
            <h2>Step 3: Manufacturing & Distribution</h2>

            <label>Manufacturing Location(s) <abbr class="required" title="required">*</abbr></label>
            <input type="text" name="manufacturing_locations" required>

            <label>Distribution Channels <abbr class="required" title="required">*</abbr></label>
            <input type="text" name="distribution_channels" required>

            <label>Authorized Resellers (optional)</label>
            <textarea name="authorized_resellers"></textarea>

            <label>Product Supply Chain (optional)</label>
            <textarea name="product_supply_chain"></textarea>

            <button type="button" class="ur-prev-step">Previous</button>
            <button type="button" class="ur-next-step">
                <span class="btn-text">Next</span>
                <span class="spinner" style="display: none;">
                    <i class="fa fa-spinner fa-spin"></i>
                </span>
            </button>
        </fieldset>

        <!-- Step 4: Product Information -->
        <fieldset class="ur-step" style="display: none;">
            <h2>Step 4: Product Information</h2>

            <label>Product Categories <abbr class="required" title="required">*</abbr></label>
            <input type="text" name="product_categories" required>


            <label>1 – 5 Product IDs e.g. EAN, GTIN</label>
            <input type="text" name="product_ids_1" placeholder="Product ID 1">
            <input type="text" name="product_ids_2" placeholder="Product ID 2">
            <input type="text" name="product_ids_3" placeholder="Product ID 3">
            <input type="text" name="product_ids_4" placeholder="Product ID 4">
            <input type="text" name="product_ids_5" placeholder="Product ID 5">

            <div>
                <label>Upload Real Life Product Photos of one of your products clearly showing the Brand Name and if possible, Product ID (barcode) mentioned above <abbr class="required" title="required">*</abbr></label>
                <label>Product Image 1 (for Product ID 1)</label>
                <input type="file" name="product_images_1" accept=".jpg,.jpeg,.png,.gif">

                <label>Product Image 2 (for Product ID 2)</label>
                <input type="file" name="product_images_2" accept=".jpg,.jpeg,.png,.gif">

                <label>Product Image 3 (for Product ID 3)</label>
                <input type="file" name="product_images_3" accept=".jpg,.jpeg,.png,.gif">

                <label>Product Image 4 (for Product ID 4)</label>
                <input type="file" name="product_images_4" accept=".jpg,.jpeg,.png,.gif">

                <label>Product Image 5 (for Product ID 5)</label>
                <input type="file" name="product_images_5" accept=".jpg,.jpeg,.png,.gif">

                <span id="file-error" style="color: red; display: none;">At least one product image is required.</span>
            </div>
            <br>
            <button type="button" class="ur-prev-step">Previous</button>
            <button type="button" class="ur-next-step">
                <span class="btn-text">Next</span>
                <span class="spinner" style="display: none;">
                    <i class="fa fa-spinner fa-spin"></i>
                </span>
            </button>
        </fieldset>

        <!-- Step 5: Brand Protection Preferences -->
        <fieldset class="ur-step" style="display: none;">
            <h2>Step 5: Brand Protection Preferences</h2>

            <label>Would you like to sell on Brags under your own Brand Name?</label>
            <select name="sell_on_brags" id="sell_on_brags" required>
                <option value="Yes">Yes</option>
                <option value="No">No</option>
            </select>

            <label>Your Brags Registered Seller Email Address <span id="email-required" class="required-asterisk required">*</span></label>
            <input type="email" name="brags_seller_email" id="brags_seller_email" required>

            <label>Your Brags Store URL <span id="url-required"  class="required-asterisk required">*</span></label>
            <input type="url" name="brags_store_url" id="brags_store_url" required>

            <label>Would you like to approve resellers before they sell your brand?</label>
            <select name="approve_resellers" required>
                <option value="Yes">Yes</option>
                <option value="No">No</option>
            </select>

            <label>Additional Documentation (optional)</label>
            <input type="file" name="additional_documents" accept=".pdf,.doc,.docx" style="width: 100%;">

            <button type="button" class="ur-prev-step">
                Previous
            </button>
            <button type="button" class="ur-next-step">
                <span class="btn-text">Next</span>
                <span class="spinner" style="display: none;">
                    <i class="fa fa-spinner fa-spin"></i>
                </span>
            </button>
        </fieldset>
        <!-- Step 6: Review and Submit -->
        <fieldset class="ur-step" style="display: none;">
            <h2>Step 6: Review and Submit</h2>
            <p>Please review all the details entered before submitting.</p>

            <div id="review-section">
                <h3>Brand Information</h3>
                <p><strong>Brand Name:</strong> <span id="review_brand_name"></span></p>
                <p><strong>Trademark Office:</strong> <span id="review_trademark_office"></span></p>
                <p><strong>Trademark Registration Number:</strong> <span id="review_trademark_number"></span></p>
                <p><strong>Brand Description:</strong> <span id="review_brand_description"></span></p>
                <p><strong>Brand Logo:</strong> <img id="review_brand_logo" src="" alt="Brand Logo" style="max-width:150px; display:none;"></p>

                <h3>Business Information</h3>
                <p><strong>Business Name:</strong> <span id="review_business_name"></span></p>
                <p><strong>Business Address:</strong> <span id="review_business_address"></span></p>
                <p><strong>Business Contact Email:</strong> <span id="review_business_email"></span></p>
                <p><strong>Primary Contact:</strong> <span id="review_primary_contact"></span></p>
                <p><strong>Phone Number:</strong> <span id="review_phone_number"></span></p>
                <p><strong>Website URL:</strong> <span id="review_website_url"></span></p>

                <h3>Manufacturing & Distribution</h3>
                <p><strong>Manufacturing Locations:</strong> <span id="review_manufacturing_locations"></span></p>
                <p><strong>Distribution Channels:</strong> <span id="review_distribution_channels"></span></p>


                <h3>Product Information</h3>
                <p><strong>Product Categories:</strong> <span id="review_product_categories">N/A</span></p>
                <p><strong>Product IDs:</strong> <span id="review_product_ids">N/A</span></p>
                <p><strong>Product Images:</strong></p>
                <div id="review_product_images" style="display: flex; flex-wrap: wrap;"></div>


                <h3>Brand Protection Preferences</h3>
                <p><strong>Sell on Brags:</strong> <span id="review_sell_on_brags"></span></p>
                <p><strong>Brags Seller Email:</strong> <span id="review_brags_seller_email"></span></p>
                <p><strong>Brags Store URL:</strong> <span id="review_brags_store_url"></span></p>
                <p><strong>Approve Resellers:</strong> <span id="review_approve_resellers"></span></p>

                <h3>Additional Documents</h3>
                <p><strong>Uploaded Document:</strong> <a id="review_additional_documents" href="#" target="_blank" style="display:none;">View Document</a></p>
            </div>

            <button type="button" class="ur-prev-step">Previous</button>
            <button type="submit" id="ur-submit-btn">
                <span class="btn-text">Submit Application</span>
                <span class="spinner" style="display: none;">
                    <i class="fa fa-spinner fa-spin"></i>
                </span>
            </button>
        </fieldset>

    </div>

    <script>
        jQuery(document).ready(function ($) {
            // Initialize country dropdown
            $("#country").countrySelect({
            preferredCountries: ["us", "gb", "in", "de", "au"],
            });

            // Form validation
            $("#phone-form").on("submit", function (e) {
            e.preventDefault();

            let countryData = $("#country").countrySelect("getSelectedCountryData");
            let countryCode = countryData.iso2.toUpperCase();
            let phoneNumber = $("#phone").val();

            let parsedNumber = libphonenumber.parsePhoneNumber(phoneNumber, countryCode);

            if (!parsedNumber || !parsedNumber.isValid()) {
                $("#phone-error").text("Invalid phone number").show();
            } else {
                $("#phone-error").hide();
                alert("Phone number is valid!");
            }
            });
        });
    </script>

    <script>

        document.getElementById("sell_on_brags").addEventListener("change", function () {
                let emailField = document.getElementById("brags_seller_email");
                let urlField = document.getElementById("brags_store_url");
                let emailAsterisk = document.getElementById("email-required");
                let urlAsterisk = document.getElementById("url-required");

                if (this.value === "No") {
                    emailField.removeAttribute("required");
                    urlField.removeAttribute("required");
                    emailAsterisk.style.display = "none";
                    urlAsterisk.style.display = "none";
                } else {
                    emailField.setAttribute("required", "required");
                    urlField.setAttribute("required", "required");
                    emailAsterisk.style.display = "inline";
                    urlAsterisk.style.display = "inline";
                }
            });


        let currentStep = 0;

        document.addEventListener("DOMContentLoaded", function() {
            let steps = document.querySelectorAll(".ur-step");
            let nextButtons = document.querySelectorAll(".ur-next-step");
            let prevButtons = document.querySelectorAll(".ur-prev-step");


            let form = document.getElementById("ur-multi-step-form-fa");
            let submitButton = document.getElementById("ur-submit-btn");
            let btnText = submitButton.querySelector(".btn-text");
            let spinner = submitButton.querySelector(".spinner");

            submitButton.addEventListener("click", function (e) {
                e.preventDefault(); // Prevent default form submission
                //user_form_submit
                submit_brand_owner_form(currentStep);

            });




            function showStep(step) {
                steps.forEach((s, index) => {
                    s.style.display = index === step ? "block" : "none";
                });

                if (step === steps.length - 1) {
                    populateReviewStep();
                }
            }


            function validateField(field, message) {
                // Remove existing error message if present
                if (field && field.nextElementSibling && field.nextElementSibling.classList.contains('bf_error')) {
                    field.nextElementSibling.remove();
                }

                // Check if the field is empty
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.border = '2px solid red';

                    // Create error message
                    let errorSpan = document.createElement('span');
                    errorSpan.classList.add('bf_error', 'error');
                    errorSpan.innerText = message;
                    field.after(errorSpan);
                    return false;
                } else {
                    field.style.border = ''; // Reset border if valid
                    return true;
                }
            }

            function validateStep(step) {
                let inputs = steps[step].querySelectorAll("input[required], select[required], textarea[required]");
                let isValid = true;
                if(step=='0'){
                    // Select all required fields
                    let brandName = document.querySelector('input[name="brand_name"]');
                    let brandLogo = document.querySelector('input[name="brand_logo"]');
                    let trademarkOffice = document.querySelector('select[name="trademark_office"]');
                    let trademarkNumber = document.querySelector('input[name="trademark_number"]');
                    let brandDescription = document.querySelector('textarea[name="brand_description"]');

                    brandLogo.style.border = ''; // Reset border if valid
                    if (brandLogo.nextElementSibling && brandLogo.nextElementSibling.classList.contains('bf_error')) {
                        brandLogo.nextElementSibling.remove();
                    }

                    // Validate each field
                    if(!validateField(brandName, 'Brand name is required')){
                        isValid = false;
                    }else if(!validateField(brandName, 'Brand name is required')){
                        isValid = false;
                    }else if(!validateField(trademarkOffice, 'Please select a Trademark Office')){
                        isValid = false;
                    }else if(!validateField(trademarkNumber, 'Trademark Registration Number is required')){
                        isValid = false;
                    }else if(!validateField(brandDescription, 'Brand Description is required')){
                        isValid = false;
                    }else if (!brandLogo.files.length) {
                        isValid = false;
                        brandLogo.style.border = '2px solid red';

                        if (!brandLogo.nextElementSibling || !brandLogo.nextElementSibling.classList.contains('bf_error')) {
                            let errorSpan = document.createElement('span');
                            errorSpan.classList.add('bf_error', 'error');
                            errorSpan.innerText = 'Brand logo is required';
                            brandLogo.after(errorSpan);
                        }
                    }else if(isValid){
                        submit_brand_owner_form(currentStep);
                    }

                }else if (step == '1') {
                    // Validate Step 2 Fields
                    let businessName = document.querySelector('input[name="business_name"]');
                    let businessAddress = document.querySelector('input[name="business_address"]');
                    let businessEmail = document.querySelector('input[name="business_email"]');
                    let primaryContact = document.querySelector('input[name="primary_contact"]');
                    let phoneNumber = document.querySelector('input[name="phone_number"]');
                    let password = document.querySelector('input[name="user_pass"]');
                    let confirmPassword = document.querySelector('input[name="user_confirm_password"]');
                    let countryData = jQuery("#country").countrySelect("getSelectedCountryData");
                    let countryCode = countryData.iso2.toUpperCase();

                    let parsedNumber = libphonenumber.parsePhoneNumberFromString(phoneNumber.value, countryCode);

                    // Email validation
                    let emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    // Phone number validation (only digits allowed)
                    let phonePattern = /^\d{10}$/;


                    if(!validateField(businessName, 'Business Name is required')){
                        isValid = false;
                    }else if(!validateField(businessAddress, 'Business Address is required')){
                        isValid = false;
                    }else if(!validateField(businessEmail, 'Business Contact Email is required')){
                        isValid = false;
                    }else if(!validateField(primaryContact, 'Primary Contact Name is required')){
                        isValid = false;
                    }else if(!validateField(phoneNumber, 'Phone Number is required')){
                        isValid = false;
                    }else if(!validateField(password, 'Password is required')){
                        isValid = false;
                    }else if(!validateField(confirmPassword, 'Confirm Password is required')){
                        isValid = false;
                    }else if (!emailPattern.test(businessEmail.value.trim())) {
                        isValid = false;
                        businessEmail.style.border = '2px solid red';
                        if (!businessEmail.nextElementSibling || !businessEmail.nextElementSibling.classList.contains('bf_error')) {
                            let errorSpan = document.createElement('span');
                            errorSpan.classList.add('bf_error', 'error');
                            errorSpan.innerText = 'Enter a valid email address';
                            businessEmail.after(errorSpan);
                        }
                    }else if (parsedNumber && !parsedNumber.isValid()) {
                        isValid = false;
                        phoneNumber.style.border = '2px solid red';
                        if (!phoneNumber.nextElementSibling || !phoneNumber.nextElementSibling.classList.contains('bf_error')) {
                            let errorSpan = document.createElement('span');
                            errorSpan.classList.add('bf_error', 'error');
                            errorSpan.innerText = "Invalid phone number for " + countryData.name;
                            phoneNumber.after(errorSpan);
                        }
                    }

                    else if (password.value !== confirmPassword.value) {
                        isValid = false;
                        confirmPassword.style.border = '2px solid red';
                        if (!confirmPassword.nextElementSibling || !confirmPassword.nextElementSibling.classList.contains('bf_error')) {
                            let errorSpan = document.createElement('span');
                            errorSpan.classList.add('bf_error', 'error');
                            errorSpan.innerText = 'Passwords do not match';
                            confirmPassword.after(errorSpan);
                        }
                    }else if(isValid){
                        submit_brand_owner_form(currentStep);
                    }
                }else if(step == '2'){

                    let manufacturingLocations = document.querySelector('input[name="manufacturing_locations"]');
                    let distributionChannels = document.querySelector('input[name="distribution_channels"]');

                    if(!validateField(manufacturingLocations, 'Manufacturing location is required')){
                         isValid = false;

                    }else if(!validateField(distributionChannels, 'Distribution channels are required')){
                         isValid = false;

                    }else if(isValid){
                        currentStep = currentStep+1;
                        showStep(currentStep);
                    }


                }else if(step == '3'){
                    let productCategories = document.querySelector('input[name="product_categories"]');
                    // let productIds = document.querySelector('input[name="product_ids"]');
                    // let productImages = document.querySelector('input[name="product_images"]');

                    let productIds1 = document.querySelector('input[name="product_ids_1"]');
                    let productIds2 = document.querySelector('input[name="product_ids_2"]');
                    let productIds3 = document.querySelector('input[name="product_ids_3"]');
                    let productIds4 = document.querySelector('input[name="product_ids_4"]');
                    let productIds5 = document.querySelector('input[name="product_ids_5"]');

                    let productImages1 = document.querySelector('input[name="product_images_1"]');
                    let productImages2 = document.querySelector('input[name="product_images_2"]');
                    let productImages3 = document.querySelector('input[name="product_images_3"]');
                    let productImages4 = document.querySelector('input[name="product_images_4"]');
                    let productImages5 = document.querySelector('input[name="product_images_5"]');

                    if(productIds1.length && productIds1.value!=''){
                        productImages1.style.border = '';
                        if (productImages1.nextElementSibling && productImages1.nextElementSibling.classList.contains('bf_error')) {
                            productImages1.nextElementSibling.remove();
                        }

                    }



                    if(!validateField(productCategories, 'Product categories are required')){
                         isValid = false;

                    }

                    else if(isValid){
                        currentStep = currentStep+1;
                        showStep(currentStep);
                    }


                }else if(step == '4'){

                    let sellOnBrags = document.querySelector('select[name="sell_on_brags"]');
                    let approveResellers = document.querySelector('select[name="approve_resellers"]');
                    let bragsSellerEmail = document.querySelector('input[name="brags_seller_email"]');
                    let bragsStoreURL = document.querySelector('input[name="brags_store_url"]');
                    let additionalDocuments = document.querySelector('input[name="additional_documents"]');

                    bragsSellerEmail.style.border = '';
                    if (bragsSellerEmail.nextElementSibling && bragsSellerEmail.nextElementSibling.classList.contains('bf_error')) {
                        bragsSellerEmail.nextElementSibling.remove();
                    }

                    bragsStoreURL.style.border = '';
                    if (bragsStoreURL.nextElementSibling && bragsStoreURL.nextElementSibling.classList.contains('bf_error')) {
                        bragsStoreURL.nextElementSibling.remove();
                    }

                    additionalDocuments.style.border = '';
                    if (additionalDocuments.nextElementSibling && additionalDocuments.nextElementSibling.classList.contains('bf_error')) {
                        additionalDocuments.nextElementSibling.remove();
                    }

                    let emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    let urlPattern = /^(https?:\/\/)?([\w-]+\.)+[\w-]+(\/[\w-./?%&=]*)?$/i;
                    lowedExtensions = /(\.pdf|\.doc|\.docx)$/i;

                    // Validate Required Select Fields
                    if(!validateField(sellOnBrags, 'Please select if you want to sell on Brags')){
                         isValid = false;

                    }else if (sellOnBrags.value=='Yes' && !emailPattern.test(bragsSellerEmail.value.trim())) {
                        isValid = false;
                        bragsSellerEmail.style.border = '2px solid red';
                        let errorSpan = document.createElement('span');
                        errorSpan.classList.add('bf_error', 'error');
                        errorSpan.innerText = 'Enter a valid email address';
                        bragsSellerEmail.after(errorSpan);
                        bragsSellerEmail.focus();
                    }else if (sellOnBrags.value=='Yes' && !urlPattern.test(bragsStoreURL.value.trim())) {
                        isValid = false;
                        bragsStoreURL.style.border = '2px solid red';
                        let errorSpan = document.createElement('span');
                        errorSpan.classList.add('bf_error', 'error');
                        errorSpan.innerText = 'Enter a valid URL';
                        bragsStoreURL.after(errorSpan);
                        bragsStoreURL.focus();
                    }else if(!validateField(approveResellers, 'Please select if you want to approve resellers')){
                         isValid = false;

                    }else if(isValid){
                        currentStep = currentStep+1;
                        showStep(currentStep);

                    }

                }


                return isValid;
            }

            function populateReviewStep() {
                document.getElementById("review_brand_name").textContent = document.querySelector("[name='brand_name']").value || "N/A";
                document.getElementById("review_trademark_office").textContent = document.querySelector("[name='trademark_office']").value || "N/A";
                document.getElementById("review_trademark_number").textContent = document.querySelector("[name='trademark_number']").value || "N/A";
                document.getElementById("review_brand_description").textContent = document.querySelector("[name='brand_description']").value || "N/A";

                let brandLogo = document.querySelector("[name='brand_logo']");
                if (brandLogo.files.length > 0) {
                    let brandLogoUrl = URL.createObjectURL(brandLogo.files[0]);
                    document.getElementById("review_brand_logo").src = brandLogoUrl;
                    document.getElementById("review_brand_logo").style.display = "block";
                }

                document.getElementById("review_business_name").textContent = document.querySelector("[name='business_name']").value || "N/A";
                document.getElementById("review_business_address").textContent = document.querySelector("[name='business_address']").value || "N/A";
                document.getElementById("review_business_email").textContent = document.querySelector("[name='business_email']").value || "N/A";
                document.getElementById("review_primary_contact").textContent = document.querySelector("[name='primary_contact']").value || "N/A";
                document.getElementById("review_phone_number").textContent = document.querySelector("[name='phone_number']").value || "N/A";
                document.getElementById("review_website_url").textContent = document.querySelector("[name='website_url']").value || "N/A";

                document.getElementById("review_manufacturing_locations").textContent = document.querySelector("[name='manufacturing_locations']").value || "N/A";
                document.getElementById("review_distribution_channels").textContent = document.querySelector("[name='distribution_channels']").value || "N/A";

                // Handle multiple Product IDs (1-5)
                let productIds = [];
                for (let i = 1; i <= 5; i++) {
                    let productIdInput = document.querySelector(`[name='product_ids_${i}']`);
                    if (productIdInput && productIdInput.value.trim() !== "") {
                        productIds.push(productIdInput.value);
                    }
                }
                document.getElementById("review_product_ids").textContent = productIds.length > 0 ? productIds.join(", ") : "N/A";

                // Handle multiple Product Images (1-5)
                let productImagesContainer = document.getElementById("review_product_images");
                productImagesContainer.innerHTML = ""; // Clear previous images

                for (let i = 1; i <= 5; i++) {
                    let productImageInput = document.querySelector(`[name='product_images_${i}']`);
                    if (productImageInput && productImageInput.files.length > 0) {
                        let productImageUrl = URL.createObjectURL(productImageInput.files[0]);
                        let imgElement = document.createElement("img");
                        imgElement.src = productImageUrl;
                        imgElement.alt = `Product Image ${i}`;
                        imgElement.style.maxWidth = "150px";
                        imgElement.style.marginRight = "10px";
                        productImagesContainer.appendChild(imgElement);
                    }
                }

                document.getElementById("review_sell_on_brags").textContent = document.querySelector("[name='sell_on_brags']").value || "N/A";
                document.getElementById("review_brags_seller_email").textContent = document.querySelector("[name='brags_seller_email']").value || "N/A";
                document.getElementById("review_brags_store_url").textContent = document.querySelector("[name='brags_store_url']").value || "N/A";
                document.getElementById("review_approve_resellers").textContent = document.querySelector("[name='approve_resellers']").value || "N/A";

                let additionalDoc = document.querySelector("[name='additional_documents']");
                let additionalDocLink = document.getElementById("review_additional_documents");
                if (additionalDoc.files.length > 0) {
                    let docUrl = URL.createObjectURL(additionalDoc.files[0]);
                    additionalDocLink.href = docUrl;
                    additionalDocLink.style.display = "inline";
                } else {
                    additionalDocLink.style.display = "none";
                }
            }


            nextButtons.forEach((btn, index) => {
                btn.addEventListener("click", function() {
                    validateStep(currentStep);
                    // if (validateStep(currentStep)) {
                    //     if (currentStep < steps.length - 1) {
                    //         currentStep++;
                    //         showStep(currentStep);
                    //     }
                    // }
                });
            });

            prevButtons.forEach((btn) => {
                btn.addEventListener("click", function() {
                    if (currentStep > 0) {
                        currentStep--;
                        showStep(currentStep);
                    }
                });
            });

            showStep(currentStep);

            function submit_brand_owner_form(c_stap){

                let form = document.getElementById("ur-multi-step-form-fa");
                let submitButton = document.getElementById("ur-submit-btn");
                let btnText = submitButton.querySelector(".btn-text");
                let spinner = submitButton.querySelector(".spinner");

                btnText.style.display = "none";
                spinner.style.display = "inline-block";
                submitButton.disabled = true;


                let btnText2 = document.querySelector(".btn-text");
                let spinner2 = document.querySelector(".spinner");

                btnText2.style.display = "none";
                spinner2.style.display = "inline-block";

                let formData = new FormData(form);
                formData.append('action','user_form_submit');
                formData.append('currentstep',c_stap);

                jQuery.ajax({
                    type: "POST",
                    url: "/wp-admin/admin-ajax.php",
                    data: formData,
                    dataType: "json",
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        console.log("Success:", response);
                        if(response.success){

                            if (response.data.redirect_url) {
                                window.location.href = response.data.redirect_url; // Redirect user
                            }else{
                                currentStep = response.data.currentstep;

                                if(currentStep < steps.length - 1){
                                    showStep(currentStep);
                                }

                            }
                        }else{
                            businessEmail = document.querySelector('input[name="business_email"]');
                            brandName = document.querySelector('input[name="brand_name"]');

                            if(response.data.error.brand_name && response.data.currentstep=='0'){
                                isValid = false;

                                brandName.style.border = '2px solid red';

                                if (brandName.nextElementSibling && brandName.nextElementSibling.classList.contains('bf_error')) {
                                    brandName.nextElementSibling.remove();
                                }
                                let errorSpan = document.createElement('span');
                                errorSpan.classList.add('bf_error', 'error');
                                errorSpan.innerText = response.data.error.brand_name;
                                brandName.after(errorSpan);
                                brandName.focus();
                            }else{
                                brandName.style.border = '';
                                if (brandName.nextElementSibling && brandName.nextElementSibling.classList.contains('bf_error')) {
                                    brandName.nextElementSibling.remove();
                                }
                            }
                            if(response.data.error.business_email && response.data.currentstep=='1'){
                                isValid = false;

                                businessEmail.style.border = '2px solid red';

                                if (businessEmail.nextElementSibling && businessEmail.nextElementSibling.classList.contains('bf_error')) {
                                    businessEmail.nextElementSibling.remove();
                                }
                                let errorSpan = document.createElement('span');
                                errorSpan.classList.add('bf_error', 'error');
                                errorSpan.innerText = response.data.error.business_email;
                                businessEmail.after(errorSpan);
                                businessEmail.focus();
                            }else{
                                businessEmail.style.border = '';
                                if (businessEmail.nextElementSibling && businessEmail.nextElementSibling.classList.contains('bf_error')) {
                                    businessEmail.nextElementSibling.remove();
                                }
                            }
                        }

                        // if (response.success && response.data.redirect_url) {
                        //     window.location.href = response.data.redirect_url; // Redirect user
                        // }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error("AJAX Error:", textStatus, errorThrown);
                        //alert("An error occurred. Please try again.");
                    },
                    complete: function() {
                        // Restore button state
                        btnText.style.display = "inline";
                        spinner.style.display = "none";
                        submitButton.disabled = false;

                        btnText2.style.display = "inline";
                        spinner2.style.display = "none";
                        submitButton.disabled = false;
                    }
                });


            }
        });
    </script>
    <?php
}
add_action('user_registration_before_form_fields', 'ur_add_custom_multistep_fields', 10, 2);


function ur_enqueue_multistep_scripts() {
    wp_enqueue_script('multi-step-form', get_stylesheet_directory_uri() . '/assets/js/multi-step-form.js', array('jquery'), null, true);
    //wp_enqueue_style('multi-step-form-style', get_stylesheet_directory_uri() . '/assets/css/multi-step-form.css');
}
add_action('wp_enqueue_scripts', 'ur_enqueue_multistep_scripts');


function redirect_logged_in_users_from_registration() {
    if (is_user_logged_in() && is_page('brand-owner-registration')) {

        // Get current user role(s)
        $user = wp_get_current_user();
        $user_roles = $user->roles;

        // Check if user is a "seller" (Dokan vendor role is 'seller' or 'vendor')
        if (in_array('seller', $user_roles) || in_array('vendor', $user_roles) || in_array('brand_owner', $user_roles)) {
            wp_redirect(site_url('/brand-owner-my-account/edit-profile/')); // Redirect sellers to My Account
            exit;
        }

        // Redirect all other logged-in users
        wp_redirect(site_url('/my-account/'));
        exit;
    }
}
add_action('template_redirect', 'redirect_logged_in_users_from_registration');


function user_form_submit() {
    // Get form data
    $currentstep = sanitize_text_field($_POST['currentstep']);
    $brand_name = sanitize_text_field($_POST['brand_name']);

    $user_email = sanitize_email($_POST['business_email']);
    $user_pass = sanitize_text_field($_POST['user_pass']);
    $confirm_pass = sanitize_text_field($_POST['user_confirm_password']);

    $term = get_term_by('name', $brand_name, 'product_brand');

    if ($term) {
        $error = array(
            'brand_name'=>'Brand already exists.'
        );
        wp_send_json_error(['error'=>$error,'currentstep'=>$currentstep]);
        wp_die();
    }
    if($currentstep==0){
        wp_send_json_success([
            'currentstep'=>$currentstep+1,
        ]);
        wp_die();
    }

    if($currentstep>0 && $currentstep<5){


        if (email_exists($user_email)) {
            $error = array(
                'business_email'=>'Email already registered.'
            );
            wp_send_json_error(['error'=>$error,'currentstep'=>$currentstep]);
            wp_die();
        }

        if ($user_pass !== $confirm_pass) {
            $error = array(
                'user_confirm_password'=>'Email already registered.'
            );
            wp_send_json_error(['error'=>$error,'currentstep'=>$currentstep]);
            wp_die();
        }
        $currentstep = $currentstep + 1;
        wp_send_json_success([
            'currentstep'=>$currentstep,
        ]);
        wp_die();
    }

    if($currentstep >= 5){


        $user_id = wp_create_user($user_email, $user_pass, $user_email);

        //print_r($user_id);
        if (is_wp_error($user_id)) {
            $error = array(
                'user_confirm_password'=>'User registration failed..'
            );
            wp_send_json_error(['error'=>$error,'currentstep'=>$currentstep]);
            wp_die();
        }

        $form_id = sanitize_text_field($_POST['ur-user-form-id']);
        $user_role = !in_array(ur_get_form_setting_by_key($form_id, 'user_registration_form_setting_default_user_role'), array_keys(ur_get_default_admin_roles()))
            ? 'subscriber'
            : ur_get_form_setting_by_key($form_id, 'user_registration_form_setting_default_user_role');
        wp_update_user(['ID' => $user_id, 'role' => $user_role]);


        update_user_meta($user_id, 'ur_form_id', $form_id);
        update_user_meta($user_id, 'registration_time', current_time('mysql'));

        // $meta_fields = [
        //     'brand_name', 'trademark_office', 'trademark_number', 'brand_description',
        //     'business_name', 'business_address', 'phone_number', 'primary_contact',
        //     'website_url', 'manufacturing_locations', 'distribution_channels',
        //     'product_categories', 'product_ids', 'sell_on_brags', 'approve_resellers',
        //     'brags_seller_email', 'brags_store_url','country'
        // ];

        // foreach ($meta_fields as $field) {
        //     if (!empty($_POST[$field])) {
        //         update_user_meta($user_id, $field, sanitize_text_field($_POST[$field]));
        //     }
        // }

        //update_user_meta($user_id, 'pw_user_status', 'pending');
        //do_action('create_brand_post',$user_id, 'pending');

        if (!empty($brand_name)) {
            $existing_brand = get_page_by_title($brand_name, OBJECT, 'brand');
            if (!$existing_brand) {
                $brand_data = array(
                    'post_title'   => sanitize_text_field($brand_name),
                    'post_content' => 'Brand created for user ID: ' . $user_id,
                    'post_status'  => 'pending',
                    'post_type'    => 'brand',
                    'post_author'  => $user_id,
                );
                $brand_id = wp_insert_post($brand_data);
                if($brand_id){
                    $meta_fields = [
                        'trademark_office', 'trademark_number', 'brand_description',
                        'business_name', 'business_address', 'phone_number', 'primary_contact',
                        'website_url', 'manufacturing_locations', 'distribution_channels',
                        'product_categories', 'product_ids', 'sell_on_brags', 'approve_resellers',
                        'brags_seller_email', 'brags_store_url', 'country'
                    ];

                    foreach ($meta_fields as $field) {
                        if (!empty($_POST[$field])) {
                            update_post_meta($brand_id, $field, sanitize_text_field($_POST[$field]));
                        }
                    }

                    // Upload and Store Brand Images
                    $upload_fields = ['brand_logo', 'additional_documents'];
                    foreach ($upload_fields as $file_field) {
                        if (!empty($_FILES[$file_field]['name'])) {
                            $file_url = upload_user_file($_FILES[$file_field], $file_field, $user_id);
                            update_post_meta($brand_id, $file_field, $file_url);
                        }
                    }

                    // Upload product images separately
                    for ($i = 1; $i <= 5; $i++) {
                        $file_field = "product_images_$i";
                        if (!empty($_FILES[$file_field]['name'])) {
                            $file_url = upload_user_file($_FILES[$file_field], $file_field, $user_id);
                            update_post_meta($brand_id, $file_field, $file_url);
                        }
                    }

                    // Store product IDs separately
                    for ($i = 1; $i <= 5; $i++) {
                        $id_field = "product_ids_$i";
                        if (!empty($_POST[$id_field])) {
                            update_post_meta($brand_id, $id_field, sanitize_text_field($_POST[$id_field]));
                        }
                    }





                }
            }
        }

        //  Handle file uploads
        // $upload_fields = ['brand_logo','additional_documents'];
        // foreach ($upload_fields as $file_field) {
        //     if (!empty($_FILES[$file_field]['name'])) {
        //         $file_url = upload_user_file($_FILES[$file_field], $file_field, $user_id);
        //         update_user_meta($user_id, $file_field, $file_url);
        //     }
        // }

        // // Upload product images separately (as individual fields)
        // for ($i = 1; $i <= 5; $i++) {
        //     $file_field = "product_images_$i";
        //     if (!empty($_FILES[$file_field]['name'])) {
        //         $file_url = upload_user_file($_FILES[$file_field], $file_field, $user_id);
        //         update_user_meta($user_id, $file_field, $file_url); // Stores each separately
        //     }
        // }

        // // Store product IDs separately
        // for ($i = 1; $i <= 5; $i++) {
        //     $id_field = "product_ids_$i";
        //     if (!empty($_POST[$id_field])) {
        //         update_user_meta($user_id, $id_field, sanitize_text_field($_POST[$id_field]));
        //     }
        // }

        //  Ensure User is Logged In
        $user = get_user_by('ID', $user_id);
        if ($user) {
            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id);
            do_action('wp_login', $user->user_login, $user);
        }

        $redirect_url = ur_get_form_redirect_url($form_id);

        //  Debugging to Ensure Data is Stored
        error_log(print_r(get_user_meta($user_id), true));

        wp_send_json_success([
            'message' => 'User registered successfully!',
            'user_id' => $user_id,
            'redirect_url' => $redirect_url
        ]);
    }

    wp_die();
}


// Function to handle file uploads
function upload_user_file($file, $meta_key, $user_id) {
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $upload = wp_handle_upload($file, ['test_form' => false]);
    if (isset($upload['file'])) {
        return $upload['url'];
    }
    return '';
}

// Hook into AJAX
add_action('wp_ajax_user_form_submit', 'user_form_submit');
add_action('wp_ajax_nopriv_user_form_submit', 'user_form_submit');


add_action('wp_ajax_update_user_profile', 'update_user_profile_ajax');
add_action('wp_ajax_nopriv_update_user_profile', 'update_user_profile_ajax'); // Optional for non-logged users

function update_user_profile_ajax() {
    // Check if the user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'You must be logged in to update profile.']);
        wp_die();
    }

    $user_id = get_current_user_id();
    $user = get_userdata($user_id);
    $user_roles = $user->roles;
    $brand_name = sanitize_text_field($_POST['brand_name']);

    $term = get_term_by('name', $brand_name, 'product_brand');
    if ($term && !in_array('brand_owner', $user_roles)) {
        $error = array('brand_name' => 'Brand already exists.');
        wp_send_json_error(['error' => $error]);
        wp_die();
    }

    // If user does not have the 'brand_owner' role, add it
    if (!in_array('brand_owner', $user_roles)) {
        $user->add_role('brand_owner');
    }

    $fields_to_update = [
        'brand_name',
        'trademark_office',
        'trademark_number',
        'brand_description',
        'business_name',
        'business_address',
        'business_email',
        'phone_number',
        'primary_contact',
        'website_url',
        'manufacturing_locations',
        'distribution_channels',
        'product_categories',
        'sell_on_brags',
        'approve_resellers',
        'brags_seller_email',
        'brags_store_url',
        'country'
    ];

    foreach ($fields_to_update as $field) {
        if (isset($_POST[$field])) {
            $sanitized_value = ($field === 'business_email') ? sanitize_email($_POST[$field]) : sanitize_text_field($_POST[$field]);
            update_user_meta($user_id, $field, $sanitized_value);
        }
    }

    // Handle checkboxes (set to 1 if checked, 0 if not)
    update_user_meta($user_id, 'sell_on_brags', isset($_POST['sell_on_brags']) ? '1' : '0');
    update_user_meta($user_id, 'approve_resellers', isset($_POST['approve_resellers']) ? '1' : '0');

    require_once ABSPATH . 'wp-admin/includes/file.php';

    // Handle Brand Logo Upload
    if (!empty($_FILES['brand_logo']['name'])) {
        $uploaded_file = wp_handle_upload($_FILES['brand_logo'], ['test_form' => false]);

        if (!isset($uploaded_file['error'])) {
            update_user_meta($user_id, 'brand_logo', $uploaded_file['url']);
        } else {
            wp_send_json_error(['message' => 'Error uploading brand logo.']);
            wp_die();
        }
    }

    // Handle Product IDs
    for ($i = 1; $i <= 5; $i++) {
        if (!empty($_POST["product_ids_$i"])) {
            $product_id = sanitize_text_field($_POST["product_ids_$i"]);
            update_user_meta($user_id, "product_id_$i", $product_id);
        }
    }

    // Handle Product Images
    for ($i = 1; $i <= 5; $i++) {
        if (!empty($_FILES["product_images_$i"]['name'])) {
            $file = [
                'name'     => $_FILES["product_images_$i"]['name'],
                'type'     => $_FILES["product_images_$i"]['type'],
                'tmp_name' => $_FILES["product_images_$i"]['tmp_name'],
                'error'    => $_FILES["product_images_$i"]['error'],
                'size'     => $_FILES["product_images_$i"]['size']
            ];

            $upload = wp_handle_upload($file, ['test_form' => false]);
            if (!isset($upload['error'])) {
                update_user_meta($user_id, "product_image_$i", $upload['url']);
            }
        }
    }

    wp_send_json_success(['message' => 'Profile updated successfully!']);
    wp_die();
}




function customize_dokan_product_search($query) {
    if (!is_admin() && $query->is_main_query() && isset($_GET['s'])) {
        global $wpdb;

        $search_query = sanitize_text_field($_GET['s']);

        // Extend default search to include _barcode custom field
        $meta_query = array(
            array(
                'key'     => '_barcode', // Search by barcode
                'value'   => $search_query,
                'compare' => 'LIKE'
            )
        );

        // Modify WooCommerce search
        $query->set('meta_query', $meta_query);
        $query->set('post_type', array('product')); // Ensure it's only searching products
    }
}
//add_action('pre_get_posts', 'customize_dokan_product_search');

function customize_woocommerce_search($where, $query) {
    global $wpdb;

    if ($query->is_main_query()) {
        $search_term = esc_sql($query->get('s'));

        if (!empty($search_term)) {
            $where .= " OR EXISTS (
                SELECT * FROM {$wpdb->postmeta}
                WHERE post_id = {$wpdb->posts}.ID
                AND meta_key = '_barcode'
                AND meta_value LIKE '%{$search_term}%'
            )";
        }
    }

    return $where;
}
add_filter('posts_where', 'customize_woocommerce_search', 10, 2);

function enqueue_custom_styles() {
    if (is_page(array('brand-owner-registration'))) {
        wp_enqueue_style('brand-owner-registration-css', get_stylesheet_directory_uri() . '/assets/css/brand-owner-registration.css');
    }
}
add_action('wp_enqueue_scripts', 'enqueue_custom_styles');
