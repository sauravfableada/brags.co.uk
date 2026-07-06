<?php
function remove_sorting_from_top_sellers($catalog_orderby) {
    if (is_page_template('woocommerce/top-sellers.php')) {
        return array(); // Remove sorting options
    }
    return $catalog_orderby;
}
add_filter('woocommerce_catalog_orderby', 'remove_sorting_from_top_sellers');




function display_price_filter_form() {
    // Get current min & max price from the query string
    $min_price = isset($_GET['min_price']) ? esc_attr($_GET['min_price']) : '10';
    $max_price = isset($_GET['max_price']) ? esc_attr($_GET['max_price']) : '800';

    ?>
    <style>
        /* Price Filter Slider Styling */
        .price_slider_wrapper {
            position: relative;
            padding: 10px;
        }

        .price_slider input[type="range"] {
            -webkit-appearance: none;
            appearance: none;
            width: 100%;
            height: 6px;
            background: #ddd;
            border-radius: 4px;
            outline: none;
            position: absolute;
            top: 10px;
            pointer-events: none; /* Prevent default interaction */
        }

        /* Style the track */
        .price_slider input[type="range"]::-webkit-slider-runnable-track {
            width: 100%;
            height: 6px;
            background: #0073aa; /* Primary color */
            border-radius: 4px;
        }

        /* Style the thumb (Handle) */
        .price_slider input[type="range"]::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 18px;
            height: 18px;
            background: #0073aa; /* Primary color */
            border: 2px solid #fff;
            border-radius: 50%;
            cursor: pointer;
            position: relative;
            margin-top: -6px;
            pointer-events: auto; /* Enable interaction */
        }

        /* Firefox support */
        .price_slider input[type="range"]::-moz-range-thumb {
            width: 18px;
            height: 18px;
            background: #0073aa;
            border: 2px solid #fff;
            border-radius: 50%;
            cursor: pointer;
            pointer-events: auto;
        }

        /* Price Label Styling */
        .price_label {
            margin-top: 20px;
            font-size: 14px;
            font-weight: bold;
            color: #333;
        }

        /* Button Styling */
        .price_slider_amount .button {
            margin-top: 30px;
            background: #0073aa;
            color: #fff;
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .price_slider_amount .button:hover {
            background: #005c8a;
        }

    </style>
    <div id="woocommerce_price_filter-5" class="wd-widget widget sidebar-widget woocommerce widget_price_filter">
        <h5 class="widget-title">Filter by price</h5>
        <form method="get">
            <div class="price_slider_wrapper">
                <div class="price_slider ui-slider ui-corner-all ui-slider-horizontal ui-widget ui-widget-content">
                    <div class="ui-slider-range ui-corner-all ui-widget-header"></div>
                    <span tabindex="0" class="ui-slider-handle ui-corner-all ui-state-default"></span>
                    <span tabindex="0" class="ui-slider-handle ui-corner-all ui-state-default"></span>
                </div>

                <div class="price_slider_amount" data-step="10">
                    <label class="screen-reader-text" for="min_price">Min price</label>
                    <input type="text" id="min_price" name="min_price" value="<?php echo $min_price; ?>" data-min="10" placeholder="Min price" style="display: none;">

                    <label class="screen-reader-text" for="max_price">Max price</label>
                    <input type="text" id="max_price" name="max_price" value="<?php echo $max_price; ?>" data-max="800" placeholder="Max price" style="display: none;">

                    <button type="submit" class="button">Filter</button>

                    <div class="price_label">
                        Price: <span class="from">£<?php echo $min_price; ?></span> — <span class="to">£<?php echo $max_price; ?></span>
                    </div>

                    <!-- Preserve other query parameters -->
                    <?php
                    foreach ($_GET as $key => $value) {
                        if (!in_array($key, ['min_price', 'max_price'])) {
                            echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
                        }
                    }
                    ?>

                    <div class="clear"></div>
                </div>
            </div>
        </form>
    </div>
    <?php
}



