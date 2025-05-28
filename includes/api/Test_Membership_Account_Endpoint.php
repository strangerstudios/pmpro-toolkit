<?php

namespace TK;

use WP_REST_Request;
use WP_REST_Server;
use WP_Error;

class Test_Membership_Account_Endpoint extends API_Endpoint {

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
		$start = microtime( true );
		global $wpdb;
		$wpdb->queries = array();

		$params = $request->get_json_params();

		// Get the current user ID from the logged in user
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return $this->json_error(
				'not_logged_in',
				'You must be logged in to view the account page.',
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

		// Gather performance data
		$end              = microtime( true );
		$total_query_time = 0;

		// If SAVEQUERIES is enabled, calculate total query time
		if ( defined( 'SAVEQUERIES' ) && SAVEQUERIES && isset( $wpdb->queries ) ) {
			foreach ( $wpdb->queries as $q ) {
				$total_query_time += $q[1];
			}
			$total_query_time = round( $total_query_time, 4 );
		}

		$query_count    = get_num_queries();
		$peak_memory_kb = round( memory_get_peak_usage( true ) / 1024 );

		return $this->json_success(
			array(
				'user_id'        => $user_id,
				'duration_sec'   => round( $end - $start, 4 ),
				'queries'        => $query_count,
				'db_time_sec'    => $total_query_time,
				'peak_memory_kb' => $peak_memory_kb,
			)
		);
	}
}
