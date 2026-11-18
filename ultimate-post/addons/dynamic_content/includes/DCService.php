<?php
namespace ULTP;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 *
 * Dynamic Content
 *
 * @package ULTP
 * @since 4.1.1
 */
final class DCService {

	/**
	 * Resolve a target under the public-render or explicit editor-preview policy.
	 *
	 * @param int|string|null $post_id Target, or the current loop post when empty.
	 * @param bool            $preview Explicit editor service call, never a saved block flag.
	 * @return \WP_Post|null Authorized target.
	 */
	private static function get_target_post( $post_id, $preview = false, $visited = array() ) {
		if ( ! is_scalar( $post_id ) && null !== $post_id ) {
			return null;
		}
		if ( empty( $post_id ) ) {
			$post_id = in_the_loop() ? get_the_ID() : ( isset( $GLOBALS['post']->ID ) ? $GLOBALS['post']->ID : 0 );
		}
		if ( ! is_numeric( $post_id ) || (int) $post_id <= 0 ) {
			return null;
		}
		$post = get_post( (int) $post_id );
		if ( ! $post instanceof \WP_Post ) {
			return null;
		}
		if ( isset( $visited[ $post->ID ] ) || count( $visited ) >= 32 ) {
			return null;
		}
		$visited[ $post->ID ] = true;
		if ( 'attachment' === $post->post_type && ! empty( $post->post_parent ) && get_post( $post->post_parent ) ) {
			$parent = self::get_target_post( $post->post_parent, false, $visited );
			if ( ! $parent && ( ! $preview || ! self::get_target_post( $post->post_parent, true, $visited ) ) ) {
				return null;
			}
		}
		if ( $preview ) {
			return current_user_can( 'edit_post', $post->ID ) ? $post : null;
		}
		if ( ! is_post_type_viewable( $post->post_type ) || post_password_required( $post ) ) {
			return null;
		}
		if ( is_post_publicly_viewable( $post ) ) {
			return $post;
		}
		// A logged-in viewer may retain WordPress-authorized access to a private post.
		return 'private' === get_post_status( $post->ID ) && is_user_logged_in() && current_user_can( 'read_post', $post->ID ) ? $post : null;
	}

	/**
	 * Gets the dynamic data to show in the editor
	 *
	 * @since 4.1.1
	 *
	 * @param array $args - Arguments
	 *
	 * @return string
	 */
	public static function get_dynamic_data( $args ) {
		$post_id   = $args['post_id'];
		$key       = $args['key'];
		$data_type = $args['data_type'];

		$res            = '';
		$process_string = true;

		switch ( $data_type ) {
			case 'link':
				$res            = self::get_link( $post_id, $key, true );
				$process_string = false;
				break;
			case 'post_types':
				$res            = self::get_post_types();
				$process_string = false;
				break;
			case 'post_info':
				$res = self::get_post_info( $post_id, $key, true );
				break;
			case 'author_info':
				$res = self::get_author_info( $post_id, $key, true );
				break;
			case 'site_info':
				$res = self::get_site_info( $key );
				break;
			case 'cf':
				$res            = self::get_field_value( $post_id, $key, true );
				$process_string = false;
				break;
			case 'comment_count':
				$res = self::get_comment_count( $post_id, true );
				break;
		}

		return $process_string ? self::process( $res ) : $res;
	}

	/**
	 * Gets the comment count for a post
	 *
	 * @since 4.1.1
	 *
	 * @param string|int|null $post_id - Post Id.
	 *
	 * @param bool            $preview Explicit capability-checked editor preview.
	 *
	 * @return string
	 */
	public static function get_comment_count( $post_id, $preview = false ) {
		$post = self::get_target_post( $post_id, $preview );
		if ( ! $post ) {
			return '';
		}
		$post_id = $post->ID;
		$res = '';

		if ( $post_id !== 0 ) {
			$comments = get_comment_count( $post_id );
			$res      = $comments['total_comments'];
		}

		return $res;
	}

	/**
	 * Applies dynamic content to RichText
	 *
	 * @since 4.1.1
	 *
	 * @param string|int|null $post_id - Post Id.
	 * @param string          $content_src - Key value.
	 *
	 * @return string
	 */
	public static function get_dc_content_for_image( $post_id, $content_src ) {
		$res = '';

		if ( self::is_field_key( $content_src ) ) {
			$res = self::get_field_value( $post_id, $content_src );
		} elseif (
			str_starts_with( $content_src, 'link_' )
		) {
			$res = self::get_link( $post_id, $content_src );
		}

		if ( empty( $res ) ) {
			$res = ULTP_URL . 'assets/img/ultp-placeholder.jpg';
		}

		return $res;
	}

