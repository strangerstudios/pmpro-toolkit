<?php
namespace PMPro_Toolkit;

use WP_CLI;
use WP_CLI_Command;
use WP_CLI\Utils;
use PMPro_Scheduled_Actions;

/**
 * Manage recurring payment dates for testing reminders.
 */
class Payment_Reminders_Command {

	/**
	 * Adjust next_payment_date for active subscriptions and clear reminder meta for testing.
	 *
	 * ## OPTIONS
	 *
	 * [--limit=<number>]
	 * : Limit the number of subscriptions to process. Default is 0 (no limit).
	 *
	 * [--user_ids=<ids>]
	 * : Comma-separated list of user IDs to process.
	 *
	 * [--days=<n>]
	 * : Set next_payment_date to n days from now (positive = future, negative = past). Default is 7.
	 *
	 * [--with_as]
	 * : If set, trigger the recurring payment reminder scheduler.
	 *
	 * ## EXAMPLES
	 *
	 *     wp pmpro-toolkit payment-reminders
	 *     wp pmpro-toolkit payment-reminders --limit=10
	 *     wp pmpro-toolkit payment-reminders --user_ids=1,2,3
	 *     wp pmpro-toolkit payment-reminders --days=3
	 *     wp pmpro-toolkit payment-reminders --with_as
	 */
	public function __invoke( $args, $assoc_args ) {

		global $wpdb;

		$limit        = isset( $assoc_args['limit'] ) ? intval( $assoc_args['limit'] ) : 0;
		$user_ids_arg = isset( $assoc_args['user_ids'] ) ? $assoc_args['user_ids'] : '';
		$days         = isset( $assoc_args['days'] ) ? intval( $assoc_args['days'] ) : 7;
		$with_as      = isset( $assoc_args['with_as'] );

		// Get users to process.
		if ( ! empty( $user_ids_arg ) ) {
			$user_ids = array_map( 'intval', array_filter( array_map( 'trim', explode( ',', $user_ids_arg ) ) ) );
		} else {
			$user_ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->users}" );
		}

		if ( $limit > 0 ) {
			$user_ids = array_slice( $user_ids, 0, $limit );
		}

		if ( empty( $user_ids ) ) {
			WP_CLI::warning( __( 'No users found to process.', 'pmpro-toolkit' ) );
			return;
		}

		$total_users = count( $user_ids );
		$progress = \WP_CLI\Utils\make_progress_bar( __( 'Processing users', 'pmpro-toolkit' ), $total_users );

		$processed_count = 0;
		foreach ( $user_ids as $user_id ) {
			$progress->tick();
			// Get all active subscriptions for user.
			$subscriptions = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}pmpro_subscriptions WHERE user_id = %d AND status = 'active'",
					$user_id
				)
			);

			if ( empty( $subscriptions ) ) {
				continue;
			}

			foreach ( $subscriptions as $subscription ) {
				$reminder_window = $days > 0 ? $days - 1 : 0;
				$next_payment_date = date( 'Y-m-d 00:00:00', strtotime( "+{$reminder_window} days", current_time( 'timestamp' ) ) );
				$wpdb->update(
					"{$wpdb->prefix}pmpro_subscriptions",
					array(
						'next_payment_date' => $next_payment_date,
						'modified'          => current_time( 'mysql' ),
					),
					array( 'id' => $subscription->id )
				);

				// Remove meta fields related to recurring payment reminders, so reminders get sent again.
				$wpdb->delete(
					"{$wpdb->prefix}pmpro_subscriptionmeta",
					array(
						'pmpro_subscription_id' => $subscription->id,
						'meta_key'              => 'pmprorm_last_next_payment_date',
					)
				);
				$wpdb->delete(
					"{$wpdb->prefix}pmpro_subscriptionmeta",
					array(
						'pmpro_subscription_id' => $subscription->id,
						'meta_key'              => 'pmprorm_last_days',
					)
				);

				++$processed_count;
			}
		}
		$progress->finish();

		WP_CLI::success(
			sprintf(
				_n(
					'Adjusted next_payment_date for %d subscription. Cleared reminder meta.',
					'Adjusted next_payment_date for %d subscriptions. Cleared reminder meta.',
					$processed_count,
					'pmpro-toolkit'
				),
				$processed_count
			)
		);

		// Optionally trigger the scheduler.
		if ( $with_as && class_exists( 'PMPro_Scheduled_Actions' ) ) {
			PMPro_Scheduled_Actions::instance()->schedule_recurring_payment_reminder_tasks();
			// Log the count of scheduled tasks.
			WP_CLI::success(
				sprintf(
					_n(
						'Scheduled %d recurring payment reminder.',
						'Scheduled %d recurring payment reminders.',
						$processed_count,
						'pmpro-toolkit'
					),
					$processed_count
				)
			);
			
		}
	}
}
