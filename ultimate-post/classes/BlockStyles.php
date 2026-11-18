<?php
/**
 * Generate block styles from the same local control definitions as the editor.
 *
 * Selectors and declarations are plugin-owned. Requests can supply only block
 * names and structured control values, never CSS rules or selector templates.
 *
 * @package ULTP
 */
namespace ULTP;

defined( 'ABSPATH' ) || exit;

class BlockStyles {
	private static $schema;
	private $rules = array(
		'lg'    => array(),
		'sm'    => array(),
		'xs'    => array(),
		'plain' => array(),
	);
	private $fonts = array();
	private $imports = array();
	private $count = 0;
	private $custom_fonts;
	private $global_settings = array();
	private $wrap_queries = null;

	/** @return string|\WP_Error Deterministic CSS, always from normalized local controls. */
	public static function generate( array $blocks ) {
		$blocks = BlockStyleInput::normalize( $blocks, 'saved' );
		if ( is_wp_error( $blocks ) ) { return $blocks; }
		$schema = BlockStyleSchema::get();
		if ( is_wp_error( $schema ) ) { return $schema; }
		self::$schema = $schema['blocks'];
		$generator = new self();
		$global = get_option( 'postx_global', array() );
		$generator->global_settings = is_array( $global ) ? $global : array();
		$generator->walk( $blocks );
		$css = implode( '', array_unique( $generator->imports ) ) . implode( '', array_unique( $generator->fonts ) ) . implode( '', $generator->rules['lg'] );
		foreach ( array( 'sm' => 991, 'xs' => 767 ) as $device => $fallback ) {
			if ( $generator->rules[$device] ) { $css .= '@media (max-width:' . $generator->breakpoint( 'breakpoint' . ucfirst( $device ), $fallback ) . 'px){' . implode( '', $generator->rules[$device] ) . '}'; }
		}
		return CSS_Sanitizer::ultp_esc_css( $css . implode( '', $generator->rules['plain'] ) );
	}

	private function walk( $blocks, $depth = 0 ) {
		if ( ! is_array( $blocks ) || $depth > 32 ) {
			return;
		}
		foreach ( $blocks as $block ) {
			if ( ++$this->count > BlockStyleInput::MAX_NODES || ! is_array( $block ) ) {
				return;
			}
			$name = $block['blockName'] ?? $block['name'] ?? '';
			$values = $block['attrs'] ?? $block['attributes'] ?? array();
			if ( is_string( $name ) && isset( self::$schema[ $name ] ) && is_array( $values ) && isset( $values['blockId'] ) && is_string( $values['blockId'] ) && preg_match( '/^[a-zA-Z0-9_-]{1,100}$/D', $values['blockId'] ) ) {
				$this->block( self::$schema[ $name ], $values );
			}
			$this->walk( $block['innerBlocks'] ?? array(), $depth + 1 );
		}
	}

	private function block( $definitions, $values ) {
		$settings = array();
		foreach ( $definitions as $key => $definition ) {
			$settings[ $key ] = $values[ $key ] ?? $definition['default'] ?? null;
		}
		foreach ( $definitions as $key => $definition ) {
			$value = $settings[ $key ];
			if ( 'dcFields' === $key && ! empty( $settings['dcEnabled'] ) && is_array( $value ) ) {
				foreach ( $value as $group ) {
					if ( ! is_array( $group ) || is_wp_error( BlockStyleValues::identifier( $group['id'] ?? null ) ) ) { continue; }
					$this->dynamic_controls( 'dcGroupStyles', $group['id'], $definitions, $settings, $values['blockId'] );
					foreach ( $group['fields'] ?? array() as $field ) {
						if ( is_array( $field ) && ! is_wp_error( BlockStyleValues::identifier( $field['id'] ?? null ) ) ) { $this->dynamic_controls( 'dcFieldStyles', $field['id'], $definitions, $settings, $values['blockId'] ); }
					}
				}
				continue;
			}
			if ( isset( $definition['fields'] ) && is_array( $value ) ) {
				foreach ( $definition['fields'] as $field => $field_definition ) {
					foreach ( array_slice( $value, 0, BlockStyleInput::MAX_ROWS ) as $index => $row ) {
						if ( ! is_array( $row ) || ! is_int( $index ) || ! array_key_exists( $field, $row ) ) { continue; }
						$this->control( $field, $row[$field], $field_definition, $row, $values['blockId'], $index );
					}
				}
			} else {
				$this->control( $key, $value, $definition, $settings, $values['blockId'] );
			}
		}
	}

