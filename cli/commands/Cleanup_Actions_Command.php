<?php
namespace PMPro_Toolkit;

use WP_CLI;
use WP_CLI\Utils;
use WP_CLI_Command;
use PMPro_Action_Scheduler;

class Cleanup_Actions_Command {

	/**
	 * Remove PMPro scheduled actions, optionally filtering by hook, group, status, or args.
	 *
	 * ## OPTIONS
	 *
	 * [--hook=<hook>]
	 * : Only delete actions with this hook name.
	 *
	 * [--group=<group>]
	 * : Only delete actions in this group.
	 *
	 * [--status=<status>]
	 * : Only delete actions with this status (default: completed).
	 *
	 * [--args=<json_args>]
	 * : Only delete actions with these exact args. Example: --args='{"user_id":123,"something":"value"}'
	 *
	 * ## EXAMPLES
	 *
	 *     wp pmpro-toolkit cleanup-actions
	 *     wp pmpro-toolkit cleanup-actions --hook=pmpro_schedule_daily
	 *     wp pmpro-toolkit cleanup-actions --hook=pmpro_schedule_hourly --status=pending
	 *     wp pmpro-toolkit cleanup-actions --group=pmpro_email --status=failed
	 *     wp pmpro-toolkit cleanup-actions --args='{"user_id":123}'
	 */
	public function __invoke( $args, $assoc_args ) {
		if ( ! class_exists( 'PMPro_Action_Scheduler' ) ) {
			WP_CLI::error( __( 'PMPro_Action_Scheduler class not found.', 'pmpro-toolkit' ) );
			return;
		}

		$hook   = isset( $assoc_args['hook'] ) ? $assoc_args['hook'] : null;
		$group  = isset( $assoc_args['group'] ) ? $assoc_args['group'] : null;
		$status = isset( $assoc_args['status'] ) ? $assoc_args['status'] : 'completed';

		// Args can be passed as a JSON string (for advanced/automated use)
		$args_array = array();
		if ( isset( $assoc_args['args'] ) && ! empty( $assoc_args['args'] ) ) {
			$args_json  = $assoc_args['args'];
			$args_array = json_decode( $args_json, true );
			if ( ! is_array( $args_array ) ) {
				WP_CLI::error( __( 'Could not parse --args. Please provide a valid JSON array.', 'pmpro-toolkit' ) );
				return;
			}
		}

		WP_CLI::line( sprintf( __( 'Looking for actions...', 'pmpro-toolkit' ) ) );

		$deleted_count = PMPro_Action_Scheduler::instance()->remove_actions(
			$hook,
			$args_array,
			$group,
			$status
		);

		if ( $deleted_count === 0 ) {
			WP_CLI::success( __( 'No actions found to delete.', 'pmpro-toolkit' ) );
			return;
		}

		WP_CLI::success(
			sprintf(
				/* translators: %d: count, %s: details */
				__( 'Deleted %1$d action(s) [%2$s].', 'pmpro-toolkit' ),
				$deleted_count,
				$this->build_criteria_description( $hook, $group, $status, $args_array )
			)
		);
	}

	/**
	 * Helper to describe criteria used in the CLI message.
	 */
	private function build_criteria_description( $hook, $group, $status, $args_array ) {
		$bits = array();
		if ( $hook ) {
			$bits[] = "hook: $hook"; }
		if ( $group ) {
			$bits[] = "group: $group"; }
		if ( $status ) {
			$bits[] = "status: $status"; }
		if ( ! empty( $args_array ) ) {
			$bits[] = 'args: ' . json_encode( $args_array );
		}
		return $bits ? implode( ', ', $bits ) : __( 'no filter', 'pmpro-toolkit' );
	}
}
