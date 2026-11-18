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
					'args'                => Request_Validation::args( 'handle_dynamic_data' ),
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
						return $this->user_can_access_custom_fields( $this->get_request_post_id( $request ), $request->get_param( 'post_type' ) ?? '' );
					},
					'args'                => Request_Validation::args( 'handle_custom_fields' ),
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
	 * Authorize an explicit post or an editorial post type before listing field sources.
	 *
	 * @param int    $post_id   Target post ID, or zero for a post-type picker.
	 * @param string $post_type Requested post type, optional for an explicit post.
	 * @return bool
	 */
	private function user_can_access_custom_fields( $post_id, $post_type ) {
		if ( ! is_user_logged_in() || $post_id < 0 || ! is_string( $post_type ) ) {
			return false;
		}

		$post_type = sanitize_key( $post_type );
		if ( $post_id > 0 ) {
			$post = get_post( $post_id );
			return $post && ( '' === $post_type || $post->post_type === $post_type ) && $this->user_can_access_post( $post_id );
		}

		$type = get_post_type_object( $post_type );
		return $type && ( $type->show_ui || is_post_type_viewable( $type ) ) && current_user_can( $type->cap->edit_posts );
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
		$valid = Request_Validation::validate( $server->get_params(), 'handle_dynamic_data' );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}
		$args    = $server->get_params();
		$post_id = isset( $args['post_id'] ) ? intval( $args['post_id'] ) : '';
		// Only the types DCService::get_dynamic_data() handles; anything else gets no data, as before.
		$data_type = isset( $args['data_type'] ) && in_array( $args['data_type'], array( 'link', 'post_types', 'post_info', 'author_info', 'site_info', 'cf', 'comment_count' ), true ) ? $args['data_type'] : '';
		// Not sanitize_key(): custom field keys (ACF, Meta Box, Pods) can be mixed case.
		$key = isset( $args['key'] ) && is_string( $args['key'] ) ? sanitize_text_field( $args['key'] ) : '';

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
		$valid = Request_Validation::validate( $server->get_params(), 'handle_custom_fields' );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}
		$args = $server->get_params();

		$post_id = isset( $args['post_id'] ) ? intval( $args['post_id'] ) : 0;
		// register_post_type() runs names through sanitize_key(), so a real post type is unchanged.
		$post_type  = isset( $args['post_type'] ) && is_string( $args['post_type'] ) ? sanitize_key( $args['post_type'] ) : '';
		$field_type = isset( $args['acf_field_type'] ) && is_string( $args['acf_field_type'] ) ? sanitize_text_field( $args['acf_field_type'] ) : '';

		if ( ! $this->user_can_access_custom_fields( $post_id, $post_type ) ) {
			return new \WP_Error( 'rest_forbidden', __( 'You are not allowed to access these custom fields.', 'ultimate-post' ), array( 'status' => rest_authorization_required_code() ) );
		}
		if ( $post_id > 0 ) {
			$post_type = get_post_type( $post_id );
		}

		$res = array();

		// All metas
		$all_custom_metas = $this->get_custom_metas( $post_id, $post_type );

		$context = array(
			'post_id'    => $post_id,
			'post_type'  => $post_type,
			'field_type' => $field_type,
		);

		// Field sources other than plain custom metas. Each one returns the `fields` list the editor shows.
		$sources = apply_filters( 'ultp_dc_custom_field_sources', array(), $context );

		// Plain custom meta keys offered to the editor. A filter may remove keys that a source above lists.
		$filtered_custom_metas = array_values( apply_filters( 'ultp_dc_custom_metas', $all_custom_metas, $context, $sources ) );

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

		if ( ! $this->user_can_access_custom_fields( $post_id, $post_type ) ) {
			return $meta_keys;
		}
		$post_type = sanitize_key( $post_type );

		if ( $post_id !== 0 ) {
			$all_keys = get_post_custom_keys( $post_id );

		} else {
			$type  = get_post_type_object( $post_type );
			$query = array(
				'post_type'      => $post_type,
				'post_status'    => 'attachment' === $post_type ? 'inherit' : 'publish',
				'posts_per_page' => 100,
				'fields'         => 'ids',
			);
			if ( ! current_user_can( $type->cap->edit_others_posts ) ) {
				$query['author'] = get_current_user_id();
			}

			$posts = get_posts( $query );

			if ( empty( $posts ) ) {
				return $meta_keys;
			}

			foreach ( $posts as $post_id ) {
				$post_id = (int) $post_id;
				if ( ! $this->user_can_access_post( $post_id ) ) {
					continue;
				}
				$keys = get_post_custom_keys( $post_id );
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
