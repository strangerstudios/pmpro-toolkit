<?php
namespace PMPro_Toolkit;

use WP_CLI;
use WP_CLI_Command;
use WP_CLI\Utils;
use PMPro_Scheduled_Actions;

class Expire_Memberships_Command {

	/**
	 * Update existing users' subscription expiration dates to test expiry functionality.
	 *
	 * ## OPTIONS
	 *
	 * [--limit=<number>]
	 * : Limit the number of users to process. Default is 0 (no limit).
	 *
	 * [--user_ids=<ids>]
	 * : Comma-separated list of user IDs to process.
	 *
	 * [--days=<n>]
	 * : Number of days to set the subscription expiration date. Can be positive (future expiry) or negative (already expired). Default is 1.
	 *
	 * [--expired]
	 * : If set, override --days and set expiration date to yesterday (-1).
	 *
	 * [--with_as]
	 * : If set, trigger the membership expiration reminder emails via Action Scheduler.
	 *
	 * ## EXAMPLES
	 *
	 *     wp pmpro-toolkit expire-memberships
	 *     wp pmpro-toolkit expire-memberships --limit=10
	 *     wp pmpro-toolkit expire-memberships --user_ids=1,2,3
	 *     wp pmpro-toolkit expire-memberships --user_ids=1,2,3 --limit=2
	 *     wp pmpro-toolkit expire-memberships --days=5
	 *     wp pmpro-toolkit expire-memberships --days=-3
	 *     wp pmpro-toolkit expire-memberships --expired
	 *     wp pmpro-toolkit expire-memberships --with_as
	 */
	public function __invoke( $args, $assoc_args ) {

		global $wpdb;

		$limit        = isset( $assoc_args['limit'] ) ? intval( $assoc_args['limit'] ) : 0;
		$user_ids_arg = isset( $assoc_args['user_ids'] ) ? $assoc_args['user_ids'] : '';
		$days         = isset( $assoc_args['days'] ) ? intval( $assoc_args['days'] ) : 1;
		$with_as      = isset( $assoc_args['with_as'] );
		$expired      = isset( $assoc_args['expired'] );

		if ( $expired ) {
			$days = -1;
		}

		if ( ! empty( $user_ids_arg ) ) {
			$user_ids = array_map( 'intval', array_filter( array_map( 'trim', explode( ',', $user_ids_arg ) ) ) );
			if ( $limit > 0 ) {
				$user_ids = array_slice( $user_ids, 0, $limit );
			}
		} else {
			$users = $wpdb->get_col( "SELECT ID FROM {$wpdb->users}" );
			if ( $limit > 0 ) {
				$user_ids = array_slice( $users, 0, $limit );
			} else {
				$user_ids = $users;
			}
		}

		if ( empty( $user_ids ) ) {
			return; // No users to assign subscriptions to.
		}

		$processed_count = 0;

		// Initialize a WP-CLI progress bar.
		$progress = \WP_CLI\Utils\make_progress_bar(
			sprintf(
			/* translators: %d: number of users */
				__( 'Processing %d users', 'pmpro-toolkit' ),
				count( $user_ids )
			),
			count( $user_ids )
		);

		foreach ( $user_ids as $user_id ) {
			// Check for an existing subscription.
			$subscription = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}pmpro_subscriptions WHERE user_id = %d AND status = 'active' ORDER BY id DESC LIMIT 1",
					$user_id
				)
			);

			$next_payment_date = date( 'Y-m-d H:i:s', strtotime( "{$days} days" ) );

			// Determine membership_level_id to use
			if ( $subscription ) {
				$membership_level_id = $subscription->membership_level_id;
			} else {
				// Try to get user's current level from pmpro_memberships_users table
				$membership_level_id = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT membership_id FROM {$wpdb->prefix}pmpro_memberships_users WHERE user_id = %d AND status = 'active' ORDER BY enddate DESC LIMIT 1",
						$user_id
					)
				);

				if ( ! $membership_level_id ) {
					// Fallback to first available membership level id from pmpro_membership_levels table
					$membership_level_id = $wpdb->get_var(
						"SELECT id FROM {$wpdb->prefix}pmpro_membership_levels ORDER BY id ASC LIMIT 1"
					);
				}
			}

			if ( $subscription ) {
				// Update existing subscription's next payment date.
				$wpdb->update(
					"{$wpdb->prefix}pmpro_subscriptions",
					array(
						'next_payment_date' => $next_payment_date,
						'modified'          => current_time( 'mysql' ),
					),
					array( 'id' => $subscription->id )
				);
			} else {
				// Insert new subscription.
				$wpdb->insert(
					"{$wpdb->prefix}pmpro_subscriptions",
					array(
						'user_id'                     => $user_id,
						'membership_level_id'         => $membership_level_id,
						'gateway'                     => 'stripe',
						'gateway_environment'         => 'test',
						'subscription_transaction_id' => uniqid( 'txn_test_' ),
						'status'                      => 'active',
						'startdate'                   => current_time( 'mysql' ),
						'next_payment_date'           => $next_payment_date,
						'billing_amount'              => 9.99,
						'cycle_number'                => 1,
						'cycle_period'                => 'Month',
						'modified'                    => current_time( 'mysql' ),
					)
				);
			}

			// Sync pmpro_memberships_users table.
			$enddate = date( 'Y-m-d H:i:s', strtotime( "{$days} days" ) );

			// Check if a record exists for this user and membership level
			$existing = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$wpdb->prefix}pmpro_memberships_users WHERE user_id = %d AND membership_id = %d",
					$user_id,
					$membership_level_id
				)
			);

			$data = array(
				'user_id'       => $user_id,
				'membership_id' => $membership_level_id,
				'startdate'     => current_time( 'mysql' ),
				'enddate'       => $enddate,
				'modified'      => current_time( 'mysql' ),
				'status'        => 'active',
			);

			if ( $existing ) {
				$wpdb->update( "{$wpdb->prefix}pmpro_memberships_users", $data, array( 'id' => $existing ) );
			} else {
				$wpdb->insert( "{$wpdb->prefix}pmpro_memberships_users", $data );
			}

			// Clear the expiration notice meta so reminders get sent again.
			delete_user_meta( $user_id, 'pmpro_expiration_notice_' . $membership_level_id );

			++$processed_count;
			$progress->tick();
		}

		// Finish the progress bar.
		$progress->finish();

		$expiry_direction = $days < 0 ? 'already expired' : 'set to expire in the future';

		WP_CLI::success( sprintf( _n( 'Expiring subscription added/updated for %1$d user (%2$s).', 'Expiring subscriptions added/updated for %1$d users (%2$s).', $processed_count, 'pmpro-toolkit' ), $processed_count, $expiry_direction ) );

		// Trigger membership expiration emails via Action Scheduler if applicable.
		if ( class_exists( 'PMPro_Scheduled_Actions' ) && $with_as ) {
			WP_CLI::log( 'Triggering membership expiration emails via scheduled actions...' );
			if ( $expired || $days < 0 ) {
				PMPro_Scheduled_Actions::instance()->pmpro_expire_memberships();
				WP_CLI::success( 'Membership expiration emails scheduled.' );
			} else {
				PMPro_Scheduled_Actions::instance()->membership_expiration_reminders();
				WP_CLI::success( 'Membership expiring soon emails scheduled.' );
			}
		}
	}
}
