<?php
namespace PMPro_Toolkit;

use WP_CLI;
use WP_CLI_Command;
use WP_CLI\Utils;

class Bulk_Add_Users_Command extends WP_CLI_Command {

	/**
	 * Pool of unused name combinations.
	 *
	 * @var array
	 */
	private $name_combinations = array();

	/**
	 * Flag to indicate if the name pool has been initialized.
	 *
	 * @var bool
	 */
	private $name_pool_initialized = false;

	/**
	 * Add a large number of new users, optionally with memberships and subscriptions.
	 *
	 * ## OPTIONS
	 *
	 * [--count=<number>]
	 * : The number of users to add. Default is 10.
	 *
	 * [--password=<password>]
	 * : The password for the new users. Default is 'password123'.
	 *
	 * [--months-back=<number>]
	 * : The number of months in the past to randomly assign start dates (only used with --with-membership). Default is 1.
	 *
	 * [--with-membership]
	 * : Create users with memberships assigned. Without this flag, only user accounts are created.
	 *
	 * [--membership_level_id=<id>]
	 * : The membership level ID to assign to each user (requires --with-membership). If not specified, levels are randomly assigned.
	 *
	 * [--for-siege=<domain>]
	 * : Output a txt file for Siege with test login POST commands, using the specified domain (e.g., http://example.com).
	 *
	 * ## EXAMPLES
	 *
	 *     wp pmpro-toolkit bulk-add-users --count=50
	 *     wp pmpro-toolkit bulk-add-users --count=100 --with-membership --membership_level_id=2
	 *     wp pmpro-toolkit bulk-add-users --count=100 --with-membership --months-back=6
	 *     wp pmpro-toolkit bulk-add-users --count=10 --for-siege=[domain including http:// or https://]
	 */
	public function __invoke( $args, $assoc_args ) {
		global $wpdb;

		$count                         = isset( $assoc_args['count'] ) ? intval( $assoc_args['count'] ) : 10;
		$custom_password               = isset( $assoc_args['password'] ) ? $assoc_args['password'] : 'password123';
		$with_membership               = isset( $assoc_args['with-membership'] );
		$specified_membership_level_id = isset( $assoc_args['membership_level_id'] ) ? intval( $assoc_args['membership_level_id'] ) : 0;
		$months                        = isset( $assoc_args['months-back'] ) ? intval( $assoc_args['months-back'] ) : 1;
		$siege_lines  = array();
		$siege_domain = isset( $assoc_args['for-siege'] ) ? rtrim( $assoc_args['for-siege'], '/' ) : '';

		// Validate months-back parameter
		if ( $months > 6 ) {
			WP_CLI::error( __( '--months-back cannot be greater than 6. Please specify a value between 1 and 6.', 'pmpro-toolkit' ) );
			return;
		}

		// If membership_level_id is provided without --with-membership, show warning
		if ( $specified_membership_level_id && ! $with_membership ) {
			WP_CLI::warning( __( '--membership_level_id specified but --with-membership not set. Memberships will not be created.', 'pmpro-toolkit' ) );
		}

		$level_ids = array();

		// Only fetch levels if we're creating memberships
		if ( $with_membership ) {
			// Fetch all active membership levels
			$levels = $wpdb->get_results(
				"SELECT id FROM {$wpdb->pmpro_membership_levels} WHERE allow_signups = 1"
			);

			if ( empty( $levels ) ) {
				WP_CLI::error( __( 'No available membership levels found. Cannot create memberships.', 'pmpro-toolkit' ) );
				return;
			}

			// Build a list of level IDs
			$level_ids = wp_list_pluck( $levels, 'id' );

			// Validate provided membership_level_id if set
			if ( $specified_membership_level_id && ! in_array( $specified_membership_level_id, $level_ids, true ) ) {
				WP_CLI::error(
					sprintf(
						__( 'Specified membership level ID %1$d is not valid. Available levels: %2$s', 'pmpro-toolkit' ),
						$specified_membership_level_id,
						implode( ', ', $level_ids )
					)
				);
				return;
			}
		}

		$batch_size          = 50;
		$processed_count     = 0;
		$memberships_batch   = array();
		$subscriptions_batch = array();

		$operation_text = $with_membership ?
			__( 'Adding users with memberships', 'pmpro-toolkit' ) :
			__( 'Adding users', 'pmpro-toolkit' );

		$progress = \WP_CLI\Utils\make_progress_bar( $operation_text, $count );

		for ( $i = 1; $i <= $count; $i++ ) {
			// Get a unique first/last name combination
			$name_parts = $this->generate_unique_name();
			$first_name   = $name_parts['first_name'];
			$last_name    = $name_parts['last_name'];
			$display_name = $name_parts['display_name'];

			$username = 'test_' . sanitize_title($display_name) . '_' . uniqid();
			// Use @pmpro.test domain for testing/demo purposes
			$email    = $username . '@pmpro.test';
			$password = $custom_password ?: wp_generate_password( 16, true, true );

			// Prepare user data with names
			$user_data = array(
				'user_login'   => $username,
				'user_pass'    => $password,
				'user_email'   => $email,
				'first_name'   => $first_name,
				'last_name'    => $last_name,
				'display_name' => $display_name,
			);
			$user_id = wp_insert_user( $user_data );

			if ( is_wp_error( $user_id ) ) {
				WP_CLI::warning(
					sprintf(
						__( 'Failed to create user: %s', 'pmpro-toolkit' ),
						$username
					)
				);
				$progress->tick();
				continue;
			}

			// If --for-siege is set, build the Siege test line for this user.
			if ( $siege_domain ) {
				$siege_lines[] = sprintf(
					'%s/wp-json/toolkit/v1/test-login POST {"username":"%s","password":"%s"}',
					$siege_domain,
					$username,
					$password
				);
			}

			// Only create membership data if --with-membership flag is set
			if ( $with_membership ) {
				$membership_level_id = $specified_membership_level_id ?
					$specified_membership_level_id :
					$level_ids[ array_rand( $level_ids ) ];

				// Generate random start date if months > 0, else use current time
				$startdate = $this->get_random_past_date( $months );
				$modified  = $startdate;

				$memberships_batch[] = $wpdb->prepare(
					"(%d, %d, %s, NULL, %s, 'active')",
					$user_id,
					$membership_level_id,
					$startdate,
					$modified
				);

				// Calculate next payment date as 1 month after startdate
				$next_payment_date = date( 'Y-m-d H:i:s', strtotime( '+1 month', strtotime( $startdate ) ) );

				$subscriptions_batch[] = $wpdb->prepare(
					"(%d, %d, 'stripe', 'test', %s, 'active', %s, %s, 9.99, 1, 'Month', %s)",
					$user_id,
					$membership_level_id,
					uniqid( 'txn_test_' ),
					$startdate,
					$next_payment_date,
					$modified
				);
			}

			++$processed_count;
			$progress->tick();

			// Batch insert every $batch_size users or at the end (only if creating memberships)
			if ( $with_membership && ( $processed_count % $batch_size === 0 || $i === $count ) ) {
				if ( ! empty( $memberships_batch ) ) {
					$result = $wpdb->query(
						"INSERT INTO {$wpdb->prefix}pmpro_memberships_users 
                        (user_id, membership_id, startdate, enddate, modified, status) VALUES " .
						implode( ',', $memberships_batch )
					);

					if ( false === $result || ! empty( $wpdb->last_error ) ) {
						WP_CLI::warning(
							sprintf(
								__( 'Database error during memberships batch insert: %s', 'pmpro-toolkit' ),
								$wpdb->last_error
							)
						);
					}
					$memberships_batch = array();
				}

				if ( ! empty( $subscriptions_batch ) ) {
					$result = $wpdb->query(
						"INSERT INTO {$wpdb->prefix}pmpro_subscriptions 
                        (user_id, membership_level_id, gateway, gateway_environment, subscription_transaction_id, status, startdate, next_payment_date, billing_amount, cycle_number, cycle_period, modified) VALUES " .
						implode( ',', $subscriptions_batch )
					);

					if ( false === $result || ! empty( $wpdb->last_error ) ) {
						WP_CLI::warning(
							sprintf(
								__( 'Database error during subscriptions batch insert: %s', 'pmpro-toolkit' ),
								$wpdb->last_error
							)
						);
					}
					$subscriptions_batch = array();
				}
			}
		}

		$progress->finish();

		// If --for-siege was set, output the file.
		if ( $siege_domain && ! empty( $siege_lines ) ) {
			$upload_dir = wp_upload_dir();
			$toolkit_dir = $upload_dir['basedir'] . '/pmpro-toolkit';
			if ( ! is_dir( $toolkit_dir ) ) {
				wp_mkdir_p( $toolkit_dir );
			}
			$siege_file = sprintf(
				'%s/siege-users-%s.txt',
				$toolkit_dir,
				uniqid()
			);
			file_put_contents( $siege_file, implode( "\n", $siege_lines ) );
			$siege_url = $upload_dir['baseurl'] . '/pmpro-toolkit/' . basename( $siege_file );
			WP_CLI::log( sprintf(
				__( 'Siege test file generated: %s', 'pmpro-toolkit' ),
				esc_url( $siege_url )
			) );
		}

		// Build success message based on what was created
		if ( $with_membership ) {
			$message = sprintf(
				__( 'Successfully added %1$d users with memberships. %2$s', 'pmpro-toolkit' ),
				$processed_count,
				$specified_membership_level_id
					? sprintf( __( 'All assigned membership level ID %d.', 'pmpro-toolkit' ), $specified_membership_level_id )
					: __( 'Membership levels were randomly assigned.', 'pmpro-toolkit' )
			);
		} else {
			$message = sprintf(
				__( 'Successfully added %d users without memberships.', 'pmpro-toolkit' ),
				$processed_count
			);
		}

		WP_CLI::success( $message );
	}

