<?php
/**
 * Global and preset CSS generated from structured controls only.
 *
 * Selectors and declarations are plugin-owned. Every stored control is checked
 * against its value grammar before it reaches CSS. Stored whole-stylesheet
 * fields are never read or output.
 *
 * @package ULTP
 */
namespace ULTP;

defined( 'ABSPATH' ) || exit;

final class GlobalStyles {
	/** Whole-stylesheet fields from earlier versions. Ignored on save and on read. */
	const LEGACY_CSS_FIELDS = array( 'globalCSS', 'rootCSS', 'presetTypoCSS' );

	/** Typography presets in output order. Other stored keys never become CSS. */
	const TYPO_KEYS = array(
		'Heading_typo',
		'Body_and_Others_typo',
		'body_typo',
		'paragraph_1_typo',
		'paragraph_2_typo',
		'paragraph_3_typo',
		'heading_h1_typo',
		'heading_h2_typo',
		'heading_h3_typo',
		'heading_h4_typo',
		'heading_h5_typo',
		'heading_h6_typo',
	);

	const PARENT_TYPO_KEYS = array( 'Heading_typo', 'Body_and_Others_typo' );

	const SYSTEM_FONTS = array( 'Arial', 'Tahoma', 'Verdana', 'Helvetica', 'Times New Roman', 'Trebuchet MS', 'Georgia' );

	const DEFAULT_PRESET_COLORS = array( '#037fff', '#026fe0', '#071323', '#132133', '#34495e', '#787676', '#f0f2f3', '#f8f9fa', '#ffffff' );

	const DEFAULT_COLORS = array(
		'Base_1_color'     => '#f4f4ff',
		'Base_2_color'     => '#dddff8',
		'Base_3_color'     => '#B4B4D6',
		'Primary_color'    => '#3323f0',
		'Secondary_color'  => '#4a5fff',
		'Tertiary_color'   => '#FFFFFF',
		'Contrast_3_color' => '#545472',
		'Contrast_2_color' => '#262657',
		'Contrast_1_color' => '#10102e',
		'Over_Primary_color' => '#ffffff',
	);

	const DEFAULT_GRADIENTS = array(
		'Primary_to_Secondary_to_Right_gradient'  => 'linear-gradient(90deg, var(--postx_preset_Primary_color) 0%, var(--postx_preset_Secondary_color) 100%)',
		'Primary_to_Secondary_to_Bottom_gradient' => 'linear-gradient(180deg, var(--postx_preset_Primary_color) 0%, var(--postx_preset_Secondary_color) 100%)',
		'Secondary_to_Primary_to_Right_gradient'  => 'linear-gradient(90deg, var(--postx_preset_Secondary_color) 0%, var(--postx_preset_Primary_color) 100%)',
		'Secondary_to_Primary_to_Bottom_gradient' => 'linear-gradient(180deg, var(--postx_preset_Secondary_color) 0%, var(--postx_preset_Primary_color) 100%)',
		'Cold_Evening_gradient'                   => 'linear-gradient(0deg, rgb(12, 52, 131) 0%, rgb(162, 182, 223) 100%, rgb(107, 140, 206) 100%, rgb(162, 182, 223) 100%)',
		'Purple_Division_gradient'                => 'linear-gradient(0deg, rgb(112, 40, 228) 0%, rgb(229, 178, 202) 100%)',
		'Over_Sun_gradient'                       => 'linear-gradient(60deg, rgb(171, 236, 214) 0%, rgb(251, 237, 150) 100%)',
		'Morning_Salad_gradient'                  => 'linear-gradient(-255deg, rgb(183, 248, 219) 0%, rgb(80, 167, 194) 100%)',
		'Fabled_Sunset_gradient'                  => 'linear-gradient(-270deg, rgb(35, 21, 87) 0%, rgb(68, 16, 122) 29%, rgb(255, 19, 97) 67%, rgb(255, 248, 0) 100%)',
	);