	private function dynamic_controls( $key, $dynamic_id, $definitions, $settings, $block_id ) {
		$definition = $definitions[$key] ?? array();
		$row = $settings[$key][$dynamic_id] ?? $settings[$key]['default'] ?? array();
		if ( ! is_array( $row ) ) { return; }
		foreach ( $row as $field => $value ) {
			if ( ! isset( $definition['fields'][$field] ) ) { continue; }
			$control = $definition['fields'][$field];
			$control['default'] = $definition['default']['default'][$field] ?? $control['default'] ?? array();
			$control['style'] = array_map( function( $rule ) use ( $dynamic_id ) { if ( isset( $rule['selector'] ) ) { $rule['selector'] = str_replace( '{{dcID}}', $dynamic_id, $rule['selector'] ); } return $rule; }, $control['style'] ?? array() );
			$this->control( $field, $value, $control, $settings[$key][$dynamic_id] ?? array(), $block_id );
		}
	}

	private function control( $key, $value, $definition, $settings, $id, $index = 0 ) {
		foreach ( $definition['style'] ?? array() as $style ) {
			if ( ! isset( $style['selector'] ) || ! $this->depends( $style, $settings ) ) {
				continue;
			}
			foreach ( (array) $style['selector'] as $template ) {
				$template = str_replace( array( '{{WOPB}}', '{{ULTP}}', '{{REPEAT_CLASS}}' ), array( '.wopb-block-' . $id, '.ultp-block-' . $id, '.ultp-repeat-' . $index ), $template );
				if ( ! is_array( $value ) ) {
					if ( $this->truthy( $value ) ) {
						$rule = $this->substitute( $template, $key, $value, $definition, $settings );
						$wrap = $style['wrap']['kind'] ?? '';
						$wrappers = '' !== $wrap ? $this->wrap_queries() : array();
						if ( '' !== $wrap && ! isset( $wrappers[ $wrap ] ) ) {
							// A wrapper this build cannot draw is left out, so the rule never applies on every screen.
							continue;
						}
						$this->rules['plain'][] = isset( $wrappers[$wrap] ) && $rule ? '@media ' . $wrappers[$wrap] . '{' . $rule . '}' : $rule;
					}
					continue;
				}
				$responsive = false;
				foreach ( array( 'lg', 'sm', 'xs' ) as $device ) {
					if ( isset( $value[ $device ] ) && $this->truthy( $value[ $device ] ) ) {
						$responsive = true;
						$part = is_array( $value[ $device ] ) ? $this->dimension( $value[ $device ] ) : ( isset( $definition['valueEnum'] ) && in_array( $value[$device], $definition['valueEnum'], true ) ? $value[$device] : $this->component( $value[$device] ) ) . $this->unit( $this->device_unit( $value, $device, '' ) );
						if ( '' === $part ) { continue; }
						$others = $settings;
						$another = $definition['anotherKey'] ?? '';
						if ( $another && isset( $others[ $another ][ $device ] ) ) {
							$others[ $another ] = $others[ $another ][ $device ];
						}
						$this->rules[ $device ][] = $this->substitute( $template, $key, $part, $definition, $others );
					}
				}
				if ( $responsive ) {
					continue;
				}
				$default = is_array( $definition['default'] ?? null ) ? $definition['default'] : array();
				// A plain { top, right, bottom, left, unit } value is a dimension, whatever flags the default carries.
				if ( array_intersect( array( 'top', 'right', 'bottom', 'left' ), array_keys( $value ) ) && empty( $value['openTypography'] ) && empty( $value['openBorder'] ) && ! ( ! empty( $value['openShadow'] ) && $this->truthy( $value['color'] ?? null ) ) ) {
					$dimension = $this->dimension( $value );
					if ( '' !== $dimension ) { $this->rules['plain'][] = $this->substitute( $template, $key, $dimension, $definition, $settings ); }
					continue;
				}
				foreach ( array( 'openTypography', 'openBorder', 'openShadow', 'openColor', 'openFilter' ) as $flag ) {
					if ( array_key_exists( $flag, $default ) && empty( $value[ $flag ] ) ) {
						continue 2;
					}
				}
				if ( array_key_exists( 'openTypography', $default ) ) {
					$this->typography( $template, $value );
				} elseif ( array_key_exists( 'openBorder', $default ) ) {
					// A border without a colour key takes the editor's default colour; an empty colour takes its fallback colour.
					$border_color = array_key_exists( 'color', $value ) ? ( $this->truthy( $value['color'] ) ? $value['color'] : '#555d66' ) : '#e5e5e5';
					$border_type  = $this->truthy( $value['type'] ?? null ) ? $value['type'] : 'solid';
					$declarations = 'border-color:' . $this->component( $border_color ) . ';border-style:' . $this->component( $border_type ) . ';border-width:' . $this->dimension( $value['width'] ?? array() ) . ';';
					$this->append( $template, $declarations );
				} elseif ( array_key_exists( 'openShadow', $default ) ) {
					$width = $value['width'] ?? array();
					$parts = array();
					foreach ( array( 'top', 'right', 'bottom', 'left' ) as $side ) {
						$parts[] = $this->number( $width[ $side ] ?? 0 ) . 'px';
					}
					// A shadow without a colour draws nothing.
					if ( $this->truthy( $value['color'] ?? null ) ) {
						$this->append( $template, 'box-shadow:' . ( 'inset' === ( $value['inset'] ?? '' ) ? 'inset ' : '' ) . implode( ' ', $parts ) . ' ' . $this->component( $value['color'] ) . ';' );
					}
				} elseif ( array_key_exists( 'openColor', $default ) ) {
					$declarations = $this->background( $value );
					if ( ! empty( $default['replace'] ) ) {
						// Only this locally declared control can replace a declaration list.
						$this->rules['plain'][] = str_replace( '{{' . $key . '}}', $declarations, $template );
					} else {
						$this->append( $template, $declarations );
					}
				} elseif ( array_key_exists( 'openFilter', $default ) ) {
					$filter = '';
					foreach ( array(
						'hue'        => array( 'hue-rotate', 'deg' ),
						'saturation' => array( 'saturate', '%' ),
						'brightness' => array( 'brightness', '%' ),
						'contrast'   => array( 'contrast', '%' ),
						'invert'     => array( 'invert', '%' ),
						'blur'       => array( 'blur', 'px' ),
					) as $field => $format ) {
						if ( ! empty( $value[ $field ] ) ) {
							$filter .= $format[0] . '(' . $this->number( $value[ $field ] ) . $format[1] . ') ';
						}
					}
					$this->rules['plain'][] = str_replace( '{{' . $key . '}}', 'filter:' . $filter . ';', $template );
				} elseif ( array_intersect( array( 'top', 'right', 'bottom', 'left' ), array_keys( $value ) ) ) {
					$dimension = $this->dimension( $value );
					if ( '' !== $dimension ) { $this->rules['plain'][] = $this->substitute( $template, $key, $dimension, $definition, $settings ); }
				} elseif ( ! empty( $value['onlyUnit'] ) ) {
					$this->rules['plain'][] = $this->substitute( $template, $key, ( $value['_value'] ?? '' ) . $this->unit( $value['unit'] ?? '' ), $definition, $settings );
				} elseif ( isset( $default['url'] ) && ! empty( $value['id'] ) ) {
					$this->append( $template, 'background-image:' . $this->url( $value['url'] ?? '' ) . ';' );
				}
			}
		}
	}

