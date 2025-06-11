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
				'args'                => $this->get_endpoint_args(),
			)
		);
	}

	/**
	 * Get endpoint argument definitions for validation.
	 *
	 * @return array
	 */
	private function get_endpoint_args() {
		return array(
			'membership_level' => array(
				'description' => __( 'Membership level ID to test.', 'tk' ),
				'type'        => 'integer',
				'default'     => 1,
				'minimum'     => 1,
			),
			'gateway'          => array(
				'description' => __( 'Payment gateway to use.', 'tk' ),
				'type'        => 'string',
				'default'     => 'check',
				'enum'        => array( 'check', 'stripe', 'paypal', 'authorizenet' ),
			),
			'skip_gateway'     => array(
				'description' => __( 'Skip remote gateway calls for local profiling.', 'tk' ),
				'type'        => 'boolean',
				'default'     => false,
			),
			'cleanup'          => array(
				'description' => __( 'Delete test user and data after checkout.', 'tk' ),
				'type'        => 'boolean',
				'default'     => false,
			),
			'checkout_date'    => array(
				'description' => __( 'Custom checkout date for backdating (MySQL datetime format).', 'tk' ),
				'type'        => 'string',
				'format'      => 'date-time',
			),
			'user_login'       => array(
				'description' => __( 'Custom username for test user.', 'tk' ),
				'type'        => 'string',
			),
			'user_email'       => array(
				'description' => __( 'Custom email for test user.', 'tk' ),
				'type'        => 'string',
				'format'      => 'email',
			),
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
	 * - Supports backdating checkouts for testing historical data (with `checkout_date` param).
	 * - Optionally deletes all test data (user, membership, orders) after profiling (with `cleanup` param).
	 * - Returns detailed performance data: PHP execution time, query count, query time, peak memory usage, and created user info.
	 *
	 * Request Parameters:
	 * - membership_level (int, optional): Membership level ID to test (default: 1).
	 * - gateway (string, optional): Payment gateway to use (default: 'check' for no-charge/dummy).
	 * - skip_gateway (bool, optional): If true, disables remote gateway calls for local profiling (default: false).
	 * - cleanup (bool, optional): If true, deletes the test user and all related data after the run (default: false).
	 * - checkout_date (string, optional): Custom checkout date in MySQL datetime format for backdating.
	 * - user_login, user_email, user_pass, first_name, last_name, baddress1, bcity, bstate, bzipcode, bphone (optional): Provide custom test user details.
	 *
	 * Example payload:
	 * {
	 *   "membership_level": 2,
	 *   "gateway": "check",
	 *   "skip_gateway": true,
	 *   "cleanup": false,
	 *   "checkout_date": "2024-12-01 14:30:00"
	 * }
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_request( WP_REST_Request $request ) {
		// Start metrics collection
		$this->start_performance_tracking();

		$params = $request->get_json_params();

		// Validate and sanitize inputs
		$level_id      = absint( $params['membership_level'] ?? 1 );
		$gateway       = sanitize_text_field( $params['gateway'] ?? 'check' );
		$skip_gateway  = ! empty( $params['skip_gateway'] );
		$cleanup       = ! empty( $params['cleanup'] );
		$checkout_date = ! empty( $params['checkout_date'] ) ?
			sanitize_text_field( $params['checkout_date'] ) :
			current_time( 'mysql' );

		// Validate membership level exists
		if ( ! pmpro_getLevel( $level_id ) ) {
			return $this->json_error(
				'invalid_level',
				sprintf( __( 'Membership level ID %d does not exist.', 'tk' ), $level_id ),
				400
			);
		}

		// Validate checkout date format
		if ( ! $this->is_valid_mysql_datetime( $checkout_date ) ) {
			return $this->json_error(
				'invalid_date',
				__( 'checkout_date must be in MySQL datetime format (YYYY-MM-DD HH:MM:SS).', 'tk' ),
				400
			);
		}

		// Generate or validate user data
		$user_data = $this->prepare_user_data( $params );
		if ( is_wp_error( $user_data ) ) {
			return $user_data;
		}

		// Check if user already exists (for existing user testing)
		$existing_user_id = username_exists( $user_data['user_login'] );
		if ( $existing_user_id ) {
			// Check if user already has this membership level
			if ( pmpro_hasMembershipLevel( $level_id, $existing_user_id ) ) {
				return $this->json_error(
					'user_has_membership',
					sprintf(
						__( 'User %1$s already has membership level %2$d.', 'tk' ),
						$user_data['user_login'],
						$level_id
					),
					409
				);
			}
		}

		// Set up checkout simulation
		$this->setup_checkout_simulation( $user_data, $level_id, $gateway, $skip_gateway, $checkout_date );

		// Buffer output to avoid sending HTML to the response
		ob_start();

		// Fire the full checkout flow (simulate a real checkout request)
		do_action( 'pmpro_checkout_preheader' );

		if ( function_exists( 'pmpro_include_checkout' ) ) {
			pmpro_include_checkout();
		}

		do_action( 'pmpro_checkout' );

		ob_end_clean();

		// Gather performance data
		$performance_data = $this->end_performance_tracking();

		// Find created/updated user ID
		$user_id = username_exists( $user_data['user_login'] );

		// Apply custom checkout date if specified and different from current time
		if ( $user_id && $checkout_date !== current_time( 'mysql' ) ) {
			$this->apply_custom_checkout_date( $user_id, $level_id, $checkout_date );
		}

		// Optionally clean up (delete user and related data)
		$deleted = false;
		if ( $cleanup && $user_id && ! $existing_user_id ) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
			wp_delete_user( $user_id );
			$deleted = true;
		}

		// Return profiling results
		$data = array(
			'user_login'      => $user_data['user_login'],
			'user_email'      => $user_data['user_email'],
			'user_id'         => $user_id,
			'level_id'        => $level_id,
			'gateway'         => $gateway,
			'skipped_gateway' => $skip_gateway,
			'checkout_date'   => $checkout_date,
			'deleted'         => $deleted,
			'existing_user'   => ! empty( $existing_user_id ),
			'metrics'         => $performance_data,
		);

		return $this->json_success( $data );
	}

	/**
	 * Prepare and validate user data for checkout.
	 *
	 * @param array $params Request parameters.
	 * @return array|WP_Error User data array or error.
	 */
	private function prepare_user_data( $params ) {
		// Check if user data is provided
		$has_user_data = ! empty( $params['user_login'] ) && ! empty( $params['user_email'] );

		if ( $has_user_data ) {
			// Use provided data
			$username   = sanitize_user( $params['user_login'] );
			$user_email = sanitize_email( $params['user_email'] );
			$first_name = sanitize_text_field( $params['first_name'] ?? 'Test' );
			$last_name  = sanitize_text_field( $params['last_name'] ?? 'User' );
			$address    = sanitize_text_field( $params['baddress1'] ?? '123 Test St' );
			$city       = sanitize_text_field( $params['bcity'] ?? 'Testville' );
			$state      = sanitize_text_field( $params['bstate'] ?? 'NY' );
			$zip        = sanitize_text_field( $params['bzipcode'] ?? '10001' );
			$phone      = sanitize_text_field( $params['bphone'] ?? '555-123-4567' );
		} else {
			// Generate data from random user API
			$random_user = $this->get_random_user_data();

			if ( ! $random_user ) {
				return $this->json_error(
					'random_user_failed',
					__( 'Could not generate test user data. Random user API is unavailable.', 'tk' ),
					503
				);
			}

			// Use random API data
			$username   = strtolower( $random_user['name']['first'] . '.' . $random_user['name']['last'] );
			$user_email = $username . '+' . uniqid() . '@example.com';
			$first_name = $random_user['name']['first'];
			$last_name  = $random_user['name']['last'];
			$address    = $random_user['location']['street']['number'] . ' ' . $random_user['location']['street']['name'];
			$city       = $random_user['location']['city'];
			$state      = $random_user['location']['state'];
			$zip        = $random_user['location']['postcode'];
			$phone      = $random_user['phone'];
		}

		// Validate required fields
		if ( empty( $username ) || empty( $user_email ) ) {
			return $this->json_error(
				'invalid_user_data',
				__( 'Valid user_login and user_email are required.', 'tk' ),
				400
			);
		}

		if ( ! is_email( $user_email ) ) {
			return $this->json_error(
				'invalid_email',
				__( 'user_email must be a valid email address.', 'tk' ),
				400
			);
		}

		return array(
			'user_login' => $username,
			'user_email' => $user_email,
			'user_pass'  => $params['user_pass'] ?? wp_generate_password( 12 ),
			'first_name' => $first_name,
			'last_name'  => $last_name,
			'address'    => $address,
			'city'       => $city,
			'state'      => $state,
			'zip'        => $zip,
			'phone'      => $phone,
		);
	}

	/**
	 * Set up the checkout simulation by populating $_POST data.
	 *
	 * @param array  $user_data User data array.
	 * @param int    $level_id Membership level ID.
	 * @param string $gateway Payment gateway.
	 * @param bool   $skip_gateway Whether to skip gateway processing.
	 * @param string $checkout_date Checkout date.
	 */
	private function setup_checkout_simulation( $user_data, $level_id, $gateway, $skip_gateway, $checkout_date ) {
		// Mimic $_POST as if the real checkout form is being submitted
		$_POST = array(
			'username'        => $user_data['user_login'],
			'password'        => $user_data['user_pass'],
			'password2'       => $user_data['user_pass'],
			'bemail'          => $user_data['user_email'],
			'bconfirmemail'   => $user_data['user_email'],
			'first_name'      => $user_data['first_name'],
			'last_name'       => $user_data['last_name'],
			'bfirstname'      => $user_data['first_name'],
			'blastname'       => $user_data['last_name'],
			'baddress1'       => $user_data['address'],
			'bcity'           => $user_data['city'],
			'bstate'          => $user_data['state'],
			'bzipcode'        => $user_data['zip'],
			'bphone'          => $user_data['phone'],
			'AccountNumber'   => '4242424242424242',   // Test Visa number
			'ExpirationMonth' => '01',
			'ExpirationYear'  => date( 'Y', strtotime( '+2 years' ) ),
			'CVV'             => '123',
			'level'           => $level_id,
			'gateway'         => $gateway,
			'payment_method'  => $gateway,
			'submit-checkout' => 1,
		);

		// Optionally short-circuit the gateway for performance testing
		if ( $skip_gateway ) {
			add_filter( 'pmpro_checkout_new_gateway_instance', array( $this, 'mock_gateway_charge' ), 10, 2 );
		}
	}

	/**
	 * Mock gateway charge method for testing.
	 *
	 * @param object $gateway_obj Gateway instance.
	 * @param string $gateway Gateway name.
	 * @return object Modified gateway instance.
	 */
	public function mock_gateway_charge( $gateway_obj, $gateway ) {
		if ( method_exists( $gateway_obj, 'charge' ) ) {
			$gateway_obj->charge = function () {
				return true;
			};
		}
		return $gateway_obj;
	}

	/**
	 * Apply custom checkout date to membership and subscription records.
	 *
	 * @param int    $user_id User ID.
	 * @param int    $level_id Membership level ID.
	 * @param string $checkout_date Checkout date.
	 */
	private function apply_custom_checkout_date( $user_id, $level_id, $checkout_date ) {
		global $wpdb;

		// Update membership start date
		$wpdb->update(
			$wpdb->pmpro_memberships_users,
			array(
				'startdate' => $checkout_date,
				'modified'  => $checkout_date,
			),
			array(
				'user_id'       => $user_id,
				'membership_id' => $level_id,
				'status'        => 'active',
			),
			array( '%s', '%s' ),
			array( '%d', '%d', '%s' )
		);

		// Update subscription start date if exists
		$wpdb->update(
			$wpdb->pmpro_subscriptions,
			array(
				'startdate'         => $checkout_date,
				'next_payment_date' => date( 'Y-m-d H:i:s', strtotime( '+1 month', strtotime( $checkout_date ) ) ),
				'modified'          => $checkout_date,
			),
			array(
				'user_id'             => $user_id,
				'membership_level_id' => $level_id,
				'status'              => 'active',
			),
			array( '%s', '%s', '%s' ),
			array( '%d', '%d', '%s' )
		);
	}

	/**
	 * Validate MySQL datetime format.
	 *
	 * @param string $datetime Datetime string to validate.
	 * @return bool
	 */
	private function is_valid_mysql_datetime( $datetime ) {
		$format = 'Y-m-d H:i:s';
		$dt     = DateTime::createFromFormat( $format, $datetime );
		return $dt && $dt->format( $format ) === $datetime;
	}

	/**
	 * Helper: Fetch random user data from randomuser.me.
	 *
	 * @return array|false
	 */
	protected function get_random_user_data() {
		$response = wp_remote_get( 'https://randomuser.me/api/?nat=us', array( 'timeout' => 10 ) );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( empty( $data['results'][0] ) ) {
			return false;
		}

		return $data['results'][0];
	}
}
