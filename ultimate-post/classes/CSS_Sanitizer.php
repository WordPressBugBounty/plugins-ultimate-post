<?php
/**
 * Sanitizer for the CSS PostX saves and prints.
 *
 * @package ULTP
 */

namespace ULTP;

defined( 'ABSPATH' ) || exit;

/**
 * Checks block, global and preset CSS before it is saved, and again before wp_add_inline_style()
 * prints it. Use CSS_Sanitizer::ultp_esc_css().
 */
class CSS_Sanitizer {

	/**
	 * Property of the declaration ultp_esc_css() is checking. See allow_css_property().
	 *
	 * @var string
	 */
	private static $css_property = '';

	/**
	 * Whitespace in CSS, for trim(): its default list differs between PHP versions.
	 *
	 * @var string
	 */
	const CSS_WHITESPACE = " \t\n\r\f";

	/**
	 * Hosts and at-rules ultp_esc_css() allows, from get_css_rules().
	 *
	 * @var array
	 */
	private static $css_rules = array();

	/**
	 * Declarations ultp_esc_css() has checked in this call, and whether each is safe.
	 *
	 * @var bool[]
	 */
	private static $css_checked = array();

	/**
	 * Hosts and at-rules ultp_esc_css() allows. Each list can be changed with a filter.
	 *
	 * @since 5.1.5
	 * @return array Allowed hosts/at-rules and the explicit Google Fonts consent state.
	 */
	private static function get_css_rules() {
		/**
		 * Hosts a url() in PostX CSS may point to, such as 'example.com'. Empty, the default, allows
		 * any host whose URL has a protocol WordPress allows, subject to Google Fonts consent.
		 * Relative URLs and data: images and fonts are always allowed.
		 *
		 * @since 5.1.5
		 * @param string[] $hosts Hosts.
		 */
		$url_hosts = apply_filters( 'ultp_css_url_hosts', array() );

		/**
		 * Hosts an @import in PostX CSS may load a stylesheet from, over https.
		 *
		 * @since 5.1.5
		 * @param string[] $hosts Hosts. Google Fonts by default, with a separate opt-in check.
		 */
		$import_hosts = apply_filters( 'ultp_css_import_hosts', array( 'fonts.googleapis.com' ) );

		/**
		 * At-rules with a block that PostX CSS may hold, without the `@` or a vendor prefix.
		 *
		 * @since 5.1.5
		 * @param string[] $at_rules At-rule names.
		 */
		$at_rules = apply_filters(
			'ultp_css_at_rules',
			array( 'media', 'supports', 'container', 'layer', 'keyframes', 'font-face', 'page', 'property', 'scope', 'starting-style', 'counter-style', 'font-feature-values', 'font-palette-values' )
		);

		$clean = function ( $items, $pattern ) {
			$items = array_filter(
				is_array( $items ) ? $items : array(),
				function ( $item ) use ( $pattern ) {
					return is_string( $item ) && preg_match( $pattern, $item );
				}
			);
			return array_values( array_unique( array_map( 'strtolower', $items ) ) );
		};
		return array(
			'url_hosts'            => $clean( $url_hosts, '/^[a-z0-9.-]+$/i' ),
			'import_hosts'         => $clean( $import_hosts, '/^[a-z0-9.-]+$/i' ),
			'at_rules'             => $clean( $at_rules, '/^[a-z][a-z-]*$/i' ),
			'google_fonts_enabled' => ultimate_post()->is_google_fonts_enabled(),
		);
	}

