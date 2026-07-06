<?php

/**
 * Suppress "error - 0 - No summary was found for this file" on phpdoc generation
 *
 * @package WPDataAccess\Settings
 */
namespace WPDataAccess\Settings;

/**
 * Class WPDA_Settings
 *
 * All tabs have the following similar structure:
 * + If form was posted save options (show success or error message)
 * + Read options
 * + Show form with options for selected tab
 *
 * Tabs Back-end Settings, Front-end Settings, Data Backup Settings and Uninstall Settings have reset buttons. When
 * the reset button on a specific tab is clicked, the default values for the settings on that tab are taken from
 * WPDA and stored in $pwdb->options.
 *
 * When the users clicks on tab Manage Repository, the repository is validated and the status of the repository
 * is shown. If the repository has errors, a button is offered to recreate the repository.
 *
 * @author  Peter Schulz
 * @since   1.0.0
 */
abstract class WPDA_Settings {
    /**
     * Menu slug of the current page
     *
     * @var string
     */
    protected $page;

    /**
     * Available tabs on the page
     *
     * @var array
     */
    protected $tabs;

    /**
     * Current tab name
     *
     * @var string
     */
    protected $current_tab;

    /**
     * WPDA_Settings constructor
     *
     * Member $this->tabs is filled in the constructor to support i18n.
     *
     * If a request was sent for recreation of the repository, this is done in the constructor. This action must
     * be performed before checking the user menu model, which is part of the constructor as well, necessary to
     * inform the user if any errors were reported.
     *
     * @param $current_tab Current tab label
     *
     * @since   1.0.0
     */
    public function __construct( $current_tab ) {
        // Get menu slug of current page.
        if ( isset( $_REQUEST['page'] ) ) {
            $this->page = sanitize_text_field( wp_unslash( $_REQUEST['page'] ) );
            // input var okay.
        } else {
            // In order to show a list table we need a page.
            wp_die( __( 'ERROR: Wrong arguments [missing page argument]', 'wp-data-access' ) );
        }
        $this->current_tab = $current_tab;
        // Tabs array is filled in constructor to add i18n.
        $this->tabs = array(
            'plugin'     => 'Plugin',
            'apps'       => 'Apps',
            'backend'    => 'Back-end',
            'frontend'   => 'Front-end',
            'uninstall'  => 'Uninstall',
            'repository' => 'Repository',
            'mail'       => 'Mail',
            'drives'     => 'Drives',
            'legacy'     => 'Legacy Tools',
            'system'     => 'System Info',
        );
    }

    protected abstract function add_content();

    /**
     * Show setting page
     *
     * Consists of tabs {@see WPDA_Settings::add_tabs()} and the content of the selected tab
     * {@see \WPDataAccess\API\WPDA_Settings::add_content()}.
     *
     * @since   1.0.0
     *
     * @see WPDA_Settings::add_tabs()
     * @see \WPDataAccess\API\WPDA_Settings::add_content()
     */
    public function show() {
        ?>
			<div class="wrap">
				<h1>
					<?php 
        echo __( 'WP Data Access Settings', 'wp-data-access' );
        ?>
				</h1>
				<?php 
        $this->add_tabs();
        $this->add_content();
        ?>
			</div>
			<script>
				jQuery(function() {
					jQuery( '.wpda_tooltip' ).tooltip();
				});
			</script>
			<?php 
    }

    /**
     * Add tabs to page
     *
     * @since   1.0.0
     */
    protected function add_tabs() {
        ?>
			<h2 class="nav-tab-wrapper">
				<?php 
        foreach ( $this->tabs as $tab => $name ) {
            $class = ( $tab === $this->current_tab ? ' nav-tab-active' : '' );
            echo '<a class="nav-tab' . esc_attr( $class ) . '" href="?page=' . esc_attr( $this->page ) . '&tab=' . esc_attr( $tab ) . '">' . esc_attr( $name ) . '</a>';
        }
        ?>
			</h2>
			<?php 
    }

}
