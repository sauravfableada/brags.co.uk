<div id="seller-approval">
    <h2>Authorise Sellers</h2>
    <p>Manage sellers who want to sell your brand.</p>

    <button id="toggle-add-seller" class="button button-primary">Add Seller</button>

  

    <!-- Popup Modal -->
    <div id="add-seller-popup" class="popup-overlay">
        <div class="popup-content">
            <span class="close-popup">&times;</span>
            <h3>Add a New Seller</h3>
        

            <label for="seller_selection">Select Seller:</label>
            <select id="seller_selection" name="seller_selection">
                <option value="">Select a Seller</option>
                <?php
                // Fetch all users with the 'seller' role
                $args = array(
                    'role'    => 'seller',
                    //'orderby' => 'user_nicename',
                    'order'   => 'ASC'
                );

                $sellers = get_users($args);

                if (!empty($sellers)) {
                    foreach ($sellers as $seller) {
                        $dokan_store_name = get_user_meta($seller->ID,'dokan_store_name',true);
                        $dokan_company_name = get_user_meta($seller->ID,'dokan_company_name',true);
                        $sc_name = ($dokan_company_name!='')?($dokan_store_name.' / '.$dokan_company_name):($dokan_store_name);
                        
                        //echo '<option value="' . esc_attr($seller->ID) . '">' . esc_html($seller->display_name . ' (' . $seller->user_email . ')') . '</option>';
                       if($sc_name!=''){
                        echo '<option value="' . esc_attr($seller->ID) . '">' . esc_html($sc_name) . '</option>';
                       }
                        
                    }
                } else {
                    echo '<option value="">No sellers found</option>';
                }


                ?>
            </select>

            <label for="brand_selection">Select Brand:</label>
            <select id="brand_selection" name="brand_selection">
                <option value="">Select a Brand</option>
                <?php
                // Fetch brands created by the logged-in user
                $user_id = get_current_user_id();

                    $args = array(
                        'post_type'      => 'brand',
                        'post_status'    => 'publish',
                        'posts_per_page' => -1,
                        'author'         => $user_id
                    );

                    $brand_query = new WP_Query($args);

                    if ($brand_query->have_posts()) {
                        while ($brand_query->have_posts()) {
                            $brand_query->the_post(); // Only call this ONCE per iteration

                            $post_id = get_the_ID();
                            $brand_term_id = get_post_meta($post_id, '_brand_term_id', true);

                            echo '<option value="' . esc_attr($brand_term_id) . '">' . esc_html(get_the_title()) . '</option>';
                        }
                        wp_reset_postdata();
                    } else {
                        echo '<option value="">No brands found</option>';
                    }
                ?>
            </select>

            <button type="button" id="submit-add-seller" class="button button-secondary">Submit</button>
        </div>
    </div>


    <?php

        global $wpdb;
        $table_name = $wpdb->prefix . 'seller_requests';
        $current_user_id = get_current_user_id();

        // Get brand IDs owned by the current user
        $brand_post_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT ID FROM {$wpdb->prefix}posts
                 WHERE post_type = 'brand' AND post_author = %d AND post_status = 'publish'",
                $current_user_id
            )
        );

        $brand_ids = [];
        if (!empty($brand_post_ids)) {
            foreach ($brand_post_ids as $post_id) {
                 $brand_term_id = get_post_meta($post_id, '_brand_term_id', true);
                if (!empty($brand_term_id)) {
                    $brand_ids[] = $brand_term_id;
                }
            }
        }

        // Fetch seller requests
        if (empty($brand_ids)) {
            $requests = [];
        } else {
            // Convert brand IDs into a comma-separated list for SQL query
            $brand_ids_placeholder = implode(',', array_map('intval', $brand_ids));

            // Retrieve only seller requests for the current user's brands
            $requests = $wpdb->get_results(
                "SELECT r.id, r.seller_id, r.brand_id, r.status,
                        u.display_name AS seller_name, u.user_email AS seller_email,
                        b.post_title AS brand_name
                 FROM {$wpdb->prefix}seller_requests AS r
                 LEFT JOIN {$wpdb->prefix}users AS u ON r.seller_id = u.ID
                 LEFT JOIN {$wpdb->prefix}posts AS b ON r.brand_id = b.ID
                 WHERE r.brand_id IN ($brand_ids_placeholder)"
            );
        }

        if (!empty($requests)) {
            echo '<div class="table-responsive">';
            echo '<table class="table " border="1" cellpadding="10" cellspacing="0">';
            echo '<tr><th>Seller Name</th><th>Store IDs</th><th>Brand</th><th>Status</th><th>Action</th></tr>';
            //
            foreach ($requests as $request) {

                $term_id = $request->brand_id; // Replace with your actual term ID
                $taxonomy = 'product_brand'; // Replace with your taxonomy name
                $dokan_store_name = get_user_meta($request->seller_id,'dokan_store_name',true);

                $term = get_term($term_id, $taxonomy);
                $b_name = $term->name;
                if (!is_wp_error($term) && !empty($term)) {
                    $b_name =  esc_html($term->name);
                }

                echo '<tr>';
                echo '<td>' . esc_html($request->seller_name) . '</td>';
                echo '<td>' . esc_html($dokan_store_name) . '</td>';
                echo '<td>' . esc_html($b_name) . '</td>';
                echo '<td class="status-' . esc_attr($request->id) . '">' . esc_html(ucfirst($request->status)) . '</td>';
                echo '<td>';
                if($request->status=='approved'){
                    echo '<button class="reject-seller" data-id="' . esc_attr($request->id) . '" title="Reject">
                        <span class="dashicons dashicons-no"></span>
                    </button> ';
                }else if($request->status=='rejected'){
                    echo '<button class="approve-seller" data-id="' . esc_attr($request->id) . '" title="Approve">
                        <span class="dashicons dashicons-yes"></span>
                    </button> ';
                }else{
                    echo '<button class="approve-seller" data-id="' . esc_attr($request->id) . '" title="Approve">
                        <span class="dashicons dashicons-yes"></span>
                    </button> ';
                    echo '<button class="reject-seller" data-id="' . esc_attr($request->id) . '" title="Reject">
                        <span class="dashicons dashicons-no"></span>
                    </button> ';
                }



                echo '<button class="delete-seller" data-id="' . esc_attr($request->id) . '" title="Delete">
                        <span class="dashicons dashicons-trash"></span>
                    </button>';
                echo '</td>';
                echo '</tr>';
            }

            echo '</table>';
            echo '</div>';
        } else {
            echo '<p>No seller requests found.</p>';
        }


    ?>
</div>
