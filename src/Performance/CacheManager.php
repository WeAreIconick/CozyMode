<?php
/**
 * Cache Manager
 *
 * @package CozyMode
 * @since 1.0.0
 */

namespace CozyMode\Performance;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cache Manager Class
 *
 * @since 1.0.0
 */
class CacheManager {

	/**
	 * Cache group
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const CACHE_GROUP = 'cozy_mode';

	/**
	 * Cache version
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $cache_version;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->cache_version = get_option( 'cozy_mode_cache_version', '1.0.0' );
		$this->init_hooks();
	}

	/**
	 * Initialize cache hooks
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		add_action( 'save_post', array( $this, 'invalidate_post_cache' ) );
		add_action( 'delete_post', array( $this, 'invalidate_post_cache' ) );
		add_action( 'wp_update_nav_menu', array( $this, 'invalidate_all_cache' ) );
		add_action( 'customize_save_after', array( $this, 'invalidate_all_cache' ) );
	}

	/**
	 * Get cached data
	 *
	 * @since 1.0.0
	 * @param string $key Cache key.
	 * @return mixed
	 */
	public function get( $key ) {
		$cache_key = $this->get_cache_key( $key );
		
		// Try object cache first
		$data = wp_cache_get( $cache_key, self::CACHE_GROUP );
		if ( false !== $data ) {
			return $data;
		}

		// Fallback to transient
		return get_transient( $cache_key );
	}

	/**
	 * Set cached data
	 *
	 * @since 1.0.0
	 * @param string $key Cache key.
	 * @param mixed $data Data to cache.
	 * @param int $expiration Expiration time in seconds.
	 * @return bool
	 */
	public function set( $key, $data, $expiration = 3600 ) {
		$cache_key = $this->get_cache_key( $key );
		
		// Set in object cache
		wp_cache_set( $cache_key, $data, self::CACHE_GROUP, $expiration );
		
		// Also set in transient for persistence
		return set_transient( $cache_key, $data, $expiration );
	}

	/**
	 * Delete cached data
	 *
	 * @since 1.0.0
	 * @param string $key Cache key.
	 * @return bool
	 */
	public function delete( $key ) {
		$cache_key = $this->get_cache_key( $key );
		
		// Delete from object cache
		wp_cache_delete( $cache_key, self::CACHE_GROUP );
		
		// Delete from transient
		return delete_transient( $cache_key );
	}

	/**
	 * Get cache key with version
	 *
	 * @since 1.0.0
	 * @param string $key Base cache key.
	 * @return string
	 */
	private function get_cache_key( $key ) {
		return $key . '_v' . $this->cache_version;
	}

	/**
	 * Check if cache is enabled
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function is_enabled() {
		return ! ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'COZY_MODE_DISABLE_CACHE' ) && COZY_MODE_DISABLE_CACHE );
	}

	/**
	 * Get cache status
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_cache_status() {
		if ( ! $this->is_enabled() ) {
			return 'disabled';
		}

		if ( wp_using_ext_object_cache() ) {
			return 'object-cache';
		}

		return 'transient';
	}

	/**
	 * Invalidate post-specific cache
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID.
	 */
	public function invalidate_post_cache( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return;
		}

		// Delete post-specific cache
		$this->delete( 'cozy_mode_button_' . $post_id );
		$this->delete( 'cozy_mode_assets_' . $post_id );
		$this->delete( 'cozy_mode_content_' . $post_id );

