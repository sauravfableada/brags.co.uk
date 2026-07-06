<?php 
$current_user = wp_get_current_user();
$user_email = $current_user->user_email;
?>
<form method="POST" enctype="multipart/form-data" id="ur-multi-step-form-fa">
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
            <input type="email" name="business_email" value="<?php echo $user_email; ?>" required>

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
            <input type="file" name="additional_documents" accept=".pdf,.doc,.docx">

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
</form>