	/** Used only when no typography preset is stored. */
	const DEFAULT_TYPOS = array(
		'Heading_typo'         => array(
			'family'    => 'Helvetica',
			'type'      => 'sans-serif',
			'weight'    => 600,
			'transform' => 'capitalize',
		),
		'Body_and_Others_typo' => array(
			'family'    => 'Helvetica',
			'type'      => 'sans-serif',
			'weight'    => 400,
			'transform' => 'lowercase',
		),
		'body_typo'            => array( 'size' => array( 'lg' => 16 ) ),
		'paragraph_1_typo'     => array( 'size' => array( 'lg' => 12 ) ),
		'paragraph_2_typo'     => array( 'size' => array( 'lg' => 12 ) ),
		'paragraph_3_typo'     => array( 'size' => array( 'lg' => 12 ) ),
		'heading_h1_typo'      => array( 'size' => array( 'lg' => 42 ) ),
		'heading_h2_typo'      => array( 'size' => array( 'lg' => 36 ) ),
		'heading_h3_typo'      => array( 'size' => array( 'lg' => 30 ) ),
		'heading_h4_typo'      => array( 'size' => array( 'lg' => 24 ) ),
		'heading_h5_typo'      => array( 'size' => array( 'lg' => 20 ) ),
		'heading_h6_typo'      => array( 'size' => array( 'lg' => 16 ) ),
	);

	/**
	 * Remove stored whole-stylesheet fields from data about to be saved or returned.
	 *
	 * @param mixed $data Option value.
	 * @return mixed
	 */
	public static function without_legacy_css( $data ) {
		if ( is_array( $data ) ) {
			foreach ( self::LEGACY_CSS_FIELDS as $field ) {
				unset( $data[ $field ] );
			}
		}
		return $data;
	}

	/**
	 * Global stylesheet from the saved site-wide controls.
	 *
	 * @param mixed $global Saved postx_global option.
	 * @return string
	 */
	public static function global_css( $global ) {
		$global = is_array( $global ) ? $global : array();
		$css    = ':root { ';
		foreach ( range( 1, 9 ) as $index ) {
			$css .= '--preset-color' . $index . ': ' . self::value( $global[ 'presetColor' . $index ] ?? '', 'component', self::DEFAULT_PRESET_COLORS[ $index - 1 ] ) . '; ';
		}
		$css .= '}';
		if ( self::truthy( $global['enablePresetColorCSS'] ?? null ) ) {
			$css .= self::color_rules( $global );
		}
		if ( self::truthy( $global['enablePresetTypoCSS'] ?? null ) ) {
			$css .= self::typo_rules( self::breakpoint( $global, 'breakpointSm', 991 ), self::breakpoint( $global, 'breakpointXs', 767 ) );
		}
		return $css;
	}

	/**
	 * Root declarations for a colour or gradient preset map.
	 *
	 * @param mixed  $values Stored preset option.
	 * @param string $kind   'color' or 'gradient'.
	 * @return string
	 */
	public static function preset_root_css( $values, $kind ) {
		$values = self::map( $values, 'gradient' === $kind ? 'gradient' : 'component' );
		if ( ! $values ) {
			$values = 'gradient' === $kind ? self::DEFAULT_GRADIENTS : self::DEFAULT_COLORS;
		}
		$css = ':root { ';
		foreach ( $values as $key => $value ) {
			$css .= '--postx_preset_' . $key . ': ' . $value . '; ';
		}
		return $css . '}';
	}

