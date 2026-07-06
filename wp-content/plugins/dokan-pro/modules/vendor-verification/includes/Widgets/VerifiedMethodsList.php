<?php

namespace WeDevs\DokanPro\Modules\VendorVerification\Widgets;

use WeDevs\DokanPro\Modules\VendorVerification\Models\VerificationMethod;
use WeDevs\DokanPro\Modules\VendorVerification\Models\VerificationRequest;

defined( 'ABSPATH' ) || exit;

/**
 * new WordPress Widget format
 * WordPress 2.8 and above
 *
 * @see http://codex.wordpress.org/Widgets_API#Developing_Widgets
 */
class VerifiedMethodsList extends \WP_Widget {

    /**
     * Instance key to keep track of the widget inside widget container in dokan-lite
     *
     * @since 3.10.2
     *
     * @var string
     */
    const INSTANCE_KEY = 'vendor_verification__Dokan_Store_Verification_list'; // Naming Structure: {module_slug}__{ClassName}

    private $seller_info;

    /**
     * Constructor
     *
     * @return void
     * */
    public function __construct() {
        $widget_ops = [ 'classname' => 'dokan-verification-list', 'description' => __( 'Dokan Vendor Verifications', 'dokan' ) ];
        parent::__construct( 'dokan-verification-list', __( 'Dokan: Verification', 'dokan' ), $widget_ops );
    }

    /**
     * Outputs the HTML for this widget.
     *
     * @param array $args     An array of standard parameters for widgets in this theme
     * @param array $instance An array of settings for this widget instance
     *
     * @return void Echoes it's output
     * */
    public function widget( $args, $instance ) {
        if ( dokan_is_store_page() || is_product() ) {
            $defaults = [
                'title' => __( 'ID Verification', 'dokan' ),
            ];

            $instance = wp_parse_args( $instance, $defaults );

            if ( is_product() ) {
                global $post;
                $seller_id = get_post_field( 'post_author', $post->ID );
            }

            if ( dokan_is_store_page() ) {
                $seller_id = (int) get_query_var( 'author' );
            }

            if ( empty( $seller_id ) ) {
                return;
            }

            $store_info = dokan_get_store_info( $seller_id );

            $this->seller_info = $store_info;

            $verification_method_ids = array_unique(
                ( new VerificationRequest() )
                ->query_field(
                    [
                        'status'    => VerificationRequest::STATUS_APPROVED,
                        'vendor_id' => $seller_id,
                        'field'     => 'method_id',
                        'order'     => 'ASC',
                    ]
                )
            );

            if ( empty( $verification_method_ids ) ) {
                return;
            }

            $verified_methods = array_map( function ( $method_id ) { return new VerificationMethod( $method_id ); }, $verification_method_ids  );

            dokan_get_template_part( 'widgets/vendor-verification', '', [
                'pro'        => true,
                'data'       => $args,
                'instance'   => $instance,
                'store_info' => $store_info,
                'methods'    => $verified_methods,
                'widget'     => $this,
            ] );
        }

        do_action( 'dokan_widget_store_vendor_verification_render', $args, $instance, $this );
    }

    /**
     * Deals with the settings when they are saved by the admin. Here is
     * where any validation should be dealt with.
     *
     * @param array $new_instance An array of new settings as submitted by the admin
     * @param array $old_instance An array of the previous settings
     *
     * @return array The validated and (if necessary) amended settings
     * */
    public function update( $new_instance, $old_instance ) {
        // update logic goes here
        $updated_instance = $new_instance;

        return $updated_instance;
    }

    /**
     * Displays the form for this widget on the Widgets page of the WP Admin area.
     *
     * @param array $instance An array of the current settings for this widget
     *
     * @return void Echoes it's output
     * */
    public function form( $instance ) {
        $instance = wp_parse_args( (array) $instance, [
            'title' => __( 'Vendor Verification', 'dokan' ),
        ] );

        $title = $instance['title'];
        ?>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'dokan' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
        </p>
        <?php
    }

    /*
     * Prints out list items after checking
     */
    public function print_item( $key, $item ) {
        switch ( $key ) {
            case 'info' :
                $this->print_info_items( $key, $item );
                break;
            case 'verification_method' :
                $this->print_verification_methods( $key, $item );
                break;
            case 'verified_info' :

                break;
            default :
                $this->print_social_item( $key, $item );
                break;
        }
    }

    /**
     * Print social items.
     *
     * @since 3.11.1
     *
     * @param string $key  Key.
     * @param string $item Item.
     *
     * @return void
     */
    public function print_social_item( $key, $item ) {
        if ( $item === '' || count( $item ) < 0 ) {
            return;
        }
        ?>
        <li class="clearfix">
            <i class="fab fa-<?php echo esc_attr( $key ); ?>"></i> <span><?php echo esc_html( ucfirst( $key ) ); ?></span><i class="far fa-check-circle verification-icon dokan-right"></i>
        </li>
        <?php
    }

    /**
     * Print verified info items.
     *
     * @since 3.11.1
     *
     * @param string $key  Key.
     * @param string $item Item.
     *
     * @return void
     */
    public function print_info_items( $key, $item ) {
        if ( isset( $item['dokan_v_id_status'] ) ) {
            if ( $item['dokan_v_id_status'] === 'approved' ) {
                ?>
                <li class="clearfix">
                    <i class="fas fa-user"></i> <span><?php _e( 'Photo ID', 'dokan' ); ?></span><i class="far fa-check-circle verification-icon dokan-right"></i>
                </li>
                <?php
            }
        }

        if ( isset( $item['store_address']['v_status'] ) && $item['store_address']['v_status'] === 'approved' ) {
            if ( count( $this->verify_address( $item['store_address'] ) ) === 0 ) {
                ?>
                <li class="clearfix">
                    <i class="fas fa-map-marker-alt"></i> <span><?php _e( 'Postal Address', 'dokan' ); ?></span><i class="far fa-check-circle verification-icon dokan-right"></i>
                </li>
                <?php
            }
        }

        if ( isset( $item['phone_status'] ) ) {
            if ( $item['phone_status'] === 'verified' ) {
                ?>
                <li class="clearfix">
                    <i class="fas fa-phone-square"></i> <span><?php _e( 'Phone', 'dokan' ); ?></span><i class="far fa-check-circle verification-icon dokan-right"></i>
                </li>
                <?php
            }
        }
    }

    /**
     * Print verified verification methods.
     *
     * @since 3.11.1
     *
     * @param string             $key  Key.
     * @param VerificationMethod $item Verified Method.
     *
     * @return void
     */
    public function print_verification_methods( $key, $item ) {
        ?>
        <li class="clearfix">
            <i class="fas fa-clipboard"></i><span style="margin-left: 5px; "><?php echo esc_html( $item->get_title() ); ?></span><i class="far fa-check-circle verification-icon dokan-right"></i>
        </li>
        <?php
    }

    /**
     * Verify address.
     *
     * @since 3.11.1
     *
     * @param array $verified_address Verified Address.
     *
     * @return array
     */
    private function verify_address( $verified_address ) {
        $store_address = $this->seller_info['address'] ?? array();
        
        return array_diff( $store_address, $verified_address );
    }
}
