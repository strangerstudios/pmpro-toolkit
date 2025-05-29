<?php

namespace TK;

use WP_REST_Request;
use WP_REST_Server;
use WP_Error;

/**
 * Performance_Testing_Endpoint
 *
 * API endpoint for performance testing
 * 
 * Usage:
 * - GET /wp-json/toolkit/v1/performance-test (Read Only mode)
 * - POST /wp-json/toolkit/v1/performance-test (Read and Write mode only)
 * 
 * The endpoint is rate-limited to 100 requests per minute per IP address.
 * 
 * Read Only mode: Returns site information, database query performance, and memory usage
 * Read and Write mode: Performs write operations (creates/deletes test data) - USE ONLY ON TEST SITES
 */
class Performance_Testing_Endpoint extends API_Endpoint {

	/**
	 * Constructor
	 */
	public function __construct() { }

	/**
	 * Register the REST API route for this endpoint
	 */
	public function register_routes() {
		global $pmprodev_options;
		
		// Only register if the endpoint is enabled
		if ( empty( $pmprodev_options['performance_endpoints'] ) || $pmprodev_options['performance_endpoints'] === 'no' ) {
			return;
		}

		// Register GET endpoint for read operations
		register_rest_route(
			$this->get_namespace(),
			'/performance-test',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'permission_callback' => array( $this, 'handle_permissions' ),
				'callback'            => array( $this, 'handle_request' ),
				'args'                => array(
					'detailed' => array(
						'description' => 'Include detailed performance metrics',
						'type'        => 'boolean',
						'default'     => false,
					),
				),
			)
		);

