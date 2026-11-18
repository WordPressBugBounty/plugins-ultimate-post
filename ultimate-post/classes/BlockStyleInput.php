<?php
/** Canonical, bounded trees shared by saved content and private previews. */
namespace ULTP;
defined( 'ABSPATH' ) || exit;
final class BlockStyleInput {
	/** Upper bounds that keep one request or one post's styles from growing without limit. */
	const MAX_BYTES = 4194304;
	const MAX_NODES = 2500;
	const MAX_ROWS  = 1000;
	public static function normalize( $blocks, $mode = 'request' ) {
		$encoded = wp_json_encode( $blocks );
		if ( false === $encoded || strlen( $encoded ) > self::MAX_BYTES ) { return BlockStyleValues::error(); }
		$schema = BlockStyleSchema::get();
		if ( is_wp_error( $schema ) ) { return $schema; }
		$count = 0;
		return self::walk( $blocks, $schema['blocks'], $mode, 0, $count );
	}
	private static function walk( $blocks, $schema, $mode, $depth, &$count ) {
		if ( ! is_array( $blocks ) || ( $blocks && array_keys( $blocks ) !== range( 0, count( $blocks ) - 1 ) ) || $depth > 32 ) { return BlockStyleValues::error(); }
		$output = array();
		foreach ( $blocks as $block ) {
			if ( ++$count > self::MAX_NODES || ! is_array( $block ) || ! is_array( $block['attrs'] ?? array() ) || ( ! is_string( $block['blockName'] ?? '' ) ) ) { return BlockStyleValues::error(); }
			$name = $block['blockName'] ?? ''; $attrs = $block['attrs'] ?? array();
			if ( isset( $schema[$name] ) ) {
				$clean = array();
				foreach ( $attrs as $key => $value ) {
					if ( in_array( $key, array( 'selector', 'rules', 'wrap', 'css', 'block_css', 'cssTemplate' ), true ) ) { if ( 'request' === $mode ) { return BlockStyleValues::error(); } continue; }
					if ( 'blockId' === $key ) { $value = '' === $value ? '' : BlockStyleValues::identifier( $value ); }
					elseif ( 'dcFields' === $key && isset( $schema[$name][$key] ) ) { $value = self::dynamic_fields( $value ); }
					elseif ( isset( $schema[$name][$key]['fields'] ) && 'array' === $schema[$name][$key]['type'] ) { $value = self::repeater( $value, $schema[$name][$key] ); }
					elseif ( isset( $schema[$name][$key] ) && ( self::has_styles( $schema[$name][$key] ) || in_array( $key, array( 'dcGroupStyles', 'dcFieldStyles' ), true ) ) ) {
						$value = BlockStyleValues::control( $value, $schema[$name][$key], $key );
						// A saved value that is not accepted draws nothing; it must not turn into the control's default.
						if ( is_wp_error( $value ) && 'request' !== $mode ) { $clean[$key] = ''; continue; }
					}
					elseif ( ! isset( $schema[$name][$key] ) || 'style' === $key ) { continue; }
					if ( is_wp_error( $value ) ) { if ( 'request' === $mode ) { return $value; } continue; }
					$clean[$key] = $value;
				}
				$attrs = $clean;
			}
			$children = self::walk( $block['innerBlocks'] ?? array(), $schema, $mode, $depth + 1, $count );
			if ( is_wp_error( $children ) ) { return $children; }
			$output[] = array( 'blockName' => $name, 'attrs' => $attrs, 'innerBlocks' => $children );
		}
		return $output;
	}
	private static function has_styles( $definition ) {
		foreach ( $definition['style'] ?? array() as $rule ) { if ( ! empty( $rule['selector'] ) ) { return true; } }
		foreach ( $definition['fields'] ?? array() as $field ) { if ( self::has_styles( $field ) ) { return true; } }
		return false;
	}
	private static function dynamic_fields( $groups ) {
		if ( ! is_array( $groups ) || count( $groups ) > 100 ) { return BlockStyleValues::error(); }
		$output = array();
		foreach ( $groups as $group ) {
			if ( null === $group ) { $output[] = null; continue; }
			if ( ! is_array( $group ) || ! is_array( $group['fields'] ?? null ) || count( $group['fields'] ) > 100 ) { return BlockStyleValues::error(); }
			$id = BlockStyleValues::identifier( $group['id'] ?? null );
			if ( is_wp_error( $id ) ) { return $id; }
			$fields = array();
			foreach ( $group['fields'] as $field ) {
				if ( ! is_array( $field ) ) { return BlockStyleValues::error(); }
				$field_id = BlockStyleValues::identifier( $field['id'] ?? null );
				if ( is_wp_error( $field_id ) ) { return $field_id; }
				$fields[] = array( 'id' => $field_id );
			}
			$output[] = array( 'id' => $id, 'fields' => $fields );
		}
		return $output;
	}
	private static function repeater( $rows, $definition ) {
		if ( ! is_array( $rows ) || count( $rows ) > self::MAX_ROWS ) { return BlockStyleValues::error(); }
		$output = array();
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) { return BlockStyleValues::error(); }
			$values = array();
			foreach ( $definition['fields'] as $field => $control ) {
				if ( ! array_key_exists( $field, $row ) || ! self::has_styles( $control ) ) { continue; }
				$value = BlockStyleValues::control( $row[$field], $control, $field );
				if ( is_wp_error( $value ) ) { return $value; }
				$values[$field] = $value;
			}
			$output[] = $values;
		}
		return $output;
	}
}
