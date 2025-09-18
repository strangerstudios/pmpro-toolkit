<?php
namespace PMPro_Toolkit;

use WP_CLI;
use WP_CLI_Command;

/**
 * WP-CLI commands mapping to destructive/utility scripts in adminpages/scripts.php.
 */
class Toolkit_Commands extends WP_CLI_Command {
	public function __construct() {
		// Load scripts file if not already loaded.
		// This file defines functions we will call in the commands below.
		// We check for one function to see if it's loaded.
		// We do this in the constructor so it's only loaded once.
		if ( ! function_exists( '\\pmprodev_clean_member_tables' ) ) {
			$script_file = dirname( __DIR__ ) . '/adminpages/scripts.php';
			if ( file_exists( $script_file ) ) {
				require_once $script_file;
			}
		}
	}
	/**
	 * Wrapper to ask for confirmation unless dry run.
	 *
	 * @param bool   $dry_run Whether this is a dry run.
	 * @param string $question The confirmation question.
	 * @return void
	 */
	private function confirm_or_continue( $dry_run, $question ) {
		if ( $dry_run ) {
			return; // No confirmation needed for dry run.
		}
		WP_CLI::confirm( $question );
	}

	/**
	 * Helper to maybe run or just echo dry-run.
	 *
	 * @param bool   $dry_run Whether dry run.
	 * @param string $desc Description of action.
	 */
	private function dry_run_note( $dry_run, $desc ) {
		if ( $dry_run ) {
			WP_CLI::log( sprintf( __( '[Dry Run] %s', 'pmpro-toolkit' ), $desc ) );
		}
	}

	/**
	 * Delete all member related data tables (TRUNCATE).
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]  : Show what would happen without making changes.
	 */
	public function clean_member_tables( $args, $assoc_args ) {
		$dry = isset( $assoc_args['dry-run'] );
		if ( $dry ) {
			$this->dry_run_note( $dry, __( 'Would truncate member tables.', 'pmpro-toolkit' ) );
			return;
		}
		$this->confirm_or_continue( $dry, __( 'Are you sure you want to truncate all member tables?', 'pmpro-toolkit' ) );
		\pmprodev_clean_member_tables( __( 'Member tables have been truncated.', 'pmpro-toolkit' ) );
		WP_CLI::success( __( 'Done.', 'pmpro-toolkit' ) );
	}

	/**
	 * Reset membership levels, discount codes and related settings (TRUNCATE level tables).
	 *
	 * ## OPTIONS
	 * [--dry-run]
	 */
	public function clean_level_data( $args, $assoc_args ) {
		$dry = isset( $assoc_args['dry-run'] );
		if ( $dry ) {
			$this->dry_run_note( $dry, __( 'Would truncate level/settings tables.', 'pmpro-toolkit' ) );
			return;
		}
		$this->confirm_or_continue( $dry, __( 'Are you sure you want to reset all membership levels and related settings?', 'pmpro-toolkit' ) );
		\pmprodev_clean_level_data( __( 'Level and discount code tables have been truncated.', 'pmpro-toolkit' ) );
		WP_CLI::success( __( 'Done.', 'pmpro-toolkit' ) );
	}

	/**
	 * Scrub (anonymize) member emails and transaction IDs.
	 *
	 * ## OPTIONS
	 * [--dry-run]
	 */
	public function scrub_member_data( $args, $assoc_args ) {
		$dry = isset( $assoc_args['dry-run'] );
		if ( $dry ) {
			$this->dry_run_note( true, __( 'Would anonymize user emails and transaction IDs.', 'pmpro-toolkit' ) );
			return;
		}
		$this->confirm_or_continue( $dry, __( 'Are you sure you want to scrub (anonymize) all member emails and transaction IDs?', 'pmpro-toolkit' ) );
		\pmprodev_scrub_member_data( __( 'Scrubbing user data...', 'pmpro-toolkit' ) );
		WP_CLI::success( __( 'Done.', 'pmpro-toolkit' ) );
	}

	/**
	 * Delete all non-admin users.
	 *
	 * ## OPTIONS
	 * [--dry-run]
	 */
	public function delete_users( $args, $assoc_args ) {
		$dry = isset( $assoc_args['dry-run'] );
		if ( $dry ) {
			$this->dry_run_note( true, __( 'Would delete all non-admin users.', 'pmpro-toolkit' ) );
			return;
		}
		$this->confirm_or_continue( $dry, __( 'Are you sure you want to DELETE all non-admin users?', 'pmpro-toolkit' ) );
		\pmprodev_delete_users( __( 'Deleting non-admins...', 'pmpro-toolkit' ) );
		WP_CLI::success( __( 'Done.', 'pmpro-toolkit' ) );
	}

