<?php

namespace TK;

/**
 * PerformanceTrackingTrait
 *
 * This trait provides methods to track performance metrics such as execution time,
 * number of database queries, and peak memory usage.
 *
 * @package TK
 */
trait PerformanceTrackingTrait {
	protected $perf_start_time;
	protected $perf_initial_query_count = 0; // Initial query count before the block starts
	protected $perf_start_memory; // Added to track memory usage within the block
    protected $perf_initial_queries = 0; // Initial queries count when SAVEQUERIES is enabled

	/**
	 * Start performance tracking for a specific block of code.
	 *
	 * This method captures the start time, initial memory usage, and initial query count
	 * before executing the block of code. It also resets the query log if SAVEQUERIES is enabled.
	 */
	protected function start_performance_tracking() {
		global $wpdb;
		$this->perf_start_time          = microtime( true ); // Capture the start time of the block
		$this->perf_start_memory        = memory_get_usage(); // Capture memory at the start of the block
		$this->perf_initial_query_count = isset( $wpdb->num_queries ) ? $wpdb->num_queries : 0; // Initial query count before the block starts

		if ( defined( 'SAVEQUERIES' ) && SAVEQUERIES && isset( $wpdb->queries ) ) {
            $this->perf_initial_queries = $wpdb->queries;
		}
	}

	/**
	 * End performance tracking and return the collected metrics.
	 *
	 * This method captures the end time, final memory usage, and final query count
	 * after executing the block of code. It also calculates the duration and memory
	 * used specifically by this block.
	 */
	protected function end_performance_tracking() {
		global $wpdb;
		$end_time   = microtime( true );
		$end_memory = memory_get_usage();

		$duration_sec   = round( $end_time - $this->perf_start_time, 4 );
		$memory_used_kb = round( ( $end_memory - $this->perf_start_memory ) / 1024, 2 ); // Memory used by this block
		$peak_memory_kb = round( memory_get_peak_usage( true ) / 1024 ); // Peak memory for the entire script up to this point
		$db_time_sec    = 0;
		$db_time_ms     = null;

		// Calculate queries and their duration *within this block*
		$queries_in_block = 0;
		if ( defined( 'SAVEQUERIES' ) && SAVEQUERIES && isset( $wpdb->queries ) && is_array( $wpdb->queries ) && is_array( $this->perf_initial_queries ) ) {
		    $initial_count = count( $this->perf_initial_queries );
		    $new_queries   = array_slice( $wpdb->queries, $initial_count );

		    foreach ( $new_queries as $q ) {
		        if ( is_array( $q ) && isset( $q[1] ) ) {
		            $db_time_sec += $q[1]; // $q[1] is the query duration
		        }
		    }

		    $db_time_ms       = round( $db_time_sec * 1000, 2 );
		    $queries_in_block = count( $new_queries );
		} elseif ( isset( $wpdb->num_queries ) ) {
		    // If SAVEQUERIES is off, we can only count the number of queries by looking at the change in the total query counter.
		    // We won't have individual query times for the block.
		    $queries_in_block = $wpdb->num_queries - $this->perf_initial_query_count;
		}

		return array(
			'savequeries_on'   => defined( 'SAVEQUERIES' ) && SAVEQUERIES,
			'queries_in_block' => $queries_in_block,    // Queries executed within the tracked block
			'duration_sec'     => $duration_sec,        // Duration of the block in seconds
			'db_time_ms'       => $db_time_ms,          // Total time of queries in the block (if SAVEQUERIES)
			'block_memory_kb'  => $memory_used_kb,      // Memory specifically used by this block
			'peak_memory_kb'   => $peak_memory_kb,      // Peak memory for the entire script up to this point
		);
	}
}
