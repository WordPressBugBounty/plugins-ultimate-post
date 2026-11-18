<?php
/**
 * Extension hooks for add-on plugins and the version checks around them.
 *
 * @package ULTP\Extended_Compat
 */

namespace ULTP;

defined( 'ABSPATH' ) || exit;

/**
 * Extension points that let an add-on plugin supply behaviour for a block, and the version checks
 * around them.
 *
 * Hooks for additional behaviour have empty defaults. Shared Free display options retain the saved
 * choices without an add-on. The add-on side, with the version rules for each pair of releases, is
 * documented in the add-on.
 *
 * `ULTP_FRONTEND_API` (ultimate-post.php) is the level of these hooks. It is raised only when a hook
 * is added. Levels are compared, never plugin version numbers.
 */
class Extended_Compat {

	/**
	 * Whether the add-on plugin is installed and loaded.
	 *
	 * Only says it is there, not that it is new enough: see `addon_outdated()`.
	 *
	 * @return bool
	 */
	public static function addon_installed() {
		return defined( 'ULTP_PRO_VER' );
	}

	/**
	 * Whether the add-on is installed but older than the hook level this plugin expects.
	 *
	 * True when `ULTP_PRO_FRONTEND_API` is missing or lower than `ULTP_PRO_MIN_FRONTEND_API`. Feeds
	 * the update notice (classes/Initialization.php). Nothing breaks when it is true: the hooks keep
	 * their empty defaults.
	 *
	 * @return bool
	 */
	public static function addon_outdated() {
		if ( ! self::addon_installed() || ! defined( 'ULTP_PRO_MIN_FRONTEND_API' ) ) {
			return false;
		}
		return ! defined( 'ULTP_PRO_BLOCK_STYLE_API' ) || ULTP_PRO_BLOCK_STYLE_API < 1 || ! defined( 'ULTP_PRO_FRONTEND_API' ) || ULTP_PRO_FRONTEND_API < ULTP_PRO_MIN_FRONTEND_API;
	}

	/**
	 * Extra wrapper class and data attributes of a post block, supplied by an add-on.
	 *
	 * The default is empty, so the block renders as an ordinary one unless an add-on answers.
	 *
	 * Filter `ultp_infinite_scroll`:
	 *  - $result  array  `class` (string, appended to the block wrapper) and `wrap_data` (string of
	 *                    `data-*` attributes for `.ultp-block-wrapper`, escaped by the callback).
	 *  - $attr    array  Block attributes.
	 *  - $context array  `paged`, `pages`, `block_name`, `curr_post_id`.
	 *
	 * @param array      $attr         Block attributes.
	 * @param int|null   $paged        Page being rendered (null when the request is not paged).
	 * @param int        $pages        Number of pages of the query.
	 * @param string     $block_name   Block name without the namespace, e.g. `post_grid_1`.
	 * @param int|string $curr_post_id Id of the post being viewed, or an empty string.
	 * @return array{class:string, wrap_data:string}
	 */
	public static function infinite_scroll( $attr, $paged, $pages, $block_name, $curr_post_id ) {
		$default = array(
			'class'     => '',
			'wrap_data' => '',
		);
		$result  = apply_filters(
			'ultp_infinite_scroll',
			$default,
			$attr,
			array(
				'paged'        => $paged,
				'pages'        => $pages,
				'block_name'   => $block_name,
				'curr_post_id' => $curr_post_id,
			)
		);

		// A callback that returns something else must not break the block.
		if ( ! is_array( $result ) ) {
			return $default;
		}
		$class = isset( $result['class'] ) && is_string( $result['class'] ) ? $result['class'] : '';
		$class = implode( ' ', array_filter( array_map( 'sanitize_html_class', explode( ' ', $class ) ) ) );
		return array(
			'class'     => $class,
			'wrap_data' => isset( $result['wrap_data'] ) && is_string( $result['wrap_data'] ) ? ultimate_post()->esc_data_attributes( $result['wrap_data'] ) : '',
		);
	}


	/**
	 * Whether an add-on switches the video popup on for a block. Off by default.
	 *
	 * Filter `ultp_video_popup_enabled`: `$enabled` bool, `$attr` array of block attributes.
	 *
	 * @param array $attr Block attributes.
	 * @return bool
	 */
	public static function video_popup_enabled( $attr ) {
		return (bool) apply_filters( 'ultp_video_popup_enabled', false, $attr );
	}

	/**
	 * Markup of the video popup of one post, supplied by an add-on. Empty by default.
	 *
	 * Filter `ultp_video_popup_html`: `$html` string, `$attr` block attributes, `$context` array with
	 * `video` (the post's video), `title` and `link` (already escaped by the block).
	 *
	 * @param array  $attr       Block attributes.
	 * @param mixed  $post_video The post's video.
	 * @param string $title      Post title, escaped.
	 * @param string $titlelink  Post link, escaped.
	 * @return string
	 */
	public static function video_popup( $attr, $post_video, $title, $titlelink ) {
		$html = apply_filters(
			'ultp_video_popup_html',
			'',
			$attr,
			array(
				'video' => $post_video,
				'title' => $title,
				'link'  => $titlelink,
			)
		);
		return ultimate_post()->esc_filtered_markup( $html, '' );
	}

	/**
	 * Meta display and category position of a post block, using its saved choices without an add-on.
	 *
	 * Covers `metaStyle`, `metaSeparator`, `metaList`, `metaListSmall` and `catPosition`. Only keys the block defines are
	 * touched, and saved values and filter answers must have the same type as the default.
	 *
	 * Filter `ultp_post_block_meta_and_category_option`: `$value` the saved choice or default, `$key` the attribute name, `$attr` the
	 * attributes the block was saved with, `$context` array with `block_name`.
	 *
	 * @param array  $attr       Block attributes.
	 * @param array  $defaults   Defaults of the block (`get_attributes()`).
	 * @param string $block_name Block name, e.g. `post-grid-5`.
	 * @return array
	 */
	public static function meta_and_category_options( $attr, $defaults, $block_name ) {
		$saved = $attr;
		foreach ( array( 'metaStyle', 'metaSeparator', 'metaList', 'metaListSmall', 'catPosition' ) as $key ) {
			if ( ! array_key_exists( $key, $defaults ) ) {
				continue;
			}
			$value = array_key_exists( $key, $saved ) && gettype( $saved[ $key ] ) === gettype( $defaults[ $key ] ) ? $saved[ $key ] : $defaults[ $key ];
			$value = apply_filters( 'ultp_post_block_meta_and_category_option', $value, $key, $saved, array( 'block_name' => $block_name ) );
			$attr[ $key ] = gettype( $value ) === gettype( $defaults[ $key ] ) ? $value : $defaults[ $key ];
		}
		return $attr;
	}
}
