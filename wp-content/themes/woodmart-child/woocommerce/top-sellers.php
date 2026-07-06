<?php
/**
 * Template Name: Top Sellers
 * Description: A WooCommerce shop-style page displaying products sorted by sales.
 */

 defined( 'ABSPATH' ) || exit;

 get_header( 'shop' );
 
 /**
  * Hook: woocommerce_before_main_content.
  *
  * @hooked woocommerce_output_content_wrapper - 10 (outputs opening divs for the content)
  * @hooked woocommerce_breadcrumb - 20
  * @hooked WC_Structured_Data::generate_website_data() - 30
  */
 do_action( 'woocommerce_before_main_content' );
 
 /**
  * Hook: woocommerce_shop_loop_header.
  *
  * @since 8.6.0
  *
  * @hooked woocommerce_product_taxonomy_archive_header - 10
  */
 do_action( 'woocommerce_shop_loop_header' );
    $limit = 12;
    $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
    $category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : '';

    $args = array(
        'post_type'      => 'product',
        'posts_per_page' => $limit,
        'paged'          => $paged,
        'orderby'        => 'meta_value_num',
        'meta_key'       => 'total_sales',
        'order'          => 'DESC',
        'post_status'    => 'publish',
    );

    $meta_query = array(
        array(
            'key'     => 'total_sales',
            'value'   => 0,
            'type'    => 'NUMERIC',
            'compare' => '>',
        ),
    );

    // Optional: price filter
    if (isset($_GET['min_price']) || isset($_GET['max_price'])) {
        $min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
        $max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : PHP_INT_MAX;

        if ($max_price > $min_price) {
            $meta_query[] = array(
                'key'     => '_price',
                'value'   => array($min_price, $max_price),
                'type'    => 'NUMERIC',
                'compare' => 'BETWEEN',
            );
        }
    }

    $args['meta_query'] = $meta_query;

    // Filter by category
    if ($category_id) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => $category_id,
            ),
        );
    }

    $query = new WP_Query($args);
    $GLOBALS['wp_query'] = $query;
    wc_set_loop_prop('total', $query->found_posts);
    wc_set_loop_prop('is_paginated', true);
    wc_set_loop_prop('total_pages', $query->max_num_pages);

 
?>
<style>/* Hide on desktop by default */
.wd-show-sidebar-btn {
  display: none;
}
.c_cat{
    font-size: 16px;
    margin-bottom: 20px;
}
.wd-content-area.site-content.wd-grid-col {
    width: 100%;
}

/* Show only on screens smaller than 768px */
@media (max-width: 767px) {
  .wd-show-sidebar-btn {
    display: block; /* or flex, inline-block depending on layout */
  }
}</style>
<div class="wd-show-sidebar-btn wd-action-btn wd-style-text wd-burger-icon">
        <a class="c_cat" href="#" rel="nofollow">Choose a Category</a>
    </div>

<div class="container my-4 mb-4 category-div">
    <div class="row">
        <?php
        $selectedCats = [71,82,76,81,88,84];
        if (!empty($selectedCats)) {
            foreach ($selectedCats as $cat_id) {
                $cat = get_term_by('id', $cat_id, 'product_cat');
                if (!$cat || is_wp_error($cat)) continue;

                $cat_name = $cat->name;
                $cat_link = get_term_link($cat_id, 'product_cat');
                $thumbnail_id = get_term_meta($cat_id, 'thumbnail_id', true);
                $image_url = wp_get_attachment_url($thumbnail_id);
                $top_sellers_link = site_url('top-sellers/?category_id=' . $cat_id);
                ?>
                <div class="col-md-6 mb-4">
                    <div class="card h-100 text-center">
                        <?php if ($image_url): ?>
                            <a href="<?php echo esc_url($top_sellers_link); ?>">
                                <img src="<?php echo esc_url($image_url); ?>" class="card-img-top" alt="<?php echo esc_attr($cat_name); ?>">
                            </a>
                        <?php endif; ?>
                       
                    </div>
                </div>
                <?php
            }
        }
        ?>
    </div>
</div>




