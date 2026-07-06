<?php
/**
 * Editor-scoped sync mode — post-save notices in the product editor.
 *
 * Sync Summary notice: in Editor-scoped mode, when the previous request synced
 * fewer variations than the product has, renders a collapsed-by-default notice
 * summarising what was synced + a "Force update all" button.
 *
 * Switch Hint notice: in Complete sync mode, when all variations were checked
 * but none had changes (wasted iteration), suggests switching to Editor-scoped
 * mode. The suggestion can be dismissed permanently.
 *
 * @package WCML\EditorScopedSync
 */

namespace WCML\EditorScopedSync;

class Notices implements \IWPML_Backend_Action {

	const SYNC_SUMMARY_TRANSIENT_PREFIX = 'wcml_editor_scoped_last_save_';
	const SWITCH_HINT_TRANSIENT_PREFIX  = 'wcml_editor_scoped_heavy_load_';
	const TRANSIENT_TTL                 = 600; // 10 minutes — long enough that the next page load picks it up

	// Runs after all WC/WCML save processing has completed so the full sync picture is available.
	const PRIORITY_AFTER_ALL_SAVE_PROCESSING = 999;
	// Runs just before PRIORITY_AFTER_ALL_SAVE_PROCESSING so the switch hint is ready if needed.
	const PRIORITY_BEFORE_SAVE_SUMMARY = 998;

	/**
	 * Switch Hint trigger floor: only suggest switching when V × L (variations × translation
	 * languages) crosses this many iterations.
	 *
	 * Per-iteration cost measured on a dedicated dev machine: ~6 ms / (variation × language).
	 * Typical client environment is shared hosting, where MySQL contention, smaller InnoDB
	 * buffer pools, and noisy-neighbor CPU push the realistic per-iteration cost to
	 * ~10–15 ms. At 12 ms / iteration, 120 iterations = ~1.4 s of WCML waste — clearly
	 * perceptible to the user, and well past the "feels slow" UX threshold.
	 *
	 * Lowering this further would catch tinier catalogs at the cost of prompting users
	 * whose saves are still fast enough to ignore.
	 *
	 * Override via the `wcml_editor_scoped_heavy_load_threshold` filter.
	 */
	const SWITCH_HINT_ITERATION_THRESHOLD = 120;

	// Key within _wcml_settings for the permanent Switch Hint dismiss.
	const SWITCH_HINT_DISMISS_KEY = 'editor_scoped_sync_switch_hint_dismissed';

	/** @var Mode */
	private $mode;

	/** @var \woocommerce_wpml */
	private $woocommerce_wpml;

	/** @var \wpdb */
	private $wpdb;

	public function __construct( Mode $mode, \woocommerce_wpml $woocommerce_wpml, \wpdb $wpdb ) {
		$this->mode             = $mode;
		$this->woocommerce_wpml = $woocommerce_wpml;
		$this->wpdb             = $wpdb;
	}

	public function add_hooks() {
		if ( $this->mode->isEditorScoped() ) {
			add_action( 'shutdown', [ $this, 'maybePersistSyncSummary' ], self::PRIORITY_AFTER_ALL_SAVE_PROCESSING );
			add_action( 'edit_form_top', [ $this, 'maybeRenderSyncSummary' ] );
		} else {
			add_action( 'shutdown', [ $this, 'maybePersistSwitchHint' ], self::PRIORITY_BEFORE_SAVE_SUMMARY );
			add_action( 'edit_form_top', [ $this, 'maybeRenderSwitchHint' ] );
			add_action( 'admin_init', [ $this, 'maybeHandleSwitchHintDismiss' ] );
		}
	}

