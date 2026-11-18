<?php
/**
 * Stored keys renamed from a "__" start to the plugin prefix.
 *
 * @package ULTP
 */

namespace ULTP;

defined( 'ABSPATH' ) || exit;

/**
 * Up to 5.1.1 some post meta, term meta and one option were stored under keys starting with "__", and the
 * share count under the bare key "share_count" (VERSION 2). PostX now writes the prefixed keys below.
 * Sites keep their saved data as follows (the approach WooCommerce takes for renamed data):
 *
 * - A one-time, versioned update copies each old value to its new key, in batches, from wp-admin. The old
 *   rows are left in place, so nothing is lost and an older PostX still finds them.
 * - Until that update has finished, reading a new key that has no value yet returns the old key's value.
 * - The view count is written to the new key only. The one-time copy above still moves its old value.
 *
 * @since 5.1.2
 */
class Legacy_Keys {

	/**
	 * Bump when a key is added to a map, so the copy runs again for the new key.
	 * 3: the option copy was skipped while the read fallback was hooked; run it again.
	 */
	const VERSION = 3;

	/**
	 * True while maybe_update() reads options, so option_fallback() steps aside (static: every instance).
	 *
	 * @var bool
	 */
	private static $copying = false;

	/**
	 * Option that records the finished update.
	 */
	const DONE_OPTION = 'ultp_legacy_keys_version';

	/**
	 * Rows copied per run, per key.
	 */
	const BATCH = 500;

	/**
	 * Post meta: new key => old key.
	 */
	const POST_META = array(
		'_ultp_builder_type'            => '__ultp_builder_type',
		'_ultp_starter_pack_post'       => '__ultp_starter_pack_post',
		'_ultp_container_width'         => '__container_width',
		'_ultp_builder_sidebar'         => '__builder_sidebar',
		'_ultp_builder_widget_area'     => '__builder_widget_area',
		'_ultp_builder_feature_video'   => '__builder_feature_video',
		'_ultp_builder_feature_caption' => '__builder_feature_caption',
		'_ultp_font_settings'           => '__font_settings',
		'_ultp_post_views_count'        => '__post_views_count',
		'_ultp_share_count'             => 'share_count',
	);

	/**
	 * Term meta: new key => old key.
	 */
	const TERM_META = array(
		'_ultp_starter_pack_term' => '__ultp_starter_pack_term',
	);

	/**
	 * Options: new name => old name.
	 */
	const OPTIONS = array(
		'ultp_site_type' => '__ultp_site_type',
	);

	/**
	 * Register the read fallbacks and the update.
	 */
	public function __construct() {
		if ( self::is_done() ) {
			return;
		}
		add_filter( 'get_post_metadata', array( $this, 'post_meta_fallback' ), 10, 4 );
		add_filter( 'get_term_metadata', array( $this, 'term_meta_fallback' ), 10, 4 );
		foreach ( array_keys( self::OPTIONS ) as $option ) {
			add_filter( 'default_option_' . $option, array( $this, 'option_fallback' ), 10, 2 );
		}
		add_action( 'admin_init', array( $this, 'maybe_update' ) );
	}

	/**
	 * Whether every old value has been copied to its new key.
	 *
	 * @return bool
	 */
	public static function is_done() {
		return (int) get_option( self::DONE_OPTION, 0 ) >= self::VERSION;
	}

	/**
	 * Post meta read: a new key with no value yet answers with the old key's value.
	 *
	 * @param mixed  $value     Null, or a value another filter returned.
	 * @param int    $object_id Post ID.
	 * @param string $meta_key  Meta key.
	 * @param bool   $single    Whether one value is wanted.
	 * @return mixed
	 */
	public function post_meta_fallback( $value, $object_id, $meta_key, $single ) {
		return $this->meta_fallback( 'post', self::POST_META, $value, $object_id, $meta_key );
	}

	/**
	 * Term meta read: as post_meta_fallback().
	 *
	 * @param mixed  $value     Null, or a value another filter returned.
	 * @param int    $object_id Term ID.
	 * @param string $meta_key  Meta key.
	 * @param bool   $single    Whether one value is wanted.
	 * @return mixed
	 */
	public function term_meta_fallback( $value, $object_id, $meta_key, $single ) {
		return $this->meta_fallback( 'term', self::TERM_META, $value, $object_id, $meta_key );
	}