	/**
	 * Typography variables, with the Google import only when the site has opted in.
	 *
	 * @param mixed $typos Stored ultpPresetTypos option.
	 * @return string
	 */
	public static function preset_typo_css( $typos ) {
		$typos = is_array( $typos ) ? $typos : array();
		$found = false;
		foreach ( self::TYPO_KEYS as $key ) {
			if ( isset( $typos[ $key ] ) && is_array( $typos[ $key ] ) ) {
				$found = true;
				break;
			}
		}
		if ( ! $found ) {
			$typos = self::DEFAULT_TYPOS;
		}
		$google  = 'yes' === ultimate_post()->get_setting( 'enable_google_fonts' );
		$imports = array();
		$vars    = '';
		foreach ( self::TYPO_KEYS as $key ) {
			$typo = $typos[ $key ] ?? null;
			if ( ! is_array( $typo ) ) {
				continue;
			}
			$family = self::value( $typo['family'] ?? '', 'family' );
			if ( '' !== $family && $google && ! in_array( $family, self::SYSTEM_FONTS, true ) && 0 !== strpos( $family, 'var(' ) ) {
				$variant = self::weight( $typo['weight'] ?? null ) . ( 'italic' === self::value( $typo['style'] ?? '', 'component' ) ? 'i' : '' );
				$imports[ $family ][ $variant ] = true;
			}
			if ( in_array( $key, self::PARENT_TYPO_KEYS, true ) ) {
				if ( '' !== $family ) {
					$vars .= self::var( $key, 'font_family', self::family_css( $family ) );
					$vars .= self::var( $key, 'font_family_type', self::value( $typo['type'] ?? '', 'component', 'sans-serif' ) );
				}
				$vars .= self::var( $key, 'font_weight', self::value( $typo['weight'] ?? '', 'component' ) );
				$vars .= self::var( $key, 'font_style', self::value( $typo['style'] ?? '', 'component' ) );
				$vars .= self::var( $key, 'text_decoration', self::value( $typo['decoration'] ?? '', 'component' ) );
				$vars .= self::var( $key, 'text_transform', self::value( $typo['transform'] ?? '', 'component' ) );
				foreach ( array( 'lg', 'sm', 'xs' ) as $device ) {
					$vars .= self::var( $key, 'letter_spacing_' . $device, self::length( self::parts( $typo, 'spacing' ), $device ) );
				}
			}
			foreach ( array( 'lg', 'sm', 'xs' ) as $device ) {
				$vars .= self::var( $key, 'font_size_' . $device, self::length( self::parts( $typo, 'size' ), $device ) );
				$vars .= self::var( $key, 'line_height_' . $device, self::length( self::parts( $typo, 'height' ), $device ) );
			}
		}
		$css = '';
		foreach ( $imports as $family => $weights ) {
			ksort( $weights );
			$css .= "@import url('https://fonts.googleapis.com/css?family=" . str_replace( '%20', '+', rawurlencode( (string) $family ) ) . ':' . implode( ',', array_keys( $weights ) ) . "');";
		}
		return $css . ':root { ' . $vars . '}';
	}

	/** Colour and body-background rules, emitted only when the site enables them. */
	private static function color_rules( $global ) {
		$css  = self::rule( array( 'html body.postx-admin-page .editor-styles-wrapper', 'html body.postx-admin-page .editor-styles-wrapper p', 'html body.postx-page', 'html body.postx-page p', 'html.colibri-wp-theme body.postx-page', 'html.colibri-wp-theme body.postx-page p', 'body.block-editor-iframe__body', 'body.block-editor-iframe__body p' ), 'color:var(--postx_preset_Contrast_2_color);' );
		$css .= self::rule( self::heading_selectors(), 'color:var(--postx_preset_Contrast_1_color);' );
		$background = self::truthy( $global['gbbodyBackground']['openColor'] ?? null ) ? self::background( $global['gbbodyBackground'] ?? null ) : '';
		$css .= self::rule( array( 'html body.postx-admin-page .editor-styles-wrapper', 'html body.postx-page', 'html body.postx-admin-page.block-editor-page.post-content-style-boxed .editor-styles-wrapper::before', 'html.colibri-wp-theme body.postx-page', 'body.block-editor-iframe__body' ), $background );
		return $css;
	}