	private function substitute( $template, $key, $value, $definition, $settings ) {
		$value = is_bool( $value ) ? '' : ( isset( $definition['valueEnum'] ) && in_array( $value, $definition['valueEnum'], true ) ? $value : $this->component( $value ) );
		if ( false === $value ) {
			return '';
		}
		$template = str_replace( '{{' . $key . '}}', $value, $template );
		$another = $definition['anotherKey'] ?? '';
		if ( $another ) {
			$template = str_replace( '{{' . $another . '}}', $this->component( $settings[ $another ] ?? '' ), $template );
		}
		return false === strpos( $template, '{{' ) ? $template : '';
	}

	/** A component cannot introduce declarations, selectors, imports or URLs. */
	private function component( $value ) {
		$value = BlockStyleValues::serialize( $value, array() );
		return is_wp_error( $value ) ? false : $value;
	}

	private function number( $value ) {
		return is_scalar( $value ) && is_numeric( $value ) && is_finite( (float) $value ) && abs( (float) $value ) <= 100000 ? (string) ( 0 + $value ) : '0';
	}

	private function truthy( $value ) {
		return null !== $value && false !== $value && '' !== $value && 0 !== $value && 0.0 !== $value;
	}

	private function breakpoint( $key, $fallback ) {
		$value = $this->global_settings[ $key ] ?? $fallback;
		return is_scalar( $value ) && ctype_digit( (string) $value ) && $value >= 240 && $value <= 3840 ? (int) $value : $fallback;
	}

