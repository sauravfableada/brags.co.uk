<style>
    .bragsy-membership-plans {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        justify-content: center;
    }

    .bragsy-plan {
        background: #ffffff;
        border-radius: 12px;
        padding: 20px;
        text-align: center;
        width: 300px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease-in-out;
    }

    .bragsy-plan:hover {
        transform: translateY(-5px);
    }

    .plan-title {
        font-size: 20px;
        font-weight: bold;
        color: #333;
        margin-bottom: 10px;
    }

    .plan-image img {
        width: 100%;
        height: auto;
        border-radius: 10px;
    }

    .plan-price {
        font-size: 22px;
        font-weight: bold;
        color: #0073aa;
        margin: 10px 0;
    }

    .plan-description {
        font-size: 14px;
        color: #666;
        margin-bottom: 15px;
    }

    .plan-button {
        display: inline-block;
        background: #0073aa;
        color: #fff;
        padding: 12px 20px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: bold;
        transition: background 0.3s ease;
    }

    .plan-button:hover {
        background: #005f8d;
    }

    .activated-button {
        background-color: green;
        color: white;
        padding: 12px 20px;
        border: none;
        border-radius: 8px;
        cursor: not-allowed;
        font-weight: bold;
    }
</style>

<h2 style="text-align: center; font-size: 28px; margin-bottom: 20px;">Bragsy Membership Plan</h2>

<?php
$user_id = get_current_user_id();



$subscriptions         = pms_get_member_subscriptions( array( 'user_id' => $user_id ) );
$subscription_statuses = pms_get_member_subscription_statuses();




//$subscriptions = get_active_pms_subscriptions();



// if (!empty($subscriptions)) {
//     echo '<ul style="text-align: center; font-size: 16px; list-style: none; padding: 0;">';
//     foreach ($subscriptions as $sub) {
//         if ( is_null( $sub ) )
// 		continue;

//         $subscription_plan = pms_get_subscription_plan( $sub->subscription_plan_id );
       

//         $plan_name = esc_html( ! empty( $sub->name ) ? $sub->name : '' );
          
//         $start_date = date('F j, Y', strtotime($sub->start_date));
//         $expiration_date = ($sub->expiration_date != '0000-00-00 00:00:00') ? date('F j, Y', strtotime($sub->expiration_date)) : 'Never';

//         echo "<li><strong>{$plan_name}</strong> (Active Since: {$start_date}, Expires: {$expiration_date})</li>";

        
//     }
//     echo '</ul>';
// } else {
//     echo '<p style="text-align: center; font-size: 18px; color: red;"> You do not have an active membership plan.</p>';
// }

if (!empty($subscriptions)) {
    echo '<ul style="text-align: center; font-size: 16px; list-style: none; padding: 0;">';
    foreach ($subscriptions as $sub) {
        if ( is_null( $sub ) )
            continue;

        $subscription_plan = pms_get_subscription_plan( $sub->subscription_plan_id );
        $plan_name = esc_html( ! empty( $subscription_plan->name ) ? $subscription_plan->name : '' );
        $start_date = date('F j, Y', strtotime($sub->start_date));
        $expiration_date = ($sub->expiration_date != '0000-00-00 00:00:00') ? date('F j, Y', strtotime($sub->expiration_date)) : 'Never';

        echo "<li><strong>{$plan_name}</strong> (Active Since: {$start_date}, Expires: {$expiration_date})";

        // Show cancel button only for active plans
        if ($sub->status === 'active') {
            echo '<form method="post" action="">';
            echo '<input type="hidden" name="pms-action" value="cancel_subscription">';
            echo '<input type="hidden" name="subscription_id" value="' . esc_attr($sub->id) . '">';
            echo '<input type="hidden" name="pmstkn" value="' . esc_attr(wp_create_nonce('pms_cancel_subscription')) . '">';
            echo '<button type="submit" style="margin-left: 10px; padding: 5px 10px; background-color: red; color: white; border: none; border-radius: 5px; cursor: pointer;">Cancel</button>';
            echo '</form>';
        }

        echo "</li>";
    }
    echo '</ul>';
} else {
    echo '<p style="text-align: center; font-size: 18px; color: red;"> You do not have an active membership plan.</p>';
}



$query = get_pms_all_subscription_products('customer');

if ($query->have_posts()) {
    echo '<div class="bragsy-membership-plans">';
    while ($query->have_posts()) {
        $query->the_post();
        global $product;
        $is_active = is_product_subscription_active(get_the_ID());
        
        echo '<div class="bragsy-plan">';
        echo '<h3 class="plan-title">' . get_the_title() . '</h3>';
        echo '<div class="plan-image">' . get_the_post_thumbnail(get_the_ID(), 'medium') . '</div>';
        echo '<div class="plan-price">' . wc_price($product->get_price()) . '</div>';
        echo '<p class="plan-description">' . get_the_excerpt() . '</p>';

        if ($is_active) {
            echo '<button class="activated-button"> Activated</button>';
        } else {
            echo '<a href="' . esc_url($product->add_to_cart_url()) . '" class="plan-button"> Subscribe Now</a>';
        }

        echo '</div>';
    }
    echo '</div>';
} else {
    echo '<p style="text-align: center; font-size: 18px; color: #666;">No products found for your Bragsy Membership.</p>';
}

wp_reset_postdata();
?>