	/**
	 * Sanitize a stylesheet before it is saved or printed.
	 *
	 * Block CSS is written by the editor, and anyone who may edit a post can send any CSS to the route
	 * that saves it, so the CSS is checked here, not only stripped of tags:
	 *
	 * - wp_strip_all_tags() first: no markup, so nothing can close the <style> element.
	 * - The CSS is read as a browser reads it: rules, at-rules and declarations, with strings, escapes
	 *   and url() kept whole and comments removed.
	 * - Each declaration is kept only if core's safecss_filter_attr() keeps it. Core's property list
	 *   is meant for style="" attributes, so the declaration's own property is allowed; its value gets
	 *   core's tests, which reject expression(), escapes outside strings and functions core does not
	 *   know. Each url() is checked first, with wp_kses_bad_protocol() as core does but only http and
	 *   https, and data: is allowed only for images and fonts. Strings are emptied for the test: they cannot run anything.
	 * - behavior and -moz-binding are removed. @import is kept only over https from the hosts in
	 *   `ultp_css_import_hosts` (Google Fonts, which PostX writes when Google Fonts are on). At-rules
	 *   with a block are kept from the list in `ultp_css_at_rules`. Anything else is removed.
	 * - A site may limit url() to the hosts in `ultp_css_url_hosts`; by default any host is allowed.
	 *   Google Fonts imports and URLs additionally require explicit opt-in, including in saved CSS.
	 *
	 * Kept rules retain their contents; approved imports are deduplicated and moved before rules,
	 * where browsers can load them. This also protects old saved CSS without rewriting site data.
	 *
	 * @since 5.1.5
	 * @param string $css CSS.
	 * @return string Sanitized CSS.
	 */
	public static function ultp_esc_css( $css ) {
		return self::sanitize_css( $css, false );
	}

	/**
	 * Sanitize stored CSS without destroying font choices while remote fonts are off.
	 *
	 * Storage is not output: callers MUST use ultp_esc_css() before printing this CSS.
	 *
	 * @param string $css          New stylesheet.
	 * @return string Sanitized CSS for storage only.
	 */
	public static function ultp_sanitize_css_for_storage( $css ) {
		return self::sanitize_css( $css, true );
	}

	/**
	 * Parse CSS with the same security rules for storage and output.
	 *
	 * @param string $css         Stylesheet.
	 * @param bool   $for_storage Whether to preserve remote font references without loading them.
	 * @return string
	 */
	private static function sanitize_css( $css, $for_storage ) {
		static $done = array();

		if ( ! is_string( $css ) || '' === $css ) {
			return '';
		}

		self::$css_rules = self::get_css_rules();
		if ( $for_storage ) {
			self::$css_rules['google_fonts_enabled'] = true;
		}
		$key             = md5( $css . wp_json_encode( self::$css_rules ) );
		if ( isset( $done[ $key ] ) ) {
			return $done[ $key ];
		}

		self::$css_checked = array();
		$text              = wp_strip_all_tags( $css );
		$pos               = 0;
		add_filter( 'safe_style_css', array( __CLASS__, 'allow_css_property' ), PHP_INT_MAX );
		add_filter( 'safecss_filter_attr_allow_css', array( __CLASS__, 'allow_stylesheet_css_functions' ), PHP_INT_MAX, 2 );
		try {
			$out = self::sanitize_css_block( $text, $pos, 0 );
		} finally {
			remove_filter( 'safe_style_css', array( __CLASS__, 'allow_css_property' ), PHP_INT_MAX );
			remove_filter( 'safecss_filter_attr_allow_css', array( __CLASS__, 'allow_stylesheet_css_functions' ), PHP_INT_MAX );
		}
		$out = self::hoist_css_imports( $out );
		// Nothing above can put '<' next to '/'; this keeps it so.
		$out = str_replace( '</', '<\/', $out );

		if ( count( $done ) > 50 ) {
			$done = array();
		}
		$done[ $key ] = $out;
		return $out;
	}

