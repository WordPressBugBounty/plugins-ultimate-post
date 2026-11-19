<?php
/**
 * Common Functions.
 *
 * @package ULTP\Functions
 * @since v.1.0.0
 */

namespace ULTP;

use ULTP\Includes\Helper\Xpo;

defined( 'ABSPATH' ) || exit;

/**
 * Functions class.
 *
 * Provides utility functions for Ultimate Post plugin.
 *
 * @package ULTP\Functions
 * @since 4.0.0
 */
class Functions {

	/**
	 * Funtion
	 *
	 * @since v.3.2.4
	 *
	 * Instance of the class.
	 *
	 * @var Functions | null
	 */
	private static $instance = null;

	/**
	 * Setup class.
	 *
	 * @since v.1.0.0
	 */
	public function __construct() {
	}

	/**
	 * Gets the instance of \ULTP\Functions class
	 *
	 * @return Functions
	 * @since v.3.2.4
	 */
	public static function get_instance() {
		if ( ! isset( $GLOBALS['ultp_settings'] ) ) {
			$GLOBALS['ultp_settings'] = get_option( 'ultp_options' );
		}

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * ID for the Builder Post or Normal Post
	 *
	 * @since v.2.3.1
	 * @return int Builder ID or current post ID
	 */
	public function get_ID() {
		$id = $this->is_builder();
		return $id ? $id : ( function_exists( 'is_shop' ) ? ( is_shop() ? wc_get_page_id( 'shop' ) : get_the_ID() ) : get_the_ID() );
	}

	/**
	 * Checking Statement of Dynamic Site Builder
	 *
	 * @since v.2.3.1
	 * @return BOOLEAN | is Builder or not
	 */
	public function is_archive_builder() {
		$id = get_the_ID();
		return get_post_type( $id ) == 'ultp_builder' ? get_post_meta( $id, '_ultp_builder_type', true ) : false;
	}

	/**
	 * Set Link with the Parameters
	 *
	 * @since v.1.1.0
	 * @param string $url .
	 * @param string $tag .
	 * @return STRING | URL with Arg
	 */
	public function get_premium_link( $url = null, $tag = '' ) {
		return Xpo::generate_utm_link(
			array(
				'url'    => $url,
				'utmKey' => $tag,
			)
		);
	}


	/**
	 * Quick Query
	 *
	 * @since v.1.1.0
	 * @param ARRAY $prams Quick query parameters.
	 * @param ARRAY $args Query arguments to modify.
	 * @return ARRAY
	 */
	public function get_quick_query( $prams, $args ) {
		/**
		 * Applies a Quick Query to the query arguments. PostX Pro provides the Quick Query choices
		 * and answers this.
		 *
		 * @param array $args  Query arguments so far.
		 * @param array $prams Block attributes (`queryQuick` names the choice).
		 */
		return apply_filters( 'ultp_quick_query_args', $args, $prams );
	}

	/**
	 * Get all reusable block IDs referenced in a post's content
	 *
	 * @since v.1.1.0
	 * @param INT|STRING $post_id .
	 * @return ARRAY
	 */
	public function get_reusable_ids( $post_id ) {
		$reusable_id = array();
		if ( $post_id ) {
			$post = get_post( $post_id );
			if ( isset( $post->post_content ) ) {
				if (
					has_blocks( $post->post_content ) &&
					strpos( $post->post_content, 'wp:block' ) &&
					strpos( $post->post_content, '"ref"' ) !== false
				) {
					$max_depth    = 20; // Nesting levels, counting innerBlocks and pattern-within-pattern steps.
					$max_patterns = 50; // Patterns whose content is loaded, including this post.
					// IDs already loaded, so a pattern that references itself or an ancestor is not expanded again.
					$visited = array( (int) $post->ID => true );

					// Each ref is recorded as written, as the top-level loop always did. A published
					// wp_block is then loaded once and its own refs are collected from its content.
					$collect = function ( $blocks, $depth ) use ( &$collect, &$reusable_id, &$visited, $max_depth, $max_patterns ) {
						foreach ( $blocks as $block ) {
							if ( isset( $block['attrs']['ref'] ) ) {
								$reusable_id[] = $block['attrs']['ref'];

								$pattern_id = absint( $block['attrs']['ref'] );
								if (
									$pattern_id &&
									! isset( $visited[ $pattern_id ] ) &&
									$depth < $max_depth &&
									count( $visited ) < $max_patterns
								) {
									$pattern = get_post( $pattern_id );
									if (
										$pattern instanceof \WP_Post &&
										'wp_block' === $pattern->post_type &&
										'publish' === $pattern->post_status &&
										strpos( $pattern->post_content, '"ref"' ) !== false
									) {
										$visited[ $pattern_id ] = true;
										$collect( parse_blocks( $pattern->post_content ), $depth + 1 );
									}
								}
							}

							if ( ! empty( $block['innerBlocks'] ) && $depth < $max_depth ) {
								$collect( $block['innerBlocks'], $depth + 1 );
							}
						}
					};

					$collect( parse_blocks( $post->post_content ), 0 );
				}
			}
		}
		return $reusable_id;
	}

	/**
	 * Reuse a healthy authenticated transport; initialize only absent or failed connections.
	 *
	 * @param string|false $context Filesystem context directory.
	 * @param bool         $relaxed Whether relaxed ownership is allowed by this write operation.
	 * @return bool Whether the global filesystem can be used.
	 */
	public function ensure_filesystem( $context = false, $relaxed = false ) {
		global $wp_filesystem;
		if ( $wp_filesystem instanceof \WP_Filesystem_Base && ! ( is_wp_error( $wp_filesystem->errors ) && $wp_filesystem->errors->has_errors() ) ) {
			return true;
		}
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		if ( WP_Filesystem( false, $context, $relaxed ) && is_object( $wp_filesystem ) ) {
			return true;
		}
		// Core leaves an unusable global after failed connections; never reuse that object.
		$wp_filesystem = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Clear core's failed transport before any later caller can dereference it.
		return false;
	}

	/**
	 * Safely reads file contents using WordPress Filesystem API
	 *
	 * @since v.4.1.8
	 * @param STRING $path .
	 * @return STRING|ARRAY.
	 */
	public function get_path_file_contents( $path ) {
		global $wp_filesystem;
		if ( ! $this->ensure_filesystem() ) {
			return '';
		}
		if ( $wp_filesystem->exists( $path ) ) {
			$contents = $wp_filesystem->get_contents( $path );
			return is_string( $contents ) ? $contents : '';
		}
		return '';
	}

	/**
	 * Builds and returns inline CSS for a post and its reusable blocks
	 *
	 * For page builders that render a template to a string. The markup is the one
	 * print_inline_css() prints. Closed in `finally`, so nothing can leave the buffer open.
	 *
	 * @since v.4.1.8
	 * @param INT|STRING $post_id     .
	 * @param BOOLEAN    $call_common .
	 * @param BOOLEAN    $strip_tags  Strip tags from the CSS, see print_inline_css().
	 * @return STRING|ARRAY.
	 */
	public function build_css_for_inline_print( $post_id, $call_common = true, $strip_tags = false ) {
		ob_start();
		try {
			$this->print_inline_css( $post_id, $call_common, $strip_tags );
		} finally {
			$html = ob_get_clean();
		}
		return $html;
	}

	/**
	 * Print the CSS of a post and its reusable blocks at this point of the output.
	 *
	 * For page builder previews: their AJAX responses hold only the widget's own output, so a style
	 * enqueued for the footer would never reach the page. WordPress prints the <style> element. One
	 * handle per call, because core prints a handle only once and each widget's response needs its CSS.
	 *
	 * @since 5.1.4
	 * @param int|string $post_id     Post ID.
	 * @param bool       $call_common Register the common scripts when there is CSS.
	 * @param bool       $strip_tags  Strip tags from the sanitised CSS. Only the template output asks for it, as it
	 *                                always has; other callers keep the sanitised CSS as it is.
	 * @return void
	 */
	public function print_inline_css( $post_id, $call_common = true, $strip_tags = false ) {
		static $count = 0;

		$post_id = absint( $post_id );
		$css     = $this->get_inline_css( $post_id, $call_common );
		if ( ! $css ) {
			return;
		}
		++$count;
		$handle = 'ultp-template-' . $post_id . '-' . $count;
		wp_register_style( $handle, false, array(), ULTP_VER );
		$css = CSS_Sanitizer::ultp_esc_css( $css );
		wp_add_inline_style( $handle, $strip_tags ? wp_strip_all_tags( $css ) : $css );
		wp_print_styles( $handle );
	}

	/**
	 * CSS of a post and its reusable blocks, not yet escaped: pass it to wp_add_inline_style()
	 * through CSS_Sanitizer::ultp_esc_css(), as print_inline_css() does.
	 *
	 * @since 5.1.3
	 * @param int|string $post_id     Post ID.
	 * @param bool       $call_common Register the common scripts when there is CSS.
	 * @return string CSS, or '' when the post has none.
	 */
	public function get_inline_css( $post_id, $call_common = true ) {
		$css = BlockStyleSource::get_post_css( $post_id );
		if ( $css && $call_common ) { $this->register_scripts_common(); }
		return $css;
	}

	/**
	 * Get Global Plugin Settings
	 *
	 * @since v.1.0.0
	 * @param STRING $key of the Option .
	 * @return ARRAY|STRING .
	 */
	public function get_setting( $key = '' ) {
		$data = $GLOBALS['ultp_settings'];
		if ( $key != '' ) {
			return isset( $data[ $key ] ) ? $data[ $key ] : '';
		} else {
			return $data;
		}
	}

	/**
	 * Whether the site administrator has explicitly enabled remote Google Fonts.
	 *
	 * Legacy disable_google_font values are not consent. Missing settings fail closed.
	 *
	 * @return bool
	 */
	public function is_google_fonts_enabled() {
		return 'yes' === $this->get_setting( 'enable_google_fonts' );
	}


	/**
	 * Settings that are safe to send to the browser.
	 *
	 * Excludes legacy addon secrets. Use this for anything localized to a script. get_setting()
	 * itself is unchanged, because callers write its result back to the option.
	 *
	 * @return array
	 */
	public function get_client_settings() {
		$data = $this->get_setting();
		if ( ! is_array( $data ) ) {
			return array();
		}
		unset( $data['chatgpt_secret_key'] );
		return $data;
	}


	/**
	 * Set Option Settings
	 *
	 * @since v.1.0.0
	 * @param STRING | $key of the Option (STRING) .
	 * @param STRING | $val Value (STRING) .
	 */
	public function set_setting( $key = '', $val = '' ) {
		if ( $key != '' ) {
			$data         = $GLOBALS['ultp_settings'];
			$data[ $key ] = $val;
			update_option( 'ultp_options', $data );
			$GLOBALS['ultp_settings'] = $data;
			do_action( 'ultp_settings_updated', $key, $val );
		}
	}


	/**
	 * Get Image HTML
	 *
	 * @since v.1.0.0
	 * @param STRING | $url .
	 * @param STRING | $size .
	 * @param STRING | $class .
	 * @param STRING | $alt .
	 * @param STRING | $lazy .
	 * @param ARRAY |  $darkImg .
	 * @param STRING | $srcset .
	 * @return STRING
	 */
	public function get_image_html( $url = '', $size = 'full', $class = '', $alt = '', $lazy = '', $darkImg = array(), $srcset = '' ) {
		$class        = sanitize_html_class( $class );
		$alt          = sanitize_text_field( (string) $alt ); // Keeps Unicode text; esc_attr() below does the attribute escaping.
		$hasDarkImage = ( $darkImg['enable'] && $darkImg['url'] ) ? ' ultp-light-image-block ' : '';
		$alt          = $alt ? ' alt="' . esc_attr( $alt ) . '" ' : '';
		$lazy_data    = $lazy ? ' loading="lazy"' : '';

		$dlMode = isset( $_COOKIE['ultplocalDLMode'] ) ? sanitize_key( wp_unslash( $_COOKIE['ultplocalDLMode'] ) ) : ultimate_post()->get_dl_mode();

		$lightMode = $hasDarkImage ? ( $dlMode == 'ultplight' ? '' : 'inactive ' ) : '';
		$darkMode  = $hasDarkImage ? ( $hasDarkImage && $dlMode == 'ultpdark' ? '' : ' inactive ' ) : '';

		$image = '<img ' . $lazy_data . $srcset . ' class="' . esc_attr( $class . $hasDarkImage . $lightMode ) . '" ' . $alt . ' src="' . esc_url( $url ) . '" />';
		if ( $hasDarkImage ) {
			$image .= '<img ' . $lazy_data . $darkImg['srcset'] . ' class="' . esc_attr( $class . $darkMode ) . ' ultp-dark-image-block " ' . $alt . ' src="' . esc_url( $darkImg['url'] ) . '" />';
		}
		return $image;
	}


	/**
	 * Get Image HTML
	 *
	 * @since v.1.0.0
	 * @param STRING | $attach_id .
	 * @param STRING | $size .
	 * @param STRING | $class .
	 * @param STRING | $alt .
	 * @param STRING | $srcset .
	 * @param STRING | $lazy .
	 * @return STRING
	 */
	public function get_image( $attach_id, $size = 'full', $class = '', $alt = '', $srcset = '', $lazy = '' ) {
		$img_alt     = get_post_meta( $attach_id, '_wp_attachment_image_alt', true );
		$alt         = $img_alt ? $img_alt : $alt;
		$alt         = $alt ? ' alt="' . esc_attr( $alt ) . '" ' : '';
		$class       = $class ? ' class="' . esc_attr( $class ) . '" ' : '';
		$size        = ( ultimate_post()->get_setting( 'disable_image_size' ) == 'yes' && strpos( $size, 'ultp_layout_' ) !== false ) ? 'full' : $size;
		$lazy_data   = $lazy ? ' loading="lazy"' : '';
		$srcset_data = $srcset ? ' srcset="' . esc_attr( wp_get_attachment_image_srcset( $attach_id ) ) . '"' : '';
		return '<img ' . $srcset_data . $lazy_data . $class . $alt . ' src="' . esc_url( (string) wp_get_attachment_image_url( $attach_id, $size ) ) . '" />';
	}

	/**
	 * Srcset and lazy-loading switches for the images of a block, from its saved boolean choices.
	 *
	 * @param array  $attr       Block attributes.
	 * @param string $block_name Block name (kept for the callers; not used).
	 * @return array{srcset:bool, lazy:bool}
	 */
	public function image_options( $attr, $block_name = '' ) {
		return array(
			'srcset' => isset( $attr['imgSrcset'] ) && true === $attr['imgSrcset'],
			'lazy'   => isset( $attr['imgLazy'] ) && true === $attr['imgLazy'],
		);
	}


	/**
	 * Get Excerpt Text
	 *
	 * @since v.1.0.0
	 * @param STRING | $post_id .
	 * @param int    $limit .
	 * @return STRING
	 */
	public function excerpt( $post_id, $limit = 55 ) {
		$content = preg_replace( '/(\[postx_template[\s\w="]*)]/m', '', get_the_excerpt( $post_id ) ); // Remove postx_template shortcode form Content
		return apply_filters( 'the_excerpt', wp_trim_words( $content, $limit ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core hook.
	}

	/**
	 * Builder Attributes
	 *
	 * @since v.1.0.0
	 * @param STRING | $type .
	 * @return STRING
	 */
	public function get_builder_attr( $type ) {
		$builder_data = '';
		if ( $type == 'archiveBuilder' || $type == 'archiveBuilderFilter' ) {
			if ( is_archive() ) {
				if ( is_date() ) {
					if ( is_year() ) {
						$builder_data = 'date###' . get_the_date( 'Y' );
					} elseif ( is_month() ) {
						$builder_data = 'date###' . get_the_date( 'Y-n' );
					} elseif ( is_day() ) {
						$builder_data = 'date###' . get_the_date( 'Y-n-j' );
					}
				} elseif ( is_author() ) {
					$builder_data = 'author###' . get_the_author_meta( 'ID' );
				} else {
					$obj = get_queried_object();
					if ( isset( $obj->taxonomy ) ) {
						$builder_data = 'taxonomy###' . $obj->taxonomy . '###' . $obj->slug;
					}
				}
			} elseif ( is_search() ) {
				$builder_data = 'search###' . get_search_query( true );
			}
		}
		if ( $type == 'archiveBuilderFilter' ) {
			return $builder_data ? $builder_data : '';
		}
		return $builder_data ? 'data-builder="' . esc_attr( $builder_data ) . '"' : '';
	}


	/**
	 * Determines if the builder is enabled based on the plugin settings.
	 *
	 * @param STRING $builder Optional. The builder name (currently unused).
	 * @return BOOL True if the builder is enabled, false otherwise.
	 */
	public function is_builder( $builder = '' ) {
		$id = '';
		if ( ultimate_post()->get_setting( 'ultp_builder' ) != 'false' ) {
			$page_id = ultimate_post()->builder_check_conditions( 'return_ib' );
			if ( $page_id ) {
				$id = $page_id;
			}
		}
		return $id;
	}


	/**
	 * Get Post Number Depending On Device
	 *
	 * @since v.2.5.4
	 * @param MIXED | $preDef .
	 * @param MIXED | $prev .
	 * @param MIXED | $current .
	 * @return STRING
	 */
	public function get_post_number( $preDef, $prev, $current ) {

		$current = is_object( $current ) ? json_decode( wp_json_encode( $current ), true ) : $current;
		if ( array(
			'lg' => $preDef,
			'sm' => $preDef,
			'xs' => $preDef,
		) == $current ) {
			if ( $preDef != $prev ) {
				return $prev;
			}
		}

		$lg = isset( $current['lg'] ) ? $current['lg'] : $prev;
		$sm = isset( $current['sm'] ) ? $current['sm'] : $lg;
		$xs = isset( $current['xs'] ) ? $current['xs'] : $lg;
		if ( $lg == $sm && $sm == $xs ) {
			return $lg;
		} else {
			global $ultpDevide;
			$currentDevice = ! empty( $ultpDevide ) ? $ultpDevide : $this->isDevice();
			if ( $currentDevice == 'mobile' ) {
				return $xs;
			} elseif ( $currentDevice == 'tablet' ) {
				return $sm;
			} else {
				return $lg;
			}
		}
	}


	/**
	 * Get Post Number Depending On Device
	 *
	 * @since v.2.5.4
	 * @return STRING | Device Type
	 */
	public function isDevice() {
		$useragent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_key( $_SERVER['HTTP_USER_AGENT'] ) : '';
		$device    = 'desktop';
		if ( $useragent ) {
			if ( preg_match( '/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i', strtolower( $useragent ) ) ) {
				$device = 'tablet';
			} elseif ( preg_match( '/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $useragent ) || preg_match( '/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr( $useragent, 0, 4 ) ) ) {
				$device = 'mobile';
			}
		}
		global $ultpDevide;
		$ultpDevide = $device;
		return $device;
	}


	/**
	 * Decode a JSON attribute a block saved, and check it before it is used.
	 *
	 * Block attributes are stored as JSON strings. A value is returned only when it decodes without
	 * an error and gives an array, or an object when `$assoc` is false. An array or an object that
	 * is already decoded is returned as it is. Anything else (an empty string, broken JSON, a number,
	 * a string, null) gives `$fallback`.
	 *
	 * @since v.5.1.2
	 * @param mixed $value    Saved attribute value, normally a JSON string.
	 * @param bool  $assoc    True decodes objects to arrays, false to `stdClass` objects.
	 * @param mixed $fallback Returned when the value is not usable JSON data.
	 * @return mixed Array or object, otherwise `$fallback`.
	 */
	public function decode_json_attr( $value, $assoc = false, $fallback = array() ) {
		if ( is_array( $value ) || is_object( $value ) ) {
			return $value;
		}
		if ( ! is_string( $value ) || '' === $value ) {
			return $fallback;
		}
		$decoded = json_decode( $value, (bool) $assoc );
		if ( JSON_ERROR_NONE !== json_last_error() ) {
			return $fallback;
		}
		return ( is_array( $decoded ) || is_object( $decoded ) ) ? $decoded : $fallback;
	}

	/**
	 * Get Raw Value from Objects
	 *
	 * @since v.2.5.3
	 * @param ARRAY | $attr .
	 * @return STRING | Device Type
	 */
	public function get_value( $attr ) {
		$data = array();
		if ( is_array( $attr ) ) {
			foreach ( $attr as $val ) {
				if ( is_object( $val ) && isset( $val->value ) ) { // Skip anything that is not a { value, label } item.
					$data[] = $val->value;
				}
			}
		}
		return $data;
	}

	/**
	 * QueryArgs for Filter
	 *
	 * @since v.2.8.9
	 * @param ARRAY | $attr .
	 * @return ARRAY
	 */
	public function getFilterQueryArgs( $attr ) {
		$tax_value = ( strlen( $attr['queryTaxValue'] ) > 2 ) ? $attr['queryTaxValue'] : array();
		$tax_value = $this->decode_json_attr( $tax_value, false, array() );
		$tax_value = is_array( $tax_value ) ? $tax_value : array(); // A JSON object is not a term list.

		$tax_value = ( isset( $tax_value[0] ) && is_object( $tax_value[0] ) ) ? $this->get_value( $tax_value ) : $tax_value;

		if ( is_array( $tax_value ) && count( $tax_value ) > 0 ) {
			$relation = isset( $attr['queryRelation'] ) ? $attr['queryRelation'] : 'OR';

			// The Advanced Filter's relation applies to the visitor's picks (sent as `multiTaxonomy`), not to
			// the block's own Taxonomy Value list, which keeps its Taxonomy Relation.
			if ( 'multiTaxonomy' === $attr['queryTax'] && isset( $attr['advFilterEnable'] ) && $attr['advFilterEnable'] === true && isset( $attr['advRelation'] ) ) {
				$relation = 'AND' === $attr['advRelation'] || 'OR' === $attr['advRelation'] ? $attr['advRelation'] : $relation;
			}

			$var = array( 'relation' => $relation );
			foreach ( $tax_value as $val ) {
				$tax_name = $attr['queryTax'];
				$resolved = null;

				// The Advanced Filter sends its category and tag choices as `taxonomy###term`.
				if ( 'multiTaxonomy' === $tax_name && is_string( $val ) ) {
					$parts = explode( '###', $val );
					if ( isset( $parts[1] ) && in_array( $parts[0], array( 'category', 'post_tag' ), true ) ) {
						$resolved = '_all' === $parts[1] ? array() : array(
							'taxonomy' => $parts[0],
							'term'     => $parts[1],
						);
					}
				}

				/**
				 * Resolves a term of a taxonomy PostX does not know (the "Multiple Taxonomy" choice, added
				 * by PostX Pro). Return null to leave the term as it is, an empty array to skip it, or
				 * `array( 'taxonomy' => ..., 'term' => ... )`.
				 *
				 * @param array|null $resolved The Advanced Filter's own category or tag term, else null.
				 * @param string     $val      The stored term.
				 * @param array      $attr     Block attributes.
				 */
				$resolved = apply_filters( 'ultp_query_filter_term', $resolved, $val, $attr );
				if ( is_array( $resolved ) ) {
					if ( empty( $resolved['taxonomy'] ) || ! isset( $resolved['term'] ) ) {
						continue;
					}
					$tax_name = $resolved['taxonomy'];
					$val      = $resolved['term'];
				}
				$var[] = array(
					'taxonomy' => $tax_name,
					'field'    => 'slug',
					'terms'    => $val,
				);
			}
		}
		return isset( $var ) ? $var : array();
	}

	/**
	 * Registered types a public query or authorized editor picker may access.
	 *
	 * @param string|array $types Requested types.
	 * @param bool         $preview Allow non-public types only with their edit capability.
	 * @return string[] Allowed types.
	 */
	public function allowed_query_post_types( $types, $preview = false ) {
		if ( is_string( $types ) && '[' === substr( $types, 0, 1 ) ) {
			$types = json_decode( $types, true );
		}
		$types = is_string( $types ) ? array( $types ) : ( is_array( $types ) ? $types : array() );
		if ( in_array( 'any', $types, true ) ) {
			$types = get_post_types( array( 'exclude_from_search' => false ) );
		}
		$allowed = array();
		foreach ( $types as $type ) {
			$object = is_string( $type ) ? get_post_type_object( $type ) : null;
			if ( $object && ( is_post_type_viewable( $object ) || ( $preview && current_user_can( $object->cap->edit_posts ) ) ) ) {
				$allowed[] = $type;
			}
		}
		return array_values( array_unique( $allowed ) );
	}

	/**
	 * Apply access after every extension/early-return query path; empty selections fail closed.
	 *
	 * @param array $args Query arguments.
	 * @param bool  $preview Explicit privileged editor call, not a saved attribute.
	 * @return array Safe query arguments.
	 */
	public function restrict_query_access( $args, $preview = false ) {
		$args              = is_array( $args ) ? $args : array();
		$requested         = isset( $args['post_type'] ) ? $args['post_type'] : 'post';
		$types             = $this->allowed_query_post_types( $requested, $preview );
		$args['post_type'] = is_string( $requested ) && 'any' !== $requested && 1 === count( $types ) ? $types[0] : $types;
		if ( empty( $types ) ) {
			// A singular ID takes precedence over post__in in WP_Query.
			unset( $args['p'], $args['page_id'], $args['attachment_id'] );
			$args['post__in']            = array( 0 );
			$args['ignore_sticky_posts'] = true;
		}
		$requested_status = isset( $args['post_status'] ) ? (array) $args['post_status'] : array( 'publish' );
		$args['post_status'] = 'publish';
		if ( is_user_logged_in() && ! empty( $types ) ) {
			$private = in_array( 'private', $requested_status, true );
			foreach ( $types as $type ) {
				$object  = get_post_type_object( $type );
				$private = $private && current_user_can( $object->cap->read_private_posts );
			}
			if ( $private ) {
				$args['post_status'] = in_array( 'publish', $requested_status, true ) ? array( 'publish', 'private' ) : 'private';
			}
		}
		// Preserve native protected-post cards and sticky ordering; readers enforce content access.
		return $args;
	}

	/**
	 * Query frontend author choices using only authors of this site's public content.
	 *
	 * @param array        $args Existing role, selection, order and limit arguments.
	 * @param string|array $post_types Selected public content types, or all public types when empty.
	 * @return array Public author records.
	 */
	public function get_public_authors( $args = array(), $post_types = '' ) {
		if ( '' === $post_types || empty( $post_types ) ) {
			$post_types = array_values( get_post_types( array( 'public' => true ) ) );
		}
		$types                       = $this->allowed_query_post_types( $post_types );
		$args['blog_id']             = get_current_blog_id();
		$args['has_published_posts'] = $types;
		$args['fields']              = array( 'ID', 'display_name' );
		if ( empty( $types ) ) {
			return array();
		}
		return get_users( $args );
	}

	/**
	 * Public taxonomies and private taxonomies the editor is explicitly allowed to manage.
	 *
	 * @param string|array $taxonomies Requested names.
	 * @return array Allowed registered taxonomy names.
	 */
	public function allowed_picker_taxonomies( $taxonomies ) {
		$allowed = array();
		foreach ( (array) $taxonomies as $name ) {
			$taxonomy = is_string( $name ) ? get_taxonomy( $name ) : null;
			if ( ! $taxonomy ) {
				continue;
			}
			$can_assign = ( ! empty( $taxonomy->show_ui ) || ! empty( $taxonomy->show_in_rest ) ) && isset( $taxonomy->cap->assign_terms ) && current_user_can( $taxonomy->cap->assign_terms );
			if ( ! empty( $taxonomy->public ) || ! empty( $taxonomy->publicly_queryable ) || $can_assign || ( isset( $taxonomy->cap->edit_terms ) && current_user_can( $taxonomy->cap->edit_terms ) ) ) {
				$allowed[] = $name;
			}
		}
		return array_values( array_unique( $allowed ) );
	}

	/**
	 * Public author picker, scoped to this site's published public content.
	 *
	 * @param string $term Public name/nicename or author ID.
	 * @param bool   $exact Match an existing selected author without wildcards.
	 * @return array Public author records.
	 */
	public function search_public_authors( $term, $exact = false ) {
		$types = $this->allowed_query_post_types( array_values( get_post_types( array( 'public' => true ) ) ) );
		if ( empty( $types ) ) {
			return array();
		}
		return get_users(
			array(
				'blog_id'             => get_current_blog_id(),
				'number'              => 10,
				'fields'              => array( 'ID', 'display_name' ),
				'has_published_posts' => $types,
				'search'              => $exact ? $term : '*' . $term . '*',
				'search_columns'      => is_numeric( $term ) ? array( 'ID' ) : array( 'display_name', 'user_nicename' ),
			)
		);
	}

	/**
	 * Match core's inclusive post-type defaults for a custom-taxonomy archive.
	 *
	 * @param array $taxonomies Queried registered taxonomy names.
	 * @return string|array Native types, finalized by the query access policy afterward.
	 */
	private function archive_taxonomy_post_types( $taxonomies ) {
		$types = array();
		foreach ( get_post_types( array( 'exclude_from_search' => false ) ) as $type ) {
			$associated = 'attachment' === $type ? get_taxonomies_for_attachments() : get_object_taxonomies( $type );
			if ( array_intersect( $taxonomies, $associated ) ) {
				$types[] = $type;
			}
		}
		sort( $types );
		return empty( $types ) ? 'any' : ( 1 === count( $types ) ? $types[0] : $types );
	}

	/**
	 * Build a block query and apply its final resource-access policy.
	 *
	 * @since v.1.0.0
	 * @param array $attr Block query attributes.
	 * @param bool  $preview Explicit editor preview, never a saved block attribute.
	 * @return array Safe query arguments.
	 */
	public function get_query( $attr, $preview = false ) {
		if ( ! is_array( $attr ) ) {
			return $this->restrict_query_access( array( 'post_type' => array() ), $preview );
		}
		$builder       = isset( $attr['builder'] ) ? $attr['builder'] : '';
		$post_type     = isset( $attr['queryType'] ) ? $attr['queryType'] : 'post';
		/**
		 * The post type (or a JSON list of post types) a block queries. A callback may answer for a
		 * source of its own.
		 *
		 * @param string|array $post_type `queryType`, or `post`.
		 * @param array        $attr      Block attributes.
		 */
		$post_type = apply_filters( 'ultp_query_post_type', $post_type, $attr );

		if ( $post_type == 'archiveBuilder' && ( $builder || $this->is_builder( $builder ) ) ) {
			$archive_query = array();
			if ( $builder ) {
				$str = explode( '###', $builder );
				if ( isset( $str[0] ) ) {
					if ( $str[0] == 'taxonomy' ) {
						if ( isset( $str[1] ) && isset( $str[2] ) ) {
							$archive_query['post_type'] = $this->archive_taxonomy_post_types( array( $str[1] ) );
							$archive_query['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Needed by the block query; the user picks these filters.
								array(
									'taxonomy' => $str[1],
									'field'    => 'slug',
									'terms'    => $str[2],
								),
							);
						}
					} elseif ( $str[0] == 'author' ) {
						if ( isset( $str[1] ) ) {
							$archive_query['author'] = $str[1];
						}
					} elseif ( $str[0] == 'search' ) {
						if ( isset( $str[1] ) ) {
							$archive_query['s'] = $str[1];
						}
					} elseif ( $str[0] == 'date' ) {
						if ( isset( $str[1] ) ) {
							$all_date = explode( '-', $str[1] );
							if ( ! empty( $all_date ) ) {
								$arg = array();
								if ( isset( $all_date[0] ) ) {
									$arg['year'] = $all_date[0];
								}
								if ( isset( $all_date[1] ) ) {
									$arg['month'] = $all_date[1];
								}
								if ( isset( $all_date[2] ) ) {
									$arg['day'] = $all_date[2];
								}
								$archive_query['date_query'][] = $arg;
							}
						}
					}
				}
			} else {
				global $wp_query;
				$archive_query = $wp_query->query_vars;
				// Core leaves post_type empty for ordinary category/tag/author/home archives.
				if ( isset( $archive_query['post_type'] ) && '' === $archive_query['post_type'] ) {
					$archive_query['post_type'] = 'post';
					if ( is_tax() ) {
						// Core expands custom-taxonomy archives to the registered types of all queried taxonomies.
						$queried_taxonomies         = isset( $wp_query->tax_query->queried_terms ) ? array_keys( $wp_query->tax_query->queried_terms ) : array();
						$archive_query['post_type'] = $this->archive_taxonomy_post_types( $queried_taxonomies );
					}
				}
			}
			$archive_query['posts_per_page'] = isset( $attr['queryNumber'] ) ? $attr['queryNumber'] : 1;
			$archive_query['paged']          = isset( $attr['paged'] ) ? $attr['paged'] : 1;

			$archive_query['paged'] = ! wp_doing_ajax() && isset( $_GET[ $attr['blockId'] . '_page' ] ) && is_numeric( $_GET[ $attr['blockId'] . '_page' ] ) ? // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only page number for block pagination.
				intval( $_GET[ $attr['blockId'] . '_page' ] ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only page number for block pagination.
				$archive_query['paged'];

			if ( isset( $attr['queryOffset'] ) && $attr['queryOffset'] ) {
				$offset        = $this->get_offset( $attr['queryOffset'], $archive_query );
				$archive_query = array_merge( $archive_query, $offset );
			}

			// Include Remove from Version 2.5.4
			if ( isset( $attr['queryInclude'] ) && $attr['queryInclude'] ) {
				$_include = explode( ',', $attr['queryInclude'] );
				if ( is_array( $_include ) && count( $_include ) ) {
					$archive_query['post__in']            = isset( $archive_query['post__in'] ) ? array_merge( $archive_query['post__in'], $_include ) : $_include;
					$archive_query['ignore_sticky_posts'] = 1;
					$archive_query['orderby']             = 'post__in';
				}
			}

			if ( isset( $attr['queryExclude'] ) && $attr['queryExclude'] ) {
				$_exclude = ( substr( $attr['queryExclude'], 0, 1 ) === '[' ) ? $this->get_value( $this->decode_json_attr( $attr['queryExclude'], false, array() ) ) : explode( ',', $attr['queryExclude'] );
				if ( is_array( $_exclude ) && count( $_exclude ) ) {
					$archive_query['post__not_in'] = isset( $archive_query['post__not_in'] ) ? array_merge( $archive_query['post__not_in'], $_exclude ) : $_exclude; // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- Needed by the block query; the user picks these filters.
				}
			}

			if ( isset( $attr['querySticky'] ) && $attr['querySticky'] ) {
				if ( filter_var( $attr['querySticky'], FILTER_VALIDATE_BOOLEAN ) ) {
					$sticky                        = get_option( 'sticky_posts', array() );
					$archive_query['post__not_in'] = isset( $archive_query['post__not_in'] ) ? array_merge( $archive_query['post__not_in'], $sticky ) : $sticky; // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- Needed by the block query; the user picks these filters.
				}
			}
			$archive_query = apply_filters( 'ultp_query_stage', $archive_query, 'archive_unique', $attr );

			$archive_query['post_status'] = 'publish';
			if ( is_user_logged_in() ) {
				if ( current_user_can( 'read_private_posts' ) ) {
					$archive_query['post_status'] = array( 'publish', 'private' );
				}
			}
			// queryOrder
			if ( ! isset( $archive_query['orderby'] ) ) {
				$archive_query['orderby'] = isset( $attr['queryOrderBy'] ) ? $attr['queryOrderBy'] : 'date';
			}
			if ( ! isset( $archive_query['queryOrder'] ) ) {
				$archive_query['order'] = isset( $attr['queryOrder'] ) ? $attr['queryOrder'] : 'ASC';
			}

			// Quick Query Support for Builder
			if ( isset( $attr['queryQuick'] ) ) {
				if ( $attr['queryQuick'] != '' ) {
					$archive_query = ultimate_post()->get_quick_query( $attr, $archive_query );
				}
			}

			if ( ! isset( $attr['ajaxCall'] ) ) {
				if ( isset( $attr['filterShow'] ) && $attr['filterShow'] && $attr['filterShow'] != 'false' ) {
					if ( isset( $attr['filterType'] ) && isset( $attr['filterValue'] ) ) {
						$filterValue = strlen( $attr['filterValue'] ) > 2 ? $attr['filterValue'] : array();
						$filterValue = $this->decode_json_attr( $filterValue, false, array() );
						if ( is_array( $filterValue ) && count( $filterValue ) > 0 && $attr['filterType'] ) {
							$final = array( 'relation' => 'OR' );
							foreach ( $filterValue as $key => $val ) {
								$final[] = array(
									'taxonomy' => $attr['filterType'],
									'field'    => 'slug',
									'terms'    => $val,
								);
							}
							$archive_query['tax_query'] = $final; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Needed by the block query; the user picks these filters.
						}
					}
				}
			}
			// from v.3.0.7 - for search builder
			if ( is_search() ) {
				$archive_query['orderby'] = 'relevance';
			}

			return $this->restrict_query_access( apply_filters( 'ultp_archive_query', $archive_query ), $preview );
		}

		// When you use load more pagination block for a grid that did not have load more feature,
		// the incoming posts would not align properly with the blocks design and looked bad.
		// This fixes that.
		if (
			isset( $attr['paginationType'] ) &&
			isset( $attr['queryNumber2'] ) &&
			isset( $attr['notFirstLoad'] ) &&
			$attr['paginationType'] === 'loadMore' &&
			$attr['notFirstLoad']
		) {
			$query_number = $attr['queryNumber2'];
			// $query_number = isset($attr['queryNumber']) ? $attr['queryNumber'] : 3;
		} else {
			$query_number = isset( $attr['queryNumber'] ) ? $attr['queryNumber'] : 3;
		}

		$paged = isset( $attr['paged'] ) ? $attr['paged'] : 1;
		$paged = ! wp_doing_ajax() &&
			isset( $attr['blockId'] ) &&
			isset( $_GET[ $attr['blockId'] . '_page' ] ) && // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only page number for block pagination.
			is_numeric( $_GET[ $attr['blockId'] . '_page' ] ) ? // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only page number for block pagination.
			intval( $_GET[ $attr['blockId'] . '_page' ] ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only page number for block pagination.
			$paged;

		$query_args = array(
			'posts_per_page' => $query_number,
			'post_type'      => is_array( $post_type ) && ! empty( $post_type ) ? $post_type : ( 'archiveBuilder' == $post_type ? 'post' : ( is_string( $post_type ) ? is_array( json_decode( $post_type ) ) ? json_decode( $post_type ) : $post_type : $post_type ) ),
			'orderby'        => isset( $attr['queryOrderBy'] ) ? $attr['queryOrderBy'] : 'date',
			'order'          => isset( $attr['queryOrder'] ) ? $attr['queryOrder'] : 'desc',
			'paged'          => $paged,
			'post_status'    => 'publish',
		);

		// For Private Post 'private'.
		if ( is_user_logged_in() ) {
			if ( current_user_can( 'read_private_posts' ) ) {
				$query_args['post_status'] = array( 'publish', 'private' );
			}
		}

		/**
		 * Specific posts ("Specific Posts" and "Custom Selections" sources), added by PostX Pro.
		 * Return the finished query arguments to use them as they are, or null to carry on.
		 *
		 * @param array|null $specific   Null.
		 * @param array      $query_args Query arguments so far.
		 * @param array      $attr       Block attributes.
		 */
		$specific = apply_filters( 'ultp_query_specific_posts', null, $query_args, $attr );
		if ( is_array( $specific ) ) {
			return $this->restrict_query_access( $specific, $preview );
		}

		/**
		 * A step of the query PostX Pro adds to. `$stage` is `author` (authors left out),
		 * `exclude_terms` (terms left out), `unique` (posts already shown by a block of the same
		 * group) or `archive_unique` (the same, for archive templates).
		 *
		 * @param array  $query_args Query arguments so far.
		 * @param string $stage      Which step.
		 * @param array  $attr       Block attributes.
		 */
		$query_args = apply_filters( 'ultp_query_stage', $query_args, 'author', $attr );

		if ( isset( $attr['queryOrderBy'] ) && isset( $attr['metaKey'] ) ) {
			if ( $attr['queryOrderBy'] == 'meta_value_num' ) {
				$query_args['meta_key'] = $attr['metaKey']; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Needed by the block query; the user picks these filters.
			}
		}

		// Include Remove from Version 2.5.4.
		if ( isset( $attr['queryInclude'] ) && $attr['queryInclude'] ) {
			$_include = explode( ',', $attr['queryInclude'] );
			if ( is_array( $_include ) && count( $_include ) ) {
				$query_args['post__in']            = isset( $query_args['post__in'] ) ? array_merge( $query_args['post__in'], $_include ) : $_include;
				$query_args['ignore_sticky_posts'] = 1;
				$query_args['orderby']             = 'post__in';
			}
		}

		if ( isset( $attr['queryTax'] ) ) {
			if ( isset( $attr['queryTaxValue'] ) ) {
				$var = $this->getFilterQueryArgs( $attr );
				if ( isset( $var ) && count( $var ) > 1 ) {
					$query_args['tax_query'] = $var; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Needed by the block query; the user picks these filters.
				}
			}
		}

		$query_args = apply_filters( 'ultp_query_stage', $query_args, 'exclude_terms', $attr );

		if ( isset( $attr['queryExclude'] ) && $attr['queryExclude'] ) {
			$_exclude = ( substr( $attr['queryExclude'], 0, 1 ) === '[' ) ? $this->get_value( $this->decode_json_attr( $attr['queryExclude'], false, array() ) ) : explode( ',', $attr['queryExclude'] );
			if ( is_array( $_exclude ) && count( $_exclude ) ) {
				$query_args['post__not_in'] = isset( $query_args['post__not_in'] ) ? array_merge( $query_args['post__not_in'], $_exclude ) : $_exclude; // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- Needed by the block query; the user picks these filters.
			}
		}

		$query_args = apply_filters( 'ultp_query_stage', $query_args, 'unique', $attr );
		// exclude current post from Post blocks // v.2.9.6.
		if ( is_single() && get_the_ID() ) {
			$query_args['post__not_in'] = isset( $query_args['post__not_in'] ) ? array_merge( $query_args['post__not_in'], array( get_the_ID() ) ) : array( get_the_ID() ); // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- Needed by the block query; the user picks these filters.
		}

		if ( isset( $attr['queryQuick'] ) ) {
			if ( $attr['queryQuick'] != '' && $post_type != 'archiveBuilder' ) {
				$query_args = ultimate_post()->get_quick_query( $attr, $query_args );
			}
		}

		if ( isset( $attr['queryOffset'] ) && $attr['queryOffset'] ) {
			$offset     = $this->get_offset( $attr['queryOffset'], $query_args );
			$query_args = array_merge( $query_args, $offset );
		}

		if ( isset( $attr['queryAuthor'] ) && $attr['queryAuthor'] ) {
			$_include = ( substr( $attr['queryAuthor'], 0, 1 ) === '[' ) ? $this->get_value( $this->decode_json_attr( $attr['queryAuthor'], false, array() ) ) : explode( ',', $attr['queryAuthor'] );
			if ( is_array( $_include ) && count( $_include ) ) {
				$query_args['author__in'] = $_include;
			}
		}

		if ( isset( $attr['querySearch'] ) && $attr['querySearch'] ) {
			$query_args['s'] = $attr['querySearch'];
		}

		if ( isset( $attr['querySticky'] ) && $attr['querySticky'] ) {
			if ( filter_var( $attr['querySticky'], FILTER_VALIDATE_BOOLEAN ) ) {
				$sticky                     = get_option( 'sticky_posts', array() );
				$query_args['post__not_in'] = isset( $query_args['post__not_in'] ) ? array_merge( $query_args['post__not_in'], $sticky ) : $sticky; // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- Needed by the block query; the user picks these filters.
			}
		}

		if ( ! isset( $attr['ajaxCall'] ) ) {
			if ( isset( $attr['filterShow'] ) && $attr['filterShow'] && $attr['filterShow'] != 'false' ) {
				if ( isset( $attr['checkFilter'] ) && isset( $attr['queryTax'] ) && isset( $attr['queryTaxValue'] ) ) {
					$var = $this->getFilterQueryArgs( $attr );
					if ( isset( $var ) && count( $var ) > 1 ) {
						$query_args['tax_query'] = $var; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Needed by the block query; the user picks these filters.
					}
				} elseif ( isset( $attr['filterType'] ) && isset( $attr['filterValue'] ) ) {
					$filterValue = strlen( $attr['filterValue'] ) > 2 ? $attr['filterValue'] : array();
					$filterValue = is_array( $filterValue ) ? $filterValue : json_decode( $filterValue );
					if ( is_array( $filterValue ) && count( $filterValue ) > 0 && $attr['filterType'] ) {
						$final = array( 'relation' => 'OR' );
						foreach ( $filterValue as $key => $val ) {
							$final[] = array(
								'taxonomy' => $attr['filterType'],
								'field'    => 'slug',
								'terms'    => $val,
								// 'operator' => '=',
							);
						}
						$query_args['tax_query'] = $final; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Needed by the block query; the user picks these filters.
					}
				}
			}
		}

		// Advanced Filter Select Dropdown Default value.
		// only on initial page load.
		if (
			! wp_doing_ajax() &&
			isset( $attr['advFilterEnable'] ) &&
			$attr['advFilterEnable'] === true &&
			isset( $attr['defQueryTax'] ) &&
			count( $attr['defQueryTax'] ) > 0
		) {
			$tax_query = array(
				'relation' =>
				isset( $attr['advRelation'] ) &&
					( 'AND' === $attr['advRelation'] || 'OR' === $attr['advRelation'] )
					? $attr['advRelation'] : 'AND',
			);
			$is_valid  = false;

			foreach ( $attr['defQueryTax'] as $_ => $defQueryTaxValue ) {
				$term = explode( '###', $defQueryTaxValue );

				// A type PostX does not build itself (an add-on's, such as Custom Taxonomy) is answered by the
				// `ultp_advanced_filter_default_tax_query` filter. Without an answer nothing is added.
				if ( ! in_array( $term[0], array( 'category', 'tags', 'author' ), true ) ) {
					$q = apply_filters( 'ultp_advanced_filter_default_tax_query', null, $term, $attr );
					if ( is_array( $q ) && ! empty( $q ) ) {
						$tax_query[] = $q;
						$is_valid    = true;
					}
					continue;
				}

				// Validation
				if ( '_all' === $term[1] || empty( $term[1] ) ) {
					continue;
				}

				if ( 'author' === $term[0] ) {

					if ( ! is_numeric( $term[1] ) ) {
						continue;
					}

					$query_args['author__in'] = $term[1];
				} else {
					if ( ! is_numeric( $term[1] ) ) {
						continue;
					}

					$q = array(
						'field'    => 'term_id',
						'taxonomy' => 'tags' === $term[0] ? 'post_tag' : 'category',
						'terms'    => $term[1],
					);

					$tax_query[] = $q;
					$is_valid    = true;
				}
			}

			if ( $is_valid ) {
				$query_args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Needed by the block query; the user picks these filters.
			}
		}
		// $query_args['test'] = is_array( $post_type ) && ! empty( $post_type ) ? $post_type : ( 'archiveBuilder' === $post_type ? 'post' : ( is_string( $post_type ) ? json_decode( $post_type ) : $post_type ) );
		$query_args['wpnonce'] = wp_create_nonce( 'ultp-public-nonce' );

		return $this->restrict_query_access( apply_filters( 'ultp_frontend_query', $query_args ), $preview );
	}

	/**
	 * Get Page Offset
	 *
	 * @since v.1.0.0
	 * @param int   $queryOffset Offset .
	 * @param ARRAY |  $query_args Query .
	 * @return ARRAY
	 */
	function get_offset( $queryOffset, $query_args ) {
		$query = array();
		if ( $query_args['paged'] > 1 ) {
			$offset_post = wp_get_recent_posts( $query_args, OBJECT );
			if ( count( $offset_post ) > 0 ) {
				$offset = array();
				for ( $x = count( $offset_post ); $x > count( $offset_post ) - $queryOffset; $x-- ) {
					$offset[] = $offset_post[ $x - 1 ]->ID;
				}
				$query['post__not_in'] = $offset; // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- Needed by the block query; the user picks these filters.
			}
		} else {
			$query['offset'] = isset( $queryOffset ) ? $queryOffset : 0;
		}
		return $query;
	}


	/**
	 * Get Page Number
	 *
	 * @since v.1.0.0
	 * @param array $attr Attribute of the Query.
	 * @param int   $post_number Post Number.
	 * @return ARRAY
	 */
	public function get_page_number( $attr, $post_number ) {
		if ( $post_number > 0 ) {
			if ( isset( $attr['queryOffset'] ) && $attr['queryOffset'] ) {
				$post_number = $post_number - (int) $attr['queryOffset'];
			}
			$post_per_page = isset( $attr['queryNumber'] ) ? ( $attr['queryNumber'] ? $attr['queryNumber'] : 1 ) : 3;
			$pages         = ceil( $post_number / $post_per_page );
			return $pages ? $pages : 1;
		} else {
			return 1;
		}
	}


	/**
	 * Get Image Size
	 *
	 * @since v.1.0.0
	 * @return ARRAY
	 */
	public function get_image_size() {
		$sizes  = get_intermediate_image_sizes();
		$filter = array( 'full' => 'Full' );
		foreach ( $sizes as $value ) {
			$title = ucwords( str_replace( array( '_', '-', 'ultp' ), array( ' ', ' ', 'PostX' ), $value ) );
			switch ( $value ) {
				case 'thumbnail':
					$title = $title . ' [150x150]';
					break;
				case 'medium':
					$title = $title . ' [300x300]';
					break;
				case 'large':
					$title = $title . ' [1024x1024]';
					break;
				case 'ultp_layout_landscape_large':
					$title = $title . ' [1200x800]';
					break;
				case 'ultp_layout_landscape':
					$title = $title . ' [870x570]';
					break;
				case 'ultp_layout_portrait':
					$title = $title . ' [600x900]';
					break;
				case 'ultp_layout_square':
					$title = $title . ' [600x600]';
					break;
				default:
					break;
			}
			$filter[ $value ] = $title;
		}
		return $filter;
	}


	/**
	 * Get All PostType Registered
	 *
	 * @since v.1.0.0
	 * @return ARRAY
	 */
	public function get_post_type() {
		$filter    = apply_filters( 'ultp_public_post_type', true );
		$post_type = get_post_types( ( $filter ? array( 'public' => true ) : '' ), 'names' );
		return array_diff( $post_type, array( 'attachment' ) );
	}

	/**
	 * Get Pagination Url
	 *
	 * @since v.2.8.9
	 *
	 * @param int    $pageNo  The page number for pagination.
	 * @param STRING $baseUrl The base URL to append the pagination parameter to.
	 *
	 * @return STRING The generated pagination URL.
	 */
	public function generatePaginationUrl( $pageNo, $baseUrl ) {
		if ( $baseUrl ) {
			$url = $baseUrl . ( $pageNo == 1 ? '' : 'page/' . $pageNo );
		} else {
			$url = get_pagenum_link( $pageNo );
		}
		return $url;
	}
	/**
	 * Get Pagination HTML
	 *
	 * @since v.1.0.0
	 * @param INT    $pages           Number of pages.
	 * @param STRING $paginationNav   Pagination navigation type.
	 * @param STRING $paginationText  Pagination text.
	 * @param BOOL   $paginationAjax  Whether to use AJAX pagination.
	 * @param STRING $baseUrl         Base URL for pagination links.
	 * @param STRING $blockId         Block ID for pagination.
	 * @return STRING
	 */
	public function pagination( $pages = '', $paginationNav = '', $paginationText = '', $paginationAjax = true, $baseUrl = '', $blockId = '' ) {
		$html      = '';
		$showitems = 3;
		$paged     = is_front_page() ? get_query_var( 'page' ) : get_query_var( 'paged' );
		$paged     = $paged ? $paged : 1;

		$paged = ! wp_doing_ajax() &&
			isset( $_GET[ $blockId . '_page' ] ) && // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only page number for block pagination.
			is_numeric( $_GET[ $blockId . '_page' ] ) ? // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only page number for block pagination.
			intval( $_GET[ $blockId . '_page' ] ) : $paged; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only page number for block pagination.

		if ( $pages == '' ) {
			global $wp_query;
			$pages = $wp_query->max_num_pages;
			if ( ! $pages ) {
				$pages = 1;
			}
		}

		$data = ( $paged >= 3 ? array( ( $paged - 1 ), $paged, $paged + 1 ) : array( 1, 2, 3 ) );

		$paginationText = explode( '|', $paginationText );

		$prev_text = isset( $paginationText[0] ) ? esc_html( $paginationText[0] ) : esc_html__( 'Previous', 'ultimate-post' );
		$next_text = isset( $paginationText[1] ) ? esc_html( $paginationText[1] ) : esc_html__( 'Next', 'ultimate-post' );

		if ( 1 != $pages ) {
			$html        .= '<ul class="ultp-pagination">';
			$display_none = 'style="display:none"';
			if ( $paged > 1 || $paginationAjax && $pages > 1 ) {
				$html .= '<li class="ultp-prev-page-numbers" ' . ( $paged == 1 ? $display_none : '' ) . '><a href="' . esc_url( $this->generatePaginationUrl( $paged - 1, $baseUrl ) ) . '">' . wp_kses( ultimate_post()->get_svg_icon( 'leftAngle2' ), ultimate_post()->rendered_content_allowed_html() ) . ' ' . ( $paginationNav == 'textArrow' ? $prev_text : '' ) . '</a></li>';
			}
			if ( $pages > 3 ) {
				$html .= '<li class="ultp-first-pages" ' . ( $paged < 2 ? $display_none : '' ) . ' data-current="1"><a href="' . esc_url( $this->generatePaginationUrl( 1, $baseUrl ) ) . '">1</a></li>';
			}
			if ( $paged > 3 || $paginationAjax && $pages > 4 ) {
				$html .= '<li class="ultp-first-dot"' . ( $paged == 1 ? $display_none : '' ) . '><a href="#">...</a></li>';
			}
			foreach ( $data as $i ) {
				if ( $pages > 3 && $pages == $i ) {
					continue;
				}
				if ( $pages >= $i ) {
					$html .= ( $paged == $i ) ? '<li class="ultp-center-item pagination-active" data-current="' . esc_attr( $i ) . '"><a href="' . esc_url( $this->generatePaginationUrl( $i, $baseUrl ) ) . '">' . esc_html( $i ) . '</a></li>' : '<li class="ultp-center-item" data-current="' . esc_attr( $i ) . '" ' . ( ( $pages > 4 && $paged == 2 && $i == 1 && ! $paginationAjax ) ? $display_none : '' ) . '><a href="' . esc_url( $this->generatePaginationUrl( $i, $baseUrl ) ) . '">' . esc_html( $i ) . '</a></li>';
				}
			}
			if ( $pages != ( $paged + 2 ) || $paginationAjax && $pages > 4 ) {
				$html .= '<li class="ultp-last-dot" ' . ( $pages <= $paged + 1 ? $display_none : '' ) . '><a href="#">...</a></li>';
			}
			if ( $pages > 3 ) {
				$html .= '<li class="ultp-last-pages' . ( $paged == $pages ? ' pagination-active' : '' ) . '" data-current="' . esc_attr( $pages ) . '"><a href="' . esc_url( $this->generatePaginationUrl( $pages, $baseUrl ) ) . '">' . esc_html( $pages ) . '</a></li>';
			}
			if ( $paged != $pages ) {
				$html .= '<li class="ultp-next-page-numbers"><a href="' . esc_url( $this->generatePaginationUrl( $paged + 1, $baseUrl ) ) . '">' . ( $paginationNav == 'textArrow' ? $next_text : '' ) . wp_kses( ultimate_post()->get_svg_icon( 'rightAngle2' ), ultimate_post()->rendered_content_allowed_html() ) . '</a></li>';
			}
			$html .= '</ul>';
		}
		return $html;
	}

	/**
	 * Svg Icon Source
	 *
	 * The file is read once per request and cached by icon name. The markup is filtered with wp_kses()
	 * against svg_icon_allowed_html() before it is returned, so it is escaped where it is produced.
	 *
	 * @since v.1.0.0
	 * @param STRING $ultp_icons Name of the SVG icon file (without extension).
	 * @return STRING Escaped SVG markup, or '' when the icon does not exist.
	 */
	public function get_svg_icon( $ultp_icons = '' ) {
		static $cache = array();
		if ( ! $ultp_icons ) {
			return '';
		}
		$key = is_scalar( $ultp_icons ) ? (string) $ultp_icons : null;
		if ( null !== $key && isset( $cache[ $key ] ) ) {
			return $cache[ $key ];
		}
		$svg  = '';
		$icon = $this->svg_icon_compatibility( $ultp_icons );
		// Only names of the shipped icon files, so the name cannot point outside assets/img/iconpack/.
		if ( is_string( $icon ) && preg_match( '/^[A-Za-z0-9_-]+$/', $icon ) ) {
			$raw = $this->get_path_file_contents( ULTP_PATH . 'assets/img/iconpack/' . $icon . '.svg' );
			if ( is_string( $raw ) ) {
				$svg = wp_kses( $raw, $this->svg_icon_allowed_html() );
			}
		}
		if ( null !== $key ) {
			$cache[ $key ] = $svg;
		}
		return $svg;
	}

	/**
	 * Exactly the elements and attributes the files in assets/img/iconpack/ use (262 files: svg, path
	 * and circle). Nothing else is allowed, so no script, style, href or event attribute can pass.
	 * The JSX spellings (strokewidth, fillrule, …) are in a few shipped files; browsers ignore them,
	 * and they are kept so the icons print as shipped.
	 *
	 * @since 5.1.5
	 * @return ARRAY Tag name => allowed attributes.
	 */
	private function svg_icon_allowed_html() {
		return array(
			'svg'    => array(
				'xmlns'   => true,
				'viewbox' => true,
				'fill'    => true,
			),
			'path'   => array(
				'd'               => true,
				'fill'            => true,
				'fill-rule'       => true,
				'fillrule'        => true,
				'clip-rule'       => true,
				'cliprule'        => true,
				'stroke'          => true,
				'stroke-width'    => true,
				'strokewidth'     => true,
				'stroke-linecap'  => true,
				'strokelinecap'   => true,
				'stroke-linejoin' => true,
				'strokelinejoin'  => true,
			),
			'circle' => array(
				'cx'              => true,
				'cy'              => true,
				'r'               => true,
				'stroke'          => true,
				'stroke-width'    => true,
				'stroke-linecap'  => true,
				'stroke-linejoin' => true,
			),
		);
	}

	/**
	 * SVG elements and the presentation attributes inline SVG in saved block content uses. Used by
	 * rendered_content_allowed_html(). Holds no script, style, foreignObject or on* attribute.
	 *
	 * @return ARRAY Tag name => allowed attributes.
	 */
	private function svg_allowed_html() {
		static $svg = null;
		if ( null !== $svg ) {
			return $svg;
		}
		$svg_attributes = array_fill_keys(
			array(
				'xmlns', 'xmlns:xlink', 'version', 'viewbox', 'preserveaspectratio', 'width', 'height', 'x', 'y',
				'x1', 'y1', 'x2', 'y2', 'cx', 'cy', 'r', 'rx', 'ry', 'fx', 'fy', 'd', 'points', 'transform',
				'fill', 'fill-rule', 'fill-opacity', 'stroke', 'stroke-width', 'stroke-linecap', 'stroke-linejoin',
				'stroke-miterlimit', 'stroke-dasharray',
				// JSX spellings found in shipped iconpack files; browsers ignore them, kept so icons print as shipped.
				'strokemiterlimit', 'strokewidth', 'strokelinecap', 'strokelinejoin', 'cliprule', 'fillrule', 'stroke-dashoffset', 'stroke-opacity',
				'opacity', 'clip-path', 'clip-rule', 'clippathunits', 'mask', 'maskunits', 'filter', 'offset',
				'stop-color', 'stop-opacity', 'gradientunits', 'gradienttransform', 'spreadmethod', 'patternunits',
				'patterntransform', 'href', 'xlink:href', 'focusable', 'enable-background', 'overflow', 'display',
				'visibility', 'color', 'vector-effect', 'font-family', 'font-size', 'font-weight', 'text-anchor',
				'dominant-baseline', 'dx', 'dy', 'xml:space', 'data-original', 'stddeviation', 'in', 'in2',
				'result', 'mode', 'values', 'type', 'flood-color', 'flood-opacity', 'operator',
			),
			true
		);

		$svg = array();
		foreach ( array(
			'svg', 'g', 'path', 'circle', 'ellipse', 'rect', 'line', 'polyline', 'polygon', 'defs', 'clippath',
			'mask', 'lineargradient', 'radialgradient', 'stop', 'use', 'symbol', 'title', 'desc', 'text', 'tspan',
			'pattern', 'filter', 'fegaussianblur', 'feoffset', 'feblend', 'fecolormatrix', 'feflood',
			'fecomposite', 'femerge', 'femergenode',
		) as $svg_tag ) {
			$svg[ $svg_tag ] = $svg_attributes;
		}
		return $svg;
	}

	/**
	 * Term link escaped for an href, or '' when there is none (get_term_link() can return a WP_Error).
	 *
	 * @since 5.1.2
	 * @param int|object $term     Term ID or object.
	 * @param string     $taxonomy Optional taxonomy.
	 * @return string
	 */
	public function esc_term_link( $term, $taxonomy = '' ) {
		$link = get_term_link( $term, $taxonomy );
		return is_wp_error( $link ) ? '' : esc_url( $link );
	}

	/**
	 * The CSS declarations of a style="…" attribute an add-on returned through a filter, not escaped.
	 * The caller must escape them with esc_attr() where it prints them. Anything that is not a single
	 * style attribute gives null.
	 *
	 * @since 5.1.2
	 * @param mixed $attribute Filter result, such as style="color: red".
	 * @return string|null The declarations (may be ''), or null when the input is not accepted.
	 */
	public function filtered_style_value( $attribute ) {
		if ( is_string( $attribute ) && preg_match( '/^\s*style="([^"]*)"\s*$/', $attribute, $match ) ) {
			return $match[1];
		}
		return null;
	}

	/**
	 * A style="…" attribute an add-on returned through a filter, with its value escaped again.
	 * Anything that is not a single style attribute is dropped.
	 *
	 * @since 5.1.2
	 * @param mixed $attribute Filter result, such as style="color: red".
	 * @return string The style attribute, or ''.
	 */
	public function esc_filtered_style( $attribute ) {
		$value = $this->filtered_style_value( $attribute );
		return null === $value ? '' : 'style="' . esc_attr( $value ) . '"';
	}

	/**
	 * Whether a post block draws its inline filter bar (`blocks/template/filter.php`).
	 *
	 * On when `filterShow` is set and the block has a taxonomy to filter by. Off while the Advanced Filter
	 * block is on (`advFilterEnable`): that block carries the filter, so the inline one is not printed.
	 *
	 * @param array $attr Block attributes.
	 * @return bool
	 */
	public function inline_filter_on( $attr ) {
		if ( empty( $attr['filterShow'] ) || ! empty( $attr['advFilterEnable'] ) ) {
			return false;
		}
		$query_type = isset( $attr['queryType'] ) ? $attr['queryType'] : '';
		return ! in_array( $query_type, array( 'posts', 'customPosts' ), true );
	}

	/**
	 * Whether a post block draws its inline pagination of a type (`loadMore` or `pagination`), or of any type when
	 * `$type` is empty.
	 *
	 * On when `paginationShow` is set and the Advanced Pagination block is off (`advPaginationEnable`): that block
	 * carries the pagination, so the inline one is not printed. The hidden markup of the Advanced Pagination block
	 * (`blocks/template/pagination_block.php`) is not affected by this.
	 *
	 * @param array  $attr Block attributes.
	 * @param string $type `navigation`, `loadMore`, `pagination`, or empty for any type.
	 * @return bool
	 */
	public function inline_pagination_on( $attr, $type = '' ) {
		if ( empty( $attr['paginationShow'] ) || ! empty( $attr['advPaginationEnable'] ) || ! isset( $attr['paginationType'] ) ) {
			return false;
		}
		return '' === $type || $type === $attr['paginationType'];
	}

	/**
	 * Whether a post block draws its inline navigation (`paginationType` `navigation`) above the posts
	 * (`navPosition` `topRight`, `$top` true) or below them (`$top` false).
	 *
	 * Same gate as `inline_pagination_on()`.
	 *
	 * @param array $attr Block attributes.
	 * @param bool  $top  True for the navigation above the posts, false for the one below them.
	 * @return bool
	 */
	public function inline_navigation_on( $attr, $top ) {
		if ( ! $this->inline_pagination_on( $attr, 'navigation' ) ) {
			return false;
		}
		$at_top = isset( $attr['navPosition'] ) && 'topRight' === $attr['navPosition'];
		return $top === $at_top;
	}

	/**
	 * Markup returned through a filter. Our own default is already escaped and is kept as it is;
	 * markup an add-on returned instead is filtered by wp_kses() against rendered_content_allowed_html().
	 *
	 * @since 5.1.2
	 * @param mixed  $filtered Filter result.
	 * @param string $default  The escaped markup that was passed to the filter.
	 * @return string
	 */
	public function esc_filtered_markup( $filtered, $default ) {
		if ( $filtered === $default ) {
			return $default;
		}
		return is_string( $filtered ) ? wp_kses( $filtered, $this->rendered_content_allowed_html() ) : '';
	}

	/**
	 * The data-* attributes of an attribute string an add-on returned through a filter, each value
	 * escaped again. Anything else is dropped. A data-* attribute may follow the closing quote of the one
	 * before it without a space.
	 *
	 * @since 5.1.2
	 * @param mixed $attributes Such as ` data-pages="3" data-blockid="b1"`.
	 * @return string The data-* attributes, each with a leading space, or ''.
	 */
	public function esc_data_attributes( $attributes ) {
		$out = '';
		if ( is_string( $attributes ) && preg_match_all( '/(?:^|\s|(?<=["\']))data-([a-z0-9_-]+)\s*=\s*(?:"([^"]*)"|\'([^\']*)\')/i', $attributes, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$value = isset( $match[3] ) ? $match[3] : $match[2];
				$out  .= ' data-' . $match[1] . '="' . esc_attr( $value ) . '"';
			}
		}
		return $out;
	}

	/**
	 * Get Taxonomy Lists
	 *
	 * @since v.1.0.0
	 * @param STRING|ARRAY $prams Taxonomy slug or arguments array.
	 * @return ARRAY
	 */
	public function taxonomy( $prams = 'category' ) {
		$data = array();

		$terms = get_terms(
			is_string( $prams ) ? array(
				'taxonomy'   => $prams,
				'hide_empty' => false,
			) : $prams
		);
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $val ) {
				$data[ urldecode_deep( $val->slug ) ] = $val->name;
			}
		}
		return $data;
	}


	/**
	 * Get Taxonomy Lists
	 *
	 * @since v.2.9.0
	 * @param STRING $tax_slug Taxonomy slug.
	 * @param INT    $number   Number of terms to retrieve.
	 * @return ARRAY
	 */
	public function builder_preview( $tax_slug, $number ) {
		$data      = array();
		$term_data = get_terms(
			array(
				'taxonomy'   => $tax_slug,
				'hide_empty' => true,
				'number'     => $number,
				'parent'     => 0,
			)
		);
		if ( ! empty( $term_data ) ) {
			foreach ( $term_data as $terms ) {
				$data[] = $this->get_tax_data( $terms );
			}
		}
		return $data;
	}

	/**
	 * Get Taxonomy Data Lists
	 *
	 * @since v.1.0.0
	 * @param object $terms Taxonomy Object.
	 * @return ARRAY
	 */
	public function get_tax_data( $terms ) {
		$temp         = array();
		$thumbnail_id = $this->get_term_image_id( $terms );
		$image_src    = array();
		if ( $thumbnail_id ) {
			$image_sizes = ultimate_post()->get_image_size();
			foreach ( $image_sizes as $key => $value ) {
				$img_src = wp_get_attachment_image_src( $thumbnail_id, $key, false );
				if ( $img_src ) {
					$image_src[ $key ] = $img_src[0];
				}
			}
		}
		$temp['url']          = get_term_link( $terms );
		$temp['thumbnail_id'] = $thumbnail_id ? (string) $thumbnail_id : '';
		$temp['name']         = $terms->name;
		$temp['desc']         = $terms->description;
		$temp['count']        = $terms->count;
		$temp['image']        = $image_src;
		$color                = $this->get_term_color( $terms );
		$temp['color']        = $color ? $color : '#037fff';
		return $temp;
	}

	/**
	 * Image attachment ID of a term. Nothing in PostX saves it: the value comes from the `ultp_term_image_id` filter.
	 *
	 * @param WP_Term $term Term.
	 * @return INT Attachment ID, or 0.
	 */
	public function get_term_image_id( $term ) {
		return absint( apply_filters( 'ultp_term_image_id', 0, $term ) );
	}

	/**
	 * Colour of a term. Nothing in PostX saves it: the value comes from the `ultp_term_color` filter.
	 *
	 * @param WP_Term $term Term.
	 * @return STRING Colour, or ''.
	 */
	public function get_term_color( $term ) {
		return apply_filters( 'ultp_term_color', '', $term );
	}


	public function get_category_data( $catSlug, $number = 40, $type = '', $tax_slug = 'category', $archiveBuilder = '' ) {
		$data = array();
		if ( $type == 'child' ) {
			if ( $archiveBuilder ) {
				$data = $this->builder_preview( $tax_slug, $number );
			} else {
				if ( is_category() ) {
					$catSlug = array( get_queried_object()->slug );
				}
				$image = '';
				if ( ! empty( $catSlug ) ) {
					foreach ( $catSlug as $cat ) {
						$parent_term = get_term_by( 'slug', $cat, $tax_slug );
						if ( ! empty( $parent_term ) ) {
							$term_data = get_terms(
								array(
									'taxonomy'   => $tax_slug,
									'hide_empty' => true,
									'number'     => $number,
									'parent'     => $parent_term->term_id,
								)
							);
							if ( ! empty( $term_data ) && ! is_wp_error( $term_data ) ) { // get_terms() gives a WP_Error for an unknown taxonomy.
								foreach ( $term_data as $terms ) {
									$data[] = $this->get_tax_data( $terms );
								}
							}
						}
					}
				}
			}
		} elseif ( $type == 'parent' ) {
			$term_data = is_category() ? array( get_term( get_queried_object()->parent, 'category' ) ) : get_terms(
				array(
					'taxonomy'   => $tax_slug,
					'hide_empty' => true,
					'number'     => $number,
					'parent'     => 0,
				)
			);
			if ( $archiveBuilder && ! empty( $term_data ) && ! is_wp_error( $term_data ) ) {
				$term_data = array( $term_data[0] );
			}
			if ( ! empty( $term_data ) && ! is_wp_error( $term_data ) ) {
				foreach ( $term_data as $terms ) {
					if ( ! empty( $terms->term_id ) ) {
						$data[] = $this->get_tax_data( $terms );
					}
				}
			}
		} elseif ( $type == 'custom' ) {
			foreach ( $catSlug as $cat ) {
				$terms = get_term_by( 'slug', $cat, $tax_slug );
				if ( ! empty( $terms ) ) {
					$data[] = $this->get_tax_data( $terms );
				}
			}
		} elseif ( $type == 'immediate_child' ) {
			if ( $archiveBuilder ) {
				$data = $this->builder_preview( $tax_slug, $number );
			} else {
				$id_ = get_queried_object();
				if ( isset( $id_->term_id ) ) {
					$term_data = get_terms(
						array(
							'taxonomy'   => $tax_slug,
							'hide_empty' => true,
							'number'     => 100,
							'parent'     => $id_->term_id,
						)
					);
					if ( ! empty( $term_data ) && ! is_wp_error( $term_data ) ) {
						foreach ( $term_data as $terms ) {
							$data[] = $this->get_tax_data( $terms );
						}
					}
				}
			}
		} elseif ( $type == 'allchild' ) {
			if ( $archiveBuilder ) {
				$data = $this->builder_preview( $tax_slug, $number );
			} else {
				$id_ = get_queried_object();
				if ( isset( $id_->term_taxonomy_id ) ) {
					$termchildren = get_term_children( $id_->term_taxonomy_id, $tax_slug );
					if ( ! is_wp_error( $termchildren ) ) { // An unknown taxonomy gives a WP_Error.
						foreach ( $termchildren as $key => $value ) {
							$terms  = get_term_by( 'id', $value, $tax_slug );
							$data[] = $this->get_tax_data( $terms );
						}
					}
				}
			}
		} elseif ( $type == 'current_level' ) {
			if ( $archiveBuilder ) {
				$data = $this->builder_preview( $tax_slug, $number );
			} else {
				$id_ = get_queried_object();
				if ( isset( $id_->parent ) ) {
					$term_data = get_terms(
						array(
							'taxonomy'   => $tax_slug,
							'hide_empty' => true,
							'number'     => $number,
							'parent'     => $id_->parent,
						)
					);
					if ( ! empty( $term_data ) && ! is_wp_error( $term_data ) ) {
						foreach ( $term_data as $terms ) {
							if ( $terms->term_id != $id_->term_id ) {
								$data[] = $this->get_tax_data( $terms );
							}
						}
					}
				}
			}
		} else {
			$term_data = get_terms(
				array(
					'taxonomy'   => $tax_slug,
					'hide_empty' => true,
					'number'     => $number,
				)
			);
			if ( ! empty( $term_data ) && ! is_wp_error( $term_data ) ) {
				foreach ( $term_data as $terms ) {
					$data[] = $this->get_tax_data( $terms );
				}
			}
		}
		return $data;
	}


	/**
	 * Get Next Previous HTML.
	 *
	 * @since v.1.0.0
	 * @return STRING
	 */
	public function next_prev() {
		$html  = '';
		$html .= '<ul>';
		$html .= '<li>';
		$html .= '<a class="ultp-prev-action ultp-disable" href="#">';
		$html .= wp_kses( ultimate_post()->get_svg_icon( 'leftAngle2' ), ultimate_post()->rendered_content_allowed_html() ) . '<span class="screen-reader-text">' . esc_html__( 'Previous', 'ultimate-post' ) . '</span>';
		$html .= '</a>';
		$html .= '</li>';
		$html .= '<li>';
		$html .= '<a class="ultp-next-action">';
		$html .= wp_kses( ultimate_post()->get_svg_icon( 'rightAngle2' ), ultimate_post()->rendered_content_allowed_html() ) . '<span class="screen-reader-text">' . esc_html__( 'Next', 'ultimate-post' ) . '</span>';
		$html .= '</a>';
		$html .= '</li>';
		$html .= '</ul>';
		return $html;
	}


	/**
	 * Get Loading HTML
	 *
	 * @since v.1.0.0
	 * @return STRING
	 */
	public function postx_loading() {
		$html  = '';
		$style = ultimate_post()->get_setting( 'preloader_style' );
		if ( $style == 'style2' ) {
			$html .= '<div class="ultp-loading-spinner" style="width:100%;height:100%"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div>'; // ultp-block-items-wrap
		} else {
			$html .= '<div class="ultp-loading-blocks" style="width:100%;height:100%;"><div style="left: 0;top: 0;animation-delay:0s;"></div><div style="left: 21px;top: 0;animation-delay:0.125s;"></div><div style="left: 42px;top: 0;animation-delay:0.25s;"></div><div style="left: 0;top: 21px;animation-delay:0.875s;"></div><div style="left: 42px;top: 21px;animation-delay:0.375s;"></div><div style="left: 0;top: 42px;animation-delay:0.75s;"></div><div style="left: 42px;top: 42px;animation-delay:0.625s;"></div><div style="left: 21px;top: 42px;animation-delay:0.5s;"></div></div>';
		}
		return '<div class="ultp-loading">' . $html . '</div>';
	}


	/**
	 * Get Filter HTML
	 *
	 * @since v.1.0.0
	 * @param STRING       $filterText        Filter Text.
	 * @param STRING       $filterType        Filter Type.
	 * @param ARRAY|STRING $filterValue Filter Value.
	 * @param STRING       $filterMobileText  Filter Mobile Text.
	 * @param BOOL         $filterMobile        Enable Filter Mobile.
	 * @return STRING
	 */
	public function filter_html( $filterText = '', $filterType = '', $filterValue = '[]', $filterMobileText = '...', $filterMobile = true ) {
		$html  = '';
		$html .= '<ul ' . ( $filterMobile ? 'class="ultp-flex-menu"' : '' ) . ' data-name="' . ( $filterMobileText ? esc_attr( $filterMobileText ) : '&nbsp;' ) . '">';
		$cat   = $this->taxonomy( $filterType );
		if ( $filterText ) {
			// Already passed through wp_kses() by the caller, which keeps the allowed HTML.
			$html .= '<li class="filter-item"><a class="filter-active" data-taxonomy="" href="#">' . wp_kses( $filterText, $this->ultp_allowed_html_tags() ) . '</a></li>';
		}
		if ( $filterValue ) {
			$filterValue = strlen( $filterValue ) > 2 ? $filterValue : array();
			$filterValue = is_array( $filterValue ) ? $filterValue : json_decode( $filterValue );
			if ( is_array( $filterValue ) && count( $filterValue ) ) {
				foreach ( $filterValue as $val ) {
					$val   = isset( $val->value ) ? $val->value : $val;
					if ( ! is_scalar( $val ) ) {
						continue;
					}
					$html .= '<li class="filter-item"><a data-taxonomy="' . esc_attr( $val ) . '" href="#">' . esc_html( isset( $cat[ $val ] ) ? $cat[ $val ] : $val ) . '</a></li>';
				}
			}
		}
		$html .= '</ul>';
		return $html;
	}


	/**
	 * Get the request body for the template service
	 *
	 * PostX Pro adds what it needs through the `ultp_template_request_body` filter.
	 *
	 * @param ARRAY  $body    Request body.
	 * @param STRING $context Which request it is: builder_premade, starter_posts, starter_content or single_page.
	 * @return ARRAY
	 */
	public function get_template_request_body( $body, $context ) {
		$filtered = apply_filters( 'ultp_template_request_body', $body, $context );
		$body     = is_array( $filtered ) ? $filtered : $body;

		return $body;
	}


	/**
	 * Validate if the template service URL is from an allowed domain over HTTPS (SSRF protection).
	 *
	 * @param STRING $url The URL to validate.
	 * @return BOOL True if allowed, false otherwise.
	 */
	public function is_allowed_template_endpoint( $url ) {
		$allowed_domains = array(
			'postxkit.wpxpo.com',
		);

		$parsed_url = wp_parse_url( $url );
		if ( ! $parsed_url || empty( $parsed_url['host'] ) ) {
			return false;
		}

		// Template-service requests are sent over HTTPS only.
		if ( empty( $parsed_url['scheme'] ) || 'https' !== strtolower( $parsed_url['scheme'] ) ) {
			return false;
		}

		$host = strtolower( $parsed_url['host'] );

		foreach ( $allowed_domains as $domain ) {
			$domain = strtolower( $domain );

			// Exact match OR valid subdomain
			if ( $host === $domain || substr( $host, - ( strlen( $domain ) + 1 ) ) === '.' . $domain ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Excerpt of a post in a block.
	 *
	 * An add-on may supply the text through the filter `ultp_post_excerpt`: `$html` (null by
	 * default; a string answer is used as it is), `$post_id`, and `$context` with `seo_meta`,
	 * `full_excerpt` and `excerpt_limit` (the arguments of this method).
	 *
	 * @since v.2.4.3
	 * @param INT $post_id Post ID.
	 * @param INT $show_seo_meta Passed on to the filter.
	 * @param INT $show_full_excerpt Show full excerpt flag.
	 * @param INT $excerpt_limit Excerpt word limit.
	 * @return STRING Excerpt.
	 */
	public function get_excerpt( $post_id = 0, $show_seo_meta = 0, $show_full_excerpt = 0, $excerpt_limit = 55 ) {
		$html = apply_filters(
			'ultp_post_excerpt',
			null,
			$post_id,
			array(
				'seo_meta'      => $show_seo_meta,
				'full_excerpt'  => $show_full_excerpt,
				'excerpt_limit' => $excerpt_limit,
			)
		);
		if ( is_string( $html ) ) {
			return $html;
		}
		if ( $show_full_excerpt == 0 ) {
			return ultimate_post()->excerpt( $post_id, $excerpt_limit );
		}
		return get_the_excerpt();
	}


	/**
	 * Get embedded video HTML.
	 *
	 * @since v.1.0.0
	 * @param STRING         $url Video URL.
	 * @param BOOL           $autoPlay Whether to autoplay the video.
	 * @param BOOL           $loop Whether to loop the video.
	 * @param BOOL           $mute Whether to mute the video.
	 * @param BOOL           $playback Whether to show playback controls.
	 * @param STRING         $preload Preload attribute value.
	 * @param ARRAY | string $poster Poster image for the video.
	 * @param BOOL           $inline Whether to play inline.
	 * @param ARRAY | string $size Size for oEmbed.
	 * @return STRING | false Video embed HTML or false on failure.
	 */
	public function get_embeded_video( $url, $autoPlay, $loop, $mute, $playback, $preload, $poster, $inline, $size, $location = '' ) {
		$vidAutoPlay = $vidloop = $vidloop = $vidmute = $vidplayback = $vidPoster = $vidInline = '';

		$video_type = '';
		// Determine video type
		$video_type = 'local';
		if ( strpos( $url, 'youtube.com' ) !== false || strpos( $url, 'youtu.be' ) !== false ) {
			$video_type = 'youtube';
		} elseif ( strpos( $url, 'vimeo.com' ) !== false ) {
			$video_type = 'vimeo';
		}

		// Get poster URL
		$poster_url = '';
		if ( is_array( $poster ) && isset( $poster['url'] ) ) {
			$poster_url = $poster['url'];
		} elseif ( is_string( $poster ) ) {
			$poster_url = $poster;
		}

		// Prepare size attributes
		$width  = is_array( $size ) && isset( $size['width'] ) ? $size['width'] : 560;
		$height = is_array( $size ) && isset( $size['height'] ) ? $size['height'] : 315;

		$video_id = '';

		if ( $url ) {
			$regex = '/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=))((\w|-){11})/';
			if ( preg_match( $regex, $url, $matches ) ) {
				$video_id = $matches[1];
			}
		}

		// Create data attributes for lazy loading
		$data_attrs = array(
			'data-video-url'   => is_scalar( $url ) ? esc_attr( $url ) : '',
			'data-video-type'  => is_scalar( $video_type ) ? esc_attr( $video_type ) : '',
			'data-autoplay'    => ! empty( $autoPlay ) ? '1' : '0',
			'data-loop'        => ! empty( $loop ) ? '1' : '0',
			'data-mute'        => ! empty( $mute ) ? '1' : '0',
			'data-controls'    => ! empty( $playback ) ? '1' : '0',
			'data-preload'     => is_scalar( $preload ) ? esc_attr( $preload ) : '',
			'data-poster'      => is_scalar( $poster_url ) ? esc_attr( $poster_url ) : '',
			'data-playsinline' => ! empty( $inline ) ? '1' : '0',
			'data-width'       => is_scalar( $width ) ? esc_attr( $width ) : '',
			'data-height'      => is_scalar( $height ) ? esc_attr( $height ) : '',
			'data-video-id'    => is_scalar( $video_id ) ? esc_attr( $video_id ) : '',
		);

		$data_string = implode(
			' ',
			array_map(
				function ( $key, $value ) {
					return $key . '="' . $value . '"';
				},
				array_keys( $data_attrs ),
				$data_attrs
			)
		);

		$html = '<div class="ultp-video-wrapper ultp-embaded-video"' . $data_string . '></div>';

		if ( $location == '' ) {
			return $html;
		}

		if ( $autoPlay ) {
			$vidAutoPlay = 'autoplay';
		}
		if ( $poster ) {
			$vidPoster = 'poster="' . esc_url( $poster_url ) . '"';
		}
		if ( $loop ) {
			$vidloop = 'loop';
		}
		if ( $mute ) {
			$vidmute = 'muted';
		}
		if ( $playback ) {
			$vidplayback = 'controls';
		}
		if ( $inline ) {
			$vidInline = 'playsinline';
		}
		if ( ! empty( $url ) ) {
			$embeded = wp_oembed_get( $url, $size );
			if ( $embeded == false ) {
				$format = '';
				$url    = strtolower( $url );
				if ( strpos( $url, '.mp4' ) ) {
					$format = 'mp4';
				} elseif ( strpos( $url, '.ogg' ) ) {
					$format = 'ogg';
				} elseif ( strpos( $url, '.webm' ) ) {
					$format = 'WebM';
				}
				$embeded = '<video 
                ' . $vidloop . '
                ' . $vidmute . '
                ' . $vidplayback . '
                preload="' . esc_attr( $preload ) . '"
                ' . $vidAutoPlay . '
                ' . $vidPoster . '
                ' . $vidInline . '
                class="ultp-video-html"
            ><source src="' . esc_url( $url ) . '" type="video/' . esc_attr( $format ) . '">' . esc_html__( 'Your browser does not support the video tag.', 'ultimate-post' ) . '</video>';
				return '<div class="ultp-video-wrapper">' . wp_kses( $embeded, ultimate_post()->rendered_content_allowed_html() ) . '</div>';
			}
			return '<div class="ultp-video-wrapper ultp-embaded-video">' . wp_kses( $embeded, ultimate_post()->rendered_content_allowed_html() ) . '</div>';
		} else {
			return false;
		}
	}

	/**
	 * Get Public taxonomy Lists
	 *
	 * @since v.2.7.0
	 * @return ARRAY | Taxonomy Lists as array
	 */
	public function get_taxonomy_list() {
		$taxonomy = get_taxonomies( array( 'public' => true ) );
		return empty( $taxonomy ) ? array() : array_keys( $taxonomy );
	}

	/**
	 * Content Print
	 *
	 * @since v.2.7.0
	 * @param int    $post_id      Post ID.
	 * @param string $builder_type Builder type.
	 * @return void | Content of the Post
	 */
	public function get_post_content( $post_id, $builder_type = '' ) {
		$content_post = get_post( $post_id );
		$content      = $content_post->post_content;
		if ( $builder_type == 'divi' || $builder_type == 'elementor' ) {
			$content = apply_filters( 'the_content', $content ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core hook.
		} else {
			global $wp_embed;
			$content = $wp_embed->autoembed( $content );
			$content = do_blocks( $content );
			$content = do_shortcode( $content );
		}
		$content = str_replace( ']]>', ']]&gt;', $content );
		$content = preg_replace( '%<p>&nbsp;\s*</p>%', '', $content );
		$content = preg_replace( '/^(?:<br\s*\/?>\s*)+/', '', $content );
		echo wp_kses( $content, $this->rendered_content_allowed_html() );
	}

	/**
	 * Rendered markup of the block theme's template part, such as its header or footer.
	 *
	 * The same steps as core's block_template_part(), returned instead of echoed, so no output buffer
	 * is needed. Not filtered: pass the result to wp_kses() with rendered_content_allowed_html()
	 * before printing it.
	 *
	 * @since 5.1.4
	 * @param string $part Template part slug, such as 'header' or 'footer'.
	 * @return string Rendered markup, or '' when the theme has no such part.
	 */
	public function get_block_template_part( $part ) {
		$template_part = get_block_template( get_stylesheet() . '//' . $part, 'wp_template_part' );
		if ( ! $template_part || empty( $template_part->content ) ) {
			return '';
		}
		return do_blocks( $template_part->content );
	}

	/**
	 * Tags and attributes allowed in rendered page content: the core "post" list, plus the form
	 * controls, embeds, responsive-image attributes and SVG that blocks and template parts render.
	 *
	 * @since 5.1.2
	 * @return array wp_kses() allowlist.
	 */
	public function rendered_content_allowed_html() {
		static $allowed = null;
		if ( null !== $allowed ) {
			return $allowed;
		}

		// The core "post" list (PostX adds its SVG tags to it in Initialization::ultp_handle_allowed_html()),
		// plus the rich-text tags PostX blocks allow in their own text attributes.
		$allowed = wp_kses_allowed_html( 'post' );
		foreach ( $this->ultp_allowed_html_tags() as $tag => $attributes ) {
			$allowed[ $tag ] = array_merge( isset( $allowed[ $tag ] ) && is_array( $allowed[ $tag ] ) ? $allowed[ $tag ] : array(), $attributes );
		}
		foreach ( $this->ultp_allowed_block_tags() as $tag ) {
			if ( ! isset( $allowed[ $tag ] ) ) {
				$allowed[ $tag ] = array();
			}
		}

		$form_common = array(
			'name'         => true,
			'value'        => true,
			'disabled'     => true,
			'required'     => true,
			'readonly'     => true,
			'autocomplete' => true,
			'autofocus'    => true,
			'form'         => true,
		);
		$extra       = array(
			'form'     => array(
				'action'     => true,
				'method'     => true,
				'novalidate' => true,
			),
			'input'    => array_merge(
				$form_common,
				array(
					'type'         => true,
					'placeholder'  => true,
					'checked'      => true,
					'min'          => true,
					'max'          => true,
					'step'         => true,
					'minlength'    => true,
					'maxlength'    => true,
					'pattern'      => true,
					'size'         => true,
					'list'         => true,
					'multiple'     => true,
					'accept'       => true,
					'inputmode'    => true,
					'enterkeyhint' => true,
				)
			),
			'select'   => array_merge(
				$form_common,
				array(
					'multiple' => true,
					'size'     => true,
				)
			),
			'option'   => array(
				'value'    => true,
				'selected' => true,
				'disabled' => true,
				'label'    => true,
			),
			'optgroup' => array(
				'label'    => true,
				'disabled' => true,
			),
			'textarea' => array_merge(
				$form_common,
				array(
					'placeholder' => true,
					'rows'        => true,
					'cols'        => true,
					'maxlength'   => true,
				)
			),
			'button'   => array_merge( $form_common, array( 'type' => true ) ),
			'label'    => array( 'for' => true ),
			'iframe'   => array(
				'src'             => true,
				'width'           => true,
				'height'          => true,
				'name'            => true,
				'frameborder'     => true,
				'allow'           => true,
				'allowfullscreen' => true,
				'loading'         => true,
				'referrerpolicy'  => true,
				'sandbox'         => true,
				// The iframe of a WordPress post embed (wp_oembed_get(), [embed]).
				'marginwidth'     => true,
				'marginheight'    => true,
				'scrolling'       => true,
				'security'        => true,
			),
			// The Button block's "download" link option; the Social Share block's share link (`url`,
			// read by ultp.js and passed to window.open(); its protocol is checked like an href, see
			// ULTP_Initialization::ultp_kses_uri_attributes()); the video icon's popup options.
			'a'        => array(
				'download'       => true,
				'url'            => true,
				'enableautoplay' => true,
			),
			// The Social Share block's wrapper: post ID and share count, sent by ultp.js to ultp_share_count.
			// The video icon of post blocks: popup options read by ultp.js and PostX Pro.
			'div'      => array(
				'postid'           => true,
				'count'            => true,
				'enablevideopopup' => true,
				'enableautoplay'   => true,
			),
			'img'      => array(
				'srcset'        => true,
				'sizes'         => true,
				'loading'       => true,
				'decoding'      => true,
				'fetchpriority' => true,
				'crossorigin'   => true,
			),
			// Embed code only: pre_kses removes every other <script> before this list applies, and keeps one
			// only when its src is an exact script file of a known provider (ULTP_Initialization::ultp_embed_script_sources()).
			'script'   => array(
				'src'            => true,
				'async'          => true,
				'defer'          => true,
				'charset'        => true,
				'type'           => true,
				'crossorigin'    => true,
				'referrerpolicy' => true,
			),
			// Canvas, such as a chart in pasted HTML. Plain attributes only.
			'canvas'   => array(
				'width'  => true,
				'height' => true,
			),
			// The fallback an image plugin adds beside a lazy-loaded <img>. pre_kses (ULTP_Initialization::ultp_pre_kses_rendered_content())
			// filters its body and puts the tags back, so they must pass here: without them the
			// fallback image would be left in the page as a second, visible image.
			'noscript' => array(),
			'picture'  => array(),
			'source'   => array(
				'src'    => true,
				'srcset' => true,
				'sizes'  => true,
				'media'  => true,
				'type'   => true,
			),
		);

		// SVG elements and the presentation attributes icons use (see svg_allowed_html()).
		foreach ( $this->svg_allowed_html() as $svg_tag => $svg_attributes ) {
			$extra[ $svg_tag ] = $svg_attributes;
		}

		foreach ( $extra as $tag => $attributes ) {
			$allowed[ $tag ] = array_merge( isset( $allowed[ $tag ] ) ? $allowed[ $tag ] : array(), $attributes );
		}

		// Attributes any element may carry. wp_kses() matches only data-* by prefix, so the ARIA 1.2
		// attributes are listed one by one.
		$global = array_fill_keys(
			array(
				'id', 'class', 'style', 'title', 'role', 'lang', 'dir', 'hidden', 'tabindex', 'data-*', 'xml:lang',
				'itemprop', 'itemscope', 'itemtype', 'itemid', 'itemref',
				'aria-activedescendant', 'aria-atomic', 'aria-autocomplete', 'aria-braillelabel',
				'aria-brailleroledescription', 'aria-busy', 'aria-checked', 'aria-colcount', 'aria-colindex',
				'aria-colindextext', 'aria-colspan', 'aria-controls', 'aria-current', 'aria-describedby',
				'aria-description', 'aria-details', 'aria-disabled', 'aria-errormessage', 'aria-expanded',
				'aria-flowto', 'aria-haspopup', 'aria-hidden', 'aria-invalid', 'aria-keyshortcuts', 'aria-label',
				'aria-labelledby', 'aria-level', 'aria-live', 'aria-modal', 'aria-multiline', 'aria-multiselectable',
				'aria-orientation', 'aria-owns', 'aria-placeholder', 'aria-posinset', 'aria-pressed', 'aria-readonly',
				'aria-relevant', 'aria-required', 'aria-roledescription', 'aria-rowcount', 'aria-rowindex',
				'aria-rowindextext', 'aria-rowspan', 'aria-selected', 'aria-setsize', 'aria-sort', 'aria-valuemax',
				'aria-valuemin', 'aria-valuenow', 'aria-valuetext',
			),
			true
		);
		foreach ( $allowed as $tag => $attributes ) {
			$allowed[ $tag ] = array_merge( is_array( $attributes ) ? $attributes : array(), $global );
		}

		return $allowed;
	}

	/**
	 * String Part finder inside array.
	 *
	 * @since v.2.7.0 updated 4.1.12
	 *
	 * @param STRING $part The part of the string to search for.
	 * @param ARRAY  $data The array to search within.
	 * @param STRING $toR  Return type: 'bool', 'value', or default (array).
	 * @return MIXED Returns true/false, value, or array depending on $toR.
	 * @since v.2.7.0 updated 4.1.12
	 */
	public function in_array_part( $part, $data, $toR = '' ) {
		$res = array();
		foreach ( $data as $key => $val ) {
			if ( strpos( $val, $part ) !== false ) {
				if ( $toR == 'bool' ) {
					return true;
				} elseif ( $toR == 'value' ) {
					return $val;
				}
				$res[ $key ] = $val;
			}
		}
		if ( $toR == 'bool' ) {
			return false;
		} elseif ( $toR == 'value' ) {
			return '';
		}
		return $res;
	}

	/**
	 * Read legacy condition lists safely without discarding valid neighboring templates.
	 *
	 * @param mixed $conditions Existing or imported condition map.
	 * @return array Conditions with list values safe for the builder readers.
	 */
	public function normalize_builder_conditions( $conditions ) {
		if ( ! is_array( $conditions ) ) {
			return array();
		}
		$clean = array();
		foreach ( $conditions as $group => $templates ) {
			if ( ! is_array( $templates ) ) {
				continue;
			}
			$clean[ $group ] = array();
			foreach ( $templates as $id => $list ) {
				$list = is_string( $list ) ? array( $list ) : $list;
				if ( ! is_array( $list ) ) {
					continue;
				}
				$clean[ $group ][ $id ] = array_values(
					array_filter(
						$list,
						function ( $condition ) {
							return is_string( $condition ) && preg_match( '~^(include|exclude)/~', $condition );
						}
					)
				);
			}
		}
		return $clean;
	}

	/**
	 * Builder Conditions
	 *
	 * @since v.2.7.0 updated 4.1.12
	 * @param STRING $type          Type of return value.
	 * @param MIXED  $cus_condition Custom conditions array or value.
	 * @return MIXED                ID or Path.
	 */
	public function builder_check_conditions( $type = 'return', $cus_condition = '' ) {
		$page_id    = '';
		$conditions = $this->normalize_builder_conditions( $cus_condition ? $cus_condition : get_option( 'ultp_builder_conditions', array() ) );

		if ( $type == 'header' || $type == 'footer' ) {
			if ( ! empty( $conditions[ $type ] ) ) {
				foreach ( $conditions[ $type ] as $key => $val ) {
					if ( 'publish' == get_post_status( $key ) && ! empty( $val ) ) {
						if ( in_array( 'include/' . $type . '/entire_site', $val ) ) {
							$page_id = $key;
						} elseif ( in_array( 'exclude/' . $type . '/entire_site', $val ) ) {
							$page_id = '';
						}

						$c_page     = ( is_archive() || is_search() ) ? 'archive' : 'singular';
						$hf_archive = $this->in_array_part( $type . '/' . $c_page, $val, '' );
						if ( ! empty( $hf_archive ) ) {
							foreach ( $hf_archive as $k => $v ) {
								if ( strpos( $v, 'include/' . $type . '/' . $c_page ) !== false ) {
									$temp    = $this->builder_check_conditions( 'return_' . $type, array( $c_page => array( $key => array( str_replace( $type . '/', '', $v ) ) ) ) );
									$page_id = $temp ? $temp : $page_id;
								} elseif ( strpos( $v, 'exclude/' . $type . '/' . $c_page ) !== false ) {
									$temp    = $this->builder_check_conditions( 'return_' . $type, array( $c_page => array( $key => array( str_replace( 'exclude/' . $type, 'include', $v ) ) ) ) );
									$page_id = $temp ? '' : $page_id;
								}
							}
						}
					}
				}
			}
		} else {
			// 404 page .
			if ( ! empty( $conditions['404'] ) && is_404() ) {
				foreach ( $conditions['404'] as $key => $val ) {
					if ( 'publish' == get_post_status( $key ) ) {
						$page_id = $key;
					}
				}
			}
			// Singular or Front Page .
			elseif (
				(
					! empty( $conditions['singular'] ) ||
					! empty( $conditions['front_page'] )
				) &&
				(
					is_singular() ||
					is_front_page() ||
					is_home()
				)
			) {
				$obj            = get_queried_object();
				$queried_is_obj = is_object( $obj );
				// front page only .
				if (
					( is_front_page() && is_home() ) ||
					( is_home() && ! $queried_is_obj ) ||
					( is_singular() && is_front_page() && $queried_is_obj )
				) {
					$f_conditions = array();
					if ( ! empty( $conditions['front_page'] ) ) {
						$f_conditions = $conditions['front_page'];
					} elseif ( ! empty( $conditions['singular'] ) ) {
						$f_conditions = $conditions['singular'];
					}
					if ( ! empty( $f_conditions ) ) {
						foreach ( $f_conditions as $key => $val ) {
							if ( ( is_array( $val ) && in_array( 'include/singular/front_page', $val ) ) ||
								( is_array( $val ) && in_array( 'include/front_page', $val ) ) ||
								'include/front_page' == $val
							) {
								if ( 'publish' == get_post_status( $key ) ) {
									$page_id = $key;
								}
							}
							// Default Front / Home page Header Footer Compatibility .
							elseif ( $queried_is_obj && is_array( $val ) && 'publish' == get_post_status( $key ) && ( $type == 'return_header' || $type == 'return_footer' ) ) {
								$base_include     = 'include/singular/' . $obj->post_type;
								$base_exclude     = 'exclude/singular/' . $obj->post_type;
								$specific_include = $base_include . '/' . $obj->ID;
								$specific_exclude = $base_exclude . '/' . $obj->ID;

								if ( in_array( $specific_include, $val ) ) {
									$page_id = $key;
								} elseif ( in_array( $specific_exclude, $val ) ) {
									$page_id = '';
								} elseif ( in_array( $base_include, $val ) ) {
									$page_id = $key;
								} elseif ( in_array( $base_exclude, $val ) ) {
									$page_id = '';
								}
							}
						}
					}
				}
				// Singular page .
				elseif ( ! empty( $conditions['singular'] ) ) {
					foreach ( $conditions['singular'] as $key => $val ) {
						if ( 'publish' == get_post_status( $key ) ) {
							// All Post Type.                -----Prority 1 .
							if ( $queried_is_obj ) {
								if ( in_array( 'include/singular/' . $obj->post_type, $val ) ) {
									$page_id = $key;
								} elseif ( in_array( 'exclude/singular/' . $obj->post_type, $val ) ) {
									$page_id = '';
								}
							}

							$singular_in_tax = $this->in_array_part( '/singular/in_', $val, '' );
							if ( ! empty( $singular_in_tax ) ) {
								$tax_list = $this->get_taxonomy_list();
								foreach ( $tax_list as $tax ) {
									if ( in_array( 'include/singular/in_' . $tax . '_children', $singular_in_tax ) ) {
										$page_id = $key;
									} elseif ( in_array( 'exclude/singular/in_' . $tax . '_children', $singular_in_tax ) ) {
										$page_id = '';
									}

									if ( $queried_is_obj ) {
										$singular_in_tax_children = $this->in_array_part( '/singular/in_' . $tax . '_children/', $singular_in_tax, '' );
										foreach ( $singular_in_tax_children as $v ) {
											$data = explode( '/', $v );
											if ( isset( $data[3] ) && $data[3] ) {
												$childs = get_term_children( $data[3], $tax );
												if ( ! empty( $childs ) && has_term( $childs, $tax ) ) {
													$page_id = $data[0] == 'exclude' ? '' : $key;
												}
											}
										}
									}

									if ( in_array( 'include/singular/in_' . $tax, $singular_in_tax ) ) {
										if ( $tax == 'post_tag' && $queried_is_obj ) {
											$cat = get_the_terms( $obj->ID, 'post_tag' );
											if ( ! empty( $cat ) ) {
												$page_id = $key;
											}
										} else {
											$page_id = $key;
										}
									} elseif ( in_array( 'exclude/singular/in_' . $tax, $singular_in_tax ) ) {
										$page_id = '';
									}

									if ( $queried_is_obj ) {
										$singular_in_tax_only = $this->in_array_part( '/singular/in_' . $tax . '/', $singular_in_tax, '' );
										foreach ( $singular_in_tax_only as $v ) {
											$data = explode( '/', $v );
											if ( isset( $data[3] ) && $data[3] ) {
												if ( is_object_in_term( $obj->ID, $tax, $data[3] ) ) {
													$page_id = $data[0] == 'exclude' ? '' : $key;
												}
											}
										}
									}
								}
							}

							// Posts by author              -----Prority 9 .
							if ( in_array( 'include/singular/post_by_author', $val ) ) {
								$page_id = $key;
							} elseif ( in_array( 'exclude/singular/post_by_author', $val ) ) {
								$page_id = '';
							}
							if ( $queried_is_obj ) {
								if ( in_array( 'include/singular/post_by_author/' . $obj->post_author, $val ) ) {
									$page_id = $key;
								} elseif ( in_array( 'exclude/singular/post_by_author/' . $obj->post_author, $val ) ) {
									$page_id = '';
								}
								// Post Type with specific id  -----Prority 10 .
								if ( in_array( 'include/singular/' . $obj->post_type . '/' . $obj->ID, $val ) ) {
									$page_id = $key;
								} elseif ( in_array( 'exclude/singular/' . $obj->post_type . '/' . $obj->ID, $val ) ) {
									$page_id = '';
								}
							}
						}
					}
				}
			}

			// Archive and Search Page .
			elseif ( ! empty( $conditions['archive'] ) ) {
				// Search Page .
				if ( is_search() ) {
					foreach ( $conditions['archive'] as $key => $val ) {
						if ( 'publish' == get_post_status( $key ) ) {
							if ( in_array( 'include/archive/search', $val ) ) {
								$page_id = $key;
							} elseif ( in_array( 'exclude/archive/search', $val ) ) {
								$page_id = '';
							}
						}
					}
				}
				// Archive Page .
				elseif ( is_archive() ) {
					$is_cat    = is_category();
					$is_tag    = is_tag();
					$is_tax    = is_tax();
					$is_date   = is_date();
					$is_author = is_author();
					$taxonomy  = ( $is_cat || $is_tag || $is_tax ) ? get_queried_object() : (object) array();

					foreach ( $conditions['archive'] as $key => $val ) {
						if ( 'publish' == get_post_status( $key ) ) {
							// This section is for all archive page .
							if ( in_array( 'include/archive', $val ) ) {
								$page_id = $key;
							} elseif ( in_array( 'exclude/archive', $val ) ) {
								$page_id = '';
							}
							// For Category .
							if ( $is_cat ) {
								// all category .
								if ( in_array( 'include/archive/category', $val ) ) {
									$page_id = $key;
								} elseif ( in_array( 'exclude/archive/category', $val ) ) {
									$page_id = '';
								}
								// specific category .
								if ( in_array( 'include/archive/category/' . $taxonomy->term_id, $val ) ) {
									$page_id = $key;
								} elseif ( in_array( 'exclude/archive/category/' . $taxonomy->term_id, $val ) ) {
									$page_id = '';
								}

								// any child of category .
								if ( in_array( 'include/archive/any_child_of_category', $val ) ) {
									$page_id = $key;
								} elseif ( in_array( 'exclude/archive/any_child_of_category', $val ) ) {
									$page_id = '';
								}

								$any_child_of_cat = $this->in_array_part( 'archive/any_child_of_category/', $val, '' );
								if ( ! empty( $any_child_of_cat ) ) {
									foreach ( $any_child_of_cat as $v ) {
										$data = explode( '/', $v );
										if ( isset( $data[3] ) && $data[3] ) {
											if ( term_is_ancestor_of( $data[3], $taxonomy->term_id, 'category' ) ) {
												$page_id = $data[0] == 'exclude' ? '' : $key;
											}
										}
									}
								}

								// all child of category .
								if ( in_array( 'include/archive/child_of_category', $val ) ) {
									$page_id = $key;
								} elseif ( in_array( 'exclude/archive/child_of_category', $val ) ) {
									$page_id = '';
								}
								// specific child of category .
								if ( in_array( 'include/archive/child_of_category/' . $taxonomy->parent, $val ) ) {
									$page_id = $key;
								} elseif ( in_array( 'exclude/archive/child_of_category/' . $taxonomy->parent, $val ) ) {
									$page_id = '';
								}
							}
							// For Tag .
							elseif ( $is_tag ) {
								if ( in_array( 'include/archive/post_tag', $val ) ) {
									$page_id = $key;
								} elseif ( in_array( 'exclude/archive/post_tag', $val ) ) {
									$page_id = '';
								}
								if ( in_array( 'include/archive/post_tag/' . $taxonomy->term_id, $val ) ) {
									$page_id = $key;
								} elseif ( in_array( 'exclude/archive/post_tag/' . $taxonomy->term_id, $val ) ) {
									$page_id = '';
								}
							}
							// Other taxonomy .
							elseif ( $is_tax ) {
								if ( in_array( 'include/archive/' . $taxonomy->taxonomy, $val ) ) {
									$page_id = $key;
								} elseif ( in_array( 'exclude/archive/' . $taxonomy->taxonomy, $val ) ) {
									$page_id = '';
								}

								if ( in_array( 'include/archive/' . $taxonomy->taxonomy . '/' . $taxonomy->term_id, $val ) ) {
									$page_id = $key;
								} elseif ( in_array( 'exclude/archive/' . $taxonomy->taxonomy . '/' . $taxonomy->term_id, $val ) ) {
									$page_id = '';
								}

								if ( in_array( 'include/archive/any_child_of_' . $taxonomy->taxonomy, $val ) ) {
									$page_id = $key;
								} elseif ( in_array( 'exclude/archive/any_child_of_' . $taxonomy->taxonomy, $val ) ) {
									$page_id = '';
								}

								$any_child_of_tax = $this->in_array_part( 'archive/any_child_of_' . $taxonomy->taxonomy . '/', $val, '' );
								if ( ! empty( $any_child_of_tax ) ) {
									foreach ( $any_child_of_tax as $v ) {
										$data = explode( '/', $v );
										if ( isset( $data[3] ) && $data[3] ) {
											if ( term_is_ancestor_of( $data[3], $taxonomy->term_id, $taxonomy->taxonomy ) ) {
												$page_id = $data[0] == 'exclude' ? '' : $key;
											}
										}
									}
								}

								if ( in_array( 'include/archive/child_of_' . $taxonomy->taxonomy, $val ) ) {
									$page_id = $key;
								} elseif ( in_array( 'exclude/archive/child_of_' . $taxonomy->taxonomy, $val ) ) {
									$page_id = '';
								}

								if ( in_array( 'include/archive/child_of_' . $taxonomy->taxonomy . '/' . $taxonomy->parent, $val ) ) {
									$page_id = $key;
								} elseif ( in_array( 'exclude/archive/child_of_' . $taxonomy->taxonomy . '/' . $taxonomy->parent, $val ) ) {
									$page_id = '';
								}
							}
							// For Date.
							elseif ( $is_date ) {
								if ( in_array( 'include/archive/date', $val ) ) {
									$page_id = $key;
								} elseif ( in_array( 'exclude/archive/date', $val ) ) {
									$page_id = '';
								}
							}
							// For Author.
							elseif ( $is_author ) {
								if ( in_array( 'include/archive/author', $val ) ) {
									$page_id = $key;
								} elseif ( in_array( 'exclude/archive/author', $val ) ) {
									$page_id = '';
								}
								$author_id = get_the_author_meta( 'ID' );
								if ( in_array( 'include/archive/author/' . $author_id, $val ) ) {
									$page_id = $key;
								} elseif ( in_array( 'exclude/archive/author/' . $author_id, $val ) ) {
									$page_id = '';
								}
							}
						}
					}
				}
			}
		}
		return $page_id;
	}

	/**
	 * Get Date Default Format
	 *
	 * @since v.2.7.2
	 * @param STRING $format Format type.
	 * @return ARRAY | Default Data
	 */
	public function get_format( $format ) {
		if ( $format == 'default_date' ) {
			return get_option( 'date_format' );
		} elseif ( $format == 'default_date_time' ) {
			return get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
		} else {
			return $format;
		}
	}

	/**
	 * Date text of the builder date blocks (Post Date Meta, Advance Post Meta).
	 *
	 * Only the format the editor offers for these blocks, 'M j, Y', is printed here. Every other saved
	 * format is passed to `$filter` with that text as its default, so another plugin can print its own
	 * format. Without an answer the default text is kept.
	 *
	 * @param string   $filter   Filter that answers the other formats: `ultp_builder_post_date_format` or
	 *                           `ultp_builder_advance_meta_date_format`.
	 * @param string   $format   Saved format value (metaDateFormat of the block).
	 * @param bool     $modified True for the modified date, false for the published date.
	 * @param int|null $post_id  Post the date belongs to, as get_the_date() takes it (null for the current post).
	 * @param array    $attr     Block attributes.
	 * @return string Date text, not escaped.
	 */
	public function get_builder_date( $filter, $format, $modified, $post_id, $attr ) {
		$default = $modified ? get_the_modified_date( 'M j, Y', $post_id ) : get_the_date( 'M j, Y', $post_id );
		return apply_filters( $filter, $default, $format, $post_id, $attr );
	}

	/**
	 * Common Frontend and Backend CSS and JS Scripts
	 *
	 * @since v.1.0.0
	 */
	public function register_scripts_common() {
		if ( ! ( isset( $GLOBALS['wp_scripts'] ) && isset( $GLOBALS['wp_scripts']->registered ) && isset( $GLOBALS['wp_scripts']->registered['ultp-script'] ) ) ) {
			wp_enqueue_style( 'ultp-style', ULTP_URL . 'assets/css/style.min.css', array(), ULTP_VER );
			wp_enqueue_script( 'ultp-script', ULTP_URL . 'assets/js/ultp.min.js', array( 'jquery', 'wp-api-fetch' ), ULTP_VER, true );
			// Lets an add-on load styles and scripts that go with the block stylesheet and script.
			do_action( 'ultp_common_assets_enqueued' );
			wp_localize_script(
				'ultp-script',
				'ultp_data_frontend',
				array(
					'url'             => ULTP_URL,
					'ultpSavedDLMode' => ultimate_post()->get_dl_mode(),
					'ajax'            => admin_url( 'admin-ajax.php' ),
					'security'        => wp_create_nonce( 'ultp-public-nonce' ),
					'home_url'        => home_url(),
					'dark_logo'       => get_option( 'ultp_site_dark_logo', false ),
				)
			);
		}
	}

	/**
	 * Get Dark Light Mode
	 *
	 * @since 4.0.0
	 * @return STRING | Dark Light Mode .
	 */
	public function get_dl_mode() {
		$data = get_option( 'postx_global', array() );
		$mode = 'ultplight';
		if ( ! empty( $data ) ) {
			$mode = isset( $data['enableDark'] ) && $data['enableDark'] ? 'ultpdark' : 'ultplight';
		}
		return $mode;
	}

	/**
	 * Get Page Post Id ( Kadence element )
	 *
	 * @since v.2.8.9
	 * @param STRING|INT $blockId Block ID to search for.
	 * @return NULL
	 */
	public function get_page_post_id( $blockId ) {
		if ( is_wp_error( BlockStyleValues::identifier( $blockId ) ) ) { return $this->get_ID(); }
		global $wpdb;
		// CSS caches cannot establish block ownership. Bind and escape content search, then verify the actual tree.
		$like = '%' . $wpdb->esc_like( $blockId ) . '%';
		$candidates = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_content LIKE %s AND post_type <> 'revision' AND post_status <> 'trash' ORDER BY ID DESC LIMIT 1000", $like ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		foreach ( $candidates as $id ) {
			$blocks = BlockStyleSource::get_blocks( (int) $id );
			if ( ! is_wp_error( $blocks ) && $this->style_tree_has_id( $blocks, $blockId ) ) { return (int) $id; }
		}
		return $this->get_ID();
	}
	private function style_tree_has_id( $blocks, $id ) {
		foreach ( $blocks as $block ) {
			if ( 0 === strpos( $block['blockName'], 'ultimate-post/' ) && ( $block['attrs']['blockId'] ?? null ) === $id ) { return true; }
			if ( $this->style_tree_has_id( $block['innerBlocks'], $id ) ) { return true; }
		}
		return false;
	}

	/**
	 * Get no Result found html
	 *
	 * @since v.2.9.0
	 * @param STRING $text No result found text.
	 * @return STRING | Taxonomy Lists as array
	 */
	public function get_no_result_found_html( $text ) {
		return $text ? '<div class="ultp-not-found-message" role="alert">' . wp_kses( $text, ultimate_post()->ultp_allowed_html_tags() ) . '</div>' : '';
	}

	/**
	 * Get all Saved Templates Lists
	 *
	 * @since v.2.9.9
	 * @param STRING $post_type Post type to retrieve.
	 * @param STRING $empty     Empty value for the select field.
	 * @return STRING | Taxonomy Lists as array
	 */
	public function get_all_lists( $post_type = 'post', $empty = '' ) {
		$args                         = array(
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		);
		$loop                         = new \WP_Query( $args );
		$data[ $empty ? $empty : '' ] = __( '- Select Template -', 'ultimate-post' );
		if ( $loop->have_posts() ) {
			foreach ( $loop->posts as $post ) {
				$data[ $post->ID ] = $post->post_title;
			}
		}
		wp_reset_postdata();
		return $data;
	}

	/**
	 * Get ID from the Youtube URL
	 *
	 * @since v.3.1.7
	 * @param STRING $url The Youtube URL .
	 * @return STRING | Taxonomy Lists as array
	 */
	public function get_youtube_id( $url = '' ) {
		if ( strpos( $url, 'youtu' ) !== false ) {
			$reg = '/youtu(?:.*\/v\/|.*v\=|\.be\/)([A-Za-z0-9_\-]{11})/m';
			preg_match( $reg, $url, $matches );
			if ( isset( $matches[1] ) ) {
				return $matches[1];
			}
		}
		return false;
	}

	/**
	 * Get Option Value bypassing cache
	 * Inspired By WordPress Core get_option
	 *
	 * @since v.3.1.6
	 * @param STRING  $option Option Name.
	 * @param BOOLEAN $default_value option default value.
	 * @return mixed
	 */
	public function get_option_without_cache( $option, $default_value = false ) {
		global $wpdb;

		if ( is_scalar( $option ) ) {
			$option = trim( $option );
		}

		if ( empty( $option ) ) {
			return false;
		}

		$value = $default_value;

		$row = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", $option ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( is_object( $row ) ) {
			$value = $row->option_value;
		} else {
			return apply_filters( "ultp_default_option_{$option}", $default_value, $option );
		}

		return apply_filters( "ultp_option_{$option}", maybe_unserialize( $value ), $option );
	}

	/**
	 * Get Transient Value bypassing cache
	 * Inspired By WordPress Core get_transient
	 *
	 * @since v.3.1.6
	 * @param STRING $transient Transient Name.
	 * @return  MIXED
	 */
	public function get_transient_without_cache( $transient ) {
		$transient_option  = '_transient_' . $transient;
		$transient_timeout = '_transient_timeout_' . $transient;
		$timeout           = $this->get_option_without_cache( $transient_timeout );

		if ( false !== $timeout && $timeout < time() ) {
			delete_option( $transient_option );
			delete_option( $transient_timeout );
			$value = false;
		}

		if ( ! isset( $value ) ) {
			$value = $this->get_option_without_cache( $transient_option );
		}

		return apply_filters( "ultp_transient_{$transient}", $value, $transient );
	}

	/**
	 * Set transient without adding to the cache
	 * Inspired By WordPress Core set_transient
	 *
	 * @since v.3.1.6
	 * @param STRING  $transient Transient Name.
	 * @param MIXED   $value Transient Value.
	 * @param INTEGER $expiration Time until expiration in seconds.
	 * @return BOOL
	 */
	public function set_transient_without_cache( $transient, $value, $expiration = 0 ) {
		$expiration = (int) $expiration;

		$transient_timeout = '_transient_timeout_' . $transient;
		$transient_option  = '_transient_' . $transient;

		$result = false;

		if ( false === $this->get_option_without_cache( $transient_option ) ) {
			$autoload = 'yes';
			if ( $expiration ) {
				$autoload = 'no';
				$this->add_option_without_cache( $transient_timeout, time() + $expiration, 'no' );
			}
			$result = $this->add_option_without_cache( $transient_option, $value, $autoload );
		} else {
			/*
			 * If expiration is requested, but the transient has no timeout option,
			 * delete, then re-create transient rather than update.
			 */
			$update = true;

			if ( $expiration ) {
				if ( false === $this->get_option_without_cache( $transient_timeout ) ) {
					delete_option( $transient_option );
					$this->add_option_without_cache( $transient_timeout, time() + $expiration, 'no' );
					$result = $this->add_option_without_cache( $transient_option, $value, 'no' );
					$update = false;
				} else {
					update_option( $transient_timeout, time() + $expiration );
				}
			}

			if ( $update ) {
				$result = update_option( $transient_option, $value );
			}
		}

		return $result;
	}

	/**
	 * Add option without adding to the cache
	 * Inspired By WordPress Core set_transient
	 *
	 * @since v.3.1.6
	 * @param STRING $option option name.
	 * @param STRING $value option value.
	 * @param STRING $autoload whether to load WordPress startup.
	 * @return BOOL
	 */
	public function add_option_without_cache( $option, $value = '', $autoload = 'yes' ) {
		global $wpdb;

		if ( is_scalar( $option ) ) {
			$option = trim( $option );
		}

		if ( empty( $option ) ) {
			return false;
		}

		wp_protect_special_option( $option );

		if ( is_object( $value ) ) {
			$value = clone $value;
		}

		$value = sanitize_option( $option, $value );

		/*
		 * Make sure the option doesn't already exist.
		 */

		if ( apply_filters( "ultp_default_option_{$option}", false, $option, false ) !== $this->get_option_without_cache( $option ) ) {
			return false;
		}

		$serialized_value = maybe_serialize( $value );
		$autoload         = ( 'no' === $autoload || false === $autoload ) ? 'no' : 'yes';

		$result = $wpdb->query( $wpdb->prepare( "INSERT INTO `$wpdb->options` (`option_name`, `option_value`, `autoload`) VALUES (%s, %s, %s) ON DUPLICATE KEY UPDATE `option_name` = VALUES(`option_name`), `option_value` = VALUES(`option_value`), `autoload` = VALUES(`autoload`)", $option, $serialized_value, $autoload ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( ! $result ) {
			return false;
		}

		return true;
	}

	/**
	 * Authorize creating content using the registered post type's capabilities.
	 *
	 * @param string $post_type Target post type.
	 * @param bool   $publish   Whether the new content will be published.
	 * @return bool
	 */
	public static function can_create_post_type( $post_type, $publish = false ) {
		$type = get_post_type_object( $post_type );
		if ( ! $type ) {
			return false;
		}
		$create = $type->cap->create_posts ?? ( $type->cap->edit_posts ?? '' );
		return $create && current_user_can( $create ) && ( ! $publish || ( ! empty( $type->cap->publish_posts ) && current_user_can( $type->cap->publish_posts ) ) );
	}

	/**
	 * Permission_check_for_restapi
	 *
	 * @since v.3.2.4
	 * @param STRING/Bool $post_id .
	 * @param STRING      $cap STRING .
	 * @return BOOL
	 */
	public function permission_check_for_restapi( $post_id = false, $cap = '' ) {
		// For a post, ask about that post: edit_post honours its status, ownership and
		// post-type capabilities. Being its author is not enough on its own.
		if ( $post_id ) {
			return current_user_can( 'edit_post', (int) $post_id );
		}
		return current_user_can( $cap ? $cap : 'edit_others_posts' );
	}

	/**
	 * Sanitize params
	 *
	 * @param MIXED $params Parameters to sanitize.
	 * @return array|bool|mixed|string
	 * @since v.4.0.0
	 */
	public function ultp_rest_sanitize_params( $params ) {
		if ( is_array( $params ) ) {
			return array_map( array( $this, 'ultp_rest_sanitize_params' ), $params );
		} elseif ( is_bool( $params ) ) {
			return rest_sanitize_boolean( $params );
		} elseif ( $params instanceof \stdClass ) {
			$clean = new \stdClass();
			foreach ( get_object_vars( $params ) as $key => $value ) {
				$clean->$key = $this->ultp_rest_sanitize_params( $value );
			}
			return $clean;
		} elseif ( is_object( $params ) || is_resource( $params ) ) {
			return '';
		} else {
			return sanitize_text_field( $params );
		}
	}

	/**
	 * Get Adv Filter Data Attributes
	 *
	 * @param ARRAY|NULL $attr .
	 * @param ARRAY|NULL $filter_attributes .
	 * @return string
	 * @since v.3.2.5
	 */
	public function get_adv_data_attrs( $attr, $filter_attributes = null ) {
		// Adv Filter Integration.
		$adv_filter_dataset = array();

		if ( $filter_attributes ) {
			$adv_filter_dataset[] = 'data-filter-value="' . esc_attr( $filter_attributes['queryTaxValue'] ) . '"';
			$adv_filter_dataset[] = 'data-filter-type="' . esc_attr( $filter_attributes['queryTax'] ) . '"';

			$is_adv               = isset( $filter_attributes['isAdv'] ) ? $filter_attributes['isAdv'] : 0;
			$adv_filter_dataset[] = 'data-filter-is-adv="' . esc_attr( $is_adv ) . '"';

			if ( $is_adv ) {
				if ( isset( $filter_attributes['queryAuthor'] ) ) {
					$adv_filter_dataset[] = "data-filter-author='" . esc_attr( $filter_attributes['queryAuthor'] ) . "'";
				}

				if ( isset( $filter_attributes['queryOrder'] ) ) {
					$adv_filter_dataset[] = "data-filter-order='" . esc_attr( $filter_attributes['queryOrder'] ) . "'";
				}

				if ( isset( $filter_attributes['queryOrderBy'] ) ) {
					$adv_filter_dataset[] = "data-filter-orderby='" . esc_attr( $filter_attributes['queryOrderBy'] ) . "'";
				}

				if ( isset( $filter_attributes['search'] ) ) {
					$adv_filter_dataset[] = 'data-filter-search="' . esc_attr( $filter_attributes['search'] ) . '"';
				}

				if ( isset( $filter_attributes['queryQuick'] ) && $filter_attributes['queryQuick'] ) {
					$adv_filter_dataset[] = 'data-filter-adv-sort="' . esc_attr( $filter_attributes['queryQuick'] ) . '"';
				}
			}
		}

		if ( $attr && isset( $attr['advFilterEnable'] ) && $attr['advFilterEnable'] ) {
			$adv_filter_dataset[] = 'data-filter-is-adv="1"';
			$adv_filter_dataset[] = 'data-filter-type="multiTaxonomy"';

			$data_filter_value    = isset( $attr['filterValue'] ) && $attr['filterValue'] ? $attr['filterValue'] : '[]';
			$adv_filter_dataset[] = 'data-filter-value="' . esc_attr( $data_filter_value ) . '"';

			$data_author          = isset( $attr['queryAuthor'] ) && $attr['queryAuthor'] ? $attr['queryAuthor'] : '[]';
			$adv_filter_dataset[] = 'data-filter-author="' . esc_attr( $data_author ) . '"';

			$data_order           = isset( $attr['queryOrder'] ) && $attr['queryOrder'] ? $attr['queryOrder'] : 'DESC';
			$adv_filter_dataset[] = 'data-filter-order="' . esc_attr( $data_order ) . '"';

			$data_orderby         = isset( $attr['queryOrderBy'] ) && $attr['queryOrderBy'] ? $attr['queryOrderBy'] : 'date';
			$adv_filter_dataset[] = 'data-filter-orderby="' . esc_attr( $data_orderby ) . '"';

			$adv_filter_dataset[] = 'data-filter-search="' . esc_attr( isset( $attr['querySearch'] ) ? $attr['querySearch'] : '' ) . '"';

			$adv_filter_dataset[] = 'data-filter-adv-sort="' . esc_attr( isset( $attr['queryQuick'] ) ? $attr['queryQuick'] : '' ) . '"';
		}

		$adv_filter_dataset = implode( ' ', $adv_filter_dataset );
		return $adv_filter_dataset;
	}

	/**
	 * Custom Text kses
	 *
	 * @since v.4.0.2
	 * @param MIXED $extras .
	 * @return ARRAY|BOOL|MIXED|STRING
	 */
	public function ultp_allowed_html_tags( $extras = array() ) {
		$allowed = array(
			'a'          => array(
				'href'  => true,
				'title' => true,
			),
			'abbr'       => array(
				'title' => true,
			),
			'b'          => array(),
			'br'         => array(),
			'blockquote' => array(
				'cite' => true,
			),
			'em'         => array(),
			'i'          => array(),
			'q'          => array(
				'cite' => true,
			),
			'strong'     => array(
				'class' => true,
				'style' => true,
			),
			'mark'       => array(
				'class' => true,
				'style' => true,
			),
		);

		return array_merge( $allowed, $extras );
	}

	/**
	 * Allowed Block Tags
	 *
	 * @since v.4.0.2
	 * @param STRING $search Optional. Tag name to check.
	 * @return ARRAY|BOOL Returns array of allowed tags or bool if $search is provided.
	 */
	public function ultp_allowed_block_tags( $search = '' ) {
		$array_lists = array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'span', 'p', 'div', 'section', 'article' );
		return $search ? in_array( $search, $array_lists ) : $array_lists;
	}

	/**
	 * Formats datasets for html
	 *
	 * @since v.4.0.2
	 *
	 * @param ARRAY $datasets .
	 * @return STRING
	 */
	public function get_formatted_datasets( &$datasets ) {
		$res = '';
		foreach ( $datasets as $key => $value ) {
			$res .= ' data-' . esc_attr( $key ) . '="' . esc_attr( $value ) . '" ';
		}
		return $res;
	}

	/**
	 * Sanitizes attributes after running necessary checks
	 *
	 * @since v.4.1.0
	 *
	 * @param ARRAY         $attr              The attributes array to sanitize.
	 * @param STRING        $key               The key to check in the attributes array.
	 * @param callable|null $sanitize_callback Callback function to sanitize the value.
	 * @param MIXED         $def_value         Default value to return if key is not set.
	 * @return MIXED               Sanitized value or default value.
	 */
	public function sanitize_attr( &$attr, $key, $sanitize_callback = null, $def_value = '' ) {
		return isset( $attr[ $key ] ) && $attr[ $key ] ?
			(
				$sanitize_callback ?
				$sanitize_callback( $attr[ $key ] ) :
				$attr[ $key ]
			)
			: $def_value;
	}

	/**
	 * Checks if dynamic content is active
	 *
	 * @since v.4.1.1
	 *
	 * @param ARRAY $attr The attributes to check for dynamic content.
	 * @return BOOLEAN
	 */
	public function is_dc_active( &$attr ) {
		if ( class_exists( '\ULTP\DCService' ) ) {
			return \ULTP\DCService::is_dc_active( $attr );
		}
		return false;
	}


	/**
	 * Checks all the taxonomy of a post type for a given slug. Returns the taxonomy
	 * if it exists.
	 *
	 * @since v.4.1.16
	 *
	 * @param STRING $post_type  The post type.
	 * @param STRING $slug       The slug.
	 * @return STRING|NULL       The taxonomy name or null if not found.
	 */
	public function get_taxonomy_by_term_slug( $post_type, $slug ) {
		$taxonomies = get_object_taxonomies( $post_type );
		foreach ( $taxonomies as $taxonomy ) {
			$res = get_term_by( 'slug', $slug, $taxonomy );

			if ( ! empty( $res ) ) {
				return $taxonomy;
			}
		}

		return null;
	}
	/**
	 * Resolve Icon Alias
	 *
	 * @since v.4.0.0
	 * @param STRING $icon_name The icon name or alias to resolve.
	 * @return STRING|FALSE Returns the resolved icon name or false if no alias found.
	 */
	public function svg_icon_compatibility( $icon_name = '' ) {
		if ( empty( $icon_name ) ) {
			return 'warning_triangle_solid';
		}
		// Get icon aliases mapping
		$icon_aliases = array(
			// Arrow aliases
			'angle_bottom_left_line'   => 'arrow_down_bottom_left_solid',
			'angle_bottom_right_line'  => 'arrow_down_bottom_right_solid',
			'angle_top_left_line'      => 'arrow_up_top_left_solid',
			'angle_top_right_line'     => 'arrow_up_top_right_solid',
			'rightFillAngle'           => 'right_triangle_angle_play_arrow_forward_solid',
			'leftAngle2'               => 'arrow_left_previous_backward_chevron_line',
			'rightAngle2'              => 'arrow_right_next_forward_chevron_line',
			'collapse_bottom_line'     => 'arrow_down_dropdown_maximize_chevron_line',
			'arrowUp2'                 => 'arrow_up_dropdown_minimize_chevron_line',
			'longArrowUp2'             => 'long_arrow_up_top_increase_solid',

			// Circle arrow aliases
			'arrow_left_circle_line'   => 'arrow_left_backward_circle_line',
			'arrow_bottom_circle_line' => 'arrow_down_bottom_downward_circle_line',
			'arrow_right_circle_line'  => 'arrow_right_forward_circle_line',
			'arrow_top_circle_line'    => 'arrow_up_top_upward_circle_line',

			// Close/Cross aliases
			'close_circle_line'        => 'cross_close_x_minimize_circle_line',
			'close_line'               => 'cross_x_close_minimize_line',

			// Direction aliases
			'arrow_down_line'          => 'arrow_down_bottom_downward_line',
			'leftArrowLg'              => 'arrow_left_backward_line',
			'rightArrowLg'             => 'arrow_left_forward_line',
			'arrow_up_line'            => 'long_arrow_up_top_increase_line',

			// Solid direction aliases
			'down_solid'               => 'arrow_down_bottom_downward_circle_solid',
			'right_solid'              => 'arrow_right_forward_circle_solid',
			'left_solid'               => 'arrow_left_backward_circle_solid',
			'up_solid'                 => 'arrow_up_top_upward_circle_solid',

			// Move aliases
			'bottom_right_line'        => 'arrow_move_up_right_line',
			'bottom_left_line'         => 'arrow_move_up_left_line',
			'top_left_angle_line'      => 'arrow_move_down_left_line',
			'top_right_line'           => 'arrow_move_down_right_line',

			// Utility aliases
			'at_line'                  => 'at_a_mail_line',
			'refresh'                  => 'refresh_reset_cycle_loop_infinity_line',
			'cart_line'                => 'shopping_cart_line',
			'cart_solid'               => 'add_plus_shopping_cart_solid',
			'cog_line'                 => 'settings_tool_function_line',
			'cog_solid'                => 'settings_tool_function_solid',
			'clock'                    => 'clock_reading_time_1_line',
			'book'                     => 'book_line',
			'download_line'            => 'download_1_line',
			'download_solid'           => 'download_1_solid',
			'downlod_bottom_solid'     => 'download_1_solid',

			// Visibility aliases
			'eye'                      => 'view_count_show_visible_eye_open_2_line',
			'hidden_line'              => 'hidden_hide_invisible_line',

			// Location aliases
			'home_line'                => 'home_house_line',
			'home_solid'               => 'home_house_solid',
			'location_line'            => 'location_gps_map_line',
			'location_solid'           => 'location_gps_map_solid',

			// Emotion aliases
			'love_line'                => 'heart_love_wishlist_favourite_line',
			'love_solid'               => 'heart_love_wishlist_favourite_solid',

			// Media aliases
			'play_line'                => 'play_media_video_circle_line',
			'videoplay'                => 'right_triangle_angle_play_arrow_forward_solid',
			'left_angle_solid'         => 'left_triangle_angle_arrow_backward_solid',

			// Shape aliases
			'caretArrow'               => 'caret_up_top_triangle_angle_arrow_upward_solid',
			'rectangle_solid'          => 'square_rounded_solid',
			'triangle_solid'           => 'triangle_shape_solid',

			// Status aliases
			'restriction_line'         => 'restriction_no_stop_line',
			'right_circle_line'        => 'correct_save_check_circle_line',
			'save_line'                => 'correct_save_check_line',
			'search_line'              => 'search_magnify_line',
			'search_solid'             => 'search_magnify_solid',

			// Warning aliases
			'notice_circle_solid'      => 'warning_circle_solid',
			'notice_solid'             => 'warning_triangle_solid',
			'warning_circle_line'      => 'warning_circle_line',
			'warning_triangle_line'    => 'warning_triangle_line',

			// Category aliases
			'cat1'                     => 'category_file_documents_1_solid',
			'cat2'                     => 'category_book_line',
			'cat3'                     => 'category_file_documents_2_line',
			'cat4'                     => 'category_file_documents_3_line',
			'cat5'                     => 'category_file_documents_3_solid',
			'cat6'                     => 'category_file_documents_4_line',
			'cat7'                     => 'category_book_line',

			// Comment aliases
			'commentCount1'            => 'messege_comment_1_line',
			'commentCount2'            => 'messege_comment_3_solid',
			'commentCount3'            => 'messege_comment_3_line',
			'commentCount4'            => 'messege_comment_6_line',
			'commentCount5'            => 'messege_comment_7_line',
			'commentCount6'            => 'messege_comment_8_line',
			'comment'                  => 'messege_comment_4_line',

			// Date aliases
			'date1'                    => 'calendar_date_4_line',
			'date2'                    => 'calendar_date_1_solid',
			'date3'                    => 'calendar_date_2_line',
			'date4'                    => 'calendar_date_4_solid',
			'date5'                    => 'calendar_date_3_line',
			'calendar'                 => 'calendar_date_3_line',

			// Reading time aliases
			'readingTime1'             => 'clock_reading_time_3_line',
			'readingTime2'             => 'clock_reading_time_2_line',
			'readingTime3'             => 'book_reading_time_line',
			'readingTime4'             => 'clock_reading_time_1_line',
			'readingTime5'             => 'hourglass_timer_time_line',

			// Tag aliases
			'tag1'                     => 'tag_bookmark_save_favourite_mark_discount_sale_line',
			'tag2'                     => 'price_tag_label_category_sale_discount_solid',
			'tag3'                     => 'price_tag_label_category_sale_discount_line',
			'tag4'                     => 'price_tag_offer_sale_coupon_solid',
			'tag5'                     => 'price_tag_label_category_sale_discount_line',
			'tag6'                     => 'growth_increase_up_solid',

			// View count aliases
			'viewCount1'               => 'view_count_show_visible_eye_open_1_line',
			'viewCount2'               => 'view_count_show_visible_eye_open_2_line',
			'viewCount3'               => 'view_count_show_visible_eye_open_3_line',
			'viewCount4'               => 'view_count_show_visible_eye_open_4_solid',
			'viewCount5'               => 'view_count_show_visible_eye_open_5_solid',
			'viewCount6'               => 'view_count_show_visible_eye_open_5_solid',

			// Author aliases
			'author1'                  => 'author_user_human_1_line',
			'author2'                  => 'author_user_human_4_line',
			'author3'                  => 'author_user_human_4_solid',
			'author4'                  => 'author_user_human_4_line',
			'author5'                  => 'author_user_human_3_solid',
			'author6'                  => 'author_user_human_6_line',
			'user'                     => 'author_user_human_3_line',

			// Device aliases
			'desktop'                  => 'desktop_monitor_computer_line',
			'laptop'                   => 'laptop_computer_line',
			'tablet'                   => 'tablet_ipad_pad_line',
			'mobile'                   => 'mobile_smartphone_phone_line',

			// Emoji aliases
			'angry_line'               => 'angry_emoji_line',
			'angry_solid'              => 'angry_emoji_solid',
			'confused_line'            => 'confused_emoji_line',
			'confused_solid'           => 'confused_emoji_solid',
			'happy_line'               => 'happy_emoji_line',
			'happy_solid'              => 'happy_emoji_solid',
			'smile_line'               => 'smile_emoji_line',
			'smile_solid'              => 'smile_emoji_solid',

			// Social aliases
			'share_line'               => 'social_community_line',
			'share'                    => 'share_social_solid',
			'apple_solid'              => 'apple_logo_icon_solid',
			'android_solid'            => 'android_logo_icon_solid',
			'google_solid'             => 'google_logo_icon_solid',
			'messenger'                => 'messenger_logo_icon_solid',
			'microsoft_solid'          => 'microsoft_logo_icon_solid',
			'mail'                     => 'mail_email_messege_solid',
			'facebook'                 => 'facebook_logo_icon_solid',
			'twitter'                  => 'twitter_x_logo_icon_line',
			'link'                     => 'link_chains_line',

			// Misc aliases
			'media_document'           => 'media_document',
			'arrowDown2'               => 'arrow_down_dropdown_maximize_chevron_line',
			'setting'                  => 'settings_tool_function_solid',
			'upload_solid'             => 'upload_1_solid',

			// Empty aliases for missing icons (return empty string)
			'correct_solid'            => 'correct_save_check_circle_line',
			'dot_solid'                => 'dot_circle_solid',
			'right_circle_solid'       => 'correct_save_check_circle_solid',
			'full_screen'              => 'full_screen_corners_out_solid',
			'zoom_in'                  => 'zoom_in_magnifying_glass_plus_line',
			'zoom_out'                 => 'zoom_out_magnifying_glass_minus_line',
			'gallery_indicator'        => 'gallery_indicator_image_solid',
			'ascending'                => 'sort_ascending_order_solid',
			'descending'               => 'sort_descending_order_line',
			'unlink'                   => 'unlink_link_break_line',
			'rocket'                   => 'rocket_fly_boost_launch_pro_line',
			'unlock'                   => 'unlocked_open_security_solid',
			'connect'                  => 'plugin_connect_socket_integration_solid',
			'leftAngle'                => 'arrow_left_previous_backward_chevron_line',
			'rightAngle'               => 'right_triangle_angle_play_arrow_forward_line',
			'plus2'                    => '',
			'hamicon_1'                => 'left_align_1_line',
			// extra in assets\img\iconpack folder
			'hamicon_2'                => 'hemicon_2_line',
			'hamicon_3'                => 'hemicon_3_line',
			'hamicon_4'                => 'hamicon_5_line',
			'hamicon_5'                => 'hamicon_4_sloid',
			'hamicon_6'                => 'hamicon_6_line',
			'instagram_solid'          => 'instagram_logo_icon_solid',
			'linkedin'                 => 'linkedin_logo_icon_solid',
			'pause_solid'              => 'pause_solid',
			'pinterest'                => 'pinterest_logo_icon_solid',
			'reddit'                   => 'reddit_logo_icon_solid',
			'skype'                    => 'skype_logo_icon_solid',
			'tiktok_lite_solid'        => 'tiktok_logo_icon_line',
			'tiktok_solid'             => 'tiktok_logo_icon_circle_solid',
			'whatsapp'                 => 'whatsapp_logo_icon_solid',
			'wordpress_lite_solid'     => 'wordpress_logo_icon_solid',
			'wordpress_solid'          => 'wordpress_logo_icon_2_solid',
			'wrong_solid'              => 'cross_close_x_minimize_circle_solid',
			'youtube_solid'            => 'youtube_logo_icon_solid',
			'five_star_line'           => 'star_rating_line',
			'rightAngleBold'           => 'arrow_right_next_forward_chevron_line',
			'leftAngleBold'            => 'arrow_left_previous_backward_chevron_line',
			'plus'                     => 'plus',
			'reset_left_line'          => 'refresh_reset_cycle_loop_infinity_line',
		);
		// Return resolved alias or false if not found
		return isset( $icon_aliases[ $icon_name ] ) ? $icon_aliases[ $icon_name ] : $icon_name;
	}
}
