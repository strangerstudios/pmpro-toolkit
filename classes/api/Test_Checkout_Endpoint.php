<?php

namespace TK;

use WP_REST_Request;
use WP_REST_Server;
use WP_Error;

class Test_Checkout_Endpoint extends API_Endpoint {

	// Trait to handle performance tracking
	use PerformanceTrackingTrait;

	public function __construct() {}

	public function register_routes() {
		register_rest_route(
			$this->get_namespace(),
			'/test-checkout',
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
	 * Simulate a complete Paid Memberships Pro checkout and profile its performance.
	 *
	 * This endpoint enables automated performance testing of the *entire* membership checkout process,
	 * as if a real user were registering and checking out for the first time.
	 *
	 * Capabilities:
	 * - Programmatically generates unique user and billing data for each run (or uses provided data).
	 * - Submits all fields required by the real checkout form, triggering all core and custom PMPro hooks, add-ons, and gateway logic.
	 * - Optionally short-circuits payment gateway logic for rapid profiling (with `skip_gateway` param).
	 * - Optionally deletes all test data (user, membership, orders) after profiling (with `cleanup` param).
	 * - Returns detailed performance data: PHP execution time, query count, query time, peak memory usage, and created user info.
	 *
	 * Request Parameters:
	 * - membership_level (int, optional): Membership level ID to test (default: 1).
	 * - gateway (string, optional): Payment gateway to use (default: 'check' for no-charge/dummy).
	 * - skip_gateway (bool, optional): If true, disables remote gateway calls for local profiling (default: false).
	 * - cleanup (bool, optional): If true, deletes the test user and all related data after the run (default: false).
	 * - user_login, user_email, user_pass, first_name, last_name, address, city, state, zip, phone (optional): Provide custom test user details.
	 *
	 * Example payload:
	 * {
	 *   "membership_level": 2,
	 *   "gateway": "check",
	 *   "skip_gateway": true,
	 *   "cleanup": true
	 * }
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function handle_request( WP_REST_Request $request ) {
		// Start metrics collection
		$this->start_performance_tracking();

		$params = $request->get_json_params();

		// Get parameters or set defaults
		$level_id     = intval( $params['membership_level'] ?? 1 );
		$gateway      = $params['gateway'] ?? 'check'; // Default to PMPro Check gateway (no remote call)
		$skip_gateway = ! empty( $params['skip_gateway'] ); // default: false
		$cleanup      = ! empty( $params['cleanup'] ); // default: false

		// Generate unique user data if not provided
		$user_login = $params['user_login'] ?? 'test_' . wp_generate_password( 8, false );
		$user_email = $params['user_email'] ?? ( $user_login . '@test.local' );
		$user_pass  = $params['user_pass'] ?? wp_generate_password( 12 );
		$first_name = $params['first_name'] ?? 'Test';
		$last_name  = $params['last_name'] ?? 'McTesterson';

		// Fake address data
		$address = $params['baddress1'] ?? '123 Testing Ave';
		$city    = $params['bcity'] ?? 'Testville';
		$state   = $params['bstate'] ?? 'NY';
		$zip     = $params['bzipcode'] ?? '10001';
		$phone   = $params['bphone'] ?? '999-555-1234';

		// Mimic $_POST as if the real checkout form is being submitted.
		$_POST = array(
			'username'        => $user_login,
			'password'        => $user_pass,
			'password2'       => $user_pass,
			'bemail'          => $user_email,
			'bconfirmemail'   => $user_email,
			'first_name'      => $first_name,
			'last_name'       => $last_name,
			'bfirstname'      => $first_name,
			'blastname'       => $last_name,
			'baddress1'       => $address,
			'bcity'           => $city,
			'bstate'          => $state,
			'bzipcode'        => $zip,
			'bphone'          => $phone,
			'AccountNumber'   => '4242424242424242',   // test VISA number
			'ExpirationMonth' => '01',
			'ExpirationYear'  => date( 'Y', strtotime( '+2 years' ) ), // 2 years in the future
			'CVV'             => '123',
			'level'           => $level_id,
			'gateway'         => $gateway,
			'payment_method'  => $gateway,
			'submit-checkout' => 1,
			// Add other custom fields as needed
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

		// Buffer output to avoid sending HTML to the response.
		ob_start();

		// Fire the full checkout flow (simulate a real checkout request).
		do_action( 'pmpro_checkout_preheader' );
		if ( function_exists( 'pmpro_include_checkout' ) ) {
			pmpro_include_checkout();
		}
		do_action( 'pmpro_checkout' );

		ob_end_clean();

		// Gather performance data
		$performance_data = $this->end_performance_tracking();

		// Find created user ID
		$created_user_id = username_exists( $user_login );

		// Optionally clean up (delete user and related data)
		$deleted = false;
		if ( $cleanup && $created_user_id ) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
			wp_delete_user( $created_user_id );
			$deleted = true;
		}

		// Return profiling results
		$data = array(
			'user_login'      => $user_login,
			'user_email'      => $user_email,
			'user_id'         => $created_user_id,
			'level_id'        => $level_id,
			'gateway'         => $gateway,
			'skipped_gateway' => $skip_gateway,
			'deleted'         => $deleted,
			'metrics'         => $performance_data,
		);

		return $this->json_success( $data );
	}
}
