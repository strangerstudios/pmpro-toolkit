<?php

namespace TK;

use TK\API_Endpoint;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Test PMPro report CSV generation and return basic statistics via REST API.
 *
 * This endpoint simulates the generation of PMPro admin reports (e.g., Sales, Memberships)
 * and collects basic output metrics by requesting the backend CSV export page.
 *
 * Capabilities:
 * - Triggers backend report generation for PMPro admin reports.
 * - Supports all known filtering parameters used by PMPro report UI (e.g., period, type, level).
 * - Returns total rows and basic statistics from the CSV.
 * - Tracks performance metrics including execution time and memory usage.
 *
 * Request Parameters (POST):
 * - report (string, required): Report to generate. One of: 'sales', 'memberships', 'login', 'memberslist'.
 * - type (string, optional): Report sub-type or graph selection.
 * - period (string, optional): Time period, e.g., 'daily', 'monthly'.
 * - month (int|string, optional): Specific month filter.
 * - year (int|string, optional): Specific year filter.
 * - discount_code (string, optional): Filter by discount code.
 * - level (int|string, optional): Membership level ID or 'all'.
 * - startdate, enddate (string, optional): Date range filter (YYYY-MM-DD).
 * - custom_start_date, custom_end_date (string, optional): Alternative custom date range fields.
 * - show_parts (string, optional): Additional sales data breakdown (e.g., 'new_renewals').
 * - s (string, optional): Search query string (login report).
 * - l (string|int, optional): Level filter for login report ('all', 1, 2, etc).
 *
 * Example payload:
 * {
 *   "report": "sales",
 *   "type": "revenue",
 *   "period": "daily",
 *   "month": 5,
 *   "year": 2025,
 *   "custom_start_date": "2025-05-01",
 *   "custom_end_date": "2025-05-31",
 *   "show_parts": "new_renewals"
 * }
 *
 * Response:
 * On success:
 * {
 *   "status": "success",
 *   "report": "sales",
 *   "stats": { "row_count": 42 },
 *   "metrics": {
 *     "duration": 0.128,
 *     "queries": 12,
 *     "db_time_sec": 0.024,
 *     "peak_memory_kb": 6096
 *   }
 * }
 *
 * On error:
 * {
 *   "code": "csv_error",
 *   "message": "No CSV data returned.",
 *   "data": { "status": 500 }
 * }
 *
 * @since 1.0.0
 */
class Test_Reports_Endpoint extends API_Endpoint {
	// Trait to handle performance tracking
	use PerformanceTrackingTrait;

	/**
	 * Register REST API routes for this endpoint.
	 */
	public function register_routes() {
		register_rest_route(
			$this->get_namespace(),
			'/pmpro-report-test',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_request' ),
				'permission_callback' => array( $this, 'handle_permissions' ),
			)
		);
	}

	/**
	 * Handle the report test request.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function handle_request( WP_REST_Request $request ) {
		$report = sanitize_text_field( $request->get_param( 'report' ) );
		if ( empty( $report ) ) {
			return $this->json_error( 'empty_report', 'Report type is required.', 400 );
		}

		// Start performance tracking
		$this->start_performance_tracking();

		$stats = array();
		$error = null;

		// Map report to CSV logic
		$report_map = array(
			'sales'       => 'sales',
			'memberships' => 'memberships',
			'login'       => 'login',
			'memberslist' => 'memberslist',
		);

		if ( ! isset( $report_map[ $report ] ) ) {
			return $this->json_error( 'invalid_report', 'Invalid report type.', 400 );
		}

		// Generate the CSV file using the same logic as the admin export
		$tmp_file = $this->generate_report_csv( $report, $request );
		if ( is_wp_error( $tmp_file ) ) {
			return $this->json_error( 'csv_error', $tmp_file->get_error_message(), 500 );
		}

		// End performance tracking
		$performance_data = $this->end_performance_tracking();

		// Parse the CSV for statistics
		$stats = $this->parse_csv_stats( $tmp_file );

		// Delete the temp file
		@unlink( $tmp_file );

		// Prepare the response data
		$data = array(
			'report'  => $report,
			'stats'   => $stats,
			'metrics' => $performance_data,
		);

		return $this->json_success( $data );
	}

	/**
	 * Generate the report CSV file by requesting the admin report page.
	 *
	 * @param string          $type
	 * @param WP_REST_Request $request
	 * @return string|WP_Error Path to temp CSV file or WP_Error
	 */
	protected function generate_report_csv( $type, $request ) {
		$admin_url = admin_url( 'admin.php' );
		$params    = array(
			'page'   => 'pmpro-reports',
			'report' => $type,
		);
		// Pass through relevant params from the request
		foreach ( array( 'type', 'period', 'month', 'year', 'discount_code', 'startdate', 'enddate', 'custom_start_date', 'custom_end_date', 'level', 'show_parts', 's', 'l' ) as $param ) {
			$value = $request->get_param( $param );
			if ( ! is_null( $value ) ) {
				$params[ $param ] = $value;
			}
		}
		$url = add_query_arg( $params, $admin_url );

		// Fetch the CSV (cookies will be sent if user is logged in)
		$response = wp_remote_get(
			$url,
			array(
				'timeout'   => 60,
				'sslverify' => false,
			)
		);
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$csv = wp_remote_retrieve_body( $response );
		if ( empty( $csv ) ) {
			return new \WP_Error( 'empty_csv', 'No CSV data returned.' );
		}
		$tmp_file = tempnam( sys_get_temp_dir(), 'pmpro_reportcsv_' );
		file_put_contents( $tmp_file, $csv );
		return $tmp_file;
	}

	/**
	 * Parse the CSV file and return statistics (row count, totals, etc).
	 *
	 * @param string $csv_file
	 * @return array
	 */
	protected function parse_csv_stats( $csv_file ) {
		$rows = array();
		if ( ( $handle = fopen( $csv_file, 'r' ) ) !== false ) {
			$header = fgetcsv( $handle );
			while ( ( $data = fgetcsv( $handle ) ) !== false ) {
				$rows[] = $data;
			}
			fclose( $handle );
		}
		return array(
			'row_count' => count( $rows ),
			// Add more stats as needed
		);
	}
}
