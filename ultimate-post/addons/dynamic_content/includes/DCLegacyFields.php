<?php
namespace ULTP;

defined( 'ABSPATH' ) || exit;

/**
 * Dynamic Content: ACF, Meta Box and Pods for PostX Pro before 1.9.0
 *
 * PostX Pro 1.9.0 and later answer `ultp_dc_custom_field_sources`, `ultp_dc_field_value` and
 * `ultp_dc_is_field_key` itself. Older Pro does not, so DCController loads this class only when
 * Xpo::is_pro_before_1_9_0(), to keep those sites as they were. Delete it when 1.9.0 becomes the
 * supported floor.
 */
class DCLegacyFields {

	const PREFIXES = array( 'acf_', 'mb_', 'pods_' );

	public function __construct() {
		add_filter( 'ultp_dc_custom_field_sources', array( $this, 'add_sources' ), 10, 2 );
		add_filter( 'ultp_dc_field_value', array( $this, 'get_field_value' ), 10, 3 );
		add_filter( 'ultp_dc_is_field_key', array( $this, 'is_field_key' ), 10, 2 );
	}

	/**
	 * Add the ACF, Meta Box and Pods field lists.
	 *
	 * @param array $sources Sources added so far.
	 * @param array $args    `post_id`, `post_type`, `field_type`.
	 * @return array
	 */
	public function add_sources( $sources, $args = array() ) {
		if ( ! is_array( $sources ) ) {
			$sources = array();
		}
		$post_id    = isset( $args['post_id'] ) ? intval( $args['post_id'] ) : 0;
		$post_type  = isset( $args['post_type'] ) ? $args['post_type'] : '';
		$field_type = isset( $args['field_type'] ) ? $args['field_type'] : '';

		$sources['acf']  = $this->get_acf_fields( $post_id, $field_type, $post_type );
		$sources['mb']   = $this->get_mb_fields( $post_id, $field_type, $post_type );
		$sources['pods'] = $this->get_pods_fields( $post_id, $field_type, $post_type );

		return $sources;
	}

