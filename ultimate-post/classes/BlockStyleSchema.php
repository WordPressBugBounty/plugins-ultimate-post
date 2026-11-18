<?php
/** Packaged, server-owned style definitions shared with the editor. */
namespace ULTP;

defined( 'ABSPATH' ) || exit;

final class BlockStyleSchema {
	/** @return array|\WP_Error */
	public static function get() {
		$file   = ULTP_PATH . 'assets/blocks/style-schema.json';
		$schema = is_readable( $file ) ? json_decode( file_get_contents( $file ), true ) : null;
		$schema = apply_filters( 'ultp_block_style_schema', $schema );
		if ( ! is_array( $schema ) || 1 !== ( $schema['version'] ?? null ) || empty( $schema['blocks'] ) || ! is_array( $schema['blocks'] ) ) {
			return new \WP_Error( 'ultp_style_schema_unavailable', __( 'PostX style definitions are unavailable. Update PostX and PostX Pro, then refresh the editor.', 'ultimate-post' ), array( 'status' => 503 ) );
		}
		foreach ( $schema['blocks'] as $name => $definitions ) {
			if ( ! is_string( $name ) || ! preg_match( '~^ultimate-post/[a-z0-9_-]+$~D', $name ) || ! self::valid_definitions( $definitions ) ) {
				return new \WP_Error( 'ultp_style_schema_unavailable', __( 'PostX style definitions are invalid. Reinstall the matching plugin versions.', 'ultimate-post' ), array( 'status' => 503 ) );
			}
		}
		return $schema;
	}
	private static function valid_definitions( $definitions, $depth = 0 ) {
		if ( ! is_array( $definitions ) || $depth > 8 ) {
			return false; }
		foreach ( $definitions as $key => $definition ) {
			if ( ! is_string( $key ) || ! is_array( $definition ) || ! isset( $definition['type'] ) ) {
				return false; }
			if ( isset( $definition['fields'] ) && ! self::valid_definitions( $definition['fields'], $depth + 1 ) ) {
				return false; }
			if ( isset( $definition['style'] ) ) {
				if ( ! is_array( $definition['style'] ) ) {
					return false; }
				foreach ( $definition['style'] as $rule ) {
					if ( ! is_array( $rule ) || ( isset( $rule['selector'] ) && ! is_string( $rule['selector'] ) && ! is_array( $rule['selector'] ) ) ) {
						return false; }
					// The kind only selects a media query from `ultp_block_style_wrap_media` (BlockStyles::wrap_queries()); it is never printed.
					if ( isset( $rule['wrap'] ) && ! ( is_array( $rule['wrap'] ) && is_string( $rule['wrap']['kind'] ?? null ) && preg_match( '~^[a-z][a-z0-9-]{0,39}$~D', $rule['wrap']['kind'] ) ) ) {
						return false; }
				}
			}
		}
		return true;
	}
	public static function fingerprint() {
		$schema = self::get();
		return is_wp_error( $schema ) ? '' : hash( 'sha256', wp_json_encode( $schema ) );
	}
}
