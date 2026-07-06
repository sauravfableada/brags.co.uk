<?php

namespace WeDevs\DokanPro\Modules\ProductQA;

use WeDevs\DokanPro\Modules\ProductQA\Models\Question;

defined( 'ABSPATH' ) || exit;


/**
 * Frontend Related functionality.
 *
 * @since 3.11.0
 */
class Frontend {
    /**
     * Constructor.
     *
     * @since 3.11.0
     */
    public function __construct() {
        add_filter( 'woocommerce_product_tabs', [ $this, 'add_tab' ] );
        add_filter( 'woocommerce_login_redirect', [ $this, 'login_redirect' ], 20 );
        add_filter( 'woocommerce_registration_redirect', [ $this, 'login_redirect' ], 20 );
    }

    /**
     * Add Tab to product page.
     *
     * @since 3.11.0
     *
     * @param array $tabs
     *
     * @return array
     */
    public function add_tab( $tabs ): array {
        $tabs['product_qa'] = [
            'title'    => __( 'Questions & Answers', 'dokan' ),
            'priority' => 50,
            'callback' => [ $this, 'tab_content' ]
        ];

        return $tabs;
    }

    /**
     * Add Tab content to product page.
     *
     * @since 3.11.0
     * @return void
     */
    public function tab_content() {
        global $product;

        wp_enqueue_script( 'dokan-product-qa-frontend' );
        wp_enqueue_style( 'dokan-react-components' );

        dokan_get_template_part(
            'product', 'tab', [
                'is_product_qa' => true,
                'pro'           => true,
                'product'       => $product,
                'count'         => ( new Question() )->count( [ 'product_id' => $product->get_id(), 'status' => Question::STATUS_VISIBLE ] ),
            ]
        );
    }

    /**
     * Redirect user to product page after successful login.
     *
     * @param string $redirect_to URL to redirect to.
     *
     * @return string
     */
    public function login_redirect( $redirect_to ): string {
        if ( empty( $_REQUEST['product_qa'] ) ) {
            return $redirect_to;
        }

        $product_id = absint( wp_unslash( $_REQUEST['product_qa'] ) );

        $product = wc_get_product( $product_id );

        if ( ! $product ) {
            return $redirect_to;
        }

        return add_query_arg( 'product_qa', $product_id, $product->get_permalink() );
    }
}