	/** Typography rules that read the preset variables. */
	private static function typo_rules( $sm, $xs ) {
		$css = self::rule( self::heading_selectors(), 'font-family:var(--postx_preset_Heading_typo_font_family),var(--postx_preset_Heading_typo_font_family_type);font-weight:var(--postx_preset_Heading_typo_font_weight);font-style:var(--postx_preset_Heading_typo_font_style);text-transform:var(--postx_preset_Heading_typo_text_transform);text-decoration:var(--postx_preset_Heading_typo_text_decoration);letter-spacing:var(--postx_preset_Heading_typo_letter_spacing_lg,normal);' );
		foreach ( range( 1, 6 ) as $level ) {
			$css .= self::rule( self::level_selectors( $level ), self::heading_size( $level, 'lg' ) );
		}
		$css .= '@media (max-width:' . $sm . 'px){' . self::heading_device( 'sm' ) . '}';
		$css .= '@media (max-width:' . $xs . 'px){' . self::heading_device( 'xs' ) . '}';
		$body = array( 'html body.postx-admin-page .editor-styles-wrapper', 'html body.postx-page', 'html body.postx-admin-page .editor-styles-wrapper p', 'html body.postx-page p', 'html.colibri-wp-theme body.postx-page', 'html.colibri-wp-theme body.postx-page p', 'body.block-editor-iframe__body', 'body.block-editor-iframe__body p' );
		$css .= self::rule( $body, 'font-family:var(--postx_preset_Body_and_Others_typo_font_family),var(--postx_preset_Body_and_Others_typo_font_family_type);font-weight:var(--postx_preset_Body_and_Others_typo_font_weight);font-style:var(--postx_preset_Body_and_Others_typo_font_style);text-transform:var(--postx_preset_Body_and_Others_typo_text_transform);text-decoration:var(--postx_preset_Body_and_Others_typo_text_decoration);letter-spacing:var(--postx_preset_Body_and_Others_typo_letter_spacing_lg,normal);font-size:var(--postx_preset_body_typo_font_size_lg,initial);line-height:var(--postx_preset_body_typo_line_height_lg,normal) !important;' );
		$body_small = array( '.postx-admin-page .editor-styles-wrapper', '.postx-page', '.postx-admin-page .editor-styles-wrapper p', '.postx-page p', 'html.colibri-wp-theme body.postx-page', 'html.colibri-wp-theme body.postx-page p', 'body.block-editor-iframe__body', 'body.block-editor-iframe__body p' );
		foreach ( array( 'sm' => $sm, 'xs' => $xs ) as $device => $width ) {
			$css .= '@media (max-width:' . $width . 'px){' . self::rule( $body_small, 'letter-spacing:var(--postx_preset_Body_and_Others_typo_letter_spacing_' . $device . ',normal);font-size:var(--postx_preset_body_typo_font_size_' . $device . ',initial);line-height:var(--postx_preset_body_typo_line_height_' . $device . ',normal) !important;' ) . '}';
		}
		return $css;
	}

	/** Heading size and line-height for one level and device. */
	private static function heading_size( $level, $device ) {
		return 'font-size:var(--postx_preset_heading_h' . $level . '_typo_font_size_' . $device . ',initial);line-height:var(--postx_preset_heading_h' . $level . '_typo_line_height_' . $device . ',normal) !important;';
	}

	/** Heading letter-spacing and per-level sizes for a smaller device. */
	private static function heading_device( $device ) {
		$css = self::rule( self::heading_selectors(), 'letter-spacing:var(--postx_preset_Heading_typo_letter_spacing_' . $device . ',normal);' );
		foreach ( range( 1, 6 ) as $level ) {
			$css .= self::rule( self::level_selectors( $level ), self::heading_size( $level, $device ) );
		}
		return $css;
	}

	/** Selectors for one heading level in each editor and frontend context. */
	private static function level_selectors( $level ) {
		return array(
			'html body.postx-admin-page .editor-styles-wrapper h' . $level,
			'html body.postx-page h' . $level,
			'html.colibri-wp-theme body.postx-page h' . $level,
			'body.block-editor-iframe__body h' . $level,
		);
	}

	private static function heading_selectors() {
		$selectors = array();
		foreach ( range( 1, 6 ) as $level ) {
			$selectors = array_merge( $selectors, self::level_selectors( $level ) );
		}
		return $selectors;
	}

