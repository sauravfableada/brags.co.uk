jQuery(document).ready(function ($) {
    
        let formBlocked = false;

    $('#dokan-register-form').on('submit', function (e) {
      
        const isSeller = $('[name="role"]').val() === 'seller';
        if (!isSeller || formBlocked) return; // Skip if not seller or already verified

        e.preventDefault(); // Stop normal registration

        let phone = $('#shop-phone').val();
        let country_code = $('#country_code').val();

        if (!phone || !country_code) {
            alert("Phone and country code are required.");
            return;
        }

        $.ajax({
            url: my_ajax_object.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'pre_register_send_otp',
                phone: phone,
                country_code: country_code,
                nonce: my_ajax_object.nonce
            },
            success: function (res) {
                if (res.success) {
                    $('body').append(res.data);
                    bindOtpVerification();
                    $('#otp-popup-overlay').show();
                } else {
                    alert(res.data);
                }
            }
        });
    });

    
});
function bindOtpVerification() {
    $('#verify-otp-btn').on('click', function () {
        const otp = $('#otp-input').val();
        const msgBox = $('#otp-message');

        msgBox.html('Verifying...');

        $.ajax({
            url: my_ajax_object.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'verify_seller_otp',
                otp: otp,
                nonce: my_ajax_object.nonce
            },
            success: function (data) {
                if (data.success) {
                    msgBox.css('color', 'green').html('✅ OTP Verified. Registering...');
                    formBlocked = true;
                    setTimeout(function () {
                        $('#otp-popup-overlay').remove();
                        $('#dokan-register-form').submit(); // submit after OTP verified
                    }, 1000);
                } else {
                    msgBox.css('color', 'red').html(data.data);
                }
            }
        });
    });
}