	/**
	 * Applies dynamic content to RichText
	 *
	 * @since 4.1.1
	 *
	 * @param array $attr - block attributes.
	 *
	 * @return string
	 */
	public static function get_dc_content_for_rich_text( &$attr ) {
		$text = '';
		$url  = '';

		$data_src     = $attr['dc']['dataSrc'];
		$content_src  = $attr['dc']['contentSrc'];
		$link_src     = $attr['dc']['linkSrc'];
		$link_enabled = $attr['dc']['linkEnabled'];
		$post_id      = $attr['dc']['postId'];
		$fallback     = $attr['dc']['fallback'];
		$max_length   = $attr['dc']['maxCharLen'];

		if ( str_starts_with( $data_src, 'site_' ) ) {
			$text = self::get_site_info( $data_src );
		} elseif ( str_starts_with( $content_src, 'post_' ) ) {
			$text = self::get_post_info( $post_id, $content_src );
		} elseif ( str_starts_with( $content_src, 'a_' ) ) {
			$text = self::get_author_info( $post_id, $content_src );
		} elseif ( self::is_field_key( $content_src ) ) {
			$text = self::get_field_value( $post_id, $content_src );
		}

		if ( ! empty( $text ) && ! empty( $max_length ) && is_string( $max_length ) && intval( $max_length ) > 0 ) {
			$text = self::limit_str( $text, intval( $max_length ) );
		}

		if ( empty( $text ) ) {
			$text = $fallback;
		}

		$text = $attr['dc']['beforeText'] . $text . $attr['dc']['afterText'];

		if ( ( $link_enabled && $link_src ) ) {
			$url = self::get_link( $post_id, $link_src );
		}

		return array( $text, $url );
	}

	/**
	 * Get Html of Dynamic Content for Blocks
	 *
	 * @since 4.1.1
	 *
	 * @param array $attr
	 *
	 * @return string
	 */
	public static function get_dc_content_for_block( &$attr, $dcContent ) {
		for ( $i = 0; $i < count( $attr['dcFields'] ); $i++ ) {
			$dcGroup = $attr['dcFields'][ $i ];

			if ( ! isset( $dcGroup['fields'] ) || ! is_array( $dcGroup['fields'] ) || ! isset( $dcGroup['id'] ) ) {
				continue;
			}

			$dcContent[ $i ] .= '<div class="ultp-dynamic-content-group-common ultp-dynamic-content-group-' . esc_attr( $dcGroup['id'] ) . '">';

			for ( $j = 0; $j < count( $dcGroup['fields'] ); $j++ ) {
				if ( ! is_array( $dcGroup['fields'][ $j ] ) ) {
					continue;
				}

				// A field saved without every key (hand-written, imported or older content) reads as empty.
				$dcField = wp_parse_args(
					$dcGroup['fields'][ $j ],
					array(
						'id'          => '',
						'icon'        => '',
						'postId'      => '',
						'dataSrc'     => '',
						'contentSrc'  => '',
						'linkEnabled' => false,
						'linkSrc'     => '',
						'beforeText'  => '',
						'afterText'   => '',
						'maxCharLen'  => '',
						'fallback'    => '',
					)
				);

				$url = '';

				if ( ( $dcField['linkEnabled'] && $dcField['linkSrc'] ) ) {
					$url = self::get_link( $dcField['postId'], $dcField['linkSrc'] );
					$url = esc_url( $url );
				}

				if ( ! empty( $url ) ) {
					$dcContent[ $i ] .= '<a class="ultp-dynamic-content-field-anchor" href="' . esc_url( $url ) . '">';
				}

				$dcContent[ $i ] .= '<div class="ultp-dynamic-content-field-common ultp-dynamic-content-field-' . esc_attr( $dcField['id'] ) . '">';
				if ( ! empty( $dcField['icon'] ) ) {
					$dcContent[ $i ] .= '<span class="ultp-dynamic-content-field-icon">' . wp_kses( ultimate_post()->get_svg_icon( $dcField['icon'] ), ultimate_post()->rendered_content_allowed_html() ) . '</span>';
				}
				$dcContent[ $i ] .= $dcField['beforeText'] ? '<p class="ultp-dynamic-content-field-before">' . wp_kses( $dcField['beforeText'], ultimate_post()->ultp_allowed_html_tags() ) . '</p>' : '';

				$text = '';

				if ( str_starts_with( $dcField['dataSrc'], 'site_' ) ) {
					$text = self::get_site_info( $dcField['dataSrc'] );
				} elseif ( str_starts_with( $dcField['contentSrc'], 'post_' ) ) {
					$text = self::get_post_info( $dcField['postId'], $dcField['contentSrc'] );
				} elseif ( str_starts_with( $dcField['contentSrc'], 'a_' ) ) {
					$text = self::get_author_info( $dcField['postId'], $dcField['contentSrc'] );
				} elseif ( self::is_field_key( $dcField['contentSrc'] ) ) {
					$text = self::get_field_value( $dcField['postId'], $dcField['contentSrc'] );
				}

				if ( ! empty( $text ) && ! empty( $dcField['maxCharLen'] ) && intval( $dcField['maxCharLen'] ) > 0 ) {
					$text = self::limit_str( $text, intval( $dcField['maxCharLen'] ) );
				}

				if ( empty( $text ) && '0' !== $text && 0 !== $text ) {
					$text = $dcField['fallback'];
				}

				$dcContent[ $i ] .= '<p class="ultp-dynamic-content-field-dc">' . esc_html( sanitize_text_field( $text ) ) . '</p>';

				$dcContent[ $i ] .= $dcField['afterText'] ? '<p class="ultp-dynamic-content-field-after">' . wp_kses( $dcField['afterText'], ultimate_post()->ultp_allowed_html_tags() ) . '</p>' : '';
				$dcContent[ $i ] .= '</div>';

				if ( ! empty( $url ) ) {
					$dcContent[ $i ] .= '</a>';
				}
			}

			$dcContent[ $i ] .= '</div>';
		}

		return $dcContent;
	}


