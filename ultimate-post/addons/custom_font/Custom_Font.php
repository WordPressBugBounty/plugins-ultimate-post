<?php
namespace ULTP;

defined( 'ABSPATH' ) || exit;

class Custom_Font {
	public function __construct() {
		$this->custom_font_post_type_callback();
		add_action( 'add_meta_boxes', array( $this, 'init_metabox_callback' ) );
		add_action( 'save_post', array( $this, 'metabox_save_data' ) );
		add_filter( 'manage_ultp_custom_font_posts_columns', array( $this, 'templates_table_head' ) );
		add_action( 'manage_ultp_custom_font_posts_custom_column', array( $this, 'templates_table_content' ), 10, 2 );
		add_filter( 'upload_mimes', array( $this, 'add_file_types_to_uploads' ) );
		add_filter( 'wp_check_filetype_and_ext', array( $this, 'font_correct_filetypes' ), 10, 5 );
		add_filter( 'wp_handle_upload_prefilter', array( $this, 'sanitize_svg_upload' ) );
		add_filter( 'wp_handle_sideload_prefilter', array( $this, 'sanitize_svg_upload' ) );
		add_filter( 'enter_title_here', array( $this, 'update_custom_font_title' ), 10, 2 );
	}

	public function update_custom_font_title( $title, $post ) {
		if ( isset( $post->post_type ) && 'ultp_custom_font' === $post->post_type ) {
			return __( 'Add Font Family Name', 'ultimate-post' );
		}
		return $title;
	}

	public function font_correct_filetypes( $data, $file, $filename, $mimes, $real_mime ) {
		if ( ! empty( $data['ext'] ) && ! empty( $data['type'] ) ) {
			return $data;
		}

		$wp_file_type = wp_check_filetype( $filename, $mimes );

		if ( 'ttf' === $wp_file_type['ext'] ) {
			$data['ext']  = 'ttf';
			$data['type'] = 'font/ttf';
		}
		return $data;
	}


	public function add_file_types_to_uploads( $file_types ) {
		if ( 'ultp_custom_font' == get_post_type() || current_user_can( 'manage_options' ) ) {
			$new_filetypes          = array();
			$new_filetypes['woff']  = 'font/woff';
			$new_filetypes['woff2'] = 'font/woff2';
			$new_filetypes['ttf']   = 'font/ttf';
			$new_filetypes['eot']   = 'font/ttf';
			// SVG only for a custom font, where every upload is sanitized by sanitize_svg_upload().
			if ( $this->is_custom_font_upload() ) {
				$new_filetypes['svg'] = 'image/svg+xml';
			}
			$file_types = array_merge( $file_types, $new_filetypes );
			return $file_types;
		} else {
			return $file_types;
		}
	}

