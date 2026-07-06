<?php

namespace WeDevs\DokanPro\VendorDiscount;

use WC_Coupon;
use WC_Order;
use WC_Order_Item_Coupon;
use WP_Query;

if ( ! defined( 'ABSPATH' ) ) {
    exit();
}

/**
 * Class Hooks
 *
 * @since   3.9.4
 *
 * @package WeDevs\DokanPro\VendorDiscount
 */
class Hooks {

    /**
     * Hooks constructor.
     *
     * @since 3.9.4
     */
    public function __construct() {
        // Generate and coupons on cart page or checkout page on order
        $id = get_the_ID();
        if ( has_cart_block_in_page( $id ) || has_checkout_block_in_page( $id ) ) {
            add_action( 'woocommerce_blocks_enqueue_cart_block_scripts_before', [ $this, 'generate_and_apply_coupon_for_discount' ] );
            add_action( 'woocommerce_blocks_enqueue_checkout_block_scripts_before', [ $this, 'generate_and_apply_coupon_for_discount' ] );
        } else {
            add_action( 'woocommerce_check_cart_items', [ $this, 'generate_and_apply_coupon_for_discount' ] );
            add_action( 'woocommerce_before_checkout_form', [ $this, 'generate_and_apply_coupon_for_discount' ] );
        }
        // Also apply on quantity update in blocks and classic cart page support
        add_action( 'woocommerce_after_cart_item_quantity_update', [ $this, 'generate_and_apply_coupon_for_discount' ] );
        add_action( 'woocommerce_coupon_error', [ $this, 'maybe_delete_coupon' ], 10, 3 );

        add_filter( 'woocommerce_cart_totals_coupon_html', [ $this, 'erase_remove_coupon_button' ], 10, 3 );
        add_filter( 'woocommerce_cart_totals_coupon_label', [ $this, 'change_coupon_label' ], 10, 2 );
        add_filter( 'woocommerce_coupon_message', [ $this, 'change_coupon_success_message' ], 10, 3 );

        add_action( 'dokan_product_updated', [ $this, 'update_product_discount_coupon_value' ], 10, 2 );

        // delete coupon after order completed
        add_action( 'woocommerce_checkout_update_order_meta', [ $this, 'delete_product_discount_coupon_after_order_completed' ], 9999, 1 );
        // this hook will delete coupons for sub orders
        add_action( 'dokan_checkout_update_order_meta', [ $this, 'delete_product_discount_coupon_after_order_completed' ], 9999, 1 );

        // replace coupon name with the discount label
        add_filter( 'woocommerce_order_get_items', [ $this, 'replace_coupon_name' ], 10, 3 );

        // Displays discount on customer dashboard.
        add_filter( 'woocommerce_get_order_item_totals', [ $this, 'display_order_discounts' ], 10, 2 );

        // fix duplicate coupon issue
        add_filter( 'dokan_should_copy_coupon_to_sub_order', [ $this, 'copy_coupon_to_sub_order' ], 10, 4 );

        // Show discount label on cart line item.
        add_filter( 'woocommerce_cart_item_price', [ $this, 'show_product_discount_on_cart_line_item' ], 10, 3 );

        // Show order discount label in dokan store page.
        add_action( 'dokan_store_profile_frame_after', [ $this, 'show_order_discount_on_store_page' ] );

        // Show product discount below product price.
        add_filter( 'woocommerce_get_price_html', [ $this, 'add_product_discount_text_below_product_price' ], 10, 2 );

        // Add discount in woocommerce cart checkout block.
        add_action( 'woocommerce_blocks_mini-cart_block_registration', [ $this, 'discount_support_in_cart_checkout_block' ] );
        add_action( 'woocommerce_blocks_cart_block_registration', [ $this, 'discount_support_in_cart_checkout_block' ] );
        add_action( 'woocommerce_blocks_checkout_block_registration', [ $this, 'discount_support_in_cart_checkout_block' ] );
    }

    /**
     * Generate and apply coupon for order and product quantity discount.
     *
     * @since 3.9.4
     *
     * @return void
     */
    public function generate_and_apply_coupon_for_discount(): void {
        $this->apply_order_discounts();
        $this->apply_product_discounts();
    }

