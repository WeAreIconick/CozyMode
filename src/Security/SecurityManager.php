<?php
/**
 * Security Manager
 *
 * @package CozyMode
 * @since 1.0.0
 */

namespace CozyMode\Security;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Security Manager Class
 *
 * @since 1.0.0
 */
class SecurityManager {

	/**
	 * Security nonce action
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const NONCE_ACTION = 'cozy_mode_security';

	/**
	 * Rate limiting data
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $rate_limits = array();

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize security hooks
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		add_action( 'init', array( $this, 'check_rate_limits' ) );
	}

	/**
	 * Create security nonce
	 *
	 * @since 1.0.0
	 * @param string $action Action name.
	 * @return string
	 */
	public function create_nonce( $action = 'cozy_mode' ) {
		return wp_create_nonce( $action );
	}

	/**
	 * Verify security nonce
	 *
	 * @since 1.0.0
	 * @param string $nonce Nonce to verify.
	 * @param string $action Action name.
	 * @return bool
	 */
	public function verify_nonce( $nonce, $action = 'cozy_mode' ) {
		return wp_verify_nonce( $nonce, $action );
	}

	/**
	 * Check if user can display button
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function can_display_button() {
		// Check rate limiting
		if ( $this->is_rate_limited() ) {
			return false;
		}

		// Check user capabilities (if needed)
		if ( is_user_logged_in() && ! current_user_can( 'read' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if user can display modal
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function can_display_modal() {
		// Check rate limiting
		if ( $this->is_rate_limited() ) {
			return false;
		}

		// Check if content is accessible
		if ( post_password_required() ) {
			return false;
		}

		return true;
	}

	/**
	 * Check rate limiting
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	private function is_rate_limited() {
		$ip = $this->get_client_ip();
		$current_time = time();
		$rate_limit_window = 60; // 1 minute
		$max_requests = 30; // 30 requests per minute

		// Get existing rate limit data
		$rate_data = get_transient( 'cozy_mode_rate_' . md5( $ip ) );

		if ( false === $rate_data ) {
			$rate_data = array(
				'count' => 1,
				'first_request' => $current_time,
			);
		} else {
			// Reset if window has passed
			if ( $current_time - $rate_data['first_request'] > $rate_limit_window ) {
				$rate_data = array(
					'count' => 1,
					'first_request' => $current_time,
				);
			} else {
				$rate_data['count']++;
			}
		}

		// Set/update transient
		set_transient( 'cozy_mode_rate_' . md5( $ip ), $rate_data, $rate_limit_window );

		// Check if rate limit exceeded
		return $rate_data['count'] > $max_requests;
	}

	/**
	 * Get client IP address
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
	 * Sanitize input data
	 *
	 * @since 1.0.0
	 * @param mixed $data Data to sanitize.
	 * @return mixed
	 */
	public function sanitize_input( $data ) {
		if ( is_array( $data ) ) {
			return array_map( array( $this, 'sanitize_input' ), $data );
		}

		if ( is_string( $data ) ) {
			return sanitize_text_field( wp_unslash( $data ) );
		}

		return $data;
	}

	/**
	 * Escape output data
	 *
	 * @since 1.0.0
	 * @param mixed $data Data to escape.
	 * @return mixed
	 */
	public function escape_output( $data ) {
		if ( is_array( $data ) ) {
			return array_map( array( $this, 'escape_output' ), $data );
		}

		if ( is_string( $data ) ) {
			return esc_html( $data );
		}

		return $data;
	}
}
