<?php

namespace WCML\Synchronization;

class Manager {

	const CONTEXT_PRODUCT_EDIT_SCREEN_UPDATE = 'product_edit_screen_update';
	const CONTEXT_PRODUCT_BULK_OR_QUICK_EDIT = 'product_bulk_or_quick_edit';

	/** @var \WPML_Post_Translation */
	private $postTranslations;

	/** @var Store */
	private $syncStore;

	/** @var string|null $context */
	private $context;

	/**
	 * @param Store $syncStore
	 */
	public function __construct( Store $syncStore ) {
		$this->syncStore        = $syncStore;

		global $wpml_post_translations;
		$this->postTranslations = $wpml_post_translations;
	}

	/**
	 * @param string|null $context
	 *
	 * @return void
	 */
	public function setContext( $context ) {
		$this->context = $context;
	}

	/**
	 * @param string|array $context
	 *
	 * @return bool
	 */
	private function isInContext( $context ) {
		$context = (array) $context;

		return in_array( $this->context, $context, true );
	}

	/**
	 * @param \WP_Post $product
	 *
	 * @return \WP_Post|array|null
	 */
	public function getOriginalProduct( $product ) {
		$originalProduct   = $product;
		$originalProductId = $this->postTranslations->get_original_element( $product->ID ) ?: $product->ID;
		if ( $originalProductId !== $product->ID ) {
			$originalProduct = get_post( $originalProductId );
		}
		return $originalProduct;
	}

	/**
	 * @param \WP_Post $product
	 *
	 * @return bool
	 */
	public function isOriginalProduct( $product ) {
		return null === $this->postTranslations->get_source_lang_code( $product->ID );
	}

	/**
	 * @param int $elementId
	 *
	 * @return string
	 */
	public function getElementLanguage( $elementId ) {
		return $this->postTranslations->get_element_lang_code( $elementId );
	}

	/**
	 * @param int       $elementId
	 * @param int|false $trid
	 * @param bool      $actualtranslationsOnly
	 *
	 * @return int[]
	 */
	public function getElementTranslations( $elementId, $trid = false, $actualtranslationsOnly = true ) {
		return $this->postTranslations->get_element_translations( $elementId, $trid, $actualtranslationsOnly );
	}

	/**
	 * @param \WP_Post          $product
	 * @param int[]             $translationsIds
	 * @param array<int,string> $translationsLanguages
	 */
	public function run( $product, $translationsIds = [], $translationsLanguages = [] ) {
		$originalProduct = $this->getOriginalProduct( $product );

		if ( empty( $translationsIds ) ) {
			$translationsIds = $this->postTranslations->get_element_translations( $originalProduct->ID, false, true );
		}

		if ( empty( $translationsIds ) ) {
			return;
		}

		foreach ( $translationsIds as $translationId ) {
			if ( isset( $translationsLanguages[ $translationId ] ) ) {
				continue;
			}
			$translationsLanguages[ $translationId ] = $this->getElementLanguage( $translationId );
		}

		do_action( 'wcml_before_sync_product', $originalProduct->ID, $product->ID );
		$this->runProductComponents( $originalProduct, $translationsIds, $translationsLanguages );
		do_action( 'wcml_after_sync_product', $originalProduct->ID, $product->ID );
	}

	/**
	 * @param \WP_Post          $product
	 * @param int[]             $translationsIds
	 * @param array<int,string> $translationsLanguages
	 */
	public function runProductComponents( $product, $translationsIds, $translationsLanguages ) {
		$components = $this->getComponentsByPostType( $product->post_type );
		if ( empty( $components ) ) {
			return;
		}

		foreach ( $translationsIds as $translationId ) {
			do_action( 'wcml_before_sync_product_data', $product->ID, $translationId, $translationsLanguages[ $translationId ] );
		}

		
		foreach ( $components as $componentName ) {
			$this->runComponent( $product, $translationsIds, $translationsLanguages, $componentName );
		}

		$wcmlDataStore = wcml_product_data_store_cpt();

		foreach ( $translationsIds as $translationId ) {
			$wcmlDataStore->update_lookup_table_data( $translationId );
			wc_delete_product_transients( $translationId );
			do_action( 'wcml_after_sync_product_data', $product->ID, $translationId, $translationsLanguages[ $translationId ] );
		}

		// Run cleanup, on product / product parent, abd eventually in translations and translations parens.
		// EVALUATE which ones runs on the product and which ones in the translations too.
		// Data store lookup
		// Cache
		// Transients
	}

	/**
	 * @param \WP_Post          $variation
	 * @param int[]             $translationsIds
	 * @param array<int,string> $translationsLanguages
	 */
	public function runProductVariationComponents( $variation, $translationsIds, $translationsLanguages ) {
		$components = $this->getComponentsByPostType( $variation->post_type );
		if ( empty( $components ) ) {
			return;
		}

		foreach ( $components as $componentName ) {
			$this->runComponent( $variation, $translationsIds, $translationsLanguages, $componentName );
		}

		$wcmlDataStore = wcml_product_data_store_cpt();

		foreach ( $translationsIds as $translationId ) {
			$wcmlDataStore->update_lookup_table_data( $translationId );
		}
	}

	/**
	 * @param \WP_Post          $product
	 * @param int[]             $translationsIds
	 * @param array<int,string> $translationsLanguages
	 * @param string            $componentName
	 */
	public function runComponent( $product, $translationsIds, $translationsLanguages, $componentName ) {
		$component = $this->syncStore->getComponent( $componentName );
		$component->run( $product, $translationsIds, $translationsLanguages );
	}

	/**
	 * @param string $postType
	 *
	 * @return string[]
	 */
	private function getComponentsByPostType( $postType ) {
		$components = [];

		if ( 'product' === $postType ) {
			$components = [
				Store::COMPONENT_ATTACHMENTS,// CONFIRMED
				Store::COMPONENT_ATTRIBUTES,// TAX CONFIRMED | MERA CONFIRMED
				Store::COMPONENT_DOWNLOADABLE_FILES,// CONFIRMED, CAN BE IMPROVED
				Store::COMPONENT_LINKED,// CONFIRMED
				Store::COMPONENT_POST,// CONFIRMED
				Store::COMPONENT_STOCK,// CONFIRMED
				Store::COMPONENT_TAXONOMIES,// CONFIRMED
				Store::COMPONENT_META,// CONFIRMED
			];

			if ( ! $this->isInContext( [ self::CONTEXT_PRODUCT_EDIT_SCREEN_UPDATE, self::CONTEXT_PRODUCT_BULK_OR_QUICK_EDIT ] ) ) {
				$components[] = Store::COMPONENT_VARIATIONS; // CONFIRMED
			}

		} elseif ( 'product_variation' === $postType ) {
			$components = [
				Store::COMPONENT_VARIATION_ATTACHMENTS,// CONFIRMED
				Store::COMPONENT_VARIATION_META,// CONFIRMED
				Store::COMPONENT_DOWNLOADABLE_FILES,// CONFIRMED, CAN BE IMPROVED
				Store::COMPONENT_VARIATION_TAXONOMIES,// CONFIRMED
				Store::COMPONENT_STOCK,// CONFIRMED
			];
		}

		return $components;
	}

}
