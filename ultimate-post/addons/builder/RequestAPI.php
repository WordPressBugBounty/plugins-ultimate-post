<?php

/**
 * Plugin Cache.
 *
 * @package ULTP\RequestAPI
 * @since v.2.7.0
 */

namespace ULTP;

defined( 'ABSPATH' ) || exit;

/**
 * RequestAPI class.
 */
class RequestAPI {
	private $api_endpoint = 'https://postxkit.wpxpo.com/wp-json/restapi/v2/';

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'get_template_data' ) );
	}

	/**
	 * Create Builder Post Type
	 *
	 * @since v.2.7.0
	 * @return NULL
	 */
	public function get_template_data() {
		register_rest_route(
			'ultp/v2',
			'/get_single_premade/',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'get_single_premade_callback' ),
					'permission_callback' => function () {
						return current_user_can( 'manage_options' );
					},
					'args'                => Request_Validation::args( 'get_single_premade_callback' ),
				),
			)
		);
		register_rest_route(
			'ultp/v2',
			'/condition/',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'condition_settings_action' ),
					'permission_callback' => function () {
						return current_user_can( 'manage_options' );
					},
					'args'                => Request_Validation::args( 'condition_settings_action' ),
				),
			)
		);
		register_rest_route(
			'ultp/v2',
			'/condition_save/',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'condition_save_action' ),
					'permission_callback' => function () {
						return current_user_can( 'manage_options' );
					},
					'args'                => Request_Validation::args( 'condition_save_action' ),
				),
			)
		);
		register_rest_route(
			'ultp/v2',
			'/data_builder/',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'data_builder_action' ),
					'permission_callback' => function () {
						return current_user_can( 'manage_options' );
					},
					'args'                => Request_Validation::args( 'data_builder_action' ),
				),
			)
		);
	}

	/**
	 * Builder Post Type Data
	 *
	 * @since v.2.7.0
	 * @param ARRAY
	 * @return ARRAY | Information of Builder Post
	 */
	public function data_builder_action( $server ) {
		$valid = Request_Validation::validate( $server->get_params(), 'data_builder_action' );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}
		$post  = $server->get_params();
		$s_pid = isset( $post['pid'] ) ? ultimate_post()->ultp_rest_sanitize_params( $post['pid'] ) : '';

		$args         = array(
			'post_type'      => 'ultp_builder',
			'post_status'    => array( 'publish', 'draft' ),
			'orderby'        => 'title',
			'order'          => 'ASC',
			'posts_per_page' => -1,
		);
		$post_results = new \WP_Query( $args );
		$post_list    = array();
		if ( ! empty( $post_results ) ) {
			while ( $post_results->have_posts() ) {
				$post_results->the_post();
				$id          = get_the_ID();
				$meta_type   = get_post_meta( $id, '_ultp_builder_type', true );
				$post_list[] = array(
					'id'     => $id,
					'title'  => get_the_title(),
					'author' => get_the_author_meta( 'display_name' ),
					'date'   => get_the_date( get_option( 'date_format' ) ),
					'edit'   => get_edit_post_link( $id ),
					'type'   => $meta_type ? $meta_type : 'archive',
					'status' => get_post_status(),
				);
			}
			wp_reset_postdata();
		}

		$arg = array(
			'success'  => true,
			'postlist' => $post_list,
			'settings' => get_option( 'ultp_builder_conditions', array() ),
			'defaults' => $this->builder_data(),
		);
		if ( $s_pid ) {
			$post_meta   = get_post_meta( $s_pid, '_ultp_builder_type', true );
			$arg['type'] = $post_meta ? $post_meta : 'archive';
		}
		return $arg;
	}


	/**
	 * Get Add Default Condition Data
	 *
	 * @since v.2.7.0
	 * @return ARRAY | Default Data
	 */
	public function builder_data() {
		$archive_data = array(
			array(
				'label' => 'All Archive',
				'value' => '',
			),
			array(
				'label'  => 'Author Archive',
				'value'  => 'author',
				'search' => 'author###',
			),
			array(
				'label' => 'Date Archive',
				'value' => 'date',
			),
			array(
				'label' => 'Search Results',
				'value' => 'search',
			),
		);
		$single_data  = array();
		$header_data  = array(
			array(
				'label' => 'Entire Site',
				'value' => 'entire_site',
			),
			array(
				'label' => 'Archive',
				'value' => 'archive',
			),
			array(
				'label' => 'Singular',
				'value' => 'singular',
			),
		);

		$post_type = get_post_types( array( 'public' => true ), 'objects' );
		foreach ( $post_type as $key => $type ) {
			// Post Type
			$single_temp  = array(
				'label'  => $type->label,
				'value'  => $type->name,
				'search' => 'type###' . $type->name,
			);
			$archive_temp = array();

			// Taxonomy
			$taxonomy = get_object_taxonomies( $type->name, 'objects' );
			if ( ! empty( $taxonomy ) ) {
				$single_tax   = $archive_tax = array();
				$single_tax[] = $single_temp;

				$archive_temp = array(
					'label' => $type->label . ' Archive',
					'value' => $type->name . '_archive',
				);
				// $archive_tax[] = $archive_temp;
				foreach ( $taxonomy as $key => $val ) {
					if ( $val->public ) {
						$single_tax[]  = array(
							'label'  => 'In ' . $val->label,
							'value'  => 'in_' . $val->name,
							'search' => 'term###' . $val->name,
						);
						$archive_tax[] = array(
							'label'  => $val->label,
							'value'  => $val->name,
							'search' => 'term###' . $val->name,
						);

						if ( $val->hierarchical ) {
							// Hierarchical
							$single_tax[]  = array(
								'label'  => 'In Child ' . $val->label,
								'value'  => 'in_' . $val->name . '_children',
								'search' => 'term###' . $val->name,
							);
							$archive_tax[] = array(
								'label'  => 'Direct Child ' . $val->label . ' Of',
								'value'  => 'child_of_' . $val->name,
								'search' => 'term###' . $val->name,
							);
							$archive_tax[] = array(
								'label'  => 'Any Child ' . $val->label . ' Of',
								'value'  => 'any_child_of_' . $val->name,
								'search' => 'term###' . $val->name,
							);
						}
					}
				}
				// Author
				$single_tax[]         = array(
					'label'  => 'Posts By Author',
					'value'  => 'post_by_author',
					'search' => 'author###',
				);
				$single_temp['attr']  = $single_tax;
				$archive_temp['attr'] = $archive_tax;
			}
			$single_data[] = $single_temp;
			if ( ! empty( $archive_temp ) ) {
				$archive_data[] = $archive_temp;
			}
		}

		return array(
			'singular' => $single_data,
			'archive'  => $archive_data,
			'header'   => $header_data,
			'footer'   => $header_data,
		);
	}


	/**
	 * Save Conditions Data
	 *
	 * @since v.2.7.0
	 * @param ARRAY
	 * @return ARRAY | Message of the Condition Success
	 */
	public function condition_save_action( $server ) {
		$valid = Request_Validation::validate( $server->get_params(), 'condition_save_action' );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}
		$post       = $server->get_params();
		$s_settings = isset( $post['settings'] ) ? ultimate_post()->ultp_rest_sanitize_params( $post['settings'] ) : '';

		if ( $s_settings ) {
			update_option( 'ultp_builder_conditions', $s_settings );
			return array(
				'success'    => true,
				'data'       => 'Settings Saved!!!',
				's_settings' => $s_settings,
			);
		}
	}


	/**
	 * Condition Settings Actions
	 *
	 * @since v.2.7.0
	 * @param ARRAY
	 * @return ARRAY | Data of the Condition
	 */
	public function condition_settings_action( $server ) {
		$valid = Request_Validation::validate( $server->get_params(), 'condition_settings_action' );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}
		global $wpdb;
		$post           = $server->get_params();
		$s_type         = isset( $post['type'] ) ? ultimate_post()->ultp_rest_sanitize_params( $post['type'] ) : '';
		$s_term         = isset( $post['term'] ) ? ultimate_post()->ultp_rest_sanitize_params( $post['term'] ) : '';
		$s_title_return = isset( $post['title_return'] ) ? rest_sanitize_boolean( $post['title_return'] ) : '';
		$search_type    = explode( '###', $s_type );

		switch ( $search_type[0] ) {
			case 'type':
				$args = array(
					'post_type'      => $search_type[1],
					'post_status'    => 'publish',
					'orderby'        => 'title',
					'order'          => 'ASC',
					's'              => $s_term,
					'posts_per_page' => 10,
				);
				if ( $s_title_return ) {
					unset( $args['s'] );
					$args['p'] = $s_term;
				}
				$post_results = new \WP_Query( $args );
				$title        = '';
				$data         = array();
				if ( ! empty( $post_results ) ) {
					while ( $post_results->have_posts() ) {
						$post_results->the_post();
						$id     = get_the_ID();
						$title  = html_entity_decode( get_the_title() );
						$data[] = array(
							'value' => $id,
							'title' => ( $title ? $title : ( '##' . $id ) ),
						);
					}
					wp_reset_postdata();
				}
				return array(
					'success' => true,
					'data'    => ( $s_title_return ? $title : $data ),
				);
				break;

			case 'term':
				$args = array(
					'taxonomy'   => $search_type[1],
					'fields'     => 'all',
					'orderby'    => 'id',
					'order'      => 'ASC',
					'name__like' => $s_term,
				);
				if ( $s_title_return ) {
					$args['term_taxonomy_id'] = array( $s_term );
					unset( $args['name__like'] );
				}
				$args['taxonomy'] = ultimate_post()->allowed_picker_taxonomies( $args['taxonomy'] );
				if ( empty( $args['taxonomy'] ) ) {
					return array(
						'success' => true,
						'data'    => array(),
					);
				}
				$post_results = get_terms( $args );
				if ( is_wp_error( $post_results ) ) {
					return array(
						'success' => true,
						'data'    => array(),
					);
				}
				$title        = '';
				$data         = array();
				if ( ! empty( $post_results ) ) {
					foreach ( $post_results as $key => $val ) {
						$title  = $val->name;
						$data[] = array(
							'value' => $val->term_id,
							'title' => $title,
						);
					}
				}
				return array(
					'success' => true,
					'data'    => ( $s_title_return ? $title : $data ),
				);
				break;

			case 'author':
				$post_results = ultimate_post()->search_public_authors( $s_term, (bool) $s_title_return );
				$title        = '';
				$data         = array();
				if ( ! empty( $post_results ) ) {
					foreach ( $post_results as $key => $val ) {
						$title  = $val->display_name;
						$data[] = array(
							'value' => $val->ID,
							'title' => $val->display_name,
						);
					}
				}
				return array(
					'success' => true,
					'data'    => ( $s_title_return ? $title : $data ),
				);
				break;
			default:
				return array( 'success' => false );
				break;
		}
		return array(
			'success' => true,
			'data'    => 'This is Testing !!!',
		);
	}


	/**
	 * Single Premade Data and Create Builder Posts
	 *
	 * @since v.2.7.0
	 * @param ARRAY
	 * @return ARRAY | Data of the Premade
	 */
	public function get_single_premade_callback( $server ) {
		$valid = Request_Validation::validate( $server->get_params(), 'get_single_premade_callback' );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}
		if ( ! Functions::can_create_post_type( 'ultp_builder' ) ) {
			return new \WP_Error( 'rest_forbidden', __( 'You are not allowed to create builder templates.', 'ultimate-post' ), array( 'status' => rest_authorization_required_code() ) );
		}

		$post         = $server->get_params();
		$id           = isset( $post['ID'] ) ? sanitize_text_field( $post['ID'] ) : '';
		$api_endpoint = isset( $post['apiEndPoint'] ) ? sanitize_text_field( $post['apiEndPoint'] ) : '';
		$s_type       = isset( $post['type'] ) ? ultimate_post()->ultp_rest_sanitize_params( $post['type'] ) : '';

		// Every template, blank or imported, is created for a template type.
		if ( ! is_string( $s_type ) || '' === $s_type ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'The template type is missing.', 'ultimate-post' ),
				)
			);
		}

		if ( $id ) {
			// SSRF protection: only the template service, over HTTPS.
			if ( 0 !== strpos( $api_endpoint, 'https://' ) || ! ultimate_post()->is_allowed_template_endpoint( $api_endpoint ) ) {
				return rest_ensure_response(
					array(
						'success' => false,
						'message' => __( 'Invalid API endpoint. Only trusted domains are allowed.', 'ultimate-post' ),
					)
				);
			}

			$import_single = ultimate_post()->get_template_request_body(
				array(
					'id'   => $id,
					'type' => 'single',
				),
				'builder_premade'
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
						'success' => false,
						'data'    => 'Something went wrong:' . $response->get_error_message(),
					)
				);
			}

			$response_data = json_decode( wp_remote_retrieve_body( $response ) );

			if ( empty( $response_data->success ) || ! isset( $response_data->content[0]->content ) ) {
				return rest_ensure_response(
					array(
						'success' => false,
					)
				);
			}

			$content = $response_data->content[0]->content;
			$post_id = $this->set_post_content( $s_type, wp_slash( $content ) );
			if ( is_wp_error( $post_id ) ) {
				return $post_id;
			}

			return rest_ensure_response(
				array(
					'success' => true,
					'link'    => get_edit_post_link( $post_id ),
				)
			);
		} else {
			$post_id = $this->set_post_content( $s_type, '' );
			if ( is_wp_error( $post_id ) ) {
				return $post_id;
			}
			return array(
				'success' => true,
				'link'    => get_edit_post_link( $post_id ),
			);
		}
	}

	/**
	 * Single Premade Data and Insert Builder Posts
	 *
	 * @since v.2.7.0
	 * @param ARRAY
	 * @return int|\WP_Error Post ID or creation error.
	 */
	public function set_post_content( $type, $body = '' ) {
		if ( ! Functions::can_create_post_type( 'ultp_builder' ) ) {
			return new \WP_Error( 'rest_forbidden', __( 'You are not allowed to create builder templates.', 'ultimate-post' ), array( 'status' => rest_authorization_required_code() ) );
		}
		$post_id  = wp_insert_post(
			array(
				'post_title'   => ucfirst( $type ) . ' Template',
				'post_content' => $body,
				'post_type'    => 'ultp_builder',
				'post_status'  => 'draft',
			),
			true
		);
		if ( is_wp_error( $post_id ) || ! $post_id ) {
			return is_wp_error( $post_id ) ? $post_id : new \WP_Error( 'ultp_insert_failed', __( 'The builder template could not be created.', 'ultimate-post' ), array( 'status' => 500 ) );
		}
		$settings = get_option( 'ultp_builder_conditions', array() );
		switch ( $type ) {
			case 'singular':
				update_post_meta( $post_id, '_ultp_builder_type', 'singular' );
				$settings['singular'][ $post_id ] = array( 'include/singular/post' );
				break;
			case 'front_page':
				update_post_meta( $post_id, '_ultp_builder_type', 'front_page' );
				$settings['singular'][ $post_id ] = array( 'include/front_page' );
				break;
			case 'author':
			case 'post_tag':
			case 'date':
			case 'search':
			case 'archive':
			case 'category':
				update_post_meta( $post_id, '_ultp_builder_type', 'archive' );
				$extra                           = $type != 'archive' ? '/' . $type : '';
				$settings['archive'][ $post_id ] = array( 'include/archive' . $extra );
				break;
			case 'header':
				update_post_meta( $post_id, '_ultp_builder_type', 'header' );
				$settings['header'][ $post_id ] = array( 'include/header/entire_site' );
				break;
			case 'footer':
				update_post_meta( $post_id, '_ultp_builder_type', 'footer' );
				$settings['footer'][ $post_id ] = array( 'include/footer/entire_site' );
				break;
			case '404':
				update_post_meta( $post_id, '_ultp_builder_type', '404' );
				$settings['404'][ $post_id ] = array( 'include/404' );
				break;
			default:
				break;
		}
		update_option( 'ultp_builder_conditions', $settings );
		return $post_id;
	}
}
