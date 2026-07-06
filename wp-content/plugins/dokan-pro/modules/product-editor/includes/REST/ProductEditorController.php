<?php

namespace WeDevs\DokanPro\Modules\ProductEditor\REST;

use WeDevs\Dokan\Abstracts\DokanRESTController;
use WeDevs\DokanPro\Modules\ProductEditor\Admin\FormSettings;
use WeDevs\DokanPro\Product\FormSchema;
use WP_Error;
use WP_REST_Request;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

/**
 * Product editor REST controller.
 *
 * @since 5.0.0
 */
class ProductEditorController extends DokanRESTController {

	/**
	 * Endpoint namespace.
	 *
	 * @since 5.0.0
	 *
	 * @var string
	 */
	protected $namespace = 'dokan/v1';

	/**
	 * Route base.
	 *
	 * @since 5.0.0
	 *
	 * @var string
	 */
	protected $rest_base = 'product-editor';

	/**
	 * Register routes.
	 *
	 * @since 5.0.0
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/settings',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_form_settings' ],
					'permission_callback' => [ $this, 'save_form_settings_permissions_check' ],
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'save_form_settings' ],
					'permission_callback' => [ $this, 'save_form_settings_permissions_check' ],
					'args'                => [
						'schema' => [
							'description'       => __( 'Product editor form schema.', 'dokan' ),
							'type'              => 'array',
							'required'          => true,
							'items'             => [
								'type' => 'object',
							],
							'sanitize_callback' => [ $this, 'sanitize_schema_param' ],
							'validate_callback' => [ $this, 'validate_schema_param' ],
						],
					],
				],
				'schema' => [ $this, 'get_public_item_schema' ],
			]
		);
	}

	/**
	 * Get form settings (schema and product types).
	 *
	 * @since 5.0.0
	 *
	 * @return \WP_REST_Response
	 */
	public function get_form_settings() {
		return rest_ensure_response( FormSettings::get_settings_data() );
	}

	/**
	 * Check permissions for saving form settings.
	 *
	 * @since 5.0.0
	 *
	 * @return bool
	 */
	public function save_form_settings_permissions_check(): bool {
		return current_user_can( 'manage_woocommerce' );
	}

	/**
	 * Save form schema settings.
	 *
	 * @since 5.0.0
	 *
	 * @param WP_REST_Request $request REST request.
	 *
	 * @return \WP_REST_Response|WP_Error
	 */
	public function save_form_settings( WP_REST_Request $request ) {
		$schema = $request->get_param( 'schema' );

		if ( ! is_array( $schema ) ) {
			return new WP_Error( 'invalid_schema_data', __( 'Invalid schema data.', 'dokan' ), [ 'status' => 400 ] );
		}

		// Diff baseline = the current effective (merged) schema, i.e. the live PHP
		// defaults with the existing saved overrides already applied. Diffing the
		// incoming items against this captures only the changes made THIS round.
		// Each baseline item is run through the same sanitizer as the incoming
		// items so formatting differences (e.g. HTML stripped from labels) don't
		// register as changes.
		$merged = [];
		foreach ( dokan()->product_editor->get_schema() as $merged_item ) {
			$clean_merged = $this->sanitize_schema_item( $merged_item );
			if ( ! empty( $clean_merged['id'] ) ) {
				$merged[ $clean_merged['id'] ] = $clean_merged;
			}
		}

		// Existing saved overrides, indexed by id. This round's changes are merged
		// on top of these so overrides the admin didn't re-touch are preserved.
		$saved = [];
		foreach ( (array) FormSettings::get_data() as $saved_item ) {
			if ( ! empty( $saved_item['id'] ) ) {
				$saved[ $saved_item['id'] ] = $saved_item;
			}
		}

		$sanitized = [];
		foreach ( $schema as $item ) {
			$clean = $this->sanitize_schema_item( $item );
			if ( empty( $clean['id'] ) ) {
				continue;
			}

			// Custom items have no PHP default; persist them in full.
			if ( ! empty( $clean['is_custom'] ) ) {
				$sanitized[] = $clean;
				continue;
			}

			$id = $clean['id'];

			// Keys that differ from the current effective schema this round.
			$changed = $this->reduce_to_changed( $clean, $merged[ $id ] ?? null );

			// Merge this round's changes over any previously-saved override so a
			// save that doesn't touch an override doesn't drop it.
			$override = array_merge( $saved[ $id ] ?? [], $changed );

			// Skip items with no override (only the id remains).
			if ( count( $override ) <= 1 ) {
				continue;
			}

			$sanitized[] = $override;
		}

		// Items present in $saved but absent from the incoming schema are dropped
		// here (deletions), since $sanitized is rebuilt from the incoming items.
		update_option( FormSchema::SETTINGS_KEY, $sanitized, false );

		return rest_ensure_response(
			[
				'success' => true,
				'message' => __( 'Settings saved successfully.', 'dokan' ),
			]
		);
	}

