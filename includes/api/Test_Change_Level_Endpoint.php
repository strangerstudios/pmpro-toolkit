<?php

namespace TK;

use WP_REST_Request;
use WP_REST_Server;
use WP_Error;

class Test_Change_Level_Endpoint extends API_Endpoint {

    public function __construct() {}

    public function register_routes() {
        register_rest_route(
            $this->get_namespace(),
            '/test-change-level',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'permission_callback' => array( $this, 'handle_permissions' ),
                'callback'            => array( $this, 'handle_request' ),
            )
        );
    }

    public function handle_permissions() {
        // Allow unauthenticated, but rate limit by IP
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = 'tk_test_changelevel_rate_' . md5( $ip );
        $count = (int) get_transient( $key );

        if ( $count >= 5 ) {
            return new WP_Error(
                'rate_limited',
                'Too many access attempts. Please wait awhile before retrying.',
                array( 'status' => 429 )
            );
        }

        set_transient( $key, $count + 1, MINUTE_IN_SECONDS / 2 );
        return true;
    }

    public function handle_request( WP_REST_Request $request ) {
        $start = microtime( true );
        global $wpdb;
        $wpdb->queries = array();

        $params = $request->get_json_params();
        $user_id = username_exists( $params['user_login'] );
        $level_id = intval( $params['membership_level'] );

        $result = function_exists( 'pmpro_changeMembershipLevel' )
            ? pmpro_changeMembershipLevel( $level_id, $user_id )
            : false;

        $end = microtime( true );
        $total_query_time = 0;
        if ( defined( 'SAVEQUERIES' ) && SAVEQUERIES && isset( $wpdb->queries ) ) {
            foreach ( $wpdb->queries as $q ) {
                $total_query_time += $q[1];
            }
            $total_query_time = round( $total_query_time, 4 );
        }

        $query_count = get_num_queries();
        $peak_memory_kb = round( memory_get_peak_usage( true ) / 1024 );

        $data = array(
            'user_id'        => $user_id,
            'level_id'       => $level_id,
            'success'        => $result,
            'duration_sec'   => round( $end - $start, 4 ),
            'queries'        => $query_count,
            'db_time_sec'    => $total_query_time,
            'peak_memory_kb' => $peak_memory_kb ? $peak_memory_kb : 0,
        );

        return $this->json_success( $data );
    }
}