	/**
	 * Media queries of the wrapper kinds a style rule can name in `wrap.kind`, asked from the
	 * `ultp_block_style_wrap_media` filter. Asked once per generation, and only when a rule has a wrapper.
	 *
	 * Filter `ultp_block_style_wrap_media`
	 *  - $queries  array  Default `array()`. Return shape: kind (string) => media query condition (string,
	 *                     without the "@media " keyword and braces), e.g. `(min-width:992px)`. A rule whose
	 *                     kind has no query is not printed.
	 *  - $context  array  `breakpoints` => array( `xl` => int, `tablet` => int, `mobile` => int ): the px
	 *                     values of the global breakpoint settings, with their defaults (992, 991, 767).
	 *
	 * @return array<string,string>
	 */
	private function wrap_queries() {
		if ( null === $this->wrap_queries ) {
			$queries = apply_filters(
				'ultp_block_style_wrap_media',
				array(),
				array(
					'breakpoints' => array(
						'xl'     => $this->breakpoint( 'breakpointSm', 992 ),
						'tablet' => $this->breakpoint( 'breakpointSm', 991 ),
						'mobile' => $this->breakpoint( 'breakpointXs', 767 ),
					),
				)
			);
			$this->wrap_queries = array();
			if ( is_array( $queries ) ) {
				foreach ( $queries as $kind => $query ) {
					if ( is_string( $kind ) && is_string( $query ) && '' !== $query ) {
						$this->wrap_queries[ $kind ] = $query;
					}
				}
			}
		}
		return $this->wrap_queries;
	}

	private function unit( $unit ) {
		return in_array( $unit, array( '', 'px', '%', 'em', 'rem', 'vh', 'vw', 'vmin', 'vmax', 'ch', 'ex', 'pt' ), true ) ? $unit : '';
	}

	/** The unit stored for a device, then the shared unit, then the fallback; an empty unit counts as unset, as in the editor. */
	private function device_unit( $value, $device, $fallback ) {
		foreach ( array( 'u' . $device, 'unit' ) as $field ) {
			if ( ! empty( $value[ $field ] ) && is_string( $value[ $field ] ) ) {
				return $value[ $field ];
			}
		}
		return $fallback;
	}

	private function dimension( $value ) {
		if ( ! is_array( $value ) ) {
			return '';
		}
		if ( ! array_filter( array_intersect_key( $value, array_flip( array( 'top', 'right', 'bottom', 'left' ) ) ), function( $part ) { return '' !== $part && null !== $part && false !== $part && 0 !== $part && 0.0 !== $part; } ) ) { return ''; }
		$parts = array();
		$unit  = ! empty( $value['unit'] ) ? $value['unit'] : 'px';
		foreach ( array( 'top', 'right', 'bottom', 'left' ) as $side ) {
			$parts[] = $this->number( $value[ $side ] ?? 0 ) . $this->unit( $unit );
		}
		return implode( ' ', $parts );
	}

	private function append( $selector, $declarations, $device = 'plain' ) {
		if ( ! $declarations || false !== strpos( $selector, '{{' ) ) {
			return;
		}
		// A control selector may open with complete plugin-owned rules, e.g. `.a { x:y; } .b`; the
		// declarations belong to the trailing selector only, as in the editor. Braces must balance.
		$end = strrpos( $selector, '}' );
		$tail = false === $end ? $selector : substr( $selector, $end + 1 );
		$head = false === $end ? '' : substr( $selector, 0, $end + 1 );
		if ( false === strpos( $tail, '{' ) && substr_count( $head, '{' ) === substr_count( $head, '}' ) && '' !== trim( $tail ) ) {
			$this->rules[ $device ][] = $head . $tail . '{' . $declarations . '}';
		}
	}

