jQuery(document).ready(function($) {
    const { ajax_url, nonce, is_edit } = dokanStaffData;
    let otpVerified = false;
    console.log('Dokan Staff JS loaded');
    function showError(target, message) {
        target.siblings('.error-message').remove();
        target.after(`<span class="error-message" style="color:red; font-size:13px;">${message}</span>`);
    }

    function clearError(target) {
        target.siblings('.error-message').remove();
    }

    if (is_edit === '1') {
        $('#send-staff-otp-btn').hide();
    }

     // Check if phone number was changed (edit mode only)
    function checkPhoneChange() {
        
        let original_phone = $('#original_phone').val().trim();
        if (is_edit === '1') {
            const currentPhone = $('#phone').val().trim();
            const phoneChanged = (currentPhone !== original_phone);
           $('#phone_changed').val(phoneChanged ? '1' : '0');

            if (phoneChanged) {
                $('#send-staff-otp-btn').show();
                $('#staff_otp_verified').val('0'); // Reset verification status
                otpVerified = false;
            }else{
                $('#send-staff-otp-btn').hide();
                $('#staff_otp_verified').val('1');
            }
        }
        $('#staff-otp-submit-error').remove(); 
    }


        // Validate OTP on form submit
    $('.vendor-staff.register').on('submit', function (e) {
        const isVerified = $('#staff_otp_verified').val();
        $('#staff-otp-submit-error').remove(); // Clear previous error

        if (isVerified !== '1') {
            e.preventDefault();

            const errorMsg = $('<div id="staff-otp-submit-error" style="color:red; margin-top:10px; font-size:13px;">Please verify your phone number using OTP before submitting.</div>');
            //$('.vendor-staff.register input[type="submit"]').closest('.dokan-text-left').append(errorMsg);
            $('#phone').after(errorMsg);

            return false;
        }
    });


    
    // Send OTP button click
    $('#send-staff-otp-btn').on('click', function () {
        const phoneField = $('#phone');
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

        $.post(dokanStaffData.ajax_url, {
            action: 'bragsy_send_seller_staff_otp',
            phone: phone,
            country_code: countryCode,
            nonce: dokanStaffData.nonce
        }, function (res) {
            if (res.success) {
                $('#otp-section').show();
                $('#send-staff-otp-btn').text('Send Again');
                $('#staff-otp-message').text('OTP sent! Please check your phone.').css('color', 'green');
            } else {
                $('#staff-otp-message').text(res.data).css('color', 'red');
            }
        });
         $('#staff-otp-submit-error').remove();
    });

      // Verify OTP
    $('#verify-staff-otp-btn').on('click', function () {
        const otpInput = $('#staff-otp-input');
        const otp = otpInput.val().trim();

        clearError(otpInput);

        if (!otp) {
            showError(otpInput, 'Please enter the OTP');
            return;
        }

        $.post(dokanStaffData.ajax_url, {
            action: 'bragsy_verify_seller_staff_otp',
            otp: otp,
            nonce: dokanStaffData.nonce
        }, function (res) {
            if (res.success) {
                otpVerified = true;
                $('#staff-otp-message').text('OTP verified!').css('color', 'green');
                $('#staff_otp_verified').val('1');
                $('#otp-section').hide();
                $('#send-otp-btn').hide();
            } else {
                $('#staff-otp-message').text(res.data).css('color', 'red');
                $('#staff_otp_verified').val('0');
            }
        });
    });


    if (is_edit === '1') {
       $('#phone').on('input', checkPhoneChange);
    }
});