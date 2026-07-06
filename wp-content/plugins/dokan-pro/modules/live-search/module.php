<?php

namespace WeDevs\DokanPro\Modules\LiveSearch;

/**
 * Dokan_Live_Search class
 *
 * @class Dokan_Live_Search The class that holds the entire Dokan_Live_Search plugin
 */
class Module {

    /**
     * Constructor for the Dokan_Live_Search class
     *
     * Sets up all the appropriate hooks and actions
     * within our plugin.
     *
     * @uses is_admin()
     * @uses add_action()
     */
    public function __construct() {
        include_once 'classes/class-dokan-live-search.php';

        $this->define_constants();

        // Widget initialization hook
        add_action( 'dokan_widgets', array( $this, 'initialize_widget_register' ) );

        // Loads frontend scripts and styles
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

        add_filter( 'dokan_settings_sections', array( $this, 'render_live_search_section' ) );
        add_filter( 'dokan_settings_fields', array( $this, 'render_live_search_settings' ) );

        // removing redirection to single product page
        add_filter( 'woocommerce_redirect_single_search_result', '__return_false' );

        add_action( 'wp_ajax_dokan_suggestion_search_action', array( $this, 'dokan_suggestion_search_action' ) );
        add_action( 'wp_ajax_nopriv_dokan_suggestion_search_action', array( $this, 'dokan_suggestion_search_action' ) );

        add_action( 'init', [ $this, 'register_scripts' ] );
    }

    /**
     * Enqueue admin scripts
     *
     * Allows plugin assets to be loaded.
     *
     * @uses wp_enqueue_script()
     * @uses wp_localize_script()
     * @uses wp_enqueue_style()
     */
    public function enqueue_scripts() {
    }

    /**
     * Callback for Ajax Action Initialization
     *
     * @return void
     */
    public function dokan_suggestion_search_action() {
        global $wpdb, $woocommerce;

        $return_result              = array();
        $return_result['type']      = 'error';
        $return_result['data_list'] = '';
        $output                     = '';
        $args                       = [
            'posts_per_page' => 250,
            'post_status'    => 'publish',
        ];

        // _wpnonce check for an extra layer of security, the function will exit if it fails
        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_wpnonce'] ) ), 'dokan_suggestion_search_nonce' ) ) {
            wp_send_json_error( __( 'Error: Nonce verification failed', 'dokan' ) );
        }

        if ( ! empty( $_POST['textfield'] ) ) {
            $args['s'] = sanitize_text_field( wp_unslash( $_POST['textfield'] ) );

            if ( ! empty( $_POST['selectfield'] ) ) {
                $args['tax_query'][] = [
                    'taxonomy' => 'product_cat',
                    'field'    => 'slug',
                    'terms'    => sanitize_title_with_dashes( wp_unslash( $_POST['selectfield'] ), '', 'save' ),
                ];
            }

            $query_results = dokan()->product->all( $args )->get_posts();

            if ( ! empty( $query_results ) ) {
                foreach ( $query_results as $result ) {
                    $product       = wc_get_product( $result->ID );
                    $price         = wc_price( $product->get_price() );
                    $price_sale    = $product->get_sale_price();
                    $price_regular = $product->get_regular_price();
                    $stock         = $product->get_stock_status();
                    $sku           = $product->get_sku();
                    $categories    = wp_get_post_terms( $result->ID, 'product_cat' );

                    if ( 'hidden' === $product->get_catalog_visibility() || 'catalog' === $product->get_catalog_visibility() ) {
                        continue;
                    }

                    if ( 'variable' === $product->get_type() ) {
                        $price = wc_price( $product->get_variation_price() ) . ' - ' . wc_price( $product->get_variation_price( 'max' ) );
                    }

                    $get_product_image = esc_url( get_the_post_thumbnail_url( $result->ID, 'thumbnail' ) );

                    if ( empty( $get_product_image ) && function_exists( 'wc_placeholder_img_src' ) ) {
                        $get_product_image = wc_placeholder_img_src();
                    }

                    $output .= '<li>';
                    $output .= '<a href="' . get_post_permalink( $result->ID ) . '">';
                    $output .= '<div class="dokan-ls-product-image">';
                    $output .= '<img src="' . $get_product_image . '">';
                    $output .= '</div>';
                    $output .= '<div class="dokan-ls-product-data">';
                    $output .= '<h3>' . $result->post_title . '</h3>';

                    if ( ! empty( $price ) ) {
                        $output .= '<div class="product-price">';
                        $output .= '<span class="dokan-ls-regular-price">' . $price . '</span>';
                        if ( ! empty( $price_sale ) ) {
                            $output .= '<span class="dokan-ls-sale-price">' . wc_price( $price_regular ) . '</span>';
                        }
                        $output .= '</div>';
                    }

                    if ( ! empty( $categories ) ) {
                        $output .= '<div class="dokan-ls-product-categories">';
                        foreach ( $categories as $category ) {
                            if ( $category->parent ) {
                                $parent  = get_term_by( 'id', $category->parent, 'product_cat' );
                                $output .= '<span>' . $parent->name . '</span>';
                            }
                            $output .= '<span>' . $category->name . '</span>';
                        }
                        $output .= '</div>';
                    }

                    if ( ! empty( $sku ) ) {
                        $output .= '<div class="dokan-ls-product-sku">' . esc_html__( 'SKU:', 'dokan' ) . ' ' . $sku . '</div>';
                    }

                    $output .= '</div>';
                    $output .= '</a>';
                    $output .= '</li>';
                }
            }
        }