	/**
	 * Validate schema argument.
	 *
	 * @since 5.0.0
	 *
	 * @param mixed           $value   Schema value.
	 * @param WP_REST_Request $request Request object.
	 * @param string          $param   Parameter name.
	 *
	 * @return bool
	 */
	public function validate_schema_param( $value, WP_REST_Request $request, string $param ): bool {
		unset( $request, $param );

		return is_array( $value );
	}

	/**
	 * Sanitize schema argument.
	 *
	 * @since 5.0.0
	 *
	 * @param mixed           $value   Schema value.
	 * @param WP_REST_Request $request Request object.
	 * @param string          $param   Parameter name.
	 *
	 * @return array
	 */
	public function sanitize_schema_param( $value, WP_REST_Request $request, string $param ): array {
		unset( $request, $param );

		if ( ! is_array( $value ) ) {
			return [];
		}

		return array_values( $value );
	}

	/**
	 * Sanitize a single schema item (section or field).
	 *
	 * @since 5.0.0
	 *
	 * @param mixed $item Raw schema item.
	 *
	 * @return array
	 */
	private function sanitize_schema_item( $item ): array {
		if ( ! is_array( $item ) ) {
			return [];
		}

		$sanitized = [
			'id'   => sanitize_key( $item['id'] ?? '' ),
			'type' => in_array( $item['type'] ?? '', [ 'section', 'field' ], true ) ? $item['type'] : 'field',
		];

		// Only persist properties that actually carry a value, so unchanged
		// fields don't override the default schema on read.
		// section_id is only needed for custom items; default items inherit it
		// from the live schema.
		if ( ! empty( $item['is_custom'] ) && isset( $item['section_id'] ) ) {
			$sanitized['section_id'] = sanitize_key( $item['section_id'] );
		}

		if ( isset( $item['label'] ) && '' !== $item['label'] ) {
			$sanitized['label'] = sanitize_text_field( $item['label'] );
		}

		if ( isset( $item['priority'] ) ) {
			$sanitized['priority'] = absint( $item['priority'] );
		}

		if ( isset( $item['visibility'] ) ) {
			$sanitized['visibility'] = (bool) $item['visibility'];
		}

		if ( isset( $item['description'] ) ) {
			$sanitized['description'] = sanitize_text_field( $item['description'] );
		}

		if ( isset( $item['is_custom'] ) ) {
			$sanitized['is_custom'] = (bool) $item['is_custom'];
		}

		if ( isset( $item['is_mandatory'] ) ) {
			$sanitized['is_mandatory'] = (bool) $item['is_mandatory'];
		}

		if ( 'field' === $sanitized['type'] ) {
			if ( isset( $item['required'] ) ) {
				$sanitized['required'] = (bool) $item['required'];
			}

			if ( isset( $item['labels'] ) && is_array( $item['labels'] ) ) {
				$sanitized['labels'] = array_map( 'sanitize_text_field', $item['labels'] );
			}

			if ( isset( $item['visibilities'] ) && is_array( $item['visibilities'] ) ) {
				$sanitized['visibilities'] = array_map(
					static function ( $visibility ) {
						return filter_var( $visibility, FILTER_VALIDATE_BOOLEAN );
					},
					$item['visibilities']
				);
			}

			if ( isset( $item['placeholder'] ) ) {
				$sanitized['placeholder'] = sanitize_text_field( $item['placeholder'] );
			}

			if ( isset( $item['variant'] ) ) {
				$sanitized['variant'] = sanitize_key( $item['variant'] );
			}

			if ( ! empty( $item['is_custom'] ) && isset( $item['options'] ) && is_array( $item['options'] ) ) {
				$sanitized['options'] = array_map(
					static function ( $option ) {
						return [
							'label' => sanitize_text_field( $option['label'] ?? '' ),
							'value' => sanitize_text_field( $option['value'] ?? '' ),
						];
					},
					$item['options']
				);
			}
		}

		return $sanitized;
	}

