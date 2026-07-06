<?php

add_action('init', function () {
    if (isset($_POST['dokan_migration']) && isset($_POST['dokan_nonce']) && wp_verify_nonce($_POST['dokan_nonce'], 'account_migration')) {

        
        $user_id = get_current_user_id();

        if ($user_id > 0) {
            update_user_meta($user_id, 'pw_user_status', 'pending');

            $user = get_user_by('id', $user_id);
            if ($user && !in_array('seller', $user->roles)) {
                if (in_array('customer', $user->roles)) {
                    $user->remove_role('customer');
                }
                $user->add_role('seller');
            }

            // Update user meta fields for First Name, Last Name, and Shop Name
            if (isset($_POST['fname'])) {
                update_user_meta($user_id, 'first_name', sanitize_text_field($_POST['fname']));
            }
            if (isset($_POST['lname'])) {
                update_user_meta($user_id, 'last_name', sanitize_text_field($_POST['lname']));
            }
            if (isset($_POST['shopname'])) {
                update_user_meta($user_id, 'dokan_store_name', sanitize_text_field($_POST['shopname']));
            }
            if (isset($_POST['dokan_vat_number'])) {
                update_user_meta($user_id, 'dokan_vat_number', sanitize_text_field($_POST['dokan_vat_number']));
            }
            if (isset($_POST['dokan_company_name'])) {
                update_user_meta($user_id, 'dokan_company_name', sanitize_text_field($_POST['dokan_company_name']));
            }
            if (isset($_POST['phone'])) {
                update_user_meta($user_id, 'dokan_store_phone', sanitize_text_field($_POST['phone']));
            }
            if (isset($_POST['phone'])) {
                update_user_meta($user_id, 'phone', sanitize_text_field($_POST['phone']));
            }
            if (isset($_POST['dokan_company_id_number'])) {
                update_user_meta($user_id, 'dokan_company_id_number', sanitize_text_field($_POST['dokan_company_id_number']));
            }

            // $vendor = dokan()->vendor->get( $user_id );
            // $vendor->set_phone(9687069311);
            // $vendor->save();

            

            $country_code = 'GB';
            if (isset($_POST['country_code'])) {
                $country_code = sanitize_text_field($_POST['country_code']);
                add_user_meta($user_id, 'country_code', $country_code);
            }

            if ( isset($_POST['shopname']) ) {
                $profile_settings['store_name'] = sanitize_text_field($_POST['shopname']);
            }
            
            if ( isset($_POST['phone']) ) {
                $profile_settings['phone'] = sanitize_text_field($_POST['phone']);
            }
            

            if ( $country_code !='') {
                $countries = get_phone_to_country_mapping();
                $country_name = isset($countries[$country_code]) ? $countries[$country_code] : $country_code;
               
        
                
                // Prepare new address info to update
                // $new_address = [
                //     'address' => [
                //         'street_1' => isset($_POST['address_street_1']) ? sanitize_text_field($_POST['address_street_1']) : '',
                //         'street_2' => isset($_POST['address_street_2']) ? sanitize_text_field($_POST['address_street_2']) : '',
                //         'city'     => isset($_POST['address_city']) ? sanitize_text_field($_POST['address_city']) : '',
                //         'zip'      => isset($_POST['address_zip']) ? sanitize_text_field($_POST['address_zip']) : '',
                //         'country'  => $country_name,
                //         'state'    => isset($_POST['address_state']) ? sanitize_text_field($_POST['address_state']) : '',
                //         'phone'=>isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '',
                //     ]
                // ];
                
                // update_user_meta( $user_id, 'dokan_profile_settings', $new_address );

                // Now update address part
                $profile_settings['address'] = [
                    'street_1' => isset($_POST['address_street_1']) ? sanitize_text_field($_POST['address_street_1']) : '',
                    'street_2' => isset($_POST['address_street_2']) ? sanitize_text_field($_POST['address_street_2']) : '',
                    'city'     => isset($_POST['address_city']) ? sanitize_text_field($_POST['address_city']) : '',
                    'zip'      => isset($_POST['address_zip']) ? sanitize_text_field($_POST['address_zip']) : '',
                    'country'  => $country_name,
                    'state'    => isset($_POST['address_state']) ? sanitize_text_field($_POST['address_state']) : '',
                ];

                

            }
            // Save final merged settings
            update_user_meta( $user_id, 'dokan_profile_settings', $profile_settings );

            if (isset($_POST['tc_agree'])) {
                update_user_meta($user_id, '_tc_agree', isset($_POST['tc_agree']) ? 'yes' : 'no');
            }
            if (isset($_POST['custom_filed'])) {
                update_user_meta($user_id, '_uk_only_shipping', isset($_POST['custom_filed']) ? 'yes' : 'no');
            }
            if (isset($_POST['brand_ownership_tc_agree'])) {
                update_user_meta($user_id, '_brand_ownership_tc_agree', isset($_POST['brand_ownership_tc_agree']) ? 'yes' : 'no');
            }
            



            // echo "<pre>";
          
            // print_r(get_user_meta($user_id, 'dokan_profile_settings',true));
            // echo "</pre>";
            // exit();

            wp_logout();

            wp_redirect(home_url('/my-account'));
            exit;
        }
    }
});
