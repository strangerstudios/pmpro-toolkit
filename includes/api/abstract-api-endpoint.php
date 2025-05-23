<?php

// Toolkit
namespace TK;

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
	 * Register REST routes for this endpoint
	 */
	abstract public function register_routes();

	/**
	 * Get the current API namespace
	 *
	 * @return string
	 */
	public function get_namespace() {
		return self::$namespace;
	}

	/**
	 * Standard JSON success response
	 *
	 * @param array $data
	 * @param int   $status HTTP status code
	 * @return \WP_REST_Response
	 */
	public function json_success( $data = array(), $status = 200 ) {
		return rest_ensure_response( array_merge( array( 'success' => true ), $data ), $status );
	}

	/**
	 * Standard JSON error response
	 *
	 * @param string $message
	 * @param int    $status HTTP status code
	 * @param string $code WP_Error code
	 * @return \WP_Error
	 */
	public function json_error( $message = 'An error occurred.', $status = 500, $code = 'api_error' ) {
		return new \WP_Error( $code, $message, array( 'status' => $status ) );
	}
}
