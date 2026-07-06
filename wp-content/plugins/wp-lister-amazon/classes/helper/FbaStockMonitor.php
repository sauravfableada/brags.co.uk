<?php

namespace WPLab\Amazon\Helper;

/**
 * FBA Stock Monitor Class
 * 
 * Monitors changes in FBA stock quantities and triggers fallback notifications
 * when items should convert from FBA to FBM (Fulfilled by Merchant).
 */
class FbaStockMonitor {

    public const SOURCE_IMPORT_HELPER = 'import_helper';
    public const SOURCE_MONITOR = 'monitor';
    public const SOURCE_MANUAL = 'manual';

    public function __construct() {
        // Hook into listing updates to monitor FBA stock changes
        add_action('wpla_after_listing_update', [$this, 'monitorFbaStockChange'], 10, 3);
        
        // Hook into explicit FBA fallback detection from ImportHelper
        add_action('wpla_fba_fallback_detected', [$this, 'handleFallbackDetected'], 10, 3);
    }

    /**
     * Monitor FBA stock changes and detect fallback conditions
     * 
     * @param int $listing_id Listing ID that was updated
     * @param array $update_data Data that was updated
     * @param array $where Where conditions used for update
     */
    public function monitorFbaStockChange( $listing_id, $update_data, $where ) {
        // Only process if fba_quantity was updated
        if (!isset($update_data['fba_quantity'])) {
            return;
        }

        // Get updated listing data
        $lm = new \WPLA_ListingsModel();
        $listing = $lm->getItem($listing_id);
        
        if (!$listing) {
            return;
        }

        // Check if this listing should fallback to FBM
        if ( $this->shouldFallbackToFBM( $listing, (int) $update_data['fba_quantity'] ) ) {
            $this->processFallbackConversion( $listing, self::SOURCE_MONITOR );
        }
    }

    /**
     * Handle explicit FBA fallback detection from ImportHelper
     * 
     * @param object $listing_item Listing object from import process
     * @param string $sku SKU being processed
     * @param int $account_id Account ID
     */
    public function handleFallbackDetected( $listing_item, $sku, $account_id ) {
        \WPLA()->logger->info( "Direct FBA fallback detected during import for SKU: {$sku}" );
        
        $this->processFallbackConversion( $listing_item, self::SOURCE_IMPORT_HELPER );
    }

    /**
     * Determine if a listing should fallback from FBA to FBM
     * 
     * @param object $listing Listing object
     * @param int $new_fba_quantity New FBA quantity
     * @return bool True if should fallback
     */
    private function shouldFallbackToFBM( $listing, $new_fba_quantity ) {
        // Get fallback settings
        $fba_enable_fallback = get_option('wpla_fba_enable_fallback', 0);
        $fba_only_mode = get_option('wpla_fba_only_mode', 0);
        $fba_stock_sync = get_option('wpla_fba_stock_sync', 0);

        // Check basic fallback conditions
        if ( !$fba_enable_fallback || $fba_only_mode || $fba_stock_sync ) {
            return false;
        }

        // Must have zero FBA quantity to trigger fallback
        if ( $new_fba_quantity > 0 ) {
            return false;
        }

        // Must have local WooCommerce stock available
        if ( $listing->quantity <= 0 ) {
            return false;
        }

        // Must currently be FBA (not already FBM)
        if ( $listing->fba_fcid === 'DEFAULT' || empty( $listing->fba_fcid ) ) {
            return false;
        }

        // Check for product-level FBA override
        $fba_overwrite = get_post_meta( $listing->post_id, '_amazon_fba_overwrite', true );
        if ( $fba_overwrite === 'FBA' ) {
            return false;
        }

        // Listing must be in a state that allows conversion
        if ( in_array( $listing->status, ['trash', 'trashed', 'ended'], true ) ) {
            return false;
        }

        return true;
    }

    /**
     * Process FBA to FBM conversion using direct PatchListingsItem API
     * 
     * @param mixed $listing Listing object or array that needs conversion
     * @param string $source Source of the detection
     * @return bool True if successful, false on failure
     */
    public function processFallbackConversion( $listing, $source = self::SOURCE_MONITOR ) {
        try {
            // Normalize listing data to object format
            $listing_obj = is_array($listing) ? (object) $listing : $listing;
            
            \WPLA()->logger->info("Processing immediate FBA-FBM conversion for SKU: {$listing_obj->sku}, source: {$source}");
            
            // Get account information
            $account = \WPLA_AmazonAccount::getAccount($listing_obj->account_id);
            if (!$account) {
                \WPLA()->logger->error("Cannot process conversion: Account not found - ID: {$listing_obj->account_id}");
                return false;
            }

            // Get original FBA fulfillment center ID for DELETE operation
            $original_fba_fcid = $listing_obj->fba_fcid ?: get_option( 'wpla_fba_fulfillment_center_id', 'AMAZON_NA' );
            
            // Create patch operations for fulfillment conversion
            $patch_operations = [
                // Delete existing FBA fulfillment (DELETE operation must not have a value)
                [
                    'op' => 'delete',
                    'path' => '/attributes/fulfillment_availability'
                ],
                // Replace with FBM fulfillment
                [
                    'op' => 'replace',
                    'path' => '/attributes/fulfillment_availability',
                    'value' => [
                        [
                            'fulfillment_channel_code' => 'DEFAULT',
                            'quantity' => (int) $listing_obj->quantity
                        ]
                    ]
                ]
            ];

            // Initialize SP-API client
            $sp_api = new \WPLA_Amazon_SP_API($listing_obj->account_id);
            
            // Call PatchListingsItem directly
            $result = $sp_api->patchListingsItem(
                $listing_obj->sku,
                'PRODUCT', // Product type
                $patch_operations,
                null, // Use default marketplaces
                3 // Default retry attempts
            );

            if ($result && isset($result->success) && $result->success) {
                // Update listing in database after successful API call
                $lm = new \WPLA_ListingsModel();
                $lm->updateListing($listing_obj->id, [
                    'fba_fcid' => 'DEFAULT'
                ]);
                
                \WPLA()->logger->info("Completed FBA-FBM conversion via direct API for SKU: {$listing_obj->sku}");
                
                // Fire action hook for extensibility
                do_action('wpla_fba_fallback_processed', $listing_obj, $result);
                
                return true;
            } else {
                $error_msg = isset($result->ErrorMessage) ? $result->ErrorMessage : 'Unknown error';
                \WPLA()->logger->error("Failed FBA-FBM conversion for SKU: {$listing_obj->sku} - {$error_msg}");
                return false;
            }
            
        } catch (\Exception $e) {
            \WPLA()->logger->error("Exception during FBA fallback processing: " . $e->getMessage());
            return false;
        }
    }

}