<?php
// =============================================
// DISABLE DEFAULT EMAILS (CUSTOMER ONLY)
// =============================================

// Remove WooCommerce customer emails only during customer registration
add_action('user_register', 'brags_remove_default_emails', 1, 1);

function brags_remove_default_emails($user_id)
{
    $user = get_userdata($user_id);

    // Only remove actions if user is a customer (not seller or admin)
    if (in_array('customer', $user->roles) && !in_array('seller', $user->roles) && !in_array('administrator', $user->roles)) {
        remove_all_actions('woocommerce_created_customer');
    }
}

// =============================================
// CUSTOM EMAIL FOR CUSTOMERS ONLY
// =============================================
add_action('user_register', 'brags_handle_user_registration_emails', 999999, 1);

function brags_handle_user_registration_emails($user_id)
{
    $user = get_userdata($user_id);

    // Send custom email only if user is a CUSTOMER (not seller or admin)
    if (in_array('customer', $user->roles) && !in_array('seller', $user->roles) && !in_array('administrator', $user->roles)) {
        $email = $user->user_email;
        $first_name = $user->first_name ?: $user->display_name;

        // Generate unique verification token
        $token = wp_generate_password(32, false);
        update_user_meta($user_id, 'email_verification_token', $token);
        update_user_meta($user_id, 'account_status', 'pending');
        // Create verification link
        $verification_url = add_query_arg([
            'user_id' => $user_id,
            'token' => $token
        ], home_url('/verify-email/'));

        $subject = 'Welcome to Brags.co.uk - Please Confirm Your Email';
        $headers = ['Content-Type: text/html; charset=UTF-8'];

        $message = '<html>
        <body style="font-family: Arial, sans-serif; line-height: 1.6;">
            <p>Hi ' . esc_html($first_name) . ',</p>
            <p>Many thanks for choosing to Buy on Brags.co.uk and manage your orders easily with a Brags Customer Account!</p>
            <p> <a href="'.esc_url($verification_url).'">Please click here to confirm your email address.</a></p>
            <p>Many thanks,<br>Brags & Partners Ltd</p>
        </body>
        </html>';

        wp_mail($email, $subject, $message, $headers);
    }
}


/**
 * Handle email verification
 */
add_action('init', 'brags_handle_email_verification');
function brags_handle_email_verification() {
    if (isset($_GET['token'], $_GET['user_id']) && is_numeric($_GET['user_id'])) {
        $user_id = intval($_GET['user_id']);
        $token = sanitize_text_field($_GET['token']);

        // Verify token
        $stored_token = get_user_meta($user_id, 'email_verification_token', true);
        
        if ($token === $stored_token) {
            // Mark as verified
            update_user_meta($user_id, 'account_status', 'approved');
            delete_user_meta($user_id, 'email_verification_token');
            
            // Log the user in
            wp_set_auth_cookie($user_id);
            
            // Redirect to account page
            wp_redirect(home_url('/my-account/'));
            exit;
        }
    }
}

/**
 * Prevent login for unverified users
 */
add_action('wp_authenticate_user', 'brags_check_user_verification', 10, 2);
// function brags_check_user_verification($user, $password) {
//     $status = get_user_meta($user->ID, 'account_status', true);
    
//     if ($status !== 'approved') {
//         return new WP_Error(
//             'account_not_verified',
//             __('Your account is not yet verified. Please check your email for the verification link.', 'brags')
//         );
//     }
    
//     return $user;
// }
function brags_check_user_verification($user, $password) {
    // Only check verification for customers
    if (in_array('customer', (array) $user->roles)) {
        $status = get_user_meta($user->ID, 'account_status', true);

        $resend_url = add_query_arg([
            'action' => 'resend_verification',
            'user_id' => $user->ID
        ], home_url('/my-account/?action=login'));
            
        if ($status !== 'approved') {
            // return new WP_Error(
            //     'account_not_verified',
            //     __('Your account is not yet verified. Please check your email for the verification link.', 'brags')
            // );

            return new WP_Error(
                'account_not_verified',
                sprintf(
                    __('Your account is not yet verified. Please check your email for the verification link. <a href="%s" class="resend-verification">Not received the email? Click here to resend the verification link.</a>', 'brags'),
                    esc_url($resend_url)
                )
            );
        }
    }
    
    return $user;
}



add_action('template_redirect', 'handle_resend_verification'); // Changed to template_redirect
function handle_resend_verification() {
    // Only process on my-account page with login action
    if (is_account_page() && isset($_GET['action']) && $_GET['action'] === 'resend_verification' && isset($_GET['user_id'])) {
        $user_id = intval($_GET['user_id']);
        
        // Verify user exists and is pending verification
        $user = get_userdata($user_id);
        if ($user && get_user_meta($user_id, 'account_status', true) === 'pending') {
            brags_handle_user_registration_emails($user_id, true);
            
            // Add success notice that will show on the my-account page
            wc_add_notice(__('Verification email resent successfully! Please check your inbox.', 'brags'), 'success');
            
            // Redirect back to login to prevent resubmission
            wp_redirect(home_url('/my-account/?action=login'));
            exit;
        }
    }
}