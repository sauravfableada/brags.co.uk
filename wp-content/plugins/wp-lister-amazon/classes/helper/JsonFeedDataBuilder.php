<?php

namespace WPLab\Amazon\Helper;

use WPLA_AmazonProfile;

class JsonFeedDataBuilder extends \WPLA_FeedDataBuilder {

	private $schema_cache = [];
	private $profile_cache = [];
	private $languages_cache = [];
	private $intentionally_empty_fields = []; // Track fields set to [---] placeholder

	public const OPERATION_UPDATE           = 'UPDATE';
	public const OPERATION_PARTIAL_UPDATE   = 'PARTIAL_UPDATE';
	public const OPERATION_PATCH            = 'PARTIAL_PATCH';
	public const OPERATION_DELETE           = 'DELETE';

	public static function getMarketplaceIdFromTemplateId( $tpl_id ) {
		global $wpdb;

		$sql = "SELECT markets.marketplace_id
				FROM {$wpdb->prefix}amazon_markets markets,
					{$wpdb->prefix}amazon_feed_templates templates
				WHERE templates.id = %d
				AND templates.site_id = markets.id";
		return $wpdb->get_var( $wpdb->prepare($sql, $tpl_id));
	}

	public function buildPriceAndQuantityJson( $items, $account) {
		$data = [
			'header' => [
				'sellerId' => $account->merchant_id,
				'version'   => '2.0'
			],
			'messages' => []
		];

		$columns = $this->getPriceAndQuantityFields();

		$max_feed_size = get_option( 'wpla_max_feed_size', 1000 );
		$msg_id = 0;

		// BATCH LOADING: Extract all IDs first
		$product_ids = [];
		$profile_ids = [];
		foreach ( $items as $item ) {
			if ( ! empty( $item['post_id'] ) ) {
				$product_ids[] = $item['post_id'];
			}
			if ( ! empty( $item['profile_id'] ) ) {
				$profile_ids[] = $item['profile_id'];
			}
		}

		// BATCH LOADING: Load all products and profiles in advance
		$products_batch = $this->load_products_batch( $product_ids );
		$profiles_batch = $this->load_profiles_batch( $profile_ids );

		foreach ( $items as $item ) {
			if ( $msg_id >= $max_feed_size ) {
				WPLA()->logger->info( 'max_feed_size reached. Breaking.');
				break;
			}

			// get WooCommerce product data from batch-loaded cache
			$product_id = $item['post_id'] ?? null;
			$product = $this->get_batch_product( $products_batch, $product_id );

			if ( ! $product ) continue;
			if ( ! $item['sku'] ) continue;

			// load profile fields from batch-loaded cache
			$profile = $this->get_batch_profile( $profiles_batch, $item['profile_id'] );

			$attributes = $this->getAttributes( $item, $profile, $columns );

			// Skip variable/parent products - they should never be in P&Q feeds regardless of attributes
			if ( $product->get_type() == 'variable' ) {
				WPLA_ListingsModel::updateWhere(
					array( 'id' => $item['id'] ),
					array( 'pnq_status' => 0 )
				);
				continue; // skip parent variations in P&Q feed
			}

			// skip empty attributes
			if ( empty( $attributes ) ) {
				continue;
			}

			$msg_id++;
			$message = [
				'messageId'     => $msg_id,
				'sku'           => $item['sku'],
				'operationType' => 'PARTIAL_UPDATE',
				'productType'   => 'PRODUCT',
				'attributes'    => $attributes
			];
			$data['messages'][] = $message;
		}

		if ( empty( $data['messages'] ) ) {
			return false;
		}

		return json_encode( $data, JSON_UNESCAPED_UNICODE );
	}

	/**
	 * Build JSON feed for deleting listings (similar to CSV add-delete column with 'x' value)
	 *
	 * @param array $items
	 * @param \WPLA_AmazonAccount $account
	 * @return string|false
	 */
	public function buildDeleteListingsJson( $items, $account ) {
		$data = [
			'header' => [
				'sellerId' => $account->merchant_id,
				'version'   => '2.0'
			],
			'messages' => []
		];

		$max_feed_size = get_option( 'wpla_max_feed_size', 1000 );
		$msg_id = 0;

		foreach ( $items as $item ) {
			if ( $msg_id >= $max_feed_size ) {
				WPLA()->logger->info( 'max_feed_size reached. Breaking.');
				break;
			}

			// Only process items with trash status
			if ( $item['status'] != 'trash' ) {
				continue;
			}

			if ( ! $item['sku'] ) {
				WPLA()->logger->info('Skipping item without SKU for deletion: ID ' . $item['id']);
				continue;
			}

			$msg_id++;
			$message = [
				'messageId'     => $msg_id,
				'sku'           => $item['sku'],
				'operationType' => self::OPERATION_DELETE
			];
			$data['messages'][] = $message;

			WPLA()->logger->info('Added deletion message for SKU: ' . $item['sku']);
		}

		if ( empty( $data['messages'] ) ) {
			WPLA()->logger->info('No items to delete found in buildDeleteListingsJson()');
			return false;
		}

		WPLA()->logger->info('Built delete feed with ' . count($data['messages']) . ' messages');
		return json_encode( $data, JSON_UNESCAPED_UNICODE );
	}

	/**
	 * Add pending items to a JSON feed. This method will add items until the feed reaches its max feed size limit
	 *
	 * @param array $items
	 * @param \WPLA_AmazonAccount $account
	 * @param string $operation
	 * @param string $product_type
	 * @param array $feed_attributes
	 *
	 * @return void
	 */
	public function addItemsToFeed( $items, $account, $operation = JsonFeedDataBuilder::OPERATION_UPDATE, $product_type = null, $feed_attributes = [], $feed_name = '' ) {
		WPLA()->logger->info('addItemsToFeed() - account id: '.$account->id);

		// Handle FBA_TO_FBM_CONVERSION feeds with patch-based format
		if ( $feed_name === 'FBA_TO_FBM_CONVERSION' ) {
			return $this->addFbaToFbmConversionFeed( $items, $account, $product_type, $feed_name );
		}
		// Handle OFFER_CLEARING feeds with patch-based format
		if ( $feed_name === 'OFFER_CLEARING' ) {
			// Group items by their profile account_id to ensure correct account usage
			$items_by_account = $this->groupItemsByProfileAccount( $items, $account );

			// Create separate PATCH feeds for each account
			foreach ( $items_by_account as $account_id => $account_items ) {
				$target_account = WPLA_AmazonAccount::getAccount( $account_id );
				if ( ! $target_account ) {
					WPLA()->logger->error('Could not load account ' . $account_id . ', skipping ' . count($account_items) . ' items');
					continue;
				}
				WPLA()->logger->info('Processing ' . count($account_items) . ' OFFER_CLEARING items for account ' . $account_id);
				$this->addOfferClearingFeed( $account_items, $target_account, $product_type, $feed_name );
			}
			return;
		}

		// For regular P&Q feeds, check if we need to segregate offer clearing items
		// Only do this if no specific feed name is provided (automatic P&Q processing)
		if ( empty( $feed_name ) && $operation === self::OPERATION_UPDATE ) {
			$segregated = $this->segregateOfferClearingItems( $items, $account );
			
			// Process offer clearing items separately if any found
			if ( ! empty( $segregated['offer_clearing'] ) ) {
				WPLA()->logger->info('Found ' . count($segregated['offer_clearing']) . ' items needing offer clearing');

				// Group segregated offer clearing items by their profile account_id
				$items_by_account = $this->groupItemsByProfileAccount( $segregated['offer_clearing'], $account );

				// Create separate PATCH feeds for each account
				foreach ( $items_by_account as $account_id => $account_items ) {
					$target_account = WPLA_AmazonAccount::getAccount( $account_id );
					if ( ! $target_account ) {
						WPLA()->logger->error('Could not load account ' . $account_id . ', skipping ' . count($account_items) . ' segregated items');
						continue;
					}
					WPLA()->logger->info('Processing ' . count($account_items) . ' segregated offer clearing items for account ' . $account_id);
					$this->addOfferClearingFeed( $account_items, $target_account, $product_type, 'OFFER_CLEARING' );
				}
			}
			
			// Continue with regular P&Q items (if any)
			$items = $segregated['regular_pnq'];
			if ( empty( $items ) ) {
				WPLA()->logger->info('All items routed to offer clearing, no regular P&Q items to process');
				return;
			}
		}

		$current_messages   = [];
		$pending_messages   = $this->getMessagesFromItems( $items, $account, $operation, $product_type, $feed_attributes );
		$max_feed_size      = get_option( 'wpla_max_feed_size', 1000 );
		$msg_id = 0;

		foreach ( $pending_messages as $messages_idx => $message ) {
			$msg_id++;

			$message['messageId'] = $msg_id;
			$current_messages[] = $message;

			unset( $pending_messages[ $messages_idx ] );

			if ( $msg_id >= $max_feed_size ) {
				WPLA()->logger->info( 'max_feed_size reached. Breaking.');
				break;
			}
		}

		if ( empty( $current_messages ) ) {
			return;
		}

		$feed = $this->getAvailableFeed( $account, $product_type, $feed_name );
		$feed_data = json_decode( $feed->data, true );
		$feed_data['messages'] = $current_messages;

		$feed->product_type = $product_type;
		$feed->data = json_encode($feed_data, JSON_UNESCAPED_UNICODE);
		$feed->line_count = count($current_messages);
		$feed->update();
	}

	/**
	 * Remove B2B pricing from listings using PATCH feed
	 * This creates a PATCH feed that replaces purchasable_offer with B2C-only offer
	 *
	 * @param array $items Array of listing items to remove B2B pricing from
	 * @param \WPLA_AmazonAccount $account Amazon account object
	 * @param string $product_type Product type (defaults to PRODUCT)
	 * @return void
	 */
	public function removeB2BPricing( $items, $account, $product_type = 'PRODUCT' ) {
		WPLA()->logger->info('removeB2BPricing() called for ' . count($items) . ' items - account id: ' . $account->id);
		
		// Create the B2B price deletion feed (will filter items internally)
		$this->addB2BPriceDeletionFeed( $items, $account, $product_type, 'B2B_PRICE_DELETION' );
	}

	/**
	 * Clear Amazon offers when sale prices are removed using PATCH feed
	 * This creates a PATCH feed that removes discounted_price attributes while preserving regular price
	 *
	 * @param array $items Array of listing items to clear offers from
	 * @param \WPLA_AmazonAccount $account Amazon account object
	 * @param string $product_type Product type (defaults to PRODUCT)
	 * @return void
	 */
	public function clearOffers( $items, $account, $product_type = 'PRODUCT' ) {
		WPLA()->logger->info('clearOffers() called for ' . count($items) . ' items - account id: ' . $account->id);
		
		// Create the offer clearing feed
		$this->addOfferClearingFeed( $items, $account, $product_type, 'OFFER_CLEARING' );
	}

	/**
	 * Handle FBA to FBM conversion using patch-based JSON format
	 * 
	 * @param array $items Listings to convert
	 * @param \WPLA_AmazonAccount $account Account object
	 * @param string $product_type Product type
	 * @param string $feed_name Feed name
	 * @return void
	 */
	private function addFbaToFbmConversionFeed( $items, $account, $product_type, $feed_name ) {
		WPLA()->logger->info('Creating patch-based FBA-FBM conversion feed');

		$max_feed_size = get_option( 'wpla_max_feed_size', 1000 );
		$patches = [];
		$processed = 0;

		// Process only the first item for now (single-SKU conversion per feed)
		if ( !empty( $items ) ) {
			$item = $items[0];
			WPLA()->logger->info('Building FBA-FBM patch for item #'. $item['post_id'] . ', SKU: ' . $item['sku']);

			// Get the original FBA fulfillment center ID to delete (preserved from before conversion)
			$current_fba_fcid = $item['original_fba_fcid'] ?: get_option( 'wpla_fba_fulfillment_center_id', 'AMAZON_NA' );
			
			// First: DELETE the existing FBA fulfillment channel (DELETE operation must not have a value)
			$patches[] = [
				'op' => 'delete',
				'path' => '/attributes/fulfillment_availability'
			];
			
			// Second: REPLACE with merchant fulfillment (DEFAULT)
			$patches[] = [
				'op' => 'replace',
				'path' => '/attributes/fulfillment_availability',
				'value' => [
					[
						'fulfillment_channel_code' => 'DEFAULT',
						'quantity' => (int) $item['quantity']
					]
				]
			];
			
			$processed = 1;
		}

		if ( empty( $patches ) ) {
			WPLA()->logger->info( 'No patches to process for FBA-FBM conversion');
			return;
		}

		// Always create a new feed for FBA-FBM conversions (don't batch them)
		$feed = $this->createEmptyFeed( $account, $product_type, $feed_name );
		
		// Set operation type for PATCH feeds
		$feed->template_name = $feed_name . ' (PATCH)';
		$feed->operation = self::OPERATION_PATCH;
		
		// Collect SKUs for metadata
		$skus = [];
		if ( !empty( $items ) ) {
			$skus[] = $items[0]['sku']; // FBA-FBM conversion only processes first item
		}

		// Build proper JSON Listings Feed structure with header and messages
		$feed_data = [
			'header' => [
				'sellerId' => $account->merchant_id,
				'version'  => '2.0'
			],
			'messages' => [
				[
					'messageId'     => 1,
					'sku'           => $items[0]['sku'],
					'operationType' => 'PATCH',
					'productType'   => $product_type,
					'patches'       => $patches
				]
			]
		];

		$feed->product_type = $product_type;
		$feed->data = json_encode($feed_data);
		$feed->line_count = $processed;

		// Store SKU information in feedOptions for table display
		$feed->feedOptions = maybe_serialize( array( 'patch_skus' => $skus ) );

		$feed->update();

		WPLA()->logger->info('Created patch-based FBA-FBM conversion feed with ' . $processed . ' item(s)');
	}

	/**
	 * Create a PATCH feed to remove B2B pricing from listings
	 * This replaces the entire purchasable_offer array with B2C-only offer
	 *
	 * @param array $items Array of items to remove B2B pricing from
	 * @param \WPLA_AmazonAccount $account Amazon account object
	 * @param string $product_type Product type
	 * @param string $feed_name Feed name
	 * @return void
	 */
	private function addB2BPriceDeletionFeed( $items, $account, $product_type, $feed_name ) {
		WPLA()->logger->info('Creating B2B price deletion feed');

		$max_feed_size = get_option( 'wpla_max_feed_size', 1000 );
		$messages = [];
		$skus = [];
		$message_id = 0;

		foreach ( $items as $item ) {
			if ( $message_id >= $max_feed_size ) {
				WPLA()->logger->info( 'max_feed_size reached. Breaking.' );
				break;
			}

			WPLA()->logger->info('Building 2-step B2B deletion patch (DELETE + REPLACE) for item #'. $item['post_id'] . ', SKU: ' . $item['sku']);

			// Build the complete B2C offer using the same processing logic as regular feeds
			$b2c_offer = $this->buildB2COfferForPatch( $item, $account );

			if ( empty( $b2c_offer ) ) {
				WPLA()->logger->warn('Skipping B2B deletion for item #'. $item['post_id'] . ' - could not build B2C offer');
				continue;
			}

			// Follow Amazon SP-API best practice for nested attributes:
			// 1. DELETE entire purchasable_offer attribute first
			// 2. Then REPLACE with new structure
			// Per Amazon Developer Support: patching nested attributes doesn't work
			$patches = [
				// Step 1: Remove existing offers (DELETE operation must not have a value)
				[
					'op' => 'delete',
					'path' => '/attributes/purchasable_offer'
				],
				// Step 2: Set new offer structure with B2C-only pricing
				[
					'op' => 'replace',
					'path' => '/attributes/purchasable_offer',
					'value' => [ $b2c_offer ]
				]
			];

			$message_id++;
			$messages[] = [
				'messageId'     => $message_id,
				'sku'           => $item['sku'],
				'operationType' => 'PATCH',
				'productType'   => $product_type,
				'patches'       => $patches
			];

			$skus[] = $item['sku'];
		}

		if ( empty( $messages ) ) {
			WPLA()->logger->info( 'No messages to process for B2B price deletion');
			return;
		}

		// Create a new feed for B2B price deletion
		$feed = $this->createEmptyFeed( $account, $product_type, $feed_name );

		// Set operation type for PATCH feeds
		$feed->template_name = $feed_name . ' (PATCH)';
		$feed->operation = self::OPERATION_PATCH;

		// Build proper JSON Listings Feed structure with header and messages
		$feed_data = [
			'header' => [
				'sellerId' => $account->merchant_id,
				'version'  => '2.0'
			],
			'messages' => $messages
		];

		$feed->product_type = $product_type;
		$feed->data = json_encode($feed_data);
		$feed->line_count = count($messages);

		// Store SKU information in feedOptions for table display
		$feed->feedOptions = maybe_serialize( array( 'patch_skus' => $skus ) );

		$feed->update();

		// Note: Meta field clearing is handled by the calling code to avoid conflicts

		WPLA()->logger->info('Created 2-step patch-based B2B price deletion feed with ' . count($messages) . ' item(s)');
	}

