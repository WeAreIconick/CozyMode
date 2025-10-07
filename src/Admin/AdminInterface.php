<?php
/**
 * Admin Interface
 *
 * @package CozyMode
 * @since 1.0.0
 */

namespace CozyMode\Admin;

use CozyMode\Core\Plugin;
use CozyMode\Performance\CacheManager;
use CozyMode\Utils\Logger;
use CozyMode\Tests\TestSuite;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin Interface Class
 *
 * @since 1.0.0
 */
class AdminInterface {

	/**
	 * Plugin instance
	 *
	 * @since 1.0.0
	 * @var Plugin
	 */
	private $plugin;

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
		$this->cache = new CacheManager();
		$this->logger = new Logger();

		$this->init_hooks();
	}

	/**
	 * Initialize admin interface
	 *
	 * @since 1.0.0
	 */
	public function init() {
		// Admin interface is initialized via hooks
	}

	/**
	 * Initialize WordPress hooks
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'wp_ajax_cozy_mode_clear_cache', array( $this, 'handle_clear_cache' ) );
		add_action( 'wp_ajax_cozy_mode_run_tests', array( $this, 'handle_run_tests' ) );
		add_action( 'wp_ajax_cozy_mode_export_logs', array( $this, 'handle_export_logs' ) );
	}

	/**
	 * Add admin menu
	 *
	 * @since 1.0.0
	 */
	public function add_admin_menu() {
		add_options_page(
			__( 'Cozy Mode', 'cozy-mode' ),
			__( 'Cozy Mode', 'cozy-mode' ),
			'manage_options',
			'cozy-mode',
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Enqueue admin assets
	 *
	 * @since 1.0.0
	 * @param string $hook_suffix Current admin page hook suffix.
	 */
	public function enqueue_admin_assets( $hook_suffix ) {
		if ( 'settings_page_cozy-mode' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'cozy-mode-admin-css',
			$this->plugin->get_plugin_url() . 'assets/css/admin.css',
			array(),
			Plugin::VERSION
		);

		wp_enqueue_script(
			'cozy-mode-admin-js',
			$this->plugin->get_plugin_url() . 'assets/js/admin.js',
			array( 'jquery' ),
			Plugin::VERSION,
			true
		);

		wp_localize_script( 'cozy-mode-admin-js', 'cozyModeAdmin', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'cozy_mode_admin' ),
			'strings' => array(
				'clearCache' => __( 'Clear Cache', 'cozy-mode' ),
				'runTests' => __( 'Run Tests', 'cozy-mode' ),
				'exportLogs' => __( 'Export Logs', 'cozy-mode' ),
				'loading' => __( 'Loading...', 'cozy-mode' ),
				'success' => __( 'Success!', 'cozy-mode' ),
				'error' => __( 'Error!', 'cozy-mode' ),
			),
		) );
	}

	/**
	 * Render admin page
	 *
	 * @since 1.0.0
	 */
	public function render_admin_page() {
		$cache_stats = $this->cache->get_cache_stats();
		$log_stats = $this->logger->get_log_stats();
		$test_suite = new TestSuite();
		$test_results = $test_suite->run_all_tests();
		$test_summary = $test_suite->get_summary();

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Cozy Mode Dashboard', 'cozy-mode' ); ?></h1>
			
			<div class="cozy-mode-admin-grid">
				<!-- Plugin Status -->
				<div class="cozy-mode-card">
					<h2><?php esc_html_e( 'Plugin Status', 'cozy-mode' ); ?></h2>
					<div class="cozy-mode-status">
						<div class="status-item">
							<span class="label"><?php esc_html_e( 'Version:', 'cozy-mode' ); ?></span>
							<span class="value"><?php echo esc_html( Plugin::VERSION ); ?></span>
						</div>
						<div class="status-item">
							<span class="label"><?php esc_html_e( 'Status:', 'cozy-mode' ); ?></span>
							<span class="value status-active"><?php esc_html_e( 'Active', 'cozy-mode' ); ?></span>
						</div>
						<div class="status-item">
							<span class="label"><?php esc_html_e( 'WordPress:', 'cozy-mode' ); ?></span>
							<span class="value"><?php echo esc_html( get_bloginfo( 'version' ) ); ?></span>
						</div>
					</div>
				</div>

				<!-- Cache Statistics -->
				<div class="cozy-mode-card">
					<h2><?php esc_html_e( 'Cache Statistics', 'cozy-mode' ); ?></h2>
					<div class="cozy-mode-stats">
						<div class="stat-item">
							<span class="label"><?php esc_html_e( 'Cache Type:', 'cozy-mode' ); ?></span>
							<span class="value"><?php echo esc_html( ucfirst( $cache_stats['type'] ) ); ?></span>
						</div>
						<div class="stat-item">
							<span class="label"><?php esc_html_e( 'Enabled:', 'cozy-mode' ); ?></span>
							<span class="value"><?php echo $cache_stats['enabled'] ? esc_html__( 'Yes', 'cozy-mode' ) : esc_html__( 'No', 'cozy-mode' ); ?></span>
						</div>
						<div class="stat-item">
							<span class="label"><?php esc_html_e( 'Transients:', 'cozy-mode' ); ?></span>
							<span class="value"><?php echo esc_html( $cache_stats['transient_count'] ); ?></span>
						</div>
						<div class="stat-item">
							<span class="label"><?php esc_html_e( 'Version:', 'cozy-mode' ); ?></span>
							<span class="value"><?php echo esc_html( $cache_stats['version'] ); ?></span>
						</div>
					</div>
					<div class="cozy-mode-actions">
						<button type="button" class="button button-secondary" id="clear-cache">
							<?php esc_html_e( 'Clear Cache', 'cozy-mode' ); ?>
						</button>
					</div>
				</div>

				<!-- Test Results -->
				<div class="cozy-mode-card">
					<h2><?php esc_html_e( 'Test Results', 'cozy-mode' ); ?></h2>
					<div class="cozy-mode-test-summary">
						<div class="test-summary-item">
							<span class="label"><?php esc_html_e( 'Total Tests:', 'cozy-mode' ); ?></span>
							<span class="value"><?php echo esc_html( $test_summary['total'] ); ?></span>
						</div>
						<div class="test-summary-item">
							<span class="label"><?php esc_html_e( 'Passed:', 'cozy-mode' ); ?></span>
							<span class="value test-passed"><?php echo esc_html( $test_summary['passed'] ); ?></span>
						</div>
						<div class="test-summary-item">
							<span class="label"><?php esc_html_e( 'Failed:', 'cozy-mode' ); ?></span>
							<span class="value test-failed"><?php echo esc_html( $test_summary['failed'] ); ?></span>
						</div>
						<div class="test-summary-item">
							<span class="label"><?php esc_html_e( 'Success Rate:', 'cozy-mode' ); ?></span>
							<span class="value"><?php echo esc_html( round( $test_summary['success_rate'], 1 ) ); ?>%</span>
						</div>
					</div>
					<div class="cozy-mode-actions">
						<button type="button" class="button button-secondary" id="run-tests">
							<?php esc_html_e( 'Run Tests', 'cozy-mode' ); ?>
						</button>
					</div>
				</div>

				<!-- Log Statistics -->
				<div class="cozy-mode-card">
					<h2><?php esc_html_e( 'Log Statistics', 'cozy-mode' ); ?></h2>
					<div class="cozy-mode-log-stats">
						<div class="log-stat-item">
							<span class="label"><?php esc_html_e( 'File Exists:', 'cozy-mode' ); ?></span>
							<span class="value"><?php echo $log_stats['file_exists'] ? esc_html__( 'Yes', 'cozy-mode' ) : esc_html__( 'No', 'cozy-mode' ); ?></span>
						</div>
						<?php if ( $log_stats['file_exists'] ) : ?>
						<div class="log-stat-item">
							<span class="label"><?php esc_html_e( 'File Size:', 'cozy-mode' ); ?></span>
							<span class="value"><?php echo esc_html( size_format( $log_stats['size'] ) ); ?></span>
						</div>
						<div class="log-stat-item">
							<span class="label"><?php esc_html_e( 'Lines:', 'cozy-mode' ); ?></span>
							<span class="value"><?php echo esc_html( $log_stats['lines'] ); ?></span>
						</div>
						<div class="log-stat-item">
							<span class="label"><?php esc_html_e( 'Last Modified:', 'cozy-mode' ); ?></span>
							<span class="value"><?php echo esc_html( gmdate( 'Y-m-d H:i:s', $log_stats['last_modified'] ) ); ?></span>
						</div>
						<?php endif; ?>
					</div>
					<div class="cozy-mode-actions">
						<button type="button" class="button button-secondary" id="export-logs">
							<?php esc_html_e( 'Export Logs', 'cozy-mode' ); ?>
						</button>
						<?php if ( $log_stats['file_exists'] ) : ?>
						<button type="button" class="button button-secondary" id="clear-logs">
							<?php esc_html_e( 'Clear Logs', 'cozy-mode' ); ?>
						</button>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<!-- Detailed Test Results -->
			<div class="cozy-mode-card">
				<h2><?php esc_html_e( 'Detailed Test Results', 'cozy-mode' ); ?></h2>
				<div class="cozy-mode-test-results">
					<?php foreach ( $test_results as $result ) : ?>
					<div class="test-result <?php echo $result['passed'] ? 'passed' : 'failed'; ?>">
						<div class="test-name">
							<?php echo esc_html( $result['name'] ); ?>
							<span class="test-status"><?php echo $result['passed'] ? '✓' : '✗'; ?></span>
						</div>
						<div class="test-message"><?php echo esc_html( $result['message'] ); ?></div>
						<div class="test-timestamp"><?php echo esc_html( $result['timestamp'] ); ?></div>
					</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Handle clear cache AJAX request
	 *
	 * @since 1.0.0
	 */
	public function handle_clear_cache() {
		check_ajax_referer( 'cozy_mode_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Insufficient permissions' );
		}

		$this->cache->invalidate_all_cache();

		wp_send_json_success( array(
			'message' => __( 'Cache cleared successfully', 'cozy-mode' ),
		) );
	}

	/**
	 * Handle run tests AJAX request
	 *
	 * @since 1.0.0
	 */
	public function handle_run_tests() {
		check_ajax_referer( 'cozy_mode_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Insufficient permissions' );
		}

		$test_suite = new TestSuite();
		$test_results = $test_suite->run_all_tests();
		$test_summary = $test_suite->get_summary();

		wp_send_json_success( array(
			'results' => $test_results,
			'summary' => $test_summary,
			'message' => __( 'Tests completed successfully', 'cozy-mode' ),
		) );
	}

	/**
	 * Handle export logs AJAX request
	 *
	 * @since 1.0.0
	 */
	public function handle_export_logs() {
		check_ajax_referer( 'cozy_mode_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Insufficient permissions' );
		}

		$log_file = WP_CONTENT_DIR . '/cozy-mode.log';
		
		if ( ! file_exists( $log_file ) ) {
			wp_send_json_error( array(
				'message' => __( 'Log file does not exist', 'cozy-mode' ),
			) );
		}

		$log_content = file_get_contents( $log_file );
		$filename = 'cozy-mode-logs-' . gmdate( 'Y-m-d-H-i-s' ) . '.txt';

		header( 'Content-Type: text/plain' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . strlen( $log_content ) );

		echo esc_html( $log_content );
		exit;
	}
}
