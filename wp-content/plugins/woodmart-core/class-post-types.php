<?php
/**
 * Post types file.
 *
 * @package Woodmart
 */

namespace WOODCORE;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Direct access not allowed.
}

/**
 * Post types class.
 */
class Post_Types {
	/**
	 * Instance.
	 *
	 * @var null
	 */
	public static $instance = null;

	/**
	 * Instance.
	 *
	 * @return Post_Types|null
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_layout' ), 1 );
		add_action( 'init', array( $this, 'linked_variations' ), 1 );
		add_action( 'init', array( $this, 'bought_together' ), 1 );
		add_action( 'init', array( $this, 'register_discounts' ), 1 );
		add_action( 'init', array( $this, 'register_free_gifts' ), 1 );
		add_action( 'init', array( $this, 'register_estimate_delivery' ), 1 );
		add_action( 'init', array( $this, 'register_abandoned_cart' ), 1 );
		add_action( 'init', array( $this, 'register_custom_product_tabs' ), 1 );
		add_action( 'init', array( $this, 'register_popup_builder' ), 1 );
		add_action( 'init', array( $this, 'register_floating_blocks' ), 1 );
	}

	/**
	 * Register floating blocks post type.
	 */
	public function register_floating_blocks() {
		$labels = array(
			'name'          => esc_html__( 'Floating Blocks', 'woodmart' ),
			'singular_name' => esc_html__( 'Floating Block', 'woodmart' ),
			'menu_name'     => esc_html__( 'Floating Blocks', 'woodmart' ),
			'search_items'  => esc_html__( 'Search Blocks', 'woodmart' ),
			'all_items'     => esc_html__( 'All Blocks', 'woodmart' ),
			'add_new'       => esc_html__( 'Add Block', 'woodmart' ),
			'add_new_item'  => esc_html__( 'Add Block', 'woodmart' ),
		);

		$args = array(
			'label'               => esc_html__( 'Floating Blocks', 'woodmart' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'editor', 'custom-fields' ),
			'hierarchical'        => false,
			'public'              => false,
			'publicly_queryable'  => is_user_logged_in(),
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => false,
			'show_in_admin_bar'   => false,
			'menu_position'       => 37,
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => true,
			'rewrite'             => false,
			'capability_type'     => 'page',
			'show_in_rest'        => true,
		);

		register_post_type( 'wd_floating_block', $args );

		$labels = array(
			'name'                  => esc_html__( 'Floating Block Categories', 'woodmart' ),
			'singular_name'         => esc_html__( 'Floating Block Category', 'woodmart' ),
			'search_items'          => esc_html__( 'Search Categories', 'woodmart' ),
			'popular_items'         => esc_html__( 'Popular Floating Block Categories', 'woodmart' ),
			'all_items'             => esc_html__( 'All Floating Block Categories', 'woodmart' ),
			'parent_item'           => esc_html__( 'Parent Category', 'woodmart' ),
			'parent_item_colon'     => esc_html__( 'Parent Category', 'woodmart' ),
			'edit_item'             => esc_html__( 'Edit Category', 'woodmart' ),
			'update_item'           => esc_html__( 'Update Category', 'woodmart' ),
			'add_new_item'          => esc_html__( 'Add New Category', 'woodmart' ),
			'new_item_name'         => esc_html__( 'New Category', 'woodmart' ),
			'add_or_remove_items'   => esc_html__( 'Add or remove Categories', 'woodmart' ),
			'choose_from_most_used' => esc_html__( 'Choose from most used floating blocks', 'woodmart' ),
			'menu_name'             => esc_html__( 'Categories', 'woodmart' ),
		);

		$args = array(
			'labels'            => $labels,
			'public'            => true,
			'show_in_nav_menus' => false,
			'show_admin_column' => false,
			'hierarchical'      => true,
			'show_tagcloud'     => true,
			'show_ui'           => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'wd_floating_block_cat' ),
			'capabilities'      => array(),
			'show_in_rest'      => true,
			'default_term'      => array(
				'name' => esc_html__( 'Uncategorized', 'woodmart' ),
				'slug' => 'uncategorized',
			),
		);