function top_sellers_category_sidebar() {

    $product_categories = get_top_selling_categories(false, 'parent');


    ?>
    <style>

main.wd-content-layout.content-layout-wrapper.container.wd-grid-g.wd-sidebar-hidden-md-sm.wd-sidebar-hidden-sm {
    display: flex !important;
    padding: 30px 0;
}

        .top-sellers-categories {
            list-style: none;
            padding: 0;
        }

        .parent-category {
            font-weight: bold;
            margin-bottom: 10px;
            position: relative;
            padding: 5px;
            border-bottom: 1px solid #ddd;
        }

        .child-categories {
            list-style: none;
            margin-left: 15px;
            padding-left: 10px;
            border-left: 2px solid #ddd;
            display: none; /* Ensure it's hidden by default */
        }

        .toggle-btn {
            cursor: pointer;
            font-size: 18px;
            margin-left: 10px;
            color: #0073aa;
            font-weight: bold;
            position: absolute;
            right: 10px;
        }

        .toggle-btn:hover {
            color: #ff6600;
        }
        .top-sellers-categories li.parent-category.Selling-Plans, .top-sellers-categories li.parent-category.Uncategorised,.top-sellers-categories li.parent-category.Bragsycustomerseller {
    display: none;
}



.custom .mobile-sidebar-toggle,
    #close-sidebar-btn {
        display: none;
    }


        @media only screen and (max-width: 767px) {
        .main-page-wrapper .filter-header.row .col-md-3 {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-direction: row-reverse;
            margin-bottom: 30px;
        }

        .custom .mobile-sidebar-toggle {
            display: block !important;
        }


        #top-categories-sidebar {
            /* position: fixed;
            top: 0;
            left: -300px; 
            width: 300px;
            height: 100%;
            background: #fff;
            z-index: 9999;
            transition: left 0.3s ease;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.2); */
        }

        #top-categories-sidebar.open {
             overflow-y: scroll;
        }
        #close-sidebar-btn {
            padding: 10px;
            text-align: right;
            cursor: pointer;
            font-size: 20px;
        }



        /* Optional: disable scroll when sidebar is open */

        body.no-scroll {
            overflow: hidden;
    position: fixed;
    width: 100%;
        }


        .custom .mobile-sidebar-toggle,
    #close-sidebar-btn {
        display: block;
    }



#top-categories-sidebar {

    /* position: fixed;

    top: 0;

    left: -300px;

    width: 300px;

    height: 100%;

    background: #fff;

    z-index: 9999;

    transition: left 0.3s ease;

    box-shadow: 2px 0 5px rgba(0, 0, 0, 0.2); */
        display: none;
        position: absolute;
        left: 0px;
        width: 100%;
        height: 100%;
        background: #fff;
        z-index: 9999;
        transition: left 0.3s ease;
        box-shadow: 2px 0 5px rgba(0, 0, 0, 0.2);

}



    #top-categories-sidebar.open {

        left: 0;


    }
    }






    </style>
    <?php

    if ($product_categories) {
        echo '<div class="sidebar-innerr-container">';
        //echo display_filtered_products();
        echo '<h5 class="widget-title">Top rated Categories</h5>';
        echo '<ul class="top-sellers-categories">';

        foreach ($product_categories as $parent_category) {
            // Get Child Categories
            $child_categories = get_top_selling_categories(false, 'child',$parent_category->term_id);

            // Parent Category Item
            echo '<li class="parent-category">';
            //echo '<a href="' . esc_url(get_term_link($parent_category)) . '">' . esc_html($parent_category->name) . '</a>';
            echo '<a href="' . esc_url(add_query_arg('category_id', $parent_category->term_id)) . '">' . esc_html($parent_category->name) . '</a>';

            // Add toggle button only if child categories exist
            if (!empty($child_categories)) {
                echo '<span class="toggle-btn">+</span>'; // Toggle button
                echo '<ul class="child-categories" style="display: none;">'; // Hidden by default
                foreach ($child_categories as $child_category) {
                    //echo '<li><a href="' . esc_url(get_term_link($child_category)) . '">' . esc_html($child_category->name) . '</a></li>';
                    echo '<li><a href="' . esc_url(add_query_arg('category_id', $child_category->term_id)) . '">' . esc_html($child_category->name) . '</a></li>';
                }
                echo '</ul>';
            }

            echo '</li>';
        }

        echo '</ul></div>';

        // JavaScript to Toggle Child Categories
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('.toggle-btn').forEach(button => {
                    button.addEventListener('click', function() {
                        let childList = this.nextElementSibling;
                        if (childList.style.display === 'none' || childList.style.display === '') {
                            childList.style.display = 'block';
                            this.textContent = '-'; // Change to minus
                        } else {
                            childList.style.display = 'none';
                            this.textContent = '+'; // Change back to plus
                        }
                    });
                });
            });
        </script>";
    }
}

// Hook into WooCommerce Sidebar
//add_action('woocommerce_sidebar', 'top_sellers_category_sidebar', 5);

// get top seling categories
function get_top_selling_categories($hide_empty = true, $type = 'all', $parent_id = 0) {
    $args = array(
        'taxonomy'   => 'product_cat',
        'hide_empty' => $hide_empty,
    );

    if ($type === 'parent') {
        $args['parent'] = 0; // Only get parent categories
    } elseif ($type === 'child') {
        $args['parent'] = $parent_id; // Get child categories of the given parent ID
    }

    $product_categories = get_terms($args);
    $category_sales = [];

    if (!empty($product_categories) && !is_wp_error($product_categories)) {
        foreach ($product_categories as $category) {
            $sales = get_category_sales($category->term_id);
            $category_sales[] = (object) array_merge((array) $category, ['sales' => $sales]);
        }

        // Sort categories by total sales (DESC)
        usort($category_sales, function ($a, $b) {
            return $b->sales - $a->sales;
        });

        return $category_sales; // Returns sorted categories similar to get_terms()
    }

    return [];
}


function get_category_sales($category_id) {
    $args = array(
        'post_type'      => 'product',
        'posts_per_page' => -1, // Get all products
        'fields'         => 'ids',
        'tax_query'      => array(
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => $category_id,
            ),
        ),
    );

    $products = get_posts($args);
    $total_sales = 0;

    foreach ($products as $product_id) {
        $sales = get_post_meta($product_id, 'total_sales', true);
        $total_sales += (int) $sales;
    }

    return $total_sales;
}