	/**
	 * Get field value
	 *
	 * @since 4.1.1
	 *
	 * @param int    $post_id
	 * @param string $meta_key
	 *
	 * @param bool   $preview Explicit capability-checked editor preview.
	 *
	 * @return string
	 */
	public static function get_field_value( $post_id, $meta_key, $preview = false ) {
		if ( ! is_string( $meta_key ) || '' === $meta_key ) {
			return '';
		}

		$post = self::get_target_post( $post_id, $preview );
		if ( ! $post ) {
			return '';
		}
		$post_id = $post->ID;

		if ( self::check_prefix( $meta_key, 'cmeta_' ) ) {
			if ( empty( $post_id ) ) {
				return '';
			}
			$key = self::maybe_remove_prefix( $meta_key, 'cmeta_' );
			if ( '' === $key || '0' === $key || is_protected_meta( $key, 'post' ) ) {
				return '';
			}
			// The publisher selected this non-protected key. REST exposure and edit-meta callbacks
			// do not define frontend read access; target visibility is checked above.
			return self::public_field_value( get_post_meta( $post_id, $key, true ) );
		}

		// Other field sources are answered by PostX Pro.
		$value = apply_filters( 'ultp_dc_field_value', null, $meta_key, $post_id );
		if ( null !== $value ) {
			return self::public_field_value( $value );
		}

		return '';
	}

	/**
	 * Keep display values and safe image/repeater arrays; never serialize account internals.
	 *
	 * @param mixed $value Field provider value.
	 * @param int   $depth Nested-array depth.
	 * @return mixed Display value, preserving supported scalar and array formats.
	 */
	private static function public_field_value( $value, $depth = 0 ) {
		if ( $depth > 32 ) {
			return '';
		}
		if ( $value instanceof \WP_User ) {
			return (string) $value->display_name;
		}
		if ( is_array( $value ) ) {
			$clean = array();
			foreach ( $value as $key => $item ) {
				if ( in_array( $key, array( 'user_pass', 'user_activation_key', 'session_tokens' ), true ) ) {
					continue;
				}
				$clean[ $key ] = self::public_field_value( $item, $depth + 1 );
			}
			return $clean;
		}
		return is_scalar( $value ) ? $value : '';
	}

