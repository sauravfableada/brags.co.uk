<?php
/**
 * Editor-scoped sync mode — gate that narrows or skips the variation pipeline.
 *
 * `wcml_editor_scoped_variation_ids` filter, applied inside `Variations::run`.
 *    When Editor-scoped mode is active, returns the list of source-variation
 *    IDs that the editor actually saved during this request. `Variations::run`
 *    intersects its iteration with this list, so unchanged variations are not
 *    pushed to translations and the per-variation fan-out is skipped for them.
 *
 * @package WCML\EditorScopedSync
 */

namespace WCML\EditorScopedSync;

class SyncGate implements \IWPML_Backend_Action {

	/** @var Mode */
	private $mode;

	public function __construct( Mode $mode ) {
		$this->mode = $mode;
	}

	public function add_hooks() {
		add_filter( 'wcml_editor_scoped_variation_ids', [ $this, 'narrowVariationIds' ], 10, 2 );
	}

	/**
	 * Filter callback — return the set of variation IDs to iterate, or null to keep current behavior.
	 *
	 * @param int[]|null $current
	 * @param int        $productId
	 * @return int[]|null
	 */
	public function narrowVariationIds( $current, $productId ) {
		if ( $this->mode->isEditorScoped() ) {
			return EditorChangeTracker::editedVariationIdsFor( $productId );
		}
		return $current; // Complete sync: pass through.
	}
}
