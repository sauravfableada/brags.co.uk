<?php

namespace WeDevs\DokanPro\Modules\RequestForQuotation;

/**
 * Quote Rules Validator Class.
 *
 * @since 3.12.3
 */
class RulesValidator {

    /**
     * Quotation rule.
     *
     * @since 3.12.3
     *
     * @var object $rule
     */
    protected object $rule;

    /**
     * Quotation rule.
     *
     * @since 3.12.3
     *
     * @var array $rule_contents
     */
    protected array $rule_contents;

    /**
     * Product id.
     *
     * @since 3.12.3
     *
     * @var int $product_id
     */
    protected int $product_id;

    /**
     * Class constructor.
     *
     * @since 3.12.3
     *
     * @param object $rule
     * @param int    $product_id
     */
    public function __construct( $rule, $product_id ) {
        $this->rule          = $rule;
        $this->product_id    = $product_id;
        $this->rule_contents = ! empty( $rule->rule_contents ) ? (array) maybe_unserialize( $rule->rule_contents ) : [];
    }

    /**
     * Validate quote availability for product.
     *
     * @since 3.12.3
     *
     * @param object $rule
     * @param int    $product_id
     *
     * @return bool
     */
    public static function validate_availability( $rule, $product_id ) {
        // Initialize the rule's validator.
        $validator  = new self( $rule ?? (object) [], $product_id );
        $user_roles = (array) ( $validator->rule_contents['selected_user_role'] ?? [] );

        // Check if the rule applies to a guest user.
        if ( ! is_user_logged_in() && in_array( 'guest', $user_roles, true ) ) {
            return $validator->validate_product_quote_availability();
        }

        $current_user      = wp_get_current_user();
        $current_user_caps = array_keys( $current_user->caps );
        if ( array_intersect( $current_user_caps, $user_roles ) && $validator->validate_product_quote_availability() ) {
            return true;
        }

        // Check the primary role of the user.
        return in_array( current( $current_user->roles ), $user_roles, true ) && $validator->validate_product_quote_availability();
    }

    /**
     * Validate a product against quote rules.
     *
     * This method checks if a given product satisfies the specified quote rules.
     *
     * @since 3.12.3
     *
     * @return bool Returns true if the product satisfies the quote rules, otherwise false.
     */
    public function validate_product_quote_availability(): bool {
        // Check if the rule applies to all products.
        if ( $this->rule->apply_on_all_product ) {
            return true;
        }

        // Check product-specific rules.
        $validate_product = $this->validate_product_wise_rules();
        if ( $validate_product !== null ) {
            return $validate_product;
        }

        // Check vendor-specific rules.
        $validate_store = $this->validate_vendor_wise_rules();
        if ( $validate_store !== null ) {
            return $validate_store;
        }

        // Check category-specific rules.
        $validate_category = $this->validate_category_rules();
        if ( $this->validate_category_rules() ) {
            return $validate_category;
        }

        return false;
    }

    /**
     * Validate product wise rules.
     *
     * @since 3.12.3
     *
     * @return bool|null
     */
    public function validate_product_wise_rules(): ?bool {
        // Check product-specific rules.
        $product_switch = $this->get_boolean_switch( 'product_switch' );
        if ( ! $product_switch ) {
            return null;
        }

        // Check if the product is excluded.
        $excluded_product = in_array( $this->product_id, (array) $this->rule_contents['exclude_product_ids'], true );
        if ( $excluded_product ) {
            return false;
        }

        // Check if the product is included.
        $included_product = in_array( $this->product_id, (array) $this->rule_contents['product_ids'], true );
        if ( $included_product ) {
            return true;
        }

        return null;
    }

    /**
     * Validate vendor wise rules.
     *
     * @since 3.12.3
     *
     * @return bool|null
     */
    public function validate_vendor_wise_rules(): ?bool {
        // Check vendor-specific rules.
        $vendor_switch = $this->get_boolean_switch( 'vendor_switch' );
        if ( ! $vendor_switch ) {
            return null;
        }

        $seller_id      = dokan_get_vendor_by_product( $this->product_id, true );
        $excluded_store = in_array( $seller_id, (array) $this->rule_contents['exclude_store_ids'], true );

        // Check if the vendor's store is excluded.
        if ( $excluded_store ) {
            return false;
        }

        // Check if the vendor's store isn't included.
        $included_store = in_array( $seller_id, (array) $this->rule_contents['store_ids'], true );
        if ( $included_store ) {
            return true;
        }

        return null;
    }

    /**
     * Validate category rules.
     *
     * @since 3.12.3
     *
     * @return bool
     */
    public function validate_category_rules(): ?bool {
        // Check category-specific rules.
        $category_switch = $this->get_boolean_switch( 'category_switch' );
        if ( ! $category_switch ) {
            return null;
        }

        // Make invalidate rule if category empty.
        if ( empty( $this->rule_contents['category_ids'] ) ) {
            return false;
        }

        // Validate categories based on rules.
        foreach ( (array) $this->rule_contents['category_ids'] as $cat ) {
            if ( has_term( $cat, 'product_cat', $this->product_id ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retrieve the boolean value of a specified switch.
     *
     * This method checks if the given switch name exists in the 'switches' array
     * within the rule contents and returns its boolean value.
     *
     * @param string $switch_name The name of the switch to retrieve.
     *
     * @return bool The boolean value of the specified switch. Returns false if the switch
     *              is not set or if it cannot be validated as a boolean.
     */
    public function get_boolean_switch( string $switch_name ): bool {
        return ! empty( $this->rule_contents['switches'][ $switch_name ] ) &&
            filter_var( $this->rule_contents['switches'][ $switch_name ], FILTER_VALIDATE_BOOLEAN ); // Check if the switch is set and validate it as a boolean.
    }
}
