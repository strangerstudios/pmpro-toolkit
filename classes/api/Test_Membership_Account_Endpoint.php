<?php

namespace TK;

use WP_REST_Request;
use WP_REST_Server;
use WP_Error;

class Test_Membership_Account_Endpoint extends API_Endpoint {

	// Trait to handle performance tracking
	use PerformanceTrackingTrait;

	public function __construct() {}

	public function register_routes() {
		register_rest_route(
			$this->get_namespace(),
			'/test-account-page',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'permission_callback' => array( $this, 'handle_permissions' ), // inherits from Abstract_API_Endpoint (require authentication)
				'callback'            => array( $this, 'handle_request' ),
			)
		);
	}

	/**
	 * Simulate viewing the Paid Memberships Pro Membership Account page.
	 *
	 * This endpoint enables automated testing of the account page rendering and hooks.
	 * Must be authenticated to access the account page.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function handle_request( WP_REST_Request $request ) {

		// Start performance tracking
		$this->start_performance_tracking();

		$params = $request->get_json_params();

		// Get the current user ID from the logged in user
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return $this->json_error(
				'not_logged_in',
				'You must be logged in to perform this test.',
				array( 'status' => 401 )
			);
		}

		// Buffer output to avoid sending HTML to the response.
		ob_start();

		// Simulate viewing the account page
		if ( function_exists( 'pmpro_loadTemplate' ) ) {
			pmpro_loadTemplate( 'account' );
		} else {
			do_action( 'pmpro_account_preheader' );
			do_action( 'pmpro_account_bullets_top' );
			do_action( 'pmpro_account_bullets_bottom' );
		}

		ob_end_clean();

		// Get performance data
		$performance_data = $this->end_performance_tracking();

		// Prepare the response data
		$data = array(
			'user_id' => $user_id,
			'metrics' => $performance_data,
		);

		return $this->json_success( $data );
	}
}
