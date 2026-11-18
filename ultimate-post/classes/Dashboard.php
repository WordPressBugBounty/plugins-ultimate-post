<?php

/**
 * All Dashboard handler
 *
 * @package ULTP\Dashboard
 * @since 4.1.11
 */

namespace ULTP;

defined( 'ABSPATH' ) || exit;

/**
 * Dashboard class.
 *
 * Handles the admin dashboard for Ultimate Post plugin.
 *
 * @package ULTP\Dashboard
 * @since 4.0.0
 */
class Dashboard {
	/**
	 * Post types the dashboard's template, builder and custom font screens act on.
	 *
	 * @since 5.1.2
	 */
	const TEMPLATE_ACTION_POST_TYPES = array( 'ultp_templates', 'ultp_builder', 'ultp_custom_font' );

	/**
	 * Setup class.
	 *
	 * @since 4.0.0
	 * @return void
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'ultp_register_route' ) );
	}

	/**
	 * REST API Action
	 *
	 * @since 4.1.11
	 * @return void
	 */
	public function ultp_register_route() {
		register_rest_route(
			'ultp/v2',
			'/addon_block_action/',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'addon_block_action' ),
					'permission_callback' => function () {
						return current_user_can( 'manage_options' );
					},
					'args'                => Request_Validation::args( 'addon_block_action' ),
				),
			)
		);
		register_rest_route(
			'ultp/v2',
			'/save_plugin_settings/',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'save_plugin_settings' ),
					'permission_callback' => function () {
						return current_user_can( 'manage_options' );
					},
					'args'                => Request_Validation::args( 'save_plugin_settings' ),
				),
			)
		);
		register_rest_route(
			'ultp/v2',
			'/get_all_settings/',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'get_all_settings' ),
					'permission_callback' => function () {
						return current_user_can( 'manage_options' );
					},
					'args'                => array(),
				),
			)
		);
		register_rest_route(
			'ultp/v2',
			'/dashborad/',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'get_dashboard_callback' ),
					'permission_callback' => function () {
						return current_user_can( 'manage_options' );
					},
					'args'                => Request_Validation::args( 'get_dashboard_callback' ),
				),
			)
		);
		register_rest_route(
			'ultp/v2',
			'/wizard_site_status/',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'wizard_site_status_callback' ),
					'permission_callback' => function () {
						return current_user_can( 'manage_options' );
					},
					'args'                => Request_Validation::args( 'wizard_site_status_callback' ),
				),
			)
		);
		register_rest_route(
			'ultp/v2',
			'/send_initial_plugin_data/',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'send_initial_plugin_data_callback' ),
					'permission_callback' => function () {
						return current_user_can( 'manage_options' );
					},
					'args'                => Request_Validation::args( 'send_initial_plugin_data_callback' ),
				),
			)
		);
		register_rest_route(
			'ultp/v2',
			'/initial_setup_complete/',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'initial_setup_complete_callback' ),
					'permission_callback' => function () {
						return current_user_can( 'manage_options' );
					},
					'args'                => Request_Validation::args( 'initial_setup_complete_callback' ),
				),
			)
		);

		register_rest_route(
			'ultp/v2',
			'/template_action/',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'template_page_action' ),
					'permission_callback' => function () {
						return current_user_can( 'manage_options' );
					},
					'args'                => Request_Validation::args( 'template_page_action' ),
				),
			)
		);
	}

	/**
	 * Save addon/blocks on/off data.
	 *
	 * @since v.3.0.0
	 * @param \WP_REST_Request $server The REST server request object.
	 * @return ARRAY. Inserted post URL and status.
	 */
	public function addon_block_action( $server ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error( 'ultp_forbidden', __( 'You cannot manage these settings.', 'ultimate-post' ), array( 'status' => 403 ) );
		}
		$valid = Request_Validation::validate( $server->get_params(), 'addon_block_action' );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}
		$post        = $server->get_params();
		$addon_name  = isset( $post['key'] ) ? ultimate_post()->ultp_rest_sanitize_params( $post['key'] ) : '';
		$addon_value = isset( $post['value'] ) ? ultimate_post()->ultp_rest_sanitize_params( $post['value'] ) : '';
		if ( $addon_name ) {
			$addon_data                              = ultimate_post()->get_setting();
			$addon_data[ $addon_name ]               = $addon_value;
			$GLOBALS['ultp_settings'][ $addon_name ] = $addon_value;
			update_option( 'ultp_options', $addon_data );
		}
		return array(
			'success' => true,
			'message' => ( $addon_value == 'true' || $addon_value == 'false' ) ? ( $addon_value == 'true' ? __( 'The addon has been enabled.', 'ultimate-post' ) : __( 'The addon has been disabled.', 'ultimate-post' ) ) : ( $addon_value == 'yes' ? __( 'The block has been enabled.', 'ultimate-post' ) : __( 'The block has been disabled.', 'ultimate-post' ) ),
		);
	}

	/**
	 * Save settings of option panel.
	 *
	 * @since v.3.0.0
	 * @param \WP_REST_Request $server The REST server request object.
	 * @return \WP_REST_Response REST response object.
	 */
	public function save_plugin_settings( $server ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error( 'ultp_forbidden', __( 'You cannot manage these settings.', 'ultimate-post' ), array( 'status' => 403 ) );
		}
		$valid = Request_Validation::validate( $server->get_params(), 'save_plugin_settings' );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}
		$post = $server->get_params();
		$data = ultimate_post()->ultp_rest_sanitize_params( $post['settings'] );
		if ( count( $data ) > 0 ) {
			foreach ( $data as $key => $val ) {
				ultimate_post()->set_setting( $key, $val );
			}
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Public hook; renaming breaks existing callbacks.
			do_action( 'ultimate_post_after_setting_save' );
		}
		return rest_ensure_response(
			array(
				'success'     => true,
				'message'     => __( 'You have successfully saved the settings data.', 'ultimate-post' ),
				'wishListArr' => wp_json_encode( $data ),
			)
		);
	}

	/**
	 * Get all settings of option panel.
	 *
	 * @since v.3.0.0
	 * @param \WP_REST_Request $server The REST server request object.
	 * @return \WP_REST_Response REST response object.
	 */
	public function get_all_settings( $server ) {
		return rest_ensure_response(
			array(
				'success'  => true,
				'settings' => ultimate_post()->get_setting(),
			)
		);
	}

	/**
	 * Saved template & custom font actions.
	 *
	 * @since v.3.0.0
	 * @param \WP_REST_Request $server The REST server request object.
	 * @return ARRAY Inserted post URL and status.
	 */
	public function get_dashboard_callback( $server ) {
		$valid = Request_Validation::validate( $server->get_params(), 'get_dashboard_callback' );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}
		$post         = $server->get_params();
		$request_type = isset( $post['type'] ) ? ultimate_post()->ultp_rest_sanitize_params( $post['type'] ) : '';
		$pType        = isset( $post['pType'] ) ? ultimate_post()->ultp_rest_sanitize_params( $post['pType'] ) : '';
		switch ( $request_type ) {

			case 'saved_templates':
				// The Saved Templates and Custom Font screens list only their own post type.
				if ( ! in_array( $pType, array( 'ultp_templates', 'ultp_custom_font' ), true ) ) {
					return $this->template_action_refused();
				}
				$post_per_page = 10;
				$data          = array();
				$args          = array(
					'post_type'      => $pType,
					'post_status'    => array( 'publish', 'draft' ),
					'posts_per_page' => $post_per_page,
					'paged'          => isset( $post['pages'] ) ? ultimate_post()->ultp_rest_sanitize_params( $post['pages'] ) : 1,
				);

				if ( isset( $post['search'] ) ) {
					$args['paged'] = 1;
					$args['s']     = ultimate_post()->ultp_rest_sanitize_params( $post['search'] );
				}

				$the_query = new \WP_Query( $args );
				if ( $the_query->have_posts() ) {
					while ( $the_query->have_posts() ) {
						$the_query->the_post();
						$final = array(
							'id'     => get_the_ID(),
							'title'  => get_the_title(),
							'date'   => get_the_modified_date( 'Y/m/d h:i a' ),
							'status' => get_post_status(),
							'edit'   => get_edit_post_link(),
						);

						if ( $pType == 'ultp_custom_font' ) {
							$final    = array_merge(
								$final,
								array(
									'woff'  => false,
									'woff2' => false,
									'ttf'   => false,
									'svg'   => false,
									'eot'   => false,
								)
							);
							$settings = get_post_meta( get_the_ID(), '_ultp_font_settings', true );
							foreach ( is_array( $settings ) ? $settings : array() as $key => $value ) {
								if ( $value['ttf'] ) {
									$final['ttf'] = true;
								}
								if ( $value['svg'] ) {
									$final['svg'] = true;
								}
								if ( $value['eot'] ) {
									$final['eot'] = true;
								}
								if ( $value['woff'] ) {
									$final['woff'] = true;
								}
								if ( $value['woff2'] ) {
									$final['woff2'] = true;
								}
							}
							$final['font_settings'] = $settings;
						}
						$data[] = $final;
					}
				}
				wp_reset_postdata();
				return array(
					'success' => true,
					'data'    => $data,
					'new'     => ( $pType == 'ultp_custom_font' ? admin_url( 'post-new.php?post_type=ultp_custom_font' ) : admin_url( 'post-new.php?post_type=ultp_templates' ) ),
					'found'   => $the_query->found_posts,
					'pages'   => $the_query->max_num_pages,
				);
				break;

			case 'action_draft':
			case 'action_publish':
				if ( isset( $post['ids'] ) && is_array( $post['ids'] ) ) {
					$post_ids   = array_map( 'absint', array_filter( $post['ids'], 'is_scalar' ) );
					$new_status = str_replace( 'action_', '', $request_type );
					foreach ( $post_ids as $id ) {
						// Skip anything that is not ours or that the user may not change.
						if ( ! $this->template_action_post( $id, $new_status ) ) {
							continue;
						}
						wp_update_post(
							array(
								'ID'          => $id,
								'post_status' => $new_status,
							)
						);
					}
					return array(
						'success' => true,
						'message' => __( 'Status changed for selected items.', 'ultimate-post' ),
					);
				}
				break;

			case 'action_delete':
				if ( isset( $post['ids'] ) && is_array( $post['ids'] ) ) {
					$post_ids = array_map( 'absint', array_filter( $post['ids'], 'is_scalar' ) );
					foreach ( $post_ids as $id ) {
						if ( ! $this->template_action_post( $id, 'delete' ) ) {
							continue;
						}
						wp_delete_post( $id, true );
					}
				}
				return array(
					'success' => true,
					'message' => __( 'The selected item is deleted.', 'ultimate-post' ),
				);

			case 'helloBarAction':
				set_transient( 'ultp_helloBar' . ULTP_HELLOBAR, 'hide', 1296000 );
				return array(
					'success' => true,
					'message' => __( 'Notice is removed.', 'ultimate-post' ),
				);
				break;
			case 'generalDiscountAction':
				set_transient( 'ultp_generalDiscount', 'hide', 60 * DAY_IN_SECONDS );
				return array(
					'success' => true,
					'message' => __( 'Notice is removed.', 'ultimate-post' ),
				);
				break;

			default:
				// code...
				break;
		}
	}

	/**
	 * Wizard site status callback.
	 *
	 * @since v.3.0.0
	 * @return \WP_REST_Response REST response object.
	 */
	public static function wizard_site_status_callback() {
		$valid = Request_Validation::validate( wp_unslash( $_POST ), 'wizard_site_status_callback' );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}
		$nonce = isset( $_POST['wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['wpnonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'ultp-admin-nonce' ) ) {
			die();
		}
		// Saves a site option: same capability as the route's permission_callback.
		if ( ! current_user_can( 'manage_options' ) ) {
			die();
		}
		if ( isset( $_POST['siteType'] ) ) {
			$site_type = map_deep( wp_unslash( $_POST['siteType'] ), 'sanitize_text_field' );
			update_option( 'ultp_site_type', $site_type );
		}
		return rest_ensure_response( array( 'success' => true ) );
	}

	/**
	 * Initial plugin data callback. Kept so the setup wizard's request still gets a success response; sends nothing.
	 *
	 * @since v.2.8.1
	 * @param \WP_REST_Request $server The REST server request object.
	 * @return \WP_REST_Response REST response object.
	 */
	public function send_initial_plugin_data_callback( $server ) {
		$valid = Request_Validation::validate( $server->get_params(), 'send_initial_plugin_data_callback' );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}
		$post  = $server->get_params();
		$nonce = isset( $post['wpnonce'] ) ? sanitize_text_field( wp_unslash( $post['wpnonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'ultp-admin-nonce' ) ) {
			die();
		}
		// Same capability as the route's permission_callback.
		if ( ! current_user_can( 'manage_options' ) ) {
			die();
		}

		return rest_ensure_response( array( 'success' => true ) );
	}

	/**
	 * Initial plugin setup complete callback.
	 *
	 * @since v.2.8.1
	 * @param \WP_REST_Request $server The REST server request object.
	 * @return \WP_REST_Response REST response object.
	 */
	public static function initial_setup_complete_callback( $server ) {
		$valid = Request_Validation::validate( $server->get_params(), 'initial_setup_complete_callback' );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}
		$post  = $server->get_params();
		$nonce = isset( $post['wpnonce'] ) ? sanitize_text_field( wp_unslash( $post['wpnonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'ultp-admin-nonce' ) ) {
			die();
		}
		// Saves a plugin setting: same capability as the route's permission_callback.
		if ( ! current_user_can( 'manage_options' ) ) {
			die();
		}
		ultimate_post()->set_setting( 'init_setup', 'yes' );
		return rest_ensure_response(
			array(
				'success'  => true,
				'redirect' => admin_url( 'admin.php?page=ultp-settings#home' ),
			)
		);
	}

	/**
	 * Delete template page and handle builder, saved templates, custom font actions.
	 *
	 * @since v.2.6.6 Shifted from RequestAPI since v4.0.3
	 * @param \WP_REST_Request $server The REST server request object.
	 * @return ARRAY Success message.
	 */
	public function template_page_action( $server ) {
		$valid = Request_Validation::validate( $server->get_params(), 'template_page_action' );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}
		$post     = $server->get_params();
		$message  = '';
		$s_Type   = isset( $post['type'] ) ? ultimate_post()->ultp_rest_sanitize_params( $post['type'] ) : '';
		$s_id     = isset( $post['id'] ) && is_scalar( $post['id'] ) ? absint( $post['id'] ) : 0;
		$s_status = isset( $post['status'] ) ? ultimate_post()->ultp_rest_sanitize_params( $post['status'] ) : '';
		$section  = isset( $post['section'] ) && is_string( $post['section'] ) ? sanitize_key( $post['section'] ) : '';

		if ( $s_Type && $s_id ) {
			if ( $s_Type == 'delete' ) {
				if ( ! $this->template_action_post( $s_id, 'delete' ) ) {
					return $this->template_action_refused();
				}
				if ( 'builder' === $section ) {
					$conditions   = get_option( 'ultp_builder_conditions', array() );
					$builder_type = get_post_meta( $s_id, '_ultp_builder_type', true );
					if ( isset( $conditions[ $builder_type ][ $s_id ] ) ) {
						unset( $conditions[ $builder_type ][ $s_id ] );
						update_option( 'ultp_builder_conditions', $conditions );
					}
				}
				wp_delete_post( $s_id, true );
				$message = __( 'Template has been deleted.', 'ultimate-post' );
			} elseif ( $s_Type == 'duplicate' ) {
				$r_post = $this->template_action_post( $s_id, 'duplicate' );
				if ( ! $r_post ) {
					return $this->template_action_refused();
				}
				$fromBuilder     = 'builder' === $section;
				$post_id         = $s_id;
				$current_user    = wp_get_current_user();
				$new_post_author = $current_user->ID;
				if ( isset( $r_post ) && $r_post != null ) {
					$args = array(
						'post_author'  => $new_post_author,
						'post_content' => str_replace( array( 'u0022', 'u002d' ), array( '\u0022', '\u002d' ), $r_post->post_content ),
						'post_excerpt' => $r_post->post_excerpt,
						'post_name'    => $r_post->post_name,
						'post_status'  => $fromBuilder ? 'draft' : $r_post->post_status,
						'post_title'   => $r_post->post_title,
						'post_type'    => $r_post->post_type,
					);
				}
				$new_post_id = wp_insert_post( $args );

				$styles = Styles::regenerate_block_css( $new_post_id );
				if ( is_wp_error( $styles ) ) { return $styles; }

				if ( $fromBuilder ) {
					$type = get_post_meta( $post_id, '_ultp_builder_type', true );
					update_post_meta( $new_post_id, '_ultp_builder_type', $type );

					$width = get_post_meta( $post_id, '_ultp_container_width', true );
					update_post_meta( $new_post_id, '_ultp_container_width', $width );

					$sidebar = get_post_meta( $post_id, '_ultp_builder_sidebar', true );
					update_post_meta( $new_post_id, '_ultp_builder_sidebar', $sidebar );

					$widget_area = get_post_meta( $post_id, '_ultp_builder_widget_area', true );
					update_post_meta( $new_post_id, '_ultp_builder_widget_area', $widget_area );

					$conditions = get_option( 'ultp_builder_conditions', array() );
					if ( $conditions && $type ) {
						if ( isset( $conditions[ $type ][ $post_id ] ) ) {
							$conditions[ $type ][ $new_post_id ] = $conditions[ $type ][ $post_id ];
							update_option( 'ultp_builder_conditions', $conditions );
						}
					}
				}
				$message = __( 'Template has been duplicated.', 'ultimate-post' );
			} elseif ( $s_Type == 'status' ) {
				if ( $s_status ) {
					// The dashboard only toggles between these two.
					if ( ! in_array( $s_status, array( 'publish', 'draft' ), true ) || ! $this->template_action_post( $s_id, $s_status ) ) {
						return $this->template_action_refused();
					}
					wp_update_post(
						array(
							'ID'          => $s_id,
							'post_status' => $s_status,
						)
					);
				}
				$message = __( 'Status has been changed.', 'ultimate-post' );
			}
		}

		return array(
			'success' => true,
			'message' => $message,
		);
	}

	/**
	 * Load a post for a dashboard action, if it is one of ours and the user may act on it.
	 *
	 * @since 5.1.2
	 * @param int    $post_id Post ID.
	 * @param string $action  delete, duplicate, publish or draft.
	 * @return \WP_Post|null The post, or null when the action is not allowed.
	 */
	private function template_action_post( $post_id, $action ) {
		$the_post = $post_id ? get_post( $post_id ) : null;
		if ( ! $the_post || ! in_array( $the_post->post_type, self::TEMPLATE_ACTION_POST_TYPES, true ) ) {
			return null;
		}
		$type_object = get_post_type_object( $the_post->post_type );
		if ( ! $type_object ) {
			// The builder and custom font types are registered only while their addon is on, and
			// the per-post checks need a registered type. All three types use page capabilities.
			$fallback_caps = array(
				'delete'    => array( 'delete_others_pages' ),
				'duplicate' => array( 'edit_others_pages' ),
				'publish'   => array( 'edit_others_pages', 'publish_pages' ),
				'draft'     => array( 'edit_others_pages' ),
			);
			if ( ! isset( $fallback_caps[ $action ] ) ) {
				return null;
			}
			foreach ( $fallback_caps[ $action ] as $cap ) {
				if ( ! current_user_can( $cap ) ) {
					return null;
				}
			}
			return $the_post;
		}
		switch ( $action ) {
			case 'delete':
				$allowed = current_user_can( 'delete_post', $the_post->ID );
				break;
			case 'duplicate':
				$allowed = current_user_can( 'edit_post', $the_post->ID ) && current_user_can( $type_object->cap->create_posts );
				break;
			case 'publish':
				$allowed = current_user_can( 'edit_post', $the_post->ID ) && current_user_can( $type_object->cap->publish_posts );
				break;
			case 'draft':
				$allowed = current_user_can( 'edit_post', $the_post->ID );
				break;
			default:
				$allowed = false;
		}
		return $allowed ? $the_post : null;
	}

	/**
	 * Response for a dashboard action that is not allowed. The dashboard acts only on success.
	 *
	 * @since 5.1.2
	 * @return array
	 */
	private function template_action_refused() {
		return array(
			'success' => false,
			'message' => __( 'You are not allowed to do this to this item.', 'ultimate-post' ),
		);
	}
}
