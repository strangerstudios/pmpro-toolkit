<?php
/**
 * API Loader class.
 *
 * @package PMPro_Toolkit
 */

namespace PMPro_Toolkit;

use PMPro_Toolkit\Test_General_Endpoint;
use PMPro_Toolkit\Test_Login_Endpoint;
use PMPro_Toolkit\Test_Checkout_Endpoint;
use PMPro_Toolkit\Test_Change_Level_Endpoint;
use PMPro_Toolkit\Test_Membership_Account_Endpoint;
use PMPro_Toolkit\Test_Cancel_Level_Endpoint;
use PMPro_Toolkit\Test_Search_Endpoint;
use PMPro_Toolkit\Test_Report_Endpoint;
use PMPro_Toolkit\Test_Member_Export_Endpoint;

class API_Loader {
	/**
	 * Array of registered API endpoints.
	 *
	 * @var array
	 */
	protected $endpoints = array();

	/**
	 * Constructor.
	 *
	 * Initializes the API endpoints based on the configuration options.
	 * If performance endpoints are enabled, it registers the routes for each endpoint.
	 */
	public function __construct() {
		global $pmprodev_options;

		// Only add performance testing endpoints if explicitly enabled
		if ( ! empty( $pmprodev_options['performance_endpoints'] ) && $pmprodev_options['performance_endpoints'] !== 'no' ) {

			$this->endpoints = array(
				new Test_General_Endpoint(),
				new Test_Login_Endpoint(),
				new Test_Checkout_Endpoint(),
				new Test_Change_Level_Endpoint(),
				new Test_Account_Endpoint(),
				new Test_Cancel_Level_Endpoint(),
				new Test_Search_Endpoint(),
				new Test_Report_Endpoint(),
				new Test_Member_Export_Endpoint(),
			);

			// Register the API routes for all endpoints.
			add_action( 'rest_api_init', array( $this, 'register_routes' ) );
			// Enable Basic Auth for REST API endpoints.
			add_filter( 'determine_current_user', array( $this, 'basic_auth_user' ), 20 );
			// Allow fallback to regular user password if App Password fails.
			add_filter( 'rest_authentication_errors', array( $this, 'hybrid_basic_auth' ), 200 );
		}
	}

	/**
	 * Kickoff the registration of all API routes used in child classes.
	 *
	 * @return void
	 */
	public function register_routes() {
		foreach ( $this->endpoints as $endpoint ) {
			if ( method_exists( $endpoint, 'register_routes' ) ) {
				$endpoint->register_routes();
			}
		}
	}

	/**
	 * Attempt Basic Auth for REST API endpoints.
	 *
	 * If Basic Auth headers are present, attempt to authenticate and set the current user.
	 *
	 * @param mixed $user The current user ID (or value from previous filters).
	 * @return int|mixed User ID if authenticated, original value otherwise.
	 */
	public function basic_auth_user( $user ) {
		if ( ! empty( $user ) ) {
			return $user;
		}
		// Only run on REST API requests.
		if ( ! defined( 'REST_REQUEST' ) || ! REST_REQUEST ) {
			return $user;
		}
		// Only attempt if PHP_AUTH_USER and PHP_AUTH_PW are set.
		if ( isset( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] ) ) {
			$user_obj = wp_authenticate( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] );
			if ( ! is_wp_error( $user_obj ) ) {
				return $user_obj->ID;
			}
		}
		return $user;
	}

	/**
	 * Enforce authentication for all API endpoints.
	 *
	 * If no user is logged in, return a 401 error. Otherwise, allow.
	 *
	 * @param mixed $result The result of previous authentication checks.
	 * @return bool|WP_Error True if authenticated, WP_Error otherwise.
	 */
	public function enable_basic_auth( $result ) {
		if ( ! empty( $result ) ) {
			return $result;
		}
		if ( ! is_user_logged_in() ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'Authentication required.', 'pmpro-toolkit' ),
				array( 'status' => 401 )
			);
		}
		return $result;
	}


	/**
	 * Fallback authentication if Application Passwords fail, allow regular WP user password via Basic Auth.
	 *
	 * This allows both Application Passwords and WP user passwords for REST API Basic Auth.
	 *
	 * @param mixed $result Result from previous authentication handlers.
	 * @return bool|WP_Error True if authenticated, WP_Error if not.
	 */
	public function hybrid_basic_auth( $result ) {
		// Only override invalid application password errors.
		if ( is_wp_error( $result ) && $result->get_error_code() === 'incorrect_password' ) {
			if (
				isset( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] )
				&& username_exists( $_SERVER['PHP_AUTH_USER'] )
			) {
				$user = wp_authenticate( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] );
				if ( ! is_wp_error( $user ) ) {
					wp_set_current_user( $user->ID );
					return true;
				}
			}
		}
		return $result;
	}
}