	/**
	 * Delete PMPro related options.
	 *
	 * ## OPTIONS
	 * [--dry-run]
	 */
	public function clean_pmpro_options( $args, $assoc_args ) {
		$dry = isset( $assoc_args['dry-run'] );
		if ( $dry ) {
			$this->dry_run_note( $dry, __( 'Would delete PMPro options.', 'pmpro-toolkit' ) );
			return;
		}
		$this->confirm_or_continue( $dry, __( 'Are you sure you want to delete PMPro options?', 'pmpro-toolkit' ) );
		\pmprodev_clean_pmpro_options( __( 'Options deleted.', 'pmpro-toolkit' ) );
		WP_CLI::success( __( 'Done.', 'pmpro-toolkit' ) );
	}

	/**
	 * Clear visits, views, logins report tables.
	 *
	 * ## OPTIONS
	 * [--dry-run]
	 */
	public function clear_vvl_report( $args, $assoc_args ) {
		$dry = isset( $assoc_args['dry-run'] );
		if ( $dry ) {
			$this->dry_run_note( $dry, __( 'Would truncate visits, views, logins tables.', 'pmpro-toolkit' ) );
			return;
		}
		$this->confirm_or_continue( $dry, __( 'Are you sure you want to clear Visits, Views, and Logins report data?', 'pmpro-toolkit' ) );
		\pmprodev_clear_vvl_report( __( 'Visits, Views, and Logins report cleared.', 'pmpro-toolkit' ) );
		WP_CLI::success( __( 'Done.', 'pmpro-toolkit' ) );
	}

	/**
	 * Delete all sandbox/test orders and subscriptions.
	 *
	 * ## OPTIONS
	 * [--dry-run]
	 */
	public function delete_test_orders( $args, $assoc_args ) {
		$dry = isset( $assoc_args['dry-run'] );
		if ( $dry ) {
			$this->dry_run_note( $dry, __( 'Would delete sandbox orders/subscriptions.', 'pmpro-toolkit' ) );
			return;
		}
		$this->confirm_or_continue( $dry, __( 'Are you sure you want to delete ALL sandbox orders and subscriptions?', 'pmpro-toolkit' ) );
		\pmprodev_delete_test_orders( __( 'Test orders deleted.', 'pmpro-toolkit' ) );
		WP_CLI::success( __( 'Done.', 'pmpro-toolkit' ) );
	}

	/**
	 * Clear cached report data (transients).
	 *
	 * ## OPTIONS
	 * [--dry-run]
	 */
	public function clear_cached_report_data( $args, $assoc_args ) {
		$dry = isset( $assoc_args['dry-run'] );
		if ( $dry ) {
			$this->dry_run_note( $dry, __( 'Would clear report cache transients.', 'pmpro-toolkit' ) );
			return; 
		}
		$this->confirm_or_continue( $dry, __( 'Clear cached report data now?', 'pmpro-toolkit' ) );
		\pmprodev_clear_cached_report_data( __( 'Cached report data cleared.', 'pmpro-toolkit' ) );
		WP_CLI::success( __( 'Done.', 'pmpro-toolkit' ) );
	}

	/**
	 * Move all users from one level to another (database only) and run hooks.
	 *
	 * ## OPTIONS
	 * --from=<id>  : Source level ID.
	 * --to=<id>    : Destination level ID.
	 * [--dry-run]
	 */
	public function move_level( $args, $assoc_args ) {
		$dry  = isset( $assoc_args['dry-run'] );
		$from = isset( $assoc_args['from'] ) ? intval( $assoc_args['from'] ) : 0;
		$to   = isset( $assoc_args['to'] ) ? intval( $assoc_args['to'] ) : 0;
		if ( $from < 1 || $to < 1 ) {
			WP_CLI::error( __( 'Please supply valid --from and --to level IDs.', 'pmpro-toolkit' ) );
		}
		if ( $dry ) {
			$this->dry_run_note( true, sprintf( __( 'Would move members from level %1$d to %2$d.', 'pmpro-toolkit' ), $from, $to ) );
			return;
		}
		$this->confirm_or_continue( $dry, sprintf( __( 'Move all members from level %1$d to %2$d?', 'pmpro-toolkit' ), $from, $to ) );
		$_REQUEST['move_level_a'] = $from;
		$_REQUEST['move_level_b'] = $to;
		\pmprodev_move_level( __( 'Users updated. Running pmpro_after_change_membership_level filter for all users...', 'pmpro-toolkit' ) );
		WP_CLI::success( __( 'Done.', 'pmpro-toolkit' ) );
	}

