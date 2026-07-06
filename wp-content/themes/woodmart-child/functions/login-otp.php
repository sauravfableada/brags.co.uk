<?php


// Hook to handle OTP send after seller login
add_action('wp_login', 'send_otp_to_seller_after_login', 10, 2);


function send_otp_to_seller_after_login($user_login, $user) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $_SESSION['is_otp_verified'] = false;

    $user_id = $user->ID;
    $phone = '';
    $country_code = '+44'; // Default country code fallback

    // Detect if user is Seller or Staff
    $is_seller = function_exists('dokan_is_user_seller') && dokan_is_user_seller($user_id);
    $is_staff  = get_user_meta($user_id, '_vendor_id', true);

    if ($is_staff) {
        // Staff - get phone & country code from user meta
        $phone = trim(get_user_meta($user_id, '_staff_phone', true));
        $country_code = trim(get_user_meta($user_id, 'country_code', true)) ?: '+44';
    } elseif ($is_seller) {
        
        // Seller - get phone & country from store profile
        $profile_info = dokan_get_store_info($user_id);
        $phone = isset($profile_info['phone']) ? trim($profile_info['phone']) : '';
        $country_name = isset($profile_info['address']['country']) ? trim($profile_info['address']['country']) : 'GB';

        $countries = get_phone_to_country_mapping();
        if (!empty($country_name) && is_array($countries)) {
            $lookup = array_search($country_name, $countries);
            if ($lookup !== false) {
                $country_code = $lookup;
            }
        }
    } else {
        // Not seller or staff - do not send OTP
        return;
    }

    // Ensure phone is formatted correctly
    if (!preg_match('/^\+/', $phone)) {
        $phone = $country_code . $phone;
    }

    // Validate
    if (empty($phone)) {
        $_SESSION['otp_error'] = 'Missing phone number for OTP.';
        wp_redirect(dokan_get_page_url('myaccount', 'woocommerce'));
        exit;
    }

    // Generate and store OTP
    $otp = rand(100000, 999999);
    update_user_meta($user_id, 'seller_otp', $otp);
    update_user_meta($user_id, 'otp_sent_time', time());

    // Send via Twilio
    try {
        $sid = defined('TWILIO_SID') ? TWILIO_SID : '';
        $token = defined('TWILIO_AUTH_TOKEN') ? TWILIO_AUTH_TOKEN : '';
        $twilio_number = defined('TWILIO_PHONE_NUMBER') ? TWILIO_PHONE_NUMBER : '';

        $client = new \Twilio\Rest\Client($sid, $token);
        $client->messages->create(
            $phone,
            [
                'from' => $twilio_number,
                'body' => "$otp is your Brags OTP code. Do not share it with anyone."
            ]
        );
    } catch (Exception $e) {
        error_log('Twilio error: ' . $e->getMessage());
        $_SESSION['otp_error'] = 'Failed to send OTP: ' . $e->getMessage();
        wp_redirect(dokan_get_page_url('myaccount', 'woocommerce'));
        exit;
    }

    // Redirect to verification page
    wp_redirect(site_url('/seller-otp-verification/'));
    exit;
}


// Handle OTP resend
add_action('wp_ajax_resend_otp', 'resend_otp');
add_action('wp_ajax_nopriv_resend_otp', 'resend_otp'); // If the user is not logged in

