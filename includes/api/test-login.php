<?php

namespace TK;

use WP_REST_Request;
use WP_REST_Server;
use WP_Error;

/**
 * Upload_Failed_Endpoint
 *
 * API endpoint for handling video upload failures
 */
class Test_Login_Endpoint extends API_Endpoint {

	/**
	 * Constructor
	 */
	public function __construct() { }

	/**
	 * Register the REST API route for this endpoint
	 */
	public function register_routes() {
		register_rest_route(
			$this->get_namespace(),
			'/test-login',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'permission_callback' => array( $this, 'permission_callback' ),
				'callback'            => array( $this, 'handle_request' ),
			)
		);
	}

	/**
	 * Permission callback for the endpoint. Unauthenticated access is allowed, but
	 * rate limiting is applied based on IP address.
	 *
	 * @return void
	 */
	public function permission_callback() {
		// Allow unauthenticated, but rate limit by IP
		$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

		// Create a unique key for this endpoint and IP
		$key = 'tk_test_login_rate_' . md5( $ip );

		// Get the current count
		$count = (int) get_transient( $key );

		if ( $count >= 5 ) {
			// Deny: too many attempts
			return new WP_Error(
				'rate_limited',
				'Too many access attempts. Please wait a minute before retrying.',
				array( 'status' => 429 )
			);
		}

		// Increment count and set/update expiration (1 minute)
		set_transient( $key, $count + 1, 60 );

		// Allow request
		return true;
	}

	/**
	 * Get a user.
	 *
	 * @param WP_REST_Request $request
	 * @return void
	 */
	public function handle_request( WP_REST_Request $request ) {
		// Start metrics collection
		$start_time = microtime( true );
		global $wpdb;
		$wpdb->queries = array();

		// TODO: Do we want persistent cookies here? [remember => true]
		$creds = array(
			'user_login'    => $request['username'],
			'user_password' => $request['password'],
			'remember'      => true,
		);
		$user  = wp_signon( $creds, false );

		// Process results
		$end_time = microtime( true );

		// Compute total DB query time
		$total_query_time = 0;

		if ( defined( 'SAVEQUERIES' ) && SAVEQUERIES && isset( $wpdb->queries ) ) {
			foreach ( $wpdb->queries as $q ) {
				$total_query_time += $q[1]; // $q[1] is query time in seconds
			}
			$total_query_time = round( $total_query_time, 4 );
		}

		// Caculate Metrics
		$query_count = get_num_queries();
		// $memory_used_kb = ($end_memory - $start_memory) / 1024;
		$peak_memory_kb = round( memory_get_peak_usage( true ) / 1024 ); // Convert to KB
		$peak_memory_kb = $peak_memory_kb ? $peak_memory_kb : 0;

		if ( is_wp_error( $user ) ) {
			return $this->json_error( $user->get_error_message(), 401, 'login_failed' );
		}

		$data = array(
			'status'         => 'success',
			'user_id'        => $user->ID,
			'duration'       => $end_time - $start_time,
			'queries'        => $query_count,
			'db_time_sec'    => $total_query_time,
			'peak_memory_kb' => $peak_memory_kb,
		);

		return $this->json_success( $data );
	}
}
