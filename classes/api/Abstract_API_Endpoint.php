<?php

// Toolkit
namespace PMPro_Toolkit;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Abstract class for registering API endpoints
 *
 * @version 1.0.0
 * @since 1.0.0
 */
abstract class API_Endpoint {
	/**
	 * Shared REST API namespace
	 *
	 * @var string
	 */
	public static $namespace = 'toolkit/v1';

	/**
	 * All child classes must implement a method to register REST routes for this endpoint.
	 */
	abstract public function register_routes();

	/**
	 * All child classes must implement a main method to handle a request.
	 *
	 * @param WP_REST_Request $request
	 * @return void
	 */
	abstract public function handle_request( WP_REST_Request $request );

	/**
	 * Get the current API namespace
	 *
	 * @return string
	 */
	public function get_namespace() {
		return self::$namespace;
	}

	/**
	 * Default permission callback for endpoints.
	 * Requires authentication by default.
	 *
	 * @return bool
	 */
	public function handle_permissions() {
		return is_user_logged_in();
	}



	/**
	 * Standard JSON success response
	 *
	 * @param array $data
	 * @param int   $status HTTP status code
	 * @return WP_REST_Response
	 */
	public function json_success( $data = array(), $status = 200 ) {
		$response = rest_ensure_response( array_merge( array( 'success' => true ), $data ) );
		$response->set_status( $status );
		return $response;
	}

	/**
	 * Standard JSON error response
	 *
	 * @param string $code WP_Error code
	 * @param string $message
	 * @param int    $status HTTP status code
	 * @return WP_Error
	 */
	public function json_error( $code = 'api_error', $message = 'An error occurred.', $status = 500 ) {
		return new WP_Error( $code, $message, array( 'status' => $status ) );
	}

	/**
	 * Check if a specific setting is enabled using global $pmprodev_options;
	 *
	 * @param string $setting The setting key to check.
	 * @return string|bool Returns the setting value if it exists, or false if not set.
	 */
	public function check_setting( $setting ) {
		global $pmprodev_options;
		return isset( $pmprodev_options[ $setting ] ) ? $pmprodev_options[ $setting ] : false;
	}

	/**
	 * Check if we should throttle requests based on IP address for unauthenticated requests.
	 *
	 * This method checks if IP throttling is enabled and applies rate limiting
	 * to unauthenticated requests based on the user's IP address.
	 *
	 * @return true|WP_Error Returns true if allowed, or a WP_Error if rate limit exceeded.
	 */
	public function throttle_if_unauthenticated() {
		// Check if IP throttling is enabled
		if ( $this->check_setting( 'ip_throttling' ) ) {
			$ip    = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
			$key   = 'tk_test_checkout_rate_' . md5( $ip );
			$count = (int) get_transient( $key );

			if ( $count >= 5 ) {
				return new WP_Error(
					'rate_limited',
					'Too many access attempts. Please wait awhile before retrying.',
					array( 'status' => 429 )
				);
			}

			set_transient( $key, $count + 1, MINUTE_IN_SECONDS / 2 );
		}

		return true;
	}

	/**
	 * Check to see if the request's method is allowed based on the performance_endpoints setting.
	 * This is an extra guard rail to prevent people from using "POST" methods when set to "read_only".
	 *
	 * @param string $method The HTTP method of the request (e.g., 'GET', 'POST', etc.).
	 * @return boolean $allowed True if the method is allowed, false if not.
	 */	
	public function is_request_allowed( $method ) {
		$allowed_methods = $this->check_setting( 'performance_endpoints' );
		$allowed = false;

		// If method is DELETE, UPDATE or PATCH let's set it to false.
		if ( in_array( $method, array( 'DELETE', 'PUT', 'PATCH' ), true ) ) {
			$allowed = false;
		} elseif ( ! empty( $allowed_methods ) && $allowed_methods === 'read_write' ) {
			$allowed = true;
		} elseif ( ! empty( $allowed_methods ) && $allowed_methods === 'read_only' && $method === 'GET' ) {
			$allowed = true;
		}

		/**
		 * Filter to allow customization of whether a request method is allowed for an endpoint.
		 * This filter passes the current allowed status, the request method, and the allowed methods setting.
		 * 
		 * @param bool  $allowed         Whether the request method is allowed.
		 * @param string $method          The HTTP method of the request.
		 * @param string $allowed_methods The allowed methods setting ('read_only', 'read_write', 'no').
		 */
		return apply_filters( 'pmpro_toolkit_is_request_allowed', $allowed, $method, $allowed_methods );
	}
}
