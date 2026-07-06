   
            jQuery('#resend-otp-link').on('click', function (e) {
                    
                e.preventDefault();
              
                var msgBox = jQuery('#otp-message');
                msgBox.html('Sending new OTP...');

                jQuery.ajax({
                    url: my_ajax_object.ajax_url,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'resend_seller_otp',
                        nonce: my_ajax_object.nonce,
                        user_id: jQuery("#user_id").val(),
                        phonono: jQuery("#phonono").val(),
                        countrycode: jQuery("#countrycode").val()
                    },
                    success: function (data) {
                        if (data.success) {
                            msgBox.css('color', 'green').html('✅ New OTP sent!');
                        } else {
                            msgBox.css('color', 'red').html(data.data || 'Failed to resend OTP.');
                        }
                    }
                });
            });