	/**
	 * Move approved top-level imports ahead of rules, including concatenated block CSS.
	 *
	 * @param string $css Already sanitized stylesheet.
	 * @return string
	 */
	private static function hoist_css_imports( $css ) {
		if ( false === stripos( $css, '@import' ) ) {
			return $css;
		}
		$pos     = 0;
		$rules   = '';
		$imports = array();
		while ( $pos < strlen( $css ) ) {
			$start = $pos;
			list( $item, $delimiter ) = self::read_css_item( $css, $pos );
			if ( '{' === $delimiter ) {
				self::skip_css_block( $css, $pos );
			} elseif ( ';' === $delimiter && preg_match( '/^\s*@import\b/i', $item ) ) {
				$imports[] = trim( $item ) . ';';
				continue;
			}
			$rules .= substr( $css, $start, $pos - $start );
		}
		return implode( '', array_unique( $imports ) ) . $rules;
	}

	/**
	 * Allow the property of the declaration ultp_esc_css() is checking. Only hooked during that check.
	 *
	 * @since 5.1.5
	 * @param string[] $properties Properties safecss_filter_attr() allows.
	 * @return string[]
	 */
	public static function allow_css_property( $properties ) {
		if ( is_array( $properties ) && '' !== self::$css_property ) {
			$properties[] = self::$css_property;
		}
		return $properties;
	}

	/**
	 * Allow the stylesheet functions core's test does not know, such as format() in @font-face and
	 * counter() in content. Only hooked while ultp_esc_css() runs.
	 *
	 * Each is removed from the test string when its arguments are only numbers, units, names, `#`,
	 * `,`, `/`, spaces and emptied strings, innermost first; then core's own check runs on what is left.
	 *
	 * @since 5.1.5
	 * @param bool   $allow_css       Whether core considers the CSS safe.
	 * @param string $css_test_string The CSS declaration, with url(), var(), calc() etc. removed.
	 * @return bool
	 */
	public static function allow_stylesheet_css_functions( $allow_css, $css_test_string ) {
		if ( $allow_css || ! is_string( $css_test_string ) ) {
			return $allow_css;
		}

		// Gradients too: core allows them only in background and background-image.
		$functions = 'format|local|tech|counters?|attr|env|fit-content'
			. '|(?:repeating-)?(?:linear|radial|conic)-gradient|gradient|color-stop'
			. '|rgba?|hsla?|hwb|lab|lch|oklab|oklch|color-mix|light-dark'
			. '|blur|brightness|contrast|drop-shadow|grayscale|hue-rotate|invert|opacity|saturate|sepia'
			. '|cubic-bezier|steps';
		for ( $i = 0; $i < 3; $i++ ) {
			$stripped = preg_replace( '/(?<![\w-])(?:-webkit-|-moz-|-o-|-ms-)?(?:' . $functions . ')\([\w\s.,%#\/"+-]*\)/i', '', $css_test_string );
			if ( null === $stripped || $stripped === $css_test_string ) {
				break;
			}
			$css_test_string = $stripped;
		}

		// The same test as safecss_filter_attr().
		return 0 === preg_match( '%[\\\(&=}]|/\*%', $css_test_string );
	}

	/**
	 * Sanitize the items of one block of a stylesheet, up to its closing brace.
	 *
	 * @since 5.1.5
	 * @param string $text  The whole stylesheet.
	 * @param int    $pos   Where the block starts; moved past its closing brace.
	 * @param int    $depth 0 for the top level.
	 * @return string The kept items, without the block's braces.
	 */
	private static function sanitize_css_block( $text, &$pos, $depth ) {
		$out    = '';
		$length = strlen( $text );
		while ( $pos < $length ) {
			list( $item, $delimiter ) = self::read_css_item( $text, $pos );

			if ( '{' === $delimiter ) {
				if ( $depth >= 16 ) {
					self::skip_css_block( $text, $pos );
					continue;
				}
				$inner = self::sanitize_css_block( $text, $pos, $depth + 1 );
				if ( self::is_safe_css_prelude( $item ) ) {
					$out .= $item . '{' . $inner . '}';
				}
				continue;
			}

			if ( '' === trim( $item, self::CSS_WHITESPACE ) ) {
				$out .= $item . ( ';' === $delimiter ? ';' : '' );
			} elseif ( $depth ? self::is_safe_css_declaration( $item ) : self::is_safe_css_statement( $item ) ) {
				$out .= $item . ( ';' === $delimiter ? ';' : '' );
			}

			if ( '}' === $delimiter ) {
				if ( $depth ) {
					return $out;
				}
				// A stray brace at the top level is kept: a browser makes it part of the next selector,
				// which drops that rule, so removing it would change the page.
				$out .= '}';
			}
		}
		return $out;
	}

