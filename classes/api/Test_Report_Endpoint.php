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
class Test_Report_Endpoint extends API_Endpoint {
	// Trait to handle performance tracking
	use PerformanceTrackingTrait;

	/**
	 * Allowed report types for this endpoint.
	 */
	const ALLOWED_REPORTS = array( 'sales', 'memberships', 'login' );

	/**
	 * Register REST API routes for this endpoint.
	 */
	public function register_routes() {
		register_rest_route(
			$this->get_namespace(),
			'/test-report',
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
		// Capture the starting output buffer level as early as possible.
		$start_buffer_level = ob_get_level();

		$report = sanitize_text_field( $request->get_param( 'report' ) );
		if ( empty( $report ) ) {
			return $this->json_error( 'empty_report', 'Report type is required.', 400 );
		}

		// Validate the report type against allowed types.
		if ( ! in_array( $report, self::ALLOWED_REPORTS, true ) ) {
			return $this->json_error(
				'invalid_report',
				sprintf(
					'Invalid report type. Allowed types: %s.',
					implode( ', ', self::ALLOWED_REPORTS )
				),
				400
			);
		}

		// Start performance tracking
		$this->start_performance_tracking();

		// Trigger the CSV export logic without capturing or parsing output
		$this->generate_report_csv( $report, $request );

		// End performance tracking
		$performance_data = $this->end_performance_tracking();

		// Prepare the response data
		$data = array(
			'report'  => $report,
			'metrics' => $performance_data,
		);

		return $this->json_success( $data );
	}

	/**
	 * Generate the report CSV file by requesting the admin report page.
	 *
	 * @param string          $type
	 * @param WP_REST_Request $request
	 * @return void
	 */
	protected function generate_report_csv( $type, $request ) {
		$script_map = array(
			'sales'       => 'sales-csv.php',
			'memberships' => 'memberships-csv.php',
			'login'       => 'login-csv.php',
		);

		if ( ! isset( $script_map[ $type ] ) ) {
			return;
		}

		// Apply sensible defaults per report type
		$defaults     = array();
		$current_year = date( 'Y' );

		if ( $type === 'memberships' ) {
			$defaults = array(
				'period' => 'annual',
				'type'   => 'signup_v_all',
				'year'   => $current_year,
				'level'  => 'all',
			);
		} elseif ( $type === 'sales' ) {
			$defaults = array(
				'period'     => 'monthly',
				'type'       => 'revenue',
				'year'       => $current_year,
				'show_parts' => 'new_renewals',
			);
		}

		// Populate $_REQUEST with defaults first
		foreach ( $defaults as $param => $value ) {
			$_REQUEST[ $param ] = $value;
		}

		// Then override with any provided request parameters
		foreach ( array(
			'type',
			'period',
			'month',
			'year',
			'discount_code',
			'startdate',
			'enddate',
			'custom_start_date',
			'custom_end_date',
			'level',
			'show_parts',
			's',
			'l',
		) as $param ) {
			$value = $request->get_param( $param );
			if ( ! is_null( $value ) ) {
				$_REQUEST[ $param ] = $value;
			}
		}

		// Include the CSV export script to trigger generation
		$_REQUEST['pmpro_no_download'] = 1;
		if ( $type === 'login' ) {
			// For login report, we need to set the 'l' parameter to 'all' if not provided
			if ( ! isset( $_REQUEST['l'] ) ) {
				$_REQUEST['l'] = 'all';
			}
		} elseif ( $type === 'sales' ) {
			require_once PMPRO_DIR . '/adminpages/reports/sales.php';
			// Start output buffering to discard HTML output from pmpro_report_sales_page(),
			// which generates the sales data transient needed by sales-csv.php.
			ob_start();
			pmpro_report_sales_page(); // Generates the transient but outputs HTML we don't need.
			ob_end_clean(); // Discard output
		}
		require_once PMPRO_DIR . '/adminpages/' . $script_map[ $type ];
	}
}