	private function typography( $selector, $value ) {
		$css = '';
		foreach ( array(
			'weight'     => 'font-weight',
			'color'      => 'color',
			'style'      => 'font-style',
			'transform'  => 'text-transform',
			'decoration' => 'text-decoration',
		) as $key => $property ) {
			$part = $this->component( $value[ $key ] ?? '' );
			if ( $part ) {
				$css .= $property . ':' . $part . '' . ';';
			}
		}
		$family = $value['family'] ?? '';
		$custom = is_string( $family ) && '' !== $family && 'none' !== $family ? $this->custom_fonts( $family ) : array();
		if ( is_string( $family ) && '' !== $family && 'none' !== $family && strlen( $family ) <= 300 && ( isset( $custom[ $family ] ) || preg_match( '/^[\p{L}\p{N} ._-]{1,100}$/uD', $family ) || preg_match( '/^var\(--[A-Za-z0-9_-]{1,100}\)$/D', $family ) ) ) {
			$quoted_family = "'" . str_replace( array( '\\', "'", '<', '>', "\n", "\r", "\f" ), array( '\\\\', "\\'", '\\3c ', '\\3e ', '\\a ', '\\d ', '\\c ' ), $family ) . "'";
			$google = 'yes' === ultimate_post()->get_setting( 'enable_google_fonts' );
			$system = 0 === strpos( $family, 'var(' ) || in_array( $family, array( 'Arial', 'Tahoma', 'Verdana', 'Helvetica', 'Times New Roman', 'Trebuchet MS', 'Georgia' ), true );
			$font_type = $this->component( $value['type'] ?? 'sans-serif' );
			$font_type = is_string( $font_type ) && '' !== $font_type ? $font_type : 'sans-serif';
			$css       = 'font-family:' . ( isset( $custom[$family] ) ? $quoted_family : $family ) . ',' . $font_type . ';' . $css;
			if ( isset( $custom[ $family ] ) ) {
				foreach ( $custom[ $family ] as $face ) {
					if ( ! is_array( $face ) ) {
						continue;
					}
					$sources = array();
					foreach ( array(
						'woff'  => 'woff',
						'woff2' => 'woff2',
						'ttf'   => 'TrueType',
						'svg'   => 'svg',
						'eot'   => 'eot',
					) as $key => $format ) {
						$url = $this->url( $face[ $key ] ?? '' );
						if ( 'none' !== $url ) {
							$sources[] = $url . " format('" . $format . "')";
						}
					}
					$weight = $this->component( $face['weight'] ?? 'normal' );
					if ( $sources && preg_match( '/^(?:normal|bold|[1-9][0-9]{0,2}|1000)$/D', $weight ) ) {
						$this->fonts[] = '@font-face{font-family:' . $quoted_family . ';font-weight:' . $weight . ';font-display:auto;src:' . implode( ',', $sources ) . ';}';
					}
				}
			} elseif ( $google && ! $system ) {
				$this->imports[] = "@import url('https://fonts.googleapis.com/css?family=" . rawurlencode( $family ) . ':' . $this->number( $value['weight'] ?? 400 ) . "');";
			}
		}
		$this->append( $selector, $css );
		foreach ( array(
			'size'    => 'font-size',
			'height'  => 'line-height',
			'spacing' => 'letter-spacing',
		) as $field => $property ) {
			$data = $value[ $field ] ?? array();
			foreach ( array( 'lg', 'sm', 'xs' ) as $device ) {
				if ( is_array( $data ) && isset( $data[ $device ] ) && $this->truthy( $data[ $device ] ) ) {
					$part = $this->component( $data[ $device ] );
					if ( false !== $part ) {
						$this->append( $selector, $property . ':' . $part . ( 0 === strpos( $part, 'var(' ) ? '' : $this->unit( $this->device_unit( $data, $device, 'px' ) ) ) . ( 'height' === $field ? ' !important' : '' ) . ';', $device );
					}
				}
			}
		}
	}

	private function custom_fonts( $family ) {
		if ( ! is_array( $this->custom_fonts ) ) { $this->custom_fonts = array(); }
		if ( ! array_key_exists( $family, $this->custom_fonts ) ) {
			$this->custom_fonts[$family] = array();
			if ( 'true' === ultimate_post()->get_setting( 'ultp_custom_font' ) ) {
				$posts = get_posts( array( 'post_type' => 'ultp_custom_font', 'post_status' => 'publish', 'title' => $family, 'numberposts' => 1, 'order' => 'ASC' ) );
				foreach ( $posts as $post ) {
					$faces = get_post_meta( $post->ID, '_ultp_font_settings', true );
					if ( is_array( $faces ) ) { $this->custom_fonts[$family] = array_slice( $faces, 0, 100 ); }
				}
			}
		}
		return $this->custom_fonts[$family] ? array( $family => $this->custom_fonts[$family] ) : array();
	}

