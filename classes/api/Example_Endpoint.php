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
	use PerformanceTrackingTrait;

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
	 * This example would override the requirement for authentication
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

		$user = $this->get_user( $user_id );

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
			'metrics'        => $performance_data,
		);

		return $this->json_success( $user_data );
	}

	/**
	 * A private class method example. Get user by ID.
	 *
	 * @param int $user_id
	 * @return WP_User|WP_Error
	 */
	private function get_user( $user_id ) {
		$user = get_user_by( 'id', $user_id );

		if ( ! $user ) {
			return new WP_Error( 'user_not_found', 'User not found.', array( 'status' => 404 ) );
		}

		return $user;
	}
}
