<?php

namespace TK;

use WP_REST_Request;
use WP_REST_Server;
use WP_Error;

/**
 * Test user login via REST API and profile authentication performance.
 *
 * This endpoint enables automated performance testing of the WordPress login/authentication process,
 * mimicking the behavior of a user logging in via `wp_signon`.
 *
 * Capabilities:
 * - Authenticates a user using provided username and password (via POST request).
 * - Collects performance metrics: PHP execution time, query count, total database query time, and peak memory usage.
 * - Allows unauthenticated access with IP-based rate limiting to prevent brute-force attempts (limit: 10 requests per 30 seconds per IP).
 * - Always logs out the user immediately after the test, regardless of outcome.
 *
 * Request Parameters:
 * - username (string, required): The user login/username to authenticate.
 * - password (string, required): The user's password.
 *
 * Example payload:
 * {
 *   "username": "demo_user",
 *   "password": "secret_password"
 * }
 *
 * Response:
 * On success:
 * {
 *   "status": "success",
 *   "user_id": 123,
 *   "duration": 0.1052,
 *   "queries": 18,
 *   "db_time_sec": 0.0275,
 *   "peak_memory_kb": 6352
 * }
 * On error (e.g. invalid credentials or rate limit exceeded):
 * {
 *   "code": "login_failed",
 *   "message": "Invalid username or incorrect password.",
 *   "data": { "status": 401 }
 * }
 *
 * @param WP_REST_Request $request REST request object.
 * @return WP_REST_Response|WP_Error Response with performance metrics or error details.
 */
class Test_Login_Endpoint extends API_Endpoint {

	// Trait to handle performance tracking
	use PerformanceTrackingTrait;

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
				'permission_callback' => array( $this, 'handle_permissions' ),
				'callback'            => array( $this, 'handle_request' ),
			)
		);
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
	 * Get a user.
	 *
	 * @param WP_REST_Request $request
	 * @return void
	 */
	public function handle_request( WP_REST_Request $request ) {
		// Start metrics collection
		$this->start_performance_tracking();

		// TODO: Do we want persistent cookies here? I don't think so. [remember => true]
		$creds = array(
			'user_login'    => $request['username'],
			'user_password' => $request['password'],
			'remember'      => false,
		);

		$user = wp_signon( $creds, false );

		// If the user is not logged in, return an error and stop processing
		if ( is_wp_error( $user ) ) {
			return $this->json_error( 'login_failed', wp_strip_all_tags( $user->get_error_message() ), 401 );
		}

		// Stop metrics collection
		$performance_data = $this->end_performance_tracking();

		// Logout the user after testing
		wp_logout();

		// Prepare the response data
		$data = array(
			'status'         => 'success',
			'user_id'        => $user->ID,
			'metrics'		 => $performance_data,
		);

		return $this->json_success( $data );
	}
}
