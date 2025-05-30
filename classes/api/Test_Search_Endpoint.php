<?php

// Toolkit
namespace TK;

use TK\API_Endpoint;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Test endpoint for frontend search performance.
 *
 * @version 1.0.0
 * @since 1.0.0
 */
class Test_Search_Endpoint extends API_Endpoint {

	// Trait to handle performance tracking
	use PerformanceTrackingTrait;

	/**
	 * Register REST API routes for this endpoint.
	 */
	public function register_routes() {
		register_rest_route(
			$this->get_namespace(),
			'/test-search',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_request' ),
				'permission_callback' => array( $this, 'handle_permissions' ),
			)
		);
	}

	/**
	 * Simulate a frontend search operation and profile performance for WordPress or members.
	 *
	 * This endpoint allows toolkit users to programmatically test and profile the performance of
	 * WordPress’s standard search (posts, pages, custom post types) or optionally search members (users).
	 * Useful for comparing search performance under different configurations and loads.
	 *
	 * Capabilities:
	 * - Runs a standard WordPress post search using WP_Query, as performed by the site frontend.
	 * - Optionally, runs a member search using WP_User_Query for searching users by login, email, or display name.
	 * - Returns detailed profiling data: PHP execution time, DB queries, DB time, peak memory usage.
	 *
	 * Request Parameters:
	 * - query (string, required): The search term to test.
	 * - type (string, optional): 'post' (default) for posts/pages/CPTs, or 'member' for users/members.
	 *
	 * Example payload:
	 * {
	 *   "query": "john",
	 *   "type": "member"
	 * }
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function handle_request( WP_REST_Request $request ) {
		$search_query = sanitize_text_field( $request->get_param( 'query' ) );
		$type         = sanitize_text_field( $request->get_param( 'type' ) );

		if ( empty( $search_query ) ) {
			return $this->json_error( 'empty_query', 'Search query is required.', 400 );
		}

		// Start performance tracking
		$this->start_performance_tracking();

		$results = array();
		$count   = 0;

		if ( $type === 'member' ) {
			// Run member search (e.g., WP_User_Query or PMPro member search)
			$user_query = new \WP_User_Query(
				array(
					'search'         => '*' . esc_attr( $search_query ) . '*',
					'search_columns' => array( 'user_login', 'user_email', 'display_name' ),
					'number'         => 10,
					'fields'         => 'ID',
				)
			);
			$results    = $user_query->get_results();
			$count      = count( $results );
		} else {
			// Run post search (default)
			$post_query = new \WP_Query(
				array(
					's'              => $search_query,
					'post_type'      => 'any',
					'posts_per_page' => 10,
					'fields'         => 'ids',
				)
			);
			$results    = $post_query->posts;
			$count      = count( $results );
		}

		// End performance tracking
		$performance_data = $this->end_performance_tracking();

		// Prepare the response data
		$data = array(
			'query'          => $search_query,
			'type'           => $type ? $type : 'post',
			'results'        => $results,
			'count'          => $count,
			'metrics'        => $performance_data,
		);

		return $this->json_success( $data );
	}
}
