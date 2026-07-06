<?php

/**
 * Suppress "error - 0 - No summary was found for this file" on phpdoc generation
 *
 * @package WPDataAccess\Simple_Form
 */

namespace WPDataAccess\Simple_Form {

	/**
	 * Class WPDA_Simple_Form_Item_File
	 *
	 * Database column is handled files
	 *
	 * @author  Peter Schulz
	 * @since   5.1.3
	 */
	class WPDA_Simple_Form_Item_File extends WPDA_Simple_Form_Item {

		/**
		 * WPDA_Simple_Form_Item_File constructor.
		 *
		 * @param array $args
		 */
		public function __construct( $args = array() ) {
			parent::__construct( $args );
		}

		/**
		 * Overwrite method
		 *
		 * @param string $action
		 * @param string $update_keys_allowed
		 */
		public function show( $action, $update_keys_allowed ) {
			parent::show( $action, $update_keys_allowed );
		}

		/**
		 * Overwrite method
		 */
		protected function show_item() {
			if ( isset( $_FILES[ $this->item_name ] ) && '' !== $_FILES[ $this->item_name ]['name'] ) {
				// Process uploaded file.
				// TODO
				// var_dump($_FILES[ $this->item_name ]);
			}
			?>
			<label>
				Select file to upload
			</label>
			<input type="file" name="<?php echo esc_attr( $this->item_name ); ?>" id="<?php echo esc_attr( $this->item_name ); ?>">
			<?php
		}

	}

}
