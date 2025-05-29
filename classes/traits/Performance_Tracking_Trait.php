<?php
/**
 * PerformanceTrackingTrait
 *
 * This trait provides methods to track performance metrics such as execution time,
 * number of database queries, and peak memory usage within a specific block of code.
 *
 * @package TK
 */
namespace TK;

trait PerformanceTrackingTrait {
	protected $perf_start_time;
	protected $perf_initial_query_count;

	protected function start_performance_tracking() {
		global $wpdb;
		$this->perf_start_time = microtime( true );
		// Store the number of queries run *before* this block
		$this->perf_initial_query_count = $wpdb->num_queries;

		if ( defined( 'SAVEQUERIES' ) && SAVEQUERIES ) {
			$wpdb->queries = array(); // Reset the query log for this specific block
		}
	}

	protected function end_performance_tracking() {
		global $wpdb;
		$end_time = microtime( true );

		$duration_sec   = round( $end_time - $this->perf_start_time, 4 );
		$peak_memory_kb = round( memory_get_peak_usage( true ) / 1024 );
		$db_time_sec    = 0;

		// Calculate queries and their duration *within this block*
		$queries_in_block = 0;
		if ( defined( 'SAVEQUERIES' ) && SAVEQUERIES && isset( $wpdb->queries ) ) {
			// If SAVEQUERIES is on, $wpdb->queries contains queries executed
			// since it was last reset. This is ideal for block-specific metrics.
			foreach ( $wpdb->queries as $q ) {
				$db_time_sec += $q[1]; // $q[1] is the query duration
			}
			$db_time_sec      = round( $db_time_sec, 4 );
			$queries_in_block = count( $wpdb->queries );
		} else {
			// If SAVEQUERIES is off, we can only count the number of queries
			// by looking at the change in the total query counter.
			// We won't have individual query times for the block.
			$queries_in_block = $wpdb->num_queries - $this->perf_initial_query_count;
		}

		return array(
			'duration_sec'     => $duration_sec,
			'queries_in_block' => $queries_in_block, // Queries executed within the tracked block
			'db_time_sec'      => $db_time_sec,      // Total time of queries in the block (if SAVEQUERIES)
			'peak_memory_kb'   => $peak_memory_kb,
		);
	}
}