	/**
	 * If we just handled an editor-driven save in editor_scoped mode, store a summary
	 * in a per-product transient so the next page load can render the notice.
	 */
	public function maybePersistSyncSummary() {
		if ( ! $this->isProductSaveRequest() ) {
			return;
		}

		$productId = $this->getRequestProductId();

		if ( ! $productId ) {
			return;
		}

		$edited  = EditorChangeTracker::editedVariationIdsFor( $productId );
		$deleted = EditorChangeTracker::deletedVariationIdsFor( $productId );

		// Merge with any prior transient. The variations-tab AJAX and the main editor save
		// are separate requests; the user's mental model is one editing session. So if the
		// previous save in the same session already recorded variation activity for this
		// product, carry it forward rather than overwriting.
		$existing = get_transient( self::SYNC_SUMMARY_TRANSIENT_PREFIX . $productId );

		if ( is_array( $existing ) ) {
			if ( ! empty( $existing['edited'] ) ) {
				$edited = array_values( array_unique( array_merge( $edited, (array) $existing['edited'] ) ) );
			}
			if ( ! empty( $existing['deleted'] ) ) {
				$deleted = array_values( array_unique( array_merge( $deleted, (array) $existing['deleted'] ) ) );
			}
		}

		// Total variations the product has, for context.
		$allIds = get_posts( [
			'post_type'   => 'product_variation',
			'post_parent' => $productId,
			'fields'      => 'ids',
			'post_status' => 'any',
			'numberposts' => -1,
		] );

		$total = count( $allIds );

		set_transient( self::SYNC_SUMMARY_TRANSIENT_PREFIX . $productId, [
			'time'                => time(),
			'edited'              => array_values( $edited ),
			'deleted'             => array_values( $deleted ),
			'total'               => $total,
			'description_changed' => $this->changedNonVariationFields( $productId ),
		], self::TRANSIENT_TTL );
	}

	/**
	 * @param int $productId
	 *
	 * @return string[] Keys from get_changes() excluding variation-attribute related.
	 */
	private function changedNonVariationFields( $productId ) {
		$changes = EditorChangeTracker::productChangesFor( $productId );
		// Always-noisy keys that we filter out from the summary copy.
		$ignore = [ 'shipping_class_id', 'stock_quantity' ];
		return array_values( array_diff( array_keys( $changes ), $ignore ) );
	}

