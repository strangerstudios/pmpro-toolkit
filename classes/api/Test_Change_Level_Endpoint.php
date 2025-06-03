<?php

namespace TK;

use WP_REST_Request;
use WP_REST_Server;
use WP_Error;

/**
 * Simulate a Paid Memberships Pro membership level change for a logged-in user and profile performance.
 *
 * This endpoint allows toolkit users to programmatically test and profile the process of changing a membership
 * level for an existing user (as if that user were logged in and visiting the checkout page).
 *
 * Capabilities:
 * - Switches the current user context to the specified user (by login or email).
 * - Submits a real PMPro checkout as a logged-in user, including all hooks, gateway routines, and add-ons.
 * - Optionally short-circuits the gateway for fast, local profiling (avoiding remote gateway calls).
 * - Can restore the user’s original membership level after profiling for a clean test (optional).
 * - Returns detailed performance data: PHP time, DB queries, DB time, peak memory usage.
 *
 * Request Parameters:
 * - user_login (string, required): The username or email address of the user to test as.
 * - membership_level (int, required): The membership level ID to change to.
 * - gateway (string, optional): Gateway to use for checkout (default: 'check' for test/no-charge).
 * - skip_gateway (bool, optional): If true, disables remote gateway calls for faster profiling (default: false).
 * - cleanup (bool, optional): If true, restores the user’s original membership level after test (default: false).
 *
 * Example payload:
 * {
 *   "user_login": "testing",
 *   "membership_level": 2,
 *   "gateway": "check",
 *   "skip_gateway": true,
 *   "cleanup": true
 * }
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
class Test_Change_Level_Endpoint extends API_Endpoint {

	// Trait to handle performance tracking
	use PerformanceTrackingTrait;

	public function __construct() {}

	public function register_routes() {
		register_rest_route(
			$this->get_namespace(),
			'/test-change-level',
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

	public function handle_request( WP_REST_Request $request ) {
		$this->start_performance_tracking();

		$params = $request->get_json_params();

		// Identify user by login or email
		$user_login = $params['user_login'] ?? null;
		$user       = false;
		if ( $user_login ) {
			$user = get_user_by( 'login', $user_login );
			if ( ! $user && is_email( $user_login ) ) {
				$user = get_user_by( 'email', $user_login );
			}
		}
		if ( ! $user ) {
			return $this->json_error(
				'user_not_found',
				'User does not exist.',
				404
			);
		}

		// Save the user's current membership for cleanup, if needed
		$original_membership = function_exists( 'pmpro_getMembershipLevelForUser' )
			? pmpro_getMembershipLevelForUser( $user->ID )
			: null;

		// "Log in" as that user for the duration
		wp_set_current_user( $user->ID );

		// Prep test data
		$level_id     = intval( $params['membership_level'] ?? 1 );
		$gateway      = $params['gateway'] ?? 'check';
		$skip_gateway = ! empty( $params['skip_gateway'] );
		$cleanup      = ! empty( $params['cleanup'] );

		// Fill $_POST as if the logged-in checkout form is submitted
		$_POST = array(
			'level'           => $level_id,
			'gateway'         => $gateway,
			'payment_method'  => $gateway,
			'AccountNumber'   => '4242424242424242',
			'ExpirationMonth' => '01',
			'ExpirationYear'  => '2028',
			'CVV'             => '123',
			'submit-checkout' => 1,
			// Add billing fields if required by your site
		);

		// Optionally short-circuit the gateway for performance test
		if ( $skip_gateway ) {
			add_filter(
				'pmpro_checkout_new_gateway_instance',
				function ( $gateway_obj, $gateway ) {
					if ( method_exists( $gateway_obj, 'charge' ) ) {
						$gateway_obj->charge = function () {
							return true;
						};
					}
					return $gateway_obj;
				},
				10,
				2
			);
		}

		ob_start();
		do_action( 'pmpro_checkout_preheader' );
		if ( function_exists( 'pmpro_include_checkout' ) ) {
			pmpro_include_checkout();
		}
		do_action( 'pmpro_checkout' );
		ob_end_clean();

		// Gather performance data
		$performance_data = $this->end_performance_tracking();

		// Optionally, restore original membership level if cleanup requested
		$restored = false;
		if ( $cleanup && $original_membership && function_exists( 'pmpro_changeMembershipLevel' ) ) {
			pmpro_changeMembershipLevel( $original_membership->ID, $user->ID );
			$restored = true;
		}

		// Return profiling results
		$data = array(
			'user_id'         => $user->ID,
			'user_login'      => $user->user_login,
			'level_id'        => $level_id,
			'gateway'         => $gateway,
			'skipped_gateway' => $skip_gateway,
			'restored'        => $restored,
			'metrics'         => $performance_data,
		);

		return $this->json_success( $data );
	}
}
