<?php
/**
 * Sanitizer for uploaded SVG font files.
 *
 * @package ULTP
 */

namespace ULTP;

defined( 'ABSPATH' ) || exit;

/**
 * Keeps only the parts of an SVG file that describe a font.
 *
 * Allowlist based: every element and attribute that is not listed is removed,
 * so scripts, event handlers, links, styles and foreign markup cannot survive.
 */
class Svg_Font_Sanitizer {

	const SVG_NS = 'http://www.w3.org/2000/svg';

	/**
	 * Elements kept, by local name. Anything else is removed with its children.
	 *
	 * @var array
	 */
	private static $allowed_elements = array(
		'svg',
		'defs',
		'metadata',
		'title',
		'desc',
		'font',
		'font-face',
		'glyph',
		'missing-glyph',
		'hkern',
		'vkern',
		'path',
	);

	/**
	 * Attributes kept, by local name. Namespaced attributes are always removed.
	 *
	 * @var array
	 */
	private static $allowed_attributes = array(
		// Root and shared.
		'id',
		'version',
		'baseprofile',
		'width',
		'height',
		'viewbox',
		'x',
		'y',
		// <font>.
		'horiz-origin-x',
		'horiz-origin-y',
		'horiz-adv-x',
		'vert-origin-x',
		'vert-origin-y',
		'vert-adv-y',
		// <font-face>.
		'font-family',
		'font-style',
		'font-variant',
		'font-weight',
		'font-stretch',
		'font-size',
		'unicode-range',
		'units-per-em',
		'panose-1',
		'stemv',
		'stemh',
		'slope',
		'cap-height',
		'x-height',
		'accent-height',
		'ascent',
		'descent',
		'widths',
		'bbox',
		'ideographic',
		'alphabetic',
		'mathematical',
		'hanging',
		'v-ideographic',
		'v-alphabetic',
		'v-mathematical',
		'v-hanging',
		'underline-position',
		'underline-thickness',
		'strikethrough-position',
		'strikethrough-thickness',
		'overline-position',
		'overline-thickness',
		// <glyph>, <missing-glyph>, <path>.
		'unicode',
		'glyph-name',
		'd',
		'orientation',
		'arabic-form',
		'lang',
		'transform',
		// <hkern>, <vkern>.
		'u1',
		'u2',
		'g1',
		'g2',
		'k',
	);

	/**
	 * Sanitize an SVG font file in place.
	 *
	 * @param string $path Absolute path to the file.
	 * @return true|\WP_Error True when the file was cleaned and rewritten.
	 */
	public static function sanitize_file( $path ) {
		$svg = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local upload in the temp directory.
		if ( false === $svg || '' === trim( $svg ) ) {
			return self::error();
		}

		$clean = self::sanitize( $svg );
		if ( is_wp_error( $clean ) ) {
			return $clean;
		}

		if ( false === file_put_contents( $path, $clean ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Local upload in the temp directory.
			return self::error();
		}
		return true;
	}

	/**
	 * Sanitize SVG markup.
	 *
	 * @param string $svg SVG markup.
	 * @return string|\WP_Error Clean markup, or an error when the file is not a usable SVG font.
	 */
	public static function sanitize( $svg ) {
		// Entity declarations are never needed by a font and are the basis of XXE and entity-expansion attacks.
		if ( false !== stripos( $svg, '<!ENTITY' ) ) {
			return self::error();
		}

		if ( ! class_exists( '\DOMDocument' ) ) {
			return self::error();
		}

		$use_errors = libxml_use_internal_errors( true );
		$dom        = new \DOMDocument();
		$loaded     = $dom->loadXML( $svg, LIBXML_NONET );
		libxml_clear_errors();
		libxml_use_internal_errors( $use_errors );

		if ( ! $loaded || ! $dom->documentElement || 'svg' !== $dom->documentElement->localName ) {
			return self::error();
		}

		self::clean_node( $dom->documentElement );

		// After cleaning, a file without a font in the SVG namespace is not an SVG font.
		if ( 0 === $dom->getElementsByTagNameNS( self::SVG_NS, 'font' )->length ) {
			return self::error();
		}

		$clean = $dom->saveXML( $dom->documentElement );
		if ( false === $clean ) {
			return self::error();
		}
		return '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $clean;
	}

	/**
	 * Remove disallowed attributes from an element and disallowed nodes below it.
	 *
	 * @param \DOMElement $element Element to clean.
	 */
	private static function clean_node( \DOMElement $element ) {
		$remove = array();
		foreach ( $element->attributes as $attribute ) {
			if ( $attribute->namespaceURI || ! in_array( strtolower( $attribute->localName ), self::$allowed_attributes, true ) ) {
				$remove[] = $attribute;
			}
		}
		foreach ( $remove as $attribute ) {
			$element->removeAttributeNode( $attribute );
		}

		$children = array();
		foreach ( $element->childNodes as $child ) {
			$children[] = $child;
		}
		foreach ( $children as $child ) {
			if ( XML_ELEMENT_NODE === $child->nodeType ) {
				if ( self::SVG_NS === $child->namespaceURI && in_array( $child->localName, self::$allowed_elements, true ) ) {
					self::clean_node( $child );
				} else {
					$element->removeChild( $child );
				}
			} elseif ( XML_TEXT_NODE !== $child->nodeType ) {
				// Comments, CDATA, processing instructions and entity references.
				$element->removeChild( $child );
			}
		}
	}

	/**
	 * The one error returned for any file that cannot be used.
	 *
	 * @return \WP_Error
	 */
	private static function error() {
		return new \WP_Error( 'ultp_invalid_svg_font', __( 'This SVG file is not a valid SVG font, so it was not uploaded. PostX accepts SVG files only when they contain a font.', 'ultimate-post' ) );
	}
}