	/**
	 * Clear Amazon offers when sale prices are removed using patch-based JSON format
	 * This creates a PATCH feed that removes discounted_price attributes while preserving regular price
	 * 
	 * @param array $items Listings to clear offers from
	 * @param \WPLA_AmazonAccount $account Account object
	 * @param string $product_type Product type
	 * @param string $feed_name Feed name
	 * @return void
	 */
	private function addOfferClearingFeed( $items, $account, $product_type, $feed_name ) {
		WPLA()->logger->info('Creating offer clearing feed');

		$max_feed_size = get_option( 'wpla_max_feed_size', 1000 );
		$messages = [];
		$skus = [];
		$message_id = 0;

		foreach ( $items as $item ) {
			if ( $message_id >= $max_feed_size ) {
				WPLA()->logger->info( 'max_feed_size reached. Breaking.' );
				break;
			}

			WPLA()->logger->info('Building 2-step offer clearing patch (DELETE + REPLACE) for item #'. $item['post_id'] . ', SKU: ' . $item['sku']);

			// Build the complete offer without sale price using the same processing logic as regular feeds
			$cleared_offer = $this->buildClearedOfferForPatch( $item, $account );

			if ( empty( $cleared_offer ) ) {
				WPLA()->logger->warn('Skipping offer clearing for item #'. $item['post_id'] . ' - could not build cleared offer');
				continue;
			}

			// Follow Amazon SP-API best practice for nested attributes:
			// 1. DELETE entire purchasable_offer attribute first
			// 2. Then REPLACE with new structure
			// Per Amazon Developer Support: patching nested attributes doesn't work
			$patches = [
				// Step 1: Remove existing offers (DELETE operation must not have a value)
				[
					'op' => 'delete',
					'path' => '/attributes/purchasable_offer'
				],
				// Step 2: Set new offer structure without sale price
				[
					'op' => 'replace',
					'path' => '/attributes/purchasable_offer',
					'value' => [ $cleared_offer ]
				]
			];

			$message_id++;
			$messages[] = [
				'messageId'     => $message_id,
				'sku'           => $item['sku'],
				'operationType' => 'PATCH',
				'productType'   => $product_type,
				'patches'       => $patches
			];

			$skus[] = $item['sku'];
		}

		if ( empty( $messages ) ) {
			WPLA()->logger->info( 'No messages to process for offer clearing');
			return;
		}

		// Create a new feed for offer clearing
		$feed = $this->createEmptyFeed( $account, $product_type, $feed_name );

		// Set operation type for PATCH feeds
		$feed->template_name = $feed_name . ' (PATCH)';
		$feed->operation = self::OPERATION_PATCH;

		// Build proper JSON Listings Feed structure with header and messages
		$feed_data = [
			'header' => [
				'sellerId' => $account->merchant_id,
				'version'  => '2.0'
			],
			'messages' => $messages
		];

		$feed->product_type = $product_type;
		$feed->data = json_encode($feed_data);
		$feed->line_count = count($messages);

		// Store SKU information in feedOptions for table display
		$feed->feedOptions = maybe_serialize( array( 'patch_skus' => $skus ) );

		$feed->update();

		WPLA()->logger->info('Created 2-step patch-based offer clearing feed with ' . count($messages) . ' item(s) for account ' . $account->id);
	}

	/**
	 * Build a complete B2C offer for PATCH operations using the same logic as regular feeds
	 *
	 * @param array $item Listing item array
	 * @param \WPLA_AmazonAccount $account Amazon account
	 * @return array|null B2C offer array or null if could not be built
	 */
	private function buildB2COfferForPatch( $item, $account ) {
		$product_id = $item['post_id'];
		$product = wc_get_product( $product_id );
		
		if ( ! $product ) {
			return null;
		}

		// Get profile for this listing
		$profile = null;
		if ( ! empty( $item['profile_id'] ) ) {
			$profile = new WPLA_AmazonProfile( $item['profile_id'] );
		}

		// Use the same field processing as regular feeds
		$price_field = 'purchasable_offer[0][our_price][0][schedule][0][value_with_tax]';
		$regular_price = $this->processFieldValue( '', $price_field, $item, $product, $profile );
		
		if ( empty( $regular_price ) || ! is_numeric( $regular_price ) ) {
			return null;
		}

		// Build the B2C offer structure
		$currency = get_woocommerce_currency();
		$marketplace_id = $item['marketplace_id'] ?? $account->marketplace_id;

		$offer = [
			'audience' => 'ALL',
			'marketplace_id' => $marketplace_id,
			'currency' => $currency,
			'our_price' => [
				[
					'schedule' => [
						[
							'value_with_tax' => $regular_price  // Already formatted by processFieldValue
						]
					]
				]
			]
		];

		// Add sale price if present (using same logic as regular feeds)
		$sale_price_field = 'purchasable_offer[0][discounted_price][0][schedule][0][value_with_tax]';
		$sale_price = $this->processFieldValue( '', $sale_price_field, $item, $product, $profile );
		
		if ( ! empty( $sale_price ) && is_numeric( $sale_price ) && $sale_price > 0 ) {
			$offer['discounted_price'] = [
				[
					'schedule' => [
						[
							'value_with_tax' => $sale_price  // Already formatted by processFieldValue
						]
					]
				]
			];
			
			// Add sale dates if configured
			$start_date_field = 'purchasable_offer[0][discounted_price][0][schedule][0][start_at]';
			$end_date_field = 'purchasable_offer[0][discounted_price][0][schedule][0][end_at]';
			
			$start_date = $this->processFieldValue( '', $start_date_field, $item, $product, $profile );
			$end_date = $this->processFieldValue( '', $end_date_field, $item, $product, $profile );
			
			if ( ! empty( $start_date ) ) {
				$offer['discounted_price'][0]['schedule'][0]['start_at'] = $start_date;
			}
			if ( ! empty( $end_date ) ) {
				$offer['discounted_price'][0]['schedule'][0]['end_at'] = $end_date;
			}
		}

		return $offer;
	}

	/**
	 * Build a complete offer for PATCH operations without sale price (discounted_price cleared)
	 * This method creates an offer with only regular pricing, effectively clearing any sale prices
	 *
	 * @param array $item Listing item array
	 * @param \WPLA_AmazonAccount $account Amazon account
	 * @return array|null Cleared offer array or null if could not be built
	 */
	private function buildClearedOfferForPatch( $item, $account ) {
		$product_id = $item['post_id'];
		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			WPLA()->logger->error('Could not load WooCommerce product ' . $product_id);
			return null;
		}

		// Get profile for this listing
		$profile = null;
		if ( ! empty( $item['profile_id'] ) ) {
			$profile = new WPLA_AmazonProfile( $item['profile_id'] );
		}

		// Use the same field processing as regular feeds
		$price_field = 'purchasable_offer[0][our_price][0][schedule][0][value_with_tax]';
		$regular_price = $this->processFieldValue( '', $price_field, $item, $product, $profile );

		if ( empty( $regular_price ) || ! is_numeric( $regular_price ) ) {
			WPLA()->logger->error('Invalid regular price for product ' . $product_id . ': ' . var_export($regular_price, true));
			return null;
		}

		// Build the cleared offer structure (without discounted_price)
		$currency = get_woocommerce_currency();
		$marketplace_id = $item['marketplace_id'] ?? $account->marketplace_id;

		$offer = [
			'audience' => 'ALL',
			'marketplace_id' => $marketplace_id,
			'currency' => $currency,
			'our_price' => [
				[
					'schedule' => [
						[
							'value_with_tax' => $regular_price  // Already formatted by processFieldValue
						]
					]
				]
			]
		];

		// Explicitly do NOT add discounted_price section - this clears any existing sale prices
		// This is the key difference from buildB2COfferForPatch which adds sale prices when present