	/**
	 * Whether the current request uploads to, or edits, a custom font the user may edit.
	 *
	 * Uploads from the font screen's media modal send the font as `post_id`; async-upload.php
	 * checks its own nonce and refuses a `post_id` the user cannot edit. The screen itself is
	 * recognised by the global post.
	 *
	 * @return bool
	 */
	private function is_custom_font_upload() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}
		$post_id = isset( $_REQUEST['post_id'] ) ? absint( $_REQUEST['post_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read only to choose the context; async-upload.php verifies the media-form nonce.
		if ( ! $post_id ) {
			$post_id = (int) get_the_ID();
		}
		return $post_id > 0 && 'ultp_custom_font' === get_post_type( $post_id ) && current_user_can( 'edit_post', $post_id );
	}


	/**
	 * Clean an SVG uploaded to a custom font.
	 *
	 * Only runs for custom font uploads, the one place this add-on allows SVG. The file keeps
	 * only its font markup; an SVG that is not a font is refused. SVG uploads anywhere else
	 * are not touched here: WordPress refuses them unless another plugin allows SVG.
	 *
	 * @param array $file Upload data from `$_FILES`.
	 * @return array
	 */
	public function sanitize_svg_upload( $file ) {
		if ( empty( $file['name'] ) || empty( $file['tmp_name'] ) || ! empty( $file['error'] ) ) {
			return $file;
		}
		if ( 'svg' !== strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) ) ) {
			return $file;
		}
		if ( ! $this->is_custom_font_upload() ) {
			return $file;
		}

		require_once __DIR__ . '/Svg_Font_Sanitizer.php';
		$sanitized = Svg_Font_Sanitizer::sanitize_file( $file['tmp_name'] );
		if ( is_wp_error( $sanitized ) ) {
			$file['error'] = $sanitized->get_error_message();
		}
		return $file;
	}

	/**
	 * Font weight as stored and printed: 100-900 in steps of 100, normal or bold.
	 *
	 * @param string $weight Raw weight.
	 * @return string
	 */
	public static function sanitize_font_weight( $weight ) {
		$weight = strtolower( trim( (string) $weight ) );
		if ( in_array( $weight, array( 'normal', 'bold', '100', '200', '300', '400', '500', '600', '700', '800', '900' ), true ) ) {
			return $weight;
		}
		return 'normal';
	}

	/**
	 * Font family name safe to print inside a CSS string or a style attribute.
	 *
	 * @param string $font_name Post title of the font.
	 * @return string
	 */
	public static function sanitize_font_family( $font_name ) {
		return str_replace( array( '"', '\\', ';', '{', '}', '<', '>' ), '', (string) $font_name );
	}

	// Template Heading Add
	public function templates_table_head( $defaults ) {
		$type_array        = array(
			'preview' => '<span class="ultp-custom-font-preview-th">' . __( 'Preview', 'ultimate-post' ) . '</span>',
			'woff'    => __( 'WOFF', 'ultimate-post' ),
			'woff2'   => __( 'WOFF2', 'ultimate-post' ),
			'ttf'     => __( 'TTF', 'ultimate-post' ),
			'svg'     => __( 'SVG', 'ultimate-post' ),
			'eot'     => __( 'EOT', 'ultimate-post' ),
		);
		$defaults['title'] = __( 'Font Family', 'ultimate-post' );
		array_splice( $defaults, 2, 0, $type_array );

		return $defaults;
	}


	// Get Font Face
	public function get_font_face( $settings, $font_name ) {
		$font_src = array();
		if ( $settings['woff'] ) {
			array_push( $font_src, 'url(' . esc_url( $settings['woff'] ) . ') format("woff")' );
		}
		if ( $settings['woff2'] ) {
			array_push( $font_src, 'url(' . esc_url( $settings['woff2'] ) . ') format("woff2")' );
		}
		if ( $settings['ttf'] ) {
			array_push( $font_src, 'url(' . esc_url( $settings['ttf'] ) . ') format("TrueType")' );
		}
		if ( $settings['svg'] ) {
			array_push( $font_src, 'url(' . esc_url( $settings['svg'] ) . ') format("svg")' );
		}
		if ( $settings['eot'] ) {
			array_push( $font_src, 'url(' . esc_url( $settings['eot'] ) . ') format("eot")' );
		}
		$font_face = '@font-face {
            font-family: "' . self::sanitize_font_family( $font_name ) . '";
            font-weight: ' . self::sanitize_font_weight( isset( $settings['weight'] ) ? $settings['weight'] : '' ) . ';
            src: ' . implode( ', ', $font_src ) . ';
        }';

		return $font_face;
	}


	// Column Content
	public function templates_table_content( $column_id, $post_id ) {
		$woff     = $woff2 = $ttf = $svg = $eot = false;
		$settings = get_post_meta( $post_id, '__font_settings', true );

		if ( $settings ) {
			foreach ( $settings as $key => $value ) {
				if ( $value['woff'] ) {
					$woff = true; }
				if ( $value['woff2'] ) {
					$woff2 = true; }
				if ( $value['ttf'] ) {
					$ttf = true; }
				if ( $value['svg'] ) {
					$svg = true; }
				if ( $value['eot'] ) {
					$eot = true; }
			}
			$font_face = $this->get_font_face( $settings[0], get_the_title( $post_id ) );
			echo '<style type="text/css">' . $font_face . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS from get_font_face(): URLs pass esc_url(), family and weight pass sanitize_font_family()/sanitize_font_weight().

			switch ( $column_id ) {
				case 0:
					echo '<span class="ultp-custom-font-preview" style="font-family: ' . esc_attr( get_the_title( $post_id ) ) . '">' . esc_html__( 'The quick brown fox jumps over the lazy dog.', 'ultimate-post' ) . '</span>';
					break;
				case 1:
					echo '<span class="dashicons ' . ( $woff ? 'dashicons-yes' : 'dashicons-no-alt' ) . '"></span>';
					break;
				case 2:
					echo '<span class="dashicons ' . ( $woff2 ? 'dashicons-yes' : 'dashicons-no-alt' ) . '"></span>';
					break;
				case 3:
					echo '<span class="dashicons ' . ( $ttf ? 'dashicons-yes' : 'dashicons-no-alt' ) . '"></span>';
					break;
				case 4:
					echo '<span class="dashicons ' . ( $svg ? 'dashicons-yes' : 'dashicons-no-alt' ) . '"></span>';
					break;
				case 5:
					echo '<span class="dashicons ' . ( $eot ? 'dashicons-yes' : 'dashicons-no-alt' ) . '"></span>';
					break;
				default:
					break;
			}
		}
	}


	function init_metabox_callback() {
		add_meta_box(
			'ultp-custom-font-id',
			__( 'Font Vaiations', 'ultimate-post' ),
			array( $this, 'custom_font_callback' ),
			'ultp_custom_font',
			'advanced'
		);
	}


	function set_data( $arr = array(), $font_name = '' ) {
		?>
		<div class="ultp-custom-font-container ultp-custom-font<?php echo empty( $arr ) ? '-copy' : ''; ?>">
			<div class="ultp-custom-font-heading">
				<div>
					<label class="font-label"><?php echo esc_html__( 'Weight:  ', 'ultimate-post' ); ?> <span class="ultp-custom-font-weight"> <?php echo esc_html( isset( $arr['weight'] ) ? $arr['weight'] : '' ); ?> </span></label>
					<select name="weight[]">
						<?php $weight = isset( $arr['weight'] ) ? $arr['weight'] : ''; ?>
						<option <?php selected( $weight, 'normal' ); ?> value="normal"><?php echo esc_html__( 'Normal', 'ultimate-post' ); ?></option>
						<option <?php selected( $weight, '100' ); ?> value="100"><?php echo esc_html__( '100', 'ultimate-post' ); ?></option>
						<option <?php selected( $weight, '200' ); ?> value="200"><?php echo esc_html__( '200', 'ultimate-post' ); ?></option>
						<option <?php selected( $weight, '300' ); ?> value="300"><?php echo esc_html__( '300', 'ultimate-post' ); ?></option>
						<option <?php selected( $weight, '400' ); ?> value="400"><?php echo esc_html__( '400', 'ultimate-post' ); ?></option>
						<option <?php selected( $weight, '500' ); ?> value="500"><?php echo esc_html__( '500', 'ultimate-post' ); ?></option>
						<option <?php selected( $weight, '600' ); ?> value="600"><?php echo esc_html__( '600', 'ultimate-post' ); ?></option>
						<option <?php selected( $weight, '700' ); ?> value="700"><?php echo esc_html__( '700', 'ultimate-post' ); ?></option>
						<option <?php selected( $weight, '800' ); ?> value="800"><?php echo esc_html__( '800', 'ultimate-post' ); ?></option>
						<option <?php selected( $weight, '900' ); ?> value="900"><?php echo esc_html__( '900', 'ultimate-post' ); ?></option>
					</select>
				</div>
				<?php
					$preview_style = '';
				if ( ! empty( $arr ) ) {
					$font_face = $this->get_font_face( $arr, $font_name );
					echo '<style type="text/css">' . $font_face . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS from get_font_face(): URLs pass esc_url(), family and weight pass sanitize_font_family()/sanitize_font_weight().
					$preview_style = 'font-family: "' . self::sanitize_font_family( $font_name ) . '"; font-weight: ' . self::sanitize_font_weight( isset( $arr['weight'] ) ? $arr['weight'] : '' );
				}
				?>
				<span <?php echo '' !== $preview_style ? 'style="' . esc_attr( $preview_style ) . '"' : ''; ?> class="ultp-custom-font-preview"><?php echo esc_html__( 'The quick brown fox jumps over the lazy dog', 'ultimate-post' ); ?></span>
				<div class="ultp-custom-font-actions">
					<span class="ultp-custom-font-edit"><span class="dashicons dashicons-edit"></span><?php echo esc_html__( 'Edit', 'ultimate-post' ); ?></span>
					<span class="ultp-custom-font-close"><span class="dashicons dashicons-no-alt"></span><?php echo esc_html__( 'Close', 'ultimate-post' ); ?></span>
					<span class="ultp-custom-font-delete"><span class="dashicons dashicons-trash"></span><?php echo esc_html__( 'Delete', 'ultimate-post' ); ?></span>
				</div>
			</div>
			<div class="ultp-custom-font-content">
				<div class="ultp-font-file-list">
					<label><?php echo esc_html__( 'WOFF File', 'ultimate-post' ); ?></label>
					<input type="text" name="woff[]" value="<?php echo esc_attr( isset( $arr['woff'] ) ? $arr['woff'] : '' ); ?>" placeholder="<?php echo esc_html__( 'The web open Font Format, Used by Modern Browsers', 'ultimate-post' ); ?>"/>
					<span class="button ultp-font-upload"><span class="dashicons dashicons-upload"></span><?php echo esc_html__( 'Upload', 'ultimate-post' ); ?></span>
				</div>
				<div class="ultp-font-file-list">
					<label><?php echo esc_html__( 'WOFF2 File', 'ultimate-post' ); ?></label>
					<input type="text" name="woff2[]" value="<?php echo esc_attr( isset( $arr['woff2'] ) ? $arr['woff2'] : '' ); ?>" placeholder="<?php echo esc_html__( 'The web open Font Format 2, Used by Super Modern Browsers', 'ultimate-post' ); ?>"/>
					<span class="button ultp-font-upload"><span class="dashicons dashicons-upload"></span><?php echo esc_html__( 'Upload', 'ultimate-post' ); ?></span>
				</div>
				<div class="ultp-font-file-list">
					<label><?php echo esc_html__( 'TTF File', 'ultimate-post' ); ?></label>
					<input type="text" name="ttf[]" value="<?php echo esc_attr( isset( $arr['ttf'] ) ? $arr['ttf'] : '' ); ?>" placeholder="<?php echo esc_html__( 'TrueType Fonts, Used for better supporting Safari, Android, iOS', 'ultimate-post' ); ?>"/>
					<span class="button ultp-font-upload"><span class="dashicons dashicons-upload"></span><?php echo esc_html__( 'Upload', 'ultimate-post' ); ?></span>
				</div>
				<div class="ultp-font-file-list">
					<label><?php echo esc_html__( 'SVG File', 'ultimate-post' ); ?></label>
					<input type="text" name="svg[]" value="<?php echo esc_attr( isset( $arr['svg'] ) ? $arr['svg'] : '' ); ?>" placeholder="<?php echo esc_html__( 'SVG font allow SVG to be used as glyphs when displaying text, Used by Legacy iOS', 'ultimate-post' ); ?>"/>
					<span class="button ultp-font-upload"><span class="dashicons dashicons-upload"></span><?php echo esc_html__( 'Upload', 'ultimate-post' ); ?></span>
				</div>
				<div class="ultp-font-file-list">
					<label><?php echo esc_html__( 'EOT File', 'ultimate-post' ); ?></label>
					<input type="text" name="eot[]" value="<?php echo esc_attr( isset( $arr['eot'] ) ? $arr['eot'] : '' ); ?>" placeholder="<?php echo esc_html__( 'Embedded OpenType, Used by IE6-IE9 Browsers', 'ultimate-post' ); ?>"/>
					<span class="button ultp-font-upload"><span class="dashicons dashicons-upload"></span><?php echo esc_html__( 'Upload', 'ultimate-post' ); ?></span>
				</div>
			</div>
		</div>
		<?php
	}


	function custom_font_callback( $post ) {
		wp_nonce_field( 'font_meta_box', 'custom_font_nonce' );
		$settings = get_post_meta( $post->ID, '__font_settings', true );

		$this->set_data(); // Set Empty Data

		if ( is_array( $settings ) && ! empty( $settings ) ) {
			foreach ( $settings as $key => $val ) {
				$this->set_data( $val, $post->post_title );
			}
		}
		echo '<span class="button ultp-font-variation-action">' . esc_html__( 'Add Variation', 'ultimate-post' ) . '</span>';
	}


	function metabox_save_data( $post_id ) {
		if ( ! isset( $_POST['custom_font_nonce'] ) ) {
			return; }
		if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['custom_font_nonce'] ) ), 'font_meta_box' ) ) {
			return; }

		$arr = array();
		if ( isset( $_POST['weight'] ) && is_array( $_POST['weight'] ) ) {
            foreach ($_POST['weight'] as $i => $value) { //phpcs:ignore
				if ( isset( $_POST['weight'][ $i ] ) &&
					( ! empty( $_POST['woff'][ $i ] ) ||
					! empty( $_POST['woff2'][ $i ] ) ||
					! empty( $_POST['ttf'][ $i ] ) ||
					! empty( $_POST['svg'][ $i ] ) ||
					! empty( $_POST['eot'][ $i ] ) ) ) {
							$temp           = array();
							$temp['weight'] = self::sanitize_font_weight( sanitize_text_field( $_POST['weight'][ $i ] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- update_post_meta() unslashes; unslashing here would strip backslashes twice.
							$temp['woff']   = isset( $_POST['woff'][ $i ] ) ? sanitize_text_field( $_POST['woff'][ $i ] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- update_post_meta() unslashes; unslashing here would strip backslashes twice.
							$temp['woff2']  = isset( $_POST['woff2'][ $i ] ) ? sanitize_text_field( $_POST['woff2'][ $i ] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- update_post_meta() unslashes; unslashing here would strip backslashes twice.
							$temp['ttf']    = isset( $_POST['ttf'][ $i ] ) ? sanitize_text_field( $_POST['ttf'][ $i ] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- update_post_meta() unslashes; unslashing here would strip backslashes twice.
							$temp['svg']    = isset( $_POST['svg'][ $i ] ) ? sanitize_text_field( $_POST['svg'][ $i ] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- update_post_meta() unslashes; unslashing here would strip backslashes twice.
							$temp['eot']    = isset( $_POST['eot'][ $i ] ) ? sanitize_text_field( $_POST['eot'][ $i ] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- update_post_meta() unslashes; unslashing here would strip backslashes twice.
							$arr[]          = $temp;
				}
			}
			update_post_meta( $post_id, '__font_settings', $arr );
		}
	}

	// Templates Post Type Register
	public function custom_font_post_type_callback() {
		$labels = array(
			'name'               => _x( 'Custom Fonts', 'Custom Font', 'ultimate-post' ),
			'singular_name'      => _x( 'Saved Custom Font', 'Custom Font', 'ultimate-post' ),
			'menu_name'          => __( 'Saved Custom Font', 'ultimate-post' ),
			'parent_item_colon'  => __( 'Parent Custom Font', 'ultimate-post' ),
			'all_items'          => __( 'Saved Custom Font', 'ultimate-post' ),
			'view_item'          => __( 'View Custom Font', 'ultimate-post' ),
			'add_new_item'       => __( 'Add New', 'ultimate-post' ),
			'add_new'            => __( 'Add New', 'ultimate-post' ),
			'edit_item'          => __( 'Edit Custom Font', 'ultimate-post' ),
			'update_item'        => __( 'Update Custom Font', 'ultimate-post' ),
			'search_items'       => __( 'Search Custom Font', 'ultimate-post' ),
			'not_found'          => __( 'No Custom Font Found', 'ultimate-post' ),
			'not_found_in_trash' => __( 'Not Custom Font found in Trash', 'ultimate-post' ),
		);
		$args   = array(
			'labels'              => $labels,
			'show_in_rest'        => true,
			'supports'            => array( 'title' ),
			'hierarchical'        => false,
			'public'              => false,
			'rewrite'             => false,
			'show_ui'             => true,
			'show_in_menu'        => false,
			'show_in_nav_menus'   => false,
			'exclude_from_search' => true,
			'capability_type'     => 'page',
		);
		register_post_type( 'ultp_custom_font', $args );
	}
}