	/**
	 * Assign a membership level to all users without an active membership.
	 *
	 * ## OPTIONS
	 * --level=<id>        : Level ID to assign.
	 * --start=<YYYY-MM-DD>: (required) Start date.
	 * [--end=<YYYY-MM-DD>] : End date (optional).
	 * [--dry-run]
	 */
	public function give_level( $args, $assoc_args ) {
		$dry   = isset( $assoc_args['dry-run'] );
		$level = isset( $assoc_args['level'] ) ? intval( $assoc_args['level'] ) : 0;
		$start = isset( $assoc_args['start'] ) ? sanitize_text_field( $assoc_args['start'] ) : '';
		$end   = isset( $assoc_args['end'] ) ? sanitize_text_field( $assoc_args['end'] ) : '';
		if ( $level < 1 || empty( $start ) ) {
			WP_CLI::error( __( 'Please supply --level and --start.', 'pmpro-toolkit' ) );
		}
		if ( $dry ) {
			$this->dry_run_note( true, sprintf( __( 'Would assign level %1$d to users without membership starting %2$s%3$s.', 'pmpro-toolkit' ), $level, $start, $end ? ' ending ' . $end : '' ) );
			return;
		}
		$this->confirm_or_continue( $dry, sprintf( __( 'Assign level %d to all users without an active membership?', 'pmpro-toolkit' ), $level ) );
		$_REQUEST['give_level_id']        = $level;
		$_REQUEST['give_level_startdate'] = $start;
		$_REQUEST['give_level_enddate']   = $end;
		\pmprodev_give_level( __( '%1$s users were given level %2$s', 'pmpro-toolkit' ) );
		WP_CLI::success( __( 'Done.', 'pmpro-toolkit' ) );
	}

	/**
	 * Cancel all users with a specific membership level (and recurring subscriptions).
	 *
	 * ## OPTIONS
	 * --level=<id> : Level ID to cancel.
	 * [--dry-run]
	 */
	public function cancel_level( $args, $assoc_args ) {
		$dry   = isset( $assoc_args['dry-run'] );
		$level = isset( $assoc_args['level'] ) ? intval( $assoc_args['level'] ) : 0;
		if ( $level < 1 ) {
			WP_CLI::error( __( 'Please supply --level.', 'pmpro-toolkit' ) );
		}
		if ( $dry ) {
			$this->dry_run_note( true, sprintf( __( 'Would cancel all users with level %d.', 'pmpro-toolkit' ), $level ) );
			return;
		}
		$this->confirm_or_continue( $dry, sprintf( __( 'Cancel all active memberships for level %d (including recurring subscriptions)?', 'pmpro-toolkit' ), $level ) );
		$_REQUEST['cancel_level_id'] = $level;
		\pmprodev_cancel_level( __( 'Cancelling users...', 'pmpro-toolkit' ) );
		WP_CLI::success( __( 'Done.', 'pmpro-toolkit' ) );
	}

	/**
	 * Copy content restrictions from one level to another.
	 *
	 * ## OPTIONS
	 * --from=<id> : Copy from level ID.
	 * --to=<id>   : Copy to level ID.
	 * [--dry-run]
	 */
	public function copy_memberships_pages( $args, $assoc_args ) {
		$dry  = isset( $assoc_args['dry-run'] );
		$from = isset( $assoc_args['from'] ) ? intval( $assoc_args['from'] ) : 0;
		$to   = isset( $assoc_args['to'] ) ? intval( $assoc_args['to'] ) : 0;
		if ( $from < 1 || $to < 1 ) {
			WP_CLI::error( __( 'Please supply valid --from and --to level IDs.', 'pmpro-toolkit' ) );
		}
		if ( $dry ) {
			$this->dry_run_note( true, sprintf( __( 'Would copy restricted pages from level %1$d to %2$d.', 'pmpro-toolkit' ), $from, $to ) );
			return;
		}
		$this->confirm_or_continue( $dry, sprintf( __( 'Copy restricted pages from level %1$d to %2$d?', 'pmpro-toolkit' ), $from, $to ) );
		$_REQUEST['copy_memberships_pages_from'] = $from;
		$_REQUEST['copy_memberships_pages_to']   = $to;
		\pmprodev_copy_memberships_pages( __( 'Content restrictions copied.', 'pmpro-toolkit' ) );
		WP_CLI::success( __( 'Done.', 'pmpro-toolkit' ) );
	}

	/**
	 * Delete incomplete orders older than X days (token, pending, review status).
	 *
	 * ## OPTIONS
	 * --days=<number> : Minimum age (days) of orders to delete.
	 * [--dry-run]
	 */
	public function delete_incomplete_orders( $args, $assoc_args ) {
		$dry  = isset( $assoc_args['dry-run'] );
		$days = isset( $assoc_args['days'] ) ? intval( $assoc_args['days'] ) : 0;
		if ( $days < 1 ) {
			WP_CLI::error( __( 'Please supply --days (integer > 0).', 'pmpro-toolkit' ) );
		}
		if ( $dry ) {
			$this->dry_run_note( true, sprintf( __( 'Would delete incomplete orders older than %d days.', 'pmpro-toolkit' ), $days ) );
			return;
		}
		$this->confirm_or_continue( $dry, sprintf( __( 'Delete incomplete (token/pending/review) orders older than %d days?', 'pmpro-toolkit' ), $days ) );
		$_REQUEST['delete_incomplete_orders_days'] = $days;
		\pmprodev_delete_incomplete_orders( __( '%d orders deleted.', 'pmpro-toolkit' ) );
		WP_CLI::success( __( 'Done.', 'pmpro-toolkit' ) );
	}
}
