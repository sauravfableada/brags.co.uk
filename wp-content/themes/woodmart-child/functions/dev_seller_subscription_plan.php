<?php


// function my_dokan_seller_has_active_plan( $user_id = 0 ) {
//     if ( ! $user_id ) {
//         $user_id = get_current_user_id();
//     }

//     if ( ! function_exists( 'dokan_is_user_seller' ) || ! dokan_is_user_seller( $user_id ) ) {
//         return false;
//     }

//     $pack_id = get_user_meta( $user_id, 'product_package_id', true );

//     if ( $pack_id && get_post_status( $pack_id ) === 'publish' ) {
//         return true;
//     }

//     return false;
// }


// add_filter( 'dokan_show_export_import_button', 'my_hide_export_import_for_no_plan' );

// function my_hide_export_import_for_no_plan( $show ) {
//     if ( ! my_dokan_seller_has_active_plan() ) {
//         return false;
//     }

//     return $show;
// }

// add_filter( 'dokan_get_dashboard_nav', 'my_hide_tools_page_for_no_plan' );

// function my_hide_tools_page_for_no_plan( $urls ) {
//     if ( ! my_dokan_seller_has_active_plan() ) {
//         unset( $urls['tools'] );
//     }

//     return $urls;
// }

// add_filter( 'dokan_get_dashboard_nav', 'custom_dokan_dashboard_menu_by_plan', 20 );

// function custom_dokan_dashboard_menu_by_plan( $urls ) {
//     $user_id = get_current_user_id();
//     $pack_id = get_user_meta( $user_id, 'product_package_id', true );

//     if ( ! $pack_id || get_post_status( $pack_id ) !== 'publish' ) {
//         return $urls;
//     }


//     $pack_title = get_the_title( $pack_id );

//     if ( $pack_title !== 'Seller Plan (Bragsy)' && $pack_title !== 'Seller Plan (Pro)' ) {
//         unset( $urls['tools']['submenu']['csv-import'] );
//         unset( $urls['tools']['submenu']['csv-export'] );

//         if ( empty( $urls['tools']['submenu'] ) ) {
//             unset( $urls['tools'] );
//         }
//     }

//      return $urls;
// }

// add_action( 'dokan_after_add_product_btn', 'my_show_seller_plan_info' );

// function my_show_seller_plan_info() {
//     $user_id = get_current_user_id();
//     $pack_id = get_user_meta( $user_id, 'product_package_id', true );

//     // Check if the user has an active subscription
//     if ( ! $pack_id || get_post_status( $pack_id ) !== 'publish' ) {
//         echo '<div class="dokan-alert dokan-alert-warning">';
//         echo 'You do not have an active subscription. <a href="/dashboard/subscription/">Upgrade Now</a>';
//         echo '</div>';
//         return;
//     }

//     $pack_title = get_the_title( $pack_id );

//     // echo '<span class="dokan-add-product-link">';
//     // echo '<a href="/dashboard/products/?product_id=0&action=edit" class="dokan-btn dokan-btn-theme">';
//     // echo '<i class="fas fa-briefcase">&nbsp;</i>Add new product';
//     // echo '</a>';

//     // Show Import/Export only for specific plans
//     if ( in_array( $pack_title, [ 'Seller Plan (Bragsy)', 'Seller Plan (Pro)' ] ) ) {
//         echo '<a href="/dashboard/tools/csv-import/" class="dokan-btn dokan-btn-theme" style="margin-right: 2px;">Import</a>';
//         echo '<a href="/dashboard/tools/csv-export/" class="dokan-btn dokan-btn-theme">Export</a>';
//     }

//     // echo '</span>';
// }



// add_action( 'body_class', 'my_add_dokan_dashboard_body_class' );

// function my_add_dokan_dashboard_body_class( $classes ) {
//     if ( function_exists( 'dokan_is_seller_dashboard' ) && dokan_is_seller_dashboard() ) {
//         $user_id = get_current_user_id();

//         if ( dokan_is_user_seller( $user_id ) ) {
//             $classes[] = 'dokan-seller-dashboard';

//             $pack_id = get_user_meta( $user_id, 'product_package_id', true );
//             if ( $pack_id && get_post_status( $pack_id ) === 'publish' ) {
//                 $package_name = get_the_title( $pack_id );
//                 $classes[] = 'active-plan-' . sanitize_title( $package_name );
//             } else {
//                 $classes[] = 'no-active-plan';
//             }
//         }
//     }

//     return $classes;
// }

// ------------------------------------ new code 17-4-25 -----------------------------------------

// add_action( 'init', 'dokan_auto_disable_vendor_products_on_vacation' );

// function dokan_auto_disable_vendor_products_on_vacation() {
//     if ( is_admin() ) return;

//     $vendors = get_users( array(
//         'role__in' => array( 'seller', 'vendor' ),
//         'meta_key' => 'dokan_enable_vacation',
//         'meta_value' => 'yes',
//     ) );

//     foreach ( $vendors as $vendor ) {
//         $store_id = $vendor->ID;
//         $vacation_settings = get_user_meta( $store_id, 'dokan_profile_settings', true );

//         if ( empty( $vacation_settings['enable_vacation'] ) || $vacation_settings['enable_vacation'] !== 'yes' ) {
//             continue;
//         }

//         $from = !empty( $vacation_settings['vacation_start_date'] ) ? strtotime( $vacation_settings['vacation_start_date'] ) : false;
//         $to   = !empty( $vacation_settings['vacation_end_date'] )   ? strtotime( $vacation_settings['vacation_end_date'] ) : false;

//         $now = current_time( 'timestamp' );

//         // Get vendor's products
//         $args = array(
//             'post_type'      => 'product',
//             'posts_per_page' => -1,
//             'author'         => $store_id,
//             'post_status'    => array( 'publish', 'draft' )
//         );

//         $products = get_posts( $args );

//         foreach ( $products as $product ) {
//             if ( $from && $to && $now >= $from && $now <= $to ) {
//                 // Within vacation period — set product to draft
//                 if ( $product->post_status === 'publish' ) {
//                     wp_update_post( array(
//                         'ID'          => $product->ID,
//                         'post_status' => 'draft'
//                     ) );
//                 }
//             } else {
//                 // Outside vacation — publish if it was in draft
//                 if ( $product->post_status === 'draft' ) {
//                     wp_update_post( array(
//                         'ID'          => $product->ID,
//                         'post_status' => 'publish'
//                     ) );
//                 }
//             }
//         }
//     }
// }



// add_filter( 'dokan_profile_completion_values', 'custom_dokan_profile_progress_values' );

function custom_dokan_profile_progress_values( $values ) {
    $values['phone_val'] = 15;
    $values['store_name_val'] = 15;

    // Set all social media values to 0
    if ( isset( $values['social_val'] ) && is_array( $values['social_val'] ) ) {
        $values['social_val'] = [
            'fb'       => 0,
            'twitter'  => 0,
            'youtube'  => 0,
            'linkedin' => 0,
        ];
    }

    return $values;
}

