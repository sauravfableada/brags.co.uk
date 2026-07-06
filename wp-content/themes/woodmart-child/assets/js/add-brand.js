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
            else if(isValid){
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
                
            } else if(isValid){
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
        formData.append('action','add_update_brand');
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
                        //location.reload();
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