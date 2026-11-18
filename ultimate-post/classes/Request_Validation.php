<?php
/**
 * Shared REST input contracts, used by both route registration and callbacks.
 *
 * @package ULTP
 */

namespace ULTP;

defined( 'ABSPATH' ) || exit;

/** Validate shapes before sanitizing or performing side effects. */
final class Request_Validation {
	/**
	 * Writable Free settings; existing extension settings retain compatibility.
	 *
	 * @return array Setting schemas.
	 */
	public static function settings_schema() {
		$switch     = array(
			'type' => array( 'string', 'boolean' ),
			'enum' => array( '', 'yes', 'no', 'true', 'false', true, false ),
		);
		$properties = array(
			'preloader_style' => array(
				'type' => 'string',
				'enum' => array( 'style1', 'style2' ),
			),
			'preloader_color' => array( 'type' => 'string' ),
			'container_width' => array(
				'type'    => array( 'string', 'number' ),
				'pattern' => '^[0-9]*(?:\\.[0-9]+)?$',
				'minimum' => 0,
			),
		);
		$keys       = array( 'hide_import_btn', 'disable_image_size', 'disable_view_cookies', 'enable_google_fonts', 'disable_google_font', 'ultp_templates', 'ultp_elementor', 'ultp_table_of_content', 'ultp_builder', 'ultp_dynamic_content', 'ultp_custom_font', 'heading', 'image', 'taxonomy', 'wrapper', 'news_ticker', 'accordion', 'star_rating', 'youtube_gallery' );
		foreach ( array(
			'post_grid'   => 7,
			'post_list'   => 4,
			'post_module' => 2,
			'post_slider' => 2,
		) as $prefix => $count ) {
			for ( $index = 1; $index <= $count; ++$index ) {
				$keys[] = $prefix . '_' . $index;
			}
		}
		foreach ( array( 'advance_post_meta', 'archive_title', 'author_box', 'post_next_previous', 'post_author_meta', 'post_breadcrumb', 'post_category', 'post_comment_count', 'post_comments', 'post_content', 'post_date_meta', 'post_excerpt', 'post_featured_image', 'post_reading_time', 'post_social_share', 'post_tag', 'post_title', 'post_view_count' ) as $key ) {
			$keys[] = 'builder_' . $key;
		}
		foreach ( $keys as $key ) {
			$properties[ $key ] = $switch;
		}
		// Retain schemas for existing extension values without requiring a Pro update.
		foreach ( (array) ultimate_post()->get_setting() as $key => $value ) {
			if ( ! isset( $properties[ $key ] ) && ! in_array( $key, array( 'save_version', 'activated_date' ), true ) && ( is_scalar( $value ) || is_array( $value ) ) ) {
				$properties[ $key ] = array( 'type' => is_array( $value ) ? array( 'array', 'object' ) : array( 'string', 'number', 'boolean' ) );
			}
		}
		/**
		 * Register schemas for extension-owned writable settings, without adding paid definitions in Free.
		 *
		 * @param array $properties Setting schemas keyed by option subkey.
		 */
		return apply_filters( 'ultp_rest_settings_schema', $properties );
	}

	/**
	 * Field contracts for the existing callback names.
	 *
	 * @param string $action Callback name.
	 * @return array Field schemas.
	 */
	public static function args( $action ) {
		$fields            = self::fields( $action );
		$fields['wpnonce'] = array( 'type' => 'string' );
		foreach ( $fields as &$field ) {
			$field['validate_callback'] = 'rest_validate_request_arg';
		}
		return $fields;
	}