    /**
     * Apply product quantity discount.
     *
     * @since 3.9.4
     *
     * @return void
     */
    public function apply_product_discounts(): void {
        if ( ! WC()->cart ) {
            return;
        }

        foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
            $product_discount = ( new ProductDiscount() )
                ->set_cart_item_key( $cart_item_key )
                ->set_product_id( $cart_item['product_id'] )
                ->set_quantity( $cart_item['quantity'] );

            if ( ! $product_discount->is_already_applied() && $product_discount->is_applicable() ) {
                $product_discount->apply();
            } elseif ( $product_discount->is_already_applied() && ! $product_discount->is_applicable() ) {
                $product_discount->remove()->delete_coupon();
            }
        }
    }

    /**
     * Apply order total discount.
     *
     * @since 3.9.4
     *
     * @return void
     */
    public function apply_order_discounts(): void {
        if ( ! WC()->cart ) {
            return;
        }

        $cart_data            = [];
        $applied_coupons      = [];
        $cart_applied_coupons = WC()->cart->applied_coupons;
        WC()->cart->calculate_totals();

        /**
         * Here we are extracting the vendor and his order totals so that we can apply the discounts based on the vendor settings.
         */
        foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
            $product_id = $cart_item['product_id'];
            $seller     = dokan_get_vendor_by_product( $product_id );

            if ( ! $seller ) {
                continue;
            }

            if ( ! array_key_exists( $seller->get_id(), $cart_data ) ) {
                $cart_data[ $seller->get_id() ]['total_amount'] = 0;
                $cart_data[ $seller->get_id() ]['vendor']       = $seller;
                $cart_data[ $seller->get_id() ]['product_ids']  = [];
            }

            $cart_data[ $seller->get_id() ]['total_amount']  = $cart_data[ $seller->get_id() ]['total_amount'] + (float) $cart_item['line_subtotal'];
            $cart_data[ $seller->get_id() ]['product_ids'][] = $product_id;

            // we need this to remove the coupon from the cart, $cart_item_key is unique and won't change even if the quantity is changed
            $cart_data[ $seller->get_id() ]['cart_item_keys'][] = $cart_item_key;
        }

        /**
         * Here we are applying the discounts based on the vendor settings.
         */
        foreach ( $cart_data as $data ) {
            $vendor        = $data['vendor'];
            $total_amount  = $data['total_amount'];
            $product_ids   = $data['product_ids'];
            $cart_item_key = md5( wp_json_encode( $data['cart_item_keys'] ) );

            $order_discount = ( new OrderDiscount() )
                ->set_vendor( $vendor )
                ->set_cart_item_key( $cart_item_key )
                ->set_total_amount( $total_amount )
                ->set_product_ids( $product_ids );

            if ( ! $order_discount->is_applicable() ) {
                $order_discount->remove()->delete_coupon();
                continue;
            }

            if ( ! $order_discount->is_already_applied() ) {
                $order_discount->apply();
            }

            $applied_coupons[] = $order_discount->get_coupon_code();
        }

        foreach ( $cart_applied_coupons as $coupon_code ) {
            $coupon = new WC_Coupon( $coupon_code );
            if ( Helper::is_vendor_order_discount_coupon( $coupon ) && ! in_array( $coupon_code, $applied_coupons, true ) ) {
                WC()->cart->remove_coupon( $coupon_code );
                $coupon->delete( true );
            }
        }
    }

    /**
     * Delete coupon if it is a vendor discount coupon.
     *
     * @since 3.9.4
     *
     * @param string    $err
     * @param string    $err_code
     * @param WC_Coupon $coupon
     *
     * @return string
     */
    public function maybe_delete_coupon( $err, $err_code, $coupon ) {
        if (
            is_a( $coupon, 'WC_Coupon' ) &&
            ( Helper::is_vendor_order_discount_coupon( $coupon ) || Helper::is_vendor_product_quantity_discount_coupon( $coupon ) )
        ) {
            $coupon->delete( true );
        }

        return $err;
    }

    /**
     * Erase remove coupon button.
     *
     * @since 3.9.4
     *
     * @param string    $coupon_html
     * @param WC_Coupon $coupon
     * @param string    $discount_amount_html
     *
     * @return string
     */
    public function erase_remove_coupon_button( string $coupon_html, WC_Coupon $coupon, string $discount_amount_html ): string {
        if ( Helper::is_vendor_discount_coupon( $coupon ) ) {
            return $discount_amount_html;
        }

        return $coupon_html;
    }

    /**
     * Change coupon label text in a cart totals section.
     *
     * @since 3.9.4
     *
     * @param string    $label
     * @param WC_Coupon $coupon
     *
     * @return string
     */
    public function change_coupon_label( string $label, WC_Coupon $coupon ): string {
        if ( Helper::is_vendor_product_quantity_discount_coupon( $coupon ) ) {
            return ProductDiscount::get_coupon_label(
                $coupon->get_meta( ProductDiscount::LOT_DISCOUNT_AMOUNT, true ),
                $coupon->get_meta( ProductDiscount::DISCOUNT_ITEM_TITLE, true ),
                $coupon->get_meta( ProductDiscount::LOT_DISCOUNT_QUANTITY, true )
            );
        } elseif ( Helper::is_vendor_order_discount_coupon( $coupon ) ) {
            $percentage = $coupon->get_meta( OrderDiscount::SETTING_ORDER_PERCENTAGE, true );
            $min_order  = $coupon->get_meta( OrderDiscount::SETTING_MINIMUM_ORDER_AMOUNT, true );
            $vendor     = dokan()->vendor->get( $coupon->get_meta( 'coupons_vendors_ids', true ) );

            return OrderDiscount::get_coupon_label( $percentage, $min_order, $vendor->get_shop_name() );
        }

        return $label;
    }

    /**
     * Change coupon applied success message for product quantity and order discount.
     *
     * @since 3.9.4
     *
     * @param string    $msg
     * @param string    $msg_code
     * @param WC_Coupon $coupon
     *
     * @return string
     */
    public function change_coupon_success_message( string $msg, string $msg_code, WC_Coupon $coupon ): string {
        if ( Helper::is_vendor_product_quantity_discount_coupon( $coupon ) ) {
            return __( 'Dokan vendor product quantity discount applied successfully.', 'dokan' );
        } elseif ( Helper::is_vendor_order_discount_coupon( $coupon ) ) {
            return __( 'Dokan vendor order discount applied successfully.', 'dokan' );
        }

        return $msg;
    }

    /**
     * Update product quantity discount coupon value when product quantity discount is updated.
     *
     * @since 3.9.4
     *
     * @param int   $product_id
     * @param array $data
     *
     * @return void
     */
    public function update_product_discount_coupon_value( int $product_id, array $data ) {
        if ( ! isset( $data[ ProductDiscount::LOT_DISCOUNT_AMOUNT ] ) || ! $data[ ProductDiscount::LOT_DISCOUNT_QUANTITY ] ) {
            return;
        }

        $query = new WP_Query(
            [
                'fields'         => 'ids',
                'post_type'      => 'shop_coupon',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'meta_query'     => [ // phpcs:ignore
                    'relation' => 'AND',
                    [
                        'key'     => ProductDiscount::DISCOUNT_TYPE_KEY,
                        'value'   => 'yes',
                        'compare' => '=',
                    ],
                    [
                        'key'     => 'product_ids',
                        'value'   => $product_id,
                        'compare' => '=',
                    ],
                ],
            ]
        );

        $coupons = $query->get_posts();
        if ( empty( $coupons ) ) {
            return;
        }

        foreach ( $coupons as $coupon_id ) {
            $coupon = new WC_Coupon( $coupon_id );
            $coupon->set_amount( $data[ ProductDiscount::LOT_DISCOUNT_AMOUNT ] );

            $coupon->add_meta_data( ProductDiscount::LOT_DISCOUNT_AMOUNT, $data[ ProductDiscount::LOT_DISCOUNT_AMOUNT ], true );
            $coupon->add_meta_data( ProductDiscount::LOT_DISCOUNT_QUANTITY, $data[ ProductDiscount::LOT_DISCOUNT_QUANTITY ], true );
            $coupon->save();
        }
    }

    /**
     * Update product quantity discount coupon value when product quantity discount is updated.
     *
     * @since 3.9.4
     *
     * @param int $order_id
     *
     * @return void
     */
    public function delete_product_discount_coupon_after_order_completed( int $order_id ) {
        $order = dokan()->order->get( $order_id );
        if ( ! $order ) {
            return;
        }

        foreach ( $order->get_coupons() as $order_coupon_item ) {
            $coupon = new OrderItemCouponMeta( $order_coupon_item );
            if ( $coupon->is_vendor_discount_coupon() ) {
                ( new WC_Coupon( $coupon->get_coupon_id() ) )->delete( true );
            }
        }
    }

    /**
     * Replace coupon name with the discount label.
     *
     * @since 3.9.4
     *
     * @param array             $items
     * @param WC_Order          $order
     * @param array|null|string $types
     *
     * @return array
     */
    public function replace_coupon_name( array $items, $order, $types ) {
        if ( ! in_array( 'coupon', (array) $types, true ) ) {
            return $items;
        }

        // check if the coupon is a vendor coupon discount
        foreach ( $items as $item_id => &$item ) {
            if ( ! $item instanceof WC_Order_Item_Coupon ) {
                continue;
            }

            $coupon_item_meta = new OrderItemCouponMeta( $item );

            $coupon_label = $item->get_name();
            if ( $coupon_item_meta->is_vendor_product_quantity_discount_coupon() ) {
                $coupon_label = ProductDiscount::get_coupon_label(
                    $coupon_item_meta->get_coupon_meta( ProductDiscount::LOT_DISCOUNT_AMOUNT ),
                    $coupon_item_meta->get_coupon_meta( ProductDiscount::DISCOUNT_ITEM_TITLE ),
                    $coupon_item_meta->get_coupon_meta( ProductDiscount::LOT_DISCOUNT_QUANTITY )
                );
            } elseif ( $coupon_item_meta->is_vendor_order_discount_coupon() ) {
                $vendor_id   = $coupon_item_meta->get_coupon_meta( 'coupons_vendors_ids' );
                $vendor_name = dokan()->vendor->get( $vendor_id )->get_shop_name();

                $coupon_label = OrderDiscount::get_coupon_label(
                    $coupon_item_meta->get_coupon_meta( OrderDiscount::SETTING_ORDER_PERCENTAGE ),
                    $coupon_item_meta->get_coupon_meta( OrderDiscount::SETTING_MINIMUM_ORDER_AMOUNT ),
                    $vendor_name
                );
            }

            remove_filter( 'woocommerce_coupon_code', 'wc_strtolower' );
            $item->set_name( $coupon_label );
            add_filter( 'woocommerce_coupon_code', 'wc_strtolower' );
        }

        return $items;
    }

    /**
     * Display order discounts under customer orders
     *
     * @since 3.9.4
     *
     * @param array    $table_rows
     * @param WC_Order $order
     *
     * @return array
     */
    public function display_order_discounts( $table_rows, $order ): array {
        $discounts = Helper::get_discounts_by_order( $order );

        if ( ! empty( $discounts['quantity_discount'] ) ) {
            $table_rows = dokan_array_after(
                $table_rows, 'cart_subtotal',
                [
                    'quantity_discount' => [
                        'label' => __( 'Quantity Discount:', 'dokan' ),
                        'value' => wc_price( $discounts['quantity_discount'] ),
                    ],
                ]
            );
        }

        if ( ! empty( $discounts['order_discount'] ) ) {
            $table_rows = dokan_array_after(
                $table_rows, 'cart_subtotal',
                [
                    'order_discount' => [
                        'label' => __( 'Order Discount:', 'dokan' ),
                        'value' => wc_price( $discounts['order_discount'] ),
                    ],
                ]
            );
        }

        // change discount text to discount total
        if ( ! empty( $discounts['quantity_discount'] ) || ! empty( $discounts['order_discount'] ) ) {
            $table_rows['discount']['label'] = __( 'Discount Total:', 'dokan' );
        }

        return $table_rows;
    }

    /**
     * Skip copying coupon to sub order if the coupon is not for the current vendor
     *
     * @since 3.9.4
     *
     * @param bool                 $copy
     * @param WC_Coupon            $coupon
     * @param WC_Order_Item_Coupon $item
     * @param WC_Order             $order
     *
     * @return bool
     */
    public function copy_coupon_to_sub_order( $copy, $coupon, $item, $order ) {
        $coupon_item_meta = new OrderItemCouponMeta( $item );
        if ( ! $coupon_item_meta->is_vendor_discount_coupon() ) {
            return $copy;
        }

        // check if coupon should apply for the current vendor
        $coupon_vendor_id = (int) $coupon_item_meta->get_coupon_meta( 'coupons_vendors_ids' );
        $order_vendor_id  = (int) $order->get_meta( '_dokan_vendor_id', true );
        if ( $order_vendor_id !== $coupon_vendor_id ) {
            return false;
        }

        return $copy;
    }

    /**
     * Vendor discount integration in woocommerce cart checkout block.
     *
     * @since 3.15.0
     *
     * @param \Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry $integration_registry
     *
     * @return void
     */
    public function discount_support_in_cart_checkout_block( $integration_registry ) {
        if ( version_compare( wc()->version, '8.4.0', '>=' ) ) {
            $integration_registry->register( new BlockSupportIntegration() );
        }
    }

    /**
     * Show product on cart line item.
     *
     * @since 3.11.4
     *
     * @param string $price_html
     * @param array  $cart_item
     * @param string $cart_item_key
     *
     * @return mixed|string
     */
    public function show_product_discount_on_cart_line_item( $price_html, $cart_item, $cart_item_key ) {
        $product_discount = ( new ProductDiscount() )
            ->set_cart_item_key( $cart_item_key )
            ->set_product_id( $cart_item['product_id'] )
            ->set_quantity( $cart_item['quantity'] );

        if ( $product_discount->enabled() && ! $product_discount->is_already_applied() && $product_discount->get_discount_quantity() && $product_discount->get_discount_percentage() ) {
            $remaining_qty = $product_discount->get_discount_quantity() - $product_discount->get_quantity();

            // translators: %1$s: quanity for minimum discount, %2$s: discount amount, %3$s: percentage text
            $price_html .= sprintf( _n( '(Add %1$s more item to get %2$s%3$s Discount)', '(Add %1$s more items to get %2$s%3$s Discount)', $remaining_qty, 'dokan' ), number_format_i18n( $remaining_qty ), number_format_i18n( $product_discount->get_discount_percentage() ), '%' );
        }

        return $price_html;
    }

    /**
     * Show order discount on dokan store page.
     *
     * @since 3.11.4
     *
     * @param \WP_User $store_user
     *
     * @return void
     */
    public function show_order_discount_on_store_page( $store_user ) {
        $vendor = dokan()->vendor->get( $store_user->ID );
        $order_discount = ( new OrderDiscount() )
            ->set_vendor( $vendor );

        if ( ! $order_discount->enabled() || ! $order_discount->get_discount_percentage() || ! $order_discount->get_minimum_order_amount() ) {
            return;
        }

        dokan_get_template_part(
            'vendor-discount/order-discount-store-template', '', array(
				'pro'            => true,
                'percentage'     => $order_discount->get_discount_percentage(),
                'minimum_amount' => $order_discount->get_minimum_order_amount(),
            )
        );
    }

    /**
     * Show product discount below product price label.
     *
     * @since 3.11.4
     *
     * @param string      $price_html
     * @param \WC_Product $product
     *
     * @return mixed|string
     */
    public function add_product_discount_text_below_product_price( $price_html, $product ) {
        $product_discount = ( new ProductDiscount() )
            ->set_product_id( $product->get_id() )
            ->set_item_product( $product );

        if ( $product_discount->enabled() && $product_discount->get_discount_quantity() && $product_discount->get_discount_percentage() ) {
            ob_start();

            dokan_get_template_part(
                'vendor-discount/product-discount-price-inline-template', '', array(
                    'pro'            => true,
                    'percentage'     => $product_discount->get_discount_percentage(),
                    'quantity'       => $product_discount->get_discount_quantity(),
                    'price_html'     => $price_html,
                )
            );

            return ob_get_clean();
        }

        return $price_html;
    }
}
