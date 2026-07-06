<?php // phpcs:ignore Standard.Category.SniffName.ErrorCode

/**
 * JSON REST API.
 */

namespace WPDataAccess\API {

	use WPDataAccess\WPDA;

	/**
	 * JSON REST API main class.
	 */
	class WPDA_API {

		const WPDA_NAMESPACE             = 'wpda';
		const WPDA_REST_API_TABLE_ACCESS = 'wpda_rest_api_table_access';

		public function hide() {

			add_filter(
				'rest_authentication_errors',
				function ( $access ) {
					$rest_route = isset( $GLOBALS['wp']->query_vars['rest_route'] )
						? untrailingslashit( $GLOBALS['wp']->query_vars['rest_route'] )
						: '';

					if (
                        '/' . self::WPDA_NAMESPACE === $rest_route &&
                        ! WPDA::current_user_is_admin()
                    ) {
						return new \WP_Error(
							'rest_cannot_access',
							__( 'Only authenticated admin users can access the REST API.', 'wp-data-access' ),
							array(
								'status' => rest_authorization_required_code(),
							)
						);
					}

					return $access;
				}
			);

		}

		/**
		 * Register routes.
		 *
		 * @return void
		 */
		public function init() {

			// Plugin
			$plugin = new WPDA_Plugin();
			$plugin->register_rest_routes();

			// Apps
			$apps = new WPDA_Apps();
			$apps->register_rest_routes();

			// Data Explorer
			$tree = new WPDA_Tree();
			$tree->register_rest_routes();

			// Data Tables and Data Forms
			$tables = new WPDA_Table();
			$tables->register_rest_routes();

			// Admin actions
			$actions = new WPDA_Actions();
			$actions->register_rest_routes();

			// Settings
			$settings = new WPDA_Settings();
			$settings->register_rest_routes();

            // Query Builder
            $qb = new WPDA_QB();
            $qb->register_rest_routes();

            // AI Assistant
            $ai = new WPDA_AI();
            $ai->register_rest_routes();

            // Scheduled Exports
            $export = new WPDA_Export();
            $export->register_rest_routes();

		}

	}

}
