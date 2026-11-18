<?php
/** Value grammars. A control can never supply a rule, selector or declaration name. */
namespace ULTP;
defined( 'ABSPATH' ) || exit;
final class BlockStyleValues {
	public static function error() { return new \WP_Error( 'ultp_invalid_block_styles', __( 'Invalid PostX style controls. Refresh the editor and try again.', 'ultimate-post' ), array( 'status' => 400 ) ); }
	public static function identifier( $value ) {
		if ( is_int( $value ) && $value >= 0 ) { $value = (string) $value; }
		elseif ( is_float( $value ) && is_finite( $value ) && $value >= 0 && $value <= 9007199254740991 && floor( $value ) === $value ) { $value = sprintf( '%.0f', $value ); }
		return is_string( $value ) && preg_match( '/^[A-Za-z0-9_-]{1,100}$/D', $value ) ? $value : self::error();
	}
	/** @return string|\WP_Error */
	public static function serialize( $value, array $definition ) {
		$kind = $definition['kind'] ?? 'component';
		if ( 'identifier' === $kind ) { return self::identifier( $value ); }
		if ( 'url' === $kind ) {
			if ( ! is_string( $value ) || strlen( $value ) > 2048 || preg_match( '~[\x00-\x1f\x7f"\'<>\\\\;{}]|/\*|\*/~', $value ) ) { return self::error(); }
			if ( '' === $value ) { return ''; }
			$parts = wp_parse_url( $value );
			if ( ! is_array( $parts ) || ( isset( $parts['scheme'] ) && ! in_array( strtolower( $parts['scheme'] ), array( 'http', 'https' ), true ) ) || ( ! isset( $parts['scheme'] ) && ( 0 !== strpos( $value, '/' ) || 0 === strpos( $value, '//' ) ) ) ) { return self::error(); }
			return $value;
		}
		if ( 'unit' === $kind ) { return in_array( $value, array( '', 'px', '%', 'em', 'rem', 'vh', 'vw', 'vmin', 'vmax', 'ch', 'ex', 'pt', 'deg' ), true ) ? $value : self::error(); }
		if ( 'number' === $kind ) {
			return ( is_int( $value ) || is_float( $value ) || ( is_string( $value ) && preg_match( '/^-?(?:\d+\.?\d*|\.\d+)$/D', $value ) ) ) && is_finite( (float) $value ) && abs( (float) $value ) <= 100000 ? (string) $value : self::error();
		}
		if ( 'family' === $kind ) {
			if ( ! is_string( $value ) || strlen( $value ) > 300 ) { return self::error(); }
			if ( '' === $value || preg_match( '/^[\p{L}\p{N} ._-]{1,100}$/uD', $value ) || preg_match( '/^var\(--[A-Za-z0-9_-]{1,100}\)$/D', $value ) ) { return $value; }
			if ( 'true' === ultimate_post()->get_setting( 'ultp_custom_font' ) && get_posts( array( 'post_type' => 'ultp_custom_font', 'post_status' => 'publish', 'title' => $value, 'numberposts' => 1 ) ) ) { return $value; }
			return self::error();
		}
		if ( ! is_string( $value ) && ! is_int( $value ) && ! is_float( $value ) ) { return self::error(); }
		if ( ( is_int( $value ) || is_float( $value ) || is_numeric( $value ) ) && ( ! is_finite( (float) $value ) || abs( (float) $value ) > 100000 ) ) { return self::error(); }
		$value = (string) $value;
		// Preset labels are free text, so a variable name may hold any letter or digit; nothing else outside a var() may.
		$ascii = preg_replace( '/var\(\s*--[\p{L}\p{N}_-]{1,200}/u', 'var(--x', $value );
		if ( strlen( $value ) > 2048 || ! is_string( $ascii ) || ! preg_match( '/^[a-zA-Z0-9\s#.,%()_+*!\/-]*$/D', $ascii ) || preg_match( '~(?:/\*|\*/|!\s*important)~i', $value ) ) { return self::error(); }
		preg_match_all( '/([a-zA-Z_-][a-zA-Z0-9_-]*)\s*\(/', $value, $functions );
		$allowed = 'gradient' === $kind ? array( 'linear-gradient', 'radial-gradient', 'repeating-linear-gradient', 'repeating-radial-gradient', 'rgb', 'rgba', 'hsl', 'hsla', 'var' ) : array( 'rgb', 'rgba', 'hsl', 'hsla', 'var', 'calc', 'min', 'max', 'clamp' );
		foreach ( $functions[1] as $function ) { if ( ! in_array( strtolower( $function ), $allowed, true ) ) { return self::error(); } }
		$depth = 0;
		for ( $i = 0; $i < strlen( $value ); ++$i ) {
			if ( '(' === $value[$i] ) { if ( ++$depth > 8 ) { return self::error(); } }
			if ( ')' === $value[$i] && --$depth < 0 ) { return self::error(); }
		}
		if ( $depth || preg_match( '/var\(\s*(?!--[\p{L}\p{N}_-]+)/u', $value ) ) { return self::error(); }
		return $value;
	}
	/** A stored on/off flag: true, false, 1, 0 and their string forms. Anything else is not a flag. */
	public static function flag( $value ) {
		if ( is_bool( $value ) ) { return $value; }
		if ( 1 === $value || '1' === $value || 'true' === $value ) { return true; }
		if ( 0 === $value || '0' === $value || 'false' === $value ) { return false; }
		return null;
	}
	/** Validate a complete style value using local control shape, including nested components. */
	public static function control( $value, array $definition, $key = '', $depth = 0 ) {
		if ( $depth > 16 ) { return self::error(); }
		$type = $definition['type'] ?? '';
		// A string control whose default is a responsive object (lg / sm / xs, or a value with a unit) may hold one; its children are checked below.
		if ( is_array( $value ) && ( in_array( $type, array( 'number', 'boolean', 'integer' ), true ) || ( 'string' === $type && ! is_array( $definition['default'] ?? null ) ) ) ) { return self::error(); }
		if ( in_array( $type, array( 'array', 'object' ), true ) && ! is_array( $value ) && ! is_scalar( $definition['default'] ?? null ) ) { return self::error(); }
		if ( 'boolean' === $type && ! is_bool( $value ) ) {
			$flag = self::flag( $value );
			return null === $flag ? self::error() : $flag;
		}
		if ( in_array( $type, array( 'number', 'integer' ), true ) && is_wp_error( self::serialize( $value, array( 'kind' => 'number' ) ) ) ) { return self::error(); }

		if ( is_array( $value ) ) {
			if ( count( $value ) > 100 ) { return self::error(); }
			$result = array();
			foreach ( $value as $field => $part ) {
				if ( ! is_int( $field ) && ! preg_match( '/^[A-Za-z0-9_-]{1,100}$/D', $field ) ) { return self::error(); }
				if ( in_array( $field, array( 'selector', 'rules', 'wrap', 'css', 'block_css', 'cssTemplate' ), true ) ) { return self::error(); }
				$child_definition = $definition['fields'][$field] ?? array();
				if ( isset( $definition['valueEnum'] ) && in_array( $field, array( 'lg', 'sm', 'xs' ), true ) ) { $child_definition['valueEnum'] = $definition['valueEnum']; }
				$child = self::control( $part, $child_definition, (string) $field, $depth + 1 );
				if ( is_wp_error( $child ) ) { return $child; }
				$result[$field] = $child;
			}
			return $result;
		}
		if ( null === $value || is_bool( $value ) ) { return $value; }
		if ( isset( $definition['valueEnum'] ) ) { return in_array( $value, $definition['valueEnum'], true ) ? $value : self::error(); }
		$kind = preg_match( '/^(?:u(?:lg|sm|xs)|unit)$/', $key ) ? 'unit' : ( in_array( $key, array( 'image', 'fallback', 'url', 'video' ), true ) ? 'url' : ( 'family' === $key ? 'family' : ( 'gradient' === $key ? 'gradient' : ( 'id' === $key ? 'identifier' : 'component' ) ) ) );
		$result = self::serialize( $value, array( 'kind' => $kind ) );
		return is_wp_error( $result ) ? $result : ( is_int( $value ) || is_float( $value ) ? $value : $result );
	}
}
