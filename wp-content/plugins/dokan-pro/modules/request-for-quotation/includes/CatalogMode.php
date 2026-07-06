<?php

namespace WeDevs\DokanPro\Modules\RequestForQuotation;

use WeDevs\Dokan\CatalogMode\Helper as CatalogModeHelper;

class CatalogMode {
    /**
     * @var array Catalog Mode Group Child Product
     * @since 3.7.4
     */
    protected $catalog_child_products = [];
    public $quote_rules;

    /**
     * Class Constructor
     *
     * @since 3.7.4
     */
    public function __construct() {
        if ( ! class_exists( CatalogModeHelper::class ) || ! CatalogModeHelper::is_enabled_by_admin() ) {
            return;
        }
        // Dokan Catalog Mode Integration
        add_filter( 'dokan_catalog_mode_hide_add_to_cart_button', [ $this, 'hide_add_to_cart_button_for_quote' ], 20, 2 );
        // Dokan Catalog Mode Integration
        add_filter( 'dokan_request_for_quote_add_to_cart_button_html', [ $this, 'hide_add_to_cart_button_for_quote_custom_button' ], 10, 2 );

        // Dokan Catalog Mode Integration
        add_filter( 'dokan_catalog_mode_hide_product_price', [ $this, 'hide_product_price' ], 10, 2 );

        // render extra setting fields for vendor
        add_action( 'dokan_catalog_mode_extra_settings_section', [ $this, 'render_extra_settings_fields' ], 10, 2 );
        //save Catalog Mode settings fields data
        add_filter( 'dokan_store_profile_settings_args', [ $this, 'save_settings_fields' ], 11, 2 );

        // catalog mode default vendor settings filter
        add_filter( 'dokan_catalog_mode_vendor_settings', [ $this, 'catalog_mode_vendor_settings' ], 10, 1 );

        // apply rules for catalog mode
        add_filter( 'dokan_request_a_quote_apply_rules', [ $this, 'apply_quote_rules' ], 10, 2 );
    }

    /**
     * This method will hide add to cart button for products if enabled by vendor
     *
     * @since 3.7.4
     *
     * @param bool        $purchasable
     * @param \WC_Product $product
     *
     * @return bool
     */
    public function hide_add_to_cart_button_for_quote( $purchasable, $product ) {
        global $wp_query;

        // check if enabled from settings
        $vendor_id = dokan_get_vendor_by_product( $product, true );
        if ( Helper::is_quote_support_disabled_for_catalog_mode( $vendor_id ) ) {
            return $purchasable;
        }

        if ( is_search() || ( isset( $wp_query ) && ! empty( $wp_query->get_queried_object() ) && is_shop() ) || is_product_category() || dokan_is_store_page() ) {
            return $purchasable;
        }

        if ( 'grouped' === $product->get_type() ) {
            $this->catalog_child_products = $product->get_children();
        }

        $group_child_product = false;
        if ( ! empty( $this->catalog_child_products ) && in_array( $product->get_id(), $this->catalog_child_products, true ) ) {
            $group_child_product = true;
        }

        // add dokan request a quote support
        if ( ! $group_child_product ) {
            // get quote rules
            $quote_rules = Helper::get_all_quote_rules();

            foreach ( $quote_rules as $rule ) {
                if ( RulesValidator::validate_availability( $rule, $product->get_id() ) ) {
                    // if any quote rule is matched return given value, we will handle this later
                    return 'no';
                }
            }
        }

        // return provided value
        return $purchasable;
    }

    /**
     * This method will hide Add to Cart button for quote request
     *
     * @since 3.7.4
     *
     * @param $button_html string
     * @param $product     \WC_Product
     *
     * @return string
     */
    public function hide_add_to_cart_button_for_quote_custom_button( $button_html, $product ) {
        // check if enabled by product
        if ( CatalogModeHelper::is_enabled_for_product( $product ) ) {
            return ''; // per product settings to hide add to cart button is enabled
        }

        // check if enabled by vendor global settings
        $vendor_id = dokan_get_vendor_by_product( $product, true );
        if ( ! $vendor_id ) {
            return $button_html;
        }

        if ( CatalogModeHelper::is_enabled_by_vendor( $vendor_id ) ) {
            return ''; // vendor global settings to hide add to cart button is enabled
        }

        // return provided value
        return $button_html;
    }

