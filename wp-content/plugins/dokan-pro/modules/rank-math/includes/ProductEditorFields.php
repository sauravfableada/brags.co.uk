<?php

namespace WeDevs\DokanPro\Modules\RankMath;

use WeDevs\Dokan\ProductEditor\Elements;
use WeDevs\DokanPro\Product\FormSchema as ProFormSchema;

defined( 'ABSPATH' ) || exit;

/**
 * Adds the Rank Math SEO section to the new Product Form Manager.
 *
 * The schema/layout entries plug into Lite's existing Product Form Manager
 * filters; the React entry replaces the mount-point field with the Rank
 * Math metabox wrapper at runtime.
 *
 * @since 5.0.3
 */
class ProductEditorFields {

	/**
	 * Product Form Manager section id for the SEO card.
	 *
	 * @since 5.0.3
	 *
	 * @var string
	 */
	const SECTION_RANK_MATH_SEO = 'rank_math_seo';

	/**
	 * Schema field id the React variant swaps for the Rank Math metabox.
	 *
	 * @since 5.0.3
	 *
	 * @var string
	 */
	const FIELD_MOUNT_POINT = 'rank_math_seo_mount';

	/**
	 * Field variant the React entry registers the SEO panel against.
	 *
	 * @since 5.0.3
	 *
	 * @var string
	 */
	const VARIANT_NAME = 'rank_math_seo';

	/**
	 * Script/style handle for the SEO panel bundle.
	 *
	 * @since 5.0.3
	 *
	 * @var string
	 */
	const SCRIPT_HANDLE = 'dokan-rank-math-product-editor';

	public function __construct() {
		add_filter( 'dokan_product_editor_schema', array( $this, 'extend_default_fields' ) );
		add_filter( 'dokan_product_editor_layouts', array( $this, 'extend_layouts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_product_editor_scripts' ) );
	}

