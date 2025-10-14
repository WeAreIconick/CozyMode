<?php
/**
 * Frontend Functionality
 *
 * @package CozyMode
 * @since 1.0.0
 */

namespace CozyMode\Frontend;

use CozyMode\Core\Plugin;
use CozyMode\Security\SecurityManager;
use CozyMode\Performance\CacheManager;
use CozyMode\Utils\Logger;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cozy Mode Frontend Class
 *
 * @since 1.0.0
 */
class CozyModeFrontend {

	/**
	 * Plugin instance
	 *
	 * @since 1.0.0
	 * @var Plugin
	 */
	private $plugin;

	/**
	 * Security manager
	 *
	 * @since 1.0.0
	 * @var SecurityManager
	 */
	private $security;

	/**
	 * Cache manager
	 *
	 * @since 1.0.0
	 * @var CacheManager
	 */
	private $cache;

	/**
	 * Logger instance
	 *
	 * @since 1.0.0
	 * @var Logger
	 */
	private $logger;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->plugin = Plugin::get_instance();
		$this->security = new SecurityManager();
		$this->cache = new CacheManager();
		$this->logger = new Logger();
	}

	/**
	 * Initialize frontend functionality
	 *
	 * @since 1.0.0
	 */
	public function init() {
		$this->init_hooks();
		$this->enqueue_assets();
	}

	/**
	 * Initialize WordPress hooks
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		add_filter( 'the_content', array( $this, 'add_cozy_mode_button' ), 20 );
		add_action( 'wp_footer', array( $this, 'add_modal_html' ) );
		add_action( 'wp_head', array( $this, 'add_performance_headers' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Add performance headers
	 *
	 * @since 1.0.0
	 */
	public function add_performance_headers() {
		// Add cache headers for better performance
		header( 'X-Cozy-Mode-Version: ' . Plugin::VERSION );
		header( 'X-Cozy-Mode-Cache: ' . $this->cache->get_cache_status() );
	}

	/**
	 * Enqueue CSS and JavaScript assets
	 *
	 * @since 1.0.0
	 */
	public function enqueue_assets() {
		// Only load on single post templates (not pages)
		if ( ! is_single() ) {
			return;
		}

		// Check cache first
		$cache_key = 'cozy_mode_assets_' . get_the_ID();
		$cached_assets = $this->cache->get( $cache_key );

		if ( false === $cached_assets ) {
			// Enqueue CSS with advanced optimization
			wp_enqueue_style(
				'cozy-mode-css',
				$this->plugin->get_plugin_url() . 'assets/css/cozy-mode.css',
				array(),
				Plugin::VERSION,
				'all'
			);

			// Enqueue Readability.js locally
			wp_enqueue_script(
				'readability-js',
				$this->plugin->get_plugin_url() . 'assets/js/readability.js',
				array(),
				Plugin::VERSION,
				true
			);

			// Enqueue main JavaScript with dependencies
			wp_enqueue_script(
				'cozy-mode-js',
				$this->plugin->get_plugin_url() . 'assets/js/cozy-mode.js',
				array( 'readability-js' ),
				Plugin::VERSION,
				true
			);

			// Localize script with enhanced data
			$localize_data = $this->get_localize_data();
			wp_localize_script( 'cozy-mode-js', 'cozyMode', $localize_data );

			// Cache the assets
			$this->cache->set( $cache_key, 'loaded', 3600 );
		}
	}

	/**
	 * Get localized script data
	 *
	 * @since 1.0.0
	 * @return array
	 */
	private function get_localize_data() {
		return array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce' => $this->security->create_nonce( 'cozy_mode' ),
			'postId' => get_the_ID(),
			'version' => Plugin::VERSION,
			'cacheEnabled' => $this->cache->is_enabled(),
			'strings' => array(
				'enterCozyMode' => __( 'Enter Cozy Mode', 'cozy-mode' ),
				'closeCozyMode' => __( 'Close Cozy Mode', 'cozy-mode' ),
				'readingMode' => __( 'Reading Mode', 'cozy-mode' ),
				'extractionError' => __( 'Unable to extract content. Showing original content.', 'cozy-mode' ),
				'loading' => __( 'Loading...', 'cozy-mode' ),
				'error' => __( 'An error occurred. Please try again.', 'cozy-mode' ),
			),
		);
	}

	/**
	 * Add Cozy Mode button to post content
	 *
	 * @since 1.0.0
	 * @param string $content Post content.
	 * @return string Modified content.
	 */
	public function add_cozy_mode_button( $content ) {
		// Security check
		if ( ! $this->security->can_display_button() ) {
			return $content;
		}

		// Only add button on single post templates and main query
		if ( ! is_single() || ! is_main_query() ) {
			return $content;
		}

		// Don't add button to password protected posts
		if ( post_password_required() ) {
			return $content;
		}

		// Check cache for button HTML
		$cache_key = 'cozy_mode_button_' . get_the_ID();
		$cached_button = $this->cache->get( $cache_key );

		if ( false !== $cached_button ) {
			return $cached_button . $content;
		}

		// Create the button HTML with enhanced security
		$button_html = $this->create_button_html();

		// Cache the button HTML
		$this->cache->set( $cache_key, $button_html, 1800 );

		// Log the action
		$this->logger->log( 'Button added to post ID: ' . get_the_ID(), 'info' );

		// Add button before content
		return $button_html . $content;
	}

	/**
	 * Create button HTML
	 *
	 * @since 1.0.0
	 * @return string
	 */
	private function create_button_html() {
		return sprintf(
			'<div class="cozy-mode-button-container">
				<button type="button" class="cozy-mode-toggle" aria-label="%s" data-post-id="%d" data-nonce="%s">
					<span class="cozy-mode-icon" aria-hidden="true">📖</span>
					<span class="screen-reader-text">%s</span>
				</button>
			</div>',
			esc_attr__( 'Enter Cozy Mode for better reading experience', 'cozy-mode' ),
			esc_attr( get_the_ID() ),
			esc_attr( $this->security->create_nonce( 'cozy_mode_action' ) ),
			esc_html__( 'Enter Cozy Mode - Opens content in distraction-free reading mode with optimal typography', 'cozy-mode' )
		);
	}

	/**
	 * Add modal HTML to footer
	 *
	 * @since 1.0.0
	 */
	public function add_modal_html() {
		// Only add modal on single post templates
		if ( ! is_single() ) {
			return;
		}

		// Security check
		if ( ! $this->security->can_display_modal() ) {
			return;
		}

		?>
		<div id="cozy-mode-modal" class="cozy-mode-modal" role="dialog" aria-modal="true" aria-labelledby="cozy-mode-title" hidden>
			<div class="cozy-mode-backdrop" aria-hidden="true"></div>
			<div class="cozy-mode-container">
				<div class="cozy-mode-header">
					<h1 id="cozy-mode-title" class="cozy-mode-title"><?php esc_html_e( 'Reading Mode', 'cozy-mode' ); ?></h1>
					<button type="button" class="cozy-mode-close" aria-label="<?php esc_attr_e( 'Close Cozy Mode', 'cozy-mode' ); ?>">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="cozy-mode-content">
					<div class="cozy-mode-loading" style="display: none;">
						<div class="cozy-mode-spinner"></div>
						<p><?php esc_html_e( 'Loading content...', 'cozy-mode' ); ?></p>
					</div>
					<div class="cozy-mode-article">
						<!-- Content will be populated by JavaScript -->
					</div>
				</div>
				<div class="cozy-mode-controls">
					<button type="button" class="cozy-mode-control cozy-mode-font-decrease" aria-label="<?php esc_attr_e( 'Decrease font size', 'cozy-mode' ); ?>">A-</button>
					<button type="button" class="cozy-mode-control cozy-mode-font-reset" aria-label="<?php esc_attr_e( 'Reset font size', 'cozy-mode' ); ?>">A</button>
					<button type="button" class="cozy-mode-control cozy-mode-font-increase" aria-label="<?php esc_attr_e( 'Increase font size', 'cozy-mode' ); ?>">A+</button>
					<button type="button" class="cozy-mode-control cozy-mode-theme-toggle" aria-label="<?php esc_attr_e( 'Toggle dark mode', 'cozy-mode' ); ?>">🌙</button>
					<button type="button" class="cozy-mode-control cozy-mode-print" aria-label="<?php esc_attr_e( 'Print article', 'cozy-mode' ); ?>">🖨️</button>
				</div>
			</div>
		</div>
		<?php
	}
}
