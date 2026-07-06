<?php

namespace WPDataAccess\Macro {

	class WPDA_Macro {

		protected $raw_code   = null;
		protected $raw_array  = array();
		protected $has_macros = false;

		public function __construct( $raw_code ) {
			$this->raw_code = $raw_code;
			if ( null !== $this->raw_code && '' !== str_replace( ' ', '', $this->raw_code ) ) {
				$this->raw_array = explode( "\n", $this->raw_code );//phpcs:ignore - 8.1 proof
				if ( is_array( $this->raw_array ) && count( $this->raw_array ) > 2 ) {//phpcs:ignore - 8.1 proof
					$this->has_macros = true;
				}
			}
		}

		public function exe_macro() {
			if ( $this->has_macros ) {
				$code = $this->macro_if( $this->raw_array );

				return implode( ' ', $code );
			} else {
				return $this->raw_code;
			}
		}

		protected function macro_if( $code_array ) {
			if ( ! is_array( $code_array ) ) {
				return $code_array;
			}

			$codeif   = array();
			$totalifs = 0;

			for ( $line = 0; $line < count( $code_array ); $line++ ) {//phpcs:ignore - 8.1 proof
				$code = $code_array[ $line ];
				if ( $this->is_macro( 'if', $code ) ) {
					$totalifs++;

					// Process macro if
					$line_start = $line;
					$line_else  = -1;
					$line_end   = $line; // prevent PHP errors
					$ifs_found  = 1;
					for ( $i = $line + 1; $i < count( $code_array ); $i++ ) {//phpcs:ignore - 8.1 proof
						if ( $this->is_macro( 'if', $code_array[ $i ] ) ) {
							$ifs_found++;
						}

						if ( $this->is_macro( 'else', $code_array[ $i ] ) ) {
							if ( 1 === $ifs_found ) {
								$line_else = $i;
							}
						}

						if ( $this->is_macro( 'endif', $code_array[ $i ] ) ) {
							if ( 1 === $ifs_found ) {
								$line_end = $i;
							} else {
								$ifs_found--;
							}
						}
					}

					if ( -1 === $line_else ) {
						// Get code between if and end if
						$code_slice = array_slice( $code_array, $line_start + 1, $line_end - $line_start - 1 );//phpcs:ignore - 8.1 proof
					} else {
						// Get code between if and else
						$code_slice = array_slice( $code_array, $line_start + 1, $line_else - $line_start - 1 );//phpcs:ignore - 8.1 proof
						// Get code between else and end if
						$code_else = array_slice( $code_array, $line_else + 1, $line_end - $line_else - 1 );//phpcs:ignore - 8.1 proof
					}

					// Process nested ifs
					$code_slice = $this->macro_if( $code_slice );

					$condition = null;
					foreach ( array( '==', '!=', '<', '>' ) as $c ) { // Handle each condition individually in macro_if_check
						if ( $this->macro_if_has( $code, $c, $condition ) ) {
							if ( $this->macro_if_check( $condition, $c ) ) {
								$codeif = array_merge( $codeif, $code_slice );//phpcs:ignore - 8.1 proof
							} else {
								if ( $line_else > -1 ) {
									$codeif = array_merge( $codeif, $code_else );//phpcs:ignore - 8.1 proof
								}
							}
							$line = $line_end;
							break;
						}
					}
				} else {
					$codeif = array_merge( $codeif, array( $code_array[ $line ] ) );//phpcs:ignore - 8.1 proof
				}
			}

			if ( $totalifs === 0 ) {
				return $code_array;
			} else {
				return $codeif;
			}
		}

		protected function macro_if_has( $code, $if, &$condition ) {
			$condition = explode( $if, html_entity_decode( substr( str_replace( ' ', '', (string )  $code ), 8 ) ) );//phpcs:ignore - 8.1 proof
			return is_array( $condition ) && count( $condition ) === 2;//phpcs:ignore - 8.1 proof
		}

		protected function macro_if_check( $condition, $if ) {
			switch ( $if ) {
				case '==';
					return is_array( $condition ) && count( $condition ) === 2 && $condition[0] == $condition[1];//phpcs:ignore - 8.1 proof
					break;
				case '!=';
					return is_array( $condition ) && count( $condition ) === 2 && $condition[0] != $condition[1];//phpcs:ignore - 8.1 proof
					break;
				case '<';
					return is_array( $condition ) && count( $condition ) === 2 && $condition[0] < $condition[1];//phpcs:ignore - 8.1 proof
					break;
				case '>';
					return is_array( $condition ) && count( $condition ) === 2 && $condition[0] > $condition[1];//phpcs:ignore - 8.1 proof
					break;
			}
		}

		protected function is_macro( $macro, $code ) {
			return "#macro{$macro}" === substr( str_replace( ' ', '', $code ), 0, strlen( "#macro{$macro}" ) );
		}

	}

}