	/**
	 * Reduce a sanitized item to only the mergeable keys that differ from a
	 * baseline schema item (the current effective/merged schema item).
	 *
	 * Custom (or unknown) items have no baseline to diff against and are kept as-is.
	 * For default items only the id and the changed mergeable keys are returned.
	 *
	 * @since 5.0.5
	 *
	 * @param array      $item        Sanitized schema item.
	 * @param array|null $default_val Matching baseline schema item, if any.
	 *
	 * @return array
	 */
	private function reduce_to_changed( array $item, $default_val ): array {
		if ( ! is_array( $default_val ) || ! empty( $item['is_custom'] ) ) {
			return $item;
		}

		$reduced = [ 'id' => $item['id'] ];

		foreach ( FormSettings::get_mergeable_keys() as $key ) {
			if ( ! array_key_exists( $key, $item ) ) {
				continue;
			}

			$default_value = array_key_exists( $key, $default_val ) ? $default_val[ $key ] : $this->implicit_default( $key );

			if ( $this->value_differs( $default_value, $item[ $key ] ) ) {
				$reduced[ $key ] = $item[ $key ];
			}
		}

		return $reduced;
	}

	/**
	 * Implicit default for a mergeable key when the default schema omits it.
	 *
	 * Mirrors the values the editor applies client-side so auto-filled props
	 * (e.g. a section's visibility) aren't mistaken for real changes.
	 *
	 * @since 5.0.5
	 *
	 * @param string $key Mergeable key.
	 *
	 * @return mixed
	 */
	private function implicit_default( string $key ) {
		switch ( $key ) {
			case 'visibility':
				return true;

			case 'required':
				return false;

			default:
				return null;
		}
	}

	/**
	 * Determine whether a value differs from its default.
	 *
	 * An absent default is treated as the type-appropriate empty value, so a
	 * falsy value (false / '' / 0) does not count as a change.
	 *
	 * @since 5.0.5
	 *
	 * @param mixed $default_value Default value (null when absent).
	 * @param mixed $value         Submitted value.
	 *
	 * @return bool
	 */
	private function value_differs( $default_value, $value ): bool {
		if ( is_array( $value ) || is_array( $default_value ) ) {
			return (array) $default_value !== (array) $value;
		}

		if ( null === $default_value ) {
			return ! empty( $value );
		}

		return $default_value !== $value;
	}

