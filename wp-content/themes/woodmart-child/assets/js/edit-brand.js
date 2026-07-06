jQuery(document).ready(function ($) {
    // Initialize country dropdown
    $("input[name='country']").countrySelect({
        preferredCountries: ["us", "gb", "in", "de", "au"],
    });

    // Function to display error messages
    function showError(element, message) {
        let errorSpan = document.createElement("span");
        errorSpan.classList.add("bf_error", "error");
        errorSpan.innerText = message;
        element.after(errorSpan);
        element.focus();
    }

    // Function to validate email format
    function validateEmail(email) {
        let emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        return emailRegex.test(email);
    }

    // Function to validate phone number (Only numbers, 10-15 digits)
    function validatePhoneNumber(phone) {
        let phoneRegex = /^[0-9]{10,15}$/;
        return phoneRegex.test(phone);
    }

    $("#update_profile").on("click", function (e) {
        e.preventDefault();

        // Clear previous error messages
        $(".bf_error").remove();

        // Define required fields
        const requiredFields = [
            { name: "brand_name", message: "Brand Name is required" },
            { name: "trademark_office", message: "Trademark Office is required" },
            { name: "trademark_number", message: "Trademark Number is required" },
            { name: "brand_description", message: "Brand Description is required" },
            { name: "business_name", message: "Business Name is required" },
            { name: "business_address", message: "Business Address is required" },
            { name: "phone_number", message: "Phone Number is required", type: "phone" },
            { name: "primary_contact", message: "Primary Contact is required" },
            { name: "business_email", message: "Business Email is required", type: "email" },
            // { name: "website_url", message: "Website URL is required" },
            { name: "manufacturing_locations", message: "Manufacturing Locations is required" },
            { name: "distribution_channels", message: "Distribution Channels is required" },
            { name: "product_categories", message: "Product Category is required" },
        ];

        let isValid = true;

        requiredFields.forEach((field) => {
            let inputElement = $(`input[name="${field.name}"], textarea[name="${field.name}"]`);
            let value = inputElement.val().trim();

            // Remove any existing error message
            inputElement.next(".bf_error").remove();

            // Required field validation
            if (value === "") {
                showError(inputElement[0], field.message);
                isValid = false;
            }
            // Email validation
            else if (field.type === "email" && !validateEmail(value)) {
                showError(inputElement[0], "Enter a valid email address");
                isValid = false;
            }
            // Phone number validation
            else if (field.type === "phone" && !validatePhoneNumber(value)) {
                showError(inputElement[0], "Enter a valid phone number (10-15 digits)");
                isValid = false;
            }
        });

        if (!isValid) return false;

        // Prepare FormData for AJAX request
        let formData = new FormData($("#edit-brand-form")[0]);
        formData.append("action", "edit_update_brand");

        $.ajax({
            url: ajaxurl, // Localized in PHP
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function () {
                $("#update-message").html("<p>Updating...</p>");
            },
            success: function (response) {
                if (response.success) {
                    $("#update-message").html('<p style="color:green;">' + response.data.message + "</p>");
                    setTimeout(function () {
                        location.reload();
                    }, 2000);
                } else {
                    if (response.data.errors) {
                        Object.keys(response.data.errors).forEach(function (field) {
                            let inputElement = $(`input[name="${field}"], textarea[name="${field}"]`);
                            if (inputElement.length > 0) {
                                showError(inputElement[0], response.data.errors[field]);
                            }
                        });
                    }
                    if (response.data.message) {
                        $("#update-message").html('<p style="color:red;">' + response.data.message + "</p>");
                    }
                }
            },
            error: function () {
                $("#update-message").html('<p style="color:red;">An error occurred. Please try again.</p>');
            },
        });
    });
});
