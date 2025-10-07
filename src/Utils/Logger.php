<?php
/**
 * Logger Utility
 *
 * @package CozyMode
 * @since 1.0.0
 */

namespace CozyMode\Utils;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Logger Class
 *
 * @since 1.0.0
 */
class Logger {

	/**
	 * Log levels
	 *
	 * @since 1.0.0
	 * @var array
	 */
	const LOG_LEVELS = array(
		'emergency' => 0,
		'alert' => 1,
		'critical' => 2,
		'error' => 3,
		'warning' => 4,
		'notice' => 5,
		'info' => 6,
		'debug' => 7,
	);

	/**
	 * Current log level
	 *
	 * @since 1.0.0
	 * @var int
	 */
	private $log_level;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->log_level = $this->get_log_level();
	}

	/**
	 * Get current log level
	 *
	 * @since 1.0.0
	 * @return int
	 */
	private function get_log_level() {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			return self::LOG_LEVELS['debug'];
		}

		return self::LOG_LEVELS['error'];
	}

	/**
	 * Log a message
	 *
	 * @since 1.0.0
	 * @param string $message Log message.
	 * @param string $level Log level.
	 * @param array $context Additional context.
	 */
	public function log( $message, $level = 'info', $context = array() ) {
		// Check if we should log this level
		if ( self::LOG_LEVELS[ $level ] > $this->log_level ) {
			return;
		}

		// Format the log entry
		$log_entry = $this->format_log_entry( $message, $level, $context );

		// Write to WordPress debug log
		if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			// Use WordPress logging instead of error_log
			if ( function_exists( 'wp_debug_log' ) ) {
				wp_debug_log( $log_entry );
			}
		}

		// Store in custom log file if enabled
		if ( defined( 'COZY_MODE_CUSTOM_LOG' ) && COZY_MODE_CUSTOM_LOG ) {
			$this->write_to_file( $log_entry );
		}
	}

	/**
	 * Format log entry
	 *
	 * @since 1.0.0
	 * @param string $message Log message.
	 * @param string $level Log level.
	 * @param array $context Additional context.
	 * @return string
	 */
	private function format_log_entry( $message, $level, $context ) {
		$timestamp = current_time( 'Y-m-d H:i:s' );
		$context_string = ! empty( $context ) ? ' ' . wp_json_encode( $context ) : '';
		
		return sprintf(
			'[%s] Cozy Mode %s: %s%s',
			$timestamp,
			strtoupper( $level ),
			$message,
			$context_string
		);
	}

	/**
	 * Write to custom log file
	 *
	 * @since 1.0.0
	 * @param string $log_entry Log entry.
	 */
	private function write_to_file( $log_entry ) {
		$log_file = WP_CONTENT_DIR . '/cozy-mode.log';
		
		// Ensure directory exists
		$log_dir = dirname( $log_file );
		if ( ! wp_mkdir_p( $log_dir ) ) {
			return;
		}

		// Write to file
		file_put_contents( $log_file, $log_entry . PHP_EOL, FILE_APPEND | LOCK_EX );
	}

	/**
	 * Log emergency message
	 *
	 * @since 1.0.0
	 * @param string $message Log message.
	 * @param array $context Additional context.
	 */
	public function emergency( $message, $context = array() ) {
		$this->log( $message, 'emergency', $context );
	}

	/**
	 * Log alert message
	 *
	 * @since 1.0.0
	 * @param string $message Log message.
	 * @param array $context Additional context.
	 */
	public function alert( $message, $context = array() ) {
		$this->log( $message, 'alert', $context );
	}

	/**
	 * Log critical message
	 *
	 * @since 1.0.0
	 * @param string $message Log message.
	 * @param array $context Additional context.
	 */
	public function critical( $message, $context = array() ) {
		$this->log( $message, 'critical', $context );
	}

	/**
	 * Log error message
	 *
	 * @since 1.0.0
	 * @param string $message Log message.
	 * @param array $context Additional context.
	 */
	public function error( $message, $context = array() ) {
		$this->log( $message, 'error', $context );
	}

	/**
	 * Log warning message
	 *
	 * @since 1.0.0
	 * @param string $message Log message.
	 * @param array $context Additional context.
	 */
	public function warning( $message, $context = array() ) {
		$this->log( $message, 'warning', $context );
	}

	/**
	 * Log notice message
	 *
	 * @since 1.0.0
	 * @param string $message Log message.
	 * @param array $context Additional context.
	 */
	public function notice( $message, $context = array() ) {
		$this->log( $message, 'notice', $context );
	}

	/**
	 * Log info message
	 *
	 * @since 1.0.0
	 * @param string $message Log message.
	 * @param array $context Additional context.
	 */
	public function info( $message, $context = array() ) {
		$this->log( $message, 'info', $context );
	}

	/**
	 * Log debug message
	 *
	 * @since 1.0.0
	 * @param string $message Log message.
	 * @param array $context Additional context.
	 */
	public function debug( $message, $context = array() ) {
		$this->log( $message, 'debug', $context );
	}

	/**
	 * Get log statistics
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_log_stats() {
		$log_file = WP_CONTENT_DIR . '/cozy-mode.log';
		
		if ( ! file_exists( $log_file ) ) {
			return array(
				'file_exists' => false,
				'size' => 0,
				'lines' => 0,
			);
		}

		return array(
			'file_exists' => true,
			'size' => filesize( $log_file ),
			'lines' => count( file( $log_file ) ),
			'last_modified' => filemtime( $log_file ),
		);
	}

	/**
	 * Clear log file
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function clear_log() {
		$log_file = WP_CONTENT_DIR . '/cozy-mode.log';
		
		if ( file_exists( $log_file ) ) {
			return wp_delete_file( $log_file );
		}

		return true;
	}
}