        // If above action fails, result type is set to 'error' set to value, if success, updated
        if ( $output ) {
            $return_result['type']      = 'success';
            $return_result['data_list'] = $output;
        }
        echo wp_json_encode( $return_result );
        die();
    }

    /**
     * Add Settings section in Dokan Settings
     *
     * @since 1.0
     *
     * @param array $sections
     *
     * @return array
     */
    public function render_live_search_section( $sections ) {
        $sections[] = [
            'id'                   => 'dokan_live_search_setting',
            'title'                => __( 'Live Search', 'dokan' ),
            'icon_url'             => plugins_url( 'assets/images/search.svg', __FILE__ ),
            'description'          => __( 'Ajax Live Search Control', 'dokan' ),
            'document_link'        => 'https://dokan.co/docs/wordpress/modules/how-to-install-configure-use-dokan-live-search/',
            'settings_title'       => __( 'Live Search Settings', 'dokan' ),
            'settings_description' => __( 'You can configure your site settings for customers to utilize when navigating stores for specific products.', 'dokan' ),
        ];

        return $sections;
    }

    /**
     * Add live search options on Dokan Settings under General section
     *
     * @since 1.0
     *
     * @param array $settings_fields
     *
     * @return array
     */
    public function render_live_search_settings( $settings_fields ) {
        $settings_fields['dokan_live_search_setting'] = [
            'live_search_option' => [
                'name'    => 'live_search_option',
                'label'   => __( 'Live Search Options', 'dokan' ),
                'desc'    => __( 'Select one option which one will apply on search box', 'dokan' ),
                'type'    => 'select',
                'default' => 'suggestion_box',
                'options' => [
                    'suggestion_box'  => __( 'Search with Suggestion Box', 'dokan' ),
                    'old_live_search' => __( 'Autoload Replace Current Content', 'dokan' ),
                ],
                'tooltip' => __( 'Select one option which one will apply on search box.', 'dokan' ),
            ],
        ];

        return $settings_fields;
    }

    /**
     * Callback for Widget Initialization
     *
     * @since 3.10.2 Updated to comply with `dokan-lite` widget registration process
     *
     * @param array $widgets List of widgets to be registered
     *
     * @return array
     */
    public function initialize_widget_register( array $widgets ): array {
        $widgets[ \Dokan_Live_Search_Widget::INSTANCE_KEY ] = \Dokan_Live_Search_Widget::class;
        return $widgets;
    }

    /**
     * Define constants
     *
     * @since 3.7.4
     */
    public function define_constants() {
        define( 'DOKAN_LIVE_SEARCH_FILE', __FILE__ );
        define( 'DOKAN_LIVE_SEARCH_ASSETS', plugin_dir_url( DOKAN_LIVE_SEARCH_FILE ) . 'assets/' );
    }

    /**
     * Register scripts
     *
     * @since 3.7.4
     */
    public function register_scripts() {
        wp_register_style( 'dokan-ls-custom-style', DOKAN_LIVE_SEARCH_ASSETS . 'css/style.css', false, DOKAN_PLUGIN_VERSION );
        wp_register_script( 'dokan-ls-custom-js', DOKAN_LIVE_SEARCH_ASSETS . 'js/script.js', array( 'jquery' ), DOKAN_PLUGIN_VERSION, true );

        wp_localize_script(
            'dokan-ls-custom-js', 'dokanLiveSearch', array(
                'ajaxurl'             => admin_url( 'admin-ajax.php' ),
                'loading_img'         => plugins_url( 'assets/images/loading.gif', __FILE__ ),
                'currentTheme'        => wp_get_theme()->stylesheet,
                'themeTags'           => apply_filters( 'dokan_ls_theme_tags', array() ),
                'dokan_search_action' => 'dokan_suggestion_search_action',
                'dokan_search_nonce'  => wp_create_nonce( 'dokan_suggestion_search_nonce' ),
            )
        );
    }
}
