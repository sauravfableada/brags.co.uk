<?php
    add_action('woocommerce_register_post', 'send_otp_before_register', 10, 3);

    function send_otp_before_register($username, $email, $errors)
    {
        if (isset($_POST['role']) && $_POST['role'] === 'seller') {
        if (isset($_POST['country_code']) && isset($_POST['phone'])) {
            $country_code = sanitize_text_field($_POST['country_code']);
            $phone = sanitize_text_field($_POST['phone']);
            
            // Save the phone number and country code temporarily, e.g., in session or user meta
            $_SESSION['temp_country_code'] = $country_code;
            $_SESSION['temp_phone'] = $phone;
    
            // Generate OTP
            $otp = rand(100000, 999999);
    
            // Store OTP in session for verification later
            $_SESSION['seller_otp'] = $otp;
    
            // You can also save it to user meta if necessary
            // update_user_meta($user_id, 'seller_otp', $otp);
    
            // Send OTP via Twilio
            $sid = defined('TWILIO_SID') ? TWILIO_SID : '';
            $token = defined('TWILIO_AUTH_TOKEN') ? TWILIO_AUTH_TOKEN : '';
            $twilio_number = defined('TWILIO_PHONE_NUMBER') ? TWILIO_PHONE_NUMBER : '';
    
            $client = new \Twilio\Rest\Client($sid, $token);
            
            try {
                $client->messages->create(
                    $country_code . $phone,
                    [
                        'from' => $twilio_number,
                        'body' => "$otp is your Brags OTP code. Do not share it with anyone."
                    ]
                );
            } catch (Exception $e) {
                error_log('Twilio error: ' . $e->getMessage());
            }
            
            // Add an error to redirect user back to the registration page for OTP verification
            $errors->add('otp_verification', 'Please verify your OTP before registration.');
        }
    }
    }
    
?>