	/**
	 * @param \WP_Post|null $post Provided by edit_form_top.
	 */
	public function maybeRenderSyncSummary( $post = null ) {
		$postId = $this->getEditedPostId( $post );

		if ( ! $postId ) {
			return;
		}

		// Only on product editor.
		if ( get_post_type( $postId ) !== 'product' ) {
			return;
		}
		$summary = get_transient( self::SYNC_SUMMARY_TRANSIENT_PREFIX . $postId );

		if ( ! is_array( $summary ) || empty( $summary['total'] ) ) {
			return;
		}

		// Only render if at least one variation was skipped this save.
		$editedCount  = count( $summary['edited'] ?? [] );
		$total        = (int) $summary['total'];
		$skippedCount = max( 0, $total - $editedCount - count( $summary['deleted'] ?? 0 ) );

		if ( $skippedCount <= 0 ) {
			delete_transient( self::SYNC_SUMMARY_TRANSIENT_PREFIX . $postId );
			return;
		}

		$nonce = ForceUpdateEndpoint::nonce();
		?>
		<style>
			.wcml-esn-notice { background:#f0f6fc; border-left:4px solid #72aee6; padding:0; margin:15px 0; position:relative; }
			.wcml-esn-notice details { padding:0; }
			.wcml-esn-notice summary { padding:12px 40px 12px 16px; cursor:pointer; list-style:none; display:flex; align-items:center; gap:12px; }
			.wcml-esn-notice summary::-webkit-details-marker { display:none; }
			.wcml-esn-notice .chev { transition:transform 0.15s; color:#646970; font-size:18px; }
			.wcml-esn-notice details[open] .chev { transform:rotate(90deg); }
			.wcml-esn-notice .body { padding:0 16px 12px 44px; border-top:1px solid #c5d9ed40; }
			.wcml-esn-notice .dismiss { position:absolute; top:10px; right:10px; background:none; border:none; cursor:pointer; color:#646970; }
		</style>
		<div class="wcml-esn-notice" data-product-id="<?php echo (int) $postId; ?>">
			<button type="button" class="dismiss" onclick="this.closest('.wcml-esn-notice').remove()">×</button>
			<details>
				<summary>
					<svg width="18" height="18" viewBox="0 0 20 20" fill="#2271b1"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
					<strong style="flex:1">
						<?php
						if ( 0 === $editedCount ) {
							esc_html_e( 'WPML synced translations for product-level changes only.', 'woocommerce-multilingual' );
						} else {
							echo esc_html( sprintf(
								/* translators: 1: number of synced variations, 2: total variations in product */
								__( 'WPML synced translations for %1$d of %2$d variations.', 'woocommerce-multilingual' ),
								$editedCount,
								$total
							) );
						}
						?>
					</strong>
					<span class="chev">›</span>
				</summary>
				<div class="body">
					<p>
						<?php
						echo esc_html( sprintf(
							/* translators: %d: number of variations that were not checked */
							_n(
								'%d other variation was not checked because its data didn\'t change here.',
								'%d other variations were not checked because their data didn\'t change here.',
								$skippedCount,
								'woocommerce-multilingual'
							),
							$skippedCount
						) );
						?>
					</p>
					<p>
						<?php
						printf(
							/* translators: %s: "Force update all" label */
							wp_kses_post( __( 'If you\'ve made changes <strong>outside this editor</strong> (for example, retranslating an attribute term), use <em>%s</em> below to make sure every translation is current.', 'woocommerce-multilingual' ) ),
							esc_html__( 'Force update all', 'woocommerce-multilingual' )
						);
						?>
					</p>
					<p>
						<button type="button" class="button" id="wcml-esn-force-update">
							<?php esc_html_e( 'Force update all variation translations', 'woocommerce-multilingual' ); ?>
						</button>
					</p>
					<p style="margin-top:8px;color:#3c434a;">
						<?php
						printf(
							/* translators: %s: link to the WCML Settings page */
							wp_kses_post( __( 'You can switch to <em>"Always check all variations"</em> on the %s.', 'woocommerce-multilingual' ) ),
							sprintf(
								'<a href="%s" target="_blank" rel="noopener">%s<span class="screen-reader-text"> %s</span>&nbsp;<svg width="11" height="11" viewBox="0 0 12 12" fill="currentColor" aria-hidden="true" style="vertical-align:-1px;"><path d="M3.5 1.5v1H8.79L1.65 9.64l.71.71L9.5 3.21V8.5h1V1.5h-7z"/></svg></a>',
								esc_url( admin_url( 'admin.php?page=wpml-wcml&tab=settings' ) ),
								esc_html__( 'WCML Settings page', 'woocommerce-multilingual' ),
								esc_html__( '(opens in a new tab)', 'woocommerce-multilingual' )
							)
						);
						?>
					</p>
					<p id="wcml-esn-force-update-result" style="display:none;color:#00a32a"></p>
				</div>
			</details>
		</div>
		<script>
			(function () {
				var btn = document.getElementById('wcml-esn-force-update');
				if (!btn) return;
				btn.addEventListener('click', function () {
					btn.textContent = '<?php echo esc_js( __( 'Syncing all variations…', 'woocommerce-multilingual' ) ); ?>';
					btn.disabled = true;
					var data = new FormData();
					data.append('action', '<?php echo esc_js( ForceUpdateEndpoint::ACTION ); ?>');
					data.append('product_id', '<?php echo (int) $postId; ?>');
					data.append('_wpnonce', '<?php echo esc_js( $nonce ); ?>');
					fetch(ajaxurl, { method: 'POST', body: data, credentials: 'same-origin' })
						.then(function (r) { return r.json(); })
						.then(function (j) {
							var out = document.getElementById('wcml-esn-force-update-result');
							if (j && j.success) {
								btn.textContent = '<?php echo esc_js( __( 'Done — all variations synced', 'woocommerce-multilingual' ) ); ?>';
								if (out) {
									out.style.display = 'block';
									out.textContent = '<?php echo esc_js( sprintf( __( 'Sync completed for product #%d.', 'woocommerce-multilingual' ), $postId ) ); ?>';
								}
							} else {
								btn.textContent = '<?php echo esc_js( __( 'Failed — see console', 'woocommerce-multilingual' ) ); ?>';
								console.error(j);
							}
						})
						.catch(function (e) { console.error(e); btn.textContent = '<?php echo esc_js( __( 'Error — ', 'woocommerce-multilingual' ) ); ?>' + e; })
						.finally(function () { btn.disabled = false; });
				});
			})();
		</script>
		<?php
		// Clear the transient now that we've rendered. Subsequent page reloads should not re-show
		// the same notice; only fresh save activity should produce a new one.
		delete_transient( self::SYNC_SUMMARY_TRANSIENT_PREFIX . $postId );
	}

	/**
	 * If we're in Complete sync mode and a save just iterated all variations but
	 * produced no variation changes (= wasted full iteration), set a transient so
	 * the next page load can render the "switch to faster mode" suggestion.
	 */
	public function maybePersistSwitchHint() {
		if ( $this->isSwitchHintDismissed() ) {
			return;
		}
		if ( ! $this->isProductSaveRequest() ) {
			return;
		}

		$productId = $this->getRequestProductId();

		if ( ! $productId || get_post_type( $productId ) !== 'product' ) {
			return;
		}

		// Skip if real variation activity happened — the iteration wasn't wasted.
		if ( EditorChangeTracker::hasAnyVariationActivity( $productId ) ) {
			return;
		}

		// Only suggest for products that actually have variations (a simple product is fine in Complete sync).
		$variationCount = (int) count( get_posts( [
			'post_type'   => 'product_variation',
			'post_parent' => $productId,
			'fields'      => 'ids',
			'post_status' => 'any',
			'numberposts' => -1,
		] ) );

		if ( $variationCount < 1 ) {
			return;
		}

		// Count translations of this product so the notice copy can say "× N languages".
		$trid = $this->wpdb->get_var( $this->wpdb->prepare(
			"SELECT trid FROM {$this->wpdb->prefix}icl_translations WHERE element_id = %d AND element_type = %s",
			$productId, 'post_product'
		) );

		$translationCount = $trid ? (int) $this->wpdb->get_var( $this->wpdb->prepare(
			"SELECT COUNT(*) FROM {$this->wpdb->prefix}icl_translations WHERE trid = %d AND element_id != %d AND element_type = %s",
			$trid, $productId, 'post_product'
		) ) : 0;

		// No translations => no waste worth flagging.
		if ( $translationCount < 1 ) {
			return;
		}

		// Threshold gate: only suggest the switch when WCML's waste is large enough to feel.
		// Math: ~6 ms / iteration measured; threshold 200 iterations ≈ 1.2 s WCML waste.
		$threshold = (int) apply_filters( 'wcml_editor_scoped_heavy_load_threshold', self::SWITCH_HINT_ITERATION_THRESHOLD );

		if ( ( $variationCount * $translationCount ) < $threshold ) {
			return;
		}

		set_transient( self::SWITCH_HINT_TRANSIENT_PREFIX . $productId, [
			'time'              => time(),
			'variation_count'   => $variationCount,
			'translation_count' => $translationCount,
		], self::TRANSIENT_TTL );
	}

	/**
	 * @param \WP_Post|null $post Provided by edit_form_top.
	 */
	public function maybeRenderSwitchHint( $post = null ) {
		if ( $this->isSwitchHintDismissed() ) {
			return;
		}

		$postId = $this->getEditedPostId( $post );

		if ( ! $postId || get_post_type( $postId ) !== 'product' ) {
			return;
		}

		$hint = get_transient( self::SWITCH_HINT_TRANSIENT_PREFIX . $postId );

		if ( ! is_array( $hint ) ) {
			return;
		}

		$variations   = (int) ( $hint['variation_count'] ?? 0 );
		$translations = (int) ( $hint['translation_count'] ?? 0 );
		$dismissUrl   = wp_nonce_url(
			add_query_arg( [
				'wcml_esn_dismiss' => 'heavy_load',
				'post'             => $postId,
				'action'           => 'edit',
			], admin_url( 'post.php' ) ),
			'wcml_esn_dismiss_heavy_load'
		);

		$settingsUrl = admin_url( 'admin.php?page=wpml-wcml&tab=settings' );
		?>
		<style>
			.wcml-esn-notice-a { background:#fcf9e8; border-left:4px solid #dba617; padding:12px 40px 12px 16px; margin:15px 0; position:relative; }
			.wcml-esn-notice-a .dismiss-x { position:absolute; top:8px; right:10px; background:none; border:none; cursor:pointer; color:#646970; font-size:18px; line-height:1; }
			.wcml-esn-notice-a p { margin:6px 0; }
			.wcml-esn-notice-a .lede { font-weight:600; }
			.wcml-esn-notice-a .meta { color:#3c434a; }
			.wcml-esn-notice-a .actions { color:#646970; font-size:13px; }
		</style>
		<div class="wcml-esn-notice-a" data-product-id="<?php echo (int) $postId; ?>">
			<button type="button" class="dismiss-x" onclick="this.closest('.wcml-esn-notice-a').remove()"
				aria-label="<?php esc_attr_e( 'Dismiss', 'woocommerce-multilingual' ); ?>">×</button>
			<p class="lede">
				<?php
				echo esc_html( sprintf(
					/* translators: 1: number of variations checked, 2: number of translation languages */
					_n(
						'WPML verified %1$d variation × %2$d language on this save. None of them had changes that needed syncing.',
						'WPML verified %1$d variations × %2$d languages on this save. None of them had changes that needed syncing.',
						$variations,
						'woocommerce-multilingual'
					),
					$variations,
					$translations
				) );
				?>
			</p>
			<p class="meta">
				<?php
				echo wp_kses_post( __( 'That\'s wasted work. Switching to <em>"Only sync variations edited in the editor"</em> would make saves like this much faster — WPML will only sync the variations you actually changed and tell you what was skipped.', 'woocommerce-multilingual' ) );
				?>
			</p>
			<p class="actions">
				<?php
				printf(
					/* translators: %s: link to the WCML Settings page */
					wp_kses_post( __( 'You can switch on the %s.', 'woocommerce-multilingual' ) ),
					sprintf(
						'<a href="%s" target="_blank" rel="noopener">%s<span class="screen-reader-text"> %s</span>&nbsp;<svg width="11" height="11" viewBox="0 0 12 12" fill="currentColor" aria-hidden="true" style="vertical-align:-1px;"><path d="M3.5 1.5v1H8.79L1.65 9.64l.71.71L9.5 3.21V8.5h1V1.5h-7z"/></svg></a>',
						esc_url( $settingsUrl ),
						esc_html__( 'WCML Settings page', 'woocommerce-multilingual' ),
						esc_html__( '(opens in a new tab)', 'woocommerce-multilingual' )
					)
				);
				?>
				<span style="margin:0 8px;color:#dcdcde;">|</span>
				<a href="<?php echo esc_url( $dismissUrl ); ?>" style="color:#646970;">
					<?php esc_html_e( 'Don\'t show this again on this site', 'woocommerce-multilingual' ); ?>
				</a>
			</p>
		</div>
		<?php
		// Consume the hint so the notice doesn't keep re-appearing on subsequent reloads.
		delete_transient( self::SWITCH_HINT_TRANSIENT_PREFIX . $postId );
	}

	/**
	 * Handle the "Don't show this again" link click.
	 */
	public function maybeHandleSwitchHintDismiss() {
		if ( ! isset( $_GET['wcml_esn_dismiss'] ) || 'heavy_load' !== $_GET['wcml_esn_dismiss'] ) {
			return;
		}

		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'wcml_esn_dismiss_heavy_load' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$settings = $this->woocommerce_wpml->get_settings();
		$settings[ self::SWITCH_HINT_DISMISS_KEY ] = '1';
		$this->woocommerce_wpml->update_settings( $settings );

		// Drop the dismiss query params from the URL.
		wp_safe_redirect( remove_query_arg( [ 'wcml_esn_dismiss', '_wpnonce' ] ) );
		exit;
	}

	private function getRequestProductId(): int {
		return isset( $_REQUEST['post_ID'] ) ? (int) $_REQUEST['post_ID']
			: ( isset( $_REQUEST['product_id'] ) ? (int) $_REQUEST['product_id'] : 0 );
	}

	/**
	 * @param \WP_Post|null $post
	 *
	 * @return int
	 */
	private function getEditedPostId( $post ): int {
		return $post
			? (int) $post->ID
			: ( isset( $_GET['post'] ) ? (int) $_GET['post'] : 0 );
	}

	private function isProductSaveRequest(): bool {
		$pagenow = isset( $GLOBALS['pagenow'] ) ? $GLOBALS['pagenow'] : '';
		$action  = isset( $_REQUEST['action'] ) ? (string) $_REQUEST['action'] : '';

		$isSavingPostFromEditor       = 'post.php' === $pagenow && 'editpost' === $action;
		$isSavingVariationsFromEditor = 'woocommerce_save_variations' === $action;

		return $isSavingPostFromEditor || $isSavingVariationsFromEditor;
	}

	private function isSwitchHintDismissed(): bool {
		$settings = $this->woocommerce_wpml->get_settings();
		return ! empty( $settings[ self::SWITCH_HINT_DISMISS_KEY ] );
	}
}