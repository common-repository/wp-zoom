<?php
/**
 * Cache class file
 *
 * @package SeattleWebCo\WPZoom
 */

namespace SeattleWebCo\WPZoom;

/**
 * Cache data for current request and as a transient
 */
class Cache {

	/**
	 * Retrieves cache
	 *
	 * @param string $key Cache key.
	 * @param string $group Optional cache group.
	 * @return mixed False on empty or expired cache, or mixed response on success
	 */
	public static function get( $key, $group = '' ) {
		$cache = wp_cache_get( $key, $group );

		if ( false !== $cache ) {
			return json_decode( $cache, true );
		}

		$transient = get_transient( $key );

		if ( false !== $transient ) {
			return json_decode( $transient, true );
		}

		return false;
	}

	/**
	 * Sets cache and transient
	 *
	 * @param string  $key Cache key.
	 * @param mixed   $value Cache value.
	 * @param string  $group Optional cache group.
	 * @param integer $expires When to expire cached value.
	 * @return void
	 */
	public static function set( $key, $value, $group = '', $expires = 300 ) {
		if ( null === $value ) {
			return;
		}

		wp_cache_set( $key, wp_json_encode( $value ), $group, $expires );

		set_transient( $key, wp_json_encode( $value ), $expires );
	}

	/**
	 * Delete cache
	 *
	 * @param string $key Cache key.
	 * @return void
	 */
	public static function delete( $key ) {
		wp_cache_delete( $key );

		delete_transient( $key );
	}

	/**
	 * Delete all cache and transients
	 *
	 * @return void
	 */
	public static function delete_all() {
		global $wpdb;

		wp_cache_flush();

		// phpcs:ignore
		$wpdb->query(
			"
			DELETE FROM $wpdb->options
			WHERE	option_name LIKE '_transient_timeout_wp_zoom%'
			OR		option_name LIKE '_transient_wp_zoom%'
		"
		);
	}

}
