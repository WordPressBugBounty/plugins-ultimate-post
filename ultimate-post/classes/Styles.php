<?php
/**
 * Styles Add and Style REST API Action.
 *
 * @package ULTP\Styles
 * @since v.1.0.0
 */
namespace ULTP;

defined( 'ABSPATH' ) || exit;

/**
 * Styles class.
 *
 * Handles enqueuing and management of plugin stylesheets for Ultimate Post.
 *
 * @package ULTP\Styles
 * @since 4.0.0
 */
class Styles {

	/**
	 * Setup class.
	 *
	 * @since v.1.0.0
	 */
	private $changed_wp_block = '';

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'rest_api_callback' ) );
		add_action( 'wp_ajax_ultp_disable_google_font', array( $this, 'disable_google_font_callback' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_global_css' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_global_css' ) );
		add_action( 'enqueue_block_assets', array( $this, 'enqueue_editor_global_css' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_postx_block_css' ) );
		add_filter( 'render_block', array( $this, 'render_block_callback' ), 10, 2 ); // render block to enqueue corresponding css.
		add_action( 'ultp_enqueue_postx_block_css', array( $this, 'ultp_enqueue_postx_block_css_callback' ), 10, 1 ); // action to enqueue the block css.

		add_action( 'after_delete_post', array( $this, 'ultp_delete_post_callback' ), 10, 2 ); // Delete Plugin Data CSS file delete Action.
	}

	/**
	 * REST API Action
	 *
	 * @since v.1.0.0
	 * @return void
	 */
	public function rest_api_callback() {
		register_rest_route(
			'ultp/v1',
			'/save_block_css/',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'save_block_content_css' ),
					'permission_callback' => array( $this, 'can_save_block_css' ),
				),
			)
		);
		register_rest_route(
			'ultp/v1',
			'/get_other_post_content/',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'get_other_post_content_callback' ),
					'permission_callback' => function () {
						return current_user_can( 'publish_posts' );
					},
					'args'                => Request_Validation::args( 'get_other_post_content_callback' ),
				),
			)
		);
		register_rest_route(
			'ultp/v1',
			'/action_option/',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'global_settings_action' ),
					'permission_callback' => function ( $request ) {
						// Saving writes the site-wide postx_global option: administrators only.
						$type = is_string( $request['type'] ) ? sanitize_text_field( $request['type'] ) : '';
						return current_user_can( 'set' === $type ? 'manage_options' : 'edit_posts' );
					},
					'args'                => Request_Validation::args( 'global_settings_action' ),
				),
			)
		);
		register_rest_route(
			'ultp/v1',
			'/postx_presets/',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'postx_presets_callback' ),
					'permission_callback' => function () {
						return current_user_can( 'manage_options' ); // manage_options For patch security issue.
					},
					'args'                => Request_Validation::args( 'postx_presets_callback' ),
				),
			)
		);
	}

	/**
	 * Save block css corresponding to page id
	 *
	 * @since v.1.0.0
	 *
	 * @param \WP_REST_Request $request Request Param of the REST API.
	 * @return ARRAY/Exception | Array of the Custom Message.
	 * @throws \Exception If CSS cannot be saved due to permission issues.
	 */
	/** One object-capability policy for permission handling and direct callback callers. */
	public function can_save_block_css( $request ) {
		$valid = $this->validate_block_style_request( $request );
		if ( is_wp_error( $valid ) ) { return $valid; }
		$id = $valid['post_id'];
		$allowed = 'ultp-widget' === $id ? current_user_can( 'edit_theme_options' ) : BlockStyleSource::can_edit( $id );
		return $allowed ? true : new \WP_Error( 'rest_forbidden', __( 'You cannot edit this PostX style source.', 'ultimate-post' ), array( 'status' => rest_authorization_required_code() ) );
	}

	private function validate_block_style_request( $request ) {
		$params = $request->get_params();
		if ( array_key_exists( 'block_css', $params ) || array_diff( array_keys( $params ), array( 'post_id', 'preview', 'blocks', 'has_block', 'src', 'fseTempId', '_locale', '_wpnonce' ) ) ) { return BlockStyleValues::error(); }
		$id = BlockStyleSource::target( $params['post_id'] ?? null );
		if ( is_wp_error( $id ) ) { return $id; }
		$booleans = array( true, false, 1, 0, '1', '0', 'true', 'false' );
		foreach ( array( 'preview', 'has_block' ) as $key ) { if ( array_key_exists( $key, $params ) && ! in_array( $params[$key], $booleans, true ) ) { return BlockStyleValues::error(); } }
		$preview = in_array( $params['preview'] ?? false, array( true, 1, '1', 'true' ), true );
		if ( ( ! $preview && array_key_exists( 'blocks', $params ) ) || ( $preview && ( 'ultp-widget' === $id || ! array_key_exists( 'blocks', $params ) ) ) ) { return BlockStyleValues::error(); }
		foreach ( array( 'src', 'fseTempId' ) as $key ) { if ( isset( $params[$key] ) && ( ! is_string( $params[$key] ) || strlen( $params[$key] ) > 200 ) ) { return BlockStyleValues::error(); } }
		return array( 'post_id' => $id, 'preview' => $preview, 'blocks' => $params['blocks'] ?? null );
	}

	public function save_block_content_css( $request ) {
		$allowed = $this->can_save_block_css( $request );
		if ( is_wp_error( $allowed ) ) { return $allowed; }
		$params = $this->validate_block_style_request( $request );
		$id = $params['post_id'];
		if ( ! $params['preview'] ) { return self::regenerate_block_css( $id ); }
		if ( ! get_post( $id ) ) { return new \WP_Error( 'ultp_style_source_missing', __( 'PostX style source was not found.', 'ultimate-post' ), array( 'status' => 404 ) ); }
		$blocks = BlockStyleInput::normalize( $params['blocks'] );
		if ( is_wp_error( $blocks ) ) { return $blocks; }
		set_transient( '_ultp_preview_blocks_' . get_current_user_id() . '_' . $id, $blocks, HOUR_IN_SECONDS );
		return array( 'success' => true, 'preview' => true, 'message' => __( 'PostX preview styles are ready.', 'ultimate-post' ) );
	}

	/** Regenerate derivatives after saved content exists. Never use historical stylesheet text. */
	public static function regenerate_block_css( $source_id ) {
		$id = BlockStyleSource::target( $source_id );
		if ( is_wp_error( $id ) ) { return $id; }
		$blocks = BlockStyleSource::get_blocks( $id, 'edit' );
		if ( is_wp_error( $blocks ) ) { return $blocks; }
		$css = BlockStyles::generate( $blocks );
		if ( is_wp_error( $css ) ) { return $css; }
		global $wp_filesystem;
		$uploads = wp_get_upload_dir();
		$dir = trailingslashit( $uploads['basedir'] ) . 'ultimate-post/';
		$file = $dir . 'ultp-css-' . $id . '.css';
		if ( ! ultimate_post()->ensure_filesystem( $uploads['basedir'], true ) ) {
			return new \WP_Error( 'ultp_style_write_failed', __( 'PostX could not access its CSS folder. Check uploads permissions and retry.', 'ultimate-post' ), array( 'status' => 500 ) );
		}
		$ok = true;
		if ( '' !== $css ) {
			$ok = ( $wp_filesystem->is_dir( $dir ) || $wp_filesystem->mkdir( $dir ) ) && ( ( $wp_filesystem->exists( $file ) && $wp_filesystem->get_contents( $file ) === $css ) || $wp_filesystem->put_contents( $file, $css ) );
		} elseif ( $wp_filesystem->exists( $file ) ) { $ok = $wp_filesystem->delete( $file ); }
		if ( ! $ok ) { return new \WP_Error( 'ultp_style_write_failed', __( 'PostX could not save styles. Check uploads permissions and retry.', 'ultimate-post' ), array( 'status' => 500 ) ); }
		$old = 'ultp-widget' === $id ? get_option( $id, '' ) : get_post_meta( $id, '_ultp_css', true );
		if ( 'ultp-widget' === $id ) { update_option( $id, $css ); }
		elseif ( '' !== $css ) { update_post_meta( $id, '_ultp_css', wp_slash( $css ) ); update_post_meta( $id, '_ultp_active', 'yes' ); }
		else { delete_post_meta( $id, '_ultp_css' ); delete_post_meta( $id, '_ultp_active' ); }
		if ( $old !== $css ) { ultimate_post()->set_setting( 'save_version', wp_rand( 1, 1000 ) ); }
		delete_transient( '_ultp_preview_blocks_' . get_current_user_id() . '_' . $id );
		return array( 'success' => true, 'message' => __( 'PostX styles have been regenerated.', 'ultimate-post' ) );
	}

	/**
	 * Get Post Content for other Posts while performing css save
	 *
	 * @since v.4.1.10
	 * @param \WP_REST_Request $server Request Param of the REST API.
	 * @return ARRAY/Exception | Array of the Custom Message.
	 */
	public function get_other_post_content_callback( $server ) {
		$valid = Request_Validation::validate( $server->get_params(), 'get_other_post_content_callback' );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}
		$post     = $server->get_params();
		$post_id  = isset( $post['postId'] ) ? absint( $post['postId'] ) : 0;
		$the_post = $post_id ? get_post( $post_id ) : null;
		$can_edit = $the_post && current_user_can( 'edit_post', $post_id );

		// Published synced patterns are readable by anyone who may insert them, as in core.
		$can_read = $can_edit || (
			$the_post &&
			'wp_block' === $the_post->post_type &&
			'publish' === $the_post->post_status &&
			current_user_can( 'read_post', $post_id )
		);

		if ( $can_read ) {
			// Rewrites the pattern's block attributes, so only for users who may edit it.
			if ( $can_edit && 'wp_block' === $the_post->post_type ) {
				$this->handle_wpblock_current_id( $post_id );
			}
			return array(
				'success' => true,
				'data'    => get_post( $post_id )->post_content,
				'message' => __( 'Data retrive done', 'ultimate-post' ),
			);
		} else {
			return array(
				'success' => false,
				'message' => __( 'Data not found!!', 'ultimate-post' ),
			);
		}
	}


	/**
	 * Get and Set PostX Global Settings
	 *
	 * @since v.2.4.24
	 * @param \WP_REST_Request $server Request Param of the REST API.
	 * @return ARRAY | Array of the Custom Message.
	 */
	public function global_settings_action( $server ) {
		$valid = Request_Validation::validate( $server->get_params(), 'global_settings_action' );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}
		$post  = $server->get_params();
		$_type = isset( $post['type'] ) ? ultimate_post()->ultp_rest_sanitize_params( $post['type'] ) : '';
		if ( $_type && ultimate_post()->permission_check_for_restapi() ) {
			if ( $_type == 'set' ) {
				if ( current_user_can( 'manage_options' ) ) {
					// Stylesheet fields are dropped; CSS is generated from the saved controls.
					update_option( 'postx_global', GlobalStyles::without_legacy_css( ultimate_post()->ultp_rest_sanitize_params( $post['data'] ) ) );
				}
				return array( 'success' => true );
			} else {
				return array(
					'success' => true,
					'data'    => GlobalStyles::without_legacy_css( get_option( 'postx_global', array() ) ),
				);
			}
		} else {
			return array( 'success' => false );
		}
	}

	/**
	 * Get and Set PostX Presets Settings
	 *
	 * @since v.2.4.24
	 * @param \WP_REST_Request $server Request Param of the REST API.
	 * @return ARRAY | Array of the Custom Message.
	 */
	public function postx_presets_callback( $server ) {
		$valid = Request_Validation::validate( $server->get_params(), 'postx_presets_callback' );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}
		$post = $server->get_params();
		$type = isset( $post['type'] ) ? sanitize_text_field( $post['type'] ) : '';
		$key  = isset( $post['key'] ) ? sanitize_text_field( $post['key'] ) : '';

		// Only the three preset options, never an arbitrary option name.
		if ( ! in_array( $key, array( 'ultpPresetColors', 'ultpPresetGradients', 'ultpPresetTypos' ), true ) ) {
			return array( 'success' => false );
		}
		// Stylesheet fields are dropped; CSS is generated from the saved controls.
		$data = isset( $post['data'] ) ? GlobalStyles::without_legacy_css( $this->sanitize_preset_data( $post['data'] ) ) : '';

		if ( $type ) {
			if ( $type == 'set' ) {
				if ( current_user_can( 'manage_options' ) ) {
					update_option( $key, $data );
				}
				return array( 'success' => true );
			} else {
				return array(
					'success' => true,
					'data'    => GlobalStyles::without_legacy_css( get_option( $key, array() ) ),
				);
			}
		} else {
			return array( 'success' => false );
		}
	}

	/**
	 * Sanitize preset data: every string through sanitize_text_field(), nested arrays walked.
	 * Numbers, booleans and null keep their type, which the editor reads back as saved.
	 *
	 * @since 5.1.2
	 * @param mixed $data Preset data from the request.
	 * @return mixed
	 */
	private function sanitize_preset_data( $data ) {
		if ( is_array( $data ) ) {
			return array_map( array( $this, 'sanitize_preset_data' ), $data );
		}
		if ( is_string( $data ) ) {
			return sanitize_text_field( $data );
		}
		if ( is_int( $data ) || is_float( $data ) || is_bool( $data ) || is_null( $data ) ) {
			return $data;
		}
		return '';
	}

	/**
	 * Disable Google Font Callback
	 *
	 * * @since v.2.8.1
	 *
	 * @return STRING
	 */
	public function disable_google_font_callback() {
		$nonce = isset( $_POST['wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['wpnonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'ultp-admin-nonce' ) ) {
			return;
		}
		// Rewrites every PostX CSS file and every post's saved CSS, so it needs the same
		// capability as saving the settings page it is run from.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		global $wp_filesystem;
		$upload_dir_url = wp_upload_dir();
		$dir            = trailingslashit( $upload_dir_url['basedir'] ) . 'ultimate-post/';
		if ( ! ultimate_post()->ensure_filesystem( $upload_dir_url['basedir'], true ) ) {
			return wp_send_json_error( __( 'PostX could not access its CSS files. Check the uploads folder permissions and try again.', 'ultimate-post' ) );
		}
		$css_dir = glob( $dir . '*.css' );
		if ( false === $css_dir ) {
			return wp_send_json_error( __( 'PostX could not read its CSS directory. Check the uploads folder permissions and try again.', 'ultimate-post' ) );
		}

		// Custom Font.
		$custom_fonts = array();
		if ( ultimate_post()->get_setting( 'ultp_custom_font' ) == 'true' ) {
			$args  = array(
				'post_type'   => 'ultp_custom_font',
				'post_status' => 'publish',
				'numberposts' => -1,
				'order'       => 'ASC',
			);
			$posts = get_posts( $args );
			if ( $posts ) {
				foreach ( $posts as $post ) {
					if ( ! empty( $post->post_title ) ) {
						$custom_fonts[] = preg_quote( $post->post_title, '/' );
					}
				}
			}
		}
		wp_reset_postdata();
		$custom_fonts = implode( '|', $custom_fonts );
		// system font.
		$exclude_typo = implode( '|', array( 'Arial', 'Tahoma', 'Verdana', 'Helvetica', 'Times New Roman', 'Trebuchet MS', 'Georgia' ) );
		// Fonts to keep: the custom fonts, then the system fonts. Join with '|' only when there are custom fonts, or the last one fuses with 'Arial'.
		$keep_fonts = '' !== $custom_fonts ? $custom_fonts . '|' . $exclude_typo : $exclude_typo;

		if ( count( $css_dir ) > 0 ) {
			foreach ( $css_dir as $key => $value ) {
				$css = $wp_filesystem->get_contents( $value );
				if ( false === $css ) {
					return wp_send_json_error( __( 'PostX could not read a CSS file. Some files may already be updated; check permissions and try again.', 'ultimate-post' ) );
				}
				$filter_css = preg_replace( '/(@import)[\w\s:\/?=,;.\'()+]*;/m', '', $css ); // Remove Import Font.
				$final_css  = preg_replace( '/(font-family:)((?!' . $keep_fonts . ')[\w\s:,\\\'-])*;/mi', '', $filter_css ); // Font Replace Except Default Font
				if ( ! $wp_filesystem->put_contents( $value, $final_css ) ) {
					return wp_send_json_error( __( 'PostX could not update a CSS file. Some files may already be updated; check permissions and try again.', 'ultimate-post' ) );
				}
			}
		}

		global $wpdb;
		$results = $wpdb->get_results( "SELECT * FROM $wpdb->postmeta WHERE `meta_key`='_ultp_css'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( ! empty( $results ) ) {
			foreach ( $results as $key => $value ) {
				$filter_css = preg_replace( '/(@import)[\w\s:\/?=,;.\'()+]*;/m', '', $value->meta_value ); // Remove Import Font.
				$final_css  = preg_replace( '/(font-family:)((?!' . $keep_fonts . ')[\w\s:,\\\'-])*;/mi', '', $filter_css ); // Font Replace Except Default Font.
				// update_post_meta() unslashes its value, so slash it first to keep backslashes such as content:"\f101".
				update_post_meta( $value->post_id, '_ultp_css', wp_slash( $final_css ) );
			}
		}

		return wp_send_json_success( __( 'CSS Updated!', 'ultimate-post' ) );
	}

	/**
	 * Load preset previews on PostX's dashboard and setup screens.
	 *
	 * @return void
	 */
	public function enqueue_admin_global_css() {
		$screen = get_current_screen();
		if ( $screen && in_array( $screen->id, array( 'toplevel_page_ultp-settings', 'postx_page_ultp-setup-wizard' ), true ) ) {
			$this->postx_global_css_callback();
		}
	}

	/**
	 * Load presets in the editor shell and its block-content iframe.
	 *
	 * @return void
	 */
	public function enqueue_editor_global_css() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && $screen->is_block_editor() ) {
			$this->postx_global_css_dependancies();
		}
	}

	/**
	 * Check global style loaded or not
	 *
	 * @since 4.0.0
	 * @return NULL
	 */
	public function postx_global_css_dependancies() {
		// The iframe copies registered styles but starts a fresh queue. Reuse their inline CSS.
		if ( ! wp_style_is( 'wpxpo-global-style', 'registered' ) ) {
			$this->postx_global_css_callback();
		}
		$wp_styles = wp_styles();
		$style     = $wp_styles->query( 'wp-block-library', 'registered' );
		$array = array( 'wpxpo-global-style', 'ultp-preset-colors-style', 'ultp-preset-gradient-style', 'ultp-preset-typo-style' );
		foreach ( $array as $arr ) {
			if ( wp_style_is( $arr, 'registered' ) ) {
				wp_enqueue_style( $arr );
				if ( $style && ! in_array( $arr, $style->deps, true ) ) {
					$style->deps[] = $arr;
				}
			}
		}
	}

	/**
	 * Set Global Color Codes
	 *
	 * @since v.1.0.0
	 * @return void
	 */
	public function postx_global_css_callback() {
		// Global CSS is generated from saved controls; stored stylesheet fields are never read.
		$global    = get_option( 'postx_global', array() );
		$theme_css = GlobalStyles::global_css( $global );

		wp_register_style( 'wpxpo-global-style', false, array(), ULTP_VER );
		wp_enqueue_style( 'wpxpo-global-style' );
		wp_add_inline_style( 'wpxpo-global-style', CSS_Sanitizer::ultp_esc_css( $theme_css ) );

		// preset Colors.
		$ultpPresetColors = get_option( 'ultpPresetColors', array() );
		$rootCSS          = GlobalStyles::preset_root_css( $ultpPresetColors, 'color' );
		$savedDLMode = ( isset( $global['enableDark'] ) && $global['enableDark'] ) ? 'ultpdark' : 'ultplight';
		$localDLMode = isset( $_COOKIE['ultplocalDLMode'] ) ? sanitize_key( wp_unslash( $_COOKIE['ultplocalDLMode'] ) ) : $savedDLMode;

		if ( $localDLMode == 'ultplight' && $savedDLMode == 'ultpdark' ) {
			$rootCSS = $this->handle_dark_light_color_switcher( $rootCSS );
		} elseif ( $localDLMode == 'ultpdark' ) {
			if ( $savedDLMode == 'ultplight' ) {
				$rootCSS = $this->handle_dark_light_color_switcher( $rootCSS );
			}
			// esc_url() also drops any '"', so the stored URL cannot leave the quoted url(). With no
			// dark logo set, the site logo is left as it is (CSS_Sanitizer::ultp_esc_css() removes an empty url()).
			$dark_logo = esc_url( (string) get_option( 'ultp_site_dark_logo', '' ) );
			if ( '' !== $dark_logo ) {
				$rootCSS = $rootCSS . ' .ultp-dark-logo.wp-block-site-logo img {content: url("' . $dark_logo . '");}';
			}
		}

		wp_register_style( 'ultp-preset-colors-style', false, array(), ULTP_VER );
		wp_enqueue_style( 'ultp-preset-colors-style' );
		wp_add_inline_style( 'ultp-preset-colors-style', CSS_Sanitizer::ultp_esc_css( $rootCSS ) );

		// preset Gradients.
		$ultpPresetGradients = get_option( 'ultpPresetGradients', array() );
		wp_register_style( 'ultp-preset-gradient-style', false, array(), ULTP_VER );
		wp_enqueue_style( 'ultp-preset-gradient-style' );
		wp_add_inline_style( 'ultp-preset-gradient-style', CSS_Sanitizer::ultp_esc_css( GlobalStyles::preset_root_css( $ultpPresetGradients, 'gradient' ) ) );

		// preset Typos.
		$ultpPresetTypos = get_option( 'ultpPresetTypos', array() );
		wp_register_style( 'ultp-preset-typo-style', false, array(), ULTP_VER );
		wp_enqueue_style( 'ultp-preset-typo-style' );
		wp_add_inline_style( 'ultp-preset-typo-style', CSS_Sanitizer::ultp_esc_css( GlobalStyles::preset_typo_css( $ultpPresetTypos ) ) );
	}

	/**
	 * Enqueue The Block Style
	 *
	 * * @since v.4.1.8
	 *
	 * @return void
	 */
	public function enqueue_postx_block_css() {
		$this->postx_global_css_callback();
		if ( apply_filters( 'postx_common_script', false ) ||
			isset( $_GET['et_fb'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only Divi editor detection (divi theme issue).
		) {
			ultimate_post()->register_scripts_common();
		}
		if ( is_admin() ) {
			return;
		}
		if ( wp_is_block_theme() ) {
			$this->handle_old_fse_css();
			$this->handle_fse_template_parts_css();
		}
		$this->ultp_enqueue_postx_block_css_callback( array( 'post_id' => ultimate_post()->get_ID() ) );
	}


	/**
	 * Enqueue The Block Style based on block( wp_block, fse_template, wp_template, wp_template_part )
	 *
	 * @since v.4.1.8
	 *
	 * @param STRING $block_content The block content.
	 * @param ARRAY  $block         The block data array.
	 * @return void
	 */
	public function render_block_callback( $block_content, $block ) {
		// ultimate-post/social
		$block_list = array(
			'ultimate-post/menu',
			'ultimate-post/menu-item', 
			'ultimate-post/accordion-item',
			'ultimate-post/tabs',
			'ultimate-post/gallery',
			'ultimate-post/social',
			'ultimate-post/button',
			'ultimate-post/list',
			'ultimate-post/table-of-content'
		);

		if ( isset( $block['blockName'] ) ) {
			if ( in_array($block['blockName'], $block_list) ) {
				switch ($block['blockName']) {
					case 'ultimate-post/menu':
						$start = '_ultp_mn_ic_';
						$end = '_ultp_mn_ic_end_';
						break;
					case 'ultimate-post/menu-item':
						$start = '_ultp_mi_ic_';
						$end = '_ultp_mi_ic_end_';
						break;
					case 'ultimate-post/accordion-item':
						$start = '_ultp_aci_ic_';
						$end = '_ultp_aci_ic_end_';
						break;
					case 'ultimate-post/tabs':
						$start = '_ultp_tb_ic_';
						$end = '_ultp_tb_ic_end_';
						break;
					case 'ultimate-post/gallery':
						$start = '_ultp_gl_ic_';
						$end = '_ultp_gl_ic_end_';
						break;
					case 'ultimate-post/social':
						$start = '_ultp_sc_ic_';
						$end = '_ultp_sc_ic_end_';
						break;
					case 'ultimate-post/button':
						$start = '_ultp_btn_ic_';
						$end = '_ultp_btn_ic_end_';
						break;
					case 'ultimate-post/list':
						$start = '_ultp_list_ic_';
						$end = '_ultp_list_ic_end_';
						break;
					case 'ultimate-post/table-of-content':
						$start = '_ultp_toc_ic_';
						$end = '_ultp_toc_ic_end_';
						break;
				}
				// ultimate-post/list
				$pattern = '/' . $start . '(.*?)' . $end . '/';
				preg_match_all( $pattern, $block_content, $matches );
				if ( is_array( $matches[1] ) ) {
					foreach ( $matches[1] as $m ) {
						if ( $m ) {
							$block_content = str_replace( $start . $m . $end, wp_kses( ultimate_post()->get_svg_icon( $m ), ultimate_post()->rendered_content_allowed_html() ), $block_content );
						}
					}
				}
			}
		}
		if ( ! is_admin() &&
			isset( $block['blockName'] ) &&
			$block['blockName'] === 'core/block' &&
			! empty( $block['attrs']['ref'] )
		) {
			// Validate ref_id is a positive integer
			$ref_id = absint( $block['attrs']['ref'] );
			if ( $ref_id < 1 ) {
				return $block_content;
			}
			
			if ( get_post_type( $ref_id ) === 'wp_block'
			) {
				do_action(
					'ultp_enqueue_postx_block_css',
					array(
						'post_id' => $ref_id,
						'css'     => '',
					)
				);
			}
		}
		if ( ! is_admin() &&
			isset( $block['blockName'] ) &&
			strpos( $block['blockName'], 'ultimate-post/' ) === 0
			&& ! empty( $block['attrs']['currentPostId'] )
		) {
			do_action(
				'ultp_enqueue_postx_block_css',
				array(
					'post_id' => $block['attrs']['currentPostId'],
					'css'     => '',
				)
			);
		}
		return $block_content;
	}

	/**
	 * Enqueue The Block Style
	 *
	 * @since v.4.1.8
	 *
	 * @param ARRAY $data The data array containing post_id and css.
	 * @return NULL
	 */
	public function ultp_enqueue_postx_block_css_callback( $data ) {
		$source = $data['post_id'] ?? '';
		$id = $source instanceof \WP_Block_Template ? 'template-' . md5( $source->id ) : BlockStyleSource::target( $source );
		if ( is_wp_error( $id ) || wp_style_is( 'ultp-post-' . $id, 'enqueued' ) ) { return; }
		// The legacy action's css argument is intentionally ignored.
		$css = is_int( $id ) && BlockStyleSource::preview_authorized( $id ) ? BlockStyleSource::get_preview_css( $id ) : BlockStyleSource::get_post_css( $source );
		if ( is_wp_error( $css ) || '' === $css ) { return; }
		ultimate_post()->register_scripts_common();
		wp_register_style( 'ultp-post-' . $id, false, array(), ULTP_VER );
		wp_enqueue_style( 'ultp-post-' . $id );
		wp_add_inline_style( 'ultp-post-' . $id, CSS_Sanitizer::ultp_esc_css( $css ) );
	}

	/**
	 * Enqueue CSS for FSE template parts (header, footer, etc.) that contain PostX blocks.
	 * Must run during wp_enqueue_scripts (before wp_head) so styles land in <head>.
	 *
	 * @since v.4.1.8
	 * @return void
	 */
	public function handle_fse_template_parts_css() {
		foreach ( get_block_templates( array(), 'wp_template_part' ) as $part ) {
			$this->ultp_enqueue_postx_block_css_callback( array( 'post_id' => $part ) );
		}
	}

	/**
	 * Enqueue The Block Style for old saved fse
	 *
	 * * @since v.4.1.8
	 *
	 * @return void
	 */
	public function handle_old_fse_css() {
		global $_wp_current_template_id;
		if ( is_string( $_wp_current_template_id ) ) {
			$template = get_block_template( $_wp_current_template_id );
			if ( $template ) { $this->ultp_enqueue_postx_block_css_callback( array( 'post_id' => $template ) ); }
		}
	}

	/**
	 * Handle WP Block postid
	 *
	 * @since v.4.1.8
	 * @param int $post_id ID of the synced pattern (wp_block) post.
	 * @return void
	 */
	public function handle_wpblock_current_id( $post_id ) {
		$this->changed_wp_block = '';
		$post                   = get_post( $post_id );
		$post_content           = $post->post_content;

		// Parse the blocks
		$blocks         = parse_blocks( $post_content );
		$updated_blocks = $this->update_block_attributes_func( $blocks, $post_id );
		if ( $this->changed_wp_block ) {
			wp_update_post(
				array(
					'ID'           => $post_id,
					'post_content' => serialize_blocks( $updated_blocks ),
				)
			);
		}
	}

	/**
	 * Handle WP Block postid save
	 *
	 * @since v.4.1.8
	 * @param ARRAY      $blocks   The array of blocks to update.
	 * @param INT|STRING $post_id The post ID to set in block attributes.
	 * @return array/Exception | Array of the Custom Message
	 */
	function update_block_attributes_func( $blocks, $post_id ) {
		foreach ( $blocks as &$block ) {
			if ( strpos( $block['blockName'], 'ultimate-post/' ) > -1 &&
				isset( $block['attrs']['currentPostId'] ) &&
				$post_id != $block['attrs']['currentPostId']
			) {
				$this->changed_wp_block = true;
				$block['attrs']         = array_merge( $block['attrs'], array( 'currentPostId' => $post_id ) );
			}
			// Recursively update inner blocks.
			if ( ! empty( $block['innerBlocks'] ) ) {
				$block['innerBlocks'] = $this->update_block_attributes_func( $block['innerBlocks'], $post_id );
			}
		}
		return $blocks;
	}

	/**
	 * Save Import CSS in the top of the File
	 *
	 * @since v.1.0.0
	 * @param STRING | $get_css CSS (STRING).
	 * @return STRING | Generated CSS
	 */
	public function set_top_css( $get_css = '' ) {
		return CSS_Sanitizer::ultp_sanitize_css_for_storage( $get_css );
	}


	/**
	 * Swap color for dark light
	 *
	 * @since 4.0.0
	 * @param STRING $rootCSS The root CSS string to swap colors in.
	 * @return NULL
	 */
	public function handle_dark_light_color_switcher( $rootCSS ) {
		$rootCSS = str_replace(
			array( '--postx_preset_Base_1_color', '--postx_preset_Base_2_color', '--postx_preset_Base_3_color' ),
			array( '--postx_preset_Base_1_color_dum', '--postx_preset_Base_2_color_dum', '--postx_preset_Base_3_color_dum' ),
			$rootCSS
		);
		$rootCSS = str_replace(
			array( '--postx_preset_Contrast_1_color', '--postx_preset_Contrast_2_color', '--postx_preset_Contrast_3_color' ),
			array( '--postx_preset_Base_1_color', '--postx_preset_Base_2_color', '--postx_preset_Base_3_color' ),
			$rootCSS
		);
		$rootCSS = str_replace(
			array( '--postx_preset_Base_1_color_dum', '--postx_preset_Base_2_color_dum', '--postx_preset_Base_3_color_dum' ),
			array( '--postx_preset_Contrast_1_color', '--postx_preset_Contrast_2_color', '--postx_preset_Contrast_3_color' ),
			$rootCSS
		);
		return $rootCSS;
	}


	/**
	 * Delete Plugin Data CSS file delete Action
	 *
	 * @since v.2.9.8
	 *
	 * @param MIXED $post_id The post ID whose CSS file should be deleted.
	 * @param MIXED $post    The post object (unused).
	 * @return void
	 */
	public function ultp_delete_post_callback( $post_id, $post ) {
		$upload          = wp_upload_dir();
		$upload_dir      = $upload['basedir'];
		$upload_dir_path = $upload_dir . "/ultimate-post/ultp-css-{$post_id}.css";
		if ( file_exists( $upload_dir_path ) ) {
			wp_delete_file( $upload_dir_path );
		}
	}
}
