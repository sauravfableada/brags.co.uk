<?php

namespace WeDevs\DokanPro\Modules\RequestForQuotation\Admin\Dashboard\Pages;

use WeDevs\Dokan\Admin\Dashboard\Pages\AbstractPage;

/**
 * Class RequestQuote.
 *
 * @package WeDevs\DokanPro\Modules\RequestForQuotation\Admin\Dashboard\Pages
 */
class RequestQuote extends AbstractPage {

	/**
	 * Get the ID of the page.
	 *
	 * @since 5.0.0
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'request-quote';
	}

	/**
	 * Get the menu.
	 *
	 * @since 5.0.0
	 *
	 * @param string $capability Menu capability.
	 * @param string $position   Menu position.
	 *
	 * @return array|int[]|string[]
	 */
	public function menu( string $capability, string $position ): array {
		return array(
			'page_title' => esc_html__( 'Request for Quote', 'dokan' ),
			'menu_title' => esc_html__( 'RFQ', 'dokan' ),
			'route'      => 'request-for-quote',
			'capability' => $capability,
			'position'   => 65,
		);
	}

	/**
	 * Get the settings.
	 *
	 * @since 5.0.0
	 *
	 * @return array|mixed[]
	 */
	public function settings(): array {
		return array();
	}

	/**
	 * Get the scripts.
	 *
	 * @since 5.0.0
	 *
	 * @return string[]
	 */
	public function scripts(): array {
		return array( 'dokan-rfq-admin-panel' );
	}

	/**
	 * Get the styles.
	 *
	 * @since 5.0.0
	 *
	 * @return array<string> An array of style handles.
	 */
	public function styles(): array {
		return array( 'dokan-rfq-admin-panel' );
	}

	/**
	 * Register the page scripts and styles.
	 *
	 * @since 5.0.0
	 *
	 * @return void
	 */
	public function register(): void {
		// Register React admin panel script.
		$admin_panel_asset = DOKAN_RAQ_PATH . '/assets/js/admin-panel/index.asset.php';

		if ( file_exists( $admin_panel_asset ) ) {
			$assets                   = include $admin_panel_asset;
			$component_handler        = 'dokan-react-frontend';
			$assets['dependencies'][] = $component_handler;

			wp_register_style(
				'dokan-rfq-admin-panel',
				DOKAN_RAQ_ASSETS . '/js/admin-panel/style-index.css',
				array( $component_handler ),
				$assets['version']
			);

			wp_register_script(
				'dokan-rfq-admin-panel',
				DOKAN_RAQ_ASSETS . '/js/admin-panel/index.js',
				$assets['dependencies'],
				$assets['version'],
				true
			);

			wp_set_script_translations(
				'dokan-rfq-admin-panel',
				'dokan'
			);
		}
	}
}