function resend_otp() {
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'You must be logged in to request a resend.']);
    }
    $method = sanitize_text_field($_POST['method']);

    $user_id = get_current_user_id();

    $is_seller = function_exists('dokan_is_user_seller') && dokan_is_user_seller($user_id);
    $is_staff = get_user_meta($user_id, '_vendor_id', true);

     // Logic to resend OTP based on the method (sms or email)
     if ($method === 'sms') {
        $phone = '';
        $country_code = '+44';

        if ($is_staff) {
            $phone = trim(get_user_meta($user_id, '_staff_phone', true));
            $country_code = trim(get_user_meta($user_id, 'country_code', true)) ?: '+44';
        }elseif($is_seller){

            $profile_info = dokan_get_store_info($user_id);
            $phone = isset($profile_info['phone']) ? trim($profile_info['phone']) : '';
            $country_name = isset($profile_info['address']['country']) ? trim($profile_info['address']['country']) : 'GB';

            $countries = get_phone_to_country_mapping();
            if (!empty($country_name) && is_array($countries)) {
                $lookup = array_search($country_name, $countries);
                if ($lookup !== false) {
                    $country_code = $lookup;
                }
            }
            
        }

            // $profile_info = dokan_get_store_info($user_id);
            // $phone = isset($profile_info['phone']) ? trim($profile_info['phone']) : '';
            // $country_name = isset($profile_info['country']) ? trim($profile_info['country']) : 'GB';

            // $countries = get_phone_to_country_mapping();
            // $country_code = '+44'; // Default to UK code
            // if ($country_name != '') {
            //     $country_code = array_search($country_name, $countries);
            // }

            // if (!preg_match('/^\+/', $phone)) {
            //     $phone = $country_code . $phone;
            // }

            if (empty($phone)) {
                wp_send_json_error(['message' => 'Missing phone number for OTP.']);
            }

            if (!preg_match('/^\+/', $phone)) {
                $phone = $country_code . $phone;
            }

          

            // Check if OTP was sent within the last 5 minutes
            $last_sent_time = get_user_meta($user_id, 'otp_sent_time', true);
            $time_difference = time() - $last_sent_time;

            if ($time_difference < 300) {
                // This condition will ensure that the error message is passed correctly
                wp_send_json_error(['message' => 'You can only resend OTP after 5 minutes. Please wait a bit and try again.']);
            }

            // Resend OTP
            $otp = rand(100000, 999999);
            update_user_meta($user_id, 'seller_otp', $otp);
            update_user_meta($user_id, 'otp_sent_time', time());

            try {
                $sid = defined('TWILIO_SID') ? TWILIO_SID : '';
                $token = defined('TWILIO_AUTH_TOKEN') ? TWILIO_AUTH_TOKEN : '';
                $twilio_number = defined('TWILIO_PHONE_NUMBER') ? TWILIO_PHONE_NUMBER : '';

                $client = new \Twilio\Rest\Client($sid, $token);
                $client->messages->create(
                    $phone,
                    [
                        'from' => $twilio_number,
                        'body' => "$otp is your Brags OTP code. Do not share it with anyone."
                    ]
                );
            } catch (Exception $e) {
                error_log('Twilio error: ' . $e->getMessage());
                wp_send_json_error(['message' => 'Failed to send OTP: ' . $e->getMessage()]);
            }

        }elseif ($method === 'email') {
            // Fetch user email dynamically (no hardcoding)
            $email = get_userdata($user_id)->user_email;

            if ($email) {
                //$email="kirtan.fableadtechnolabs@gmail.com";
                // Generate a new OTP
                $otp = rand(100000, 999999);
                update_user_meta($user_id, 'seller_otp', $otp);
                update_user_meta($user_id, 'otp_sent_time', time());
        
                // Email subject and body content
                $subject = "Your OTP Code";
                $message = "
                <p>Hello,</p>
                <p><strong>$otp</strong> is your Brags OTP code. Do not share it with anyone.</p>
                <p>Best regards,<br>Brags!</p>
                ";
        
                // Set the email headers (set to HTML email)
                $headers = array('Content-Type: text/html; charset=UTF-8');
        
                // Send the email using wp_mail
                $mail_sent = wp_mail($email, $subject, $message, $headers);
                
        
                // Handle the email sending status
                if (!$mail_sent) {
                    error_log('Failed to send OTP email to ' . $email);
                    wp_send_json_error(['message' => 'Failed to send OTP email to ']);
                }

                if(isset($_GET['dev']) && $_GET['dev']=='admin'){
                     wp_send_json_success(['message' => 'OTP sent to your email address. ','otp'=>$otp]);
                }
        
                // Success response
                wp_send_json_success(['message' => 'OTP sent to your email address. ']);
            } else {
                // Handle case where email is not found for the user
                wp_send_json_error(['message' => 'Email address not found for the user.']);
            }
        }

    wp_send_json_success(['message' => 'OTP has been resent successfully.']);
}