	/**
	 * Post info
	 *
	 * @since 4.1.1
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key Key value.
	 *
	 * @param bool   $preview Explicit capability-checked editor preview.
	 *
	 * @return string
	 */
	public static function get_post_info( $post_id, $key, $preview = false ) {
		if ( ! is_string( $key ) || '' === $key ) {
			return '';
		}

		$key = self::maybe_remove_prefix( $key, 'post_' );

		$res = '';

		$post = self::get_target_post( $post_id, $preview );
		if ( ! $post ) {
			return '';
		}
		$post_id = $post->ID;

		switch ( $key ) {
			case 'id':
				$res = $post_id;
				break;
			case 'title':
				$res = get_the_title( $post_id );
				break;
			case 'excerpt':
				if ( ! doing_filter( 'get_the_excerpt' ) ) {
					$res = get_the_excerpt( $post_id );
				}
				break;
			case 'date':
				$res = get_the_date( '', $post_id );
				break;
			case 'comments':
				$res = strval( get_comment_count( $post_id )['all'] );
				break;
		}

		return $res;
	}

	/**
	 * Author info
	 *
	 * @since 4.1.1
	 *
	 * @param int    $post_id
	 * @param string $key
	 *
	 * @param bool   $preview Explicit capability-checked editor preview.
	 *
	 * @return string
	 */
	public static function get_author_info( $post_id, $key, $preview = false ) {
		if ( ! is_string( $key ) ) {
			return '';
		}
		$key = self::maybe_remove_prefix( $key, 'a_' );
		// These are the explicit author fields offered by the editor, including selected email.
		if ( ! in_array( $key, array( 'display_name', 'first_name', 'last_name', 'nickname', 'description', 'email', 'url' ), true ) ) {
			return '';
		}
		$post = self::get_target_post( $post_id, $preview );
		return $post ? get_the_author_meta( $key, (int) $post->post_author ) : '';
	}

	/**
	 * Site info
	 *
	 * @since 4.1.1
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	public static function get_site_info( $key ) {
		if ( ! is_string( $key ) || '' === $key ) {
			return '';
		}

		// Removes prefix if present
		$key = self::maybe_remove_prefix( $key, 'site_' );

		$res = '';

		switch ( $key ) {
			case 'title':
				$res = get_bloginfo();
				break;
			case 'tagline':
				$res = get_bloginfo( 'description' );
				break;
			case 'arc_title':
				$res = self::get_page_title();
				break;
			case 'arc_desc':
				$res = wp_kses_post( get_the_archive_description() );
				break;
		}

		return $res;
	}

	/**
	 * Gets the link
	 *
	 * @since 4.1.1
	 * @param string $post_id Post ID.
	 * @param string $key Key value.
	 *
	 * @param bool   $preview Explicit capability-checked editor preview.
	 *
	 * @return string
	 */
	public static function get_link( $post_id, $key, $preview = false ) {
		$res = '';

		$post = self::get_target_post( $post_id, $preview );
		if ( ! $post ) {
			return '';
		}
		$post_id = $post->ID;

		if ( self::is_field_key( $key ) ) {
			$res = self::get_field_value( $post_id, $key, $preview );
		} else {
			$author_id = absint( get_post_field( 'post_author', $post_id ) );
			$key       = self::maybe_remove_prefix( $key, 'link_' );
			switch ( $key ) {
				case 'post_featured_image':
					$res = wp_get_attachment_image_url( get_post_thumbnail_id( $post_id ), 'full' );
					break;
				case 'post_permalink':
					$res = get_permalink( $post_id );
					break;
				case 'post_comments':
					$res = get_comments_link( $post_id );
					break;
				case 'a_profile':
					$res = get_avatar_url( $author_id );
					break;
				case 'a_arc':
					$res = get_author_posts_url( $author_id );
					break;
				case 'a_page':
					$res = get_the_author_meta( 'url', $author_id );
					break;
			}
		}

		return $res;
	}

	/**
	 * Replaces substring with a string
	 *
	 * @since 4.1.1
	 * @param string $old Post content string.
	 * @param string $new Value to insert.
	 *
	 * @return string
	 */
	public static function replace( $old, $new ) {
		$pattern = '/<span\s+class\s*=\s*["\']ultp-richtext-dynamic-content["\']\s*>(.*?)<\/span>/';

		// Replaces only the first match, the rest matches are deleted.
		$res = preg_replace_callback(
			$pattern,
			function ( $matches ) use ( &$firstMatch, $new ) {
				if ( ! isset( $firstMatch ) ) {
					$firstMatch = $matches[0];
					return $new;
				} else {
					return '';
				}
			},
			$old
		);

		return is_null( $res ) ? $old : $res;
	}