	/**
	 * Shared meta fallback. Returning an array makes get_metadata() use it as the stored values.
	 *
	 * @param string $type      'post' or 'term'.
	 * @param array  $map       New key => old key.
	 * @param mixed  $value     Null, or a value another filter returned.
	 * @param int    $object_id Object ID.
	 * @param string $meta_key  Meta key.
	 * @return mixed
	 */
	private function meta_fallback( $type, $map, $value, $object_id, $meta_key ) {
		if ( null !== $value || ! is_string( $meta_key ) || ! isset( $map[ $meta_key ] ) ) {
			return $value;
		}
		// All meta of the object, from the meta cache. The key is empty, so this filter does not run again.
		$all = get_metadata( $type, $object_id );
		if ( ! is_array( $all ) || isset( $all[ $meta_key ] ) || ! isset( $all[ $map[ $meta_key ] ] ) ) {
			return $value;
		}
		return array_map( 'maybe_unserialize', $all[ $map[ $meta_key ] ] );
	}

	/**
	 * Option read: a new option that is not saved yet answers with the old option's value.
	 *
	 * @param mixed  $default Default value.
	 * @param string $option  Option name.
	 * @return mixed
	 */
	public function option_fallback( $default, $option ) {
		if ( self::$copying ) {
			return $default;
		}
		$map = self::OPTIONS;
		return isset( $map[ $option ] ) ? get_option( $map[ $option ], $default ) : $default;
	}

	/**
	 * Copy old values to the new keys, one batch per key per admin request, until none is left.
	 */
	public function maybe_update() {
		if ( self::is_done() || wp_doing_ajax() || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		// One request at a time: the lock expires by itself if a request dies.
		if ( get_transient( 'ultp_legacy_keys_lock' ) ) {
			return;
		}
		set_transient( 'ultp_legacy_keys_lock', 1, MINUTE_IN_SECONDS );
		// Saved (autoloaded) from the first run, so is_done() is answered from the options cache on every request.
		add_option( self::DONE_OPTION, 0 );

		$left = 0;
		foreach ( self::POST_META as $new => $old ) {
			$left += $this->copy_meta( 'post', $new, $old );
		}
		foreach ( self::TERM_META as $new => $old ) {
			$left += $this->copy_meta( 'term', $new, $old );
		}
		foreach ( self::OPTIONS as $new => $old ) {
			// Ask about the saved option itself: while the update is pending, option_fallback()
			// answers a missing new option with the old value, so the copy would be skipped.
			self::$copying = true;
			$saved         = get_option( $old, null );
			$current       = get_option( $new, false );
			self::$copying = false;
			if ( null !== $saved && false === $current ) {
				add_option( $new, $saved );
			}
		}

		if ( 0 === $left ) {
			update_option( self::DONE_OPTION, self::VERSION );
		}
		delete_transient( 'ultp_legacy_keys_lock' );
	}

	/**
	 * Copy one batch of an old meta key to its new key, for objects that do not have the new key yet.
	 *
	 * @param string $type 'post' or 'term'.
	 * @param string $new  New key.
	 * @param string $old  Old key.
	 * @return int How many objects were copied (0 when this key is finished).
	 */
	private function copy_meta( $type, $new, $old ) {
		global $wpdb;
		if ( 'term' === $type ) {
			$table = $wpdb->termmeta;
			$id    = 'term_id';
			$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT o.term_id AS object_id, o.meta_value FROM {$wpdb->termmeta} o LEFT JOIN {$wpdb->termmeta} n ON n.term_id = o.term_id AND n.meta_key = %s WHERE o.meta_key = %s AND n.meta_id IS NULL ORDER BY o.meta_id LIMIT %d", $new, $old, self::BATCH ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time data update.
		} else {
			$table = $wpdb->postmeta;
			$id    = 'post_id';
			$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT o.post_id AS object_id, o.meta_value FROM {$wpdb->postmeta} o LEFT JOIN {$wpdb->postmeta} n ON n.post_id = o.post_id AND n.meta_key = %s WHERE o.meta_key = %s AND n.meta_id IS NULL ORDER BY o.meta_id LIMIT %d", $new, $old, self::BATCH ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time data update.
		}
		if ( empty( $rows ) ) {
			return 0;
		}
		$done = array();
		foreach ( $rows as $row ) {
			$object_id = (int) $row->object_id;
			if ( isset( $done[ $object_id ] ) ) {
				continue;
			}
			// The value is copied exactly as stored (already serialized), so it reads back the same.
			$wpdb->insert( $table, array( $id => $object_id, 'meta_key' => $new, 'meta_value' => $row->meta_value ), array( '%d', '%s', '%s' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.SlowDBQuery.slow_db_query_meta_key,WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- One-time data update.
			wp_cache_delete( $object_id, $type . '_meta' );
			$done[ $object_id ] = true;
		}
		return count( $done );
	}
}
