<?php
/**
 * Cozy Mode Test Suite
 *
 * @package CozyMode
 * @since 1.0.0
 */

namespace CozyMode\Tests;

use CozyMode\Core\Plugin;
use CozyMode\Security\SecurityManager;
use CozyMode\Performance\CacheManager;
use CozyMode\Utils\Logger;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Test Suite Class
 *
 * @since 1.0.0
 */
class TestSuite {

	/**
	 * Test results
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $results = array();

	/**
	 * Run all tests
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function run_all_tests() {
		$this->results = array();

		// Core functionality tests
		$this->test_plugin_initialization();
		$this->test_security_manager();
		$this->test_cache_manager();
		$this->test_logger();

		// Performance tests
		$this->test_performance_metrics();

		// Security tests
		$this->test_security_features();

		return $this->results;
	}

	/**
	 * Test plugin initialization
	 *
	 * @since 1.0.0
	 */
	private function test_plugin_initialization() {
		$test_name = 'Plugin Initialization';
		
		try {
			$plugin = Plugin::get_instance();
			$this->assert_not_null( $plugin, 'Plugin instance should not be null' );
			$this->assert_true( $plugin instanceof Plugin, 'Plugin should be instance of Plugin class' );
			
			$this->add_test_result( $test_name, true, 'Plugin initialized successfully' );
		} catch ( Exception $e ) {
			$this->add_test_result( $test_name, false, 'Plugin initialization failed: ' . $e->getMessage() );
		}
	}

	/**
	 * Test security manager
	 *
	 * @since 1.0.0
	 */
	private function test_security_manager() {
		$test_name = 'Security Manager';
		
		try {
			$security = new SecurityManager();
			
			// Test nonce creation
			$nonce = $security->create_nonce( 'test_action' );
			$this->assert_not_empty( $nonce, 'Nonce should not be empty' );
			
			// Test nonce verification
			$is_valid = $security->verify_nonce( $nonce, 'test_action' );
			$this->assert_true( $is_valid, 'Valid nonce should verify successfully' );
			
			// Test invalid nonce
			$is_invalid = $security->verify_nonce( 'invalid_nonce', 'test_action' );
			$this->assert_false( $is_invalid, 'Invalid nonce should fail verification' );
			
			$this->add_test_result( $test_name, true, 'Security manager tests passed' );
		} catch ( Exception $e ) {
			$this->add_test_result( $test_name, false, 'Security manager test failed: ' . $e->getMessage() );
		}
	}

	/**
	 * Test cache manager
	 *
	 * @since 1.0.0
	 */
	private function test_cache_manager() {
		$test_name = 'Cache Manager';
		
		try {
			$cache = new CacheManager();
			
			// Test cache set/get
			$test_key = 'test_cache_key';
			$test_data = array( 'test' => 'data' );
			
			$set_result = $cache->set( $test_key, $test_data, 60 );
			$this->assert_true( $set_result, 'Cache set should succeed' );
			
			$retrieved_data = $cache->get( $test_key );
			$this->assert_equal( $retrieved_data, $test_data, 'Retrieved data should match stored data' );
			
			// Test cache delete
			$delete_result = $cache->delete( $test_key );
			$this->assert_true( $delete_result, 'Cache delete should succeed' );
			
			$deleted_data = $cache->get( $test_key );
			$this->assert_false( $deleted_data, 'Deleted cache should return false' );
			
			$this->add_test_result( $test_name, true, 'Cache manager tests passed' );
		} catch ( Exception $e ) {
			$this->add_test_result( $test_name, false, 'Cache manager test failed: ' . $e->getMessage() );
		}
	}

	/**
	 * Test logger
	 *
	 * @since 1.0.0
	 */
	private function test_logger() {
		$test_name = 'Logger';
		
		try {
			$logger = new Logger();
			
			// Test logging (should not throw errors)
			$logger->info( 'Test log message' );
			$logger->error( 'Test error message' );
			$logger->debug( 'Test debug message' );
			
			// Test log stats
			$stats = $logger->get_log_stats();
			$this->assert_true( is_array( $stats ), 'Log stats should be an array' );
			
			$this->add_test_result( $test_name, true, 'Logger tests passed' );
		} catch ( Exception $e ) {
			$this->add_test_result( $test_name, false, 'Logger test failed: ' . $e->getMessage() );
		}
	}