	private static function rule( $selectors, $declarations ) {
		return '' === $declarations ? '' : implode( ',', $selectors ) . '{' . $declarations . '}';
	}

	private static function var( $key, $suffix, $value ) {
		return '' === $value ? '' : '--postx_preset_' . $key . '_' . $suffix . ': ' . $value . '; ';
	}

	/**
	 * A family for a custom property. Plain names and var() references are written as they are, so existing output does not change.
	 * Any other name (such as a custom font title) becomes a single-quoted CSS string, escaped the same way
	 * BlockStyles quotes a font family, so no multibyte extension is needed.
	 */
	private static function family_css( $family ) {
		if ( preg_match( '/^[\p{L}\p{N} _-]{1,100}$/uD', $family ) || 0 === strpos( $family, 'var(' ) ) {
			return $family;
		}
		return "'" . str_replace( array( '\\', "'", '<', '>', "\n", "\r", "\f" ), array( '\\\\', "\\'", '\\3c ', '\\3e ', '\\a ', '\\d ', '\\c ' ), $family ) . "'";
	}

	/** Declarations for a colour or gradient background, or the empty string. */
	private static function background( $value ) {
		if ( ! is_array( $value ) ) {
			return '';
		}
		$css  = self::truthy( $value['clip'] ?? null ) ? '-webkit-background-clip:text;-webkit-text-fill-color:transparent;' : '';
		$type = $value['type'] ?? '';
		if ( 'color' === $type ) {
			$color = self::value( $value['color'] ?? '', 'component' );
			$css  .= '' === $color ? '' : 'background-color:' . $color . ';';
		} elseif ( 'gradient' === $type && self::truthy( $value['gradient'] ?? null ) ) {
			if ( is_array( $value['gradient'] ) ) {
				$css .= self::gradient_object( $value['gradient'] );
			} else {
				$gradient = self::value( $value['gradient'], 'gradient' );
				$css     .= '' === $gradient ? '' : 'background-image:' . $gradient . ';';
			}
		} elseif ( 'image' === $type ) {
			$fallback = self::value( $value['fallbackColor'] ?? ( $value['color'] ?? '' ), 'component' );
			$image    = self::value( $value['image'] ?? '', 'url' );
			$css     .= '' === $fallback ? '' : 'background-color:' . $fallback . ';';
			if ( '' !== $image ) {
				$css .= 'background-image:url("' . $image . '");';
				if ( isset( $value['position']['x'], $value['position']['y'] ) ) {
					$x = self::percent( $value['position']['x'] );
					$y = self::percent( $value['position']['y'] );
					$css .= '' === $x || '' === $y ? '' : 'background-position-x:' . $x . '%;background-position-y:' . $y . '%;';
				}
				foreach ( array( 'attachment' => 'background-attachment', 'repeat' => 'background-repeat', 'size' => 'background-size' ) as $field => $property ) {
					$part = self::value( $value[ $field ] ?? '', 'component' );
					$css .= '' === $part ? '' : $property . ':' . $part . ';';
				}
			}
		}
		return $css;
	}

	private static function gradient_object( $gradient ) {
		$color1 = self::value( $gradient['color1'] ?? '', 'component' );
		$color2 = self::value( $gradient['color2'] ?? '', 'component' );
		$start  = self::value( $gradient['start'] ?? 0, 'number' );
		$stop   = self::value( $gradient['stop'] ?? 100, 'number' );
		if ( '' === $color1 || '' === $color2 || '' === $start || '' === $stop ) {
			return '';
		}
		if ( 'linear' === ( $gradient['type'] ?? '' ) ) {
			$direction = self::value( $gradient['direction'] ?? 0, 'number' );
			if ( '' === $direction ) {
				return '';
			}
			return 'background-image:linear-gradient(' . $direction . 'deg,' . $color1 . ' ' . $start . '%,' . $color2 . ' ' . $stop . '%);';
		}
		$radial = self::value( $gradient['radial'] ?? '', 'component' );
		if ( '' === $radial ) {
			return '';
		}
		return 'background-image:radial-gradient(circle at ' . $radial . ',' . $color1 . ' ' . $start . '%,' . $color2 . ' ' . $stop . '%);';
	}