		// Log cache invalidation
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'wp_debug_log' ) ) {
			wp_debug_log( 'Cozy Mode: Cache invalidated for post ID ' . $post_id );
		}
	}

	/**
	 * Invalidate all cache
	 *
	 * @since 1.0.0
	 */
	public function invalidate_all_cache() {
		// Update cache version to invalidate all cache
		$this->cache_version = time();
		update_option( 'cozy_mode_cache_version', $this->cache_version );

		// Clear object cache
		wp_cache_flush();

		// Clear transients
		$this->clear_all_transients();

		// Log cache invalidation
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'wp_debug_log' ) ) {
			wp_debug_log( 'Cozy Mode: All cache invalidated' );
		}
	}

	/**
	 * Clear all plugin transients
	 *
	 * @since 1.0.0
	 */
	private function clear_all_transients() {
		// Use WordPress cache functions instead of direct database queries
		wp_cache_flush();
		
		// Clear any known transients manually
		$known_transients = array(
			'cozy_mode_rate_' . md5( $this->get_client_ip() ),
			'cozy_mode_button_' . get_the_ID(),
			'cozy_mode_assets_' . get_the_ID(),
			'cozy_mode_content_' . get_the_ID(),
		);
		
		foreach ( $known_transients as $transient ) {
			delete_transient( $transient );
		}
	}
	
	/**
	 * Get client IP address safely
	 *
	 * @since 1.0.0
	 * @return string
	 */
	private function get_client_ip() {
		$ip_keys = array(
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_X_CLUSTER_CLIENT_IP',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR',
		);

		foreach ( $ip_keys as $key ) {
			if ( array_key_exists( $key, $_SERVER ) === true ) {
				$server_value = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
				foreach ( explode( ',', $server_value ) as $ip ) {
					$ip = trim( $ip );
					if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) !== false ) {
						return $ip;
					}
				}
			}
		}

		return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0.0.0.0';
	}

	/**
	 * Get cache statistics
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_cache_stats() {
		// Use WordPress cache functions instead of direct database queries
		$transient_count = 0;
		
		// Count known transients manually
		$known_transients = array(
			'cozy_mode_rate_' . md5( $this->get_client_ip() ),
			'cozy_mode_button_' . get_the_ID(),
			'cozy_mode_assets_' . get_the_ID(),
			'cozy_mode_content_' . get_the_ID(),
		);
		
		foreach ( $known_transients as $transient ) {
			if ( get_transient( $transient ) !== false ) {
				$transient_count++;
			}
		}

		return array(
			'version' => $this->cache_version,
			'enabled' => $this->is_enabled(),
			'type' => $this->get_cache_status(),
			'transient_count' => (int) $transient_count,
			'object_cache' => wp_using_ext_object_cache(),
		);
	}

	/**
	 * Warm up cache
	 *
	 * @since 1.0.0
	 * @param array $post_ids Post IDs to warm up.
	 */
	public function warm_up_cache( $post_ids = array() ) {
		if ( empty( $post_ids ) ) {
		// Get recent posts (only posts, not pages)
		$posts = get_posts( array(
			'numberposts' => 10,
			'post_status' => 'publish',
			'post_type' => 'post',
		) );
			$post_ids = wp_list_pluck( $posts, 'ID' );
		}

		foreach ( $post_ids as $post_id ) {
			// Pre-cache button HTML
			$button_html = $this->generate_button_html( $post_id );
			$this->set( 'cozy_mode_button_' . $post_id, $button_html, 3600 );

			// Pre-cache content
			$content = $this->generate_content_cache( $post_id );
			$this->set( 'cozy_mode_content_' . $post_id, $content, 1800 );
		}
	}

	/**
	 * Generate button HTML for caching
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID.
	 * @return string
	 */
	private function generate_button_html( $post_id ) {
		return sprintf(
			'<div class="cozy-mode-button-container">
				<button type="button" class="cozy-mode-toggle" aria-label="%s" data-post-id="%d">
					<span class="cozy-mode-icon" aria-hidden="true">📖</span>
					<span class="screen-reader-text">%s</span>
				</button>
			</div>',
			esc_attr__( 'Enter Cozy Mode for better reading experience', 'cozy-mode' ),
			esc_attr( $post_id ),
			esc_html__( 'Enter Cozy Mode - Opens content in distraction-free reading mode with optimal typography', 'cozy-mode' )
		);
	}

	/**
	 * Generate content cache
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID.
	 * @return array
	 */
	private function generate_content_cache( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return array();
		}

		return array(
			'title' => get_the_title( $post_id ),
			'content' => apply_filters( 'the_content', $post->post_content ),
			'excerpt' => get_the_excerpt( $post_id ),
			'date' => get_the_date( '', $post_id ),
			'author' => get_the_author_meta( 'display_name', $post->post_author ),
		);
	}
}