	/**
	 * Move past a block whose opening brace was just read, without keeping anything from it.
	 *
	 * @since 5.1.5
	 * @param string $text The whole stylesheet.
	 * @param int    $pos  Moved past the block's closing brace.
	 * @return void
	 */
	private static function skip_css_block( $text, &$pos ) {
		$level  = 1;
		$length = strlen( $text );
		while ( $level && $pos < $length ) {
			list( , $delimiter ) = self::read_css_item( $text, $pos );
			if ( '{' === $delimiter ) {
				++$level;
			} elseif ( '}' === $delimiter ) {
				--$level;
			}
		}
	}

	/**
	 * Read the next item of a stylesheet: the text up to a `;`, `{` or `}` that is not inside a
	 * string, a url(), parentheses or brackets, or escaped. Comments are left out of the text.
	 *
	 * @since 5.1.5
	 * @param string $text The whole stylesheet.
	 * @param int    $pos  Where to start; moved past the delimiter.
	 * @return array The item's text, and its delimiter or '' at the end of the stylesheet.
	 */
	private static function read_css_item( $text, &$pos ) {
		$length = strlen( $text );
		$item   = '';
		$nested = 0;
		while ( $pos < $length ) {
			$run   = strcspn( $text, "\\\"'/;{}()[]", $pos );
			$item .= substr( $text, $pos, $run );
			$pos  += $run;
			if ( $pos >= $length ) {
				break;
			}
			$char = $text[ $pos ];

			if ( '\\' === $char ) {
				$item .= substr( $text, $pos, 2 );
				$pos  += 2;
			} elseif ( '"' === $char || "'" === $char ) {
				$end   = self::css_string_end( $text, $pos );
				$item .= substr( $text, $pos, $end - $pos );
				$pos   = $end;
			} elseif ( '/' === $char ) {
				if ( '*' === substr( $text, $pos + 1, 1 ) ) {
					$close = strpos( $text, '*/', $pos + 2 );
					$pos   = false === $close ? $length : $close + 2;
				} else {
					$item .= '/';
					++$pos;
				}
			} elseif ( '(' === $char && self::ends_with_url( $item ) && ! preg_match( '/\G\(\s*[\'"]/', $text, $match, 0, $pos ) ) {
				// An unquoted url() runs to the next `)` that is not escaped, whatever it holds.
				$end = $pos + 1;
				while ( $end < $length ) {
					$end += strcspn( $text, ')\\', $end );
					if ( $end >= $length || ')' === $text[ $end ] ) {
						break;
					}
					$end += 2;
				}
				$end   = min( $length, $end + 1 );
				$item .= substr( $text, $pos, $end - $pos );
				$pos   = $end;
			} elseif ( '(' === $char || '[' === $char ) {
				++$nested;
				$item .= $char;
				++$pos;
			} elseif ( ')' === $char || ']' === $char ) {
				$nested = max( 0, $nested - 1 );
				$item  .= $char;
				++$pos;
			} elseif ( $nested ) {
				$item .= $char;
				++$pos;
			} else {
				++$pos;
				return array( $item, $char );
			}
		}
		return array( $item, '' );
	}

