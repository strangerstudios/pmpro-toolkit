<?php

namespace TK;

use TK\API_Endpoint;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Test PMPro member export CSV generation and return basic statistics via REST API.
 *
 * This endpoint simulates the generation of PMPro member list exports
 * and collects basic output metrics by requesting the backend CSV export page.
 *
 * Capabilities:
 * - Triggers backend member export generation for PMPro member lists.
 * - Supports all known filtering parameters used by PMPro member list UI.
 * - Returns total rows and basic statistics from the CSV.
 * - Tracks performance metrics including execution time and memory usage.
 *
 * Request Parameters (POST):
 * - s (string, optional): Search query string. Supports wildcards (*) and colon-separated field searches.
 * - l (string|int, optional): Level filter. Values: 'all', level ID (1, 2, etc), 'oldmembers', 'expired', 'cancelled'.
 * - pn (int, optional): Page number for pagination.
 * - limit (int, optional): Limit number of records to export.
 *
 * Search Key Examples (when using colon in search):
 * - "login:john" - Search user login field
 * - "email:@example.com" - Search email field
 * - "discount:SAVE10" - Search by discount code
 * - "first_name:John" - Search user meta field
 *
 * Example payload:
 * {
 *   "s": "john*",
 *   "l": "1",
 *   "limit": 1000
 * }
 *
 * Response:
 * On success:
 * {
 *   "status": "success",
 *   "export_type": "members",
 *   "filters": {
 *     "search": "john*",
 *     "level": "1",
 *     "limit": 1000
 *   },
 *   "metrics": {
 *     "duration": 2.45,
 *     "queries": 24,
 *     "db_time_sec": 0.156,
 *     "peak_memory_kb": 12048
 *   }
 * }
 *
 * On error:
 * {
 *   "code": "export_error",
 *   "message": "Member export failed.",
 *   "data": { "status": 500 }
 * }
 *
 * @since 1.0.0
 */
class Test_Member_Export_Endpoint extends API_Endpoint {
    // Trait to handle performance tracking
    use PerformanceTrackingTrait;

    /**
     * Register REST API routes for this endpoint.
     */
    public function register_routes() {
        register_rest_route(
            $this->get_namespace(),
            '/test-member-export',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'handle_request' ),
                'permission_callback' => array( $this, 'handle_permissions' ),
            )
        );
    }

    /**
     * Handle the member export test request.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function handle_request( WP_REST_Request $request ) {

        // Start performance tracking
        $this->start_performance_tracking();

        // Generate the member export CSV
        $filters = $this->generate_member_export_csv( $request );

        // End performance tracking
        $performance_data = $this->end_performance_tracking();

        // Prepare the response data
        $data = array(
            'export_type' => 'members',
            'filters'     => $filters,
            'metrics'     => $performance_data,
        );

        return $this->json_success( $data );
    }

    /**
     * Generate the member export CSV by including the memberslist-csv.php file.
     *
     * @param WP_REST_Request $request
     * @return array Filters applied to the export
     */
    protected function generate_member_export_csv( $request ) {
        // Capture the filters being applied
        $filters = array();

        // Set up $_REQUEST parameters for the memberslist-csv.php script
        foreach ( array(
            's',      // Search query
            'l',      // Level filter
            'pn',     // Page number
            'limit',  // Limit
        ) as $param ) {
            $value = $request->get_param( $param );
            if ( ! is_null( $value ) ) {
                $_REQUEST[ $param ] = $value;
                $filters[ $param ] = $value;
            }
        }

        // Add some sensible defaults if not provided
        if ( ! isset( $_REQUEST['l'] ) ) {
            $_REQUEST['l'] = 'all';
            $filters['l'] = 'all';
        }

        // Prevent actual file download
        $_REQUEST['pmpro_no_download'] = 1;

        // Start output buffering to discard any HTML output from the export script.
        ob_start();
        require_once PMPRO_DIR . '/adminpages/memberslist-csv.php';
        ob_end_clean();

        return $filters;
    }
}