	/**
	 * Whether the SEO section should be rendered for the vendor.
	 *
	 * Honors both the section-level and the field-level visibility toggle
	 * the admin can set in the Product Form Manager.
	 *
	 * @since 5.0.3
	 *
	 * @return bool
	 */
	public static function is_section_visible(): bool {
		if ( ! class_exists( ProFormSchema::class ) ) {
			return true;
		}

		$saved = get_option( ProFormSchema::SETTINGS_KEY, array() );
		if ( ! is_array( $saved ) ) {
			return true;
		}

		foreach ( $saved as $item ) {
			if ( empty( $item['id'] ) ) {
				continue;
			}

			$is_ours = self::SECTION_RANK_MATH_SEO === $item['id']
				|| self::FIELD_MOUNT_POINT === $item['id'];

			if ( $is_ours && array_key_exists( 'visibility', $item ) && false === (bool) $item['visibility'] ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Registers the SEO section and its mount-point field in the schema.
	 *
	 * @since 5.0.3
	 *
	 * @param array $fields Flat schema items.
	 *
	 * @return array
	 */
	public function extend_default_fields( array $fields ): array {
		$fields[] = array(
			'id'          => self::SECTION_RANK_MATH_SEO,
			'type'        => 'section',
			'label'       => __( 'Rank Math SEO', 'dokan' ),
			'description' => __( 'Manage SEO for this product', 'dokan' ),
			'visibility'  => true,
			'priority'    => 45,
		);

		$fields[] = array(
			'id'          => self::FIELD_MOUNT_POINT,
			'section_id'  => self::SECTION_RANK_MATH_SEO,
			'type'        => 'field',
			'variant'     => self::VARIANT_NAME,
			'label'       => __( 'Rank Math SEO Panel', 'dokan' ),
			'description' => __( 'Embed the Rank Math SEO panel inside the vendor product editor.', 'dokan' ),
			'visibility'  => true,
		);

		return $fields;
	}

	/**
	 * Inserts the SEO card between Inventory (40) and Shipping by pushing
	 * Shipping to priority 50 and placing SEO at 45.
	 *
	 * @since 5.0.3
	 *
	 * @param array $layouts Flat layout items.
	 *
	 * @return array
	 */
	public function extend_layouts( array $layouts ): array {
		if ( ! self::is_section_visible() ) {
			return $layouts;
		}

		foreach ( $layouts as &$layout ) {
			if ( isset( $layout['id'] ) && Elements::SECTION_SHIPPING === $layout['id'] ) {
				$layout['priority'] = 50;
				break;
			}
		}
		unset( $layout );

		$layouts[] = array(
			'id'        => self::SECTION_RANK_MATH_SEO,
			'parent_id' => Elements::PRIMARY_COLUMN,
			'priority'  => 45,
			'layout'    => array(
				'type'       => 'card',
				'withHeader' => true,
			),
		);

		return $layouts;
	}

	/**
	 * Enqueues the React mount bundle on the new vendor product editor.
	 *
	 * @since 5.0.3
	 *
	 * @return void
	 */
	public function enqueue_product_editor_scripts() {
		if ( ! $this->is_new_product_editor() ) {
			return;
		}

		// Boot the metabox first so `rank-math-editor` exists before we register against it.
		$this->boot_rank_math_metabox();
		if ( ! $this->register_assets() ) {
			return;
		}

		wp_enqueue_script( self::SCRIPT_HANDLE );
		if ( wp_style_is( self::SCRIPT_HANDLE, 'registered' ) ) {
			wp_enqueue_style( self::SCRIPT_HANDLE );
		}
	}

	/**
	 * Whether the current request is the new vendor product editor page.
	 *
	 * @since 5.0.3
	 *
	 * @return bool
	 */
	protected function is_new_product_editor(): bool {
		return self::is_section_visible() && self::is_new_dashboard_request();
	}

	/**
	 * Whether the current request is a new vendor dashboard page.
	 *
	 * @since 5.0.3
	 *
	 * @return bool
	 */
	public static function is_new_dashboard_request(): bool {
		global $wp;

        // Validate new dashboard request; Match Lite's NewDashboard::enqueue_scripts() detection.
		return ! is_admin() && dokan_is_seller_dashboard() && isset( $wp->query_vars['new'] );
	}

	/**
	 * Boots Rank Math's metabox for the edited product on the new route.
	 *
	 * Mirrors the legacy editor's enqueue flow, which
	 * `dokan_vendor_dashboard_script_loaded` does not always reach here.
	 *
	 * @since 5.0.3
	 *
	 * @return void
	 */
	protected function boot_rank_math_metabox() {
		if ( wp_script_is( 'rank-math-editor', 'enqueued' ) ) {
			return;
		}

		$cmb2_bootstrap = dokan_pro()->module->rank_math->get_cmb2_bootstrap_class();
		if ( null === $cmb2_bootstrap ) {
			return;
		}

		// Swap global $post to the edited product so Rank Math localises that product's data — the SPA hash route leaves $post on the dashboard page.
		$product_id   = (int) get_user_meta( get_current_user_id(), 'dokan_rank_math_edit_post_id', true );
		$product_post = $product_id > 0 ? get_post( $product_id ) : null;
		$swap_post    = $product_post instanceof \WP_Post && 'product' === $product_post->post_type;

		global $post;
		$original_post = $post;
		if ( $swap_post ) {
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$post = $product_post;
			setup_postdata( $post );
		}

		$cmb2_bootstrap::initiate()->include_cmb();
		( new Frontend() )->process();

		if ( $swap_post ) {
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$post = $original_post;
			if ( $original_post instanceof \WP_Post ) {
				setup_postdata( $original_post );
			} else {
				wp_reset_postdata();
			}
		}
	}

	/**
	 * Registers the SEO panel script and style; returns false when the build is missing.
	 *
	 * @since 5.0.3
	 *
	 * @return bool
	 */
	protected function register_assets(): bool {
		if ( wp_script_is( self::SCRIPT_HANDLE, 'registered' ) ) {
			return true;
		}

		$asset_file = DOKAN_RANK_MATH_PATH . '/assets/js/product-editor-rank-math.asset.php';
		if ( ! file_exists( $asset_file ) ) {
			return false;
		}

		$asset = require $asset_file;
		$deps  = $asset['dependencies'] ?? array();

		// Depend on Rank Math's editor bundle so its store exists before we re-point it.
		if ( wp_script_is( 'rank-math-editor', 'registered' ) ) {
			$deps[] = 'rank-math-editor';
		}

		wp_register_script(
			self::SCRIPT_HANDLE,
			plugins_url( 'assets/js/product-editor-rank-math.js', DOKAN_RANK_MATH_FILE ),
			$deps,
			$asset['version'],
			true
		);
		wp_set_script_translations( self::SCRIPT_HANDLE, 'dokan' );

		$css_path = DOKAN_RANK_MATH_PATH . '/assets/js/product-editor-rank-math.css';
		if ( file_exists( $css_path ) ) {
			wp_register_style(
				self::SCRIPT_HANDLE,
				plugins_url( 'assets/js/product-editor-rank-math.css', DOKAN_RANK_MATH_FILE ),
				array(),
				$asset['version']
			);
		}

		return true;
	}
}