	/**
	 * Callback-specific field schemas; permission checks remain in their existing routes.
	 *
	 * @param string $action Callback name.
	 * @return array Field schemas.
	 */
	private static function fields( $action ) {
		$string  = array( 'type' => 'string' );
		$id      = array(
			'type'    => 'integer',
			'minimum' => 0,
		);
		$flag    = array(
			'type' => array( 'string', 'boolean' ),
			'enum' => array( '', 'yes', 'no', 'true', 'false', true, false ),
		);
		$strings = array(
			'type'  => array( 'string', 'array' ),
			'items' => $string,
		);
		// Whole-stylesheet fields older editors still send. They are type-checked here and ignored by the callbacks.
		$data    = array(
			'type'       => array( 'object', 'string' ),
			'maxLength'  => 0,
			'properties' => array(
				'globalCSS'     => $string,
				'rootCSS'       => $string,
				'presetTypoCSS' => $string,
			),
		);
		$query   = array(
			'queryType'     => $strings,
			'queryNumber'   => array(
				'type'    => 'integer',
				'minimum' => -1,
			),
			'paged'         => array(
				'type'    => 'integer',
				'minimum' => 0,
			),
			'queryOffset'   => $id,
			'queryTaxValue' => $string,
			'queryOrder'    => array(
				'type' => 'string',
				'enum' => array( 'asc', 'desc', 'ASC', 'DESC' ),
			),
		);
		switch ( $action ) {
			case 'handle_dynamic_data':
				return array(
					'post_id'   => $id,
					'key'       => $string,
					'data_type' => array(
						'type' => 'string',
						'enum' => array( 'link', 'post_types', 'post_info', 'author_info', 'site_info', 'cf', 'comment_count' ),
					),
				);
			case 'handle_custom_fields':
				return array(
					'post_id'        => $id,
					'post_type'      => $string,
					'acf_field_type' => $string,
				);
			case 'get_dashboard_callback':
				return array(
					'type'   => $string,
					'pType'  => $string,
					'pages'  => $id,
					'search' => $string,
					'id'     => $id,
				);
			case 'wizard_site_status_callback':
				return array( 'siteType' => $string );
			case 'send_initial_plugin_data_callback':
				return array(
					'site'     => $string,
					'siteType' => $string,
				);
			case 'initial_setup_complete_callback':
				return array();
			case 'init_site_dark_logo_callback':
				return array(
					'logo' => array(
						'type'       => 'object',
						'properties' => array(
							'url' => $string,
							'id'  => $id,
						),
					),
				);
			case 'get_custom_image_size':
				return array( 'id' => $id );
			case 'save_plugin_settings':
				return array(
					'settings' => array(
						'type'                 => 'object',
						'required'             => true,
						'properties'           => self::settings_schema(),
						'additionalProperties' => true,
					),
				);
			case 'addon_block_action':
				return array(
					'key'   => array(
						'type'      => 'string',
						'required'  => true,
						'minLength' => 1,
						'pattern'   => '^[A-Za-z0-9_.:-]+$',
					),
					'value' => array_merge( $flag, array( 'required' => true ) ),
				);
			case 'global_settings_action':
				return array(
					'type' => array(
						'type'     => 'string',
						'required' => true,
						'enum'     => array( 'get', 'set' ),
					),
					'data' => $data,
				);
			case 'postx_presets_callback':
				return array(
					'type' => array(
						'type'     => 'string',
						'required' => true,
						'enum'     => array( 'get', 'set' ),
					),
					'key'  => array(
						'type'     => 'string',
						'required' => true,
						'enum'     => array( 'ultpPresetColors', 'ultpPresetGradients', 'ultpPresetTypos' ),
					),
					'data' => $data,
				);
			case 'condition_save_action':
				$conditions = array(
					'type'                 => 'object',
					'additionalProperties' => array(
						'type'  => 'array',
						'items' => array(
							'type'    => 'string',
							'pattern' => '^(include|exclude)/',
						),
					),
				);
				return array(
					'settings' => array(
						'type'                 => 'object',
						'required'             => true,
						'properties'           => array_fill_keys( array( 'header', 'footer', 'archive', 'singular', '404', 'front_page' ), $conditions ),
						'additionalProperties' => false,
					),
				);
			case 'condition_settings_action':
				return array(
					'type'         => $string,
					'term'         => array( 'type' => array( 'string', 'integer' ) ),
					'title_return' => $flag,
				);
			case 'data_builder_action':
				// The dashboard sends an empty ID when loading the builder list and defaults.
				return array(
					'pid' => array(
						'type'    => array( 'integer', 'string' ),
						'minimum' => 0,
						'pattern' => '^[0-9]*$',
					),
				);
			case 'get_single_premade_callback':
				return array(
					'ID'          => $id,
					'apiEndPoint' => $string,
					'type'        => $string,
				);
			case 'starter_dummy_post_callback':
			case 'starter_import_content_callback':
			case 'single_page_import':
				return array(
					'ID'             => $id,
					'api_endpoint'   => $string,
					'importDummy'    => $flag,
					'installPlugin'  => $flag,
					'validate_only'  => $flag,
					'deletePrevious' => $flag,
					'previous_items' => $string,
					'excludepages'   => $strings,
				);
			case 'deletepost_getnewsletters':
				return array(
					'deletePrevious' => array(
						'type' => array( 'string', 'boolean' ),
						'enum' => array( '', 'yes', 'no', 'true', 'false', true, false, 'preview', 'commit' ),
					),
					'previous_items' => $string,
					'get_newsletter' => $flag,
				);
			case 'save_block_content_css':
				return array(
					'post_id'   => array(
						'type'    => array( 'string', 'integer' ),
						'pattern' => '^(?:[0-9]+|ultp-widget)$',
					),
					'block_css' => $string,
					'has_block' => $flag,
					'preview'   => $flag,
				);
			case 'get_other_post_content_callback':
				return array( 'postId' => $id );
			case 'search_settings_action':
				return array(
					'type'      => $string,
					'condition' => $string,
					'term'      => array( 'type' => array( 'string', 'integer' ) ),
					'postType'  => $strings,
				);
			case 'ultp_route_post_data':
				foreach ( array( 'blockId', 'builder', 'filterType', 'metaKey', 'paginationType', 'queryOrderBy', 'queryQuick', 'querySearch', 'queryTax', 'advRelation' ) as $key ) {
					$query[ $key ] = $string;
				}
				foreach ( array( 'queryExclude', 'queryInclude', 'filterValue' ) as $key ) {
					$query[ $key ] = $string;
				}
				$query['queryAuthor'] = $string;
				foreach ( array( 'advFilterEnable', 'ajaxCall', 'checkFilter', 'filterShow', 'notFirstLoad', 'querySticky' ) as $key ) {
					$query[ $key ] = $flag;
				}
				$query['defQueryTax']  = array(
					'type'                 => 'object',
					'additionalProperties' => $string,
				);
				$query['queryNumber2'] = $id;
				return array_merge( $query, array( 'maxTaxonomy' => $id ) );
			case 'ultp_route_taxonomy_info_data':
				return array(
					'taxValue'       => $strings,
					'queryNumber'    => $id,
					'taxType'        => $string,
					'taxSlug'        => $string,
					'archiveBuilder' => $string,
				);
			case 'ultp_search_result':
				return array(
					'searchText'  => $string,
					'paged'       => $id,
					'postPerPage' => $id,
				);
			case 'template_page_action':
				return array(
					'id'      => $id,
					'type'    => array(
						'type' => 'string',
						'enum' => array( 'delete', 'duplicate', 'status' ),
					),
					'section' => $string,
					'status'  => array(
						'type' => 'string',
						'enum' => array( 'publish', 'draft' ),
					),
				);
			case 'premade_wishlist_save':
				return array(
					'id'   => $id,
					'type' => $string,
				);
			default:
				return array();
		}
	}