<?php
 if ( woocommerce_product_loop() ) {
 
     /**
      * Hook: woocommerce_before_shop_loop.
      *
      * @hooked woocommerce_output_all_notices - 10
      * @hooked woocommerce_result_count - 20
      * @hooked woocommerce_catalog_ordering - 30
      */
     //do_action( 'woocommerce_before_shop_loop' );
    echo "<div class='filter-header row'>";
    ?>
    
        <div class="col-md-9">
            <div class="wd-products-per-page" style="display:none">
                <span class="per-page-title">Show</span>

                <?php 
                $per_page_options = [9, 12, 18, 24]; // Define available per-page options
                $current_per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 12; // Default to 12

                foreach ($per_page_options as $option) {
                    $class = ($current_per_page == $option) ? 'per-page-variation current-variation' : 'per-page-variation';
                    $link = esc_url(add_query_arg('per_page', $option));
                    echo '/<a rel="nofollow noopener" href="' . $link . '" class="' . $class . '"><span>' . esc_html($option) . '</span></a>';
                    if ($option !== end($per_page_options)) {
                        echo '<span class="per-page-border"></span>';
                    }
                }
                ?>
            </div>

            <div class="wd-products-shop-view products-view-grid">
                <?php
                $grid_options = [2, 3, 4]; // Available grid layouts
                $current_grid = isset($_GET['per_row']) ? intval($_GET['per_row']) : 3; // Default to 3 columns

                foreach ($grid_options as $option) {
                    $class = ($current_grid == $option) ? 'shop-view current-variation per-row-' . $option : 'shop-view per-row-' . $option;
                    $link = esc_url(add_query_arg(['shop_view' => 'grid', 'per_row' => $option]));
                    echo '<a rel="nofollow noopener" href="' . $link . '" class="' . $class . '" aria-label="Grid view ' . esc_attr($option) . '"></a>';
                }
                ?>
            </div>
        </div>
    <?php
    echo ' <div class="col-md-3">';
    if (function_exists('woocommerce_catalog_ordering')) {
        add_filter('woocommerce_catalog_orderby', function ($options) {
            return [
                'popularity' => __('Sort by popularity', 'woocommerce')
            ];
        });
        echo '<div class="products-per-page">';
        woocommerce_catalog_ordering();
        echo '</div>';
    }
    echo "</div>";

    echo "</div>";
   
    echo '<div class="products-grid per-row-' . esc_attr($current_grid) . '">';
     woocommerce_product_loop_start();

     

     if ( $query->have_posts() ) {
         while ( $query->have_posts() ) {
            $query->the_post();
            // $product = wc_get_product(get_the_ID());
            //echo '<p style="color:red;">Sales: ' . get_post_meta(get_the_ID(), 'total_sales', true) . '</p>';
 
             /**
              * Hook: woocommerce_shop_loop.
              */
             do_action( 'woocommerce_shop_loop' );
             if (isset($_GET['view']) && $_GET['view'] === 'list') {
                wc_get_template_part('content', 'product-list'); // Use list view template
            } else {
                
                wc_get_template_part('content', 'product'); // Default grid view
            }
 
             //wc_get_template_part( 'content', 'product' );
         }
     }
 
     woocommerce_product_loop_end();
     echo '</div>';
     /**
      * Hook: woocommerce_after_shop_loop.
      *
      * @hooked woocommerce_pagination - 10
      */
     do_action( 'woocommerce_after_shop_loop' );
 } else {
     /**
      * Hook: woocommerce_no_products_found.
      *
      * @hooked wc_no_products_found - 10
      */
     do_action( 'woocommerce_no_products_found' );
 }
 
 /**
  * Hook: woocommerce_after_main_content.
  *
  * @hooked woocommerce_output_content_wrapper_end - 10 (outputs closing divs for the content)
  */
 do_action( 'woocommerce_after_main_content' );
 
 /**
  * Hook: woocommerce_sidebar.
  *
  * @hooked woocommerce_get_sidebar - 10
  */
 ?>
 
 <?php
  echo '<div class="sidebar-container col-lg-3 col-md-3 col-12 order-last order-md-first sidebar-left area-sidebar-shop top-sellers-sidebar">';
  display_price_filter_form();  
  top_sellers_category_sidebar();
  echo '</div>';
 //do_action( 'woocommerce_sidebar' );
 
 get_footer( 'shop' );
?>
<script>
document.addEventListener('DOMContentLoaded', function () {
  // Show sidebar logic
  const showSidebarBtn = document.querySelector('.wd-show-sidebar-btn');
  if (!showSidebarBtn) {
    console.error('Show button not found!');
    return;
  }

  showSidebarBtn.addEventListener('click', function (e) {
    e.preventDefault();
    e.stopPropagation();

    const sidebar = document.getElementById("top-categories-sidebar");
    if (sidebar) {
      sidebar.style.display = "block";
      //sidebar.style.left = "-175px";
    }
  });

  // Close sidebar logic (moved inside DOMContentLoaded and inside event listener)
  const closeSidebarHandler = function () {
    const closeBtn = document.getElementById("close-sidebar-btn");
    const sidebar = document.getElementById("top-categories-sidebar");

    if (closeBtn && sidebar) {
      closeBtn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();

        // Reset the styles
        sidebar.style.position = "";
        sidebar.style.left = "";
        sidebar.style.display = "none";
      });
    } else {
      console.warn('Sidebar or close button not found!');
    }
  };

  // Delay this part to ensure the element exists if it's loaded slightly later
  setTimeout(closeSidebarHandler, 100);
});



</script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const minPriceInput = document.getElementById("min_price");
    const maxPriceInput = document.getElementById("max_price");
    const minLabel = document.querySelector(".price_label .from");
    const maxLabel = document.querySelector(".price_label .to");
    const slider = document.querySelector(".price_slider");

    if (!slider) {
        console.error("Custom Price Slider: Slider element not found!");
        return;
    }

    // Default Min & Max Values
    const minPrice = parseInt(minPriceInput.dataset.min) || 10;
    const maxPrice = parseInt(maxPriceInput.dataset.max) || 800;

    let currentMin = parseInt(minPriceInput.value) || minPrice;
    let currentMax = parseInt(maxPriceInput.value) || maxPrice;

    // Create Range Inputs
    slider.innerHTML = `
        <input type="range" id="price_min_range" min="${minPrice}" max="${maxPrice}" step="10" value="${currentMin}">
        <input type="range" id="price_max_range" min="${minPrice}" max="${maxPrice}" step="10" value="${currentMax}">
    `;

    const minRange = document.getElementById("price_min_range");
    const maxRange = document.getElementById("price_max_range");

    // Function to Update Price Labels
    function updateLabels() {
        let minValue = parseInt(minRange.value);
        let maxValue = parseInt(maxRange.value);

        if (minValue >= maxValue) {
            minValue = maxValue - 10; // Prevent overlap
            minRange.value = minValue;
        }

        minLabel.textContent = "£" + minValue;
        maxLabel.textContent = "£" + maxValue;

        minPriceInput.value = minValue;
        maxPriceInput.value = maxValue;
    }

    // Attach Event Listeners
    minRange.addEventListener("input", updateLabels);
    maxRange.addEventListener("input", updateLabels);

    // Set initial values
    updateLabels();
});


</script>
