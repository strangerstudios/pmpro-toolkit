<?php

namespace TK;

use WP_REST_Request;
use WP_REST_Server;
use WP_Error;

/**
 * Performance_Testing_Endpoint
 *
 * API endpoint for general performance testing.
 * This endpoint allows for both read and write operations, depending on the configuration.
 *
 * Usage:
 * - GET /wp-json/toolkit/v1/performance-test (Read Only mode)
 * - POST /wp-json/toolkit/v1/performance-test (Read and Write mode only)
 *
 * The endpoint is rate-limited to 5 requests per minute per IP address.
 * Read Only mode: Returns site information, database query performance, and memory usage.
 * Read and Write mode: Performs write operations (creates/deletes test data) - USE ONLY ON TEST SITES.
 */
class Test_General_Endpoint extends API_Endpoint {

	use PerformanceTrackingTrait; // Leverage the trait

	/**
	 * Register the REST API route for this endpoint
	 */
	public function register_routes() {
		$base_args = array(
			'permission_callback' => array( $this, 'handle_permissions' ),
			'args'                => array(
				'detailed' => array(
					'description' => 'Include detailed performance metrics for PMPro',
					'type'        => 'boolean',
					'default'     => false,
				),
			),
		);

		// Register GET endpoint for read operations
		$get_args             = $base_args;
		$get_args['methods']  = WP_REST_Server::READABLE;
		$get_args['callback'] = array( $this, 'handle_request' );

		register_rest_route(
			$this->get_namespace(),
			'/test-general',
			$get_args
		);

		// Check if the performance endpoints setting is enabled for read_write
		global $pmprodev_options;
		$performance_endpoints_setting = isset( $pmprodev_options['performance_endpoints'] ) ? $pmprodev_options['performance_endpoints'] : 'no';

		// Only register write method if read_write is enabled
		if ( $performance_endpoints_setting === 'read_write' ) {
			$post_args             = $base_args;
			$post_args['methods']  = WP_REST_Server::CREATABLE;
			$post_args['callback'] = array( $this, 'handle_write_request' );

			register_rest_route(
				$this->get_namespace(),
				'/test-general',
				$post_args
			);
		}
	}

	/**
	 * Permission callback for the endpoint. Unauthenticated access is allowed, but
	 * rate limiting is applied based on IP address.
	 *
	 * @return bool|WP_Error
	 */
	public function handle_permissions() {
		return $this->throttle_if_unauthenticated();
	}