	/**
	 * Validate callback inputs too; callers outside REST must not bypass the schema.
	 *
	 * @param array  $params Request params.
	 * @param string $action Callback name.
	 * @return true|\WP_Error Validation result.
	 */
	public static function validate( $params, $action ) {
		// Core REST permits scalar-to-array coercion; the callback stores the original tree.
		// Require actual nested lists here so a validated string cannot reach in_array().
		if ( 'condition_save_action' === $action && isset( $params['settings'] ) && is_array( $params['settings'] ) ) {
			foreach ( $params['settings'] as $templates ) {
				if ( ! is_array( $templates ) ) {
					return self::invalid( 'settings' );
				}
				foreach ( $templates as $conditions ) {
					if ( ! is_array( $conditions ) ) {
						return self::invalid( 'settings' );
					}
				}
			}
		}
		$rules = self::args( $action );
		foreach ( $rules as $key => $schema ) {
			if ( ! array_key_exists( $key, $params ) ) {
				if ( ! empty( $schema['required'] ) ) {
					return self::invalid( $key );
				}
				continue;
			}
			$value = $params[ $key ];
			if ( 'object' === $schema['type'] && ! is_array( $value ) ) {
				return self::invalid( $key );
			}
			$result = rest_validate_value_from_schema( $value, $schema, $key );
			if ( is_wp_error( $result ) || ! self::is_json_value( $value ) ) {
				return self::invalid( $key );
			}
		}
		if ( in_array( $action, array( 'global_settings_action', 'postx_presets_callback' ), true ) && 'set' === $params['type'] && ( ! isset( $params['data'] ) || ! is_array( $params['data'] ) ) ) {
			return self::invalid( 'data' );
		}
		if ( 'save_block_content_css' === $action && ! empty( $params['has_block'] ) && rest_sanitize_boolean( $params['has_block'] ) && ! array_key_exists( 'block_css', $params ) ) {
			return self::invalid( 'block_css' );
		}
		if ( 'search_settings_action' === $action && isset( $params['type'] ) && 'taxvalue' === $params['type'] ) {
			$selector = explode( '###', isset( $params['condition'] ) ? $params['condition'] : '' );
			if ( empty( $selector[0] ) || empty( $selector[1] ) ) {
				return self::invalid( 'condition' );
			}
		}
		if ( 'condition_settings_action' === $action && isset( $params['type'] ) ) {
			$selector = explode( '###', $params['type'] );
			if ( in_array( $selector[0], array( 'type', 'term' ), true ) && empty( $selector[1] ) ) {
				return self::invalid( 'type' );
			}
		}
		if ( in_array( $action, array( 'starter_import_content_callback', 'deletepost_getnewsletters' ), true ) && isset( $params['previous_items'] ) ) {
			$previous = json_decode( $params['previous_items'], true );
			if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $previous ) ) {
				return self::invalid( 'previous_items' );
			}
			foreach ( array( 'posts', 'term' ) as $kind ) {
				if ( ! isset( $previous[ $kind ] ) || ! is_array( $previous[ $kind ] ) ) {
					return self::invalid( 'previous_items' );
				}
				foreach ( $previous[ $kind ] as $id ) {
					if ( ( ! is_int( $id ) && ! is_string( $id ) ) || ! ctype_digit( (string) $id ) || (int) $id < 1 ) {
						return self::invalid( 'previous_items' );
					}
				}
			}
		}
		if ( 'addon_block_action' === $action ) {
			$settings = self::settings_schema();
			$valid    = isset( $settings[ $params['key'] ] ) ? rest_validate_value_from_schema( $params['value'], $settings[ $params['key'] ], 'value' ) : true;
			if ( is_wp_error( $valid ) ) {
				return self::invalid( 'value' );
			}
		}
		return true;
	}

	/**
	 * JSON-compatible request trees only, with a bound against recursive/hostile internal calls.
	 *
	 * @param mixed $value Value.
	 * @param int   $depth Nesting depth.
	 * @return bool Whether the value can be safely sanitized.
	 */
	private static function is_json_value( $value, $depth = 0 ) {
		if ( $depth > 32 || is_object( $value ) || is_resource( $value ) ) {
			return false;
		}
		if ( is_array( $value ) ) {
			foreach ( $value as $child ) {
				if ( ! self::is_json_value( $child, $depth + 1 ) ) {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * Return a REST error rather than a PHP warning/fatal or partial write.
	 *
	 * @param string $key Invalid field.
	 * @return \WP_Error Request error.
	 */
	private static function invalid( $key ) {
		return new \WP_Error(
			'ultp_invalid_param',
			/* translators: %s: Invalid request parameter name. */
			sprintf( __( 'Invalid value for %s.', 'ultimate-post' ), $key ),
			array(
				'status' => 400,
				'param'  => $key,
			)
		);
	}
}
