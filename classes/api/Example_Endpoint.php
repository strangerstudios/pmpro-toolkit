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
class Example_Endpoint extends API_Endpoint {

	// Trait to handle performance tracking
	use \TK\PerformanceTrackingTrait;

	/**
	 * Constructor
	 */
	public function __construct() { }

	/**
	 * Register the example REST API routes for this endpoint
	 */
	public function register_routes() {

		register_rest_route(
			$this->get_namespace(),
			'/test-example',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'permission_callback' => array( $this, 'handle_permissions' ),
				'callback'            => array( $this, 'handle_request' ),
				'args'                => array(
					'user_id' => array(
						'required' => true,
						'type'     => 'integer',
					),
				),
			)
		);
	}

	/**
	 * Check if the user has the right permissions
	 * TODO: Add logic to check if the user has the right permissions
	 */
	public function handle_permissions() {
		return true;
	}

	/**
	 * Get a user.
	 *
	 * @param WP_REST_Request $request
	 * @return void
	 */
	public function handle_request( WP_REST_Request $request ) {
		$user_id = absint( $request->get_param( 'user_id' ) );

		if ( empty( $user_id ) ) {
			return new WP_Error( 'invalid_user_id', 'Invalid user ID', array( 'status' => 400 ) );
		}

		// Start performance tracking
		$this->start_performance_tracking();

		$user = get_user_by( 'id', $user_id );

		if ( ! $user ) {
			return $this->json_error( 'user_not_found', 'User not found.', 404 );
		}

		// End performance tracking
		$performance_data = $this->end_performance_tracking();

		// Prepare the response data
		$user_data = array(
			'id'             => $user->ID,
			'username'       => $user->user_login,
			'email'          => $user->user_email,
			'display_name'   => $user->display_name,
			'duration_sec'   => $performance_data['duration_sec'],
			'queries'        => $performance_data['queries'],
			'db_time_sec'    => $performance_data['db_time_sec'],
			'peak_memory_kb' => $performance_data['peak_memory_kb'],
		);

		return $this->json_success( $user_data );
	}
}
