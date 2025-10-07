<?php
/**
 * Plugin Name: Cozy Mode
 * Plugin URI: https://github.com/cozy-mode/cozy-mode
 * Description: Transform any WordPress post into a distraction-free reading experience with optimal typography settings (66 characters per line, 1.5 line height, 18px font size) powered by Readability.js for content extraction.
 * Version: 1.0.0
 * Author: Cozy Mode Team
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: cozy-mode
 * Requires at least: 5.0
 * Tested up to: 6.8
 * Requires PHP: 7.4
 *
 * @package CozyMode
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'COZY_MODE_VERSION', '1.0.0' );
define( 'COZY_MODE_PLUGIN_FILE', __FILE__ );
define( 'COZY_MODE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'COZY_MODE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'COZY_MODE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main plugin class - Simplified for immediate functionality
 */
class Cozy_Mode {

	/**
	 * Single instance of the plugin
	 *
	 * @var Cozy_Mode
	 */
	private static $instance = null;

	/**
	 * Get single instance of the plugin
	 *
	 * @return Cozy_Mode
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize WordPress hooks
	 */
	private function init_hooks() {
		add_action( 'init', array( $this, 'init' ) );
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
	}

	/**
	 * Initialize the plugin
	 */
	public function init() {
		// Include the main functionality class
		require_once COZY_MODE_PLUGIN_DIR . 'includes/class-cozy-mode.php';

		// Initialize the main functionality
		Cozy_Mode_Main::get_instance();
	}

	/**
	 * Plugin activation
	 */
	public function activate() {
		// Set default options if needed
		$default_options = array(
			'version' => COZY_MODE_VERSION,
			'activated' => current_time( 'mysql' ),
		);

		add_option( 'cozy_mode_options', $default_options );

		// Flush rewrite rules
		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation
	 */
	public function deactivate() {
		// Clean up any transients using WordPress functions only
		// Since we don't actually use transients in this plugin, 
		// we can simply flush rewrite rules
		flush_rewrite_rules();
	}
}

// Initialize the plugin
Cozy_Mode::get_instance();
