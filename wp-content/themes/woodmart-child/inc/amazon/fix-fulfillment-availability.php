<?php
/**
 * Fix Amazon Fulfillment Availability Error
 * 
 * This fixes the issue where AMAZON_NA (a fulfillment center ID) is incorrectly
 * being sent as the fulfillment_channel_code in the fulfillment_availability field.
 * 
 * The correct value for fulfillment_channel_code should be 'AMAZON_FULFILLMENT' for FBA items.
 */

add_filter('wpla_listing_feed_column_value', 'fix_amazon_fulfillment_availability', 10, 4);

function fix_amazon_fulfillment_availability($value, $column, $item, $profile) {
    // Only process fulfillment_availability fields
    if (strpos($column, 'fulfillment_availability') === false) {
        return $value;
    }
    
    // Check if this is the fulfillment_channel_code field
    if (strpos($column, 'fulfillment_channel_code') !== false) {
        // If the value is a fulfillment center ID like AMAZON_NA, AMAZON_EU, etc.
        // Replace it with the correct channel code
        if (in_array($value, ['AMAZON_NA', 'AMAZON_EU', 'AMAZON_CA', 'AMAZON_IN', 'AMAZON_AU'])) {
            error_log("WP-Lister Fix: Replacing fulfillment_channel_code '$value' with 'AMAZON_FULFILLMENT'");
            return 'AMAZON_FULFILLMENT';
        }
    }
    
    return $value;
}