		return $offer;
	}

	/**
	 * @param array $listing A listing array from the amazon_listings table
	 * @param WPLA_AmazonProfile $profile
	 * @param array $json_fields Field names to pull values from. If empty, field names will be pulled from the schema
	 *
	 * @return array array of attributes
	 */
	public function getAttributes( $listing, $profile, $json_fields = [] ) {
		// Reset intentionally empty fields tracking for each item
		$this->intentionally_empty_fields = [];

		$product_id = $listing['post_id'] ?? null;
		$product    = wc_get_product( $product_id );
		$json_fields= $this->getJsonFields($listing, $profile, $json_fields);

		$product_type   = $this->getListingProductType( $listing, $profile );
		$marketplace_id = $this->getListingMarketplaceId( $listing, $profile );

		// Handle Offer Only as PRODUCT type for schema lookup
		$schema_product_type = $product_type;
		if ( $product_type === 'LISTING_OFFER_ONLY' || $product_type === 'Inventory Loader' ) {
			WPLA()->logger->info('using PRODUCT schema for Offer Only in getAttributes');
			$schema_product_type = 'PRODUCT'; // Use PRODUCT schema for offer-only feeds
		}

		//$is_variable_product = $product->is_type(['variable', 'variable-product-part']);

		// Disable pricing modifications from the Price Based on Country plugin
		$this->disableCountryBasedPricing();

		$schema = $this->getSchemaFromCache( $schema_product_type, $marketplace_id );
		
		if ( ! $schema ) {
			return [];
		}

		$language = $this->getLanguageFromCache($marketplace_id, $schema );

		// convert fields for Listing Loader feeds or listings without a profile
		/*if ( $profile->id == 0 || $profile->feed_type == 'ListingLoader' ) {
			WPLA()->logger->info('Using Inventory Loader fields');
			$converter = new ProfileProductTypeConverter();
			$listing_attributes = (array)maybe_unserialize( $listing['attributes'] );
			$listing['attributes'] = $converter->convertFromArray( $listing_attributes );
		}*/

		// apply the product values to the profile fields
		$product_fields = $this->getProductValues( $listing );

		$fields = [];
		foreach ( $json_fields as $key => $field ) {
			$value = $product_fields[ $key ] ?? '';

			// overwrite profile with product values if available
			if ( !empty( $product_fields[ $key ] ) ) {
				$value = $product_fields[ $key ];
			}

			// run text conversions to get the final value
			$value = $this->processFieldValue( $value, $key, $listing, $product, $profile );
			$value = apply_filters( 'wpla_filter_listing_feed_column', $value, $key, $listing, $product, $profile, '' );

			if ( $value ) {
				$value = str_replace( array("\t","\n","\r"), ' ', $value ); // make sure there are no tabs or line breaks in any field

				// Clean any malformed UTF-8 sequences
				if ( is_string($value) ) {
					$value = \WPLA_ListingsModel::convertToUTF8( $value );
				}
			}

			// Skip image fields with empty URLs - prevents Amazon error 4001001
			// Empty image URLs should not be included in the feed at all
			if ( empty( $value ) && strpos( $key, 'image_locator' ) !== false && strpos( $key, 'media_location' ) !== false ) {
				continue;
			}

			// assign the processed $value back to the $fields array
			$fields[ $key ] = $value;
		}

		// This should probably in the WPLA_Profile class

		// parse the field names (eg. brand[0][value]) to get a multi-dim array of key=>value pairs
		$fields_str = $this->buildQueryString( $fields );
		$fields_arr = [];

		parse_str( $fields_str, $fields_arr );

		// Skip B2B offers for parent/variable products - they should not have pricing
		if ( $product && !($product->get_type() == 'variable' || $product->get_type() == 'variable-product-part') ) {
            // Inject B2B offer fields if B2B price exists
			$fields_arr = $this->injectB2BOfferFields( $fields_arr, $product_id, $marketplace_id, $listing, $product, $profile );
        }

		$fields_arr = $this->filterEmptyFields( $fields_arr );
		$fields_arr = $this->filterSchemaAttributes( $fields_arr, $schema );
		$fields_arr = $this->reindexArrays( $fields_arr );
		$fields_arr = $this->insertMarketData( $fields_arr, $marketplace_id, $language );
		$fields_arr = $this->applyProperDataTypes( $fields_arr );

		// Enrich size attributes with size_class and size_system
		$fields_arr = $this->enrichSizeAttributes( $fields_arr, $listing );

		$fields_arr = apply_filters('wpla_json_feed_listing_attributes', $fields_arr, $listing, $profile);

		// Convert Unicode escapes to native UTF-8 characters
		$fields_arr = $this->convertUnicodeEscapesToUtf8($fields_arr);

		return $fields_arr;
	}

	public function getInventoryLoaderFields() {
		return array(
			'externally_assigned_product_identifier[0][type]'   => 'External Product ID Type',
			'externally_assigned_product_identifier[0][value]'  => 'External Product ID Value',
			'merchant_suggested_asin[0][value]'                 => 'Merchant Suggested ASIN',
			'condition_type[0][value]'                          => 'Offering Condition Type',
			'condition_note[0][value]'                          => 'Offer Condition Note',
			'product_tax_code[0][value]'                        => 'Product Tax Code',
			'merchant_release_date[0][value]'                   => 'Merchant Release Date',
			'main_offer_image_locator[0][media_location]'       => 'Main Image Location',
			'other_offer_image_locator_1[0][media_location]'    => 'Other Image Location',
			'other_offer_image_locator_2[0][media_location]'    => 'Other Image Location',
			'other_offer_image_locator_3[0][media_location]'    => 'Other Image Location',
			'other_offer_image_locator_4[0][media_location]'    => 'Other Image Location',
			'other_offer_image_locator_5[0][media_location]'    => 'Other Image Location',
			'fulfillment_availability[0][fulfillment_channel_code]'     => 'Fulfillment Channel Code',
			'fulfillment_availability[0][quantity]'                     => 'Quantity',
			'fulfillment_availability[0][lead_time_to_ship_max_days]'   => 'Handling Time',
			'fulfillment_availability[0][restock_date]'                 => 'Restock Date',
			'purchasable_offer[0][our_price][0][schedule][0][value_with_tax]'           => 'Price',
			'purchasable_offer[0][discounted_price][0][schedule][0][value_with_tax]'    => 'Sale Price',
			'purchasable_offer[0][discounted_price][0][schedule][0][start_at]'          => 'Sale Start Date',
			'purchasable_offer[0][discounted_price][0][schedule][0][end_at]'            => 'Sale End Date',
			'purchasable_offer[0][minimum_seller_allowed_price][0][schedule][0][value_with_tax]' => 'Minimum Price',
			'purchasable_offer[0][maximum_seller_allowed_price][0][schedule][0][value_with_tax]' => 'Maximum Price',
		);
	}

	public function getPriceAndQuantityFields() {
		return array(
			'purchasable_offer[0][our_price][0][schedule][0][value_with_tax]'           => 'Price',
			'purchasable_offer[0][discounted_price][0][schedule][0][value_with_tax]'    => 'Sale Price',
			'purchasable_offer[0][discounted_price][0][schedule][0][start_at]'          => 'Sale Start Date',
			'purchasable_offer[0][discounted_price][0][schedule][0][end_at]'            => 'Sale End Date',
			'purchasable_offer[0][minimum_seller_allowed_price][0][schedule][0][value_with_tax]' => 'Minimum Price',
			'purchasable_offer[0][maximum_seller_allowed_price][0][schedule][0][value_with_tax]' => 'Maximum Price',
			'fulfillment_availability[0][fulfillment_channel_code]'     => 'Fulfillment Channel Code',
			'fulfillment_availability[0][quantity]'                     => 'Quantity',
			'fulfillment_availability[0][lead_time_to_ship_max_days]'   => 'Handling Time',
			'fulfillment_availability[0][restock_date]'                 => 'Restock Date',
		);
	}

	/**
	 * Get the fields allowed for LISTING_OFFER_ONLY product type
	 *
	 * This returns offer-level fields (price, quantity, condition, offer images)
	 * and excludes listing-level fields (description, bullets, keywords, product images)
	 *
	 * @return array
	 */
	public function getOfferOnlyFields() {
		return array(
			// Identifiers
			'item_name[0][value]'                                       => 'Item Name',
			'brand[0][value]'                                           => 'Brand',
			'merchant_suggested_asin[0][value]'                         => 'Merchant Suggested ASIN',
			'externally_assigned_product_identifier[0][type]'           => 'External Product ID Type',
			'externally_assigned_product_identifier[0][value]'          => 'External Product ID Value',

			// Condition
			'condition_type[0][value]'                                  => 'Offering Condition Type',
			'condition_note[0][value]'                                  => 'Offer Condition Note',

			// Tax/Release
			'product_tax_code[0][value]'                                => 'Product Tax Code',
			'merchant_release_date[0][value]'                           => 'Merchant Release Date',

			// Offer Images (not product images)
			'main_offer_image_locator[0][media_location]'               => 'Main Offer Image Location',
			'other_offer_image_locator_1[0][media_location]'            => 'Other Offer Image Location 1',
			'other_offer_image_locator_2[0][media_location]'            => 'Other Offer Image Location 2',
			'other_offer_image_locator_3[0][media_location]'            => 'Other Offer Image Location 3',
			'other_offer_image_locator_4[0][media_location]'            => 'Other Offer Image Location 4',
			'other_offer_image_locator_5[0][media_location]'            => 'Other Offer Image Location 5',

			// Fulfillment
			'fulfillment_availability[0][fulfillment_channel_code]'     => 'Fulfillment Channel Code',
			'fulfillment_availability[0][quantity]'                     => 'Quantity',
			'fulfillment_availability[0][lead_time_to_ship_max_days]'   => 'Handling Time',
			'fulfillment_availability[0][restock_date]'                 => 'Restock Date',

			// Pricing
			'purchasable_offer[0][our_price][0][schedule][0][value_with_tax]'           => 'Price',
			'purchasable_offer[0][discounted_price][0][schedule][0][value_with_tax]'    => 'Sale Price',
			'purchasable_offer[0][discounted_price][0][schedule][0][start_at]'          => 'Sale Start Date',
			'purchasable_offer[0][discounted_price][0][schedule][0][end_at]'            => 'Sale End Date',
			'purchasable_offer[0][minimum_seller_allowed_price][0][schedule][0][value_with_tax]' => 'Minimum Price',
			'purchasable_offer[0][maximum_seller_allowed_price][0][schedule][0][value_with_tax]' => 'Maximum Price',
			'purchasable_offer[0][currency]'                            => 'Currency',
			'purchasable_offer[0][marketplace_id]'                      => 'Marketplace ID',
			'purchasable_offer[0][audience]'                            => 'Audience',
		);
	}

	/**
	 * Detect items that need offer clearing (sale price removal) vs regular P&Q update
	 *
	 * @param array $items Array of listing items to check
	 * @param \WPLA_AmazonAccount $account Amazon account object
	 * @return array Array with 'offer_clearing' and 'regular_pnq' keys containing filtered items
	 */
	public function segregateOfferClearingItems( $items, $account ) {
		$offer_clearing_items = [];
		$regular_pnq_items = [];

		// BATCH LOADING: Extract all IDs first
		$product_ids = [];
		$profile_ids = [];
		foreach ( $items as $item ) {
			if ( ! empty( $item['post_id'] ) ) {
				$product_ids[] = $item['post_id'];
			}
			if ( ! empty( $item['profile_id'] ) ) {
				$profile_ids[] = $item['profile_id'];
			}
		}

		// BATCH LOADING: Load all products, profiles, and meta in advance
		$products_batch = $this->load_products_batch( $product_ids );
		$profiles_batch = $this->load_profiles_batch( $profile_ids );
		$meta_batch = $this->load_post_meta_batch( $product_ids, ['_wpla_needs_offer_clearing'] );

		foreach ( $items as $item ) {
			$product_id = $item['post_id'] ?? null;
			if ( ! $product_id ) {
				$regular_pnq_items[] = $item;
				continue;
			}

			$product = $this->get_batch_product( $products_batch, $product_id );
			if ( ! $product ) {
				$regular_pnq_items[] = $item;
				continue;
			}

			// Get profile for this listing from batch-loaded cache
			$profile = null;
			if ( ! empty( $item['profile_id'] ) ) {
				$profile = $this->get_batch_profile( $profiles_batch, $item['profile_id'] );
			}

			// Check if this item needs offer clearing (sale price removal)
			if ( $this->needsOfferClearing( $item, $product, $profile, $meta_batch ) ) {
				$offer_clearing_items[] = $item;
			} else {
				$regular_pnq_items[] = $item;
			}
		}

		WPLA()->logger->info('segregateOfferClearingItems() - offer_clearing: ' . count($offer_clearing_items) . ', regular_pnq: ' . count($regular_pnq_items));

		return [
			'offer_clearing' => $offer_clearing_items,
			'regular_pnq' => $regular_pnq_items
		];
	}

	/**
	 * Check if a listing item needs offer clearing (sale price removal)
	 * This determines when OFFER_CLEARING feed should be used vs regular P&Q update
	 *
	 * @param array $item Listing item array
	 * @param \WC_Product $product WooCommerce product object
	 * @param \WPLA_AmazonProfile|null $profile Amazon profile object
	 * @param array $meta_batch Optional batch-loaded meta data (for performance)
	 * @return bool True if item needs offer clearing, false for regular P&Q update
	 */
	private function needsOfferClearing( $item, $product, $profile, $meta_batch = [] ) {
		// Check for explicit offer clearing flag in item meta or attributes
		// Use batch-loaded meta if available, otherwise fall back to get_post_meta
		if ( ! empty( $meta_batch ) ) {
			$needs_clearing = $this->get_batch_meta( $meta_batch, $item['post_id'], '_wpla_needs_offer_clearing', false );
		} else {
			$needs_clearing = get_post_meta( $item['post_id'], '_wpla_needs_offer_clearing', true );
		}

		if ( $needs_clearing ) {
			// Clear the flag since we're processing it
			delete_post_meta( $item['post_id'], '_wpla_needs_offer_clearing' );
			return true;
		}

		// Get the current WooCommerce sale price
		$current_sale_price = $product->get_sale_price('edit');

		// Get what would be sent to Amazon as discounted_price
		$sale_price_field = 'purchasable_offer[0][discounted_price][0][schedule][0][value_with_tax]';
		$processed_sale_price = $this->processFieldValue( '', $sale_price_field, $item, $product, $profile );

		// Check if sale price is scheduled to end (WooCommerce sale dates)
		$sale_date_to = $product->get_date_on_sale_to('edit');
		$is_sale_ending = false;
		
		if ( $sale_date_to && $sale_date_to->getTimestamp() <= time() ) {
			$is_sale_ending = true;
			WPLA()->logger->info('Sale price ended for product ' . $item['post_id'] . ', needs offer clearing');
		}

		// Primary detection: WooCommerce sale ended but there was a processed sale price before
		if ( $is_sale_ending && ! empty( $processed_sale_price ) ) {
			return true;
		}

		// Secondary detection: No current sale price but previous Amazon data might have had one
		// This is more complex to detect without storing previous state
		// For now, we rely on explicit flagging via post meta

		return false;
	}

	/**
	 * Mark a product for offer clearing on next P&Q update
	 * This sets a meta flag that will be detected during feed processing
	 *
	 * @param int $product_id WooCommerce product ID
	 * @return bool True on success, false on failure
	 */
	public static function markProductForOfferClearing( $product_id ) {
		// Validate and sanitize input
		$product_id = absint( $product_id );
		if ( ! $product_id ) {
			WPLA()->logger->error( 'markProductForOfferClearing() - Invalid product ID provided' );
			return false;
		}

		// Verify product exists
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			WPLA()->logger->error( 'markProductForOfferClearing() - Product ' . $product_id . ' does not exist' );
			return false;
		}

		// Check if user has capability to manage products (for manual calls)
		if ( ! wp_doing_cron() && ! current_user_can( 'edit_products' ) ) {
			WPLA()->logger->error( 'markProductForOfferClearing() - User lacks capability to manage products' );
			return false;
		}

		// Set the offer clearing flag
		$result = update_post_meta( $product_id, '_wpla_needs_offer_clearing', true );
		if ( false === $result ) {
			WPLA()->logger->error( 'markProductForOfferClearing() - Failed to set meta for product ' . $product_id );
			//return false;
		}

		// Log the action with detailed information
		WPLA()->logger->info( 'markProductForOfferClearing() - Product ' . $product_id . ' marked for offer clearing' );
		
		// Integrate with existing WPLA listing system
		self::updateListingsForOfferClearing( $product_id );
		
		return true;
	}

	/**
	 * Update WPLA listings when product is marked for offer clearing
	 * Handles both locked and unlocked listings appropriately
	 *
	 * @param int $product_id WooCommerce product ID
	 * @return void
	 */
	private static function updateListingsForOfferClearing( $product_id ) {
		// Additional validation for internal method
		$product_id = absint( $product_id );
		if ( ! $product_id ) {
			WPLA()->logger->error( 'updateListingsForOfferClearing() - Invalid product ID provided' );
			return;
		}

		$lm = new \WPLA_ListingsModel();
		$listings = $lm->getAllItemsByPostOrParentID( $product_id );

		if ( empty( $listings ) ) {
			WPLA()->logger->info('updateListingsForOfferClearing() - No listings found for product ' . $product_id);
			return;
		}

		// Group listings by account_id (from profile) to update them appropriately
		$listings_by_account = array();
		foreach ( $listings as $listing ) {
			// Get account_id from profile, fallback to listing account_id
			$account_id = null;
			if ( ! empty( $listing->profile_id ) ) {
				$profile = new \WPLA_AmazonProfile( $listing->profile_id );
				$account_id = $profile->account_id;
			}
			// Fallback to listing's account_id if profile doesn't have one
			if ( empty( $account_id ) ) {
				$account_id = $listing->account_id;
			}

			$listings_by_account[ $account_id ][] = $listing;
		}

		WPLA()->logger->info('updateListingsForOfferClearing() - Product ' . $product_id . ' has listings on accounts: ' . implode(', ', array_keys($listings_by_account)));

		$updated_count = 0;
		foreach ( $listings_by_account as $account_id => $account_listings ) {
			WPLA()->logger->info('updateListingsForOfferClearing() - Processing ' . count($account_listings) . ' listings for account ' . $account_id);

			foreach ( $account_listings as $listing ) {
				// Skip trashed listings
				if ( $listing->status == 'trashed' ) {
					continue;
				}

				// Handle locked vs unlocked listings differently
				if ( $listing->locked ) {
					// For locked listings with offer clearing, we need to set status to 'changed'
					// because offer clearing requires PATCH feeds, not just P&Q updates
					$lm->updateWhere(
						array( 'id' => $listing->id ),
						array( 'status' => \WPLA_ListingsModel::STATUS_CHANGED )
					);

					WPLA()->logger->info( sprintf(
						'updateListingsForOfferClearing() - Locked listing %d (product %d, account %d) status set to changed for offer clearing',
						$listing->id,
						$product_id,
						$listing->account_id
					) );
				} else {
					// For unlocked listings, set status to changed for full update
					$new_status = null;
					switch ( $listing->status ) {
						case \WPLA_ListingsModel::STATUS_ONLINE:
						case \WPLA_ListingsModel::STATUS_SUBMITTED:
							$new_status = \WPLA_ListingsModel::STATUS_CHANGED;
							break;
						case \WPLA_ListingsModel::STATUS_FAILED:
							$new_status = $listing->asin ? \WPLA_ListingsModel::STATUS_CHANGED : \WPLA_ListingsModel::STATUS_PREPARED;
							break;
						default:
							// Keep existing status for prepared, matched, sold
							$new_status = $listing->status;
					}

					if ( $new_status ) {
						$lm->updateWhere(
							array( 'id' => $listing->id ),
							array( 'status' => $new_status )
						);

						WPLA()->logger->info( sprintf(
							'updateListingsForOfferClearing() - Listing %d (product %d, account %d) status updated to %s for offer clearing',
							$listing->id,
							$product_id,
							$listing->account_id,
							$new_status
						) );
					}
				}
				$updated_count++;
			}
		}

		WPLA()->logger->info('updateListingsForOfferClearing() - Updated ' . $updated_count . ' listings for product ' . $product_id);

		// Trigger feed update if there were changes
		if ( $updated_count > 0 ) {
			// Use the existing hook system to trigger feed updates
			do_action( 'wpla_product_has_changed', $product_id, false );
		}
	}

	/**
	 * Group items by their profile account_id to ensure correct account processing for PATCH feeds
	 *
	 * @param array $items Array of listing items
	 * @param \WPLA_AmazonAccount $fallback_account Fallback account if profile account not found
	 * @return array Grouped items by account_id
	 */
	private function groupItemsByProfileAccount( $items, $fallback_account ) {
		$items_by_account = array();

		// BATCH LOADING: Extract all profile IDs first
		$profile_ids = [];
		foreach ( $items as $item ) {
			if ( ! empty( $item['profile_id'] ) ) {
				$profile_ids[] = $item['profile_id'];
			}
		}

		// BATCH LOADING: Load all profiles in advance
		$profiles_batch = $this->load_profiles_batch( $profile_ids );

		foreach ( $items as $item ) {
			// Get account_id from profile, fallback to listing account_id, then fallback account
			$account_id = null;

			if ( ! empty( $item['profile_id'] ) ) {
				$profile = $this->get_batch_profile( $profiles_batch, $item['profile_id'] );
				$account_id = $profile->account_id ?? null;
			}

			// Fallback to listing's account_id if profile doesn't have one
			if ( empty( $account_id ) && ! empty( $item['account_id'] ) ) {
				$account_id = $item['account_id'];
			}

			// Final fallback to provided account
			if ( empty( $account_id ) ) {
				$account_id = $fallback_account->id;
			}

			$items_by_account[ $account_id ][] = $item;
		}

		return $items_by_account;
	}

	/**
	 * Clear the offer clearing flag for a product
	 *
	 * @param int $product_id WooCommerce product ID
	 * @return void
	 */
	public static function clearOfferClearingFlag( $product_id ) {
		delete_post_meta( $product_id, '_wpla_needs_offer_clearing' );
	}

	/**
	 * Check if sale price was removed and mark for offer clearing
	 * This should be called when WooCommerce product is updated
	 *
	 * @param int $product_id WooCommerce product ID
	 * @param \WC_Product $product WooCommerce product object  
	 * @return void
	 */
	public static function checkSalePriceRemoval( $product_id, $product = null ) {
		if ( ! $product ) {
			$product = wc_get_product( $product_id );
		}
		
		if ( ! $product ) {
			return;
		}

		// Get previous sale price from post meta (stored when product was last processed)
		$previous_sale_price = get_post_meta( $product_id, '_wpla_previous_sale_price', true );
		$current_sale_price = $product->get_sale_price('edit');

		// If there was a previous sale price but now there isn't, mark for offer clearing
		if ( ! empty( $previous_sale_price ) && empty( $current_sale_price ) ) {
			$result = self::markProductForOfferClearing( $product_id );
			if ( $result ) {
				WPLA()->logger->info('Sale price removed for product ' . $product_id . ', marked for offer clearing');
			} else {
				WPLA()->logger->error('Failed to mark product ' . $product_id . ' for offer clearing after sale price removal');
			}
		}

		// Store current sale price for next comparison
		if ( ! empty( $current_sale_price ) ) {
			update_post_meta( $product_id, '_wpla_previous_sale_price', $current_sale_price );
		} else {
			delete_post_meta( $product_id, '_wpla_previous_sale_price' );
		}
	}

	/**
	 * Get the messages from the items array and build the messages array for the feed data.
	 * This method will also check if the listing can be submitted to Amazon based on the profile settings
	 *
	 * @param array $items
	 * @param \WPLA_AmazonAccount $account
	 * @param string $operation
	 * @param string $product_type
	 * @param array $feed_attributes
	 *
	 * @return array
	 */
	private function getMessagesFromItems( $items, $account, $operation = JsonFeedDataBuilder::OPERATION_UPDATE, $product_type = null, $feed_attributes = [] ) {
		$messages = [];
		$max_feed_size = get_option( 'wpla_max_feed_size', 1000 );
		$lm = new \WPLA_ListingsModel();

		// BATCH LOADING: Extract all IDs first
		$product_ids = [];
		foreach ( $items as $item ) {
			if ( ! empty( $item['post_id'] ) ) {
				$product_ids[] = $item['post_id'];
			}
		}

		// BATCH LOADING: Load all products and meta in advance
		$products_batch = $this->load_products_batch( $product_ids );
		$meta_batch = $this->load_post_meta_batch( $product_ids, ['_wpla_asin'] );

		$msg_id = 0;
		foreach ( $items as $item ) {
			WPLA()->logger->info('Building Message for item #'. ($item['post_id'] ?? 'unknown'));

			$profile = $this->getProfileFromCache( $item['profile_id'] );

			$submission_check = $this->canSubmitListing( $item, $profile );
			if ( is_wp_error( $submission_check ) ) {
				WPLA()->logger->info( 'Skipping listing: ' . $submission_check->get_error_message() );
				continue;
			}

			// Check for existing ASIN from both sources: listings table first, then post meta fallback
			$existing_asin = '';
			$asin_source = '';
			if ( ! empty( $item['asin'] ) ) {
				$existing_asin = $item['asin'];
				$asin_source = 'listings_table';
			} elseif ( ! empty( $this->get_batch_meta( $meta_batch, $item['post_id'], '_wpla_asin' ) ) ) {
				$existing_asin = $this->get_batch_meta( $meta_batch, $item['post_id'], '_wpla_asin' );
				$asin_source = 'post_meta';
			}

			// Log which ASIN source is being used for debugging
			if ( ! empty( $existing_asin ) ) {
				WPLA()->logger->info( "Item #{$item['post_id']} has ASIN '{$existing_asin}' from {$asin_source}" );
			}

			// If this item has no ASIN from either source, assume that this is a new listing that needs to be published
			if ( empty( $existing_asin ) && $item['status'] == \WPLA_ListingsModel::STATUS_PREPARED ) {
				WPLA()->logger->info( 'Item has no ASIN (checked both post meta and listings table) - scheduled this to be submitted as a new listing');
				$lm->enqueueForPublish( $item['id'] );
				continue;
			}

			// Determine operation type based on item status (similar to CSV add-delete column)
			$item_operation = $operation;
			if ( $item['status'] == 'trash' ) {
				$item_operation = self::OPERATION_DELETE;
				WPLA()->logger->info('Item status is trash - setting operation to DELETE');
			}

			// For DELETE operations, we don't need complex attributes
			if ( $item_operation == self::OPERATION_DELETE ) {
				$attributes = [];
			} else {
				// For PRODUCT feeds (P&Q and Inventory Loader), skip variable parent products entirely
				if ( $product_type === 'PRODUCT' ) {
					$product = $this->get_batch_product( $products_batch, $item['post_id'] ?? null );
					if ( $product && $product->get_type() == 'variable' ) {
						WPLA()->logger->info( 'Skipping variable parent product in PRODUCT feed: ' . $item['sku'] );
						continue;
					}
				}
				
				$attributes = $this->getAttributes( $item, $profile, $feed_attributes );

				// skip empty attributes for non-delete operations
				if ( empty( $attributes ) ) {
					WPLA()->logger->info('No attributes found for this item. Skipping');
					continue;
				}
			}

			$msg_id++;

			// Determine product type once upfront for efficiency (avoid duplicate calls to getListingProductType)
			$original_product_type = null;
			if ( $item_operation != self::OPERATION_DELETE ) {
				$original_product_type = $product_type ?? $this->getListingProductType( $item, $profile );
			}

			// For NEW offer-only listings, PARTIAL_UPDATE is not allowed by Amazon's API
			// We must convert it to UPDATE operation type for creating offers on existing ASINs
			$message_operation = $item_operation;
			if ( $item_operation === self::OPERATION_PARTIAL_UPDATE && ($original_product_type === 'LISTING_OFFER_ONLY' || $original_product_type === 'Inventory Loader') ) {
				$message_operation = self::OPERATION_UPDATE;
				WPLA()->logger->info('Converting PARTIAL_UPDATE to UPDATE for offer-only listing: ' . $item['sku']);
			}

			$message = [
				'messageId'     => $msg_id,
				'sku'           => $item['sku'],
				'operationType' => $message_operation,
				//'requirements'  => 'LISTING',
			];

			// Only add productType and attributes for non-delete operations
			if ( $item_operation != self::OPERATION_DELETE ) {
				$message_product_type = $original_product_type;

				// Convert LISTING_OFFER_ONLY and Inventory Loader to PRODUCT for JSON feed
				if ( $original_product_type === 'LISTING_OFFER_ONLY' || $original_product_type === 'Inventory Loader' ) {
					$message_product_type = 'PRODUCT';
				}

				$message['productType'] = $message_product_type;

				// Only add requirements for offer-only feeds (revert to original behavior for regular PRODUCT feeds)
				if ( $original_product_type === 'Inventory Loader' || $original_product_type === 'LISTING_OFFER_ONLY' ) {
					$message['requirements'] = 'LISTING_OFFER_ONLY';
				}

				$message['attributes'] = $attributes;
			}

			$messages[] = apply_filters( 'wpla_json_builder_message_array', $message, $item, $profile, $attributes );

			WPLA()->logger->info( 'Total messages generated: '. count( $messages ) );

			if ( $msg_id >= $max_feed_size ) {
				WPLA()->logger->info( 'max_feed_size reached. Breaking.');
				break;
			}
		}

		return $messages;
	}

	/**
	 * @param \WPLA_AmazonAccount $account
	 *
	 * @return \WPLA_AmazonFeed
	 */
	private function getAvailableFeed( $account, $product_type, $feed_name = '' ) {
		$feed_name = $feed_name ?: 'Listings Data Feed';
		$existing_feed_id = \WPLA_AmazonFeed::getPendingFeedId( 'JSON_LISTINGS_FEED', $feed_name, $account->id, $product_type );

		if ( ! $existing_feed_id ) {
			// return a new feed
			return $this->createEmptyFeed( $account, $product_type, $feed_name );
		}

		$feed = new \WPLA_AmazonFeed( $existing_feed_id );

		$decoded = json_decode( $feed->getData() );

		if ( $decoded ) {
			if ( isset($decoded->messages) && count( $decoded->messages ) < get_option( 'wpla_max_feed_size', 1000 ) ) {
				return $feed;
			}
		}

		return $this->createEmptyFeed( $account, $product_type, $feed_name );
	}

	private function createEmptyFeed( $account, $product_type = '', $feed_name = 'Listings Data Feed' ) {
		$data = [
			'header' => [
				'sellerId' => $account->merchant_id,
				'version'   => '2.0'
			],
			'messages' => []
		];

		// return a new feed
		$new_feed = new \WPLA_AmazonFeed();
		$new_feed->FeedType             = 'JSON_LISTINGS_FEED';
		$new_feed->product_type         = $product_type;
		$new_feed->template_name        = $feed_name;
		$new_feed->FeedProcessingStatus = 'pending';
		$new_feed->status               = \WPLA_AmazonFeed::STATUS_PENDING;
		$new_feed->account_id           = $account->id;
		$new_feed->data                 = json_encode( $data, JSON_UNESCAPED_UNICODE );
		$new_feed->date_created         = gmdate('Y-m-d H:i:s');
		$new_feed->id = null;
		$new_feed->add();

		return $new_feed;
	}

	private function getProfileFromCache( $profile_id ) {
		if ( isset( $this->profile_cache[ $profile_id ] ) ) {
			return $this->profile_cache[ $profile_id ];
		} else {
			$profile = new WPLA_AmazonProfile( $profile_id );
			$this->profile_cache[ $profile_id ] = $profile;
			return $profile;
		}
	}

	/**
	 * @param string $value
	 * @param string $property
	 * @param object $listing
	 * @param \WC_Product $product
	 * @param WPLA_AmazonProfile $profile
	 *
	 * @return string
	 */
	private function processFieldValue( $value, $property, $listing, $product, $profile ) {
		$product_id      = $product->get_id() ;
		$product_type    = $product->get_type();
		$product_sku     = $product->get_sku();
		$fba_enabled     = $this->isFba( $listing, $profile );

		$profile_details = !empty($profile->details) ? maybe_unserialize( $profile->details ) : array();
		$profile_fields  = !empty($profile->fields) ? maybe_unserialize( $profile->fields ) : array();
		$variations_mode = $profile_details['variations_mode'] ?? 'default';

		// Strip composite enum suffixes (enum_value|||sanitized_label) from all profile field values
		// so every code path that reads profile_fields receives a clean enum value.
		$profile_fields = array_map( [ AmazonSchemaFormGenerator::class, 'stripEnumComposite' ], $profile_fields );

		// Also strip from any value passed in from product-level custom fields (_wpla_custom_feed_columns),
		// which are stored with the same composite format and bypass the profile_fields strip above.
		$value = AmazonSchemaFormGenerator::stripEnumComposite( $value );

		// set correct post_id for variations
		// post_id is the child ID and product_id is the parent in case of a variable product
		// $parent_id = $product_id / $child_id = $post_id
		$child_id = $parent_id = $product_id;
		//$parent_id = $product_id;
		if ( $product_type == 'variation' || $product_type == 'product-part-variation' ) {
			// set the $product_id to the parent's ID
			$parent_id = \WPLA_ProductWrapper::getVariationParent( $child_id );
		}

		WPLA()->logger->debug('processFieldValue ('. $property .') #'. $child_id .'/'. $parent_id );

		// process hard-coded values
		switch ( $property ) {

			case 'externally_assigned_product_identifier[0][value]':
				if ( empty( $value ) ) {
					$value = get_post_meta( $child_id, '_amazon_product_id', true );
				}
				break;

			case 'externally_assigned_product_identifier[0][type]':
				if ( empty( $value ) ) {
					$value = strtolower( get_post_meta( $child_id, '_amazon_id_type', true ) );
				}
				break;

			case 'sku':
				$value = $listing['sku']; // we have to use the item SKU - or feed processing would fail if SKU is different in WooCommerce and WP-Lister
				break;

			case 'purchasable_offer[0][our_price][0][schedule][0][value_with_tax]':
				$value = $this->getRegularPriceValue( $child_id, $parent_id, $listing, $profile );

				###
				# 05/19/23: Only apply the profile value if there's no _amazon_price set in the product #60955
				###
				// Ugh, we've gone full circle now.
				// Revising again because apparently, profile fields need to have the highest priority #20404 #20390
				// So now, the regular price can be overridden by _amazon_price, and that too can be overridden by the profile field
				if ( (!$this->getProductAmazonPrice( $parent_id ) && !$this->getProductAmazonPrice( $child_id ) ) && isset( $profile_fields[$property] ) && ! empty( $profile_fields[$property] ) ) {
					$value = $profile_fields[$property];
				}

				$value  = $this->formatPriceDecimal( $value );
				break;

			case 'purchasable_offer[0][discounted_price][0][schedule][0][value_with_tax]':
				// Priority: Product-level Product Type attribute > Profile field (with shortcode processing)
				// $value already contains product-level attribute if set (from getProductValues)
				// WC sale price is ONLY used when [product_sale_price] shortcode is in the profile field

				// If no product-level attribute, use profile field with shortcode processing
				// replaceShortcodes() will:
				// 1. Get profile field value if $value is empty
				// 2. Parse shortcodes like [product_sale_price] to get WC sale price
				if ( empty( $value ) ) {
					$value = $this->replaceShortcodes( $value, $property, $listing, $child_id, $profile );
				}

				// Explicitly unmapped [---] clears the sale price
				if ( isset($profile_fields[$property]) && '[---]' === $profile_fields[$property] ) {
					$value = '';
				}

				// SP-API JSON Feeds API is complaining about using the same price for the Sale and Regular prices
				// If there's no sale price, return empty to omit entire discounted_price section
				if ( empty($value) ) {
					$value = '';
				} else {
					$value = $this->formatPriceDecimal( $value );
					
					// After formatting, check if sale price equals regular price
					$regular_price = $this->getRegularPriceValue( $child_id, $parent_id, $listing, $profile );
					$regular_price = $this->formatPriceDecimal( $regular_price );
					
					if ( $regular_price && ( floatval($value) >= floatval($regular_price) ) ) {
						$value = '';
					}
				}
				break;

			case 'purchasable_offer[0][discounted_price][0][schedule][0][start_at]':
				// Check profile field configuration first to determine if sale price should be included
				$sale_price_property = 'purchasable_offer[0][discounted_price][0][schedule][0][value_with_tax]';

				// If profile field is explicitly unmapped, don't include any sale price data
				if ( isset($profile_fields[$sale_price_property]) && '[---]' === $profile_fields[$sale_price_property] ) {
					return '';
				}

				// Check for product-level Product Type sale price attribute first (highest priority)
				$product_custom_props = $this->getProductCustomProperties( $parent_id );
				$sale_price = $product_custom_props[$sale_price_property] ?? '';

				// If no product-level attribute, use profile field with shortcode processing
				// replaceShortcodes() will parse [product_sale_price] to get WC sale price
				if ( empty( $sale_price ) ) {
					$sale_price = $this->replaceShortcodes( '', $sale_price_property, $listing, $child_id, $profile );
				}

				// Format and check against regular price
				if ( ! empty($sale_price) ) {
					$sale_price = $this->formatPriceDecimal( $sale_price );
					$regular_price = $this->getRegularPriceValue( $child_id, $parent_id, $listing, $profile );
					$regular_price = $this->formatPriceDecimal( $regular_price );

					if ( $regular_price && ( floatval($sale_price) >= floatval($regular_price) ) ) {
						$sale_price = '';
					}
				}

				// If no sale price, return empty to omit entire discounted_price section
				if ( empty($sale_price) ) {
					return '';
					//break;
				}

				// Priority: Product-level date attribute > WC date > Profile date
				// $value already contains product-level attribute if set (from getProductValues)

				// Fall back to WC date if no product-level date attribute
				if ( empty( $value ) ) {
					$date = get_post_meta( $product_id, '_sale_price_dates_from', true );
					if ( $date ) $value = wp_date( 'Y-m-d', $date );
				}

				// Use profile value if no product-level or WC date
				if ( empty( $value ) ) {
					if ( isset( $profile_fields[$property] ) && ! empty( $profile_fields[$property] ) && '[---]' !== $profile_fields[$property] ) {
						$value = $profile_fields[$property];
						// Process shortcodes in profile value before date parsing
						if ( strpos($value, '[') !== false ) {
							$value = $this->replaceShortcodes( $value, $property, $listing, $child_id, $profile );
						}
					}
				}

				// Convert MM/DD/YYYY format to YYYY-MM-DD if needed
				$value = \WPLA_DateTimeHelper::convertDateFormatForAmazon( $value );

				// We have a discounted price, so we need a schedule
				if ( ! $value ) {
					$value = '2010-12-31';
				}

				break;

			case 'purchasable_offer[0][discounted_price][0][schedule][0][end_at]':
				// Check profile field configuration first to determine if sale price should be included
				$sale_price_property = 'purchasable_offer[0][discounted_price][0][schedule][0][value_with_tax]';

				// If profile field is explicitly unmapped, don't include any sale price data
				if ( isset($profile_fields[$sale_price_property]) && '[---]' === $profile_fields[$sale_price_property] ) {
					return '';
				}

				// Check for product-level Product Type sale price attribute first (highest priority)
				$product_custom_props = $this->getProductCustomProperties( $parent_id );
				$sale_price = $product_custom_props[$sale_price_property] ?? '';

				// If no product-level attribute, use profile field with shortcode processing
				// replaceShortcodes() will parse [product_sale_price] to get WC sale price
				if ( empty( $sale_price ) ) {
					$sale_price = $this->replaceShortcodes( '', $sale_price_property, $listing, $child_id, $profile );
				}

				// Format and check against regular price
				if ( ! empty($sale_price) ) {
					$sale_price = $this->formatPriceDecimal( $sale_price );
					$regular_price = $this->getRegularPriceValue( $child_id, $parent_id, $listing, $profile );
					$regular_price = $this->formatPriceDecimal( $regular_price );

					if ( $regular_price && ( floatval($sale_price) >= floatval($regular_price) ) ) {
						$sale_price = '';
					}
				}

				// If no sale price, return empty to omit entire discounted_price section
				if ( empty($sale_price) ) {
					return '';
					//break;
				}

				// Priority: Product-level date attribute > WC date > Profile date
				// $value already contains product-level attribute if set (from getProductValues)

				// Fall back to WC date if no product-level date attribute
				if ( empty( $value ) ) {
					$date = get_post_meta( $product_id, '_sale_price_dates_to', true );
					if ( $date ) $value = wp_date( 'Y-m-d', $date );
				}

				// Use profile value if no product-level or WC date
				if ( empty( $value ) ) {
					if ( isset( $profile_fields[$property] ) && ! empty( $profile_fields[$property] ) && '[---]' !== $profile_fields[$property] ) {
						$value = $profile_fields[$property];
						// Process shortcodes in profile value before date parsing
						if ( strpos($value, '[') !== false ) {
							$value = $this->replaceShortcodes( $value, $property, $listing, $child_id, $profile );
						}
					}
				}

				// Convert MM/DD/YYYY format to YYYY-MM-DD if needed
				$value = \WPLA_DateTimeHelper::convertDateFormatForAmazon( $value );

				// We have a discounted price (confirmed above), so we need a schedule
				// If no end date, use future date to keep the sale active
				if ( ! $value ) {
					$value = '2029-12-31';
				}

				break;

			case 'purchasable_offer[0][minimum_seller_allowed_price][0][schedule][0][value_with_tax]':
				$value = get_post_meta( $product_id, '_amazon_minimum_price', true );

				// Deduct the shipping fee from the min/max prices (only if repricing is enabled, unless filter overrides)
				$apply_shipping_to_all = apply_filters( 'wpla_repricing_shipping_apply_to_all', false );
				$has_repricing = $listing['min_price'] > 0 && $listing['max_price'] > 0;
				if ( $value && ( $apply_shipping_to_all || $has_repricing ) && $shipping_fee = get_option( 'wpla_repricing_shipping', false ) ) {
					$value  = $this->formatPriceDecimal( $value );
					$value -= $shipping_fee;
				}

				$value  = $this->formatPriceDecimal( $value );
				break;

			case 'purchasable_offer[0][maximum_seller_allowed_price][0][schedule][0][value_with_tax]':
				$value = get_post_meta( $product_id, '_amazon_maximum_price', true );

				// Deduct the shipping fee from the min/max prices (only if repricing is enabled, unless filter overrides)
				$apply_shipping_to_all = apply_filters( 'wpla_repricing_shipping_apply_to_all', false );
				$has_repricing = $listing['min_price'] > 0 && $listing['max_price'] > 0;
				if ( $value && ( $apply_shipping_to_all || $has_repricing ) && $shipping_fee = get_option( 'wpla_repricing_shipping', false ) ) {
					$value  = str_replace( ',', '.', $value ); // covert to a dot decimal character - will get converted to comma later if necessary in self::convertCurrencyFormat()
					$value -= $shipping_fee;
				}

				$value  = $this->formatPriceDecimal( $value );
				break;

			case 'purchasable_offer[0][audience]':
				// Always set default offer to ALL (B2C) - B2B offer will be added separately if needed
				$value = 'ALL';
				break;

			case 'purchasable_offer[0][currency]':
			case 'purchasable_offer[1][currency]':
				// Apply schema default for empty currency field
				if ( empty( $value ) ) {
					$product_type_name = $this->getListingProductType( $listing, $profile );
					$marketplace_id = $this->getListingMarketplaceId( $listing, $profile );
					$schema = $this->getSchemaFromCache( $product_type_name, $marketplace_id );
					$value = $schema['properties']['purchasable_offer']['items']['properties']['currency']['default'] ?? get_woocommerce_currency();
				}
				break;

			case 'list_price[0][currency]':
				// Apply schema default for empty currency field in pricing fields
				if ( empty( $value ) ) {
					$product_type_name = $this->getListingProductType( $listing, $profile );
					$marketplace_id = $this->getListingMarketplaceId( $listing, $profile );
					$schema = $this->getSchemaFromCache( $product_type_name, $marketplace_id );
					// Try to get currency from schema, fallback to WooCommerce default currency
					$value = $schema['properties']['list_price']['items']['properties']['currency']['default'] ?? get_woocommerce_currency();
				}
				
				break;

			case 'uvp_list_price[0][currency]':
				// Apply schema default for empty currency field in UVP list price
				if ( empty( $value ) ) {
					$product_type_name = $this->getListingProductType( $listing, $profile );
					$marketplace_id = $this->getListingMarketplaceId( $listing, $profile );
					$schema = $this->getSchemaFromCache( $product_type_name, $marketplace_id );
					// Try to get currency from schema, fallback to WooCommerce default currency
					$value = $schema['properties']['uvp_list_price']['items']['properties']['currency']['default'] ?? get_woocommerce_currency();
				}
				
				break;

			case 'fulfillment_availability[0][fulfillment_channel_code]':
				$value = 'DEFAULT';

				if ( $fba_enabled ) {
					$profile_fcid = '';
					if ( ! empty( $profile_fields['fulfillment_center_id'] ) ) {
						if ( ! in_array( $profile_fields['fulfillment_center_id'], [ 'DEFAULT', '[---]' ], true ) ) {
							$profile_fcid = $profile_fields['fulfillment_center_id'];
						}
					} elseif ( ! empty( $profile_fields['fulfillment-center-id'] ) ) {
						if ( ! in_array( $profile_fields['fulfillment-center-id'], [ 'DEFAULT', '[---]' ], true ) ) {
							$profile_fcid = $profile_fields['fulfillment-center-id'];
						}
					}

					$listing_fcid = $listing['fba_fcid'];
					if ( $listing_fcid === 'DEFAULT' || empty( $listing_fcid ) ) {
						$listing_fcid = '';
					}

					// Priority: listing FCID, then profile FCID
					$value = $listing_fcid ?: $profile_fcid;

					// handle FBA only mode - force FBA enabled if set
					$fba_only_mode = get_option( 'wpla_fba_only_mode', 0 );

					// handle FBA on product / variation level
					$fba_overwrite = get_post_meta( $product_id, '_amazon_fba_overwrite', true );

					// Only use global FCID as fallback when explicitly in FBA-only mode or product has FBA override
					// This prevents randomly changing matched FBM listings to FBA
					if ( ! $value && ( $fba_only_mode || $fba_overwrite == 'FBA' ) ) {
						$value = get_option( 'wpla_fba_fulfillment_center_id' );
					}

					// If still no valid FCID, fall back to DEFAULT (FBM)
					if ( ! $value ) {
						$value = 'DEFAULT';
					}

					// Check for explicit FBM override
					if ( $fba_overwrite == 'FBM' ) {
						$value = 'DEFAULT';
					}
				}

				break;

			case 'fulfillment_availability[0][quantity]':
				if ( ! $fba_enabled ) {

					if ( ($product_type == 'variation' || $product_type == 'product-part-variation' ) && empty( $parent_id ) ) {
						wpla_show_message('<b>Warning: The parent product for variation #'.$child_id.' (SKU '.$listing['sku'].') does not exist!</b><br>Please remove that item from WP-Lister and check the integrity of your WooCommerce database.','warn');
						$value = '';
					} else {
						$value = '';
						if ( $product_type != 'variable' && $product_type != 'variable-product-part' ) {
							$value = intval( \WPLA_ProductWrapper::getStock( $product ) );
						}
					}

					WPLA()->logger->info( 'Current quantity: '. $value );

					// regard WooCommerce's Out Of Stock Threshold option - if enabled
					if ( $out_of_stock_threshold = get_option( 'woocommerce_notify_no_stock_amount' ) ) {
						if ( $value && 1 == get_option( 'wpla_enable_out_of_stock_threshold' ) ) {
							$value = intval($value) - intval($out_of_stock_threshold);
						}
					}

					WPLA()->logger->info( 'Value after OOS threshold: '. $value );

					if ( $value < 0 ) $value = 0; // amazon doesn't allow negative values

					// allow custom profile value to overwrite WooCommerce quantity
					if ( isset( $profile_fields[$property] ) && ($profile_fields[$property] !== '' || $profile_fields[$property] === 0) ) {
						$value = $profile_fields[$property];
						WPLA()->logger->info( 'Value from profile: '. $value );
					}

					// If the Hide on Amazon checkbox is checked, simply send a stock quantity of 0 to make this unsellable #38320
					if ( get_post_meta( $child_id, '_amazon_is_disabled', true ) ) {
						WPLA()->logger->info( 'Product #'. $child_id .' is hidden/disabled. Setting the stock quantity to 0' );
						$value = 0;
					}
				}
				break;

			case 'fulfillment_availability[0][lead_time_to_ship_max_days]':
				// For FBA listings, Amazon handles fulfillment so we shouldn't specify lead times
				if ( ! $fba_enabled ) {
					if ( $handling_time = get_post_meta( $child_id, '_amazon_handling_time', true ) ) {
						$value = intval( $handling_time );
					}
				} else {
					// FBA listing - exclude lead time (Amazon handles fulfillment)
					$value = '';
				}
				break;

			case 'bullet_point[0][value]':
			case 'bullet_point[1][value]':
			case 'bullet_point[2][value]':
			case 'bullet_point[3][value]':
			case 'bullet_point[4][value]':
			case 'bullet_point[5][value]':
				// Handle bullet points separately from keywords
				$key = $this->getProductMetaKeyFromProperty( $property );
				if ( $key ) {
					// Start with profile value as default
					$value = isset( $profile_fields[$property] ) ? $profile_fields[$property] : '';
					
					// Override with product-level value if it exists
					$product_value = get_post_meta( $parent_id, '_amazon_'. $key, true );
					if ( ! empty( $product_value ) ) {
						$value = $product_value;
					}
				}
				break;
			
			case 'generic_keyword[0][value]':
			case 'generic_keyword[1][value]':
			case 'generic_keyword[2][value]':
			case 'generic_keyword[3][value]':
			case 'generic_keyword[4][value]':
				// Check if single keyword mode is enabled
				if ( 'single' == get_option( 'wpla_keyword_fields_type', 'separate' ) ) {
					// In single mode, only put search_term in the first keyword field
					if ( 'generic_keyword[0][value]' === $property ) {
						$value = get_post_meta( $parent_id, '_amazon_search_term', true );
					} else {
						$value = ''; // Leave other keyword fields empty
					}
				} else {
					// In separate mode, use individual keyword fields
					$key = $this->getProductMetaKeyFromProperty( $property );
					if ( $key ) {
						$value = get_post_meta( $parent_id, '_amazon_'. $key, true );
					}
				}

				if ( isset( $profile_fields[$property] ) && ! empty( $profile_fields[$property] ) ) {
					$value = $profile_fields[$property];
				}

				$value = self::htmlEntityDecode( self::doTranslate( $value, $profile->account_id ) );

				break;

			case 'main_product_image_locator[0][media_location]':
			case 'main_offer_image_locator[0][media_location]':
				WPLA()->logger->info( "Processing image property: {$property} for product {$product_id}" );
				// if gallery mode is set to ignore images, skip this process
				if ( get_option( 'wpla_product_gallery_fallback', 'none' ) == 'ignore' ) {
					$value = '';
					WPLA()->logger->info( "Gallery fallback set to ignore - skipping image processing" );
					break;
				}

				// if offer images are disabled, skip this column
				if ( strstr($property,'offer_image_locator') && get_option( 'wpla_enable_product_offer_images', 0 ) == 0 ) {
					break;
				}

				// check for product-level field value first (highest priority)
				WPLA()->logger->info( "Looking for custom feed columns on product_id: {$product_id}, parent_id: {$parent_id}" );
				$custom_props = get_post_meta( $parent_id, '_wpla_custom_feed_columns', true );
				//WPLA()->logger->debug( "Checking custom feed columns: " . print_r($custom_props, true) );
				if ( is_array($custom_props) && !empty($custom_props[$property]) ) {
					WPLA()->logger->info( "Found custom feed column override: {$custom_props[$property]}" );
					return wpla_encode_image_url( $custom_props[$property] );
				}

				// check for product-level meta field override (like bullet_point and generic_keyword)
				$key = $this->getProductMetaKeyFromProperty( $property );
				if ( $key ) {
					$product_override = get_post_meta( $parent_id, '_amazon_'. $key, true );
					WPLA()->logger->info( "Checking meta key '_amazon_{$key}' for parent {$parent_id}: {$product_override}" );
					if ( !empty($product_override) ) {
						WPLA()->logger->info( "Found product-level image override: {$product_override}" );
						return wpla_encode_image_url( $product_override );
					}
				}

				// check if custom post meta field 'amazon_image_url' exists
				$amazon_image_url = get_post_meta( $child_id, 'amazon_image_url', true );
				WPLA()->logger->info( "Checking amazon_image_url for child {$child_id}: {$amazon_image_url}" );
				if ( $amazon_image_url ) {
					WPLA()->logger->info( "Found amazon_image_url override: {$amazon_image_url}" );
					return wpla_encode_image_url( $amazon_image_url );
				}

				// $value      = $product->get_image('full');
				$attachment_id = get_post_thumbnail_id( $child_id );
				$image_url     = wp_get_attachment_image_src( $attachment_id, 'full' );
				$value         = is_array( $image_url ) ? $image_url[0] : '';

				WPLA()->logger->info( 'wpla_variation_main_image_fallback: '. get_option('wpla_variation_main_image_fallback','parent') );
				if ( empty($value) && ( $product_type == 'variation' || $product_type == 'product-part-variation' ) && get_option('wpla_variation_main_image_fallback','parent') == 'parent' ) {
					$attachment_id = get_post_thumbnail_id( $parent_id );
					$image_url     = wp_get_attachment_image_src( $attachment_id, 'full' );
					$value         = $image_url[0] ?? '';
					WPLA()->logger->info( 'found '. $value .' for '. $parent_id );
				}

				// if main image is disabled, use first enabled gallery image
				$disabled_images = array_filter( explode( ',', get_post_meta( $product_id, '_wpla_disabled_gallery_images', true ) ) );

				if ( ! $value || in_array( $attachment_id, $disabled_images ) ) {
					// $gallery_images = $product->get_gallery_attachment_ids();
					$gallery_images = \WPLA_ProductWrapper::getGalleryAttachmentIDs( $product );
					$gallery_images = array_values( array_diff( $gallery_images, $disabled_images ) );
					$gallery_images = apply_filters( 'wpla_product_gallery_attachment_ids', $gallery_images, $product_id );
					if ( isset( $gallery_images[0] ) ) {
						$image_url = wp_get_attachment_image_src( $gallery_images[0], 'full' );
						$value = @$image_url[0];
					}
				}

				// custom amazon image
				// Allow filter to override the default behavior of not overriding existing variation images
				$allow_variation_override = apply_filters( 'wpla_allow_custom_gallery_variation_override', false, $product_id, $parent_id );
				
				// Use custom gallery if:
				// 1. Not a variation (always override for non-variations), OR
				// 2. Variation with no existing image (fallback), OR  
				// 3. Filter explicitly allows variation override
				if ( $product_type != 'variation' || empty( $value ) || $allow_variation_override ) {
					$custom_images = get_post_meta( $parent_id, '_amazon_image_gallery', true );
					if ( !empty( $custom_images ) ) {
						$custom_images = array_filter( array_map( 'trim', explode( ',', $custom_images ) ) );
						$image_url = wp_get_attachment_image_src( $custom_images[ 0 ], 'full' );
						$value = @$image_url[0];
					}
				}

				// custom product level column overwrites WooCommerce image
				if ( isset( $profile_fields[$property] ) && ! empty( $profile_fields[$property] ) ) $value = $profile_fields[$property];

				// maybe fall back to parent variation featured image (disable to avoid the same swatch image for all child variations - ticket #6662)
				WPLA()->logger->info( 'variation_main_image for '. $listing['sku'] );
				WPLA()->logger->info( 'product type: '. $product_type );
				WPLA()->logger->info( 'current value: '. $value );

				WPLA()->logger->info( 'new value: '. $value );

				$value = apply_filters( 'wpla_product_main_image_url', $value, $child_id );
				$value = self::convertImageUrl( $value );
				break;


			case 'other_product_image_locator_1[0][media_location]':
			case 'other_product_image_locator_2[0][media_location]':
			case 'other_product_image_locator_3[0][media_location]':
			case 'other_product_image_locator_4[0][media_location]':
			case 'other_product_image_locator_5[0][media_location]':
			case 'other_product_image_locator_6[0][media_location]':
			case 'other_product_image_locator_7[0][media_location]':
			case 'other_product_image_locator_8[0][media_location]':
			case 'other_offer_image_locator_1[0][media_location]':
			case 'other_offer_image_locator_2[0][media_location]':
			case 'other_offer_image_locator_3[0][media_location]':
			case 'other_offer_image_locator_4[0][media_location]':
			case 'other_offer_image_locator_5[0][media_location]':
				// if gallery mode is set to ignore images, skip this process
				if ( get_option( 'wpla_product_gallery_fallback', 'none' ) == 'ignore' ) {
					WPLA()->logger->info( 'product_gallery_fallback is set to ignore. Setting value to ""' );
					$value = '';
					break;
				}

				// if offer images are disabled, skip this column
				if ( strstr($property,'offer_image_locator') && get_option( 'wpla_enable_product_offer_images', 0 ) == 0 ) {
					WPLA()->logger->info( 'product_offer_images disabled. Skipping' );
					break;
				}

				// check for product-level field value first (highest priority)
				$custom_props = get_post_meta( $parent_id, '_wpla_custom_feed_columns', true );
				if ( is_array($custom_props) && !empty($custom_props[$property]) ) {
					$value = self::convertImageUrl( $custom_props[$property] );
					break;
				}

				$base_property = $this->getBaseProperty( $property );
				$image_index = substr($base_property, -1);        // skip first image

				WPLA()->logger->info( 'image_index: '. $image_index );

				if ( 'skip' != get_option( 'wpla_product_gallery_first_image' )) {
					$image_index -= 1;	// include first image
					WPLA()->logger->info( 'Skipping first image. New index: '. $image_index);
				}

				// build list of enabled gallery images (attachment_ids)
				$disabled_images = explode( ',', get_post_meta( $product_id, '_wpla_disabled_gallery_images', true ) );
				// $gallery_images = $product->get_gallery_attachment_ids();
				$gallery_images = \WPLA_ProductWrapper::getGalleryAttachmentIDs( $product );
				$gallery_images = array_values( array_diff( $gallery_images, $disabled_images ) );
				$gallery_images = apply_filters( 'wpla_product_gallery_attachment_ids', $gallery_images, $child_id );


				if ( isset( $gallery_images[ $image_index ] ) ) {
					$image_url = wp_get_attachment_image_src( $gallery_images[ $image_index ], 'full' );
					$value = @$image_url[0];
					$value = self::convertImageUrl( $value );
				} else {
					WPLA()->logger->info( 'gallery_images['. $image_index .'] does not exist.' );
				}

				// custom amazon image
				// Allow filter to override the default behavior of not overriding existing variation images
				$allow_variation_override = apply_filters( 'wpla_allow_custom_gallery_variation_override', false, $child_id, $parent_id );

				// Use custom gallery if:
				// 1. Not a variation (always override for non-variations), OR
				// 2. Filter explicitly allows variation override
				// Note: Variations should not inherit parent gallery images unless explicitly allowed
				if ( $product_type != 'variation' || $allow_variation_override ) {
					$custom_images = get_post_meta( $parent_id, '_amazon_image_gallery', true );
					if ( ! empty( $custom_images ) ) {
						// if using custom images, always skip the first image because it is already being used as
						// the listing's primary image
						$base_property = $this->getBaseProperty( $property );
						$image_index = substr( $base_property, - 1 );

						$custom_images = array_filter( array_map( 'trim', explode( ',', $custom_images ) ) );

						if ( ! empty( $custom_images[ $image_index ] ) ) {
							$image_url = wp_get_attachment_image_src( $custom_images[ $image_index ], 'full' );
							$value     = @$image_url[0];
						}
					}
				}

				// custom product level column overwrites WooCommerce image
				if ( isset( $profile_fields[$property] ) && ! empty( $profile_fields[$property] ) ) $value = $profile_fields[$property];
				break;

			case 'merchant_suggested_asin[0][value]':
				$value = get_post_meta( $child_id, '_wpla_asin', true );
				
				// Fallback to listing ASIN if _wpla_asin is empty
				if ( empty( $value ) && ! empty( $listing['asin'] ) ) {
					$value = $listing['asin'];
				}
				break;

			case 'condition_type[0][value]':
				$value = get_post_meta( $child_id, '_amazon_condition_type', true );

				// fallback to parent's condition type
				if ( ! $value ) {
					$value = get_post_meta( $parent_id, '_amazon_condition_type', true );
				}

				// if this item was imported but has no product level condition, use original report value
				if ( ! $value && $listing['source'] == 'imported' ) {
					$report_row = json_decode( $item['details'] ?? '', true );
					if ( is_array($report_row) && isset( $report_row['item-condition'] ) ) {
						$value = WPLA_ImportHelper::convertNumericConditionIdToType( $report_row['item-condition'] );
					}
				}

				$value = wpla_convert_legacy_item_condition( $value );

				// New is not a valid value anymore
				if ( $value === 'New' ) {
					$value = 'new_new';
				}

				if ( ! $value && ! isset( $profile_fields[$property] ) ) {
					$value = 'new_new';	// avoid an empty value for Offer feeds without profile
				}
				break;

			case 'condition_note[0][value]':
				$value = get_post_meta( $child_id, '_amazon_condition_note', true );

				// fallback to parent's condition note
				if ( ! $value ) {
					$value = get_post_meta( $parent_id, '_amazon_condition_note', true );
				}

				//$value = self::doTranslate( $value, $profile->account_id );
				// decode charset to prevent getting invalid characters #51609
				$value = self::htmlEntityDecode( self::doTranslate( $value, $profile->account_id ) );
				break;

			case 'parentage_level[0][value]':
				if ( $product_type == 'variable' || $product_type == 'variable-product-part' ) {
					$value = 'parent';
				} elseif ( $product_type == 'variation' || $product_type == 'product-part-variation' ) {
					$value = 'child';
				}

				if ( $variations_mode == 'flat' ) {
					$value = '';
				}
				break;

			case 'child_parent_sku_relationship[0][child_relationship_type]':
				if ( $product_type == 'variation' || $product_type == 'product-part-variation' )
					$value = 'variation';
				if ( $variations_mode == 'flat' ) $value = '';
				break;

			case 'child_parent_sku_relationship[0][parent_sku]':
				if ( $product_type == 'variation' || $product_type == 'product-part-variation' ) {
					$parent_product = \WPLA_ProductWrapper::getProduct( $parent_id );

					if ( $parent_product ) {
						$value = $parent_product->get_sku();
					}
				}
				if ( $variations_mode == 'flat' ) $value = '';
				break;

			case 'variation_theme[0][name]':
				// handle empty vtheme for legacy items
				/*if ( empty( $value ) && in_array( $product_type, array( 'variation', 'variable', 'variable-product-part', 'product-part-variation' ) ) ) {
					$parent_id = $listing['parent_id'] ?? $listing['post_id'];
					$value     = \WPLA_ListingsModel::getVariationThemeForPostID( $parent_id );
				}*/

				// Only use the listing's vtheme if there's no product-level value
				if ( empty( $value ) ) {
					$value = $listing['vtheme'];
				}

				if ( $value ) {
					// Convert or Map attributes first before replace dashes with slashes
					$value = self::convertToEnglishAttributeLabel($value);
					$value = str_replace('-', '/', $value);

					switch ( strtolower( $value ) ) {
						case 'colour':
							$value = 'COLOR';
							break;

						case 'colorsize':
						case 'color/size':
						case 'colour/size':
						case 'coloursize':
							$value = 'COLOR/SIZE';
							break;

						case 'size/colour':
						case 'size/color':
							$value = 'SIZE/COLOR';
							break;

						case 'materialcolor':
							$value = 'COLOR/MATERIAL';
							break;
					}

					if ($variations_mode == 'flat') {
						$value = '';
					}

				}

				if ( isset( $profile_fields[$property] ) && ! empty( $profile_fields[$property] ) ) {
					$value = $profile_fields[$property];
				}

				// Exclude completely from simple products #19914
				// Don't use [---] here as it would now be sent to Amazon (after #72845 fix)
				// Simple products should not have variation_theme at all
				if ( $product->is_type( 'simple' ) ) {
					return '';
				}

				// SP-API not requires the VarTheme be uppercase
				if ( $value ) {
					$value = strtoupper( $value );
				}
				break;

			case 'color[0][standardized_values]':
				if ( $product_type == 'variation' || $product_type == 'product-part-variation' ) {
					$color_name = WPLA()->memcache->getColumnValue( $product_sku, 'color_name' );

					if ( ! $color_name ) {
						// try to get the color_name in case it hasn't been processed yet
						$color_name = self::parseProductColumn( 'color_name', $listing, $product, $profile );
					}

					$color_name = strtolower( $color_name );
					$variation_color_map = get_option( 'wpla_variation_color_map', array() );
					if ( $color_name && array_key_exists( $color_name, $variation_color_map ) ) {
						$color_value = $variation_color_map[ $color_name ];
					} else {
						$color_value = $color_name;
					}

					// standardized_values must be an array according to Amazon's schema
					if ( ! empty( $color_value ) ) {
						$value = [ $color_value ];
					}
				}
				break;

			case 'apparel_size[0][size]':
			case 'shirt_size[0][size]':
			case 'skirt_size[0][size]':
			case 'bottoms_size[0][size]':
			case 'footwear_size[0][size]':
			case 'size_map[0][value]':
			case 'size[0][value]':
				if ( $product_type == 'variation' || $product_type == 'product-part-variation' ) {
					$size_name = WPLA()->memcache->getColumnValue( $product_sku, 'size_name' );

					if ( ! $size_name ) {
						// try to get the color_name in case it hasn't been processed yet
						$size_name = self::parseProductColumn( 'size_name', $listing, $product, $profile );
					}

					$size_name = strtolower( $size_name );
					$variation_size_map = get_option( 'wpla_variation_size_map', array() );

					if ( !empty( $variation_size_map ) ) {
						$excluded_markets = get_option( 'wpla_sizemap_excluded_markets', array() );
						$item_market = WPLA()->accounts[ $listing['account_id'] ]->market_code;

						if ( in_array( $item_market, $excluded_markets ) ) {
							WPLA()->logger->info( 'Item is in the excluded markets sizemap array. Skipping mapping.' );
						} else {
							WPLA()->logger->info( 'Mapping size_name: '. $size_name );
							if ( $size_name ) {
								$lowered_size_name = strtolower( $size_name );
								//WPLA()->logger->info( 'Lowered size_name'. $lowered_size_name );
								//WPLA()->logger->debug( 'Size Map'. print_r( $variation_size_map,1 ) );
								if ( array_key_exists( $lowered_size_name, $variation_size_map ) ) {
									$value = $variation_size_map[ $lowered_size_name ];
									WPLA()->logger->info( 'Found value: '. $value );
								}
							}
						}
					}

				}
				break;

			case 'batteries_required[0][value]':
				if ( ! $value && $fba_enabled ) {
					$value = false;	// set default value to false for FBA enabled items
				}
				// custom product level column overwrites default value
				if ( isset( $profile_fields[$property] ) && ! empty( $profile_fields[$property] ) ) {
					$value = $profile_fields[$property];
				}
				break;

			case 'supplier_declared_dg_hz_regulation[0][value]':
			case 'supplier_declared_dg_hz_regulation[1][value]':
			case 'supplier_declared_dg_hz_regulation[2][value]':
			case 'supplier_declared_dg_hz_regulation[3][value]':
			case 'supplier_declared_dg_hz_regulation[4][value]':
				if ( ! $value && $fba_enabled ) {
					$value = 'not_applicable';	// set default value to 'not_applicable' for FBA enabled items
				}
				// custom product level column overwrites default value
				if ( isset( $profile_fields[$property] ) && ! empty( $profile_fields[$property] ) ) {
					$value = $profile_fields[$property];
				}
				break;

			case 'fulfillment_availability[0][restock_date]':
				WPLA()->logger->info( 'getting restock_date for '. $child_id );
				$value = get_post_meta( $child_id, '_amazon_restock_date', true );
				WPLA()->logger->info( 'found '. $value );

				// fallback to parent's restock date
				if ( ! $value ) {
					$value = get_post_meta( $parent_id, '_amazon_restock_date', true );
				}

				// format the date to YYYY-MM-DD
				if ( $value ) {
					$value = date( 'Y-m-d', strtotime( $value ) );
				}
				break;
		}

		WPLA()->logger->debug( 'value after switch statement: '. $value );

		/*
		// Generic currency field handling - apply default currency if field is empty and ends with [currency]
		if ( empty( $value ) && preg_match( '/\[currency\]$/', $property ) ) {
			$value = get_woocommerce_currency(); // Fallback to WooCommerce default currency for any empty currency field
			WPLA()->logger->info( 'Applied default currency ' . $value . ' to field: ' . $property );
		}*/


		$value = $this->handleVariationAttributes( $value, $property, $listing, $product, $profile );

		// Force empty value for properties with a [---] value
		// Track this field so it's not filtered out (sent to Amazon to clear attribute)
		if ( '[---]' === $value ) {
			$this->intentionally_empty_fields[$property] = true;
			return '';
		}

		// Process shortcodes in product-level values before checking profile fields
		// This ensures shortcodes set at product level (Edit Product → Amazon tab) are processed
		if ( !empty($value) && is_string($value) && strpos($value, '[') !== false ) {
			$value = $this->replaceShortcodes( $value, $property, $listing, $child_id, $profile );
		} elseif ( is_array($value) ) {
			$value = $this->replaceShortcodes( $value, $property, $listing, $child_id, $profile );
		}

		// parent variations should only have certain columns
		// these three seem to work on Amazon CA / Automotive: item_sku, parent_child, variation_theme
		// but on US and DE, more columns are required:
		// $parent_var_columns = array('item_sku','parent_child','variation_theme'); // CA
		$parent_var_columns = apply_filters( 'wpla_allowed_parent_var_columns', array(
			'sku',
			'parentage_level[0][value]',
			'variation_theme[0][name]',
			'brand[0][value]',
			'item_name[0][value]',
			'department[0][value]',
			'department[1][value]',
			'department[2][value]',
			'department[3][value]',
			'department[4][value]',
			'product_description[0][value]',
			'item_type_keyword[0][value]',
			'item_type_name[0][value]',
			'bullet_point[0][value]',
			'bullet_point[1][value]',
			'bullet_point[2][value]',
			'bullet_point[3][value]',
			'bullet_point[4][value]',
			'special_features[0][value]',
			'special_features[1][value]',
			'special_features[2][value]',
			'special_features[3][value]',
			'special_features[4][value]',
			'main_product_image_locator[0][media_location]',
			'manufacturer[0][value]',
			'manufacturer_minimum_age[0][value]',
			'manufacturer_minimum_unit_of_measure[0][value]',
			'style[0][value]',
			'closure[0][type][value]',
			'lifestyle[0][value]',
			'lifestyle[1][value]',
			'lifestyle[2][value]',
			'lifestyle[3][value]',
			'lifestyle[4][value]',
			'material[0][value]',
			'pattern_type[0][value]',
			'model_year[0][value]',
			'shoe_width[0][unit]',
			'target_audience_keyword[0][value]',
			'target_audience_keyword[1][value]',
			'target_audience_keyword[2][value]',
			'target_audience_keyword[3][value]',
			'target_audience_keyword[4][value]',
			'binding[0][value]',
			'publication_date[0][value]',
			'author[0][value]',
			'part_number[0][value]',
			'ingredients[0][value]',
			'ingredients[1][value]',
			'ingredients[2][value]',
			'ingredients[3][value]',
			'ingredients[4][value]',
			//'update_delete', // added as instructed by AMZ in #34078
			'alcohol_content[0][value]', // added for #34632
			'alcohol_content[0][unit]',
			'unit_count[0][value]',
			'unit_count[0][type][value]', // added unit_count and unit_count_type #37866
			'display[0][type][value]', // added for 40982
			'watch_movement_type[0][value]', // added for 40982
			'target_gender[0][value]', // added for 41962
			'gem_type[0][value]', // added for 42127
			'outer[0][material][value]', // added for 43975
			'recommended_browse_nodes[0][value]',
			'material_composition[0][value]', // added for 47870
			'feed_product_type', // added for 51943
			'country_of_origin[0][value]', // added for 52180
			'age_range_description[0][value]', // added for 52839
			'fabric_type[0][value]', // added for 52839
			'supplier_declared_dg_hz_regulation[0][value]',
			'batteries_required[0][value]',
			'condition_type[0][value]',
			'merchant_suggested_asin[0][value]',
		), $property, $listing, $profile );

		if ( ($product_type == 'variable' || $product_type == 'variable-product-part') && ! in_array( $property, $parent_var_columns ) ) {
			$value = '';
		} else {
			// process profile fields - if not empty
			// Checking only against an empty string because using empty() will return TRUE for 0 values, and we need to be able to submit 0 to Amazon
			if ( !isset( $profile_fields[ $property ] ) || $profile_fields[ $property ] == '' ) {
				return $value;
			}

			// empty shortcode overrides default value
			if ( isset( $profile_fields[$property] ) && '[---]' === $profile_fields[$property] ) {
				// Track this field so it's not filtered out (sent to Amazon to clear attribute)
				$this->intentionally_empty_fields[$property] = true;
				return '';
			}

			// use profile value as it is - if $value is still empty (ie. there is no product level value for this column)
			$used_profile_value = false;
			if ( empty($value) && $value !== 0 ) {
				$value = $profile_fields[$property];
				$used_profile_value = true;
			}


			// Only process shortcodes if we used a profile value (product-level shortcodes already processed above)
			if ( $used_profile_value ) {
				$value = $this->replaceShortcodes( $value, $property, $listing, $child_id, $profile );
			}
			$value = $this->handleSizeAttributes( $value, $property, $listing, $profile );
		}

		return $value;
	}

	/**
	 * Get the price value for the product
	 * @param int $product_id
	 * @param int $parent_id
	 * @param string $listing
	 * @param WPLA_AmazonProfile $profile
	 *
	 * @return mixed|string|null
	 */
	private function getRegularPriceValue( $product_id, $parent_id, $listing, $profile ) {
		// send empty price if external_repricer flag is set
		if ( $this->isUsingExternalRepricer( $product_id ) ) {
			return '';
		}

		$product = wc_get_product( $product_id );

		$value = $product->get_regular_price();
		$value = $profile->id ? $profile->processProfilePrice( $value ) : $value;
		$value = apply_filters( 'wpla_filter_product_price', $value, $product_id, $product, $listing, $profile );

		$parent_amazon_price = $this->getProductAmazonPrice( $parent_id );
		$child_amazon_price  = $this->getProductAmazonPrice( $product_id );

		if ( $child_amazon_price > 0 ) {
			$value = $child_amazon_price;
		} elseif ( $parent_id != $product_id && $parent_amazon_price > 0 ) {
			$value = $parent_amazon_price;
		}

		$value = $this->deductShippingFeesFromMinMax( $value, $listing );

		return $value;
	}

	// check if there is an active sale price (different from the standard price) for current row / SKU
	private function withActiveSalePrice( $product_id, $parent_id, $listing, $profile ) {

		// check if there is a sale price for this row
		$sale_price = $this->getSalePriceValue( $product_id, $parent_id, $listing, $profile );
		if ( ! $sale_price ) {
			return false;
		}

		// if there is a sale price, check if it's different from the standard price
		if ( $sale_price == $this->getRegularPriceValue( $product_id, $parent_id, $listing, $profile ) ) {
			return false;
		}

		// yes, there is a sale price
		return true;
	} // withActiveSalePrice()

	private function getSalePriceValue( $product_id, $parent_id, $listing, $profile ) {
		if ( $this->isUsingExternalRepricer( $product_id ) ) {
			return '';
		}

		$product = wc_get_product( $product_id );

		$value = $product->get_sale_price();
		$value = $profile->id ? $profile->processProfilePrice( $value ) : $value;
		$value = apply_filters( 'wpla_filter_sale_price', $value, $product_id, $product, $listing, $profile );
		$value = $value ? number_format($value,2, null, '' ) : $value;

		// make sure sale_price is not higher than standard_price / price - Amazon might silently ignore price updates otherwise
		$standard_price = $this->getRegularPriceValue( $product_id, $parent_id, $listing, $profile );
		if ( $standard_price && ( $value > $standard_price ) ) {
			$value = '';
		}

		// if sale price equals regular price, there's no discount - return empty to omit discounted_price section
		if ( $standard_price && $value && ( floatval($value) >= floatval($standard_price) ) ) {
			$value = '';
		}

		// if no sale price is set, return empty to omit discounted_price section entirely
		// This prevents Amazon validation errors with invalid date ranges
		if ( empty($value) ) {
			$value = '';
		}

		// if sale price is disabled, use standard price here
		if ( get_option( 'wpla_disable_sale_price', 0 ) ) {
			//$value = $standard_price; # Should return empty if Use Sale Price is disabled
			$value = '';
		}

		if ( $value ) {
			// Deduct the shipping fee from the min/max prices
			$value = $this->deductShippingFeesFromMinMax( $value, $listing );
		}


		return $value;
	}

	/**
	 * Get the custom amazon price from the product
	 *
	 * @param int $product_id
	 *
	 * @return mixed
	 */
	private function getProductAmazonPrice( $product_id ) {
		return get_post_meta( $product_id, '_amazon_price', true );
	}

	/**
	 * Convert the property name to the field name stored in the postmeta table (bullet_point[0][value] to bullet_point1)
	 *
	 * @param string $property
	 *
	 * @return string|FALSE
	 */
	private function getProductMetaKeyFromProperty( $property ) {
		$map = [
			'bullet_point[0][value]' => 'bullet_point1',
			'bullet_point[1][value]' => 'bullet_point2',
			'bullet_point[2][value]' => 'bullet_point3',
			'bullet_point[3][value]' => 'bullet_point4',
			'bullet_point[4][value]' => 'bullet_point5',
			'generic_keyword[0][value]' => 'generic_keywords1',
			'generic_keyword[1][value]' => 'generic_keywords2',
			'generic_keyword[2][value]' => 'generic_keywords3',
			'generic_keyword[3][value]' => 'generic_keywords4',
			'generic_keyword[4][value]' => 'generic_keywords5',
			'main_product_image_locator[0][media_location]' => 'main_product_image_locator',
		];

		return $map[ $property ] ?? false;
	}

	private function formatPriceDecimal( $price ) {
		if (is_numeric( $price )) {
			// Format the value so it has the correct decimal character #51029
			$price = str_replace( ',', '.', $price ); // covert to a dot decimal character - will get converted to comma later if necessary in self::convertCurrencyFormat()
			$price = number_format( floatval( $price ), 2, null, '' );
		}

		return $price;
	}

	private function disableCountryBasedPricing() {
		// Stop the Price Based on Country plugin from modifying these prices #55996
		add_filter( 'wc_price_based_country_stop_pricing', function() {
			return true; // True to do not load the frontend pricing.
		});
	}

	private function isUsingExternalRepricer( $product_id ) {
		// send empty price if external_repricer flag is set
		$x_repricer = get_post_meta( $product_id, '_amazon_external_repricer', true );
		$y_repricer = get_option( 'wpla_external_repricer_mode', false );

		if ( $x_repricer || $y_repricer ) {
			return true;
		}

		return false;
	}

	private function deductShippingFeesFromMinMax( $price, $listing ) {
		// Deduct the shipping fee from the min/max prices (only if repricing is enabled, unless filter overrides)
		$apply_shipping_to_all = apply_filters( 'wpla_repricing_shipping_apply_to_all', false );
		$has_repricing = $listing['min_price'] > 0 && $listing['max_price'] > 0;
		if ( $price && ( $apply_shipping_to_all || $has_repricing ) && $shipping_fee = get_option( 'wpla_repricing_shipping', false ) ) {
			$price -= $shipping_fee;

			// remove the shipping fee from the min/max prices
			if ( $has_repricing ) {
				$listing['min_price'] -= $shipping_fee;
				$listing['max_price'] -= $shipping_fee;
			}
		}

		// Format the value so it has the correct decimal character #51029
		$price = str_replace( ',', '.', $price ); // covert to a dot decimal character - will get converted to comma later if necessary in self::convertCurrencyFormat()
		$price = number_format( floatval( $price ), 2, null, '' );

		// make sure price stays within min/max boundaries - prevent Amazon from throwing price alert / validation error (would make listing inactive)
		if ( $listing['min_price'] > 0 ) {
			$price = max( $price, $listing['min_price'] );
		}

		if ( $listing['max_price'] > 0 ) {
			$price = min( $price, $listing['max_price'] );
		}

		return $price;
	}

	/**
	 * Get property values from the product level
	 *
	 * @param array $listing
	 * @param WPLA_AmazonProfile $profile
	 * @return void
	 */
	private function getProductValues( $listing ) {
		$product_id     = $listing['post_id'] ?? null;

		WPLA()->logger->debug('getProductValues for '.$listing['sku'].' - ID '.$product_id);

		// set correct variation_id for variations
		$wc_product = wc_get_product( $product_id );
		$product_type = $wc_product->get_type();
		$variation_id = $product_id;

		if ( $product_type == 'variation' || $product_type == 'product-part-variation' ) {
			// set the $product_id to the parent's ID
			$product_id = \WPLA_ProductWrapper::getVariationParent( $variation_id );
		}

		// get custom parent product level feed properties - and merge with profile columns
		$product_level_properties = $this->getProductCustomProperties( $product_id );

		return $product_level_properties;
	}

	/**
	 * @param array $listing
	 * @param WPLA_AmazonProfile $profile
	 *
	 * @return string
	 */
	public function getListingProductType( $listing, $profile ) {
		$product_id = ($listing['parent_id'] ?? null) ?: ($listing['post_id'] ?? null);

		// load the product type from the product
		$product_type = get_post_meta( $product_id, '_wpla_custom_product_type', true );

		if ( !$product_type && $profile->id ) {
			$product_type   = $profile->product_type;
		}

		// If there's still no product type at this point, use the generic PRODUCT product type and
		// assume that this is ListingLoader or PnQ feed type
		if ( !$product_type ) {
			$product_type = 'PRODUCT';
		}

		return $product_type;
	}

	/**
	 * @param array $listing
	 * @param WPLA_AmazonProfile $profile
	 *
	 * @return string
	 */
	private function getListingMarketplaceId( $listing, $profile ) {
		$marketplace_id = $profile->marketplace_id;

		if ( !$profile->id ) {
			$product_id = ($listing['parent_id'] ?? null) ?: ($listing['post_id'] ?? null);

			// load the marketplace from the product
			$marketplace_id = get_post_meta( $product_id, '_wpla_custom_marketplace_id', true );
		}

		if ( empty( $marketplace_id ) ) {
			// pull from the listing account
			$marketplace_id = WPLA()->accounts[ $listing['account_id'] ]->marketplace_id;
		}

		return $marketplace_id;
	}

	/**
	 * @param array $listing
	 * @param WPLA_AmazonProfile $profile
	 *
	 * @return bool
	 */
	private function isFba( $listing, $profile ) {
		$fba_enabled = false;

		$product_id     = ($listing['parent_id'] ?? null) ?: ($listing['post_id'] ?? null);
		$profile_fields = $profile->id ? maybe_unserialize( $profile->fields )  : array();

		// handle FBA mode / fallback
		if ( get_option( 'wpla_fba_enabled', 0 ) ) {
			if ( get_option('wpla_fba_enable_fallback') == 1 ) {
				// fallback enabled
				// if there is no FBA qty, FBA will be disabled
				$fba_enabled = $listing['fba_quantity'] > 0; // if there is FBA qty, always enable FBA
			} else {
				// fallback disabled
				$fba_enabled = $listing['fba_fcid'] && ( $listing['fba_fcid'] != 'DEFAULT' ) ; // regard fba_fcid column - ignore stock
			}
		}

		// if fulfillment_center_id / fulfillment-center-id is forced to AMAZON_NA / AMAZON_EU in the listing profile,
		// make sure to set $fba_enabled to regard this overwrite in ListingLoader feeds as well
		if ( isset( $profile_fields['fulfillment_center_id'] ) && ! empty( $profile_fields['fulfillment_center_id'] ) ) {
			$fba_enabled = ! ( $profile_fields['fulfillment_center_id'] == 'DEFAULT' || $profile_fields['fulfillment_center_id'] == '[---]' );
		}
		if ( isset( $profile_fields['fulfillment-center-id'] ) && ! empty( $profile_fields['fulfillment-center-id'] ) ) {
			$fba_enabled = ! ( $profile_fields['fulfillment-center-id'] == 'DEFAULT' || $profile_fields['fulfillment-center-id'] == '[---]' );
		}

		// handle FBA only mode - force FBA enabled if set
		// FBA needs to be enabled as well #29966
		$fba_only_mode = get_option( 'wpla_fba_only_mode', 0 );
		if ( get_option( 'wpla_fba_enabled', 0 ) && $fba_only_mode ) $fba_enabled = true;

		// handle FBA on product / variation level
		$fba_overwrite = get_post_meta( $product_id, '_amazon_fba_overwrite', true );
		if ( $fba_overwrite == 'FBA' ) {
			$fba_enabled = true;
		} elseif ( $fba_overwrite == 'FBM' ) {
			$fba_enabled = false;
		}

		return $fba_enabled;
	}

	/**
	 * @param WC_Product $product
	 * @return array
	 */
	private function getProductCustomProperties( $product_id ) {
		// Check if product has a custom product type set
		// If no product type is specified, don't use any product-level custom properties
		$product_type = get_post_meta( $product_id, '_wpla_custom_product_type', true );
		if ( empty( $product_type ) ) {
			return [];
		}

		$custom_props = get_post_meta( $product_id, '_wpla_custom_feed_columns', true );

		if ( empty( $custom_props ) || !is_array( $custom_props ) ) {
			$custom_props = [];
		}

		$converter = new ProfileProductTypeConverter();
		return $converter->convertFromArray( $custom_props );
	}

	/**
	 * Get the Product Type schema from cache
	 * @param string $product_type
	 * @param string $marketplace_id
	 *
	 * @return object
	 */
	private function getSchemaFromCache( $product_type, $marketplace_id ) {
		$key = $product_type .'_'. $marketplace_id;

		// Use array_key_exists to detect cached null values (failed lookups)
		if ( array_key_exists( $key, $this->schema_cache ) ) {
			return $this->schema_cache[ $key ];
		}

		$types_mdl  = new \WPLab\Amazon\Models\AmazonProductTypesModel();
		$type_obj   = $types_mdl->getDefinitionsProductType( $product_type, $marketplace_id, true );

		if ( ! $type_obj || is_wp_error( $type_obj ) ) {
			// Cache the failure to prevent repeated API calls for the same product type
			$this->schema_cache[ $key ] = null;
			return null;
		}

		$schema     = json_decode( $type_obj->getSchema(), true );

		$this->schema_cache[ $key ] = $schema;

		return $schema;
	}

	/**
	 * Run some basic check to see if this listing can be submitted to Amazon
	 *
	 * @param array $item
	 * @param WPLA_AmazonProfile $profile
	 *
	 * @return true|\WP_Error Returns true if valid, WP_Error object if invalid
	 */
	public function canSubmitListing( $item, $profile ) {
		WPLA()->logger->info('canSubmitListing() - id: '.($item['post_id'] ?? 'unknown'));

		// get WooCommerce product data
		$product_id      = $item['post_id'] ?? null;
		$product         = wc_get_product( $product_id );
		$profile_details = maybe_unserialize( $profile->details );

		if ( ! $product || !$product->exists() ) {
			$error_msg = "WooCommerce product #{$product_id} not found or doesn't exist";
			WPLA()->logger->info( $error_msg );
			return new \WP_Error( 'wpla_product_not_found', $error_msg );
		}

		if ( !$item['sku'] ) {
			$error_msg = "Listing SKU is empty for product #{$product_id}";
			WPLA()->logger->info( $error_msg );
			return new \WP_Error( 'WPLA_MISSING_SKU', $error_msg );
		}

		WPLA()->logger->info('processing item '.$item['sku'].' - ID '.$product_id);

		// Skip listing parent variables if profile variations mode is FLAT #53534
		if ( is_array($profile_details) && $profile_details['variations_mode'] == 'flat' && $product->is_type( 'variable' ) ) {
			$error_msg = "Skipping variable parent product in flat variations mode (SKU: {$item['sku']})";
			WPLA()->logger->debug( $error_msg );
			return new \WP_Error( 'wpla_flat_variations_parent', $error_msg );
		}

		if ( apply_filters( 'wpla_filter_skip_listing_feed_item', false, $item, $product, $profile ) === true ) {
			$error_msg = "Listing skipped by filter wpla_filter_skip_listing_feed_item (SKU: {$item['sku']})";
			WPLA()->logger->info( $error_msg );
			return new \WP_Error( 'wpla_filter_skip', $error_msg );
		}

		return true;
	}

	private function replaceShortcodes( $value, $property, $listing, $product_id, $profile, $skip_profile_fallback = false ) {
		// use profile value as it is - if $value is still empty (ie. there is no product level value for this column)
		if ( !$skip_profile_fallback && ($value === null || $value === '') ) {
			$profile_fields = !empty($profile->fields) ? maybe_unserialize($profile->fields) : array();
			$value = AmazonSchemaFormGenerator::stripEnumComposite( $profile_fields[ $property ] ?? '' );
		}

		// If value is an array, process each string element that contains a shortcode.
		// Use $skip_profile_fallback = true on recursive calls to prevent infinite loops
		// (empty elements should not pull in the profile value again).
		if (is_array($value)) {
			foreach ($value as $k => $v) {
				// Only recurse into string elements containing shortcodes.
				// Non-string elements (integers, nested arrays) are passed through unchanged.
				if ( is_string($v) && strpos($v, '[') !== false ) {
					$value[$k] = $this->replaceShortcodes($v, $property, $listing, $product_id, $profile, true);
				}
			}
			return $value;
		}

		// Now $value is a string, so process shortcodes (including indexed shortcodes like [attribute_name][0])
		$product = wc_get_product( $listing['post_id'] ?? null );
		if ( preg_match_all( '/\[([^\]]+)\](?:\[([0-9]+)\])?/', $value, $matches ) ) {
			foreach ($matches[0] as $placeholder) {
				wpla_logger_start_timer('parseProfileShortcode');
				$value = self::parseProfileShortcode( $value, $placeholder, $listing, $product, $product_id, $profile );
				wpla_logger_end_timer('parseProfileShortcode');
			}
		}
		WPLA()->logger->debug( 'value after parseProfileShortcode: '. $value );

		return $value;
	}

	private function handleVariationAttributes( $value, $property, $listing, $product, $profile ) {
		// handle variation attribute values / attribute columns
		if ( in_array( $product->get_type(), array('variation','variable', 'variable-product-part', 'product-part-variation') ) ) {

			if ( substr( $property, 0, 6 ) == 'color[' || substr( $property, 0, 5 ) == 'size[' ) {
				wpla_logger_start_timer('parseVariationAttributeColumn');
				$value = self::parseVariationAttributeColumn( $value, $property, $listing, $product, $profile );
				wpla_logger_end_timer('parseVariationAttributeColumn');
			}
		}

		WPLA()->logger->debug( 'value after parseVariationAttributeColumn: '. $value );
		return $value;
	}

	private function handleSizeAttributes( $value, $property, $listing, $profile ) {
		/**
		 * Handle size columns and their size map conversions
		 */
		if ( $value ) {
			$custom_size_map = get_option( 'wpla_custom_size_map', array() );
			//WPLA()->logger->debug( 'Handling size columns: '. print_r( $property, true ) );
			//if ( isset( $profile_fields[$column] ) && ! empty( $profile_fields[$column] ) ) $value = $profile_fields[$column];
			//WPLA()->logger->debug( 'Current value: '. print_r($value, true) );

			// Extract top-level property name from full JSON path (e.g. 'apparel_size[0][size]' -> 'apparel_size')
			$top_property = preg_replace( '/\[.*/', '', $property );

			if ( !empty( $custom_size_map[ $top_property ] ) ) {
				WPLA()->logger->info( 'Found size map for column' );

				$excluded_markets = get_option( 'wpla_sizemap_excluded_markets', array() );
				$item_market = WPLA()->accounts[ $listing['account_id'] ]->market_code;

				if ( in_array( $item_market, $excluded_markets ) ) {
					WPLA()->logger->info( 'Item is in the excluded markets sizemap array. Skipping mapping.' );
				} else {
					if (is_array($value)) {
						foreach ($value as $key => $sub_value) {
							if ( array_key_exists( $sub_value, $custom_size_map[ $top_property ] ) ) {
								$value[$key] = $custom_size_map[ $top_property ][ $sub_value ];
								WPLA()->logger->info( 'Found replacement for value. New value: '. $value[$key] );
							}
						}
					} else {
						if ( array_key_exists( $value, $custom_size_map[ $top_property ] ) ) {
							$value = $custom_size_map[ $top_property ][ $value ];
							WPLA()->logger->info( 'Found replacement for value. New value: ' . $value );
						}
					}
				}
			}
		}

		return $value;
	}

	/**
	 * Enrich size attributes with size_class, size_system, and proper size values
	 * Processes all size-related attributes and ensures they have the required Amazon fields
	 *
	 * @param array $attributes The attributes array
	 * @param array $listing The listing data
	 * @return array Modified attributes with enriched size data
	 */
	private function enrichSizeAttributes( $attributes, $listing ) {
		// Get marketplace code for size system detection
		$marketplace_code = 'US';
		if ( isset( $listing['account_id'] ) && isset( WPLA()->accounts[ $listing['account_id'] ] ) ) {
			$marketplace_code = WPLA()->accounts[ $listing['account_id'] ]->market_code;
		}

		// Get custom size map to check which fields have custom mappings
		$custom_size_map = get_option( 'wpla_custom_size_map', array() );

		// Check excluded markets - custom mappings are not applied to excluded markets
		$excluded_markets = get_option( 'wpla_sizemap_excluded_markets', array() );
		$item_market      = WPLA()->accounts[ $listing['account_id'] ]->market_code ?? 'US';
		$is_excluded      = in_array( $item_market, $excluded_markets );

		// Get list of size properties to process
		$size_properties = SizeMapper::getSizeProperties();

		foreach ( $size_properties as $size_property ) {
			if ( isset( $attributes[ $size_property ] ) && ! empty( $attributes[ $size_property ] ) ) {
				WPLA()->logger->info( sprintf(
					'Enriching size attribute: %s for marketplace: %s',
					$size_property,
					$marketplace_code
				) );

				// Skip size mapping if this property has a custom mapping and is not in excluded market
				$has_custom_mapping = ! empty( $custom_size_map[ $size_property ] ) && ! $is_excluded;

				$attributes[ $size_property ] = SizeMapper::enrichSizeData(
					$attributes[ $size_property ],
					$marketplace_code,
					$has_custom_mapping
				);
			}
		}

		return $attributes;
	}

	/**
	 * Apply proper data types to field values based on Amazon's schema requirements
	 * Converts string values to appropriate types (boolean, integer, float, array) based on field patterns
	 * @param array $array
	 * @return array
	 */
	private function applyProperDataTypes($array) {
		return $this->applyDataTypesRecursive($array);
	}

	/**
	 * Recursively apply data types to array elements based on field name patterns
	 * @param mixed $data
	 * @param string $path Current field path for pattern matching
	 * @return mixed
	 */
	private function applyDataTypesRecursive($data, $path = '') {
		if (!is_array($data)) {
			return $this->castValueByPath($data, $path);
		}

		foreach ($data as $key => $value) {
			$current_path = $path ? $path . '[' . $key . ']' : $key;
			$data[$key] = $this->applyDataTypesRecursive($value, $current_path);
		}

		return $data;
	}

	/**
	 * Cast a value to the appropriate type based on its field path
	 * @param mixed $value
	 * @param string $path
	 * @return mixed
	 */
	private function castValueByPath($value, $path) {
		// Array fields - handle these even if not strings
		if ($this->isArrayField($path)) {
			return $this->convertToArray($value);
		}

		// Only process string values that need conversion for other types
		if (!is_string($value) || empty($value)) {
			return $value;
		}

		// Boolean fields - convert 'true'/'false' strings to actual booleans
		if ($this->isBooleanField($path)) {
			return $this->convertToBoolean($value);
		}

		// Integer fields - convert numeric strings to integers
		if ($this->isIntegerField($path)) {
			return $this->convertToInteger($value);
		}

		// Float fields - convert numeric strings to floats
		if ($this->isFloatField($path)) {
			return $this->convertToFloat($value);
		}

		return $value;
	}

	/**
	 * Check if a field path represents a boolean field
	 * @param string $path
	 * @return bool
	 */
	private function isBooleanField($path) {
		$boolean_patterns = [
			'/batteries_required.*\[value\]/',
			'/supplier_declared_has_product_identifier_exemption.*\[value\]/',
			'/is_liquid_double_sealed.*\[value\]/',
			'/contains_liquid_contents.*\[value\]/',
			'/is_heat_sensitive.*\[value\]/',
			'/batteries_included.*\[value\]/',
		];

		foreach ($boolean_patterns as $pattern) {
			if (preg_match($pattern, $path)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if a field path represents an integer field
	 * @param string $path
	 * @return bool
	 */
	private function isIntegerField($path) {
		$integer_patterns = [
			'/fulfillment_availability.*\[quantity\]/',
			'/.*\[lead_time_to_ship_max_days\]/',
			'/unit_count\[\d+\]\[value\]/',
			'/number_of_items.*\[value\]/',
			'/fc_shelf_life.*\[value\]/',
			'/recommended_browse_nodes\[\d+\]\[value\]/',
		];

		foreach ($integer_patterns as $pattern) {
			if (preg_match($pattern, $path)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if a field path represents a float field
	 * @param string $path
	 * @return bool
	 */
	private function isFloatField($path) {
		$float_patterns = [
			'/.*price.*\[value_with_tax\]/',
			'/list_price.*\[value_with_tax\]/',
			'/item_weight.*\[value\]/',
			'/item_package_weight.*\[value\]/',
			'/liquid_volume.*\[value\]/',
			'/.*_dimensions.*\[value\]/',
		];

		foreach ($float_patterns as $pattern) {
			if (preg_match($pattern, $path)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if a field path represents an array field
	 * @param string $path
	 * @return bool
	 */
	private function isArrayField($path) {
		return preg_match('/\[standardized_values\]$/', $path);
	}

	/**
	 * Convert string to boolean
	 * @param string $value
	 * @return bool|string
	 */
	private function convertToBoolean($value) {
		$value_lower = strtolower(trim($value));

		// Only convert if it's actually a boolean-like string
		if (in_array($value_lower, ['true', 'false', '1', '0'], true)) {
			return in_array($value_lower, ['true', '1'], true);
		}

		// Return original value if not boolean-like
		return $value;
	}

	/**
	 * Convert string to integer
	 * @param string $value
	 * @return int
	 */
	private function convertToInteger($value) {
		return is_numeric($value) ? (int) $value : 0;
	}

	/**
	 * Convert string to float
	 * @param string $value
	 * @return float
	 */
	private function convertToFloat($value) {
		return is_numeric($value) ? (float) $value : 0.0;
	}

	/**
	 * Convert string to array if needed
	 * @param string $value
	 * @return array
	 */
	private function convertToArray($value) {
		// If it's already an array, return as-is
		if (is_array($value)) {
			return $value;
		}

		// Convert single value to array
		return [$value];
	}

	/**
	 * Remove empty fields. Empty fields are fields that have no properties or only have a marketplace_id and/or language_tag sub-property
	 * Also excludes is_inventory_available field completely to prevent it from appearing in feeds
	 * Preserves fields that were intentionally set to [---] placeholder (tracked in $this->intentionally_empty_fields)
	 * @param array $fields
	 * @param string $path Current path in nested array (for tracking [---] fields)
	 *
	 * @return array
	 */
	private function filterEmptyFields($array, $path = '') {
		// Special handling for purchasable_offer discounted_price section
		if (isset($array['purchasable_offer'])) {
			foreach ($array['purchasable_offer'] as $offer_index => $offer) {
				if (isset($offer['discounted_price'])) {
					$has_sale_price = false;
					$sale_price = null;
					
					// Check if any discounted_price entry has a non-empty value_with_tax
					foreach ($offer['discounted_price'] as $discount_price) {
						if (isset($discount_price['schedule'])) {
							foreach ($discount_price['schedule'] as $schedule) {
								if (!empty($schedule['value_with_tax'])) {
									$has_sale_price = true;
									$sale_price = $schedule['value_with_tax'];
									break 2; // Break out of both loops
								}
							}
						}
					}
					
					// Get the regular price for comparison
					$regular_price = null;
					if (isset($offer['our_price'])) {
						foreach ($offer['our_price'] as $our_price) {
							if (isset($our_price['schedule'])) {
								foreach ($our_price['schedule'] as $schedule) {
									if (!empty($schedule['value_with_tax'])) {
										$regular_price = $schedule['value_with_tax'];
										break 2; // Break out of both loops
									}
								}
							}
						}
					}
					
					// If no sale price found or sale price equals regular price, remove entire discounted_price section
					if (!$has_sale_price || ($sale_price !== null && $regular_price !== null && $sale_price === $regular_price)) {
						unset($array['purchasable_offer'][$offer_index]['discounted_price']);
					}
				}
			}
		}

		foreach ($array as $key => $value) {
			// Always exclude is_inventory_available field regardless of value
			if ($key === 'is_inventory_available') {
				unset($array[$key]);
				continue;
			}
			
			if (is_array($value)) {
				// Build path for nested arrays
				$current_path = $path ? "{$path}[{$key}]" : $key;

				// Recursively filter nested arrays with path
				$array[$key] = $this->filterEmptyFields($value, $current_path);

				if ( $array[ $key ] == [] ) {
					unset( $array[ $key ] );
				} else {
					// Only remove empty structural containers
					if ($this->isEmptyContainer($key, $array[$key], $path)) {
						unset($array[$key]);
					}
				}
			} elseif ( $value == "" && !in_array( $key, ['marketplace_id', 'language_tag', 'currency', 'unit', 'name'] ) ) {
				// Build full path for this field
				$current_path = $path ? "{$path}[{$key}]" : $key;

				// Don't filter out fields that were intentionally set to [---]
				if ( !isset($this->intentionally_empty_fields[$current_path]) ) {
					unset( $array[ $key ] );
				}
				// else: keep the empty value to send to Amazon (clears the attribute)
			}
		}
		return $array;
	}

	/**
	 * Filter attributes based on product type schema to prevent NoAdditionalPropertiesError
	 *
	 * @param array $fields The feed fields array
	 * @param array $schema The product type schema
	 * @return array Filtered fields array
	 */
	private function filterSchemaAttributes( $fields, $schema ) {
		if ( empty( $schema ) || empty( $schema['properties'] ) ) {
			return $fields;
		}

		$allowed_properties = array_keys( $schema['properties'] );
		$filtered_fields = [];
		$removed_attributes = [];

		foreach ( $fields as $key => $value ) {
			if ( in_array( $key, $allowed_properties ) ) {
				$filtered_fields[$key] = $value;
			} else {
				$removed_attributes[] = $key;
			}
		}

		// Log removed attributes for debugging
		if ( ! empty( $removed_attributes ) ) {
			WPLA()->logger->info( 'Schema validation: Removed invalid attributes: ' . implode( ', ', $removed_attributes ) );
		}

		return $filtered_fields;
	}

	/**
	 * Conservative check for empty containers
	 * Only removes containers with ONLY metadata fields that are actually empty
	 * Preserves containers that have intentionally empty fields (set via [---] placeholder)
	 *
	 * @param string $key
	 * @param array $array
	 * @param string $path Current path for tracking intentionally empty fields
	 * @return bool
	 */
	private function isEmptyContainer($key, $array, $path = '') {
		// Check if this container or any of its children are intentionally empty (from [---])
		$current_path = $path ? "{$path}[{$key}]" : $key;
		foreach ($this->intentionally_empty_fields as $intentional_path => $value) {
			// If this path or any child path is intentionally empty, preserve the container
			if (strpos($intentional_path, $current_path) === 0) {
				return false; // Preserve containers with intentionally empty fields
			}
		}

		// Special handling for content structures - check if they have actual content
		$content_field_map = [
			'variation_theme' => 'name',
			'brand' => ['name', 'value'],
			'department' => ['name', 'value'],
			'item_name' => 'value',
			'product_description' => 'value',
			'bullet_point' => 'value',
			'generic_keyword' => 'value',
			'special_feature' => 'value',
		];
		
		if (isset($content_field_map[$key])) {
			$content_fields = (array)$content_field_map[$key];
			// Check if any content field has a value
			foreach ($content_fields as $content_field) {
				if (isset($array[$content_field]) && $array[$content_field] !== '' && $array[$content_field] !== null) {
					return false; // Has actual content, preserve it
				}
			}
		}
		
		// Only remove containers with ONLY pure structural metadata AND no content
		$pure_structural_fields = ['marketplace_id', 'language_tag', 'currency', 'unit'];
		
		// Must contain ONLY structural fields (no content fields)
		$non_structural_keys = array_diff_key($array, array_flip($pure_structural_fields));
		if (!empty($non_structural_keys)) {
			// Check if the non-structural keys have actual content
			foreach ($non_structural_keys as $content_key => $content_value) {
				if ($content_value !== '' && $content_value !== null) {
					return false; // Has content, preserve it
				}
			}
		}
		
		return true; // Only empty structural metadata or empty content fields, safe to remove
	}

	/**
	 * This fixes bullet_point and similar arrays from having gaps like [0,1,4] to [0,1,2]
	 * 
	 * @param array $array
	 * @return array
	 */
	private function reindexArrays( $array ) {
		// Arrays that should be converted from associative to indexed
		$arrays_to_reindex = [
			'bullet_point',
			'generic_keyword',
			'special_features',
			'department',
			'lifestyle',
			'target_audience_keyword',
			'ingredients',
			'other_product_image_locator',
			'other_offer_image_locator',
			'compatible_with_vehicle_type',
			'compatibility_options',
			'material',
			'recommended_browse_nodes',
			'theme',
			'purchasable_offer'
		];
		
		foreach ( $array as $key => $value ) {
			if ( is_array( $value ) && in_array( $key, $arrays_to_reindex ) ) {
				// Check if this is an array with numeric keys (string or int)
				$keys = array_keys( $value );
				$has_numeric_keys = !empty( $keys ) && array_reduce( $keys, function( $carry, $k ) {
					return $carry && is_numeric( $k );
				}, true );
				
				if ( $has_numeric_keys ) {
					// Build a new indexed array
					$indexed_array = [];
					foreach ( $value as $item ) {
						$indexed_array[] = $item;
					}
					$array[ $key ] = $indexed_array;
				}
			}
		}
		
		return $array;
	}

	/**
	 * Recursively insert marketplace_id and language_tag values
	 * @param $fields
	 * @param $marketplace
	 * @param $language
	 *
	 * @return mixed
	 */
	private function insertMarketData( $fields, $marketplace, $language ) {
		foreach ( $fields as $key => $value ) {
			if ( is_array( $value ) ) {
				if ( isset( $value['marketplace_id'] ) ) {
					$value['marketplace_id'] = $marketplace;
				}

				if ( isset( $value['language_tag'] ) ) {
					$value['language_tag'] = $language;
				}
				$fields[ $key ] = $this->insertMarketData( $value, $marketplace, $language );
			}
		}

		return $fields;
	}

	/**
	 * Inject B2B offer fields when product has B2B pricing
	 *
	 * @param array $fields_arr The processed fields array
	 * @param int $product_id The product ID
	 * @param string $marketplace_id The marketplace ID
	 * @param array $listing The listing data
	 * @param WC_Product $product The WooCommerce product
	 * @param object $profile The listing profile
	 * @return array Modified fields array with B2B offer if needed
	 */
	private function injectB2BOfferFields( $fields_arr, $product_id, $marketplace_id, $listing, $product, $profile ) {
		// Check if product has B2B price
		$b2b_price = get_post_meta( $product_id, '_amazon_b2b_price', true );
		
		// If no product-level B2B price, check profile default B2B price
		if ( empty( $b2b_price ) && $profile && $profile->id ) {
			$profile_details = maybe_unserialize( $profile->details );
			$profile_b2b_price = isset( $profile_details['b2b_price'] ) ? $profile_details['b2b_price'] : '';
			if ( !empty( $profile_b2b_price ) ) {
				$b2b_price = $profile_b2b_price;
			}
		}
		
		// Process shortcodes if B2B price contains them
		if ( !empty($b2b_price) && is_string($b2b_price) && strpos($b2b_price, '[') !== false ) {
			$b2b_price = $this->replaceShortcodes( $b2b_price, 'b2b_price', $listing, $product_id, $profile );
		}
		
		// Apply B2B price filter  
		$b2b_price = apply_filters( 'wpla_filter_b2b_price', $b2b_price, $product_id, $product, $listing, $profile );
		
		// List of B2B-specific fields that should not be in B2C offers
		$b2b_specific_fields = ['quantity_discount_plan'];
		
		if ( empty( $b2b_price ) ) {
			// No B2B price: Clean up B2B fields from B2C offer
			if ( isset( $fields_arr['purchasable_offer'][0] ) ) {
				foreach ( $b2b_specific_fields as $field ) {
					unset( $fields_arr['purchasable_offer'][0][$field] );
				}
			}
			return $fields_arr;
		}

		// Get currency from the existing offer or default
		$currency = $fields_arr['purchasable_offer'][0]['currency'] ?? get_woocommerce_currency();

		// Start with basic B2B offer structure
		$b2b_offer = [
			'audience' => 'B2B',
			'currency' => $currency,
			'marketplace_id' => $marketplace_id,
			'our_price' => [
				[
					'schedule' => [
						[
							'value_with_tax' => (float) $b2b_price
						]
					]
				]
			]
		];

		// Move B2B-specific fields from offer[0] to offer[1]
		if ( isset( $fields_arr['purchasable_offer'][0] ) ) {
			foreach ( $b2b_specific_fields as $field ) {
				if ( isset( $fields_arr['purchasable_offer'][0][$field] ) ) {
					// Move the field to B2B offer
					$b2b_offer[$field] = $fields_arr['purchasable_offer'][0][$field];
					// Remove from B2C offer
					unset( $fields_arr['purchasable_offer'][0][$field] );
				}
			}
		}

		// Add the B2B offer as second offer
		$fields_arr['purchasable_offer'][1] = $b2b_offer;

		return $fields_arr;
	}

	private function buildQueryString( $fields ) {
		$str = '';

		if ( $fields ) {
			foreach ( $fields as $key => $value ) {
				if (is_array($value)) {
					foreach ($value as $v) {
						$str .= $key . '[]=' . urlencode($v) . '&';
					}
				} else {
					$str .= $key . '=' . urlencode($value) . '&';
				}
			}
			$str = rtrim( $str, '&' );
		}

		return $str;
	}

	/**
	 * Returns the basename of the property without its array structure (e.g. model_name[0][value] will be returned as model_name)
	 * @param string $property
	 *
	 * @return string
	 */
	private function getBaseProperty( $property ) {
		parse_str( $property, $parts );
		return array_key_first($parts);
	}

	/**
	 * @param array $listing
	 * @param WPLA_AmazonProfile $profile
	 * @param array $json_fields
	 *
	 * @return array
	 */

	private function getJsonFields( $listing, $profile, $json_fields = [] ) {
		if ( !empty( $json_fields ) ) {
			return $json_fields;
		}

		if ( !$profile->id ) {
			WPLA()->logger->info('no profile found, falling back to Inventory Loader');
			$json_fields = $this->getInventoryLoaderFields();
		} else {
			$product_type = $this->getListingProductType( $listing, $profile );
			$marketplace_id = $this->getListingMarketplaceId( $listing, $profile );

			if ( !$product_type || !$marketplace_id ) {
				// this is not a valid request
				return '[]';
			}

			// Handle Inventory Loader as special case
			if ( $product_type === 'Inventory Loader' ) {
				WPLA()->logger->info('using Inventory Loader product type fields');
				$json_fields = $this->getInventoryLoaderFields();
			} elseif ( $product_type === 'LISTING_OFFER_ONLY' ) {
				WPLA()->logger->info('using Offer Only fields');
				$json_fields = $this->getOfferOnlyFields();
			} else {
				// Disable pricing modifications from the Price Based on Country plugin
				$this->disableCountryBasedPricing();

				$schema      = $this->getSchemaFromCache( $product_type, $marketplace_id );

				if ( ! $schema ) {
					return [];
				}

				$form_gen    = new AmazonSchemaFormGenerator( $schema );
				$json_fields = $form_gen->getFields();
			}
		}

		return $json_fields;
	}

	private function getLanguageFromCache( $marketplace_id, $schema = null ) {
		if ( isset( $this->language_cache[ $marketplace_id ] ) ) {
			return $this->language_cache[ $marketplace_id ];
		}

		if ( $schema && !isset( $languages[ $marketplace_id ] ) ) {
			$this->languages_cache[ $marketplace_id ] = $schema['$defs']['language_tag']['default'];
			return $this->languages_cache[ $marketplace_id ];
		}

		return '';
	}

	public static function parseVariationAttributeColumn( $value, $column, $item, $product, $profile ) {
		$profile_fields  = $profile ? maybe_unserialize( $profile->fields )  : array();

		// skip if this is not an actual attribute column (like size_name or color_name)
		if ( in_array( $column, array( 'item_name', 'external_product_id_type', 'feed_product_type', 'brand_name' ) ) ) return $value;

		// Skip overriding the variation attribute if there's a profile value for it #55702
		if ( isset( $profile_fields[ $column ] ) && !empty( $profile_fields[ $column ] ) && apply_filters( 'wpla_override_variation_attribute_with_profile', false, $item, $column, $value, $profile ) ) {
			return $value;
		}

		// adjust some incompatible vtheme values
		$vtheme = $item['vtheme'];
		$vtheme = str_replace( 'Name', '', $vtheme ); 							// ColorName -> Color
		$vtheme = strtolower($vtheme) == 'sizecolor' ? 'Size-Color' : $vtheme; 	// sizecolor -> Size-Color
		$vtheme = strtolower($vtheme) == 'colorsize' ? 'Color-Size' : $vtheme; 	// colorsize -> Color-Size

		$vtheme_array   = explode( '-', $vtheme );
		$col_slug       = str_replace('_name', '', $column);
		$col_slug       = str_replace('_type', '', $col_slug);
		$attribute_name = false;

		// filter attributes used in variation-theme - maybe this should be moved to parseProductColumn() above...
		foreach ($vtheme_array as $vtheme_attribute) {
			$vtheme_attribute = self::convertToEnglishAttributeLabel( $vtheme_attribute );
			if ( strstr( $col_slug, strtolower($vtheme_attribute) ) !== false )
				$attribute_name = $vtheme_attribute;
		}
		if ( ! $attribute_name ) return $value;

		// parent product should have empty attributes
		if ( $product->get_type() == 'variable' || $product->get_type() == 'variable-product-part' ) return '';

		// find variation
		// $variations = WPLA_ProductWrapper::getVariations( $product->id );
		$parent_id = \WPLA_ProductWrapper::getVariationParent( wpla_get_product_meta( $product, 'id' ) );
		$variations = WPLA()->memcache->getProductVariations( $parent_id );

		foreach ($variations as $var) {
			if ( $var['sku'] == $item['sku'] ) {
				// find attribute value
				foreach ( $var['variation_attributes'] as $attribute_label => $attribute_value ) {
					$translated_label = self::convertToEnglishAttributeLabel( $attribute_label );
					if ( $translated_label == $attribute_name ) {
						// $value = utf8_decode( $attribute_value ); // Amazon is supposed to use UTF, but de facto accepts only ISO-8859-1/15
						$value = $attribute_value;
					}
				}
				// // find attribute value - doesn't work for non-english attributes
				// if ( isset( $var['variation_attributes'][$attribute_name] ) ) {
				// 	$value = $var['variation_attributes'][$attribute_name];
				// }
			}
		}

		return $value;
	} // parseVariationAttributeColumn()

	/**
	 * Convert Unicode escape sequences to native UTF-8 characters
	 * This fixes the issue where German umlauts and other special characters
	 * are being escaped as \u00fc instead of displayed as ü
	 *
	 * @param mixed $data Array or string data to process
	 * @return mixed Processed data with UTF-8 characters
	 */
	private function convertUnicodeEscapesToUtf8($data) {
		if (is_array($data)) {
			return array_map([$this, 'convertUnicodeEscapesToUtf8'], $data);
		}

		if (is_string($data)) {
			// Convert common German umlauts and special characters from Unicode escapes to UTF-8
			$replacements = [
				'\u00fc' => 'ü',  // ü
				'\u00e4' => 'ä',  // ä
				'\u00f6' => 'ö',  // ö
				'\u00dc' => 'Ü',  // Ü
				'\u00c4' => 'Ä',  // Ä
				'\u00d6' => 'Ö',  // Ö
				'\u00df' => 'ß',  // ß
				// Add more common special characters as needed
				'\u00e9' => 'é',  // é
				'\u00e8' => 'è',  // è
				'\u00ea' => 'ê',  // ê
				'\u00eb' => 'ë',  // ë
				'\u00c9' => 'É',  // É
				'\u00c8' => 'È',  // È
				'\u00ca' => 'Ê',  // Ê
				'\u00cb' => 'Ë',  // Ë
			];

			return str_replace(array_keys($replacements), array_values($replacements), $data);
		}

		return $data;
	}

	/**
	 * BATCH LOADING METHODS - Fix N+1 query problem
	 * These methods batch load all required data before processing items,
	 * reducing database queries from ~7,000 to ~3-10 per chunk.
	 */

	/**
	 * Batch load WooCommerce products
	 * Uses WooCommerce's wc_get_product() with internal caching for efficiency
	 *
	 * @param array $product_ids Array of product IDs to load
	 * @return array Associative array of product_id => WC_Product object
	 */
	protected static function load_products_batch( $product_ids ) {
		$products = [];

		// Remove duplicates and ensure IDs are integers
		$product_ids = array_unique( array_map( 'absint', array_filter( $product_ids ) ) );

		if ( empty( $product_ids ) ) {
			return $products;
		}

		// WooCommerce's wc_get_product() has internal caching,
		// but we still batch them together to minimize overhead
		foreach ( $product_ids as $product_id ) {
			$product = wc_get_product( $product_id );
			if ( $product ) {
				$products[ $product_id ] = $product;
			}
		}

		WPLA()->logger->debug( 'Batch loaded ' . count( $products ) . ' products from ' . count( $product_ids ) . ' IDs' );

		return $products;
	}

	/**
	 * Batch load post meta for multiple products and meta keys
	 * Single query instead of N queries per meta key
	 *
	 * @param array $product_ids Array of product IDs
	 * @param array $meta_keys Array of meta keys to load
	 * @return array Multi-dimensional array: product_id => meta_key => meta_value
	 */
	protected function load_post_meta_batch( $product_ids, $meta_keys ) {
		global $wpdb;

		// Remove duplicates and ensure IDs are integers
		$product_ids = array_unique( array_map( 'absint', array_filter( $product_ids ) ) );

		if ( empty( $product_ids ) || empty( $meta_keys ) ) {
			return [];
		}

		// Build SQL query to fetch all meta in one go
		$meta_keys_sql = "'" . implode( "','", array_map( 'esc_sql', $meta_keys ) ) . "'";
		$product_ids_sql = implode( ',', $product_ids );

		$results = $wpdb->get_results( "
			SELECT post_id, meta_key, meta_value
			FROM {$wpdb->postmeta}
			WHERE post_id IN ({$product_ids_sql})
			AND meta_key IN ({$meta_keys_sql})
		", ARRAY_A );

		// Organize results by product ID and meta key
		$meta_by_product = [];
		foreach ( $results as $row ) {
			$post_id = (int) $row['post_id'];
			$meta_key = $row['meta_key'];
			$meta_value = $row['meta_value'];

			if ( ! isset( $meta_by_product[ $post_id ] ) ) {
				$meta_by_product[ $post_id ] = [];
			}

			// Handle serialized data
			$meta_by_product[ $post_id ][ $meta_key ] = maybe_unserialize( $meta_value );
		}

		WPLA()->logger->debug( 'Batch loaded ' . count( $results ) . ' meta values for ' . count( $product_ids ) . ' products and ' . count( $meta_keys ) . ' meta keys' );

		return $meta_by_product;
	}

	/**
	 * Batch load Amazon profiles
	 * Single query to load all needed profiles instead of N queries
	 *
	 * @param array $profile_ids Array of profile IDs to load
	 * @return array Associative array of profile_id => WPLA_AmazonProfile object
	 */
	protected function load_profiles_batch( $profile_ids ) {
		global $wpdb;

		// Remove duplicates, filter out nulls/zeros, and ensure IDs are integers
		$profile_ids = array_unique( array_map( 'absint', array_filter( $profile_ids ) ) );

		if ( empty( $profile_ids ) ) {
			return [];
		}

		$profiles = [];
		$profile_ids_sql = implode( ',', $profile_ids );

		// Fetch all profile data in one query
		$table_name = $wpdb->prefix . 'amazon_profiles';
		$results = $wpdb->get_results( "
			SELECT *
			FROM {$table_name}
			WHERE id IN ({$profile_ids_sql})
		", ARRAY_A );

		// Convert to WPLA_AmazonProfile objects
		foreach ( $results as $row ) {
			$profile = new WPLA_AmazonProfile();
			foreach ( $row as $key => $value ) {
				$profile->$key = $value;
			}
			$profiles[ (int) $row['id'] ] = $profile;
		}

		WPLA()->logger->debug( 'Batch loaded ' . count( $profiles ) . ' profiles from ' . count( $profile_ids ) . ' IDs' );

		return $profiles;
	}

	/**
	 * Get a value from batch-loaded post meta with fallback to empty string
	 *
	 * @param array $meta_batch Batch-loaded meta array from load_post_meta_batch()
	 * @param int $product_id Product ID
	 * @param string $meta_key Meta key to retrieve
	 * @param mixed $default Default value if not found
	 * @return mixed Meta value or default
	 */
	protected function get_batch_meta( $meta_batch, $product_id, $meta_key, $default = '' ) {
		return $meta_batch[ $product_id ][ $meta_key ] ?? $default;
	}

	/**
	 * Get a product from batch-loaded products with fallback to null
	 *
	 * @param array $products_batch Batch-loaded products array from load_products_batch()
	 * @param int $product_id Product ID
	 * @return WC_Product|null Product object or null
	 */
	protected static function get_batch_product( $products_batch, $product_id ) {
		return $products_batch[ $product_id ] ?? null;
	}

	/**
	 * Get a profile from batch-loaded profiles with fallback to new profile
	 *
	 * @param array $profiles_batch Batch-loaded profiles array from load_profiles_batch()
	 * @param int $profile_id Profile ID
	 * @return WPLA_AmazonProfile Profile object (may be empty if not found)
	 */
	protected function get_batch_profile( $profiles_batch, $profile_id ) {
		// Return cached profile or create a new empty one
		if ( isset( $profiles_batch[ $profile_id ] ) ) {
			return $profiles_batch[ $profile_id ];
		}

		// Return empty profile as fallback (same behavior as new WPLA_AmazonProfile(null))
		return new WPLA_AmazonProfile();
	}


}