	/**
	 * Get endpoint response schema.
	 *
	 * @since 5.0.0
	 *
	 * @return array
	 */
	public function get_item_schema(): array {
		if ( $this->schema ) {
			return $this->add_additional_fields_schema( $this->schema );
		}

		$option_schema = [
			'type'       => 'object',
			'properties' => [
				'label' => [
					'description' => __( 'Option display label.', 'dokan' ),
					'type'        => 'string',
				],
				'value' => [
					'description' => __( 'Option value.', 'dokan' ),
					'type'        => 'string',
				],
			],
		];

		$schema_item = [
			'type'       => 'object',
			'properties' => [
				'id'           => [
					'description' => __( 'Unique identifier for the schema item.', 'dokan' ),
					'type'        => 'string',
				],
				'section_id'   => [
					'description' => __( 'Parent section ID. Null for sections, required for fields.', 'dokan' ),
					'type'        => [ 'string', 'null' ],
				],
				'type'         => [
					'description' => __( 'Item type.', 'dokan' ),
					'type'        => 'string',
					'enum'        => [ 'section', 'field' ],
				],
				'label'        => [
					'description' => __( 'Display label.', 'dokan' ),
					'type'        => 'string',
				],
				'labels'       => [
					'description' => __( 'Per-product-type labels keyed by product type slug.', 'dokan' ),
					'type'        => 'object',
				],
				'description'  => [
					'description' => __( 'Item description or help text.', 'dokan' ),
					'type'        => 'string',
				],
				'variant'      => [
					'description' => __( 'Field variant/input type.', 'dokan' ),
					'type'        => 'string',
					'enum'        => [
						'text',
						'select',
						'multiselect',
						'async_select',
						'checkbox',
						'textarea',
						'editor',
						'radio',
						'number',
						'file',
						'datetime',
						'image',
						'gallery',
						'attribute',
					],
				],
				'placeholder'  => [
					'description' => __( 'Placeholder text for text-based fields.', 'dokan' ),
					'type'        => 'string',
				],
				'priority'     => [
					'description' => __( 'Sort order priority.', 'dokan' ),
					'type'        => 'integer',
				],
				'visibility'   => [
					'description' => __( 'Default visibility state.', 'dokan' ),
					'type'        => 'boolean',
				],
				'visibilities' => [
					'description' => __( 'Per-product-type visibility overrides keyed by product type slug.', 'dokan' ),
					'type'        => 'object',
				],
				'required'     => [
					'description' => __( 'Whether the field is required.', 'dokan' ),
					'type'        => 'boolean',
				],
				'is_custom'    => [
					'description' => __( 'Whether this is a custom (user-created) item.', 'dokan' ),
					'type'        => 'boolean',
				],
				'is_mandatory' => [
					'description' => __( 'Whether the item is mandatory and cannot be hidden.', 'dokan' ),
					'type'        => 'boolean',
				],
				'options'      => [
					'description' => __( 'Selectable options for select/radio fields.', 'dokan' ),
					'type'        => 'array',
					'items'       => $option_schema,
				],
				'dependencies' => [
					'description' => __( 'Conditional dependency rules for field display.', 'dokan' ),
					'type'        => 'array',
					'items'       => [
						'type'       => 'object',
						'properties' => [
							'comparison' => [
								'description' => __( 'Comparison operator.', 'dokan' ),
								'type'        => 'string',
							],
							'key'        => [
								'description' => __( 'Field ID to compare against.', 'dokan' ),
								'type'        => 'string',
							],
							'value'      => [
								'description' => __( 'Value to compare with.', 'dokan' ),
								'type'        => [ 'string', 'boolean', 'number' ],
							],
						],
					],
				],
			],
		];

		$product_type_schema = [
			'type'       => 'object',
			'properties' => [
				'label' => [
					'description' => __( 'Product type display label.', 'dokan' ),
					'type'        => 'string',
				],
				'value' => [
					'description' => __( 'Product type slug.', 'dokan' ),
					'type'        => 'string',
				],
			],
		];

		$this->schema = [
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'product_editor_form_settings',
			'type'       => 'object',
			'properties' => [
				'schema'  => [
					'description' => __( 'Product editor form schema items (sections and fields).', 'dokan' ),
					'type'        => 'array',
					'items'       => $schema_item,
					'readonly'    => true,
				],
				'types'   => [
					'description' => __( 'Available product types.', 'dokan' ),
					'type'        => 'array',
					'items'       => $product_type_schema,
					'readonly'    => true,
				],
				'success' => [
					'description' => __( 'Whether the request was successful (POST response only).', 'dokan' ),
					'type'        => 'boolean',
					'readonly'    => true,
				],
				'message' => [
					'description' => __( 'Response message (POST response only).', 'dokan' ),
					'type'        => 'string',
					'readonly'    => true,
				],
			],
		];

		return $this->add_additional_fields_schema( $this->schema );
	}
}