	/**
	 * Helper function to get a random date in the past X months.
	 *
	 * @param int $months Number of months back.
	 * @return string MySQL datetime string.
	 */
	private function get_random_past_date( $months ) {
		if ( $months <= 0 ) {
			return current_time( 'mysql' );
		}

		$now       = current_time( 'timestamp' );
		$earliest  = strtotime( '-' . $months . ' months', $now );
		$random_ts = mt_rand( $earliest, $now );

		return date( 'Y-m-d H:i:s', $random_ts );
	}

	/**
	 * Initialize the name pool with first/last combinations and shuffle.
	 */
	private function init_name_pool() {
	    // Expanded name pools
	    $first_names = array(
	        'Alice','Bob','Carol','David','Eve','Frank','Grace','Hank','Ivy','Jack',
	        'Karen','Liam','Mona','Nate','Olivia','Paul','Quincy','Rachel','Steve','Tina',
	        'Uma','Victor','Wendy','Xander','Yvonne','Zack','Aaron','Bianca','Cody','Diana',
	        'Ethan','Fiona','Gavin','Hailey','Ian','Jenna','Kyle','Laura','Miles','Nia',
	        'Omar','Piper','Quinn','Riley','Sara','Trent','Ursula','Vince','Willow','Xena',
	        'Yara','Zane','Amber','Blake','Chloe','Derek','Elena','Felix','Gemma','Hudson',
	        'Isla','Jonah','Kendra','Leon','Maya','Noah','Opal','Preston','Quilla','Roman',
	        'Selena','Tyler','Ulric','Valeria','Wyatt','Ximena','Yosef','Zephyr','Adrian','Bailey',
	        'Carmen','Dylan','Ella','Finn','Giselle','Holden','India','Jared','Kylie','Lucas'
	    );
	    $last_names = array(
	        'Smith','Johnson','Williams','Brown','Jones','Miller','Davis','Garcia','Rodriguez','Wilson',
	        'Anderson','Thomas','Taylor','Moore','Jackson','Martin','Lee','Perez','Thompson','White',
	        'Harris','Sanchez','Clark','Ramirez','Lewis','Robinson','Walker','Young','Allen','King',
	        'Wright','Scott','Torres','Nguyen','Hill','Flores','Green','Adams','Nelson','Baker',
	        'Hall','Rivera','Campbell','Mitchell','Carter','Roberts','Gomez','Phillips','Evans','Turner',
	        'Diaz','Parker','Cruz','Edwards','Collins','Reyes','Stewart','Morris','Morales','Murphy',
	        'Cook','Rogers','Gutierrez','Ortiz','Morgan','Cooper','Peterson','Bailey','Reed','Kelly',
	        'Howard','Ramos','Kim','Cox','Ward','Richardson','Watson','Brooks','Chavez','Wood',
	        'James','Bennett','Gray','Mendoza','Ruiz','Hughes','Price','Alvarez','Castillo','Sanders'
	    );

	    // Build combinations
	    foreach ( $first_names as $first ) {
	        foreach ( $last_names as $last ) {
	            $this->name_combinations[] = array(
	                'first_name' => $first,
	                'last_name'  => $last,
	                'display_name' => sprintf( '%s %s', $first, $last ),
	            );
	        }
	    }

	    // Randomize order
	    shuffle( $this->name_combinations );
	    $this->name_pool_initialized = true;
	}

	/**
	 * Return a unique name combination, initializing pool on first call.
	 *
	 * @return array {
	 *     @type string $first_name
	 *     @type string $last_name
	 *     @type string $display_name
	 * }
	 */
	private function generate_unique_name() {
	    if ( ! $this->name_pool_initialized || empty( $this->name_combinations ) ) {
	        $this->name_combinations = array();
	        $this->init_name_pool();
	    }
	    // Pop one combination off the pool
	    return array_pop( $this->name_combinations );
	}
}