add_shortcode('seller_otp_verification', function () {
    if (!is_user_logged_in()) {
        return 'Please log in first.';
    }

    ob_start();
    ?>
    <div style="max-width: 400px; margin: 50px auto; text-align: center;">
        <h3>OTP Verification</h3>
        <p>Enter the OTP sent to your phone.</p>
        <input type="text" id="otp-input" placeholder="Enter OTP" style="padding: 10px; width: 100%;"/>
        <button id="verify-otp-btn" style="margin-top: 15px; padding: 10px 20px;">Verify OTP</button>
        <!-- <button id="resend-otp-btn" style="margin-top: 15px; padding: 10px 20px;">Resend OTP</button> -->
        <p id="otp-message"></p>

        <p id="resendMessage" class="resend-message">
            Didn’t receive an OTP code? 
            <a href="javascript:void(0)" method="sms" class="resend-otp-btn">Resend to your phone</a> or 
            <a href="javascript:void(0)" method="email" class="resend-otp-btn">Send OTP to your email address</a>.
        </p>

    </div>

    <script>
        document.getElementById('verify-otp-btn').addEventListener('click', function () {
            var otp = document.getElementById('otp-input').value;
            var msg = document.getElementById('otp-message');

            msg.innerHTML = 'Verifying...';

            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=verify_seller_otp&otp=' + otp
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    msg.style.color = 'green';
                    msg.innerHTML = 'OTP Verified. Redirecting...';
                    setTimeout(() => window.location.href = '/dashboard/', 1500);
                } else {
                    msg.style.color = 'red';
                    msg.innerHTML = data.data;
                }
            });
        });

        // Select all the resend OTP links
        const resendOtpLinks = document.querySelectorAll('.resend-otp-btn');

        // Loop through each link and add an event listener
        resendOtpLinks.forEach(link => {
            link.addEventListener('click', function () {
                var method = this.getAttribute('method'); // Get the method (either "sms" or "email")
                var msg = document.getElementById('otp-message');

                msg.innerHTML = 'Resending OTP...';

                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=resend_otp&method=' + method // Pass the method parameter (sms or email)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        msg.style.color = 'green';
                        msg.innerHTML = data.data.message; // Show the success message
                    } else {
                        msg.style.color = 'red';
                        msg.innerHTML = data.data.message; // Show the error message
                    }
                })
                .catch(err => {
                    msg.style.color = 'red';
                    msg.innerHTML = 'There was an error processing your request. Please try again.';
                });
            });
        });

    </script>

    <?php
    return ob_get_clean();
});




add_action('wp_ajax_verify_seller_otp', 'verify_seller_otp_ajax');

function verify_seller_otp_ajax() {
    if (!is_user_logged_in()) {
        wp_send_json_error('Not logged in');
    }

    $user_id = get_current_user_id();
    $expected_otp = get_user_meta($user_id, 'seller_otp', true);
    $entered_otp = isset($_POST['otp']) ? sanitize_text_field($_POST['otp']) : '';

    if ($entered_otp === $expected_otp) {
        //update_user_meta($user_id, 'is_otp_verified', true);
        $_SESSION['is_otp_verified'] = true;
        wp_send_json_success('OTP Verified');
    } else {
        wp_send_json_error('Invalid OTP');
    }
}


add_action('template_redirect', function () {
    if (!is_user_logged_in()) return;

    if (!session_id()) {
        session_start();
    }

    $user_id = get_current_user_id();
    $user = get_userdata( $user_id );
    $user_roles = $user->roles; // Get user roles
    // Bypass for admins
    if (user_can($user_id, 'manage_options') || !in_array( 'seller', $user_roles )) {
        return;
    }

    

    if (!function_exists('dokan_is_user_seller') || !dokan_is_user_seller($user_id)) {
        return; // Only affect sellers
    }

    $is_verified = isset($_SESSION['is_otp_verified']) ? $_SESSION['is_otp_verified'] : false;

    $allowed_pages = [
        '/seller-otp-verification/',
        '/logout',
        '/wp-login.php',
        '/my-account/customer-logout/'
    ];

    $current_path = $_SERVER['REQUEST_URI'];
    foreach ($allowed_pages as $page) {
        if (strpos($current_path, $page) !== false) return;
    }

    if (!$is_verified) {
        wp_redirect(site_url('/seller-otp-verification/'));
        exit;
    }
});