	/**
	 * Position just after the CSS string that starts at $pos. Like a browser, a string also ends
	 * before a line break.
	 *
	 * @since 5.1.5
	 * @param string $text Text.
	 * @param int    $pos  Position of the opening quote.
	 * @return int
	 */
	private static function css_string_end( $text, $pos ) {
		$quote  = $text[ $pos ];
		$length = strlen( $text );
		$i      = $pos + 1;
		while ( $i < $length ) {
			$i += strcspn( $text, $quote . "\\\n\r\f", $i );
			if ( $i >= $length ) {
				break;
			}
			if ( $quote === $text[ $i ] ) {
				return $i + 1;
			}
			if ( '\\' !== $text[ $i ] ) {
				return $i;
			}
			$i += 2;
		}
		return $length;
	}

	/**
	 * Whether the text ends with the function name `url`, as in `url(`.
	 *
	 * @since 5.1.5
	 * @param string $text Text before a `(`.
	 * @return bool
	 */
	private static function ends_with_url( $text ) {
		$length = strlen( $text );
		if ( $length < 3 || 'url' !== strtolower( substr( $text, -3 ) ) ) {
			return false;
		}
		return 3 === $length || ! preg_match( '/[\w-]/', $text[ $length - 4 ] );
	}

	/**
	 * Whether a rule's selector or a block at-rule's prelude may be kept. Nothing in a selector can
	 * run or load anything; the at-rules are a fixed list.
	 *
	 * @since 5.1.5
	 * @param string $prelude Text before the `{`.
	 * @return bool
	 */
	private static function is_safe_css_prelude( $prelude ) {
		$prelude = trim( $prelude, self::CSS_WHITESPACE );
		if ( '' === $prelude ) {
			return false;
		}
		if ( '@' !== $prelude[0] ) {
			return true;
		}
		return preg_match( '/^@(?:-webkit-|-moz-)?([a-z-]+)/i', $prelude, $match )
			&& in_array( strtolower( $match[1] ), self::$css_rules['at_rules'], true );
	}