	/**
	 * Whether a dynamic content key is an ACF, Meta Box or Pods field.
	 *
	 * @param bool   $is_field Answer so far.
	 * @param string $key      Key with its prefix.
	 * @return bool
	 */
	public function is_field_key( $is_field, $key = '' ) {
		if ( $is_field ) {
			return true;
		}
		foreach ( self::PREFIXES as $prefix ) {
			if ( self::check_prefix( $key, $prefix ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Value of an ACF, Meta Box or Pods field.
	 *
	 * @param mixed  $value    Value so far; `null` when nothing has answered.
	 * @param string $meta_key Key with its prefix.
	 * @param mixed  $post_id  Post ID, already resolved by PostX (may be empty).
	 * @return mixed `null` when the key is not one of these.
	 */
	public function get_field_value( $value, $meta_key = '', $post_id = '' ) {
		if ( null !== $value || ! is_string( $meta_key ) || '' === $meta_key ) {
			return $value;
		}

		if ( self::check_prefix_and_remove( $meta_key, 'acf_' ) ) {
			if ( class_exists( 'ACF' ) ) {
				if ( ! empty( $post_id ) ) {
					return self::extract_acf_value( get_field( $meta_key, $post_id ) );
				} else {
					return get_field( $meta_key, false );
				}
			}
			return '';
		}

		if ( self::check_prefix_and_remove( $meta_key, 'mb_' ) ) {
			if ( function_exists( 'rwmb_get_value' ) ) {
				$field_value = rwmb_get_value( $meta_key, array(), $post_id );
				return self::extract_mb_value( $field_value, $meta_key, $post_id );
			}
			return '';
		}

		if ( self::check_prefix_and_remove( $meta_key, 'pods_' ) ) {
			if ( function_exists( 'pods' ) ) {
				$pods = pods( get_post_type( $post_id ), $post_id );
				if ( is_object( $pods ) && method_exists( $pods, 'exists' ) &&
					$pods->exists() && method_exists( $pods, 'field' )
				) {
					return self::extract_pods_value( $pods->field( $meta_key ) );
				}
			}
			return '';
		}

		return $value;
	}

	/**
	 * Get ACF fields
	 *
	 * @param int    $post_id
	 * @param string $field_type
	 * @param string $post_type
	 *
	 * @return array
	 */
	public function get_acf_fields( $post_id, $field_type, $post_type ) {
		$res = array(
			'fields'   => array(),
			'_fields'  => array(),
			'prefixes' => array(),
		);

		if ( ! class_exists( 'ACF' ) ) {
			return $res;
		}

		if ( empty( $post_id ) && empty( $post_type ) ) {
			global $post;
			$post_id = isset( $post->ID ) ? $post->ID : '';
		}

		$allowed_types = self::get_allowed_acf_field_types( $field_type );

		// If post_id set set, return ACF fields for that post
		if ( ! empty( $post_id ) ) {
			$fields = get_field_objects( $post_id );
			if ( is_array( $fields ) ) {
				foreach ( $fields as $field ) {
					if ( in_array( $field['type'], $allowed_types, true ) ) {
						$res['fields'][] = array(
							'value' => $field['name'],
							'label' => $field['label'],
						);
					}

					$res['_fields'][] = $field['name'];

					// For filtering out pesky repeater field's sub fields
					if ( $field['type'] === 'repeater' ) {
						$res['prefixes'][] = $field['name'];
					}
				}
			}
		} elseif ( ! empty( $post_type ) ) {
			$groups = acf_get_field_groups( array( 'post_type' => $post_type ) );
			foreach ( $groups as $group ) {
				$fields = acf_get_fields( $group['ID'] );
				if ( is_array( $fields ) ) {
					foreach ( $fields as $field ) {
						if ( in_array( $field['type'], $allowed_types, true ) ) {
							$res['fields'][] = array(
								'value' => $field['name'],
								'label' => $field['label'] . ' [' . $group['title'] . ']',
							);
						}
						$res['_fields'][] = $field['name'];

						if ( $field['type'] === 'repeater' ) {
							$res['prefixes'][] = $field['name'];
						}
					}
				}
			}
		}

		return $res;
	}

	/**
	 * Get Meta Box fields
	 *
	 * @param int    $post_id
	 * @param string $field_type
	 * @param string $post_type
	 *
	 * @return array
	 */
	public function get_mb_fields( $post_id, $field_type, $post_type ) {
		$res = array(
			'fields'   => array(),
			'_fields'  => array(),
			'prefixes' => array(),
		);

		if ( ! function_exists( 'rwmb_get_field_settings' ) ) {
			return $res;
		}

		$value = null;

		if ( empty( $post_id ) && empty( $post_type ) ) {
			global $post;
			$value = isset( $post->ID ) ? $post->ID : '';
		} elseif ( ! empty( $post_id ) ) {
			$value = $post_id;
		} elseif ( ! empty( $post_type ) ) {
			$value = $post_type;
		}

		if ( empty( $value ) ) {
			return $res;
		}

		$allowed_types = self::get_allowed_mb_field_type( $field_type );

		$fields = rwmb_get_object_fields( $value );

		if ( is_array( $fields ) ) {
			foreach ( $fields as $field ) {
				if ( in_array( $field['type'], $allowed_types, true ) ) {
					$res['fields'][] = array(
						'value' => $field['id'],
						'label' => $field['name'],
					);
				}

				$res['_fields'][] = $field['id'];
			}
		}

		return $res;
	}

	/**
	 * Get Pods fields
	 *
	 * @param int    $post_id
	 * @param string $field_type
	 * @param string $post_type
	 *
	 * @return array
	 */
	public function get_pods_fields( $post_id, $field_type, $post_type ) {
		$res = array(
			'fields'   => array(),
			'_fields'  => array(),
			'prefixes' => array(),
		);

		if ( ! function_exists( 'pods' ) ) {
			return $res;
		}

		if ( empty( $post_id ) ) {
			global $post;
			$post_id = isset( $post->ID ) ? $post->ID : '';
		}

		if ( empty( $post_type ) ) {
			$post_type = get_post_type( $post_id );
		}

		if ( empty( $post_type ) ) {
			return $res;
		}

		$allowed_types = self::get_allowed_pods_field_types( $field_type );

		$pods = pods( $post_type, $post_id );
		if ( is_object( $pods ) && method_exists( $pods, 'exists' ) && $pods->exists() && method_exists( $pods, 'fields' ) ) {
			$fields = $pods->fields();
			if ( is_array( $fields ) ) {
				foreach ( $fields as $field ) {
					if ( in_array( $field['type'], $allowed_types, true ) &&
						'field' === $field['object_type'] &&
						'post_type' === $field['object_storage_type']
					) {
						$res['fields'][] = array(
							'value' => $field['name'],
							'label' => $field['label'],
						);
					}

					$res['_fields'][] = $field['name'];
				}
			}
		}

		return $res;
	}

	/**
	 * Get ACF allowed field types
	 *
	 * @param string $type optional, possible values 'text'|'image'|'url'.
	 *
	 * @return array
	 */
	public static function get_allowed_acf_field_types( $type = 'text' ) {

		if ( 'image' === $type ) {
			return array(
				'image',
			);
		} elseif ( 'url' === $type ) {
			return array(
				'text',
				'email',
				'image',
				'file',
				'page_link',
				'url',
				'link',
			);
		}

		return array(
			'text',
			'textarea',
			'number',
			'range',
			'email',
			'url',
			'password',
			'wysiwyg',
			'select',
			'checkbox',
			'radio',
			'true_false',
			'date_picker',
			'time_picker',
			'date_time_picker',
			'color_picker',
		);
	}

	/**
	 * Get Meta Box allowed field types
	 *
	 * @param string $type optional, possible values 'text'|'image'|'url'.
	 *
	 * @return array
	 */
	public static function get_allowed_mb_field_type( $type ) {

		if ( $type === 'image' ) {
			return array(
				'image',
				'image_advanced',
				'image_upload',
				'single_image',
				'url',
				'file',
				'file_advanced',
				'file_input',
				'file_upload',
			);
		} elseif ( $type === 'url' ) {
			return array(
				'url',
				'file',
				'file_advanced',
				'file_input',
				'file_upload',
			);
		}

		return array(
			'text',
			'email',
			'number',
			'textaraa',
			'select',
			'radio',
			'checkbox',
			'checkbox_list',
		);
	}

	/**
	 * Get Pods allowed field types
	 *
	 * @param string $type optional, possible values 'text'|'image'|'url'.
	 *
	 * @return array
	 */
	public static function get_allowed_pods_field_types( $type = 'text' ) {

		if ( 'image' === $type ) {
			return array(
				'images',
				'file',
			);
		} elseif ( 'url' === $type ) {
			return array(
				'text',
				'email',
				'images',
				'file',
				'link',
				'website',
			);
		}

		return array(
			'text',
			'paragraph',
			'password',
			'phone',
			'time',
			'website',
			'number',
			'wysiwyg',
			'email',
			'link',
			'boolean',
			'code',
			'number',
			'currency',
			'date',
			'datetime',
		);
	}

	/**
	 * Formats ACF value based upon data type
	 *
	 * @param array|string $value ACF Meta Value.
	 *
	 * @return string
	 */
	private static function extract_acf_value( $value ) {
		if ( is_array( $value ) ) {
			$image_id = absint( $value['id'] );
			$value    = wp_get_attachment_image_src( $image_id );
		} elseif ( is_numeric( $value ) ) {
			return $value;
		}

		return $value;
	}

	/**
	 * Formats Pods value based upon data type
	 *
	 * @param array|string $value Pods Meta Value.
	 *
	 * @return string
	 */
	private static function extract_pods_value( $value ) {
		if ( is_array( $value ) ) {

			// Single image
			if ( isset( $value['guid'] ) ) {
				return $value['guid'];
			}

			// Multi image
			if ( isset( $value[0]['guid'] ) ) {
				return $value[0]['guid'];
			}

			// Repeater
			if ( isset( $value[0] ) &&
				( is_string( $value[0] ) || is_numeric( $value[0] ) || is_bool( $value[0] ) )
			) {
				return $value[0];
			}

			return '';
		}

		return $value;
	}

	/**
	 * Formats Meta Box value based upon data type
	 *
	 * @param array|string $custom_fields Meta Box value.
	 * @param string       $meta_key      Field ID.
	 * @param mixed        $post_id       Post ID.
	 *
	 * @return string
	 */
	private static function extract_mb_value( $custom_fields, $meta_key, $post_id ) {

		if ( ! is_array( $custom_fields ) ) {
			return strval( $custom_fields );
		}

		$field_data = rwmb_get_field_settings( $meta_key, array(), $post_id );

		// Process the first element if its an array
		foreach ( $custom_fields as $field ) {
			$field_type  = $field_data['type'];
			$field_value = '';

			switch ( $field_type ) {
				case 'checkbox':
				case 'radio':
					$field_value = isset( $field['checked'] ) ? 'checked' : '';
					break;
				case 'select':
				case 'radio_list':
				case 'checkbox_list':
				case 'taxonomy':
				case 'taxonomy_advanced':
				case 'post':
				case 'post_advanced':
				case 'post_checkbox_list':
				case 'post_select':
					$field_value = isset( $field['selected'] ) ? $field['selected'] : '';
					break;
				case 'file':
				case 'file_input':
				case 'file_advanced':
				case 'file_upload':
					$field_value = isset( $field['url'] ) ? $field['url'] : $field['id'];
					break;
				case 'single_image':
					$field_value = wp_get_attachment_image_url( $custom_fields['ID'], 'full' );
					break;
				case 'image':
				case 'image_advanced':
				case 'image_upload':
				case 'plupload_image':
				case 'thickbox_image':
					$field_value = wp_get_attachment_image_url( $field['ID'], 'full' );
					break;
				case 'date':
				case 'datetime':
				case 'time':
					$field_value = $field['date'];
					break;
				default:
					$field_value = $field['value'];
					break;
			}

			return $field_value;
		}

		return '';
	}

	private static function check_prefix( $key, $prefix ) {
		return is_string( $key ) && substr( $key, 0, strlen( $prefix ) ) === $prefix;
	}

	private static function check_prefix_and_remove( &$key, $prefix ) {
		if ( substr( $key, 0, strlen( $prefix ) ) === $prefix ) {
			$key = substr( $key, strlen( $prefix ) );
			return true;
		}
		return false;
	}
}