		register_taxonomy( 'wd_floating_block_cat', array( 'wd_floating_block' ), $args );
	}

	/**
	 * Register popup builder post type.
	 */
	public function register_popup_builder() {
		$labels = array(
			'name'          => esc_html__( 'Popups', 'woodmart' ),
			'singular_name' => esc_html__( 'Popup', 'woodmart' ),
			'menu_name'     => esc_html__( 'Popups', 'woodmart' ),
			'search_items'  => esc_html__( 'Search Popup', 'woodmart' ),
			'all_items'     => esc_html__( 'All Popups', 'woodmart' ),
			'add_new'       => esc_html__( 'Add Popup', 'woodmart' ),
			'add_new_item'  => esc_html__( 'Add Popup', 'woodmart' ),
		);

		$args = array(
			'label'               => esc_html__( 'Popups', 'woodmart' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'editor', 'custom-fields' ),
			'hierarchical'        => false,
			'public'              => false,
			'publicly_queryable'  => is_user_logged_in(),
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => false,
			'show_in_admin_bar'   => false,
			'menu_position'       => 36,
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => true,
			'rewrite'             => false,
			'capability_type'     => 'page',
			'show_in_rest'        => true,
		);

		register_post_type( 'wd_popup', $args );

		$labels = array(
			'name'                  => esc_html__( 'Popup Categories', 'woodmart' ),
			'singular_name'         => esc_html__( 'Popup Category', 'woodmart' ),
			'search_items'          => esc_html__( 'Search Categories', 'woodmart' ),
			'popular_items'         => esc_html__( 'Popular Popup Categories', 'woodmart' ),
			'all_items'             => esc_html__( 'All Popup Categories', 'woodmart' ),
			'parent_item'           => esc_html__( 'Parent Category', 'woodmart' ),
			'parent_item_colon'     => esc_html__( 'Parent Category', 'woodmart' ),
			'edit_item'             => esc_html__( 'Edit Category', 'woodmart' ),
			'update_item'           => esc_html__( 'Update Category', 'woodmart' ),
			'add_new_item'          => esc_html__( 'Add New Category', 'woodmart' ),
			'new_item_name'         => esc_html__( 'New Category', 'woodmart' ),
			'add_or_remove_items'   => esc_html__( 'Add or remove Categories', 'woodmart' ),
			'choose_from_most_used' => esc_html__( 'Choose from most used popups', 'woodmart' ),
			'menu_name'             => esc_html__( 'Categories', 'woodmart' ),
		);

		$args = array(
			'labels'            => $labels,
			'public'            => true,
			'show_in_nav_menus' => false,
			'show_admin_column' => false,
			'hierarchical'      => true,
			'show_tagcloud'     => true,
			'show_ui'           => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'wd_popup_cat' ),
			'capabilities'      => array(),
			'show_in_rest'      => true,
			'default_term'      => array(
				'name' => esc_html__( 'Uncategorized', 'woodmart' ),
				'slug' => 'uncategorized',
			),
		);

		register_taxonomy( 'wd_popup_cat', array( 'wd_popup' ), $args );
	}

	/**
	 * Register layout post type.
	 */
	public function register_layout() {
		register_post_type(
			'woodmart_layout',
			array(
				'label'               => esc_html__( 'Layouts', 'woodmart' ),
				'labels'              => array(
					'name'          => esc_html__( 'Layouts', 'woodmart' ),
					'singular_name' => esc_html__( 'Layout', 'woodmart' ),
					'menu_name'     => esc_html__( 'Layouts', 'woodmart' ),
					'all_items'     => esc_html__( 'All Layouts', 'woodmart' ),
					'add_new'       => esc_html__( 'Add Layout', 'woodmart' ),
					'add_new_item'  => esc_html__( 'Add Layout', 'woodmart' ),
				),
				'supports'            => array( 'title', 'editor', 'custom-fields' ),
				'show_in_nav_menus'   => false,
				'hierarchical'        => false,
				'public'              => true,
				'menu_position'       => 32,
				'menu_icon'           => 'dashicons-format-gallery',
				'publicly_queryable'  => is_user_logged_in(),
				'show_in_rest'        => true,
				'show_in_admin_bar'   => false,
				'capability_type'     => 'page',
				'exclude_from_search' => true,
			)
		);
	}

	/**
	 * Register layout post type.
	 */
	public function linked_variations() {
		if ( ! function_exists( 'woodmart_get_opt' ) || ( ! woodmart_get_opt( 'linked_variations' ) && ( ! function_exists( 'woodmart_is_import_demo_content' ) || ! woodmart_is_import_demo_content() ) ) ) {
			return;
		}

		register_post_type(
			'woodmart_woo_lv',
			array(
				'label'               => esc_html__( 'Linked Variations', 'woodmart' ),
				'labels'              => array(
					'name'          => esc_html__( 'Linked Variations', 'woodmart' ),
					'singular_name' => esc_html__( 'Linked Variations', 'woodmart' ),
					'menu_name'     => esc_html__( 'Linked Variations', 'woodmart' ),
					'add_new'       => esc_html__( 'Add New', 'woodmart' ),
					'add_new_item'  => esc_html__( 'Add New', 'woodmart' ),
				),
				'supports'            => array( 'title' ),
				'show_in_nav_menus'   => false,
				'hierarchical'        => false,
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => 'edit.php?post_type=product',
				'publicly_queryable'  => false,
				'show_in_rest'        => true,
				'show_in_admin_bar'   => false,
				'capability_type'     => 'product',
				'exclude_from_search' => true,
			)
		);
	}

	/**
	 * Register frequently bought together post type.
	 */
	public function bought_together() {
		if ( ! function_exists( 'woodmart_get_opt' ) || ( ! woodmart_get_opt( 'bought_together_enabled' ) && ( ! function_exists( 'woodmart_is_import_demo_content' ) || ! woodmart_is_import_demo_content() ) ) ) {
			return;
		}

		register_post_type(
			'woodmart_woo_fbt',
			array(
				'label'               => esc_html__( 'Frequently Bought Together', 'woodmart' ),
				'labels'              => array(
					'name'          => esc_html__( 'Frequently Bought Together', 'woodmart' ),
					'singular_name' => esc_html__( 'Frequently Bought Together', 'woodmart' ),
					'menu_name'     => esc_html__( 'Frequently Bought Together', 'woodmart' ),
					'add_new'       => esc_html__( 'Add New', 'woodmart' ),
					'add_new_item'  => esc_html__( 'Add New', 'woodmart' ),
				),
				'supports'            => array( 'title' ),
				'show_in_nav_menus'   => false,
				'hierarchical'        => false,
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => 'edit.php?post_type=product',
				'publicly_queryable'  => false,
				'show_in_rest'        => true,
				'show_in_admin_bar'   => false,
				'capability_type'     => 'product',
				'exclude_from_search' => true,
			)
		);
	}

	/**
	 * Register Dynamic Pricing & Discounts post type.
	 */
	public function register_discounts() {
		if ( ! function_exists( 'woodmart_get_opt' ) || ( ! woodmart_get_opt( 'discounts_enabled' ) && ( ! function_exists( 'woodmart_is_import_demo_content' ) || ! woodmart_is_import_demo_content() ) ) ) {
			return;
		}

		register_post_type(
			'wd_woo_discounts',
			array(
				'label'               => esc_html__( 'Dynamic Discounts', 'woodmart' ),
				'labels'              => array(
					'name'          => esc_html__( 'Dynamic Discounts', 'woodmart' ),
					'singular_name' => esc_html__( 'Dynamic Discount', 'woodmart' ),
					'menu_name'     => esc_html__( 'Dynamic Discounts', 'woodmart' ),
					'add_new'       => esc_html__( 'Add New', 'woodmart' ),
					'add_new_item'  => esc_html__( 'Add New Rule', 'woodmart' ),
					'edit_item'     => esc_html__( 'Edit Discount Rule', 'woodmart' ),
				),
				'supports'            => array( 'title' ),
				'show_in_nav_menus'   => false,
				'hierarchical'        => false,
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => 'edit.php?post_type=product',
				'publicly_queryable'  => false,
				'show_in_rest'        => true,
				'capability_type'     => 'product',
				'exclude_from_search' => true,
			)
		);
	}

	/**
	 * Register Free gifts post type.
	 */
	public function register_free_gifts() {
		if ( ! function_exists( 'woodmart_get_opt' ) || ( ! woodmart_get_opt( 'free_gifts_enabled' ) && ( ! function_exists( 'woodmart_is_import_demo_content' ) || ! woodmart_is_import_demo_content() ) ) ) {
			return;
		}

		register_post_type(
			'wd_woo_free_gifts',
			array(
				'label'               => esc_html__( 'Free Gifts', 'woodmart' ),
				'labels'              => array(
					'name'          => esc_html__( 'Free Gifts', 'woodmart' ),
					'singular_name' => esc_html__( 'Free Gift', 'woodmart' ),
					'menu_name'     => esc_html__( 'Free Gifts', 'woodmart' ),
					'add_new'       => esc_html__( 'Add New', 'woodmart' ),
					'add_new_item'  => esc_html__( 'Add New Gift', 'woodmart' ),
					'edit_item'     => esc_html__( 'Edit Gift Rule', 'woodmart' ),
				),
				'supports'            => array( 'title' ),
				'show_in_nav_menus'   => false,
				'hierarchical'        => false,
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => 'edit.php?post_type=product',
				'publicly_queryable'  => false,
				'show_in_rest'        => true,
				'capability_type'     => 'product',
				'exclude_from_search' => true,
			)
		);
	}

	/**
	 * Register Estimate Delivery post type.
	 */
	public function register_estimate_delivery() {
		if ( ! function_exists( 'woodmart_get_opt' ) || ( ! woodmart_get_opt( 'estimate_delivery_enabled' ) && ( ! function_exists( 'woodmart_is_import_demo_content' ) || ! woodmart_is_import_demo_content() ) ) ) {
			return;
		}

		register_post_type(
			'wd_woo_est_del',
			array(
				'label'               => esc_html__( 'Estimate Delivery', 'woodmart' ),
				'labels'              => array(
					'name'          => esc_html__( 'Estimate Delivery', 'woodmart' ),
					'singular_name' => esc_html__( 'Estimate Delivery', 'woodmart' ),
					'menu_name'     => esc_html__( 'Estimate Delivery', 'woodmart' ),
					'add_new'       => esc_html__( 'Add New', 'woodmart' ),
					'add_new_item'  => esc_html__( 'Add New Rule', 'woodmart' ),
					'edit_item'     => esc_html__( 'Edit Estimate Delivery Rule', 'woodmart' ),
				),
				'supports'            => array( 'title' ),
				'show_in_nav_menus'   => false,
				'hierarchical'        => false,
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => 'edit.php?post_type=product',
				'publicly_queryable'  => false,
				'show_in_rest'        => true,
				'capability_type'     => 'product',
				'exclude_from_search' => true,
			)
		);
	}

	/**
	 * Register Abandoned Cart post type.
	 */
	public function register_abandoned_cart() {
		if ( ! function_exists( 'woodmart_get_opt' ) || ! woodmart_get_opt( 'cart_recovery_enabled' ) ) {
			return;
		}

		register_post_type(
			'wd_abandoned_cart',
			array(
				'label'               => esc_html__( 'Carts', 'woodmart' ),
				'labels'              => array(
					'name'          => esc_html__( 'Abandoned Cart', 'woodmart' ),
					'singular_name' => esc_html__( 'Abandoned Cart', 'woodmart' ),
					'menu_name'     => esc_html__( 'Abandoned Cart', 'woodmart' ),
					'add_new'       => esc_html__( 'Add New Abandoned Cart', 'woodmart' ),
					'add_new_item'  => esc_html__( 'Add New Abandoned Cart', 'woodmart' ),
					'edit_item'     => esc_html__( 'Abandoned cart information', 'woodmart' ),
				),
				'supports'            => array( '' ),
				'show_in_nav_menus'   => false,
				'hierarchical'        => false,
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => false,
				'exclude_from_search' => true,
				'capability_type'     => 'post',
				'capabilities'        => array( 'create_posts' => false ),
				'map_meta_cap'        => true,
			)
		);
	}

	/**
	 * Register custom product tabs.
	 */
	public function register_custom_product_tabs() {
		if ( ! function_exists( 'woodmart_get_opt' ) || ( ! woodmart_get_opt( 'custom_product_tabs_enabled' ) && ( ! function_exists( 'woodmart_is_import_demo_content' ) || ! woodmart_is_import_demo_content() ) ) ) {
			return;
		}

		register_post_type(
			'wd_product_tabs',
			array(
				'label'               => esc_html__( 'Custom Tabs', 'woodmart' ),
				'labels'              => array(
					'name'          => esc_html__( 'Custom Tabs', 'woodmart' ),
					'singular_name' => esc_html__( 'Custom Tabs', 'woodmart' ),
					'menu_name'     => esc_html__( 'Custom Tabs', 'woodmart' ),
					'add_new'       => esc_html__( 'Add New Tab', 'woodmart' ),
					'add_new_item'  => esc_html__( 'Add New Tab', 'woodmart' ),
					'edit_item'     => esc_html__( 'Edit Tabs', 'woodmart' ),
				),
				'supports'            => array( 'title', 'editor' ),
				'show_in_nav_menus'   => false,
				'hierarchical'        => false,
				'public'              => false,
				'show_in_admin_bar'   => false,
				'show_in_menu'        => 'edit.php?post_type=product',
				'publicly_queryable'  => is_user_logged_in(),
				'show_in_rest'        => true,
				'capability_type'     => 'product',
				'exclude_from_search' => true,
				'show_ui'             => true,
				'rewrite'             => false,
			)
		);
	}
}

Post_Types::get_instance();
