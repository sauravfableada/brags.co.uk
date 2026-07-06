<?php

namespace WeDevs\DokanPro\Announcement;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Announcement Post Type
 *
 * @since 3.9.4
 */
class PostType {
    /*
     * Post type name
     *
     * @since 3.9.4
     *
     * @var string
     */
    private $post_type = 'dokan_announcement';

    /**
     * Class constructor
     *
     * @since 3.9.4
     */
    public function __construct() {
        add_action( 'init', [ $this, 'register_post_type' ],20 );
    }

    /**
     * Register announcement post type
     *
     * @since 2.1
     * @since 3.9.4 moved this method from Announcement class to PostType class
     *
     * @return void
     */
    public function register_post_type() {
        register_post_type(
            $this->post_type, array(
                'label'           => __( 'Announcement', 'dokan' ),
                'description'     => '',
                'public'          => false,
                'show_ui'         => true,
                'show_in_menu'    => false,
                'capability_type' => 'post',
                'hierarchical'    => false,
                'rewrite'         => array( 'slug' => '' ),
                'query_var'       => false,
                'supports'        => array( 'title', 'editor' ),
                'labels'          => array(
                    'name'               => __( 'Announcement', 'dokan' ),
                    'singular_name'      => __( 'Announcement', 'dokan' ),
                    'menu_name'          => __( 'Dokan Announcement', 'dokan' ),
                    'add_new'            => __( 'Add Announcement', 'dokan' ),
                    'add_new_item'       => __( 'Add New Announcement', 'dokan' ),
                    'edit'               => __( 'Edit', 'dokan' ),
                    'edit_item'          => __( 'Edit Announcement', 'dokan' ),
                    'new_item'           => __( 'New Announcement', 'dokan' ),
                    'view'               => __( 'View Announcement', 'dokan' ),
                    'view_item'          => __( 'View Announcement', 'dokan' ),
                    'search_items'       => __( 'Search Announcement', 'dokan' ),
                    'not_found'          => __( 'No Announcement Found', 'dokan' ),
                    'not_found_in_trash' => __( 'No Announcement found in trash', 'dokan' ),
                    'parent'             => __( 'Parent Announcement', 'dokan' ),
                ),
            )
        );
    }
}
