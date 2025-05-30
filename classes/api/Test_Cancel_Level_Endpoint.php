<?php

namespace TK;

use WP_REST_Request;
use WP_REST_Server;
use WP_Error;

/**
 * Simulate cancelling a Paid Memberships Pro membership level for a logged-in user and profile performance.
 *
 * This endpoint allows toolkit users to programmatically test and profile the process of cancelling a membership
 * level for an existing user (as if that user were logged in and visiting the account/cancel page).
 *
 * Capabilities:
 * - Switches the current user context to the specified user (by login or email).
 * - Cancels the user's membership level using PMPro routines, including all hooks and add-ons.
 * - Can restore the user’s original membership level after profiling for a clean test (optional).
 * - Returns detailed performance data: PHP time, DB queries, DB time, peak memory usage.
 *
 * Request Parameters:
 * - membership_level (int, required): The membership level ID to cancel.
 * - cleanup (bool, optional): If true, restores the user’s original membership level after test (default: false).
 *
 * Example payload:
 * {
 *   "membership_level": 2,
 *   "cleanup": true
 * }
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
class Test_Cancel_Level_Endpoint extends API_Endpoint {

	// Trait to handle performance tracking
	use PerformanceTrackingTrait;

	public function __construct() {}

	public function register_routes() {
		register_rest_route(
			$this->get_namespace(),
			'/test-cancel-level',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'permission_callback' => array( $this, 'handle_permissions' ), // inherits from Abstract_API_Endpoint (require authentication)
				'callback'            => array( $this, 'handle_request' ),
			)
		);
	}

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

		// Save the user's current membership for cleanup, if needed
		$original_membership = function_exists( 'pmpro_getMembershipLevelForUser' )
			? pmpro_getMembershipLevelForUser( $user_id )
			: null;

		// Prep test data
		$level_id = intval( $params['membership_level'] ?? 1 );
		$cleanup  = ! empty( $params['cleanup'] );

		// Cancel the membership level
		$cancelled = false;
		if ( function_exists( 'pmpro_cancelMembershipLevel' ) ) {
			$cancelled = pmpro_cancelMembershipLevel( $level_id, $user_id );
		}

		// Gather performance data
		$performance_data = $this->end_performance_tracking();

		// Optionally, restore original membership level if cleanup requested
		$restored = false;
		if ( $cleanup && $original_membership && function_exists( 'pmpro_changeMembershipLevel' ) ) {
			pmpro_changeMembershipLevel( $original_membership->ID, $user_id );
			$restored = true;
		}

		// Return profiling results
		return $this->json_success(
			array(
				'user_id'        => $user_id,
				'level_id'       => $level_id,
				'cancelled'      => $cancelled,
				'restored'       => $restored,
				'metrics'       => $performance_data,
			)
		);
	}
}
