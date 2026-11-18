<?php

/**
 * Importer System
 *
 * @package ULTP\Importer
 * @since 4.0.0
 */

namespace ULTP;

defined( 'ABSPATH' ) || exit;

/**
 * Importer class.
 *
 * Handles starter site import, plugin installation, and REST API endpoints for Ultimate Post.
 *
 * @package ULTP\Importer
 * @since 4.0.0
 */
class Importer {
	/**
	 * Setup class.
	 *
	 * @since 4.0.0
	 * @return NULL No return value.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'get_starter_rest_endpoint_callback' ) );
	}

	/**
	 * Register REST API endpoints for starter import and related actions.
	 *
	 * @since 4.0.0
	 * @return void No return value.
	 */
	public function get_starter_rest_endpoint_callback() {
		register_rest_route(
			'ultp/v3',
			'/single_page_import/',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'single_page_import' ),
					'permission_callback' => function () {
						return current_user_can( 'edit_others_posts' );
					},
					'args'                => Request_Validation::args( 'single_page_import' ),
				),
			)
		);
		register_rest_route(
			'ultp/v3',
			'/deletepost_getnewsletters/',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'deletepost_getnewsletters' ),
					'permission_callback' => function () {
						return current_user_can( 'manage_options' );
					},
					'args'                => Request_Validation::args( 'deletepost_getnewsletters' ),
				),
			)
		);
		register_rest_route(
			'ultp/v3',
			'/starter_import_content/',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'starter_import_content_callback' ),
					'permission_callback' => function () {
						return current_user_can( 'manage_options' );
					},
					'args'                => Request_Validation::args( 'starter_import_content_callback' ),
				),
			)
		);
		register_rest_route(
			'ultp/v3',
			'/starter_dummy_post/',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'starter_dummy_post_callback' ),
					'permission_callback' => function () {
						return current_user_can( 'manage_options' );
					},
					'args'                => Request_Validation::args( 'starter_dummy_post_callback' ),
				),
			)
		);
	}

	/**
	 * Starter dummy post callback.
	 *
	 * @since 4.0.0
	 *
	 * @param \WP_REST_Request $server The REST request object.
	 */
	function starter_dummy_post_callback( $server ) {
		$valid = Request_Validation::validate( $server->get_params(), 'starter_dummy_post_callback' );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}
		$post         = $server->get_params();
		$api_endpoint = isset( $post['api_endpoint'] ) ? sanitize_text_field( $post['api_endpoint'] ) : '';
		$import_dummy = isset( $post['importDummy'] ) ? sanitize_text_field( $post['importDummy'] ) : '';

		// SSRF protection: Validate API endpoint
		if ( ! $this->is_allowed_api_endpoint( $api_endpoint ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'Invalid API endpoint. Only trusted domains are allowed.', 'ultimate-post' ),
				)
			);
		}

		$response = wp_safe_remote_post(
			$api_endpoint . '/wp-json/importer/site_all_posts',
			array(
				'method'  => 'POST',
				'timeout' => 120,
				'body'    => ultimate_post()->get_template_request_body(
					array(
						'type'     => 'site_posts',
						'ultp_ver' => ULTP_VER,
					),
					'starter_posts'
				),
			)
		);
		if ( is_wp_error( $response ) ) {
			return rest_ensure_response(
				array(
					'success'       => false,
					'response_data' => $response,
				)
			);
		}
		$response_data = json_decode( $response['body'] );
		// json_decode() returns null for a non-JSON body, so check the shape before reading it.
		if ( JSON_ERROR_NONE !== json_last_error() || ! is_object( $response_data ) || empty( $response_data->success ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'returns' => $response_data,
				)
			);
		}

		// Dummy Post and Taxonomy creation.
		$importable_posts = isset( $response_data->posts ) ? $response_data->posts : array();
		$created_posts    = $import_dummy != 'no' ? $this->starter_pack_dummy_post_creation( $importable_posts ) : array();
		if ( is_wp_error( $created_posts ) ) {
			return $created_posts;
		}
		return rest_ensure_response(
			array(
				'success'       => true,
				'created_posts' => $created_posts,
			)
		);
	}


	/**
	 * Import the starter site's pages, templates, menus and plugin content (REST: starter_import_content).
	 *
	 * Order of operations:
	 * 1. Plan: fetch and validate the complete payload from the starter site. A failure here returns
	 *    before anything on this site changes. validate_only stops after this step.
	 * 2. Stage: create the new posts, media, menus and nav items. Builder conditions, the front page,
	 *    logos and same-titled menus are not applied yet, so the live site keeps its current templates.
	 * 3. Switch: draft the builder templates that were published before this request, then apply the
	 *    new conditions, options and menu names. This is the only step that changes what visitors see.
	 * 4. Delete previous: only when deletePrevious is "yes", and only the items listed in previous_items
	 *    (read before this request started). Runs after the switch and never touches new items.
	 *
	 * A failure in steps 2 or 3 rolls back everything the request created or changed. The same rollback
	 * runs on shutdown if the request stops early (for example a fatal error).
	 *
	 * @since 4.0.0
	 *
	 * @param \WP_REST_Request $server The REST request object.
	 * @return \WP_REST_Response
	 */
	public function starter_import_content_callback( $server ) {
		$valid = Request_Validation::validate( $server->get_params(), 'starter_import_content_callback' );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}
		$post             = $server->get_params();
		$api_endpoint     = isset( $post['api_endpoint'] ) ? sanitize_text_field( $post['api_endpoint'] ) : '';
		$installed_plugin = isset( $post['installPlugin'] ) ? sanitize_text_field( $post['installPlugin'] ) : '';
		$validate_only    = $this->starter_request_flag( $post, 'validate_only' );
		$delete_previous  = $this->starter_request_flag( $post, 'deletePrevious' );

		if ( ! $this->is_allowed_api_endpoint( $api_endpoint ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'Invalid API endpoint. Only trusted domains are allowed.', 'ultimate-post' ),
				)
			);
		}

		// 1. Plan. Nothing on this site has changed yet.
		$plan = $this->starter_fetch_import_plan( $api_endpoint );
		if ( ! $plan['success'] ) {
			return rest_ensure_response(
				array(
					'success'       => false,
					'message'       => $plan['message'],
					'response_data' => $plan['response_data'],
				)
			);
		}
		if ( $validate_only ) {
			return rest_ensure_response(
				array(
					'success'   => true,
					'validated' => true,
				)
			);
		}
		$response_data = $plan['data'];

		// Page titles to skip. Stays null (import nothing) when the value is missing or invalid.
		$excludepages     = null;
		$excludepages_raw = $server->get_param( 'excludepages' );
		if ( is_string( $excludepages_raw ) && '' !== $excludepages_raw ) {
			// REST params arrive unslashed; stripslashes() here would break titles with " or \.
			$excludepages_raw = json_decode( $excludepages_raw, true );
			if ( JSON_ERROR_NONE !== json_last_error() ) {
				$excludepages_raw = null;
			}
		}
		if ( is_array( $excludepages_raw ) ) {
			$excludepages = array_values( array_map( 'sanitize_text_field', array_filter( $excludepages_raw, 'is_string' ) ) );
		}

		$state = array(
			'ledger'   => array(
				'posts'       => array(), // Created post IDs. Removed on rollback.
				'attachments' => array(), // Created media IDs. Removed on rollback.
				'menus'       => array(), // Created menu term IDs. Removed on rollback.
				'renamed_menus' => array(), // Old menu IDs => original name/slug; their items stay intact until commit.
				'drafted'     => array(), // Builder ID => status before the switch.
				'options'     => array(), // Option name => array( 'existed' => bool, 'value' => mixed ).
				'finished'    => false,   // True once committed or rolled back.
			),
			'pending'  => array(
				'options'            => array(), // Option name => new value, applied in the switch.
				'builder_conditions' => array(), // Builder type => post ID => conditions, applied in the switch.
				'menu_swaps'         => array(), // New menus that replace a same-titled menu in the switch.
				'publish'            => array(), // Staged page and template IDs, published in the switch.
			),
			'warnings' => array(),
			'previous' => $delete_previous ? $this->starter_decode_previous_items( $server->get_param( 'previous_items' ) ) : array(
				'posts' => array(),
				'term'  => array(),
			),
		);

		// A fatal error or dropped connection skips the catch below. Undo the staged work on shutdown.
		register_shutdown_function(
			function () use ( &$state ) {
				if ( ! $state['ledger']['finished'] ) {
					$this->starter_import_rollback( $state );
				}
			}
		);

		$builders_before = $this->starter_published_builder_ids();

		try {
			// 2. Stage. Nothing below changes what visitors see.
			$this->starter_check_import_permissions( $response_data, $excludepages );
			$this->starter_stage_logos( $response_data, $state );
			$pages      = $this->starter_stage_pages( $response_data, $excludepages, $state );
			$menus      = $this->starter_stage_menus( $response_data, $pages['inserted_pages'], $state );
			$navigation = $this->starter_stage_navigation( $response_data, $pages['inserted_header_footer'], $state );
			$this->starter_stage_other_plugin_content( $response_data, $pages['inserted_pages'], $state );

			// 3. Switch. This is the commit point for the import.
			$this->starter_switch( $builders_before, $state );
			$state['ledger']['finished'] = true;
		} catch ( \Throwable $e ) {
			$rollback_warnings = $this->starter_import_rollback( $state );
			return rest_ensure_response(
				array(
					'success'     => false,
					'message'     => $e instanceof \RuntimeException ? $e->getMessage() : __( 'The import stopped unexpectedly. The changes made so far were rolled back.', 'ultimate-post' ),
					'rolled_back' => true,
					'warnings'    => array_merge( $state['warnings'], $rollback_warnings ),
				)
			);
		}

		$this->starter_cleanup_replaced_menus( $state );

		// 4. Delete previous starter items. Only after the new content is live.
		$deleted = array(
			'posts' => array(),
			'term'  => array(),
		);
		if ( $delete_previous ) {
			$removed = $this->starter_delete_previous_items( $state['previous'], $state['ledger'] );
			$deleted = array(
				'posts' => $removed['posts'],
				'term'  => $removed['term'],
			);
			$state['warnings'] = array_merge( $state['warnings'], $removed['failed'] );
		}

		foreach ( array_keys( $pages['inserted_meta'] ) as $post_id ) {
			$pages['inserted_meta'][ $post_id ] = get_post_meta( $post_id, '_ultp_css', true );
		}

		return rest_ensure_response(
			array(
				'success'                     => true,
				'inserted_pages'              => $pages['inserted_pages'],
				'inserted_pages_with_content' => $pages['inserted_pages_with_content'],
				'inserted_meta'               => $pages['inserted_meta'],
				'navCreated'                  => $navigation,
				'menuCreated'                 => $menus['menuCreated'],
				'menu_item_parent'            => $menus['menu_item_parent'],
				'inserted_header_footer'      => $pages['inserted_header_footer'],
				'wow_optin_status'            => $installed_plugin,
				'warnings'                    => $state['warnings'],
				'deleted_previous'            => $deleted,
			)
		);
	}

	/**
	 * Fetch the complete import plan from the starter site and check it.
	 *
	 * Makes no changes on this site.
	 *
	 *
	 * @param string $api_endpoint Allowed starter site URL.
	 * @return array{success: bool, message?: string, response_data?: mixed, data?: object}
	 */
	private function starter_fetch_import_plan( $api_endpoint ) {
		$response = wp_safe_remote_post(
			$api_endpoint . '/wp-json/importer/single',
			array(
				'method'  => 'POST',
				'timeout' => 120,
				'body'    => ultimate_post()->get_template_request_body(
					array(
						'ultp_ver' => ULTP_VER,
					),
					'starter_content'
				),
			)
		);
		if ( is_wp_error( $response ) ) {
			return $this->starter_plan_failure(
				/* translators: %s: the HTTP error message. */
				sprintf( __( 'The starter site could not be reached (%s). Nothing on this site was changed.', 'ultimate-post' ), $response->get_error_message() ),
				$response
			);
		}
		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			return $this->starter_plan_failure(
				/* translators: %d: the HTTP status code. */
				sprintf( __( 'The starter site answered with HTTP status %d. Nothing on this site was changed.', 'ultimate-post' ), $code ),
				null
			);
		}
		$data = json_decode( wp_remote_retrieve_body( $response ) );
		if ( JSON_ERROR_NONE !== json_last_error() || ! is_object( $data ) ) {
			return $this->starter_plan_failure( __( 'The starter site sent a response that could not be read. Nothing on this site was changed.', 'ultimate-post' ), null );
		}
		if ( empty( $data->success ) ) {
			return $this->starter_plan_failure( __( 'The starter site did not return an import. Nothing on this site was changed.', 'ultimate-post' ), $data );
		}
		$problem = $this->starter_plan_problem( $data );
		if ( '' !== $problem ) {
			return $this->starter_plan_failure( $problem, $data );
		}
		return array(
			'success' => true,
			'data'    => $data,
		);
	}

	/**
	 * Build a failed plan result.
	 *
	 * @param string $message       Message for the user.
	 * @param mixed  $response_data Raw response, kept for compatibility with earlier responses.
	 * @return array
	 */
	private function starter_plan_failure( $message, $response_data ) {
		return array(
			'success'       => false,
			'message'       => $message,
			'response_data' => $response_data,
		);
	}

	/**
	 * Check the parts of the starter payload the import reads.
	 *
	 * @param object $data Decoded payload.
	 * @return string Empty when the payload is usable, otherwise a message.
	 */
	private function starter_plan_problem( $data ) {
		$problem = __( 'The starter site sent an incomplete import. Nothing on this site was changed.', 'ultimate-post' );

		if ( ! isset( $data->content ) || ! is_array( $data->content ) ) {
			return $problem;
		}
		$needs_conditions = false;
		foreach ( $data->content as $item ) {
			if (
				! is_object( $item )
				|| ! isset( $item->name, $item->type )
				|| ! is_string( $item->name )
				|| ! is_string( $item->type )
				|| ! property_exists( $item, 'content' )
				|| ( null !== $item->content && ! is_string( $item->content ) )
				|| ( isset( $item->_ultp_css ) && ! is_string( $item->_ultp_css ) )
			) {
				return $problem;
			}
			if ( ! empty( $item->ultp_builder_type ) ) {
				if ( ! is_string( $item->ultp_builder_type ) ) {
					return $problem;
				}
				$needs_conditions = true;
			}
		}
		if ( $needs_conditions && ( ! isset( $data->conditions ) || ! is_object( $data->conditions ) ) ) {
			return $problem;
		}
		if ( isset( $data->conditions ) ) {
			if ( ! is_object( $data->conditions ) ) {
				return $problem;
			}
			foreach ( $data->conditions as $templates ) {
				if ( ! is_object( $templates ) && ! is_array( $templates ) ) {
					return $problem;
				}
				foreach ( $templates as $conditions ) {
					// Legacy service payloads may contain one condition as a string.
					$conditions = is_string( $conditions ) ? array( $conditions ) : $conditions;
					if ( ! is_array( $conditions ) ) {
						return $problem;
					}
					foreach ( $conditions as $condition ) {
						if ( ! is_string( $condition ) || ! preg_match( '~^(include|exclude)/~', $condition ) ) {
							return $problem;
						}
					}
				}
			}
		}

		if ( isset( $data->menu ) ) {
			if ( ! is_array( $data->menu ) ) {
				return $problem;
			}
			foreach ( $data->menu as $menu ) {
				if ( ! is_object( $menu ) || ! isset( $menu->title ) || ! is_string( $menu->title ) ) {
					return $problem;
				}
			}
		}
		if ( isset( $data->navigation ) && ! is_array( $data->navigation ) ) {
			return $problem;
		}
		if ( isset( $data->other_plugin_content ) && ! is_object( $data->other_plugin_content ) ) {
			return $problem;
		}
		return '';
	}

	/**
	 * Check selected content before staging; reused/unused resources are checked only if created.
	 *
	 * @param object     $data     Validated import plan.
	 * @param array|null $excluded Page titles to skip, or null to skip all pages.
	 * @return void
	 */
	private function starter_check_import_permissions( $data, $excluded ) {
		if ( is_array( $excluded ) ) {
			foreach ( $data->content as $item ) {
				if ( ! in_array( $item->name, $excluded, true ) && ! $this->starter_skip_form_type( $item->type ) ) {
					$this->starter_require_post_permission( $item->type );
				}
			}
		}
		if ( ! empty( $data->other_plugin_content->contact_form7 ) && $this->starter_can_import_form( 'wpcf7_contact_form' ) ) {
			$this->starter_require_post_permission( 'wpcf7_contact_form' );
		}
		if ( ! empty( $data->menu ) && ! current_user_can( 'edit_theme_options' ) ) {
			throw new \RuntimeException( __( 'You are not allowed to import navigation menus.', 'ultimate-post' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- returned by the import rollback, not HTML.
		}
	}

	/**
	 * The import publishes newly created content, independently of its manage_options route gate.
	 *
	 * @param string $post_type Target post type.
	 * @return void
	 */
	private function starter_require_post_permission( $post_type ) {
		if ( ! Functions::can_create_post_type( $post_type, true ) ) {
			throw new \RuntimeException( __( 'You are not allowed to create and publish this imported content type.', 'ultimate-post' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- returned by the import rollback, not HTML.
		}
	}

	/**
	 * Contact Form 7 and MailChimp for WP form records are imported only while their plugin is active.
	 *
	 * @param string $post_type Form post type.
	 * @return bool
	 */
	private function starter_form_plugin_active( $post_type ) {
		return (bool) get_post_type_object( $post_type );
	}

	/**
	 * Whether an optional form plugin is active and the current user may create and publish its records.
	 *
	 * @param mixed $post_type Post type from the starter payload.
	 * @return bool
	 */
	private function starter_can_import_form( $post_type ) {
		return $this->starter_form_plugin_active( $post_type ) && Functions::can_create_post_type( $post_type, true );
	}

	/**
	 * Whether a payload item is an optional form type that cannot be imported here. Such items are skipped,
	 * and the rest of the import continues.
	 *
	 * @param mixed $post_type Post type from the starter payload.
	 * @return bool
	 */
	private function starter_skip_form_type( $post_type ) {
		return in_array( $post_type, array( 'wpcf7_contact_form', 'mc4wp-form' ), true ) && ! $this->starter_can_import_form( $post_type );
	}

	/**
	 * Stage the site logo and dark logo.
	 *
	 * A logo is taken only when the site has none, or when its current one is a starter-site image that
	 * "Delete previous" is going to remove. The option is changed in the switch.
	 *
	 * @param object $response_data Validated payload.
	 * @param array  $state         Import state, by reference.
	 */
	private function starter_stage_logos( $response_data, array &$state ) {
		$previous_posts = $state['previous']['posts'];

		$site_logo = get_option( 'site_logo', '' );
		$logo_free = empty( $site_logo ) || in_array( (int) $site_logo, $previous_posts, true );
		if ( $logo_free && ! empty( $response_data->site_logo ) && is_string( $response_data->site_logo ) ) {
			$logo_id = $this->upload_post_cat_media( $response_data->site_logo, '', 'Site Logo' );
			if ( $logo_id ) {
				$this->starter_track_created( $state, 'attachments', $logo_id );
				$state['pending']['options']['site_logo'] = $logo_id;
			} else {
				$state['warnings'][] = __( 'The site logo could not be downloaded from the starter site.', 'ultimate-post' );
			}
		}

		$dark_logo = (string) get_option( 'ultp_site_dark_logo', '' );
		$dark_free = '' === $dark_logo || $this->starter_is_previous_attachment_url( $dark_logo, $previous_posts );
		if ( $dark_free && ! empty( $response_data->dark_logo ) && is_string( $response_data->dark_logo ) ) {
			$dark_logo_id = $this->upload_post_cat_media( $response_data->dark_logo, '', 'Site Dark Logo' );
			if ( $dark_logo_id ) {
				$this->starter_track_created( $state, 'attachments', $dark_logo_id );
			}
			$dark_logo_url = $dark_logo_id ? wp_get_attachment_url( $dark_logo_id ) : '';
			if ( $dark_logo_url ) {
				$state['pending']['options']['ultp_site_dark_logo'] = sanitize_url( $dark_logo_url );
			} else {
				$state['warnings'][] = __( 'The dark logo could not be downloaded from the starter site.', 'ultimate-post' );
			}
		}
	}

	/**
	 * Stage pages, templates and their metadata.
	 *
	 * @param object     $response_data Validated payload.
	 * @param array|null $excludepages  Titles to skip, or null to import nothing.
	 * @param array      $state         Import state, by reference.
	 * @return array{inserted_pages: array, inserted_meta: array, inserted_header_footer: array}
	 */
	private function starter_stage_pages( $response_data, $excludepages, array &$state ) {
		$inserted_meta               = array();
		$inserted_pages              = array();
		$inserted_pages_with_content = array();
		$inserted_header_footer      = array();

		if ( is_array( $excludepages ) ) {
			foreach ( $response_data->content as $val ) {
				$title = $val->name;
				if ( in_array( $title, $excludepages, true ) ) {
					continue;
				}
				$post_type = $val->type;
				if ( $this->starter_skip_form_type( $post_type ) ) {
					continue;
				}
				$this->starter_require_post_permission( $post_type );
				// wp_insert_post() unslashes its data, so the backslash is added twice here. Without it the stored
				// block comment holds a bare " or --, the attribute JSON no longer parses, and the saved attributes are lost.
				$p_content = str_replace( array( 'u0022', 'u002d' ), array( '\u0022', '\u002d' ), (string) $val->content );
				$p_content = str_replace( array( 'u003c', 'u003e', 'currentPostId' ), array( '<', '>', 'current_PostId' ), $p_content );
				$post_id   = wp_insert_post(
					array(
						'post_title'   => $title,
						'post_type'    => $post_type,
						'post_status'  => 'draft', // Published in the switch, so visitors see nothing before it.
						'post_content' => $p_content,
					)
				);
				if ( ! $post_id || is_wp_error( $post_id ) ) {
					/* translators: %s: page or template title. */
					throw new \RuntimeException( sprintf( __( 'PostX could not create "%s" from the starter site.', 'ultimate-post' ), $title ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- caught internally and returned as JSON text, never printed as HTML.
				}
				$this->starter_track_created( $state, 'posts', $post_id );
				update_post_meta( $post_id, '_ultp_starter_pack_post', true );
				$inserted_pages[ $post_id ] = $title;
				// Debug aid: the content as the starter API sent it, and the content handed to wp_insert_post().
				$inserted_pages_with_content[] = array(
					'id'                 => (int) $post_id,
					'title'              => $title,
					'content_from_wpxpo' => (string) $val->content,
					'content_saving'     => $p_content,
				);
				$state['pending']['publish'][] = (int) $post_id;

				if ( isset( $val->ultp_template ) && $val->ultp_template === 'ultp_page_template' ) {
					update_post_meta( $post_id, '_wp_page_template', 'ultp_page_template' );
				}
				if ( isset( $val->home_page ) && $val->home_page == 'home_page' ) {
					// Applied in the switch, so the front page changes only after everything is created.
					$state['pending']['options']['show_on_front'] = 'page';
					$state['pending']['options']['page_on_front'] = $post_id;
					$inserted_header_footer['home_page']          = $post_id;
				}
				if ( isset( $val->ultp_builder_type ) && ( $val->ultp_builder_type == 'header' || $val->ultp_builder_type == 'footer' ) ) {
					$inserted_header_footer[] = $post_id;
				}
				if ( $post_type == 'ultp_builder' && ! empty( $val->ultp_builder_type ) ) {
					$builder_type = $val->ultp_builder_type;
					$builder_id   = isset( $val->id ) ? (string) $val->id : '';
					$conditions   = $response_data->conditions;
					$by_type      = isset( $conditions->$builder_type ) ? $conditions->$builder_type : null;
					$builder_cond = is_object( $by_type ) && isset( $by_type->$builder_id ) ? $by_type->$builder_id : null;

					// Conditions are applied in the switch, so the new template does nothing until then.
					$normalized = ultimate_post()->normalize_builder_conditions( array( $builder_type => array( $post_id => $builder_cond ) ) );
					$state['pending']['builder_conditions'][ $builder_type ][ $post_id ] = isset( $normalized[ $builder_type ][ $post_id ] ) ? $normalized[ $builder_type ][ $post_id ] : array();
					update_post_meta( $post_id, '_ultp_builder_type', $builder_type );
				}
				if ( isset( $val->meta ) && is_array( $val->meta ) ) {
					foreach ( $val->meta as $k => $v ) {
						update_post_meta( $post_id, $k, str_replace( array( 'u0022', 'u002d' ), array( '\u0022', '\u002d' ), $v ) );
					}
				}
				$css_saved = $this->save_post_block_css( $post_id );
				if ( empty( $css_saved['success'] ) ) { throw new \RuntimeException( (string) $css_saved['message'] ); }
				$inserted_meta[ $post_id ] = get_post_meta( $post_id, '_ultp_css', true );
			}
		}

		return array(
			'inserted_pages'              => $inserted_pages,
			'inserted_pages_with_content' => $inserted_pages_with_content,
			'inserted_meta'               => $inserted_meta,
			'inserted_header_footer'      => $inserted_header_footer,
		);
	}

	/**
	 * Stage navigation menus and the nav items inside them.
	 *
	 * An existing menu with the same title is not removed here. The new menu gets a temporary name, and
	 * the switch removes the old one and gives the new one its title.
	 *
	 * @param object $response_data          Validated payload.
	 * @param array  $inserted_pages         Post ID => title of pages created in this import.
	 * @param array  $state                  Import state, by reference.
	 * @return array{menuCreated: array, menu_item_parent: array}
	 */
	private function starter_stage_menus( $response_data, $inserted_pages, array &$state ) {
		$menu_created     = array();
		$menu_item_parent = array();
		$response_menu    = isset( $response_data->menu ) ? $response_data->menu : array();

		if ( ! empty( $response_menu ) && is_array( $response_menu ) ) {
			foreach ( $response_menu as $menu ) {
				$replace     = null;
				$create_name = $menu->title;
				$menu_exists = wp_get_nav_menu_object( $menu->title );
				if ( $menu_exists ) {
					// Creating a menu fails while one with this title exists, so the new one gets a temporary name.
					if ( ! current_user_can( 'delete_term', $menu_exists->term_id ) ) {
						continue;
					}
					$replace     = array(
						'term_id'  => (int) $menu_exists->term_id,
						'taxonomy' => $menu_exists->taxonomy,
						'slug'     => $menu_exists->slug,
						'name'     => $menu->title,
					);
					$create_name = $menu->title . ' ' . wp_generate_password( 8, false );
				}

				$menu_id = wp_create_nav_menu( $create_name );
				if ( is_wp_error( $menu_id ) || ! $menu_id ) {
					/* translators: %s: menu title. */
					$state['warnings'][] = sprintf( __( 'The menu "%s" could not be created.', 'ultimate-post' ), $menu->title );
					continue;
				}
				$this->starter_track_created( $state, 'menus', $menu_id );

				if ( isset( $menu->items ) && ! empty( $menu->items ) ) {
					$item = null;
					foreach ( $menu->items as $v ) {
						$inserted = array_search( $v->title, $inserted_pages, true );
						if ( $inserted || $v->type == 'custom' || $v->type == 'category' ) {
							if (
								( $v->type == 'category' && get_term_by( 'name', $v->title, 'category' ) )
								|| $v->type != 'category'
							) {
								$item                     = array(
									'menu-item-title'  => $v->title,
									'menu-item-status' => 'publish',
								);
								$item['menu-item-object'] = $v->type;
								$item['menu-item-type']   = $v->post_type;
								if ( isset( $v->menu_item_parent ) && $v->menu_item_parent ) {
									$parent_id                     = $this->find_parent_nav_item( wp_get_nav_menu_items( $menu_id ), $v->menu_item_parent );
									$menu_item_parent[ $v->title ] = $parent_id;
									$item['menu-item-parent-id']   = $parent_id;
								}
								if ( $v->type == 'custom' ) {
									$item['menu-item-url'] = $v->url ? $v->url : home_url( '/' );
								} elseif ( $v->type == 'category' ) {
									$fetched_term = get_term_by( 'name', $v->title, 'category' );
									if ( $fetched_term ) {
										$item['menu-item-object-id'] = $fetched_term->term_id;
									}
								} else {
									$item['menu-item-object-id'] = $inserted;
								}
								$item_saved = wp_update_nav_menu_item( $menu_id, 0, $item );
								if ( is_wp_error( $item_saved ) ) {
									/* translators: %s: menu title. */
									$state['warnings'][] = sprintf( __( 'An item in the menu "%s" could not be added.', 'ultimate-post' ), $menu->title );
								}
							}
						}
					}
					update_term_meta( $menu_id, '_ultp_starter_pack_term', 'postx_term' );
					$menu_created[ $menu_id ] = $item;
				}

				if ( $replace ) {
					$replace['new']                        = (int) $menu_id;
					$state['pending']['menu_swaps'][] = $replace;
				}
			}
		}

		return array(
			'menuCreated'      => $menu_created,
			'menu_item_parent' => $menu_item_parent,
		);
	}

	/**
	 * Stage navigation posts for header and footer builders.
	 *
	 * @param object $response_data          Validated payload.
	 * @param array  $inserted_header_footer Builder IDs created in this import.
	 * @param array  $state                  Import state, by reference.
	 * @return array Created navigation IDs.
	 */
	private function starter_stage_navigation( $response_data, $inserted_header_footer, array &$state ) {
		$nav_created = array();
		$navigation  = isset( $response_data->navigation ) ? $response_data->navigation : array();
		if ( empty( $inserted_header_footer ) || empty( $navigation ) || ! is_array( $navigation ) ) {
			return $nav_created;
		}

		foreach ( $inserted_header_footer as $builder_id ) {
			$builder_post = get_post( $builder_id );
			if ( ! $builder_post ) {
				continue;
			}
			$builder_post_content = $builder_post->post_content;
			foreach ( $navigation as $v ) {
				$site_navID = $v->id;
				if ( strpos( $builder_post_content, 'wp:navigation' ) > -1 && ( strpos( $builder_post_content, '{"ref":' . $site_navID . ',' ) > -1 || strpos( $builder_post_content, '{"ref":' . $site_navID . '}' ) > -1 ) ) {

					// Use the menu of the same name, if this site has one.
					$new_navID   = '';
					$menu_exists = wp_get_nav_menu_object( $v->post_title );
					// During staging the old menu still has this title; prefer its staged replacement.
					foreach ( $state['pending']['menu_swaps'] as $swap ) {
						if ( $swap['name'] === $v->post_title ) {
							$menu_exists = wp_get_nav_menu_object( (int) $swap['new'] );
							break;
						}
					}
					if ( $menu_exists ) {
						$menu_blocks = \WP_Classic_To_Block_Menu_Converter::convert( $menu_exists );
						$this->starter_require_post_permission( 'wp_navigation' );
						$new_navID   = wp_insert_post(
							array(
								'post_content' => $menu_blocks,
								'post_title'   => $menu_exists->name,
								'post_name'    => $menu_exists->slug,
								'post_status'  => 'publish',
								'post_type'    => 'wp_navigation',
							)
						);
						if ( ! $new_navID || is_wp_error( $new_navID ) ) {
							throw new \RuntimeException( __( 'PostX could not create a navigation for the header or footer.', 'ultimate-post' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- caught internally and returned as JSON text, never printed as HTML.
						}
						$this->starter_track_created( $state, 'posts', $new_navID );
						update_post_meta( $new_navID, '_ultp_starter_pack_post', true );
					}
					if ( strpos( $builder_post_content, '{"ref":' . $site_navID . ',' ) > -1 ) {
						$builder_post_content = str_replace( '{"ref":' . $site_navID . ',', '{"ref":' . $new_navID . ',', $builder_post_content );
					} elseif ( strpos( $builder_post_content, '{"ref":' . $site_navID . '}' ) ) {
						$builder_post_content = str_replace( '{"ref":' . $site_navID . '}', '{"ref":' . $new_navID . '}', $builder_post_content );
					}

					$updated = wp_update_post(
						array(
							'ID'           => $builder_id,
							'post_content' => str_replace( array( 'u0022', 'u002d' ), array( '\u0022', '\u002d' ), $builder_post_content ),
						),
						true
					);
					if ( is_wp_error( $updated ) || ! $updated ) {
						throw new \RuntimeException( __( 'PostX could not update a header or footer template with its navigation.', 'ultimate-post' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- caught internally and returned as JSON text, never printed as HTML.
					}
					$nav_created[] = $new_navID;
				}
			}
		}
		return $nav_created;
	}

	/**
	 * Stage contact forms and MailChimp forms.
	 *
	 * @param object $response_data  Validated payload.
	 * @param array  $inserted_pages Post ID => title of pages created in this import.
	 * @param array  $state          Import state, by reference.
	 */
	private function starter_stage_other_plugin_content( $response_data, $inserted_pages, array &$state ) {
		$other_plugin_content = isset( $response_data->other_plugin_content ) ? $response_data->other_plugin_content : new \stdClass();
		$contact_forms        = isset( $other_plugin_content->contact_form7 ) ? $other_plugin_content->contact_form7 : array();
		$mc4wp                = isset( $other_plugin_content->mc4wp ) ? $other_plugin_content->mc4wp : array();

		// contact form 7 post create.
		if ( is_array( $contact_forms ) && ! empty( $contact_forms ) && $this->starter_can_import_form( 'wpcf7_contact_form' ) ) {
			foreach ( $contact_forms as $post ) {
				$this->starter_require_post_permission( 'wpcf7_contact_form' );
				$post_id = wp_insert_post(
					array(
						'post_title'   => $post->post_title,
						'post_type'    => 'wpcf7_contact_form',
						'post_status'  => 'publish',
						'post_content' => str_replace( array( 'u002d', '<wordpress@postxkit.wpxpo.com>' ), array( '\u002d', '' ), $post->post_content ),
					)
				);
				if ( ! $post_id || is_wp_error( $post_id ) ) {
					throw new \RuntimeException( __( 'PostX could not create a contact form from the starter site.', 'ultimate-post' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- caught internally and returned as JSON text, never printed as HTML.
				}
				$this->starter_track_created( $state, 'posts', $post_id );
				update_post_meta( $post_id, '_form', $post->_form );
				update_post_meta( $post_id, '_hash', $post->_hash );
				update_post_meta( $post_id, '_ultp_starter_pack_post', true );
			}
		}

		// mailchimp post create.
		if ( ! empty( $inserted_pages ) && ! empty( $mc4wp ) && $this->starter_can_import_form( 'mc4wp-form' ) ) {
			foreach ( $inserted_pages as $id => $p_t ) {
				$current_post         = get_post( $id );
				$current_post_content = $current_post->post_content;
				foreach ( $mc4wp as $v ) {
					$parsed_args               = array(
						'post_type'      => 'mc4wp-form',
						'post_status'    => 'publish',
						'posts_per_page' => -1,
					);
					$mc4wp_forms_data          = new \WP_Query( $parsed_args );
					$mc4wp_forms_current_posts = $mc4wp_forms_data->posts;

					$site_ID = $v->id;
					if ( strpos( $current_post_content, '[mc4wp_form id=' . $site_ID . ']' ) > -1 ) {
						$new_ID = '';
						// check for same mailchimp post.
						if ( ! empty( $mc4wp_forms_current_posts ) ) {
							foreach ( $mc4wp_forms_current_posts as $val ) {
								if ( $val->post_title == $v->post_title ) {
									$new_ID = $val->ID;
								}
							}
						}
						if ( ! $new_ID ) {
							$this->starter_require_post_permission( 'mc4wp-form' );
							$new_ID = wp_insert_post(
								array(
									'post_title'   => $v->post_title,
									'post_type'    => 'mc4wp-form',
									'post_status'  => 'publish',
									'post_content' => $v->post_content,
								)
							);
							if ( ! $new_ID || is_wp_error( $new_ID ) ) {
								throw new \RuntimeException( __( 'PostX could not create a MailChimp form from the starter site.', 'ultimate-post' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- caught internally and returned as JSON text, never printed as HTML.
							}
							$this->starter_track_created( $state, 'posts', $new_ID );
							update_post_meta( $new_ID, '_ultp_starter_pack_post', true );
						}

						$current_post_content = str_replace( '[mc4wp_form id=' . $site_ID . ']', '[mc4wp_form id=' . $new_ID . ']', $current_post_content );
						$updated              = wp_update_post(
							array(
								'ID'           => $id,
								'post_content' => str_replace( array( 'u0022', 'u002d' ), array( '\u0022', '\u002d' ), $current_post_content ),
							),
							true
						);
						if ( is_wp_error( $updated ) || ! $updated ) {
							throw new \RuntimeException( __( 'PostX could not add a MailChimp form to an imported page.', 'ultimate-post' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- caught internally and returned as JSON text, never printed as HTML.
						}
					}
				}
			}
		}
	}

	/**
	 * Switch the live site over to the staged import.
	 *
	 * Records every value it overwrites first, so rollback can put it back.
	 *
	 * @param int[] $builders_before Builder templates published before this request.
	 * @param array $state           Import state, by reference.
	 * @throws \RuntimeException When a change cannot be made.
	 */
	private function starter_switch( $builders_before, array &$state ) {
		// Snapshot every option this step may change, before the first change.
		$switch_options = array( 'show_on_front', 'page_on_front', 'site_logo', 'ultp_site_dark_logo', 'ultp_builder_conditions' );
		foreach ( $switch_options as $name ) {
			if ( array_key_exists( $name, $state['ledger']['options'] ) ) {
				continue;
			}
			$missing = new \stdClass();
			$value   = get_option( $name, $missing );
			$existed = $missing !== $value;

			$state['ledger']['options'][ $name ] = array(
				'existed' => $existed,
				'value'   => $existed ? $value : null,
			);
		}

		// Draft the templates that were live before. Only those are switched off.
		foreach ( $builders_before as $builder_id ) {
			if ( ! $this->current_user_can_change_post( $builder_id, 'edit' ) ) {
				continue;
			}
			$previous_status                           = get_post_status( $builder_id );
			$state['ledger']['drafted'][ $builder_id ] = $previous_status;
			$result                                    = wp_update_post(
				array(
					'ID'          => $builder_id,
					'post_status' => 'draft',
				),
				true
			);
			if ( is_wp_error( $result ) || ! $result ) {
				/* translators: %s: template title. */
				throw new \RuntimeException( sprintf( __( 'PostX could not switch off the template "%s".', 'ultimate-post' ), get_the_title( $builder_id ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- caught internally and returned as JSON text, never printed as HTML.
			}
		}

		// Publish the staged pages and templates. They stay drafts until here, so visitors never see a partial import.
		foreach ( $state['pending']['publish'] as $post_id ) {
			$this->starter_require_post_permission( get_post_type( $post_id ) );
			$published = wp_update_post(
				array(
					'ID'          => $post_id,
					'post_status' => 'publish',
				),
				true
			);
			if ( is_wp_error( $published ) || ! $published ) {
				/* translators: %s: page or template title. */
				throw new \RuntimeException( sprintf( __( 'PostX could not publish "%s" from the starter site.', 'ultimate-post' ), get_the_title( $post_id ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- caught internally and returned as JSON text, never printed as HTML.
			}
		}

		// Apply the new options and builder conditions.
		$options = $state['pending']['options'];
		if ( ! empty( $state['pending']['builder_conditions'] ) ) {
			$conditions_settings = get_option( 'ultp_builder_conditions', array() );
			if ( ! is_array( $conditions_settings ) ) {
				$conditions_settings = array();
			}
			foreach ( $state['pending']['builder_conditions'] as $builder_type => $items ) {
				if ( ! isset( $conditions_settings[ $builder_type ] ) || ! is_array( $conditions_settings[ $builder_type ] ) ) {
					$conditions_settings[ $builder_type ] = array();
				}
				foreach ( $items as $post_id => $conditions ) {
					$conditions_settings[ $builder_type ][ $post_id ] = $conditions;
				}
			}
			$options['ultp_builder_conditions'] = $conditions_settings;
		}
		foreach ( $options as $name => $value ) {
			if ( ! update_option( $name, $value ) && get_option( $name ) != $value ) { // phpcs:ignore Universal.Operators.StrictComparisons.LooseNotEqual -- WordPress persists scalar option values as strings.
				throw new \RuntimeException( __( 'PostX could not save the starter site settings.', 'ultimate-post' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Caught internally and returned as JSON text.
			}
		}

		// Content conversion and reference remapping are complete before these derivatives are written.
		foreach ( $state['pending']['publish'] as $post_id ) {
			$generated = $this->save_post_block_css( $post_id );
			if ( empty( $generated['success'] ) ) { throw new \RuntimeException( (string) $generated['message'] ); }
		}

		// Reserve the original names reversibly. Old IDs/items are deleted only after the entire switch commits.
		foreach ( $state['pending']['menu_swaps'] as $swap ) {
			$old = get_term( $swap['term_id'], $swap['taxonomy'] );
			if ( $old instanceof \WP_Term ) {
				if ( ! current_user_can( 'delete_term', $old->term_id ) ) {
					/* translators: %s: menu title. */
					throw new \RuntimeException( sprintf( __( 'PostX could not replace the existing menu "%s".', 'ultimate-post' ), $swap['name'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- caught internally and returned as JSON text, never printed as HTML.
				}
				$state['ledger']['renamed_menus'][ $old->term_id ] = array(
					'name' => $old->name,
					'slug' => $old->slug,
				);
				$renamed_old                                       = wp_update_term(
					$old->term_id,
					'nav_menu',
					array(
						'name' => $old->name . ' ' . wp_generate_password( 12, false ),
						'slug' => $old->slug . '-postx-backup-' . $old->term_id,
					)
				);
				if ( is_wp_error( $renamed_old ) ) {
					throw new \RuntimeException( __( 'PostX could not prepare an existing menu for replacement.', 'ultimate-post' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Caught internally and returned as JSON text.
				}
			}
			$renamed = wp_update_term(
				$swap['new'],
				'nav_menu',
				array(
					'name' => $swap['name'],
					'slug' => $swap['slug'],
				)
			);
			if ( is_wp_error( $renamed ) ) {
				/* translators: %s: menu title. */
				throw new \RuntimeException( sprintf( __( 'PostX could not give the new menu the title "%s".', 'ultimate-post' ), $swap['name'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- caught internally and returned as JSON text, never printed as HTML.
			}
		}
		$locations = get_theme_mod( 'nav_menu_locations', array() );
		if ( is_array( $locations ) ) {
			$replaced = $locations;
			foreach ( $state['pending']['menu_swaps'] as $swap ) {
				foreach ( $replaced as $location => $menu_id ) {
					if ( (int) $menu_id === (int) $swap['term_id'] ) {
						$replaced[ $location ] = (int) $swap['new'];
					}
				}
			}
			if ( $replaced !== $locations ) {
				$state['ledger']['menu_locations'] = $locations;
				set_theme_mod( 'nav_menu_locations', $replaced );
				if ( get_theme_mod( 'nav_menu_locations', array() ) !== $replaced ) {
					throw new \RuntimeException( __( 'PostX could not assign the new menus to the theme.', 'ultimate-post' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Caught internally and returned as JSON text.
				}
			}
		}
	}

	/**
	 * Remove old menu backups only after the new site has committed.
	 *
	 * @param array $state Import state, by reference.
	 * @return void
	 */
	private function starter_cleanup_replaced_menus( array &$state ) {
		foreach ( array_keys( isset( $state['ledger']['renamed_menus'] ) ? $state['ledger']['renamed_menus'] : array() ) as $menu_id ) {
			try {
				$removed = current_user_can( 'delete_term', $menu_id ) && true === wp_delete_nav_menu( $menu_id );
			} catch ( \Throwable $error ) {
				$removed = false;
			}
			if ( ! $removed ) {
				/* translators: %d: old menu ID. */
				$state['warnings'][] = sprintf( __( 'The new menu is live, but PostX could not remove the old menu backup %d.', 'ultimate-post' ), $menu_id );
			}
		}
	}

	/**
	 * Undo everything the import created or changed so far.
	 *
	 * @param array $state Import state, by reference.
	 * @return string[] Problems that could not be undone. Empty when the rollback was complete.
	 */
	private function starter_import_rollback( array &$state ) {
		$errors = array();
		$ledger = &$state['ledger'];
		if ( $ledger['finished'] ) {
			return $errors;
		}

		foreach ( $ledger['options'] as $name => $saved ) {
			if ( $saved['existed'] ) {
				if ( ! update_option( $name, $saved['value'] ) && get_option( $name ) != $saved['value'] ) { // phpcs:ignore Universal.Operators.StrictComparisons.LooseNotEqual -- WordPress persists scalar option values as strings.
					/* translators: %s: option name. */
					$errors[] = sprintf( __( 'PostX could not restore the setting %s.', 'ultimate-post' ), $name );
				}
			} else {
				$missing = new \stdClass();
				if ( ! delete_option( $name ) && get_option( $name, $missing ) !== $missing ) {
					/* translators: %s: option name. */
					$errors[] = sprintf( __( 'PostX could not remove the imported setting %s.', 'ultimate-post' ), $name );
				}
			}
		}

		foreach ( $ledger['drafted'] as $builder_id => $status ) {
			$restored = wp_update_post(
				array(
					'ID'          => $builder_id,
					'post_status' => $status,
				),
				true
			);
			if ( is_wp_error( $restored ) || ! $restored ) {
				/* translators: 1: template ID, 2: post status. */
				$errors[] = sprintf( __( 'Template %1$d could not be restored to %2$s.', 'ultimate-post' ), $builder_id, $status );
			}
		}

		foreach ( array_reverse( $ledger['posts'] ) as $post_id ) {
			if ( wp_delete_post( $post_id, true ) ) {
				$this->save_post_block_css( $post_id, '', 'delete' );
			} else {
				/* translators: %d: post ID. */
				$errors[] = sprintf( __( 'Imported item %d could not be removed.', 'ultimate-post' ), $post_id );
			}
		}
		foreach ( $ledger['attachments'] as $attachment_id ) {
			if ( ! wp_delete_attachment( $attachment_id, true ) ) {
				/* translators: %d: attachment ID. */
				$errors[] = sprintf( __( 'Imported media %d could not be removed.', 'ultimate-post' ), $attachment_id );
			}
		}
		foreach ( $ledger['menus'] as $menu_id ) {
			if ( ! wp_delete_nav_menu( $menu_id ) ) {
				/* translators: %d: imported menu ID. */
				$errors[] = sprintf( __( 'Imported menu %d could not be removed.', 'ultimate-post' ), $menu_id );
			}
		}
		// Free the original names by removing staged menus first, then restore the untouched old menus.
		foreach ( isset( $ledger['renamed_menus'] ) ? $ledger['renamed_menus'] : array() as $menu_id => $original ) {
			if ( is_wp_error( wp_update_term( $menu_id, 'nav_menu', $original ) ) ) {
				/* translators: %d: original menu ID. */
				$errors[] = sprintf( __( 'The original name of menu %d could not be restored.', 'ultimate-post' ), $menu_id );
			}
		}

		if ( isset( $ledger['menu_locations'] ) ) {
			set_theme_mod( 'nav_menu_locations', $ledger['menu_locations'] );
			if ( get_theme_mod( 'nav_menu_locations', array() ) !== $ledger['menu_locations'] ) {
				$errors[] = __( 'PostX could not restore the theme menu assignments.', 'ultimate-post' );
			}
		}

		$ledger['finished'] = true;
		return $errors;
	}

	/**
	 * Record an object created by the import, so rollback can remove it.
	 *
	 * @param array      $state Import state, by reference.
	 * @param string     $kind  Ledger key: posts, attachments or menus.
	 * @param int|string $id    Object ID.
	 */
	private function starter_track_created( array &$state, $kind, $id ) {
		$state['ledger'][ $kind ][] = (int) $id;
	}

	/**
	 * Read a boolean-style "yes" flag from request params.
	 *
	 * @param array  $params Request params.
	 * @param string $key    Param name.
	 * @return bool
	 */
	private function starter_request_flag( $params, $key ) {
		return isset( $params[ $key ] ) && is_string( $params[ $key ] ) && 'yes' === sanitize_text_field( $params[ $key ] );
	}

	/**
	 * Decode the previous_items list that the client read from the preview.
	 *
	 * @param mixed $raw JSON string of {"posts": [...], "term": [...]}.
	 * @return array{posts: int[], term: int[]}
	 */
	private function starter_decode_previous_items( $raw ) {
		$decoded = is_string( $raw ) ? json_decode( $raw, true ) : null;
		return array(
			'posts' => is_array( $decoded ) && isset( $decoded['posts'] ) ? $this->starter_int_list( $decoded['posts'] ) : array(),
			'term'  => is_array( $decoded ) && isset( $decoded['term'] ) ? $this->starter_int_list( $decoded['term'] ) : array(),
		);
	}

	/**
	 * Turn a list into unique positive integers.
	 *
	 * @param mixed $list Any value.
	 * @return int[]
	 */
	private function starter_int_list( $list ) {
		if ( ! is_array( $list ) ) {
			return array();
		}
		return array_values( array_unique( array_filter( array_map( 'absint', $list ) ) ) );
	}

	/**
	 * Whether an object carries the starter-pack marker (current or legacy meta key).
	 *
	 * @param string $type 'post' or 'term'.
	 * @param int    $id   Object ID.
	 * @return bool
	 */
	private function starter_is_pack_item( $type, $id ) {
		if ( 'term' === $type ) {
			$meta_type = 'term';
			$keys      = array( '_ultp_starter_pack_term', \ULTP\Legacy_Keys::TERM_META['_ultp_starter_pack_term'] );
		} else {
			$meta_type = 'post';
			$keys      = array( '_ultp_starter_pack_post', \ULTP\Legacy_Keys::POST_META['_ultp_starter_pack_post'] );
		}
		foreach ( $keys as $key ) {
			if ( metadata_exists( $meta_type, $id, $key ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Every post and term that carries the starter-pack marker.
	 *
	 * @return array{posts: array, term: array}
	 */
	private function starter_previous_candidates() {
		global $wpdb;
		$post_ids = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key IN ( %s, %s )", '_ultp_starter_pack_post', \ULTP\Legacy_Keys::POST_META['_ultp_starter_pack_post'] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- One-off cleanup before import; must read live data.
		$terms    = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT term_id FROM {$wpdb->termmeta} WHERE meta_key IN ( %s, %s )", '_ultp_starter_pack_term', \ULTP\Legacy_Keys::TERM_META['_ultp_starter_pack_term'] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- One-off cleanup before import; must read live data.
		return array(
			'posts' => is_array( $post_ids ) ? $post_ids : array(),
			'term'  => is_array( $terms ) ? $terms : array(),
		);
	}

	/**
	 * IDs of the builder templates that are published right now.
	 *
	 * @return int[]
	 */
	private function starter_published_builder_ids() {
		$builders = new \WP_Query(
			array(
				'post_type'      => 'ultp_builder',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
		return is_array( $builders->posts ) ? array_map( 'intval', $builders->posts ) : array();
	}

	/**
	 * Whether a previous attachment has this URL (the dark logo is stored as a URL).
	 *
	 * @param string $url          URL to match.
	 * @param int[]  $previous_ids Previous post IDs.
	 * @return bool
	 */
	private function starter_is_previous_attachment_url( $url, $previous_ids ) {
		foreach ( $previous_ids as $post_id ) {
			if ( 'attachment' === get_post_type( $post_id ) && wp_get_attachment_url( $post_id ) === $url ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Delete the previous starter items from the list read before this import, after the switch.
	 *
	 * @param array $previous Listed post and term IDs.
	 * @param array $ledger   Ledger of what this import created. Those items are never deleted here.
	 * @return array{posts: array, term: array, failed: string[]}
	 */
	private function starter_delete_previous_items( $previous, $ledger ) {
		$post_ids = array();
		foreach ( $previous['posts'] as $post_id ) {
			if ( in_array( $post_id, $ledger['posts'], true ) || in_array( $post_id, $ledger['attachments'], true ) ) {
				continue;
			}
			if ( $this->starter_is_pack_item( 'post', $post_id ) ) {
				$post_ids[] = $post_id;
			}
		}
		$term_ids = array();
		foreach ( $previous['term'] as $term_id ) {
			if ( in_array( $term_id, $ledger['menus'], true ) ) {
				continue;
			}
			if ( $this->starter_is_pack_item( 'term', $term_id ) ) {
				$term_ids[] = $term_id;
			}
		}
		return $this->delete_starter_pack_items( $post_ids, $term_ids );
	}

	/**
	 * Delete starter-pack posts and terms the current user may delete.
	 *
	 * Shared by the deletion steps. Each item is checked for capability at the time it is removed.
	 *
	 * @param array $post_ids Post IDs to delete.
	 * @param array $term_ids Term IDs to delete.
	 * @return array{posts: array, term: array, failed: string[]}
	 */
	private function delete_starter_pack_items( $post_ids, $term_ids ) {
		$deleted_posts = array();
		$deleted_terms = array();
		$failed        = array();

		$site_logo = get_option( 'site_logo', '' );
		$dark_logo = (string) get_option( 'ultp_site_dark_logo', '' );
		foreach ( $post_ids as $post_id ) {
			if ( ! $this->current_user_can_change_post( $post_id, 'delete' ) ) {
				continue;
			}
			if ( get_post_type( $post_id ) == 'ultp_builder' ) {
				$conditions   = get_option( 'ultp_builder_conditions', array() );
				$builder_type = get_post_meta( $post_id, '_ultp_builder_type', true );
				if ( isset( $conditions[ $builder_type ][ $post_id ] ) ) {
					unset( $conditions[ $builder_type ][ $post_id ] );
					update_option( 'ultp_builder_conditions', $conditions );
				}
			}
			if ( $site_logo == $post_id ) {
				update_option( 'site_logo', '' );
			}
			// The dark logo is saved as a URL; clear it when it is the image being deleted.
			if ( $dark_logo && 'attachment' === get_post_type( $post_id ) && wp_get_attachment_url( $post_id ) === $dark_logo ) {
				update_option( 'ultp_site_dark_logo', '' );
			}
			if ( wp_delete_post( $post_id, true ) ) {
				$deleted_posts[] = $post_id;
				$this->save_post_block_css( $post_id, '', 'delete' );
			} else {
				/* translators: %d: post ID. */
				$failed[] = sprintf( __( 'PostX could not delete the previous item %d.', 'ultimate-post' ), $post_id );
			}
		}

		foreach ( $term_ids as $term_id ) {
			$term = get_term( (int) $term_id );
			if ( ! $term instanceof \WP_Term || ! current_user_can( 'delete_term', $term->term_id ) ) {
				continue;
			}
			if ( true === wp_delete_term( $term->term_id, $term->taxonomy ) ) {
				$deleted_terms[] = $term_id;
			} else {
				/* translators: %d: term ID. */
				$failed[] = sprintf( __( 'PostX could not delete the previous term %d.', 'ultimate-post' ), $term_id );
			}
		}

		return array(
			'posts'  => $deleted_posts,
			'term'   => $deleted_terms,
			'failed' => $failed,
		);
	}

	/**
	 * Find Menu Parent item
	 *
	 * @since 4.0.0
	 *
	 * @param array  $menu_items Array of menu items.
	 * @param string $title      Title of the parent menu item to find.
	 */
	public function find_parent_nav_item( $menu_items, $title ) {
		if ( $menu_items ) {
			foreach ( $menu_items as $menu_item ) {
				if ( $menu_item->title == $title ) {
					return $menu_item->ID;
				}
			}
		}
		return 0;
	}

	/**
	 * Save CSS of pages
	 *
	 * Saves the provided CSS content for a specific post ID. Depending on the operation type,
	 * it can also handle deletion of the CSS.
	 *
	 * @since 4.0.0
	 *
	 * @param int    $id   The post ID.
	 * @param string $css  The CSS content to save.
	 * @param string $type The operation type (e.g., 'delete').
	 * @throws \Exception If the CSS cannot be saved or deleted.
	 */
	public function save_post_block_css( $id, $css = '', $type = '' ) {
		$target = BlockStyleSource::target( $id );
		if ( ! is_int( $target ) || ! BlockStyleSource::can_edit( $target ) ) { return array( 'success' => false, 'message' => __( 'You cannot edit this style source.', 'ultimate-post' ) ); }
		if ( 'delete' === $type ) {
			$path = trailingslashit( wp_get_upload_dir()['basedir'] ) . 'ultimate-post/ultp-css-' . $target . '.css';
			if ( file_exists( $path ) ) { wp_delete_file( $path ); }
			return array( 'success' => true );
		}
		$result = Styles::regenerate_block_css( $target );
		return is_wp_error( $result ) ? array( 'success' => false, 'message' => $result->get_error_message() ) : $result;
	}

	/**
	 * Whether the current user may edit or delete one starter-pack post
	 *
	 * The routes already require manage_options; this asks about the post itself.
	 * map_meta_cap() cannot answer for an unregistered post type (ultp_builder is
	 * registered only while the Builder addon is on) and raises _doing_it_wrong, so
	 * for those it asks for edit_others_posts, which is what core falls back to.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $action  'edit' or 'delete'.
	 * @return bool
	 */
	private function current_user_can_change_post( $post_id, $action ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return false;
		}
		if ( ! post_type_exists( $post->post_type ) ) {
			return current_user_can( 'edit_others_posts' );
		}
		return current_user_can( 'delete' === $action ? 'delete_post' : 'edit_post', $post->ID );
	}

	/**
	 * Delete previos site import post/pages
	 *
	 * deletePrevious modes:
	 * - "preview": list what "Delete previous" would remove. Deletes nothing. Used before the import.
	 * - "commit":  delete the items listed in previous_items (from a preview). Used after a successful import
	 *              when there are no templates to import; starter_import_content_callback() does it otherwise.
	 * - "yes":     delete every starter item now. Kept for older clients; the starter import no longer uses it.
	 *
	 * @since 4.0.0
	 *
	 * @param \WP_REST_Request $server The REST request object.
	 */
	public function deletepost_getnewsletters( $server ) {
		$valid = Request_Validation::validate( $server->get_params(), 'deletepost_getnewsletters' );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}
		$post           = $server->get_params();
		$deletePrevious = isset( $post['deletePrevious'] ) ? sanitize_text_field( $post['deletePrevious'] ) : '';
		$deleted_terms  = array();
		$deleted_posts  = array();
		$candidates     = array(
			'posts' => array(),
			'term'  => array(),
		);
		$failed         = array();

		if ( 'preview' === $deletePrevious ) {
			$found               = $this->starter_previous_candidates();
			$candidates['posts'] = array_values(
				array_filter(
					$found['posts'],
					function ( $post_id ) {
						return $this->current_user_can_change_post( $post_id, 'delete' );
					}
				)
			);
			$candidates['term']  = array_values(
				array_filter(
					$found['term'],
					function ( $term_id ) {
						return current_user_can( 'delete_term', (int) $term_id );
					}
				)
			);
		} elseif ( 'yes' === $deletePrevious ) {
			$found         = $this->starter_previous_candidates();
			$deleted       = $this->delete_starter_pack_items( $found['posts'], $found['term'] );
			$deleted_posts = $deleted['posts'];
			$deleted_terms = $deleted['term'];
			$failed        = $deleted['failed'];
		} elseif ( 'commit' === $deletePrevious ) {
			$listed  = $this->starter_decode_previous_items( isset( $post['previous_items'] ) ? $post['previous_items'] : '' );
			$post_ids = array_filter(
				$listed['posts'],
				function ( $post_id ) {
					return $this->starter_is_pack_item( 'post', $post_id );
				}
			);
			$term_ids = array_filter(
				$listed['term'],
				function ( $term_id ) {
					return $this->starter_is_pack_item( 'term', $term_id );
				}
			);
			$deleted       = $this->delete_starter_pack_items( $post_ids, $term_ids );
			$deleted_posts = $deleted['posts'];
			$deleted_terms = $deleted['term'];
			$failed        = $deleted['failed'];
		}

		return rest_ensure_response(
			array(
				'success'    => true,
				'term'       => $deleted_terms,
				'posts'      => $deleted_posts,
				'candidates' => $candidates,
				'failed'     => $failed,
			)
		);
	}

	/**
	 * Import Single Template
	 *
	 * @since 4.0.0
	 *
	 * @param \WP_REST_Request $server The REST request object.
	 */
	public function single_page_import( $server ) {
		$valid = Request_Validation::validate( $server->get_params(), 'single_page_import' );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}
		$post         = $server->get_params();
		$id           = isset( $post['ID'] ) ? sanitize_text_field( $post['ID'] ) : '';
		$api_endpoint = isset( $post['api_endpoint'] ) ? sanitize_text_field( $post['api_endpoint'] ) : '';

		if ( $id && $api_endpoint ) {
			// SSRF protection: Validate API endpoint
			if ( ! $this->is_allowed_api_endpoint( $api_endpoint ) ) {
				return rest_ensure_response(
					array(
						'success' => false,
						'message' => __( 'Invalid API endpoint. Only trusted domains are allowed.', 'ultimate-post' ),
					)
				);
			}
			$import_single = ultimate_post()->get_template_request_body(
				array(
					'id'       => $id,
					'type'     => 'single',
					'ultp_ver' => ULTP_VER,
				),
				'single_page'
			);
			$response      = wp_safe_remote_post(
				$api_endpoint . '/wp-json/importer/single',
				array(
					'method'  => 'POST',
					'timeout' => 120,
					'body'    => $import_single,
				)
			);
			if ( is_wp_error( $response ) ) {
				return rest_ensure_response(
					array(
						'success'       => false,
						'response_data' => $response,
					)
				);
			}
			$response_data = json_decode( wp_remote_retrieve_body( $response ) );
			if ( empty( $response_data->success ) || ! isset( $response_data->content[0] ) ) {
				return rest_ensure_response(
					array(
						'success' => false,
					)
				);
			}
			$content = $response_data->content[0];
			return rest_ensure_response(
				array(
					'success' => true,
					'content' => $content,
				)
			);
		}
	}

	/**
	 * Create dummy posts
	 *
	 * @since 4.0.0
	 *
	 * @param array $importable_posts Array of importable post objects.
	 */
	public function starter_pack_dummy_post_creation( $importable_posts ) {
		$added_posts = array();
		if ( is_array( $importable_posts ) && ! empty( $importable_posts ) ) {
			if ( ! Functions::can_create_post_type( 'post', true ) ) {
				return new \WP_Error( 'rest_forbidden', __( 'You are not allowed to create and publish imported posts.', 'ultimate-post' ), array( 'status' => rest_authorization_required_code() ) );
			}
			// Check required uploads before creating any post, so a permission failure leaves no partial import.
			foreach ( $importable_posts as $post ) {
				if ( is_object( $post ) && ! empty( $post->img_src ) && ! current_user_can( 'upload_files' ) ) {
					return new \WP_Error( 'rest_forbidden', __( 'You are not allowed to upload imported media.', 'ultimate-post' ), array( 'status' => rest_authorization_required_code() ) );
				}
				foreach ( array( 'post_category' => 'category', 'post_tags' => 'post_tag' ) as $field => $taxonomy ) {
					if ( ! is_object( $post ) || empty( $post->$field ) || ! is_array( $post->$field ) ) {
						continue;
					}
					$tax = get_taxonomy( $taxonomy );
					if ( ! $tax || ! current_user_can( $tax->cap->assign_terms ) ) {
						return new \WP_Error( 'rest_forbidden', __( 'You are not allowed to assign imported terms.', 'ultimate-post' ), array( 'status' => rest_authorization_required_code() ) );
					}
					foreach ( $post->$field as $term ) {
						if ( is_object( $term ) && isset( $term->slug ) && is_string( $term->slug ) && ! get_term_by( 'slug', $term->slug, $taxonomy ) && ! current_user_can( $tax->cap->manage_terms ) ) {
							return new \WP_Error( 'rest_forbidden', __( 'You are not allowed to create imported terms.', 'ultimate-post' ), array( 'status' => rest_authorization_required_code() ) );
						}
					}
				}
			}
			foreach ( $importable_posts as $post ) {
				// A post needs a string title and content. Other fields are read only when they have the expected type.
				if ( ! is_object( $post ) || ! isset( $post->post_title, $post->post_content ) || ! is_string( $post->post_title ) || ! is_string( $post->post_content ) ) {
					continue;
				}
				$img_src = isset( $post->img_src ) && is_string( $post->img_src ) ? $post->img_src : '';
				$post_id  = wp_insert_post(
					array(
						'post_title'   => $post->post_title,
						'post_type'    => 'post',
						'post_status'  => 'publish',
						'post_content' => str_replace( 'u002d', '\u002d', $post->post_content ),
					)
				);
				$image_id = $this->upload_post_cat_media( $img_src, $post_id, $post->post_title );
				set_post_thumbnail( $post_id, $image_id );

				update_post_meta( $post_id, '_ultp_starter_pack_post', true );
				$this->save_post_block_css( $post_id );
				$post_category = isset( $post->post_category ) ? $post->post_category : array();
				$k             = 0;
				$cat_ids       = array();
				if ( is_array( $post_category ) && ! empty( $post_category ) ) {
					foreach ( $post_category as $i => $cat ) {
						if ( ! is_object( $cat ) || ! isset( $cat->name, $cat->slug ) || ! is_string( $cat->name ) || ! is_string( $cat->slug ) ) {
							continue;
						}
						if ( ! isset( $cat->img_src ) || ! is_string( $cat->img_src ) ) {
							$cat->img_src = '';
						}
						$cat_id = $this->starter_pack_dummy_taxonomy_creation( $cat->name, $cat->slug, $cat->img_src, 'category', $cat );
						if ( is_wp_error( $cat_id ) ) {
							return $cat_id;
						}
						wp_set_post_terms( $post_id, $cat_id, 'category', $k == 0 ? false : true );
						++$k;
						$cat_ids[] = $cat_id;
					}
				}
				$post_tags = isset( $post->post_tags ) ? $post->post_tags : array();
				$tag_ids   = array();
				$j         = 0;
				if ( is_array( $post_tags ) && ! empty( $post_tags ) ) {
					foreach ( $post_tags as $i => $tag ) {
						if ( ! is_object( $tag ) || ! isset( $tag->slug ) || ! is_string( $tag->slug ) ) {
							continue;
						}
						$tag_id = wp_set_post_terms( $post_id, $tag->slug, 'post_tag', $j == 0 ? false : true );
						if ( ! is_wp_error( $tag_id ) && is_array( $tag_id ) && isset( $tag_id[0] ) ) {
							update_term_meta( $tag_id[0], '_ultp_starter_pack_term', 'postx_term' );
							$tag_ids[] = $tag_id[0];
						}
						++$j;
					}
				}
				$added_posts[ $post_id ] = array(
					'title'   => $post->post_title,
					'cat_id'  => $cat_ids,
					'tag_ids' => $tag_ids,
				);
			}
		}
		return $added_posts;
	}

	/**
	 * Create dummy category of posts
	 *
	 * @since 4.0.0
	 *
	 * @param STRING $name     The name of the taxonomy term.
	 * @param STRING $slug     The slug for the taxonomy term.
	 * @param STRING $img_src  (Optional) The image source URL for the taxonomy term.
	 * @param STRING $taxonomy (Optional) The taxonomy type.
	 * @param OBJECT $term_data (Optional) The raw payload entry for the term. Built from the arguments above when omitted.
	 */
	public function starter_pack_dummy_taxonomy_creation( $name, $slug, $img_src = '', $taxonomy = '', $term_data = null ) {
		$new_taxonomy = get_term_by( 'slug', $slug, $taxonomy );
		if ( ! $new_taxonomy ) {
			$tax = get_taxonomy( $taxonomy );
			if ( ! $tax || ! current_user_can( $tax->cap->manage_terms ) ) {
				return new \WP_Error( 'rest_forbidden', __( 'You are not allowed to create imported terms.', 'ultimate-post' ), array( 'status' => rest_authorization_required_code() ) );
			}
			$new_term = wp_insert_term(
				$name,
				$taxonomy,
				array(
					'slug' => $slug,
				)
			);
			if ( is_wp_error( $new_term ) ) {
				return $new_term;
			}
			if ( $new_term ) {
				update_term_meta( $new_term['term_id'], '_ultp_starter_pack_term', 'postx_term' );

				if ( null === $term_data ) {
					$term_data = (object) array(
						'name'    => $name,
						'slug'    => $slug,
						'img_src' => $img_src,
					);
				}
				// Older listeners may upload the optional image themselves. Keep term creation available,
				// but do not give those listeners a media URL the caller is not allowed to import.
				if ( is_object( $term_data ) && ! empty( $term_data->img_src ) && ! current_user_can( 'upload_files' ) ) {
					$term_data          = clone $term_data;
					$term_data->img_src = '';
				}
				// Fires once per created term: ( term ID, taxonomy, raw payload entry with name, slug and img_src ).
				do_action( 'ultp_starter_term_imported', $new_term['term_id'], $taxonomy, $term_data );

				return $new_term['term_id'];
			}
		} else {
			return $new_taxonomy->term_id;
		}
	}

	/**
	 * Upload image for post/category
	 *
	 * @since 4.0.0
	 *
	 * @param STRING $src     The source URL of the image.
	 * @param INT    $post_id The post ID to attach the image to.
	 * @param STRING $title   The title for the image.
	 */
	public function upload_post_cat_media( $src, $post_id, $title ) {
		if ( ! $src ) {
			return 0;
		}
		if ( ! current_user_can( 'upload_files' ) ) {
			throw new \RuntimeException( __( 'You are not allowed to upload imported media.', 'ultimate-post' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- handled by the import rollback; not HTML.
		}

		// Outside wp-admin, media_sideload_image() needs media.php, file.php and image.php.
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$image_id = media_sideload_image( $src, $post_id, $title, 'id' );
		if ( is_wp_error( $image_id ) || ! $image_id ) {
			return 0;
		}
		update_post_meta( $image_id, '_ultp_starter_pack_post', true );
		return $image_id;
	}

	/**
	 * Validate if the API endpoint is from an allowed domain (SSRF protection).
	 *
	 * @since 5.0.6
	 * @param string $url The URL to validate.
	 * @return bool True if allowed, false otherwise.
	 */
	private function is_allowed_api_endpoint( $url ) {
		return ultimate_post()->is_allowed_template_endpoint( $url );
	}
}