	/**
	 * Gets the post types
	 *
	 * @since 4.1.1
	 *
	 * @return array[string]
	 */
	public static function get_post_types() {
		$res        = array();
		$post_types = get_post_types(
			array(
				'public' => true,
			),
			'objects'
		);

		foreach ( $post_types as $_ => $value ) {
			if ( 'attachment' !== $value->name ) {
				$res[] = array(
					'value' => $value->name,
					'label' => $value->label,
				);
			}
		}

		return $res;
	}

	/**
	 * Checks if Dynamic Content is active
	 *
	 * @since 4.1.1
	 *
	 * @param array $attr Block attributes.
	 *
	 * @return boolean
	 */
	public static function is_dc_active( &$attr ) {
		return ultimate_post()->get_setting( 'ultp_dynamic_content' ) == 'true' &&
			isset( $attr['dcEnabled'] );
	}

	/**
	 * Get page title
	 *
	 * @since 4.1.1
	 *
	 * @return string
	 */
	private static function get_page_title() {
		$title = '';

		if ( is_singular() ) {
			$title = get_the_title();
		} elseif ( is_search() ) {
			/* translators: %s: Search query. */
			$title = sprintf( esc_html__( 'Search Results for: %s', 'ultimate-post' ), get_search_query() );

			if ( get_query_var( 'paged' ) ) {
				/* translators: %s: Page number. */
				$title .= sprintf( esc_html__( '&nbsp;&ndash; Page %s', 'ultimate-post' ), get_query_var( 'paged' ) );
			}
		} elseif ( is_category() ) {
			$title = single_cat_title( '', false );
		} elseif ( is_tag() ) {
			$title = single_tag_title( '', false );
		} elseif ( is_author() ) {
			$title = get_the_author();
		} elseif ( is_year() ) {
			$title = get_the_date( __( 'Y', 'ultimate-post' ) );
		} elseif ( is_month() ) {
			$title = get_the_date( __( 'F Y', 'ultimate-post' ) );
		} elseif ( is_day() ) {
			$title = get_the_date( __( 'F j, Y', 'ultimate-post' ) );
		} elseif ( is_post_type_archive() ) {
			$title = post_type_archive_title( '', false );
		} elseif ( is_tax() ) {
			$title = single_term_title( '', false );
		} elseif ( is_archive() ) {
			$title = esc_html__( 'Archives', 'ultimate-post' );
		} elseif ( is_404() ) {
			$title = esc_html__( 'Page Not Found', 'ultimate-post' );
		}

		return $title;
	}

	/**
	 * Properly decodes HTML chars from string
	 *
	 * @param mixed $str
	 * @return mixed
	 */
	public static function process( $str ) {
		if ( is_string( $str ) ) {
			$res = esc_html( html_entity_decode( $str ) );
			return str_replace( '&amp;', '&', $res );
		}
		return $str;
	}

	/**
	 * Limits a string by word
	 *
	 * @param string $str
	 * @param int    $limit
	 * @return string
	 */
	public static function limit_str( $str, $limit ) {
		return join( ' ', array_slice( explode( ' ', $str ), 0, $limit ) );
	}

	/**
	 * Whether a dynamic content key is a custom field
	 *
	 * Plain custom metas (`cmeta_`) are handled here; other field sources are answered by PostX Pro.
	 *
	 * @param string $key Key with its prefix.
	 * @return bool
	 */
	private static function is_field_key( $key ) {
		if ( ! is_string( $key ) || '' === $key ) {
			return false;
		}
		if ( self::check_prefix( $key, 'cmeta_' ) ) {
			return true;
		}
		return (bool) apply_filters( 'ultp_dc_is_field_key', false, $key );
	}

	private static function check_prefix( $key, $prefix ) {
		return substr( $key, 0, strlen( $prefix ) ) === $prefix;
	}

	private static function maybe_remove_prefix( $key, $prefix ) {
		if ( substr( $key, 0, strlen( $prefix ) ) === $prefix ) {
			return substr( $key, strlen( $prefix ) );
		}
		return $key;
	}
}
