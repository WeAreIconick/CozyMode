<?php
/**
 * Core Plugin Class
 *
 * @package CozyMode
 * @since 1.0.0
 */

namespace CozyMode\Core;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main Cozy Mode plugin class
 *
 * @since 1.0.0
 */
class Plugin {

	/**
	 * Plugin version
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const VERSION = '1.0.0';

	/**
	 * Plugin instance
	 *
	 * @since 1.0.0
	 * @var Plugin
	 */
	private static $instance = null;

	/**
	 * Plugin file path
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $plugin_file;

	/**
	 * Plugin directory path
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $plugin_dir;

	/**
	 * Plugin URL
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $plugin_url;

	/**
	 * Plugin basename
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $plugin_basename;

	/**
	 * Get plugin instance
	 *
	 * @since 1.0.0
	 * @return Plugin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->plugin_file = COZY_MODE_PLUGIN_FILE;
		$this->plugin_dir = COZY_MODE_PLUGIN_DIR;
		$this->plugin_url = COZY_MODE_PLUGIN_URL;
		$this->plugin_basename = COZY_MODE_PLUGIN_BASENAME;

		$this->init_hooks();
	}

	/**
	 * Initialize WordPress hooks
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		add_action( 'init', array( $this, 'init' ) );
		register_activation_hook( $this->plugin_file, array( $this, 'activate' ) );
		register_deactivation_hook( $this->plugin_file, array( $this, 'deactivate' ) );
	}

	/**
	 * Initialize the plugin
	 *
	 * @since 1.0.0
	 */
	public function init() {
		// Load the main functionality
		$this->load_frontend();
		
		// Load admin interface if in admin
		if ( is_admin() ) {
			$this->load_admin();
		}
	}

	/**
	 * Load admin functionality
	 *
	 * @since 1.0.0
	 */
	private function load_admin() {
		// Load admin class
		$admin = new \CozyMode\Admin\AdminInterface();
		$admin->init();
	}

	/**
	 * Plugin activation
	 *
	 * @since 1.0.0
	 */
	public function activate() {
		// Set default options
		$default_options = array(
			'version' => self::VERSION,
			'activated' => current_time( 'mysql' ),
			'cache_version' => time(),
		);

		add_option( 'cozy_mode_options', $default_options );

		// Flush rewrite rules
		flush_rewrite_rules();

		// Clear any existing caches
		wp_cache_flush();
	}

	/**
	 * Plugin deactivation
	 *
	 * @since 1.0.0
	 */
	public function deactivate() {
		// Clean up any cached data
		wp_cache_flush();

		// Clear any transients
		$this->clear_transients();

		// Flush rewrite rules
		flush_rewrite_rules();
	}

	/**
	 * Clear plugin transients
	 *
	 * @since 1.0.0
	 */
	private function clear_transients() {
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
	 * Get plugin file path
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_plugin_file() {
		return $this->plugin_file;
	}

	/**
	 * Get plugin directory path
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_plugin_dir() {
		return $this->plugin_dir;
	}

	/**
	 * Get plugin URL
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_plugin_url() {
		return $this->plugin_url;
	}

	/**
	 * Get plugin basename
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_plugin_basename() {
		return $this->plugin_basename;
	}
}
