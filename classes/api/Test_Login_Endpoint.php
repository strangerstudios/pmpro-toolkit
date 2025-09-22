<?php

namespace PMPro_Toolkit;

use WP_REST_Request;
use WP_REST_Server;
use WP_Error;

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
	 * Success response:
	 * {
	 *   "success": true,
	 *   "status": "success",
	 *   "user_id": 927,
	 *   "metrics": {
	 *       "savequeries_on": true,
	 *       "queries_in_block": 2,
	 *       "duration_sec": 0.048000000000000001,
	 *       "db_time_ms": 0.25,
	 *       "block_memory_kb": 150.19999999999999,
	 *       "peak_memory_kb": 212992
	 *   }
	 * }
	 *
	 * @param WP_REST_Request $request REST request object.
	 * @return WP_REST_Response|WP_Error Response with performance metrics or error details.
	 */
	public function handle_request( WP_REST_Request $request ) {

		$method = $request->get_method();
		if ( ! $this->is_request_allowed( $method ) ) {
			return $this->json_error(
				'method_not_allowed',
				'The ' . $method . ' method is not allowed for this endpoint. Please adjust your toolkit settings.',
				array( 'status' => 405 )
			);
		}

		// Start metrics collection first
		$this->start_performance_tracking();

		// Direct credential check (bypassing wp_signon completely)
		$user = get_user_by( 'login', $request['username'] );

		if ( ! $user || ! wp_check_password( $request['password'], $user->user_pass, $user->ID ) ) {
			return $this->json_error( 'login_failed', __( 'Invalid username or incorrect password.', 'pmpro-toolkit' ), 401 );
		}

		// Stop metrics collection
		$performance_data = $this->end_performance_tracking();

		// Prepare the response data
		$data = array(
			'status'  => 'success',
			'user_id' => $user->ID,
			'metrics' => $performance_data,
		);

		return $this->json_success( $data );
	}
}
