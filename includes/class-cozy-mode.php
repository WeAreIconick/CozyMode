<?php
/**
 * Main Cozy Mode functionality class
 *
 * @package CozyMode
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cozy Mode main functionality class
 */
class Cozy_Mode_Main {

	/**
	 * Single instance of the class
	 *
	 * @var Cozy_Mode_Main
	 */
	private static $instance = null;

	/**
	 * Get single instance of the class
	 *
	 * @return Cozy_Mode_Main
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
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'the_content', array( $this, 'add_cozy_mode_button' ), 20 );
		add_action( 'wp_footer', array( $this, 'add_modal_html' ) );
	}

	/**
	 * Enqueue CSS and JavaScript assets
	 */
	public function enqueue_assets() {
		// Only load on singular posts/pages
		if ( ! is_singular() ) {
			return;
		}

		// Enqueue CSS
		wp_enqueue_style(
			'cozy-mode-css',
			COZY_MODE_PLUGIN_URL . 'assets/css/cozy-mode.css',
			array(),
			COZY_MODE_VERSION
		);

		// Enqueue Readability.js locally
		wp_enqueue_script(
			'readability-js',
			COZY_MODE_PLUGIN_URL . 'assets/js/readability.js',
			array(),
			COZY_MODE_VERSION,
			true
		);

		// Enqueue main JavaScript
		wp_enqueue_script(
			'cozy-mode-js',
			COZY_MODE_PLUGIN_URL . 'assets/js/cozy-mode.js',
			array( 'readability-js' ),
			COZY_MODE_VERSION,
			true
		);

		// Localize script with data
		$localize_data = array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'cozy_mode' ),
			'postId' => get_the_ID(),
			'strings' => array(
				'enterCozyMode' => __( 'Enter Cozy Mode', 'cozy-mode' ),
				'closeCozyMode' => __( 'Close Cozy Mode', 'cozy-mode' ),
				'readingMode' => __( 'Reading Mode', 'cozy-mode' ),
				'extractionError' => __( 'Unable to extract content. Showing original content.', 'cozy-mode' ),
			),
		);

		wp_localize_script( 'cozy-mode-js', 'cozyMode', $localize_data );
	}

	/**
	 * Add Cozy Mode button to post content
	 *
	 * @param string $content Post content.
	 * @return string Modified content.
	 */
	public function add_cozy_mode_button( $content ) {
		// Only add button on singular posts/pages and main query
		if ( ! is_singular() || ! is_main_query() ) {
			return $content;
		}

		// Don't add button to password protected posts
		if ( post_password_required() ) {
			return $content;
		}

		// Create the button HTML
		$button_html = sprintf(
			'<div class="cozy-mode-button-container">
				<button type="button" class="cozy-mode-toggle" aria-label="%s" data-post-id="%d">
					<span class="cozy-mode-icon" aria-hidden="true">📖</span>
					<span class="screen-reader-text">%s</span>
				</button>
			</div>',
			esc_attr__( 'Enter Cozy Mode for better reading experience', 'cozy-mode' ),
			esc_attr( get_the_ID() ),
			esc_html__( 'Enter Cozy Mode - Opens content in distraction-free reading mode with optimal typography', 'cozy-mode' )
		);

		// Add button before content
		return $button_html . $content;
	}

	/**
	 * Add modal HTML to footer
	 */
	public function add_modal_html() {
		// Only add modal on singular posts/pages
		if ( ! is_singular() ) {
			return;
		}

		?>
		<div id="cozy-mode-modal" class="cozy-mode-modal" role="dialog" aria-modal="true" hidden>
			<div class="cozy-mode-backdrop" aria-hidden="true"></div>
			<div class="cozy-mode-click-overlay"></div>
			<button type="button" class="cozy-mode-close" aria-label="<?php esc_attr_e( 'Close Cozy Mode', 'cozy-mode' ); ?>">
				<span aria-hidden="true">&times;</span>
			</button>
			<div class="cozy-mode-container">
				<div class="cozy-mode-content">
					<div class="cozy-mode-article">
						<!-- Content will be populated by JavaScript -->
					</div>
				</div>
				<div class="cozy-mode-controls">
					<button type="button" class="cozy-mode-control cozy-mode-font-decrease" aria-label="<?php esc_attr_e( 'Decrease font size', 'cozy-mode' ); ?>">A-</button>
					<button type="button" class="cozy-mode-control cozy-mode-font-reset" aria-label="<?php esc_attr_e( 'Reset font size', 'cozy-mode' ); ?>">A</button>
					<button type="button" class="cozy-mode-control cozy-mode-font-increase" aria-label="<?php esc_attr_e( 'Increase font size', 'cozy-mode' ); ?>">A+</button>
					<button type="button" class="cozy-mode-control cozy-mode-theme-toggle" aria-label="<?php esc_attr_e( 'Toggle dark mode', 'cozy-mode' ); ?>">🌙</button>
				</div>
			</div>
		</div>
		<?php
	}
}