		// Only register write methods if read_write is enabled
		if ( $pmprodev_options['performance_endpoints'] === 'read_write' ) {
			register_rest_route(
				$this->get_namespace(),
				'/performance-test',
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'permission_callback' => array( $this, 'handle_permissions' ),
					'callback'            => array( $this, 'handle_write_request' ),
				)
			);
		}
	}

	/**
	 * Permission callback for the endpoint. Rate limited by IP address.
	 *
	 * @return boolean|WP_Error
	 */
	public function handle_permissions() {
		// Get the user's IP address
		$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

		// Create a unique key for this endpoint and IP
		$key = 'tk_performance_test_rate_' . md5( $ip );

		// Get the current count
		$count = (int) get_transient( $key );

		// Allow 100 requests per minute
		if ( $count >= 100 ) {
			return new WP_Error(
				'rate_limit_exceeded',
				'Rate limit exceeded. Please try again later.',
				array( 'status' => 429 )
			);
		}

		// Increment the count
		$count++;
		set_transient( $key, $count, 60 ); // 60 seconds

		return true;
	}

	/**
	 * Handle GET requests for performance testing
	 *
	 * @param WP_REST_Request $request
	 * @return array
	 */
	public function handle_request( WP_REST_Request $request ) {
		$start_time = microtime( true );
		$start_memory = memory_get_usage();

		// Get detailed parameter
		$detailed = $request->get_param( 'detailed' );

		// Simulate some database queries and processing
		global $wpdb;
		
		$results = array();
		
		// Test 1: Get site info
		$results['site_info'] = array(
			'site_name' => get_bloginfo( 'name' ),
			'wp_version' => get_bloginfo( 'version' ),
			'php_version' => PHP_VERSION,
			'timestamp' => current_time( 'mysql' ),
			'pmpro_version' => defined( 'PMPRO_VERSION' ) ? PMPRO_VERSION : 'Not installed',
		);

		// Test 2: Database query performance
		$db_start = microtime( true );
		$users_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->users}" );
		$posts_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts}" );
		
		// PMPro-specific queries if available
		$pmpro_members_count = 0;
		$pmpro_levels_count = 0;
		if ( defined( 'PMPRO_VERSION' ) ) {
			$pmpro_members_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->pmpro_memberships_users} WHERE status = 'active'" );
			$pmpro_levels_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->pmpro_membership_levels}" );
		}
		
		$db_time = microtime( true ) - $db_start;

		$results['database_test'] = array(
			'users_count' => $users_count,
			'posts_count' => $posts_count,
			'pmpro_active_members' => $pmpro_members_count,
			'pmpro_levels_count' => $pmpro_levels_count,
			'query_time_ms' => round( $db_time * 1000, 2 ),
		);

		// Test 3: Memory and processing test
		$memory_test_start = microtime( true );
		$test_array = array();
		for ( $i = 0; $i < 1000; $i++ ) {
			$test_array[] = str_repeat( 'a', 100 );
		}
		$memory_test_time = microtime( true ) - $memory_test_start;

		$results['memory_test'] = array(
			'array_size' => count( $test_array ),
			'processing_time_ms' => round( $memory_test_time * 1000, 2 ),
		);

		// PMPro specific tests if detailed and PMPro is available
		if ( $detailed && defined( 'PMPRO_VERSION' ) && function_exists( 'pmpro_getAllLevels' ) ) {
			$pmpro_start = microtime( true );
			$levels = pmpro_getAllLevels();
			$pmpro_time = microtime( true ) - $pmpro_start;
			
			$results['pmpro_test'] = array(
				'levels_loaded' => count( $levels ),
				'load_time_ms' => round( $pmpro_time * 1000, 2 ),
			);
		}

		// Performance metrics
		$end_time = microtime( true );
		$end_memory = memory_get_usage();

		$results['performance'] = array(
			'total_execution_time_ms' => round( ( $end_time - $start_time ) * 1000, 2 ),
			'memory_used_kb' => round( ( $end_memory - $start_memory ) / 1024, 2 ),
			'peak_memory_kb' => round( memory_get_peak_usage() / 1024, 2 ),
		);

		return array(
			'success' => true,
			'mode' => 'read_only',
			'detailed' => $detailed,
			'data' => $results,
		);
	}

	/**
	 * Handle POST requests for performance testing (write operations)
	 *
	 * @param WP_REST_Request $request
	 * @return array
	 */
	public function handle_write_request( WP_REST_Request $request ) {
		$start_time = microtime( true );
		$start_memory = memory_get_usage();

		global $wpdb;
		
		$results = array();
		
		// WARNING: This will modify data - only for testing!
		$results['warning'] = 'This endpoint modifies site data and is only for testing purposes.';

		// Test 1: Create and delete a test post
		$write_start = microtime( true );
		$test_post_id = wp_insert_post( array(
			'post_title'   => 'Performance Test Post - ' . time(),
			'post_content' => 'This is a test post created by the performance testing endpoint.',
			'post_status'  => 'draft',
			'post_type'    => 'post',
		) );

		if ( ! is_wp_error( $test_post_id ) ) {
			// Delete the test post immediately
			wp_delete_post( $test_post_id, true );
			$write_time = microtime( true ) - $write_start;
			
			$results['write_test'] = array(
				'operation' => 'create_and_delete_post',
				'success' => true,
				'execution_time_ms' => round( $write_time * 1000, 2 ),
			);
		} else {
			$results['write_test'] = array(
				'operation' => 'create_and_delete_post',
				'success' => false,
				'error' => $test_post_id->get_error_message(),
			);
		}

		// Test 2: Database write test with options
		$option_start = microtime( true );
		$test_option_name = 'pmprodev_performance_test_' . time();
		update_option( $test_option_name, array( 'test' => true, 'timestamp' => time() ) );
		$option_value = get_option( $test_option_name );
		delete_option( $test_option_name );
		$option_time = microtime( true ) - $option_start;

		$results['option_test'] = array(
			'operation' => 'create_get_delete_option',
			'success' => ! empty( $option_value ),
			'execution_time_ms' => round( $option_time * 1000, 2 ),
		);

		// Performance metrics
		$end_time = microtime( true );
		$end_memory = memory_get_usage();

		$results['performance'] = array(
			'total_execution_time_ms' => round( ( $end_time - $start_time ) * 1000, 2 ),
			'memory_used_kb' => round( ( $end_memory - $start_memory ) / 1024, 2 ),
			'peak_memory_kb' => round( memory_get_peak_usage() / 1024, 2 ),
		);

		return array(
			'success' => true,
			'mode' => 'read_write',
			'data' => $results,
		);
	}
}
