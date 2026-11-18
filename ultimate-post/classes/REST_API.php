<?php
/**
 * REST API Action.
 *
 * @package ULTP\REST_API
 * @since v.1.0.0
 */

namespace ULTP;

defined( 'ABSPATH' ) || exit;

/**
 * REST API class.
 *
 * Handles custom REST API endpoints for Ultimate Post plugin.
 *
 * @package ULTP\REST_API
 * @since 4.0.0
 */
class REST_API {

	/**
	 * Upper bound for the search endpoint's postPerPage. Matches the max of the
	 * Search block's "Show Initial Post" slider, so no UI value is ever capped.
	 * This is the same max range of the slider in gutenburg editor. 
	 * 100 is enough for search bar result.
	 *
	 * @since v.5.0.35
	 */
	const SEARCH_POSTS_PER_PAGE_MAX = 100;

	/**
	 * Longest accepted search term. Longer strings only widen the LIKE pattern
	 * scanned against every row; no real query needs more.
	 *
	 * @since v.5.0.35
	 */
	const SEARCH_TEXT_MAX_LENGTH = 200;

	/**
	 * Upper bound for excerptLimit. Also keeps $num_words + 1 inside integer range
	 * in wp_trim_words(), which otherwise overflows to float on a huge value.
	 *
	 * @since v.5.0.35
	 */
	const SEARCH_EXCERPT_WORDS_MAX = 400;