	/**
	 * Whether a top-level statement may be kept: an @import of Google Fonts, or an @layer list.
	 *
	 * @since 5.1.5
	 * @param string $statement Text before the `;`.
	 * @return bool
	 */
	private static function is_safe_css_statement( $statement ) {
		$statement = trim( $statement, self::CSS_WHITESPACE );
		if ( preg_match( '/^@layer\s+[\w.-]+(?:\s*,\s*[\w.-]+)*$/i', $statement ) ) {
			return true;
		}
		if ( ! preg_match( '/^@import\s+(?:url\(\s*([\'"])([^\'"\\\\]*)\1\s*\)|url\(\s*([^\'"()\s\\\\]*)\s*\)|([\'"])([^\'"\\\\]*)\4)$/i', $statement, $match ) ) {
			return false;
		}
		$url = $match[2] . ( isset( $match[3] ) ? $match[3] : '' ) . ( isset( $match[5] ) ? $match[5] : '' );
		return 'https' === strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) )
			&& self::is_google_font_url_allowed( $url )
			&& in_array( strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) ), self::$css_rules['import_hosts'], true );
	}

	/**
	 * Whether a declaration may be kept. See ultp_esc_css().
	 *
	 * @since 5.1.5
	 * @param string $item Text of the declaration.
	 * @return bool
	 */
	private static function is_safe_css_declaration( $item ) {
		$declaration = trim( $item, self::CSS_WHITESPACE );
		if ( isset( self::$css_checked[ $declaration ] ) ) {
			return self::$css_checked[ $declaration ];
		}

		$safe = false;
		// behavior and -moz-binding load code in old browsers, from a URL core would allow.
		if (
			preg_match( '/^(--[\w-]+|-?[a-z][a-z0-9-]*)\s*:(.*)$/is', $declaration, $parts ) &&
			! preg_match( '/^(?:-ms-)?behaviou?r$|^-moz-binding$/i', $parts[1] )
		) {
			$value = self::css_test_value( $parts[2] );
			// Core's test removes var() whole, its fallback too, so escapes and expression() are
			// rejected here as well, anywhere outside a string.
			if (
				null !== $value &&
				false === strpos( $value, ';' ) &&
				false === strpos( $value, '\\' ) &&
				! preg_match( '/expression\s*\(/i', $value )
			) {
				// Kept only if core returns the declaration whole (it removes line breaks and tabs).
				$test               = trim( $parts[1] . ':' . $value, self::CSS_WHITESPACE );
				self::$css_property = $parts[1];
				$safe               = str_replace( array( "\n", "\r", "\t" ), '', $test ) === safecss_filter_attr( $test );
				self::$css_property = '';
			}
		}

		self::$css_checked[ $declaration ] = $safe;
		return $safe;
	}

	/**
	 * A declaration value as safecss_filter_attr() should test it: each url() checked and removed,
	 * each string emptied.
	 *
	 * @since 5.1.5
	 * @param string $value Declaration value.
	 * @return string|null Value to test, or null when a url() is not safe.
	 */
	private static function css_test_value( $value ) {
		$out    = '';
		$length = strlen( $value );
		$pos    = 0;
		while ( $pos < $length ) {
			$run  = strcspn( $value, "\\\"'(", $pos );
			$out .= substr( $value, $pos, $run );
			$pos += $run;
			if ( $pos >= $length ) {
				break;
			}
			$char = $value[ $pos ];

			if ( '\\' === $char ) {
				// Left in: core's test rejects an escape outside a string.
				$out .= substr( $value, $pos, 2 );
				$pos += 2;
			} elseif ( '"' === $char || "'" === $char ) {
				$pos  = self::css_string_end( $value, $pos );
				$out .= '""';
			} elseif ( self::ends_with_url( $out ) ) {
				if ( ! preg_match( '/\G\(\s*(?:"([^"\\\\\n\r\f]*)"|\'([^\'\\\\\n\r\f]*)\'|([^\'"()\s\\\\]*))\s*\)/', $value, $match, 0, $pos ) ) {
					return null;
				}
				$url = implode( '', array_slice( $match, 1 ) );
				if ( ! self::is_safe_css_url( $url ) ) {
					return null;
				}
				$out  = substr( $out, 0, -3 );
				$pos += strlen( $match[0] );
			} else {
				$out .= '(';
				++$pos;
			}
		}
		return $out;
	}

	/**
	 * Whether a url() in CSS may be kept: a relative URL or an http(s) URL that
	 * wp_kses_bad_protocol() leaves as it is (as safecss_filter_attr() checks it, with only http and
	 * https allowed), on a host `ultp_css_url_hosts` allows; or a data: image or font.
	 *
	 * @since 5.1.5
	 * @param string $url URL, without quotes.
	 * @return bool
	 */
	private static function is_safe_css_url( $url ) {
		$url = trim( $url, self::CSS_WHITESPACE );
		if ( '' === $url ) {
			return false;
		}
		if ( preg_match( '#^data:(?:image|font|application/(?:x-)?font)[\w./+-]*[;,]#i', $url ) ) {
			return true;
		}
		if ( wp_kses_bad_protocol( $url, array( 'http', 'https' ) ) !== $url ) {
			return false;
		}
		// A site may limit the hosts; a relative URL has none.
		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		if ( ! self::is_google_font_url_allowed( $url ) ) {
			return false;
		}
		return empty( self::$css_rules['url_hosts'] ) || ! $host || in_array( $host, self::$css_rules['url_hosts'], true );
	}

	/**
	 * Enforce Google Fonts consent independently of the configurable host allowlists.
	 *
	 * @param string $url CSS import or declaration URL.
	 * @return bool
	 */
	private static function is_google_font_url_allowed( $url ) {
		$host = rtrim( strtolower( rawurldecode( (string) wp_parse_url( $url, PHP_URL_HOST ) ) ), '.' );
		return self::$css_rules['google_fonts_enabled'] || ! in_array( $host, array( 'fonts.googleapis.com', 'fonts.gstatic.com', 'fonts.google.com' ), true );
	}
}