function display_filtered_products() {
    //if (isset($_GET['min_price']) && isset($_GET['max_price'])) {
        $min_price = floatval($_GET['min_price']??10);
        $max_price = floatval($_GET['max_price']??800);

        $filtered_products = get_filtered_products_by_price($min_price, $max_price);

        if (!empty($filtered_products)) {
            echo '<ul class="filtered-products">';
            foreach ($filtered_products as $product) {
                $product_obj = wc_get_product($product->ID);
                echo '<li>';
                echo '<a href="' . get_permalink($product->ID) . '">' . get_the_title($product->ID) . '</a>';
                echo '<span>Price: ' . wc_price($product_obj->get_price()) . '</span>';
                echo '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>No products found in this price range.</p>';
        }
    //}
}



function category_button_sidebar_mobile_open() {

        ?>
        <script type="text/javascript">
            // jQuery(document).ready(function($) {
            //     $('.page-id-17444 .sidebar-container.col-lg-3.col-md-3.col-12.order-last.order-md-first.sidebar-left.area-sidebar-shop.top-sellers-sidebar').attr('id', 'top-categories-sidebar');

            //     // Add the open sidebar button if it doesn't already exist
            //     if ($('#open-sidebar-btn').length === 0) {
            //         $('.page-id-17444 .site-content .filter-header.row .products-per-page').after('<div class="custom"><button style="cursor: pointer;color: #000;" id="open-sidebar-btn" class="mobile-sidebar-toggle">☰ Category</button></div>');
            //     }

            //     // Add the close sidebar button if it doesn't already exist
            //     if ($('#close-sidebar-btn').length === 0) {
            //         $('#top-categories-sidebar').prepend('<div id="close-sidebar-btn">×</div>');
            //     }

            //     // Open sidebar on click
            //     $(document).on('click', '#open-sidebar-btn', function(e) {
            //         e.preventDefault();
            //         $('#top-categories-sidebar').addClass('open');
            //         $('body').addClass('no-scroll');
            //     });

            //     // Close sidebar on click
            //     $(document).on('click', '#close-sidebar-btn', function() {
            //         $('#top-categories-sidebar').removeClass('open');
            //         $('body').removeClass('no-scroll');
            //     });

            //     $('.top-sellers-categories li').each(function() {
            //         var $link = $(this).find('a');
            //         var categoryName = $link.text().trim();

            //         if (categoryName !== '') {
            //             // Convert category name to a class-safe format (e.g., remove spaces)
            //             var categoryClass = categoryName.replace(/\s+/g, '-').replace(/[^a-zA-Z0-9\-]/g, '');

            //             // Add both classes
            //             $(this).addClass('parent-category').addClass(categoryClass);
            //         }
            //     });
            // });


            jQuery(document).ready(function($) {
    // Add ID to the sidebar container
    $('.page-id-17444 .sidebar-container.col-lg-3.col-md-3.col-12.order-last.order-md-first.sidebar-left.area-sidebar-shop.top-sellers-sidebar')
        .attr('id', 'top-categories-sidebar');

    // Add the open sidebar button if it doesn't already exist
    if ($('#open-sidebar-btn').length === 0) {
        $('.page-id-17444 .site-content .filter-header.row .products-per-page')
            .after('<div class="custom"><button style="cursor: pointer;color: #000;" id="open-sidebar-btn" class="mobile-sidebar-toggle">☰ Category</button></div>');
    }

    // Add the close sidebar button if it doesn't already exist
    if ($('#close-sidebar-btn').length === 0) {
        $('#top-categories-sidebar').prepend('<div id="close-sidebar-btn">×</div>');
    }

    // Open sidebar on click
    $(document).on('click', '#open-sidebar-btn', function(e) {
        e.preventDefault();
        $('#top-categories-sidebar').addClass('open');
        $('body').addClass('no-scroll');  // Disable body scroll
        $('#top-categories-sidebar').css('transform', 'translateX(0)');  // Optional: Add smooth transition effect
    });

    // Close sidebar on click
    $(document).on('click', '#close-sidebar-btn', function() {
        $('#top-categories-sidebar').removeClass('open');
        $('body').removeClass('no-scroll');  // Re-enable body scroll
        $('#top-categories-sidebar').css('transform', 'translateX(-100%)');  // Optional: Add smooth transition effect
    });

    // Add categories classes
    $('.top-sellers-categories li').each(function() {
        var $link = $(this).find('a');
        var categoryName = $link.text().trim();

        if (categoryName !== '') {
            // Convert category name to a class-safe format
            var categoryClass = categoryName.replace(/\s+/g, '-').replace(/[^a-zA-Z0-9\-]/g, '');

            // Add both classes
            $(this).addClass('parent-category').addClass(categoryClass);
        }
    });
});



        </script>
        <?php

}
add_action('wp_footer', 'category_button_sidebar_mobile_open');


?>