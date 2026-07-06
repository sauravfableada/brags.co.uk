jQuery(function($) {
    let otpVerified = false;

    function showError(target, message) {
        target.siblings('.error-message').remove();
        target.after(`<span class="error-message" style="color:red; font-size:13px;">${message}</span>`);
    }

    function clearError(target) {
        target.siblings('.error-message').remove();
    }

    // Handle role change
    $('input[name="role"]').on('change', function () {
        if ($(this).val() === 'seller') {
            $('#send-otp-btn').show().text('Send OTP');
            $('p.form-row.form-row-first').hide();
            $('p.form-row.form-row-last').hide();
            //$('#passport_upload_individual').attr('required',true);
        } else {
            $('#send-otp-btn, #otp-section').hide();
            otpVerified = false;
            $('#otp_verified').val('0');
            $('p.form-row.form-row-first').show();
            $('p.form-row.form-row-last').show();
            //$('#passport_upload_individual').removeAttr('required');
        }
    });

    // Send OTP button click
    $('#send-otp-btn').on('click', function () {
        const phoneField = $('#shop-phone');
        const codeField = $('#country_code');
        const phone = phoneField.val().trim();
        const countryCode = codeField.val().trim();

        clearError(phoneField);
        clearError(codeField);

        if (!countryCode) {
            showError(codeField, 'Country code is required');
            return;
        }

        if (!phone) {
            showError(phoneField, 'Phone number is required');
            return;
        }

        $.post(bragsy_otp_ajax.ajax_url, {
            action: 'bragsy_send_seller_otp',
            phone: phone,
            country_code: countryCode,
            nonce: bragsy_otp_ajax.nonce
        }, function (res) {
            if (res.success) {
                $('#otp-section').show();
                $('#send-otp-btn').text('Send Again');
                $('#otp-message').text('OTP sent! Please check your phone.').css('color', 'green');
            } else {
                $('#otp-message').text(res.data).css('color', 'red');
            }
        });
    });

    // Verify OTP
    $('#verify-otp-btn').on('click', function () {
        const otpInput = $('#otp-input');
        const otp = otpInput.val().trim();

        clearError(otpInput);

        if (!otp) {
            showError(otpInput, 'Please enter the OTP');
            return;
        }

        $.post(bragsy_otp_ajax.ajax_url, {
            action: 'bragsy_verify_seller_otp',
            otp: otp,
            nonce: bragsy_otp_ajax.nonce
        }, function (res) {
            if (res.success) {
                otpVerified = true;
                $('#otp-message').text('OTP verified!').css('color', 'green');
                $('#otp_verified').val('1');
                $('#otp-section').hide();
                $('#send-otp-btn').hide();
            } else {
                $('#otp-message').text(res.data).css('color', 'red');
                $('#otp_verified').val('0');
            }
        });
    });


    // File upload success handler
    $('#passport_upload_individual').on('change', function() {
        const $input = $(this);
        const $button = $('#upload_passport_button_individual');
        const maxFiles = parseInt($input.data('max-files')) || 10; // Get from data attribute or default to 10
        const allowedTypes = ['pdf', 'doc', 'docx', 'jpeg', 'jpg', 'png'];
        
        // Remove any existing messages
        $input.siblings('.error-message').remove();
        $input.siblings('.success-message').remove();

        if (this.files.length > 0) {
            // Validate file count
            if (this.files.length > maxFiles) {
                $button.after(`
                    <span class="error-message" style="color:red; font-size:13px;">
                        Error: Maximum ${maxFiles} files allowed. You selected ${this.files.length}.
                    </span>
                `);
                $input.val(''); // Clear the selection
                return; // Exit the function
            }
            
            // Validate file types
            const invalidFiles = [];
            Array.from(this.files).forEach(file => {
                const extension = file.name.split('.').pop().toLowerCase();
                if (!allowedTypes.includes(extension)) {
                    invalidFiles.push(file.name);
                }
            });
            
            if (invalidFiles.length > 0) {
                $button.after(`
                    <span class="error-message" style="color:red; font-size:13px;">
                        Invalid file type(s): ${invalidFiles.join(', ')}<br>
                        Allowed types: ${allowedTypes.join(', ')}
                    </span>
                `);
                $input.val(''); // Clear the selection
                return; // Exit the function
            }
            
            // If validation passes, show success message
            $button.after(`
                <span class="success-message" style="color:green; font-size:13px;">
                    ${this.files.length} file(s) selected successfully. Ready for upload.
                </span>
            `);
            
            // Optional: Display file names in console
            const fileNames = Array.from(this.files).map(file => file.name).join(', ');
            console.log('Selected files:', fileNames);
        }
        
        // if (this.files.length > 0) {
        //     // Show success message
        //     $button.after(`
        //         <span class="success-message" style="color:green; font-size:13px;">
        //             File Uploaded Successfully. ${this.files.length} file(s) selected.
        //         </span>
        //     `);
            
        //     // Optional: Display file names
        //     const fileNames = Array.from(this.files).map(file => file.name).join(', ');
        //     console.log('Selected files:', fileNames);
        // }
    });


    // Prevent form submission if OTP not verified
    $('form.register').on('submit', function (e) {
        const isSeller = $('input[name="role"]:checked').val() === 'seller';
        const account_type = $('select[name="account_type"]').val();
         
        if (isSeller && $('#otp_verified').val() !== '1') {
            e.preventDefault();
            showError($('#shop-phone'), 'Please verify your phone number');
            $('#shop-phone').focus();
        }else if (isSeller && $('#passport_upload_individual').val() == '') {
            if(account_type=='individual'){
                e.preventDefault();
                showError($('#upload_passport_button_individual'), 'Please upload your Passport');
                $('#account_type').focus();
            }
            
        }
    });
});