	/**
	 * Handle GET requests for performance testing (read operations)
	 *
	 * @param WP_REST_Request $request
	 * @return array|\WP_REST_Response
	 */
	public function handle_request( WP_REST_Request $request ) {
		$overall_start_time   = microtime( true );
		$overall_start_memory = memory_get_usage();

		// Get detailed parameter
		$detailed = $request->get_param( 'detailed' );

		global $wpdb;
		$results = array();

		// Test 1: Get site info
		$this->start_performance_tracking();
		$results['site_info'] = array(
			'site_name'     => get_bloginfo( 'name' ),
			'site_url'      => get_bloginfo( 'url' ),
			'wp_version'    => get_bloginfo( 'version' ),
			'php_version'   => PHP_VERSION,
			'timestamp'     => current_time( 'mysql' ),
			'pmpro_version' => defined( 'PMPRO_VERSION' ) ? PMPRO_VERSION : 'Not installed',
		);

		$results['site_info']['block_performance'] = $this->end_performance_tracking();

		// Test 2: Database query performance
		$this->start_performance_tracking();
		$users_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->users}" );
		$posts_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts}" );

		$pmpro_members_count = 0;
		$pmpro_levels_count  = 0;
		if ( defined( 'PMPRO_VERSION' ) && isset( $wpdb->pmpro_memberships_users ) && isset( $wpdb->pmpro_membership_levels ) ) {
			$pmpro_members_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->pmpro_memberships_users} WHERE status = 'active'" );
			$pmpro_levels_count  = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->pmpro_membership_levels}" );
		}
		$db_block_performance = $this->end_performance_tracking();

		$results['database_test'] = array(
			'users_count'          => $users_count,
			'posts_count'          => $posts_count,
			'pmpro_active_members' => $pmpro_members_count,
			'pmpro_levels_count'   => $pmpro_levels_count,
			'metrics'              => $db_block_performance,
		);

		// Test 3: Memory and processing test
		$this->start_performance_tracking();
		$test_array = array();
		for ( $i = 0; $i < 10000; $i++ ) {
			$test_array[] = str_repeat( 'a', 100 ); // Simulate some memory usage and processing
		}
		unset( $test_array ); // Free up memory explicitly
		$memory_block_performance = $this->end_performance_tracking();

		$results['memory_test'] = array(
			'array_elements_created' => 1000, // Indicating what was done
			'metrics'                => $memory_block_performance,
		);

		// PMPro specific tests if detailed and PMPro is available
		if ( $detailed && defined( 'PMPRO_VERSION' ) && function_exists( 'pmpro_getAllLevels' ) ) {
			$this->start_performance_tracking();
			$levels                  = pmpro_getAllLevels( true ); // Pass true to force refresh from DB
			$pmpro_block_performance = $this->end_performance_tracking();

			$results['pmpro_detailed_test'] = array(
				'levels_loaded' => is_array( $levels ) ? count( $levels ) : 0,
				'metrics'       => $pmpro_block_performance,
			);
		}

		// Overall Performance metrics for the entire request
		$overall_end_time   = microtime( true );
		$overall_end_memory = memory_get_usage();

		$results['overall_request_performance'] = array(
			'total_execution_time_ms' => round( ( $overall_end_time - $overall_start_time ) * 1000, 2 ),
			'total_memory_used_kb'    => round( ( $overall_end_memory - $overall_start_memory ) / 1024, 2 ),
			'script_peak_memory_kb'   => round( memory_get_peak_usage( true ) / 1024, 2 ), // Peak for the whole script
		);

		return new \WP_REST_Response(
			array( // Always return WP_REST_Response
				'success'                  => true,
				'mode'                     => 'read_only',
				'detailed_pmpro_requested' => (bool) $detailed,
				'savequeries_enabled'      => (bool) ( defined( 'SAVEQUERIES' ) && SAVEQUERIES ),
				'data'                     => $results,
			),
			200
		);
	}

	/**
	 * Handle POST requests for performance testing (write operations)
	 *
	 * @param WP_REST_Request $request
	 * @return array|\WP_REST_Response
	 */
	public function handle_write_request( WP_REST_Request $request ) {
		$overall_start_time   = microtime( true );
		$overall_start_memory = memory_get_usage();

		global $wpdb;
		$results = array();

		// Test 1: Create and delete a test post
		// Start performance tracking for the write operation
		$this->start_performance_tracking();

		$test_post_id = wp_insert_post(
			array(
				'post_title'   => 'Performance Test Post - ' . time(),
				'post_content' => 'This is a test post created by the performance testing endpoint.',
				'post_status'  => 'draft', // Use draft to avoid it appearing on live site if something goes wrong
				'post_type'    => 'post',
			),
			true
		); // Pass true to return WP_Error on failure

		$write_test_data = array( 'operation' => 'create_and_delete_post' );

		if ( ! is_wp_error( $test_post_id ) && $test_post_id > 0 ) {
			wp_delete_post( $test_post_id, true ); // true to force delete, bypass trash
			$write_test_data['success'] = true;
		} else {
			$write_test_data['success'] = false;
			$write_test_data['error']   = is_wp_error( $test_post_id ) ? $test_post_id->get_error_message() : 'Failed to create post.';
		}
		$write_test_data['metrics'] = $this->end_performance_tracking();
		$results['write_test_post'] = $write_test_data;

		// Test 2: Database write test with options
		// Start performance tracking for the option write operation
		$this->start_performance_tracking();
		$test_option_name         = 'tk_performance_test_option_' . time();
		$option_set               = update_option(
			$test_option_name,
			array(
				'test_data' => true,
				'timestamp' => time(),
			),
			false
		); // false for autoload
		$option_value_retrieved   = get_option( $test_option_name );
		$option_deleted           = delete_option( $test_option_name );
		$option_block_performance = $this->end_performance_tracking();

		$results['option_write_test'] = array(
			'operation'        => 'create_get_delete_option',
			'option_created'   => (bool) $option_set,
			'option_retrieved' => ! empty( $option_value_retrieved ),
			'option_deleted'   => (bool) $option_deleted,
			'metrics'          => $option_block_performance,
		);

		// Overall Performance metrics for the entire request
		$overall_end_time   = microtime( true );
		$overall_end_memory = memory_get_usage();

		$results['overall_request_performance'] = array(
			'total_execution_time_ms' => round( ( $overall_end_time - $overall_start_time ) * 1000, 2 ),
			'total_memory_used_kb'    => round( ( $overall_end_memory - $overall_start_memory ) / 1024, 2 ),
			'script_peak_memory_kb'   => round( memory_get_peak_usage( true ) / 1024, 2 ),
		);

		return new \WP_REST_Response(
			array( // Always return WP_REST_Response
				'success'             => true,
				'mode'                => 'read_write',
				'warning'             => 'This endpoint modifies site data and is ONLY for testing purposes on non-production sites.',
				'savequeries_enabled' => (bool) ( defined( 'SAVEQUERIES' ) && SAVEQUERIES ),
				'data'                => $results,
			),
			200
		);
	}
}