	private function background( $value ) {
		$type = $value['type'] ?? '';
		$css = ! empty( $value['clip'] ) ? '-webkit-background-clip:text;-webkit-text-fill-color:transparent;' : '';
		if ( 'color' === $type ) {
			$css .= 'background-color:' . $this->component( $value['color'] ?? '' ) . ';';
		} elseif ( 'gradient' === $type ) {
			$gradient = $value['gradient'] ?? '';
			if ( is_array( $gradient ) ) {
				$position = 'linear' === ( $gradient['type'] ?? '' ) ? 'linear-gradient(' . $this->number( $gradient['direction'] ?? 0 ) . 'deg,' : 'radial-gradient(circle at ' . $this->component( $gradient['radial'] ?? 'center' ) . ',';
				$gradient = $position . $this->component( $gradient['color1'] ?? '' ) . ' ' . $this->number( $gradient['start'] ?? 0 ) . '%,' . $this->component( $gradient['color2'] ?? '' ) . ' ' . $this->number( $gradient['stop'] ?? 100 ) . '%)';
			}
			// A gradient is a gradient function, or a single preset variable such as var(--postx_preset_Name_gradient).
			if ( is_string( $gradient ) && preg_match( '/^(?:(?:repeating-)?(?:linear|radial)-gradient\(|var\(\s*--[\p{L}\p{N}_-]+\s*\)$)/uD', $gradient ) ) {
				$css .= 'background-image:' . ( is_wp_error( BlockStyleValues::serialize( $gradient, array( 'kind' => 'gradient' ) ) ) ? '' : $gradient ) . ';';
			}
		} elseif ( 'image' === $type ) {
			$color = $value['fallbackColor'] ?? $value['color'] ?? '';
			if ( $color ) { $css .= 'background-color:' . $this->component( $color ) . ';'; }
			if ( ! empty( $value['image'] ) ) {
				$css .= 'background-image:' . $this->url( $value['image'] ) . ';';
				if ( isset( $value['position']['x'], $value['position']['y'] ) ) { $css .= 'background-position-x:' . $this->number( 100 * (float) $value['position']['x'] ) . '%;background-position-y:' . $this->number( 100 * (float) $value['position']['y'] ) . '%;'; }
				foreach ( array( 'attachment', 'repeat', 'size' ) as $field ) { if ( ! empty( $value[$field] ) ) { $css .= 'background-' . $field . ':' . $this->component( $value[$field] ) . ';'; } }
			}
		} elseif ( 'video' === $type && ! empty( $value['fallback'] ) ) { $css .= 'background-image:' . $this->url( $value['fallback'] ) . ';background-size:cover;background-position:50% 50%;'; }

		return $css;
	}

	/** URLs use a separate grammar, including site-relative media. */
	private function url( $value ) {
		$value = BlockStyleValues::serialize( $value, array( 'kind' => 'url' ) );
		return is_wp_error( $value ) || '' === $value ? 'none' : 'url("' . $value . '")';
	}

	private function equal( $a, $b ) {
		if ( gettype( $a ) === gettype( $b ) ) { return $a === $b; }
		if ( null === $a || null === $b || is_array( $a ) || is_array( $b ) ) { return false; }
		$number = function( $value ) {
			if ( is_bool( $value ) ) { return $value ? 1.0 : 0.0; }
			if ( is_string( $value ) && '' === trim( $value ) ) { return 0.0; }
			return is_numeric( $value ) ? (float) $value : null;
		};
		$a = $number( $a ); $b = $number( $b );
		return null !== $a && null !== $b && $a === $b;
	}
	private function depends( $style, $settings ) {
		foreach ( $style['depends'] ?? array() as $dependency ) {
			$actual = $settings[ $dependency['key'] ] ?? null;
			// The current editor treats a != array as a non-restricting condition.
			if ( '!=' === $dependency['condition'] && is_array( $dependency['value'] ?? null ) ) { continue; }
			$expected = $dependency['value'] ?? null;
			$match = is_array( $expected ) ? count( array_filter( $expected, function( $item ) use ( $actual ) { return $this->equal( $actual, $item ); } ) ) > 0 : $this->equal( $actual, $expected );
			if ( '!=' === $dependency['condition'] ? $match : ! $match ) {
				return false;
			}
		}
		return true;
	}
}