    /**
     * This method will show/hide product price for quote module
     *
     * @since 3.7.4
     *
     * @param string      $purchasable
     * @param \WC_Product $product
     *
     * @return mixed|string
     */
    public function hide_product_price( $purchasable, $product ) {
        // check if enabled from settings
        $vendor_id = dokan_get_vendor_by_product( $product, true );
        if ( Helper::is_quote_support_disabled_for_catalog_mode( $vendor_id ) ) {
            return $purchasable; // if disabled return provided value
        }

        // For shop single page loop main product.
        if ( 'grouped' === $product->get_type() ) {
            $this->catalog_child_products = $product->get_children();

            return $purchasable;
        }

        // For shop single page loop child product.
        if ( ! empty( $this->catalog_child_products ) && in_array( $product->get_id(), $this->catalog_child_products, true ) ) {
            return $purchasable;
        }

        if ( 'variation' === $product->get_type() ) {
            $product_id = $product->get_parent_id();
            $product    = wc_get_product( $product_id );
        }

        if ( empty( $this->quote_rules ) ) {
            $this->quote_rules = Helper::get_all_quote_rules();
        }

        foreach ( $this->quote_rules as $rule ) {
            if ( ! RulesValidator::validate_availability( $rule, $product->get_id() ) ) {
                continue;
            }
            if ( $rule->hide_price ) {
                return 'yes';
            } else {
                return 'no';
            }
        }

        return $purchasable;
    }

    /**
     * This method will determine if quote rules will apply based on catalog mode settings
     *
     * @since 3.7.4
     *
     * @param bool $apply
     * @param \WC_Product $product
     *
     * @return bool
     */
    public function apply_quote_rules( $apply, $product ) {
        // check if enabled from settings
        $vendor_id = dokan_get_vendor_by_product( $product, true );
        if (
            CatalogModeHelper::is_enabled_by_admin()
            && CatalogModeHelper::is_enabled_by_vendor( $vendor_id )
        ) {
            return ! Helper::is_quote_support_disabled_for_catalog_mode( $vendor_id );
        }
        return $apply;
    }

    /**
     * Render Request A Quote setting fields for vendor
     *
     * @since 3.7.4
     *
     * @param int $user_id
     * @param array $catalog_mode_data
     *
     * @return void
     */
    public function render_extra_settings_fields( $user_id, $catalog_mode_data ) {
        $quote_support = isset( $catalog_mode_data['request_a_quote_enabled'] ) ? $catalog_mode_data['request_a_quote_enabled'] : 'off';
        ?>
        <div class="dokan-form-group">
            <label class="dokan-w3 dokan-control-label" for="catalog_mode_request_a_quote_support"><?php esc_html_e( 'Enable Request Quote Support', 'dokan' ); ?></label>
            <div class="dokan-w5 dokan-text-left">
                <label for="catalog_mode_request_a_quote_support">
                    <input type="checkbox" id="catalog_mode_request_a_quote_support" value="on" name="catalog_mode[request_a_quote_enabled]"
                        <?php checked( $quote_support, 'on' ); ?> />
                    <span> <?php esc_html_e( 'Check to add Request Quote support for your products.', 'dokan' ); ?></span>
                </label>
            </div>
        </div>
        <?php
    }

    /**
     * This method will save settings fields for Catalog Mode
     *
     * @since 3.7.4
     *
     * @param int   $store_id
     * @param array $dokan_settings
     *
     * @return array
     */
    public function save_settings_fields( $dokan_settings, $store_id ) {
        if ( ! isset( $_POST['_dokan_catalog_mode_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_dokan_catalog_mode_nonce'] ), 'dokan_catalog_mode_settings_action' ) ) {
            return $dokan_settings;
        }

        if ( ! dokan_is_user_seller( $store_id ) ) {
            return $dokan_settings;
        }

        $dokan_settings['catalog_mode']['request_a_quote_enabled'] = isset( $_POST['catalog_mode']['request_a_quote_enabled'] ) ? 'on' : 'off';

        // set hide price to off if add to cart button is off
        if ( ! isset( $_POST['catalog_mode']['hide_add_to_cart_button'] ) ) {
            $dokan_settings['catalog_mode']['request_a_quote_enabled'] = 'off';
        }

        return $dokan_settings;
    }

    /**
     * This method will add default settings field for Catalog Mode
     *
     * @since 3.7.4
     *
     * @param string[] $catalog_mode_settings
     *
     * @return string[]
     */
    public function catalog_mode_vendor_settings( $catalog_mode_settings ) {
        if ( ! isset( $catalog_mode_settings['request_a_quote_enabled'] ) ) {
            $catalog_mode_settings['request_a_quote_enabled'] = 'off';
        }
        return $catalog_mode_settings;
    }
}
