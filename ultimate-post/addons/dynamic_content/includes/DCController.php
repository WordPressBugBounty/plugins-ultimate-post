<?php
namespace ULTP;

use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 *
 * Dynamic Content
 *
 * @package ULTP\Addons
 * @since 4.1.1
 */
final class DCController {

	/**
	 * Setup class.
	 *
	 * @since v.4.1.1
	 */
	public function __construct() {
		require_once ULTP_PATH . '/addons/dynamic_content/includes/DCService.php';

		// PostX Pro before 1.9.0 does not answer the custom field filters below.
		if ( \ULTP\Includes\Durbin\Xpo::is_pro_before_1_9_0() ) {
			require_once ULTP_PATH . '/addons/dynamic_content/includes/DCLegacyFields.php';
			new DCLegacyFields();
		}

		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes() {
		register_rest_route(
			'ultp/v2',
			'/get_dynamic_content/',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'handle_dynamic_data' ),
					// there is an unauthenticated route for fetching user data against post, that can be used to fetch user sensitive data like user email, user password reset token hast ( user_activation_key ) , private or draft post title e.t.c as unauthenticated user.
					'permission_callback' => function ( $request ) {
						$post_id = $this->get_request_post_id( $request );

						if ( ! $this->user_can_access_post( $post_id ) ) {
							return false;
						}

						$data_type = method_exists( $request, 'get_param' )
							? $request->get_param('data_type')
							: ( isset( $request['data_type'] ) ? $request['data_type'] : '' );

						// Restrict author_info to users who can edit the author OR admins
						if ( $data_type === 'author_info' ) {
							$post      = get_post( $post_id );
							$author_id = $post ? (int) $post->post_author : 0;
							if ( ! current_user_can( 'edit_user', $author_id ) && ! current_user_can( 'manage_options' ) ) {
								return false;
							}
						}

						return true;
					},
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			'ultp/v2',
			'/get_custom_fields/',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'handle_custom_fields' ),
					// custom field keys can reveal the meta-field schema of posts the caller shouldn't be able to see (private/draft/pending/scheduled/password-protected), so require the same access check as get_dynamic_content.
					'permission_callback' => function ( $request ) {
						return $this->user_can_access_post( $this->get_request_post_id( $request ) );
					},
					'args'                => array(),
				),
			)
		);
	}

	/**
	 * Reads the `post_id` request param, compatible with both WP_REST_Request and plain array access.
	 *
	 * @param WP_REST_Request|array $request Request object or params array.
	 * @return int
	 */
	private function get_request_post_id( $request ) {
		return method_exists( $request, 'get_param' )
			? intval( $request->get_param( 'post_id' ) )
			: ( isset( $request['post_id'] ) ? intval( $request['post_id'] ) : 0 );
	}

	/**
	 * Whether the current user may access dynamic data/custom fields for the given post.
	 *
	 * @param int $post_id
	 * @return bool
	 */
	private function user_can_access_post( $post_id ) {
		// User must be logged in
		if ( ! is_user_logged_in() ) {
			return false;
		}

		// If no post_id, require edit_posts capability
		if ( $post_id <= 0 ) {
			return current_user_can( 'edit_posts' );
		}

		// Get the post
		$post = get_post( $post_id );
		if ( ! $post ) {
			return false;
		}

		// Check if post is password protected
		if ( post_password_required( $post ) ) {
			return false;
		}

		// User must be able to edit this specific post
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return false;
		}

		// For non-published posts, user must be author OR have edit_posts capability
		if ( $post->post_status !== 'publish' ) {
			if ( (int) $post->post_author !== get_current_user_id() && ! current_user_can( 'edit_posts' ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Dynamic data to show in the editor
	 *
	 * @since v.4.1.1
	 * @param WP_REST_Request $server
	 * @return WP_REST_Response
	 */
	public function handle_dynamic_data( $server ) {
		$args      = $server->get_params();
		$post_id   = isset( $args['post_id'] ) ? intval( $args['post_id'] ) : '';
		$data_type = isset( $args['data_type'] ) ? $args['data_type'] : '';
		$key       = isset( $args['key'] ) ? $args['key'] : '';

		return rest_ensure_response(
			array(
				'data' => DCService::get_dynamic_data(
					array(
						'post_id'   => $post_id,
						'key'       => $key,
						'data_type' => $data_type,
					)
				),
			)
		);
	}

	/**
	 * Gets custom fields keys and labels
	 *
	 * @since v.4.1.1
	 * @param WP_REST_Request $server
	 * @return WP_REST_Response
	 */
	public function handle_custom_fields( $server ) {
		$args = $server->get_params();

		$post_id    = isset( $args['post_id'] ) ? intval( $args['post_id'] ) : 0;
		$post_type  = isset( $args['post_type'] ) ? $args['post_type'] : '';
		$field_type = isset( $args['acf_field_type'] ) ? $args['acf_field_type'] : '';

		$res = array();

		// All metas
		$all_custom_metas = $this->get_custom_metas( $post_id, $post_type );

		// Field sources other than plain custom metas, added by PostX Pro.
		$sources = apply_filters(
			'ultp_dc_custom_field_sources',
			array(),
			array(
				'post_id'    => $post_id,
				'post_type'  => $post_type,
				'field_type' => $field_type,
			)
		);
		$sources = is_array( $sources ) ? $sources : array();

		// Keys and key prefixes that belong to those sources are not plain custom metas.
		$source_keys     = array();
		$source_prefixes = array();
		foreach ( $sources as $source ) {
			if ( ! empty( $source['_fields'] ) && is_array( $source['_fields'] ) ) {
				$source_keys = array_merge( $source_keys, $source['_fields'] );
			}
			if ( ! empty( $source['prefixes'] ) && is_array( $source['prefixes'] ) ) {
				$source_prefixes = array_merge( $source_prefixes, $source['prefixes'] );
			}
		}

		$filtered_custom_metas = array_values( array_diff( $all_custom_metas, $source_keys ) );
		$filtered_custom_metas = array_values(
			array_filter(
				$filtered_custom_metas,
				function ( $item ) use ( $source_prefixes ) {
					foreach ( $source_prefixes as $prefix ) {
						if ( str_starts_with( $item, $prefix ) ) {
							return false;
						}
					}
					return true;
				}
			)
		);

		// Return value
		$res['custom_metas'] = array(
			'fields' => array_map(
				function ( $item ) {
					return array(
						'label' => $item,
						'value' => $item,
					);
				},
				$filtered_custom_metas
			),
		);

		foreach ( $sources as $key => $source ) {
			if ( 'custom_metas' === $key || ! is_array( $source ) ) {
				continue;
			}
			$res[ $key ] = array(
				'fields' => isset( $source['fields'] ) && is_array( $source['fields'] ) ? $source['fields'] : array(),
			);
		}

		return rest_ensure_response(
			array(
				'data' => $res,
			)
		);
	}

	/**
	 * Get custom post metas
	 *
	 * @param string $post_type Post Type.
	 * @since 4.1.0
	 * @return array
	 */
	public function get_custom_metas( $post_id, $post_type ) {

		$meta_keys = array();
		$all_keys  = array();

		if ( $post_id !== 0 ) {
			$all_keys = get_post_custom_keys( $post_id );

		} else {
			if ( ! isset( $post_type ) ) {
				return $meta_keys;
			}

			$posts = get_posts(
				array(
					'post_type'      => $post_type,
					'posts_per_page' => -1,
					'fields'         => 'id',
				)
			);

			if ( empty( $posts ) ) {
				return $meta_keys;
			}

			foreach ( $posts as $post ) {
				$post_id = isset( $post->ID ) ? $post->ID : 0;
				$keys    = get_post_custom_keys( $post_id );
				if ( is_array( $keys ) ) {
					foreach ( $keys as $key ) {
						$all_keys[] = $key;
					}
				}
			}
		}

		if ( is_array( $all_keys ) ) {
			foreach ( $all_keys as $key ) {
				if ( ! isset( $meta_keys[ $key ] ) && ! str_starts_with( $key, '_' ) ) {
					$meta_keys[ $key ] = $key;
				}
			}
		}

		return array_keys( $meta_keys );
	}
}