	/** A position fraction (0 to 1) as a percentage number, or the empty string. */
	private static function percent( $value ) {
		return is_numeric( $value ) ? self::value( 100 * (float) $value, 'number' ) : '';
	}

	/**
	 * A length from a size, height or spacing group. A unit suffix comes from the
	 * matching u-prefixed key, as the editor stores it.
	 */
	private static function length( $part, $device ) {
		$raw = $part[ $device ] ?? null;
		if ( null === $raw || false === $raw || '' === $raw ) {
			return '';
		}
		if ( is_int( $raw ) || is_float( $raw ) || ( is_string( $raw ) && is_numeric( $raw ) ) ) {
			$unit = $part[ 'u' . $device ] ?? '';
			if ( ! is_string( $unit ) ) {
				return '';
			}
			$unit   = '' === $unit ? 'px' : $unit;
			$unit   = BlockStyleValues::serialize( $unit, array( 'kind' => 'unit' ) );
			$number = BlockStyleValues::serialize( $raw, array( 'kind' => 'number' ) );
			return is_wp_error( $unit ) || is_wp_error( $number ) ? '' : $number . $unit;
		}
		return self::value( $raw, 'component' );
	}

	private static function parts( $typo, $group ) {
		return is_array( $typo[ $group ] ?? null ) ? $typo[ $group ] : array();
	}

	/** A Google Fonts weight from the typography controls, 400 when none is usable. */
	private static function weight( $value ) {
		$digits = is_int( $value ) ? (string) $value : ( is_string( $value ) ? $value : '' );
		$weight = ctype_digit( $digits ) ? (int) $digits : 400;
		return $weight >= 100 && $weight <= 900 && 0 === $weight % 100 ? $weight : 400;
	}

	private static function breakpoint( $global, $key, $fallback ) {
		$value  = $global[ $key ] ?? null;
		$digits = is_int( $value ) ? (string) $value : ( is_string( $value ) ? $value : '' );
		return ctype_digit( $digits ) && (int) $digits >= 240 && (int) $digits <= 3840 ? (int) $digits : $fallback;
	}

	/**
	 * Validate one control against its value grammar.
	 *
	 * @param mixed  $value   Stored value.
	 * @param string $kind    Grammar name for BlockStyleValues::serialize().
	 * @param string $default Returned when the value is missing or invalid.
	 * @return string
	 */
	private static function value( $value, $kind, $default = '' ) {
		if ( ! is_string( $value ) && ! is_int( $value ) && ! is_float( $value ) ) {
			return $default;
		}
		$clean = BlockStyleValues::serialize( $value, array( 'kind' => $kind ) );
		return is_wp_error( $clean ) || '' === $clean ? $default : $clean;
	}

	/** Keep valid, named entries of a preset map and drop everything else. */
	private static function map( $values, $kind ) {
		$output = array();
		if ( ! is_array( $values ) ) {
			return $output;
		}
		foreach ( array_slice( $values, 0, 100, true ) as $key => $value ) {
			if ( ! is_string( $key ) || ! preg_match( '/^[A-Za-z0-9_-]{1,100}$/D', $key ) || in_array( $key, self::LEGACY_CSS_FIELDS, true ) ) {
				continue;
			}
			if ( 'gradient' === $kind && is_array( $value ) ) {
				// Older preset controls stored gradient components alongside their generated rootCSS.
				$declaration = self::gradient_object( $value );
				$value       = '' === $declaration ? '' : substr( $declaration, strlen( 'background-image:' ), -1 );
			}
			$clean = self::value( $value, $kind );
			if ( '' !== $clean ) {
				$output[ $key ] = $clean;
			}
		}
		return $output;
	}

	private static function truthy( $value ) {
		return null !== $value && false !== $value && '' !== $value && 0 !== $value && 0.0 !== $value;
	}
}
