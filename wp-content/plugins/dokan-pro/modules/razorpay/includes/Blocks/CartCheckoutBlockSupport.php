<?php

namespace WeDevs\DokanPro\Modules\Razorpay\Blocks;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Automattic\WooCommerce\StoreApi\Payments\PaymentContext;
use Automattic\WooCommerce\StoreApi\Payments\PaymentResult;
use WeDevs\DokanPro\Modules\Razorpay\Helper;

class CartCheckoutBlockSupport extends AbstractPaymentMethodType {

    /**
     * Payment method name defined by payment methods extending this class.
     *
     * @since 4.0.0
     *
     * @var string
     */
    protected $name = 'dokan_razorpay';

    /**
     * Payment method handle.
     *
     * @since 4.0.0
     *
     * @var string
     */
    protected $handle = 'razorpay-checkout-blocks-integration';

    /**
     * Class constructor.
     *
     * @since 3.15.0
     *
     * @return void
     */
	public function __construct() {
        $this->name = Helper::get_gateway_id();
        add_action( 'woocommerce_rest_checkout_process_payment_with_context', [ $this, 'add_payment_request_order_meta' ], 8, 2 );
	}

    /**
     * Add payment request order meta.
     *
     * @since 3.15.0
     *
     * @param \Automattic\WooCommerce\StoreApi\Payments\PaymentContext $context
     * @param \Automattic\WooCommerce\StoreApi\Payments\PaymentResult  $result
     *
     * @throws \Automattic\WooCommerce\StoreApi\Exceptions\RouteException
     * @return void
     */
    public function add_payment_request_order_meta( PaymentContext $context, PaymentResult &$result ) {
        if ( 'dokan_razorpay' === $context->payment_method ) {
            $items             = $context->order->get_items();
            $available_vendors = [];

            foreach ( $items as $item ) {
                /** @var \WC_Order_Item_Product $item */
                $product_id = $item->get_product_id();
                if ( Helper::should_skip_product( $product_id ) ) {
					continue;
				}
                $available_vendors[ get_post_field( 'post_author', $product_id ) ][] = wc_get_product( $product_id );
            }

            foreach ( array_keys( $available_vendors ) as $vendor_id ) {
                if ( ! Helper::is_seller_enable_for_receive_payment( $vendor_id ) ) {
                    $vendor_products = [];
                    foreach ( $available_vendors[ $vendor_id ] as $product ) {
                        $vendor_products[] = sprintf( '<a href="%s" target="_blank">%s</a>', $product->get_permalink(), $product->get_name() );
                    }

                    throw new RouteException(
                        'dokan_rest_checkout_invalid_payment_razorpay-not-configured',
                        wp_kses(
                            sprintf(
                            /* translators: 1: Vendor products */
                                __( '<strong>Error!</strong> Remove product %s and continue checkout, this product/vendor is not eligible to be paid with Razorpay', 'dokan' ),
                                implode( ', ', $vendor_products )
                            ),
                            [
                                'strong' => [],
                                'a'      => [
                                    'href' => [],
                                    'target' => [],
                                ],
                            ]
                        ),
                        401
                    );
                }
            }
        }
    }

    /**
     * This function will get called during the server side initialization process and is a good place to put any settings population etc.
     * Basically anything you need to do to initialize your gateway. Note, this will be called on every request so don't put anything expensive here.
     *
     * @since 3.15.0
     *
     * @return void
     */
    public function initialize() {}

    /**
     * This should return whether the payment method is active or not.
     *
     * @since 3.15.0
     *
     * @return bool
     */
    public function is_active() {
        $payment_activated = false;

        $gateways = WC()->payment_gateways->payment_gateways();

        if ( isset( $gateways[ $this->name ] ) ) {
            $payment_activated = 'yes' === $gateways[ $this->name ]->enabled;
        }

        if ( $payment_activated ) {
            return true;
        }

        return false;
    }

    /**
     * Returns an array of scripts/handles to be registered for this payment method.
     *
     * @since 3.15.0
     *
     * @return array
     */
    public function get_payment_method_script_handles() {
        if ( is_checkout() || is_cart() ) {
            wp_enqueue_style( $this->handle );
        }

        $asset_path   = DOKAN_RAZORPAY_PATH . 'assets/blocks/payment-support/index.asset.php';
        $version      = dokan_pro()->version;
        $dependencies = [];
        if ( file_exists( $asset_path ) ) {
            $asset        = require $asset_path;
            $version      = is_array( $asset ) && isset( $asset['version'] )
                ? $asset['version']
                : $version;
            $dependencies = is_array( $asset ) && isset( $asset['dependencies'] )
                ? $asset['dependencies']
                : $dependencies;
        }
        wp_register_script(
            $this->handle,
            DOKAN_RAZORPAY_ASSETS . 'blocks/payment-support/index.js',
            $dependencies,
            $version,
            true
        );

        wp_register_style(
            $this->handle,
            DOKAN_RAZORPAY_ASSETS . 'blocks/payment-support/style-index.css',
        );

        return [ $this->handle ];
    }

    /**
     * Return associative array of data you want to be exposed for your payment method client side.
     * This data will be available client side via wc.wcSettings.getSetting.
     *
     * @since 3.15.0
     *
     * @return array
     */
    public function get_payment_method_data() {
        return [
            'icon'        => apply_filters( 'woocommerce_razorpay_icon', DOKAN_RAZORPAY_ASSETS . 'images/razorpay.png' ),
            'title'       => Helper::get_gateway_title(),
            'description' => Helper::get_settings( 'description' ),
        ];
    }
}
