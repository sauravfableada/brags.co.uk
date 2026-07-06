<?php
/**
 * Editor-scoped sync mode — backend action loader factory.
 *
 * @package WCML\EditorScopedSync
 */

namespace WCML\EditorScopedSync;

class HooksFactory implements \IWPML_Backend_Action_Loader {

	/**
	 * @return \IWPML_Action[]
	 */
	public function create() {
		/**
		 * @var \woocommerce_wpml $woocommerce_wpml
		 * @var \wpdb             $wpdb
		 */
		global $woocommerce_wpml, $wpdb;

		$mode = new Mode( $woocommerce_wpml );

		return [
			new EditorChangeTracker(),
			new SyncGate( $mode ),
			new ForceUpdateEndpoint(),
			new Settings( $mode ),
			new Notices( $mode, $woocommerce_wpml, $wpdb ),
		];
	}
}
