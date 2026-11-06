<?php
namespace PMPro_Toolkit;

use WP_CLI;
use WP_CLI_Command;

class Bulk_Checkout_Users_Command extends WP_CLI_Command {

	/**
	 * Bulk create users and simulate checkouts for each.
	 *
	 * ## OPTIONS
	 *
	 * [--count=<number>]
	 * : Number of users to create and checkout. Default: 100
	 *
	 * [--months-back=<number>]
	 * : Randomize checkout date within the past X months. Default: 1
	 *
	 * [--endpoint=<url>]
	 * : The REST endpoint URL for test checkout. Default: current site URL + /wp-json/toolkit/v1/test-checkout
	 *
	 * [--membership_level_id=<id>]
	 * : The membership level ID to assign to each user.
	 *
	 * [--password=<password>]
	 * : The password for the new users. Default is 'password123'.
	 *
	 * ## EXAMPLES
	 *
	 *     wp pmpro-toolkit bulk-checkout-users --count=100 --months-back=3
	 */
	public function __invoke( $args, $assoc_args ) {
		$count    = isset( $assoc_args['count'] ) ? intval( $assoc_args['count'] ) : 100;
		$months   = isset( $assoc_args['months-back'] ) ? intval( $assoc_args['months-back'] ) : 1;
		$endpoint = isset( $assoc_args['endpoint'] ) ? esc_url_raw( $assoc_args['endpoint'] ) : home_url( '/wp-json/toolkit/v1/test-checkout' );

		// Use Bulk_Add_Users_Command to create users WITHOUT memberships
		$bulk_add = new \PMPro_Toolkit\Bulk_Add_Users_Command();

		// Store created users by hooking into user_register
		$created_users = array();
		add_action(
			'user_register',
			function ( $user_id ) use ( &$created_users ) {
				$user = get_userdata( $user_id );
				if ( $user ) {
					$created_users[] = array(
						'ID'         => $user->ID,
						'user_login' => $user->user_login,
						'user_email' => $user->user_email,
					);
				}
			},
			10,
			1
		);

		// Prepare args for Bulk_Add_Users_Command (exclude checkout-specific args and don't create memberships)
		$user_assoc_args = $assoc_args;
		unset( $user_assoc_args['endpoint'] );
		unset( $user_assoc_args['months-back'] ); // We'll handle date randomization in checkout
		// Explicitly do NOT include --with-membership flag

		WP_CLI::log( __( 'Creating users for checkout simulation...', 'pmpro-toolkit' ) );

		// Call Bulk_Add_Users_Command to create users without memberships
		$bulk_add->__invoke( $args, $user_assoc_args );

		// Clean up the hook
		remove_all_actions( 'user_register' );

		if ( empty( $created_users ) ) {
			WP_CLI::error( __( 'No users were created. Aborting checkout requests.', 'pmpro-toolkit' ) );
			return;
		}

		WP_CLI::log(
			sprintf(
				__( 'Created %d users. Starting checkout simulation...', 'pmpro-toolkit' ),
				count( $created_users )
			)
		);

		$progress = \WP_CLI\Utils\make_progress_bar( 'Running checkouts for created users', count( $created_users ) );
		$failed   = 0;
		$success  = 0;

		foreach ( $created_users as $user ) {
			// Simulate checkout with date randomization if specified
			$checkout_date = $this->get_random_checkout_date( $months );

			$payload = array(
				'user_login'    => $user['user_login'],
				'user_email'    => $user['user_email'],
				'skip_gateway'  => true,
				'cleanup'       => false, // Don't delete users we just created
				'checkout_date' => $checkout_date, // Add this to endpoint if needed
			);

			// Add membership level if specified
			if ( isset( $assoc_args['membership_level_id'] ) ) {
				$payload['membership_level'] = intval( $assoc_args['membership_level_id'] );
			}

			$response = wp_remote_post(
				$endpoint,
				array(
					'headers' => array( 'Content-Type' => 'application/json' ),
					'body'    => wp_json_encode( $payload ),
					'timeout' => 30,
				)
			);

			if ( is_wp_error( $response ) ) {
				WP_CLI::warning(
					sprintf(
						__( 'Checkout failed for %1$s: %2$s', 'pmpro-toolkit' ),
						$user['user_login'],
						$response->get_error_message()
					)
				);
				++$failed;
			} elseif ( wp_remote_retrieve_response_code( $response ) >= 300 ) {
				WP_CLI::warning(
					sprintf(
						__( 'Checkout failed for %1$s: HTTP %2$d', 'pmpro-toolkit' ),
						$user['user_login'],
						wp_remote_retrieve_response_code( $response )
					)
				);
				++$failed;
			} else {
				++$success;
			}

			$progress->tick();
		}

		$progress->finish();

		WP_CLI::log( __( 'Bulk user checkout complete.', 'pmpro-toolkit' ) );
		WP_CLI::log( sprintf( __( 'Success: %1$d, Failed: %2$d', 'pmpro-toolkit' ), $success, $failed ) );

		if ( $failed === 0 ) {
			WP_CLI::success( __( 'All checkouts processed successfully.', 'pmpro-toolkit' ) );
		} else {
			WP_CLI::warning( sprintf( __( '%d checkouts failed to process.', 'pmpro-toolkit' ), $failed ) );
		}
	}

	/**
	 * Get a random date within the specified months back from now.
	 *
	 * @param int $months Number of months back.
	 * @return string MySQL datetime string.
	 */
	private function get_random_checkout_date( $months ) {
		if ( $months <= 0 ) {
			return current_time( 'mysql' );
		}

		$now              = current_time( 'timestamp' );
		$earliest         = strtotime( '-' . $months . ' months', $now );
		$random_timestamp = mt_rand( $earliest, $now );

		return date( 'Y-m-d H:i:s', $random_timestamp );
	}
}

