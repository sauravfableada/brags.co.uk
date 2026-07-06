<?php

namespace WPDataAccess\Utilities;

use WPDataAccess\Data_Apps\WPDA_App_Container;
use WPDataAccess\Plugin_Table_Models\WPDA_App_Model;
use WPDataAccess\WPDA;

class WPDA_Add_App_To_Menu {

	private $wp_admin_toolbar = array();

	public function add_apps_to_menu() {

		$apps = WPDA_App_Model::add_to_dashboard_menu();
		foreach ( $apps as $app ) {
			$settings = json_decode( $app['app_settings'], true );
			if (
				! isset(
					$settings['rest_api']['authorization'],
					$settings['rest_api']['authorized_roles'],
					$settings['rest_api']['authorized_users'],
					$settings['settings']['app_menu_title']
				)
			) {
				continue;
			}
			if ( $settings['rest_api']['authorization'] === 'authorized' ) {
				$authorized_roles = $settings['rest_api']['authorized_roles'];
				$current_user_roles = WPDA::get_current_user_roles();
				if ( 0 === count( array_intersect( $authorized_roles, $current_user_roles ) ) ) {
					$authorized_users  = $settings['rest_api']['authorized_users'];
					$current_user_name = WPDA::get_current_user_login();
					if ( ! in_array( $current_user_name, $authorized_users ) ) {
						continue;
					}
				}
			}

			$title = $settings['settings']['app_menu_title'];
            $icon  = 'dashicons-database-view';
            switch ($app['app_type']) {
                case 1:
                case 3:
                case 4:
                    $icon = 'dashicons-edit';
                    break;
                case 2:
                    $icon = 'dashicons-location';
                    break;
                case 6:
                    $icon = 'dashicons-chart-bar';
                    break;
                case 7:
                    $icon = 'dashicons-dashboard';
                    break;
            }
            if ( is_admin() ) {
				// Add app to dashboard menu
				add_menu_page(
					$title,
					$title,
					WPDA::get_current_user_capability(),
					$title,
					null,
                    $icon
				);

				add_submenu_page(
					$title,
					$title,
					$title,
					WPDA::get_current_user_capability(),
					$title,
					function() use ( $app, $title, $settings ) {
						// Style not enqueued from container class.
						// Adding container style manually.
                        $fullscreen    = isset( $settings['settings']['app_start_full_screen'] ) ? $settings['settings']['app_start_full_screen'] : false;
                        $args          = array(
							'app_id'   => $app['app_id'],
							'feedback' => true,
                            'fullscreen' => $fullscreen,
						);
						$app_container = new WPDA_App_Container( $args );
						?>
						<div class="wrap wpda-dashboard-app">
							<h1 class="wp-heading-inline">
								<?php echo esc_html( $title ); ?>
							</h1>
							<?php $app_container->show(); ?>
						</div>
						<style>
                            .wrap.wpda-dashboard-app .pp-container-app {
                                margin: 20px 0 0 0;
                            }
						</style>
						<?php
					}
				);
			} else {
				// Add app to WordPress toolbar
				$this->wp_admin_toolbar[ $app['app_id'] ][] = array(
					'menu_id'    => $title,
					'menu_name'  => $title,
					'menu_title' => $title,
					'page_title' => $title,
				);
			}
		}

		if ( ! is_admin() ) {
			foreach ( $this->wp_admin_toolbar as $pid => $toolbar ) {
				foreach ( $toolbar as $key => $menu ) {
					if ( 0 === $key ) {
						$this->add_item_to_toolbar( $pid, $menu );
					}
					$this->add_item_to_toolbar( $menu['menu_id'], $menu, $pid );
				}
			}
		}

	}

	private function add_item_to_toolbar($pid, $menu, $parent = null ) {

		global $wp_admin_bar;
		$args = array(
			'id'    => $pid,
			'title' => null === $parent ? $menu['menu_name'] : $menu['page_title'],
			'href'  => admin_url('admin.php') . '?page=' . $menu['menu_id'],
			'zindex' => '9999',
			'meta' =>array(
				'class' => null === $parent ? 'wpda-wpdp-toolbar' : '',
				'title' => null === $parent ? $menu['menu_title'] : $menu['page_title'],
			)
		);
		if ( null !== $parent ) {
			$args['parent'] = $parent;
		}
		$wp_admin_bar->add_node( $args );

	}

}