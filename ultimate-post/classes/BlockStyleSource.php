<?php
/** Read structured saved sources and isolated previews. No rendering or cache-file reads. */
namespace ULTP;
defined( 'ABSPATH' ) || exit;
final class BlockStyleSource {
	/** Canonical target shared by the transport and internal writers. @return int|string|\WP_Error */
	public static function target( $value ) {
		if ( 'ultp-widget' === $value ) { return $value; }
		if ( is_int( $value ) && $value > 0 ) { return $value; }
		if ( ! is_string( $value ) || ! preg_match( '/^[1-9][0-9]*$/D', $value ) ) { return BlockStyleValues::error(); }
		$max = (string) PHP_INT_MAX;
		if ( strlen( $value ) > strlen( $max ) || ( strlen( $value ) === strlen( $max ) && strcmp( $value, $max ) > 0 ) ) { return BlockStyleValues::error(); }
		return (int) $value;
	}
	/** @return array|\WP_Error */
	public static function get_blocks( $source_id, $context = 'view' ) {
		$state = array( 'seen' => array(), 'sources' => 0, 'bytes' => 0, 'nodes' => 0 );
		return self::source( $source_id, $context, $state, 0, 0 );
	}
	public static function can_edit( $id ) {
		$post = get_post( $id );
		return $post && ! post_type_exists( $post->post_type ) ? current_user_can( 'edit_others_posts' ) : current_user_can( 'edit_post', $id );
	}
	private static function readable( $post ) {
		if ( ! $post || post_password_required( $post ) ) { return false; }
		$cap = function( $capability ) use ( $post ) { return post_type_exists( $post->post_type ) ? current_user_can( $capability, $post->ID ) : current_user_can( 'edit_others_posts' ); };
		if ( 'publish' !== $post->post_status ) { return $cap( 'read_post' ); }
		$types = (array) apply_filters( 'ultp_ajax_source_post_types', array( 'ultp_builder', 'ultp_templates', 'wp_template', 'wp_template_part', 'wp_block' ) );
		return is_post_type_viewable( $post->post_type ) || in_array( $post->post_type, $types, true ) || $cap( 'edit_post' );
	}
	private static function source( $id, $context, &$state, $refs, $depth ) {
		if ( $refs > 20 || $depth > 32 || $state['sources'] >= 50 ) { return array(); }
		if ( $id instanceof \WP_Block_Template ) {
			if ( ! in_array( $id->theme, array( get_stylesheet(), get_template() ), true ) ) { return array(); }
			if ( ! empty( $id->wp_id ) && ! self::readable( get_post( $id->wp_id ) ) ) { return array(); }
			$key = 'template:' . $id->id;
			$content = $id->content;
		} else {
			$id = self::target( $id );
			if ( is_wp_error( $id ) ) { return $id; }
			$key = (string) $id;
			if ( 'ultp-widget' === $id ) {
				if ( 'edit' === $context && ! current_user_can( 'edit_theme_options' ) ) { return new \WP_Error( 'rest_forbidden', '', array( 'status' => rest_authorization_required_code() ) ); }
				$content = '';
				foreach ( (array) get_option( 'widget_block', array() ) as $widget ) {
					if ( is_array( $widget ) && is_string( $widget['content'] ?? null ) ) { $content .= $widget['content']; if ( strlen( $content ) > BlockStyleInput::MAX_BYTES ) { return BlockStyleValues::error(); } }
				}
			} else {
				$post = get_post( $id );
				if ( $post && 'revision' === $post->post_type ) { $post = get_post( $post->post_parent ); $key = $post ? (string) $post->ID : $key; }
				if ( 'edit' === $context ) {
					if ( ! self::can_edit( $post ? $post->ID : $id ) ) { return new \WP_Error( 'rest_forbidden', '', array( 'status' => rest_authorization_required_code() ) ); }
				} elseif ( ! self::readable( $post ) ) { return array(); }
				if ( ! $post ) { return new \WP_Error( 'ultp_style_source_missing', __( 'PostX style source was not found.', 'ultimate-post' ), array( 'status' => 404 ) ); }
				$content = $post->post_content;
			}
		}
		if ( isset( $state['seen'][$key] ) ) { return array(); }
		$state['seen'][$key] = true; ++$state['sources'];
		$state['bytes'] += strlen( $content );
		if ( $state['bytes'] > BlockStyleInput::MAX_BYTES ) { return BlockStyleValues::error(); }
		$blocks = self::expand( parse_blocks( $content ), $state, $refs, $depth );
		return is_wp_error( $blocks ) ? $blocks : BlockStyleInput::normalize( $blocks, 'saved' );
	}
	private static function expand( $blocks, &$state, $refs, $depth ) {
		if ( $depth > 32 ) { return BlockStyleValues::error(); }
		$output = array();
		foreach ( $blocks as $block ) {
			// The blank freeform node between two saved blocks carries no styles and does not count toward the limit.
			if ( empty( $block['blockName'] ) && empty( $block['innerBlocks'] ) && '' === trim( (string) ( $block['innerHTML'] ?? '' ) ) ) { continue; }
			if ( ++$state['nodes'] > BlockStyleInput::MAX_NODES ) { return BlockStyleValues::error(); }
			$name = $block['blockName'] ?? ''; $attrs = $block['attrs'] ?? array();
			$children = self::expand( $block['innerBlocks'] ?? array(), $state, $refs, $depth + 1 );
			if ( is_wp_error( $children ) ) { return $children; }
			$reference = null;
			if ( 'core/block' === $name ) {
				$target = self::target( $attrs['ref'] ?? null );
				if ( ! is_wp_error( $target ) && is_int( $target ) && 'wp_block' === get_post_type( $target ) ) { $reference = $target; }
			} elseif ( 'core/template-part' === $name && is_string( $attrs['slug'] ?? null ) && is_string( $attrs['theme'] ?? get_stylesheet() ) ) {
				$theme = $attrs['theme'] ?? get_stylesheet();
				if ( in_array( $theme, array( get_stylesheet(), get_template() ), true ) && preg_match( '/^[a-zA-Z0-9_-]{1,100}$/D', $attrs['slug'] ) ) { $reference = get_block_template( $theme . '//' . $attrs['slug'], 'wp_template_part' ); }
			}
			if ( $reference ) {
				$extra = self::source( $reference, 'view', $state, $refs + 1, $depth + 1 );
				if ( is_wp_error( $extra ) ) { if ( 'ultp_invalid_block_styles' === $extra->get_error_code() ) { return $extra; } } else { $children = array_merge( $children, $extra ); }
			}
			$output[] = array( 'blockName' => $name, 'attrs' => $attrs, 'innerBlocks' => $children );
		}
		return $output;
	}
	public static function get_post_css( $source_id ) {
		$blocks = self::get_blocks( $source_id );
		$css = is_wp_error( $blocks ) ? '' : BlockStyles::generate( $blocks );
		if ( is_wp_error( $css ) ) { self::log_failure( $source_id, $css ); return ''; }
		if ( is_wp_error( $blocks ) ) { self::log_failure( $source_id, $blocks ); }
		return $css;
	}
	/** An over-limit or invalid source draws no styles; say why while debugging instead of failing silently. */
	private static function log_failure( $source_id, $error ) {
		if ( 'ultp_invalid_block_styles' !== $error->get_error_code() || ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) { return; }
		$id = is_scalar( $source_id ) ? (string) $source_id : 'template';
		error_log( 'PostX: block styles for source ' . $id . ' were not generated (limits: ' . BlockStyleInput::MAX_NODES . ' blocks, ' . BlockStyleInput::MAX_BYTES . ' bytes, depth 32): ' . $error->get_error_message() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	}
	public static function preview_authorized( $id ) {
		return is_int( $id ) && is_user_logged_in() && self::can_edit( $id ) && is_preview() && isset( $_GET['preview_id'], $_GET['preview_nonce'] ) && self::target( wp_unslash( $_GET['preview_id'] ) ) === $id && is_string( $_GET['preview_nonce'] ) && wp_verify_nonce( wp_unslash( $_GET['preview_nonce'] ), 'post_preview_' . $id );
	}
	/** Current user's autosave wins; legacy CSS transients are never consulted. */
	public static function get_preview_css( $post_id ) {
		$id = self::target( $post_id );
		if ( ! self::preview_authorized( $id ) ) { return ''; }
		$autosave = wp_get_post_autosave( $id, get_current_user_id() );
		if ( $autosave ) {
			$state = array( 'seen' => array( (string) $id => true ), 'sources' => 1, 'bytes' => strlen( $autosave->post_content ), 'nodes' => 0 );
			if ( $state['bytes'] > BlockStyleInput::MAX_BYTES ) { return BlockStyleValues::error(); }
			$blocks = self::expand( parse_blocks( $autosave->post_content ), $state, 0, 0 );
		} else {
			$blocks = get_transient( '_ultp_preview_blocks_' . get_current_user_id() . '_' . $id );
			if ( false === $blocks ) { return ''; }
			$blocks = BlockStyleInput::normalize( $blocks );
			if ( is_wp_error( $blocks ) ) { return $blocks; }
			$state = array( 'seen' => array( (string) $id => true ), 'sources' => 1, 'bytes' => strlen( wp_json_encode( $blocks ) ), 'nodes' => 0 );
			$blocks = self::expand( $blocks, $state, 0, 0 );
		}
		return is_wp_error( $blocks ) ? $blocks : BlockStyles::generate( $blocks );
	}
}