	/**
	 * Setup class.
	 *
	 * @since v.1.0.0
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'ultp_register_route' ) );
	}

	/**
	 * REST API Action
	 *
	 * @since v.1.0.0
	 * @return void
	 */
	public function ultp_register_route() {
		register_rest_route(
			'ultp',
			'common_data',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'args'                => array( 'wpnonce' => array() ),
				'callback'            => array( $this, 'ultp_route_common_data' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
		register_rest_route(
			'ultp',
			'/fetch_posts/',
			array(
				array(
					'methods'             => 'POST',
					'args'                => Request_Validation::args( 'ultp_route_post_data' ),
					'callback'            => array( $this, 'ultp_route_post_data' ),
					'permission_callback' => function () {
						return current_user_can( 'edit_posts' );
					},
				),
			)
		);
		register_rest_route(
			'ultp',
			'/specific_taxonomy/',
			array(
				array(
					'methods'             => 'POST',
					'args'                => Request_Validation::args( 'ultp_route_taxonomy_info_data' ),
					'callback'            => array( $this, 'ultp_route_taxonomy_info_data' ),
					'permission_callback' => function () {
						return current_user_can( 'edit_others_posts' );
					},
				),
			)
		);
		register_rest_route(
			'ultp/v1',
			'/search/',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'search_settings_action' ),
					'permission_callback' => function () {
						return current_user_can( 'edit_others_posts' );
					},
					'args'                => Request_Validation::args( 'search_settings_action' ),
				),
			)
		);
		register_rest_route(
			'ultp/v2',
			'/premade_wishlist_save/',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'premade_wishlist_save' ),
					'permission_callback' => function ( $request ) {
						// The wishlist is one site-wide option: changing it is for administrators.
						// Mirrors the write condition in premade_wishlist_save().
						$is_write = ! empty( $request['id'] ) && 'fetchData' !== $request['type'];
						return current_user_can( $is_write ? 'manage_options' : 'edit_others_posts' );
					},
					'args'                => Request_Validation::args( 'premade_wishlist_save' ),
				),
			)
		);
		register_rest_route(
			'ultp',
			'/ultp_search_data/',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'ultp_search_result' ),
					// Public on purpose (live results for visitors), but not unchecked: see the callback.
					'permission_callback' => array( $this, 'search_permission_check' ),
					'args'                => array_merge(
						Request_Validation::args( 'ultp_search_result' ),
						array(
							'searchText' => array(
								'type'              => 'string',
								'required'          => true,
								'validate_callback' => 'rest_validate_request_arg',
							),
						)
					),
				),
			)
		);
		register_rest_route(
			'ultp/v2',
			'/init_site_dark_logo/',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'init_site_dark_logo_callback' ),
					'permission_callback' => function () {
						// Sets the site-wide dark logo option.
						return current_user_can( 'manage_options' );
					},
					'args'                => Request_Validation::args( 'init_site_dark_logo_callback' ),
				),
			)
		);
		register_rest_route(
			'ultp/v2',
			'/get_ultp_image_size/',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'get_custom_image_size' ),
					'permission_callback' => function () {
						return current_user_can( 'edit_posts' );
					},
					'args'                => Request_Validation::args( 'get_custom_image_size' ),
				),
			)
		);
	}

	/**
	 * Save and get premade_wishlist_save
	 *
	 * Handle REST API request.
	 *
	 * @since 4.0.0
	 * @param ARRAY $server $request The REST request object.
	 * @return array The response data.
	 */
	public function premade_wishlist_save( $server ) {
		$valid = Request_Validation::validate( $server->get_params(), 'premade_wishlist_save' );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}
		$post         = $server->get_params();
		$id           = isset( $post['id'] ) ? ultimate_post()->ultp_rest_sanitize_params( $post['id'] ) : '';
		$action       = isset( $post['action'] ) ? ultimate_post()->ultp_rest_sanitize_params( $post['action'] ) : '';
		$wishListArr  = get_option( 'ultp_premade_wishlist', array() );
		$request_type = isset( $post['type'] ) ? ultimate_post()->ultp_rest_sanitize_params( $post['type'] ) : '';

		// Older saves left gaps in the keys, which encode as a JSON object. Keep the same IDs as a plain list.
		$wishListArr = is_array( $wishListArr ) ? array_values( $wishListArr ) : array();

		if ( $id && $request_type != 'fetchData' && current_user_can( 'manage_options' ) ) {
			if ( $action == 'remove' ) {
				$index = array_search( $id, $wishListArr );
				if ( $index !== false ) {
					unset( $wishListArr[ $index ] );
					$wishListArr = array_values( $wishListArr );
				}
			} elseif ( ! in_array( $id, $wishListArr ) ) {
					array_push( $wishListArr, $id );
			}
			update_option( 'ultp_premade_wishlist', $wishListArr );
		}
		return rest_ensure_response(
			array(
				'success'     => true,
				'message'     => $action == 'remove' ? __( 'Item has been removed from wishlist.', 'ultimate-post' ) : __( 'Item added to wishlist.', 'ultimate-post' ),
				'wishListArr' => wp_json_encode( $wishListArr ),
			)
		);
	}



	/**
	 * Search_settings_action
	 *
	 * @param  mixed $server .
	 * @return array
	 */
	public function search_settings_action( $server ) {
		$valid = Request_Validation::validate( $server->get_params(), 'search_settings_action' );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}
		global $wpdb;
		$post           = $server->get_params();
		$request_type   = isset( $post['type'] ) ? ultimate_post()->ultp_rest_sanitize_params( $post['type'] ) : '';
		$condition_type = isset( $post['condition'] ) ? ultimate_post()->ultp_rest_sanitize_params( $post['condition'] ) : '';
		$term_type      = isset( $post['term'] ) ? ultimate_post()->ultp_rest_sanitize_params( $post['term'] ) : '';
		// get post type
		$get_multiple_post_type = isset( $post['postType'] ) ? ultimate_post()->ultp_rest_sanitize_params( $post['postType'] ) : array();
		$multiple_post_type     = is_array( $get_multiple_post_type ) ? $get_multiple_post_type : array();
		/**
		 * Answers the list of post types whose taxonomies the picker offers.
		 *
		 * @param array|null $post_types The sanitized list, or null when nothing answers.
		 * @param mixed      $request    The sanitized `postType` value as sent.
		 * @param array      $args       `condition` and `term` of the request.
		 */
		$post_types_answer = apply_filters(
			'ultp_taxvalue_post_types',
			null,
			$get_multiple_post_type,
			array(
				'condition' => $condition_type,
				'term'      => $term_type,
			)
		);
		if ( is_array( $post_types_answer ) ) {
			$multiple_post_type = $post_types_answer;
		}
		// Only registered post types are asked for their taxonomies.
		$multiple_post_type  = array_values(
			array_filter(
				$multiple_post_type,
				function ( $type ) {
					return is_string( $type ) && post_type_exists( $type );
				}
			)
		);
		switch ( $request_type ) {
			case 'posts':
			case 'allpost':
			case 'postExclude':
				$post_type = array( 'post' );
				if ( $request_type == 'allpost' ) {
					$post_type = array_keys( ultimate_post()->get_post_type() );
				} elseif ( $request_type == 'postExclude' ) {
					$post_type = array( $condition_type );
				}
				/**
				 * Post types the post picker searches.
				 *
				 * @param array  $post_type      `post`; every public post type for `allpost`; the condition's post type for `postExclude`.
				 * @param string $request_type   `posts`, `allpost` or `postExclude`.
				 * @param string $condition_type The sanitized `condition` of the request.
				 */
				$post_type = apply_filters( 'ultp_post_search_post_types', $post_type, $request_type, $condition_type );
				$post_type = ultimate_post()->allowed_query_post_types( $post_type, true );
				$args = array(
					'post_type'      => $post_type,
					'post_status'    => 'publish',
					'posts_per_page' => 10,
				);
				if ( is_numeric( $term_type ) ) {
					$args['p'] = $term_type;
				} else {
					$args['s'] = $term_type;
				}

				$args         = ultimate_post()->restrict_query_access( $args, true );
				$post_results = new \WP_Query( $args );
				$data         = array();
				if ( ! empty( $post_results ) ) {
					while ( $post_results->have_posts() ) {
						$post_results->the_post();
						$id     = get_the_ID();
						$title  = html_entity_decode( get_the_title() );
						$data[] = array(
							'value' => $id,
							'title' => ( $title ? '[ID: ' . $id . '] ' . $title : ( '[ID: ' . $id . ']' ) ),
						);
					}
					wp_reset_postdata();
				}
				return array(
					'success' => true,
					'data'    => $data,
				);
				break;

			case 'author':
				$post_results = ultimate_post()->search_public_authors( $term_type );
				$data         = array();
				if ( ! empty( $post_results ) ) {
					foreach ( $post_results as $key => $val ) {
						$data[] = array(
							'value' => $val->ID,
							'title' => '[ID: ' . $val->ID . '] ' . $val->display_name,
						);
					}
				}
				return array(
					'success' => true,
					'data'    => $data,
				);
				break;

			case 'taxvalue':
				$split = explode( '###', $condition_type );
				$data  = array();
				// The rows of a multi-taxonomy condition are added by the filter below, not here.
				if ( $split[1] != 'multiTaxonomy' ) {
					/**
					 * Answers the taxonomies whose terms the term picker lists for a single-taxonomy condition.
					 *
					 * @param array $taxonomies Default: the taxonomy of the condition.
					 * @param array $args       `condition` (`postType###taxonomy`), `post_types` (the registered post types of the
					 *                          request) and `term` (the search text or ID).
					 */
					$condition = apply_filters(
						'ultp_taxvalue_taxonomies',
						array( $split[1] ),
						array(
							'condition'  => $condition_type,
							'post_types' => $multiple_post_type,
							'term'       => $term_type,
						)
					);

					$args = array(
						'taxonomy'   => $condition,
						'fields'     => 'all',
						'orderby'    => 'id',
						'order'      => 'ASC',
						'name__like' => $term_type,
					);
					if ( is_numeric( $term_type ) ) {
						unset( $args['name__like'] );
						$args['include'] = array( $term_type );
					}

					$args['taxonomy'] = ultimate_post()->allowed_picker_taxonomies( $args['taxonomy'] );
					$post_results     = empty( $args['taxonomy'] ) ? array() : get_terms( $args );
					if ( ! empty( $post_results ) && ! is_wp_error( $post_results ) ) {
						foreach ( $post_results as $key => $val ) {
							$data[] = array(
								'value'      => urldecode( $val->slug ),
								'title'      => '[ID: ' . $val->term_id . '] ' . $val->name,
								'live_title' => $val->name,
							);
						}
					}
				}
				/**
				 * Answers the rows of the term picker.
				 *
				 * @param array $rows Rows PostX builds: `value` and `title` (and `live_title` for one taxonomy).
				 * @param array $args `condition` (`postType###taxonomy` or `postType###multiTaxonomy`), `post_types`
				 *                    (the registered post types of the request) and `term` (the search text or ID).
				 */
				$data = apply_filters(
					'ultp_taxvalue_rows',
					$data,
					array(
						'condition'  => $condition_type,
						'post_types' => $multiple_post_type,
						'term'       => $term_type,
					)
				);
				return array(
					'success' => true,
					'data'    => $data,
				);
				break;

			case 'taxExclude':
				/**
				 * Answers the rows of the term picker of an exclude-terms condition.
				 *
				 * @param array $rows Default: one empty "- Select -" row.
				 * @param array $args `condition` (the post type), `post_types` (the registered post types of the request)
				 *                    and `term` (the search text or ID).
				 */
				$data = apply_filters(
					'ultp_taxvalue_exclude_rows',
					array(
						array(
							'value' => '',
							'title' => '- Select -',
						),
					),
					array(
						'condition'  => $condition_type,
						'post_types' => $multiple_post_type,
						'term'       => $term_type,
					)
				);
				return array(
					'success' => true,
					'data'    => $data,
				);
				break;
				// allPostType
			case 'allPostType':
				$all_types = array_values( get_post_types( array( 'public' => true ), 'names' ) );
				$postType  = array();
				foreach ( $all_types as $type ) {
					$postType[] = array(
						'title' => $type,
						'value' => $type,
					);
				}

				return array(
					'success' => true,
					'data'    => $postType,
				);
			default:
				return array(
					'success' => true,
					'data'    => array(
						array(
							'value' => '',
							'title' => '- Select -',
						),
					),
				);
				break;
		}
	}

	/**
	 * Post Data Response of REST API
	 *
	 * @since v.1.0.0
	 * @param MIXED | $prams (ARRAY), Local (BOOLEAN).
	 * @return ARRAY | Response Image Size as Array
	 */
	public function ultp_route_post_data( $prams ) {
		$valid = Request_Validation::validate( $prams->get_params(), 'ultp_route_post_data' );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}
		$prams = $prams->get_params();
		$nonce = isset( $prams['wpnonce'] ) ? sanitize_text_field( wp_unslash( $prams['wpnonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'ultp-admin-nonce' ) ) {
			die();
		}
		// Editor preview of a block's posts: same capability as the route's permission_callback.
		if ( ! current_user_can( 'edit_posts' ) ) {
			die();
		}
		$data = array();
		$args = ultimate_post()->get_query( ultimate_post()->ultp_rest_sanitize_params( $prams ), true );
		// The request picks the post type, so keep it to types the user may see.
		if ( is_array( $args ) && ! empty( $args['post_type'] ) ) {
			$args['post_type'] = $this->restrict_preview_post_types( $args['post_type'] );
			if ( empty( $args['post_type'] ) ) {
				return rest_ensure_response( $data );
			}
		}
		$loop    = new \WP_Query( $args );
		$max_tax = isset( $prams['maxTaxonomy'] ) && $prams['maxTaxonomy'] ? ( ultimate_post()->ultp_rest_sanitize_params( $prams['maxTaxonomy'] ) == '0' ? 0 : ultimate_post()->ultp_rest_sanitize_params( $prams['maxTaxonomy'] ) ) : 30;

		if ( $loop->have_posts() ) {
			while ( $loop->have_posts() ) {
				$loop->the_post();
				$var                 = array();
				$post_id             = get_the_ID();
				$user_id             = get_the_author_meta( 'ID' );
				$content_data        = get_the_content();
				$var['ID']           = $post_id;
				$var['title']        = get_the_title();
				$var['permalink']    = get_permalink();
				$var['seo_meta']     = ultimate_post()->get_excerpt( $post_id, 1 );
				$var['excerpt']      = wp_strip_all_tags( get_the_excerpt() );
				$var['excerpt_full'] = wp_strip_all_tags( get_the_excerpt() );
				$var['time']         = (int) get_the_date( 'U' ) * 1000;
				$var['timeModified'] = (int) get_the_modified_date( 'U' ) * 1000;
				$var['post_time']    = human_time_diff( get_the_time( 'U' ), current_time( 'U' ) );
				$var['view']         = get_post_meta( get_the_ID(), '_ultp_post_views_count', true );
				$var['comments']     = get_comments_number();
				$var['author_link']  = get_author_posts_url( $user_id );
				$var['avatar_url']   = get_avatar_url( $user_id );
				$var['display_name'] = get_the_author_meta( 'display_name' );
				$var['reading_time'] = ceil( strlen( $content_data ) / 1200 );
				$var['acf']          = null;

				// Field values go only to users who can edit this post, and only name, label, type and value are sent.
				if ( function_exists( 'get_field_objects' ) && current_user_can( 'edit_post', $post_id ) ) {
					$var['acf'] = $this->editor_acf_fields( get_field_objects() );
				}

				$post_video = get_post_meta( $post_id, '_ultp_builder_feature_video', true );
				// Video.
				if ( $post_video ) {
					$var['has_video'] = ultimate_post()->get_youtube_id( $post_video );
				}
				// image.
				$image_sizes = ultimate_post()->get_image_size();
				$image_src   = array();
				if ( has_post_thumbnail() ) {
					$thumb_id = get_post_thumbnail_id( $post_id );
					foreach ( $image_sizes as $key => $value ) {
						$image = wp_get_attachment_image_src( $thumb_id, $key, false );
						if ( $image && is_array( $image ) ) {
							$image_src[ $key ] = $image[0];
						}
					}
					$var['image'] = $image_src;
				} elseif ( isset( $prams['fallbackImg']['id'] ) ) {
					$fallback_id = absint( $prams['fallbackImg']['id'] );
					foreach ( $image_sizes as $key => $value ) {
						$image             = wp_get_attachment_image_src( $fallback_id, $key, false );
						$image_src[ $key ] = $image ? $image[0] : null;
					}
					$var['image']       = $image_src;
					$var['is_fallback'] = true;
				}

				// tag.
				$tag_taxonomy = isset( $prams['tag'] ) && is_string( $prams['tag'] ) ? sanitize_text_field( $prams['tag'] ) : 'post_tag';
				$tag          = taxonomy_exists( $tag_taxonomy ) ? get_the_terms( $post_id, $tag_taxonomy ) : false;
				if ( ! empty( $tag ) ) {
					$v = array();
					foreach ( $tag as $k => $val ) {
						if ( $k >= $max_tax ) {
							break; }
						$v[] = array(
							'slug' => $val->slug,
							'name' => $val->name,
							'url'  => get_term_link( $val->term_id ),
						);
					}
					$var['tag'] = $v;
				}

				// Taxonomy. An add-on may take the terms from another taxonomy (same filter as the front end).
				$cat_taxonomy = apply_filters( 'ultp_post_category_taxonomy', 'category', $prams );
				$cat_taxonomy = is_string( $cat_taxonomy ) && '' !== $cat_taxonomy ? $cat_taxonomy : 'category';
				$cat          = taxonomy_exists( $cat_taxonomy ) ? get_the_terms( $post_id, $cat_taxonomy ) : false;

				if ( ! empty( $cat ) ) {
					$v = array();
					foreach ( $cat as $k => $val ) {
						if ( $k >= $max_tax ) {
							break; }
						$v[] = array(
							'slug'  => $val->slug,
							'name'  => $val->name,
							'url'   => get_term_link( $val->term_id ),
							'color' => ultimate_post()->get_term_color( $val ),
						);
					}
					$var['category'] = $v;
				}
				$data[] = $var;
			}
			wp_reset_postdata();
		}
		return rest_ensure_response( $data );
	}


	/**
	 * Keep the parts of ACF field objects that the editor preview uses: name, label, type and value.
	 *
	 * Field settings (choices, conditional logic, return formats and similar) are not sent.
	 *
	 * @param array|false $fields Result of get_field_objects().
	 * @return array|false The reduced field list, or the input when it is not an array.
	 */
	private function editor_acf_fields( $fields ) {
		if ( ! is_array( $fields ) ) {
			return $fields;
		}
		$reduced = array();
		foreach ( $fields as $key => $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}
			$reduced[ $key ] = array(
				'name'  => isset( $field['name'] ) ? $field['name'] : $key,
				'label' => isset( $field['label'] ) ? $field['label'] : '',
				'type'  => isset( $field['type'] ) ? $field['type'] : '',
				'value' => $this->editor_acf_value( $field['value'] ?? null ),
			);
		}
		return $reduced;
	}

	/**
	 * Preserve field formats without exposing account credentials through nested values.
	 *
	 * @param mixed $value Provider value.
	 * @param int   $depth Recursion depth, including objects supplied by field integrations.
	 * @return mixed JSON-safe field value.
	 */
	private function editor_acf_value( $value, $depth = 0 ) {
		if ( $depth > 32 ) {
			return null;
		}
		if ( $value instanceof \WP_User ) {
			// Keep the existing ID/data shape and explicitly selected display/email fields.
			$value = (object) array(
				'ID'   => $value->ID,
				'data' => (object) array_intersect_key(
					(array) $value->data,
					array_flip( array( 'ID', 'user_login', 'user_nicename', 'user_email', 'user_url', 'user_registered', 'display_name' ) )
				),
			);
		}
		if ( is_array( $value ) || is_object( $value ) ) {
			$clean = array();
			foreach ( is_object( $value ) ? get_object_vars( $value ) : $value as $key => $item ) {
				if ( in_array( strtolower( (string) $key ), array( 'user_pass', 'user_activation_key', 'session_tokens' ), true ) ) {
					continue;
				}
				$clean[ $key ] = $this->editor_acf_value( $item, $depth + 1 );
			}
			return is_object( $value ) ? (object) $clean : $clean;
		}
		return is_scalar( $value ) || null === $value ? $value : null;
	}

	/**
	 * Keep the post types of an editor preview query to those the current user may see.
	 *
	 * A post type is kept when it is registered and either viewable on the front end or
	 * editable by the current user. 'any' (alone or inside a list, as WP_Query reads it) is
	 * expanded to the types WP_Query would search, then filtered the same way.
	 *
	 * @param string|array $post_type Post type, or list of post types, from the query arguments.
	 * @return string|array Allowed post type(s); empty array when none is left.
	 */
	private function restrict_preview_post_types( $post_type ) {
		$is_any = 'any' === $post_type || ( is_array( $post_type ) && in_array( 'any', $post_type, true ) );
		$types  = $is_any ? get_post_types( array( 'exclude_from_search' => false ) ) : (array) $post_type;
		$allow  = array();
		foreach ( $types as $type ) {
			if ( ! is_string( $type ) || ! post_type_exists( $type ) ) {
				continue;
			}
			$object = get_post_type_object( $type );
			if ( is_post_type_viewable( $object ) || current_user_can( $object->cap->edit_posts ) ) {
				$allow[] = $type;
			}
		}
		if ( ! $is_any && is_string( $post_type ) && 1 === count( $allow ) ) {
			return $allow[0];
		}
		return $allow;
	}

	/**
	 * STaxonomy Data Response of REST API
	 *
	 * @since v.1.0.0
	 * @param ARRAY | $prams (ARRAY).
	 * @return ARRAY | Response Taxonomy List as Array
	 */
	public function ultp_route_common_data( $prams ) {
		$nonce = $prams->get_param( 'wpnonce' );
		$nonce = is_string( $nonce ) ? sanitize_text_field( $nonce ) : '';
		if ( ! wp_verify_nonce( $nonce, 'ultp-admin-nonce' ) ) {
			return rest_ensure_response( array() );
		}
		// Editor data (taxonomies, image sizes, post types): same capability as the route's permission_callback.
		if ( ! current_user_can( 'edit_posts' ) ) {
			return rest_ensure_response( array() );
		}

		$all_post_type = ultimate_post()->get_post_type();
		$data          = array();
		foreach ( $all_post_type as $post_type_slug => $post_type ) {
			$data_term  = array();
			$taxonomies = ultimate_post()->allowed_picker_taxonomies( get_object_taxonomies( $post_type_slug ) );
			foreach ( $taxonomies as $key => $taxonomy_slug ) {
				$taxonomy_value = get_terms(
					array(
						'taxonomy'   => $taxonomy_slug,
						'hide_empty' => false,
					)
				);
				if ( ! is_wp_error( $taxonomy_value ) ) {
					$data_tax = array();
					foreach ( $taxonomy_value as $k => $taxonomy ) {
						$data_tax[ urldecode_deep( $taxonomy->slug ) ] = $taxonomy->name;
					}
					if ( count( $data_tax ) > 0 ) {
						$data_term[ $taxonomy_slug ] = $data_tax;
					}
				}
			}
			$data[ $post_type_slug ] = $data_term;
		}
		// Global Customizer.
		$global = GlobalStyles::without_legacy_css( get_option( 'postx_global', array() ) );
		// Image Size.
		$image_sizes = ultimate_post()->get_image_size();

		return rest_ensure_response(
			array(
				'taxonomy' => $data,
				'global'   => $global,
				'image'    => wp_json_encode( $image_sizes ),
				'posttype' => wp_json_encode( $all_post_type ),
			)
		);
	}

	/**
	 * Specific Taxonomy Data Response of REST API
	 *
	 * @since v.1.0.0
	 * @param ARRAY | $prams .
	 * @return ARRAY | Response Taxonomy List as Array
	 */
	public function ultp_route_taxonomy_info_data( $prams ) {
		$valid = Request_Validation::validate( $prams->get_params(), 'ultp_route_taxonomy_info_data' );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}
		$prams = $prams->get_params();
		$nonce = isset( $prams['wpnonce'] ) ? sanitize_text_field( wp_unslash( $prams['wpnonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'ultp-admin-nonce' ) ) {
			return rest_ensure_response( array() );
		}
		// Same capability as the route's permission_callback.
		if ( ! current_user_can( 'edit_others_posts' ) ) {
			return rest_ensure_response( array() );
		}
		$tax_value       = isset( $prams['taxValue'] ) ? ultimate_post()->ultp_rest_sanitize_params( $prams['taxValue'] ) : '';
		$query_number    = isset( $prams['queryNumber'] ) ? ultimate_post()->ultp_rest_sanitize_params( $prams['queryNumber'] ) : '';
		$tax_type        = isset( $prams['taxType'] ) ? ultimate_post()->ultp_rest_sanitize_params( $prams['taxType'] ) : '';
		$tax_slug        = isset( $prams['taxSlug'] ) ? ultimate_post()->ultp_rest_sanitize_params( $prams['taxSlug'] ) : '';
		$archive_builder = isset( $prams['archiveBuilder'] ) ? ultimate_post()->ultp_rest_sanitize_params( $prams['archiveBuilder'] ) : '';

		// taxValue is the block's JSON list of term slugs.
		$tax_slugs = array();
		if ( is_array( $tax_value ) ) {
			$tax_slugs = $tax_value;
		} elseif ( is_string( $tax_value ) && '' !== $tax_value ) {
			$decoded = json_decode( $tax_value );
			if ( JSON_ERROR_NONE === json_last_error() && is_array( $decoded ) ) {
				$tax_slugs = ultimate_post()->ultp_rest_sanitize_params( $decoded );
			}
		}

		if ( empty( ultimate_post()->allowed_picker_taxonomies( $tax_slug ) ) ) {
			return rest_ensure_response( array() );
		}

		return rest_ensure_response( ultimate_post()->get_category_data( $tax_slugs, $query_number, $tax_type, $tax_slug, $archive_builder ) );
	}

	/**
	 * Who may call the Search block's live results route.
	 *
	 * The route is public on purpose: the Search block shows live results to visitors, and every
	 * result is a published post. What can be checked is checked:
	 * - a logged-in user is already authenticated (WordPress checks the REST nonce for a cookie login);
	 * - a visitor must send the front-end nonce 'ultp-public-nonce', the same check as the blocks'
	 *   other front-end requests (see Blocks::ultp_get_nonce_callback()).
	 *
	 * @since v.5.1.4
	 * @param \WP_REST_Request $request The REST request.
	 * @return true|\WP_Error
	 */
	public function search_permission_check( $request ) {
		if ( is_user_logged_in() ) {
			return true;
		}
		$nonce = $request->get_param( 'wpnonce' );
		if ( is_string( $nonce ) && wp_verify_nonce( $nonce, 'ultp-public-nonce' ) ) {
			return true;
		}
		// Same code as the AJAX handlers, so the script knows to fetch a fresh nonce and retry.
		return new \WP_Error(
			'ultp_invalid_nonce',
			__( 'Security token expired. Please try again.', 'ultimate-post' ),
			array( 'status' => 403 )
		);
	}

	/**
	 * Search Block Data Showing
	 *
	 * @since v.2.9.9
	 * @param STRING  | $server .
	 * @return ARRAY | Inserted Post Url
	 */
	public function ultp_search_result( $server ) {
		$valid = Request_Validation::validate( $server->get_params(), 'ultp_search_result' );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}
		$post        = $server->get_params();
		$searchText  = isset( $post['searchText'] ) ? ultimate_post()->ultp_rest_sanitize_params( $post['searchText'] ) : '';
		$paged       = isset( $post['paged'] ) ? ultimate_post()->ultp_rest_sanitize_params( $post['paged'] ) : '';
		$postPerPage = isset( $post['postPerPage'] ) ? ultimate_post()->ultp_rest_sanitize_params( $post['postPerPage'] ) : '';

		// Public route: cap posts_per_page so -1 can never mean "unlimited".
		// 0 survives the cap, and WP_Query resolves it to the posts_per_page option.
		$postPerPage = min( absint( $postPerPage ), self::SEARCH_POSTS_PER_PAGE_MAX );
		// is_scalar first: searchText arrives as an array if the caller sends one, and
		// casting that to string is an "Array to string conversion" warning.
		$searchText = is_scalar( $searchText ) ? mb_substr( (string) $searchText, 0, self::SEARCH_TEXT_MAX_LENGTH ) : '';

		// Both callers search only from 3 characters. A shorter term (an empty one matches every
		// post) is not a search, so it can't be used to page through the whole site.
		if ( mb_strlen( $searchText ) < 3 ) {
			return array(
				'post_data'  => '',
				'post_count' => 0,
			);
		}

		$query_args = array(
			's'              => $searchText,
			'paged'          => $paged,
			'orderby'        => 'relevance',
			'posts_per_page' => $postPerPage,
			// Published posts only, also for a logged-in user who could read private ones.
			'post_status'    => 'publish',
		);
		// An add-on may narrow the search from the request parameters. The paging, ordering,
		// search text and post status set above are kept whatever it returns.
		$filtered_args = apply_filters( 'ultp_search_result_query_args', $query_args, $post );
		if ( is_array( $filtered_args ) ) {
			$query_args = array_merge( $filtered_args, $query_args );
		}

		// Public post types only. With no post_type, WP_Query searches every type not excluded from
		// search, which can include one registered as not public. An add-on may narrow the types
		// (Pro's "Exclude Post Type"), but not widen them past the public ones.
		if ( empty( $query_args['post_type'] ) || 'any' === $query_args['post_type'] ) {
			$query_args['post_type'] = get_post_types(
				array(
					'public'              => true,
					'exclude_from_search' => false,
				),
				'names'
			);
		} else {
			$query_args['post_type'] = array_intersect( (array) $query_args['post_type'], get_post_types( array( 'public' => true ), 'names' ) );
		}
		// An empty list would make WP_Query fall back to searching every type.
		if ( empty( $query_args['post_type'] ) ) {
			return array(
				'post_data'  => '',
				'post_count' => 0,
			);
		}
		$query_args['post_type'] = array_values( $query_args['post_type'] );

		$output       = '';
		$query_result = new \WP_Query( $query_args );

		/**
		 * Resolved once here rather than guarded at each use, since the request may
		 * omit any of them. The loose == is deliberate: the two callers disagree on
		 * type -- the editor sends real booleans, the frontend sends 1/0 parsed out
		 * of data attributes -- and both must keep working.
		 */
		$show_image    = isset( $post['image'] ) && $post['image'] == 1;
		$show_category = isset( $post['category'] ) && $post['category'] == 1;
		$show_author   = isset( $post['author'] ) && $post['author'] == 1;
		$show_date     = isset( $post['date'] ) && $post['date'] == 1;
		$show_excerpt  = isset( $post['excerpt'] ) && $post['excerpt'] == 1;
		$excerpt_limit = isset( $post['excerptLimit'] )
							? min( absint( $post['excerptLimit'] ), self::SEARCH_EXCERPT_WORDS_MAX )
							: 55;

		if ( $query_result->have_posts() ) {
			while ( $query_result->have_posts() ) {
				$query_result->the_post();
				$post_id = get_the_ID();
				// Last check per post: published, and of a type visitors can view.
				if ( ! is_post_publicly_viewable( $post_id ) ) {
					continue;
				}
				$title = get_the_title();

				$output .= '<div class="ultp-search-result__item">';

				if ( $show_image && has_post_thumbnail() ) {
					// has_post_thumbnail() only proves the _thumbnail_id meta exists.
					// If the attachment behind it was deleted, src() returns false.
					$thumb = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), 'thumbnail', false );
					if ( ! empty( $thumb[0] ) ) {
						$output .= '<img class="ultp-searchresult-image" src="' . esc_url( $thumb[0] ) . '" alt="' . esc_attr( $title ) . '"/>';
					}
				}

				$output .= '<div class="ultp-searchresult-content">';
				$output .= '<div class="ultp-rescontent-meta">';

				// Category.
				$post_cat = get_the_terms( $post_id, 'category' );
				if ( $show_category && $post_cat && ! is_wp_error( $post_cat ) ) {
					$output .= '<div class="ultp-searchresult-category">';
					foreach ( $post_cat as $cat ) {
						$term_link = get_term_link( $cat->term_id );
						if ( is_wp_error( $term_link ) ) {
							continue;
						}
						$output .= '<a href="' . esc_url( $term_link ) . '">' . esc_html( $cat->name ) . '</a>';
					}
					$output .= '</div>';
				}

				// Author.
				// Only the author of this published post: the name its author archive already shows.
				$user_id = $show_author ? (int) get_the_author_meta( 'ID' ) : 0;
				if ( $user_id ) {
					$output .= '<a href="' . esc_url( get_author_posts_url( $user_id ) ) . '" class="ultp-searchresult-author">' . esc_html( get_the_author_meta( 'display_name' ) ) . '</a>';
				}

				// Date.
				if ( $show_date ) {
					$output .= '<div class="ultp-searchresult-publishdate">' . esc_html( get_the_date( 'F j, Y' ) ) . '</div>';
				}

				$output .= '</div>';
				$output .= '<a href="' . esc_url( get_permalink() ) . '" class="ultp-searchresult-title">' . esc_html( $title ) . '</a>';

				if ( $show_excerpt ) {
					// wp_trim_words() strips tags, so what comes back is plain text.
					$output .= '<div class="ultp-searchresult-excerpt">' . esc_html( wp_trim_words( get_the_excerpt(), $excerpt_limit ) ) . '</div>';
				}

				$output .= '</div>';
				$output .= '</div>';
			}
			// the_post() overwrote the global $post; hand it back.
			wp_reset_postdata();
		}

		return array(
			'post_data'  => $output,
			'post_count' => $query_result->found_posts,
		);
	}

	/**
	 * PostX Site Dark Logo Init
	 *
	 * @since v.3.1.9
	 * @param ARRAY | $server .
	 * @return BOOOLEAN | Inserted Post Url
	 */
	public function init_site_dark_logo_callback( $server ) {
		$valid = Request_Validation::validate( $server->get_params(), 'init_site_dark_logo_callback' );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}
		$logo_data = $server->get_params();
		$success   = true;
		$logo_url  = isset( $logo_data['logo']['url'] ) && is_string( $logo_data['logo']['url'] ) ? sanitize_url( $logo_data['logo']['url'] ) : '';
		if ( $logo_url ) {
			update_option( 'ultp_site_dark_logo', $logo_url );
		} else {
			$success = false;
		}
		return rest_ensure_response(
			array(
				'success' => $success,
			)
		);
	}


	/**
	 * Getting Image Size
	 *
	 * @since v.4.0.1
	 * @param ARRAY | $server (number) .
	 * @return ARRAY | Image Size List as Array
	 */
	public function get_custom_image_size( $server ) {
		$valid = Request_Validation::validate( $server->get_params(), 'get_custom_image_size' );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}
		$id          = absint( $server->get_param( 'id' ) );
		$image_src   = array();
		$image_sizes = ultimate_post()->get_image_size();
		foreach ( $image_sizes as $key => $value ) {
			$image             = wp_get_attachment_image_src( $id, $key, false );
			$image_src[ $key ] = $image ? $image[0] : null;
		}
		return rest_ensure_response(
			array(
				'success' => true,
				'size'    => $image_src,
				'id'      => $id,
			)
		);
	}
}
