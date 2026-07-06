<?php
/**
 * Editor-scoped sync mode — site setting accessor.
 *
 * @package WCML\EditorScopedSync
 */

namespace WCML\EditorScopedSync;

class Mode {

	const VALUE_COMPLETE      = 'complete';
	const VALUE_EDITOR_SCOPED = 'editor_scoped';

	const SETTING_KEY = 'editor_scoped_save_mode';

	/** @var \woocommerce_wpml */
	private $woocommerce_wpml;

	public function __construct( \woocommerce_wpml $woocommerce_wpml ) {
		$this->woocommerce_wpml = $woocommerce_wpml;
	}

	/**
	 * Returns the active save-mode for editor-driven product saves.
	 *
	 * @return string self::VALUE_COMPLETE or self::VALUE_EDITOR_SCOPED
	 */
	public function current() {
		$settings = $this->woocommerce_wpml->get_settings();
		$value    = isset( $settings[ self::SETTING_KEY ] ) ? $settings[ self::SETTING_KEY ] : self::VALUE_COMPLETE;
		return self::VALUE_EDITOR_SCOPED === $value ? self::VALUE_EDITOR_SCOPED : self::VALUE_COMPLETE;
	}

	/**
	 * @return bool true if Editor-scoped sync mode is active.
	 */
	public function isEditorScoped() {
		return self::VALUE_EDITOR_SCOPED === $this->current();
	}

	/**
	 * Persist the user's choice. No capability check here — the caller is responsible.
	 *
	 * @param string $value
	 */
	public function set( $value ) {
		$value    = self::VALUE_EDITOR_SCOPED === $value ? self::VALUE_EDITOR_SCOPED : self::VALUE_COMPLETE;
		$settings = $this->woocommerce_wpml->get_settings();
		$settings[ self::SETTING_KEY ] = $value;
		$this->woocommerce_wpml->update_settings( $settings );
	}
}