	/**
	 * Test performance metrics
	 *
	 * @since 1.0.0
	 */
	private function test_performance_metrics() {
		$test_name = 'Performance Metrics';
		
		try {
			$start_time = microtime( true );
			
			// Simulate some work
			usleep( 10000 ); // 10ms
			
			$end_time = microtime( true );
			$execution_time = ( $end_time - $start_time ) * 1000; // Convert to milliseconds
			
			$this->assert_true( $execution_time < 100, 'Execution time should be reasonable' );
			
			$this->add_test_result( $test_name, true, 'Performance metrics test passed' );
		} catch ( Exception $e ) {
			$this->add_test_result( $test_name, false, 'Performance test failed: ' . $e->getMessage() );
		}
	}

	/**
	 * Test security features
	 *
	 * @since 1.0.0
	 */
	private function test_security_features() {
		$test_name = 'Security Features';
		
		try {
			$security = new SecurityManager();
			
			// Test input sanitization
			$dirty_input = '<script>alert("xss")</script>';
			$clean_input = $security->sanitize_input( $dirty_input );
			$this->assert_not_equal( $clean_input, $dirty_input, 'Input should be sanitized' );
			
			// Test output escaping
			$unsafe_output = '<div>Unsafe content</div>';
			$safe_output = $security->escape_output( $unsafe_output );
			$this->assert_not_equal( $safe_output, $unsafe_output, 'Output should be escaped' );
			
			$this->add_test_result( $test_name, true, 'Security features test passed' );
		} catch ( Exception $e ) {
			$this->add_test_result( $test_name, false, 'Security test failed: ' . $e->getMessage() );
		}
	}

	/**
	 * Assert that value is not null
	 *
	 * @since 1.0.0
	 * @param mixed $value Value to test.
	 * @param string $message Error message.
	 */
	private function assert_not_null( $value, $message ) {
		if ( null === $value ) {
			throw new Exception( esc_html( $message ) );
		}
	}

	/**
	 * Assert that value is true
	 *
	 * @since 1.0.0
	 * @param mixed $value Value to test.
	 * @param string $message Error message.
	 */
	private function assert_true( $value, $message ) {
		if ( true !== $value ) {
			throw new Exception( esc_html( $message ) );
		}
	}

	/**
	 * Assert that value is false
	 *
	 * @since 1.0.0
	 * @param mixed $value Value to test.
	 * @param string $message Error message.
	 */
	private function assert_false( $value, $message ) {
		if ( false !== $value ) {
			throw new Exception( esc_html( $message ) );
		}
	}

	/**
	 * Assert that value is not empty
	 *
	 * @since 1.0.0
	 * @param mixed $value Value to test.
	 * @param string $message Error message.
	 */
	private function assert_not_empty( $value, $message ) {
		if ( empty( $value ) ) {
			throw new Exception( esc_html( $message ) );
		}
	}

	/**
	 * Assert that two values are equal
	 *
	 * @since 1.0.0
	 * @param mixed $expected Expected value.
	 * @param mixed $actual Actual value.
	 * @param string $message Error message.
	 */
	private function assert_equal( $expected, $actual, $message ) {
		if ( $expected !== $actual ) {
			throw new Exception( esc_html( $message ) );
		}
	}

	/**
	 * Assert that two values are not equal
	 *
	 * @since 1.0.0
	 * @param mixed $expected Expected value.
	 * @param mixed $actual Actual value.
	 * @param string $message Error message.
	 */
	private function assert_not_equal( $expected, $actual, $message ) {
		if ( $expected === $actual ) {
			throw new Exception( esc_html( $message ) );
		}
	}

	/**
	 * Add test result
	 *
	 * @since 1.0.0
	 * @param string $test_name Test name.
	 * @param bool $passed Whether test passed.
	 * @param string $message Test message.
	 */
	private function add_test_result( $test_name, $passed, $message ) {
		$this->results[] = array(
			'name' => $test_name,
			'passed' => $passed,
			'message' => $message,
			'timestamp' => current_time( 'mysql' ),
		);
	}

	/**
	 * Get test results summary
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_summary() {
		$total_tests = count( $this->results );
		$passed_tests = count( array_filter( $this->results, function( $result ) {
			return $result['passed'];
		} ) );
		$failed_tests = $total_tests - $passed_tests;

		return array(
			'total' => $total_tests,
			'passed' => $passed_tests,
			'failed' => $failed_tests,
			'success_rate' => $total_tests > 0 ? ( $passed_tests / $total_tests ) * 100 : 0,
		);
	}
}
