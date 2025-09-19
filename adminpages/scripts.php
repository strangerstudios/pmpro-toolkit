<?php

global $wpdb, $pmprodev_member_tables, $pmprodev_other_tables;

$pmprodev_member_tables = array(
	$wpdb->pmpro_memberships_users,
	$wpdb->pmpro_membership_orders,
	$wpdb->pmpro_discount_codes_uses,
	$wpdb->pmpro_subscriptions,
	$wpdb->pmpro_subscriptionmeta,
);

$pmprodev_other_tables = array(
	$wpdb->pmpro_discount_codes,
	$wpdb->pmpro_discount_codes_levels,
	$wpdb->pmpro_membership_levels,
	$wpdb->pmpro_memberships_categories,
	$wpdb->pmpro_memberships_pages,
	$wpdb->pmpro_groups,
	$wpdb->pmpro_membership_levels_groups,
);

$clean_up_actions = array(
	'pmprodev_clean_member_tables'	=> array(
		'label' => __( 'Delete Member Data', 'pmpro-toolkit' ),
		'description' => __( 'Delete all member data.', 'pmpro-toolkit' ),
		'subtext' => __( 'This script deletes data from the wp_pmpro_memberships_users, wp_pmpro_membership_orders, wp_pmpro_discount_codes_uses, wp_pmpro_subscriptions and wp_pmpro_subscriptionmeta tables.', 'pmpro-toolkit' ),
		'message' => __( 'Member tables have been truncated.', 'pmpro-toolkit' ),
	),
	'pmprodev_clean_level_data'	=> array(
		'label' => __( 'Reset Membership Settings', 'pmpro-toolkit' ),
		'description' => __( 'Delete all membership level, content protection, and discount code settings.', 'pmpro-toolkit' ),
		'subtext' => __( 'This script deletes data from the wp_pmpro_discount_codes, wp_pmpro_discount_codes_levels, wp_pmpro_membership_levels, wp_pmpro_memberships_categories, and wp_pmpro_memberships_pages tables.', 'pmpro-toolkit' ),
		'message' => __( 'Level and discount code tables have been truncated.', 'pmpro-toolkit' )
	),
	'pmprodev_scrub_member_data'	=> array(
		'label' => __( 'Scrub Member Data', 'pmpro-toolkit' ),
		'description' => __( 'Scrub all member emails and transaction IDs.', 'pmpro-toolkit' ),
		'subtext' => __( 'This script updates all non-admins in the wp_users and wp_pmpro_membership_orders tables to anonymize their email addresses and order transaction IDs. This may time out on slow servers or sites with large numbers of users.', 'pmpro-toolkit' ),
		'message' => __( 'Scrubbing user data...', 'pmpro-toolkit' )
	),
	'pmprodev_delete_users'	=> array(
		'label' => __( 'Delete Users', 'pmpro-toolkit' ),
		'description' => __( 'Delete non-admin users.', 'pmpro-toolkit' ),
		'subtext' => __( 'This script deletes from wp_users and wp_usermeta tables directly. This may time out on slow servers or sites with large numbers of users.', 'pmpro-toolkit' ),
		'message' => __( 'Deleting non-admins...', 'pmpro-toolkit' )
	),
	'pmprodev_clean_pmpro_options'	=> array(
		'label' => __( 'Reset Options', 'pmpro-toolkit' ),
		'description' => __( 'Delete all PMPro options.', 'pmpro-toolkit' ),
		'subtext' => __( 'This script deletes any option prefixed with pmpro_ in the wp_options table, excluding the pmpro_db_version and assigned PMPro pages.', 'pmpro-toolkit' ),
		'message' => __( 'Options deleted.', 'pmpro-toolkit' )
	),
	'pmprodev_clear_vvl_report'	=> array(
		'label' => __( 'Clear Logins Report', 'pmpro-toolkit' ),
		'description' => __( 'Clear and reset all visits, views, and logins report data.', 'pmpro-toolkit' ),
		'message' => __( 'Visits, Views, and Logins report cleared.', 'pmpro-toolkit' )
	),
	'pmprodev_delete_test_orders' => array(
		'label' => __( 'Delete Test Orders', 'pmpro-toolkit' ),
		'description' => __( 'Delete all orders made through the testing or sandbox gateway environment.', 'pmpro-toolkit' ),
		'subtext' => __( 'This includes any test subscriptions.', 'pmpro-toolkit' ),
		'message' => __( 'Test orders deleted.', 'pmpro-toolkit' )
	),
	'pmprodev_clear_cached_report_data' => array(
		'label' => __( 'Clear cached report data', 'pmpro-toolkit' ),
		'description' => __( 'Clear cached report data.', 'pmpro-toolkit' ),
		'message' => __( 'Cached report data cleared.', 'pmpro-toolkit' )
	),
);

$level_actions = array(
	'pmprodev_move_level' => array(
	'label' => __( 'Change Membership Level', 'pmpro-toolkit' ),
	'description' => __( 'Change all members with a specific level ID to another level ID. Note: running this script will NOT cancel any recurring subscriptions.', 'pmpro-toolkit' ),
	'message' => __( 'Users updated. Running pmpro_after_change_membership_level filter for all users...', 'pmpro-toolkit' )
	),
	'pmprodev_give_level' => array(
		'label' => __( 'Assign Membership Level', 'pmpro-toolkit' ),
		'description' => __( 'Assign a specific membership level to all users without an active membership. Note: This script only assigns membership via the database and does NOT fire any pmpro_change_membership_level hooks or process payments.', 'pmpro-toolkit' ),
		'message' => __( '%s users were given level %s', 'pmpro-toolkit' )
	),
	'pmprodev_cancel_level' => array(
		'label' => __( 'Cancel Membership', 'pmpro-toolkit' ),
		'description' => __( 'Cancel all members with a specific level ID. Note: This script WILL also cancel any recurring subscriptions.', 'pmpro-toolkit' ),
		'message' => __( 'Cancelling users...', 'pmpro-toolkit' )
	),
);

$other_actions = array(
	'pmprodev_copy_memberships_pages' => array(
		'label' => __( 'Copy Content Restrictions', 'pmpro-toolkit' ),
		'description' => __( 'Make all pages that require a specific level ID also require another level ID.', 'pmpro-toolkit' ),
		'message' => __( 'Content restrictions copied.', 'pmpro-toolkit' )
	),
	'pmprodev_delete_incomplete_orders' => array(
		'label' => __( 'Delete Incomplete Orders', 'pmpro-toolkit' ),
		'description' => __( 'Delete all orders in token, pending, or review status that are older than a specified number of days.', 'pmpro-toolkit' ),
		'message' => __( '%d orders deleted.', 'pmpro-toolkit' )
	)
);

// Suppress the admin page markup when running under WP-CLI so that requiring this
// file for function access (e.g. in Toolkit_Commands) doesn't dump HTML in the terminal.
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) : ?>
<h2><?php esc_html_e( 'Database Scripts', 'pmpro-toolkit' ); ?></h2>
<p><?php esc_html_e( 'Toolkit scripts allow you to clean up, delete, duplicate, or anonymize data in your membership site. We recommend only running one script at a time. Check a setting below and click save to run the script.', 'pmpro-toolkit' ); ?></p>
<p><?php esc_html_e( 'Note: Running the scripts below will delete or modify data in your database. These changes cannot be reversed. Please take a site backup before running a script.', 'pmpro-toolkit' ); ?></p>
<form id="form-scripts" method="post" action="">
<?php wp_nonce_field( 'pmpro_toolkit_script_action', 'pmpro_toolkit_scripts_nonce' ); ?>
	<div class="pmpro_section" data-visibility="shown" data-activated="true">
		<div class="pmpro_section_toggle">
			<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
				<span class="dashicons dashicons-arrow-up-alt2"></span>
				<?php esc_html_e( 'Database Clean Up Scripts', 'pmpro-toolkit' ); ?>
			</button>
		</div>
		<div class="pmpro_section_inside">
			<table class="form-table">
				<tbody>
				<?php foreach ( $clean_up_actions as $action => $details ) : ?>
					<tr>
						<th scope="row"><?php echo esc_html( $details['label'] ); ?></th>
						<td>
							<input type="checkbox" id="<?php echo esc_attr( $action ); ?>" name="<?php echo esc_attr( $action ); ?>" value="1">
							<label for="<?php echo esc_attr( $action ); ?>"><?php echo wp_kses_post( $details['description'] ); ?></label>
							<?php if ( ! empty( $details['subtext'] ) ) : ?>
								<p class="description"><?php echo wp_kses_post( $details['subtext'] ); ?></p>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
	<div class="pmpro_section" data-visibility="shown" data-activated="true">
		<div class="pmpro_section_toggle">
			<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
				<span class="dashicons dashicons-arrow-up-alt2"></span>
				<?php esc_html_e( 'Member Scripts', 'pmpro-toolkit' ); ?>
			</button>
		</div>
		<div class="pmpro_section_inside">
			<table class="form-table">
				<tbody>
				<tr>
					<th scope="row"><?php echo esc_html( $level_actions['pmprodev_move_level']['label'] ); ?></th>
					<td>
						<input type="checkbox" id="pmprodev_move_level" name="pmprodev_move_level" value="1">
						<label for="pmprodev_move_level">
							<?php
								printf(
									/* translators: %s: script name */
									esc_html__( 'Run the %s script.', 'pmpro-toolkit' ),
									esc_html( $level_actions['pmprodev_move_level']['label'] )
								);
							?>
						</label>
						<div id="pmprodev_move_level_actions" style="display: none;">
							<p>
								<?php esc_html_e( 'From Level ID:', 'pmpro-toolkit' ); ?>
								<input type="number" name="move_level_a" value="" size="5" step="1">
								<?php esc_html_e( 'To Level ID:', 'pmpro-toolkit' ); ?>
								<input type="number" name="move_level_b" value="" size="5">
							</p>
							<p class="description"><?php echo esc_html( $level_actions['pmprodev_move_level']['description'] ); ?></p>
						</div>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php echo esc_html( $level_actions['pmprodev_give_level']['label'] ); ?></th>
					<td>
						<input type="checkbox" id="pmprodev_give_level" name="pmprodev_give_level" value="1">
						<label for="pmprodev_give_level">
							<?php
								printf(
									/* translators: %s: script name */
									esc_html__( 'Run the %s script.', 'pmpro-toolkit' ),
									esc_html( $level_actions['pmprodev_give_level']['label'] )
								);
							?>
						</label>
						<div id="pmprodev_give_level_actions" style="display: none;">
							<p>
								<?php esc_html_e( 'Level ID:', 'pmpro-toolkit' ); ?>
								<input type="number" name="give_level_id" value="" size="5">
								<?php esc_html_e( 'Start Date:', 'pmpro-toolkit' ); ?>
								<input type="date" name="give_level_startdate" value="">
								<?php esc_html_e( 'End Date:', 'pmpro-toolkit' ); ?>
								<input type="date" name="give_level_enddate" value="">
							</p>
							<p class="description"><?php echo esc_html( $level_actions['pmprodev_give_level']['description'] ); ?></p>
						</div>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php echo esc_html( $level_actions['pmprodev_cancel_level']['label'] ); ?></th>
					<td>
						<input type="checkbox" id="pmprodev_cancel_level" name="pmprodev_cancel_level" value="1">
						<label for="pmprodev_cancel_level">
							<?php
								printf(
									/* translators: %s: script name */
									esc_html__( 'Run the %s script.', 'pmpro-toolkit' ),
									esc_html( $level_actions['pmprodev_cancel_level']['label'] )
								);
							?>
						</label>
						<div id="pmprodev_cancel_level_actions" style="display: none;">
							<p>
								<?php esc_html_e( 'Level ID:', 'pmpro-toolkit' ); ?>
								<input type="number" name="cancel_level_id" value="" size="5">
							</p>
							<p class="description"><?php echo esc_html( $level_actions['pmprodev_cancel_level']['description'] ); ?></p>
						</div>
					</td>
				</tr>
				</tbody>
			</table>
		</div>
	</div>
	<div class="pmpro_section" data-visibility="shown" data-activated="true">
		<div class="pmpro_section_toggle">
			<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
				<span class="dashicons dashicons-arrow-up-alt2"></span>
				<?php esc_html_e( 'Other Scripts', 'pmpro-toolkit' ); ?>
			</button>
		</div>
		<div class="pmpro_section_inside">
			<table class="form-table">
				<tr>
					<th scope="row"><?php echo esc_html( $other_actions['pmprodev_copy_memberships_pages']['label'] ); ?></th>
					<td>
						<input type="checkbox" id="pmprodev_copy_memberships_pages" name="pmprodev_copy_memberships_pages" value="1">
						<label for="pmprodev_copy_memberships_pages">
							<?php
								printf(
									/* translators: %s: script name */
									esc_html__( 'Run the %s script.', 'pmpro-toolkit' ),
									esc_html( $other_actions['pmprodev_copy_memberships_pages']['label'] )
								);
							?>
						</label>
						<div id="pmprodev_copy_memberships_pages_actions" style="display: none;">
							<p>
								<?php esc_html_e( 'Copy From Level ID:', 'pmpro-toolkit' ); ?>
								<input type="number" name="copy_memberships_pages_from" value="">
								<?php esc_html_e( 'Copy To Level ID:', 'pmpro-toolkit' ); ?>
								<input type="number" name="copy_memberships_pages_to" value="">
							</p>
							<p class="description"><?php echo esc_html( $other_actions['pmprodev_copy_memberships_pages']['description'] ); ?></p>
						</div>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php echo esc_html( $other_actions['pmprodev_delete_incomplete_orders']['label'] ); ?></th>
					<td>
						<input type="checkbox" id="pmprodev_delete_incomplete_orders" name="pmprodev_delete_incomplete_orders" value="1">
						<label for="pmprodev_delete_incomplete_orders">
							<?php
								printf(
									/* translators: %s: script name */
									esc_html__( 'Run the %s script.', 'pmpro-toolkit' ),
									esc_html( $other_actions['pmprodev_delete_incomplete_orders']['label'] )
								);
							?>
						</label>
						<div id="pmprodev_delete_incomplete_orders_actions" style="display: none;">
							<p>
								<?php esc_html_e( 'Days:', 'pmpro-toolkit' ); ?>
								<input type="number" name="delete_incomplete_orders_days" value="">
							</p>
							<p class="description"><?php echo esc_html( $other_actions['pmprodev_delete_incomplete_orders']['description'] ); ?></p>
						</div>
					</td>
				</tr>
				</tbody>
			</table>
		</div>
	</div>
	<p class="submit">
		<input type="submit" name="submit" class="button-primary" value="<?php esc_attr_e( 'Run Selected Tools', 'pmpro-toolkit' ); ?>" />
	</p>
</form>

<script type="text/javascript">
// Show/hide actions for each script below
	const actions = [
		'move_level',
		'give_level',
		'cancel_level',
		'copy_memberships_pages',
		'delete_incomplete_orders'
	];

	jQuery(document).ready(function($) {
		actions.forEach( action => {
			$('#pmprodev_' + action ).change(function() {
				$('#pmprodev_' + action + '_actions').toggle( $(this).is( ':checked' ) );
			});
		});
	});
</script>
<?php endif; // End suppress admin markup for WP-CLI.

// Collect all actions into a single array.
$actions = array_merge( $clean_up_actions, $level_actions, $other_actions );
foreach ( $actions as $action => $options ) {
	if ( ! empty( $_POST[ $action ] ) ) {
		//Check nonce
		if ( ! isset( $_POST['pmpro_toolkit_scripts_nonce'] ) ||
		! check_admin_referer( 'pmpro_toolkit_script_action', 'pmpro_toolkit_scripts_nonce' ) ) {
			pmprodev_output_message( __( 'Security check failed.', 'pmpro-toolkit' ), 'error' );
			return;
		}
		call_user_func( $action, $options[ 'message' ] );
	}
}

/**
 * Output a message to the user.
 *
 * @param string $message The message to display.
 * @since 1.0
 * @return void
 */
function pmprodev_clean_member_tables( $message ) {
	global $wpdb, $pmprodev_member_tables;
	foreach ( $pmprodev_member_tables as $table ) {
		$wpdb->query( "TRUNCATE $table" );
	}
	pmprodev_clear_cached_report_data( '' );
	pmprodev_output_message( $message );
}

/**
 * Reset all membership level, content protection, and discount code settings.
 *
 * @param string $message The message to display after the process is complete.
 * @since 1.0
 * @return void
 */
function pmprodev_clean_level_data( $message ) {
	global $wpdb, $pmprodev_other_tables;
	foreach ( $pmprodev_other_tables as $table ) {
		$wpdb->query( "TRUNCATE $table" );
	}
	pmprodev_output_message( $message );
}

/**
 * Scrub all member emails and transaction IDs.
 *
 * @param string $message The message to display after the process is complete.
 * @since 1.0
 * @return void
 */
function pmprodev_scrub_member_data( $message ) {
	global $wpdb;

	pmprodev_output_message( $message );

	$batch_size   = 250;
	$admin_email  = get_option( 'admin_email' );
	$counter      = 0; // Global sequential counter to keep deterministic unique values.
	$offset       = 0;

	// Pre-calc prefix capability meta key for more efficient role detection if needed later.
	$caps_meta_key = $wpdb->prefix . 'capabilities';

	while ( true ) {
		// Get next batch of user IDs that have not yet been scrubbed.
		$user_ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT ID FROM {$wpdb->users} WHERE user_email NOT LIKE '%%+scrub%%' ORDER BY ID ASC LIMIT %d OFFSET %d",
			$batch_size,
			$offset
		) );

		if ( empty( $user_ids ) ) {
			break; // No more users to process.
		}

		$email_cases = array();
		$email_ids   = array();
		$tx_cases    = array(); // Will map user_id => transaction ID for CASE statements.

		foreach ( $user_ids as $user_id ) {
			$counter++;
			$new_transaction_id     = 'SCRUBBED-' . $counter;
			$tx_cases[ $user_id ]    = esc_sql( $new_transaction_id );

			// Only anonymize email for non-admins (same logic as original code).
			if ( ! user_can( $user_id, 'manage_options' ) ) {
				$new_email      = str_replace( '@', '+scrub' . $counter . '@', $admin_email );
				$email_cases[]  = 'WHEN ' . (int) $user_id . " THEN '" . esc_sql( $new_email ) . "'";
				$email_ids[]    = (int) $user_id;
			}

			// Keep meta updates (ensures a row is created if missing) – still one of the cheaper operations.
			update_user_meta( $user_id, 'pmpro_braintree_customerid', $new_transaction_id );
			update_user_meta( $user_id, 'pmpro_stripe_customerid', $new_transaction_id );
		}

		// Batch update user emails where applicable.
		if ( ! empty( $email_ids ) ) {
			$wpdb->query( "UPDATE {$wpdb->users} SET user_email = CASE ID " . implode( ' ', $email_cases ) . ' END WHERE ID IN (' . implode( ',', $email_ids ) . ')' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		// Build CASE fragment for transaction IDs.
		if ( ! empty( $tx_cases ) ) {
			$when_fragments = array();
			foreach ( $tx_cases as $uid => $txid ) {
				$when_fragments[] = 'WHEN ' . (int) $uid . " THEN '" . $txid . "'"; // Already esc_sql above.
			}
			$case_sql = 'CASE user_id ' . implode( ' ', $when_fragments ) . ' END';
			$user_id_list = implode( ',', array_map( 'intval', array_keys( $tx_cases ) ) );

			// Update orders payment_transaction_id only where not empty.
			$wpdb->query( "UPDATE {$wpdb->pmpro_membership_orders} SET payment_transaction_id = IF(payment_transaction_id <> '', $case_sql, payment_transaction_id) WHERE user_id IN ($user_id_list)" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			// Update orders subscription_transaction_id only where not empty.
			$wpdb->query( "UPDATE {$wpdb->pmpro_membership_orders} SET subscription_transaction_id = IF(subscription_transaction_id <> '', $case_sql, subscription_transaction_id) WHERE user_id IN ($user_id_list)" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			// Update subscriptions table subscription_transaction_id only where not empty.
			$wpdb->query( "UPDATE {$wpdb->pmpro_subscriptions} SET subscription_transaction_id = IF(subscription_transaction_id <> '', $case_sql, subscription_transaction_id) WHERE user_id IN ($user_id_list)" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		// Progress indicator per batch (instead of per user to reduce output overhead).
		echo '. ';

		$offset += $batch_size;
	}

	pmprodev_process_complete();
}

/**
 * Delete all non-admin users.
 *
 * @param string $message The message to display after the process is complete.
 * @since 1.0
 * @return void
 */
function pmprodev_delete_users( $message ) {
	global $wpdb;
	pmprodev_output_message( $message );
	$user_ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->users}" );
	foreach ( $user_ids as $user_id ) {
		if ( ! user_can( $user_id, 'manage_options' ) ) {
			$wpdb->query( "DELETE FROM {$wpdb->users} WHERE ID = " . intval( $user_id ) . " LIMIT 1" );
			$wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE user_id = " . intval( $user_id ) );
			echo '. ';
		}
	}
	pmprodev_process_complete();
}

/**
 * Reset all PMPro options.
 *
 * @param string $message The message to display after the process is complete.
 * @since 1.0
 * @return void
 */
function pmprodev_clean_pmpro_options( $message ) {
	delete_option( 'pmpro_db_version' );
	delete_option( 'pmpro_membership_levels' );
	delete_option( 'pmpro_non_member_page_settings' );
	delete_option( 'pmpro_options' );
	delete_option( 'pmpro_pages' );
	delete_option( 'pmpro_upgrade' );
	delete_option( 'pmpro_initial_payment' );
	delete_option( 'pmpro_recurring_payment' );
	delete_option( 'pmpro_billing_amount' );
	delete_option( 'pmpro_cycle_number' );
	delete_option( 'pmpro_cycle_period' );
	delete_option( 'pmpro_billing_limit' );
	delete_option( 'pmpro_trial_amount' );
	delete_option( 'pmpro_trial_limit' );
	delete_option( 'pmpro_code_id' );
	delete_option( 'pmpro_checkout_box_' );
	delete_option( 'pmpro_level_cost_text' );
	delete_option( 'pmpro_levels_page_settings' );
	delete_option( 'pmpro_discount_code_name' );
	delete_option( 'pmpro_discount_code_page_settings' );
	delete_option( 'pmpro_discount_code_id' );
	delete_option( 'pmpro_discount_code' );
	delete_option( 'pmpro_discount_code_page_settings' );
	delete_option( 'pmpro_discount_code' );
	pmprodev_output_message( $message );
}

/**
 * Clear and reset all visits, views, and logins report data.
 *
 * @param string $message The message to display after the process is complete.
 * @since 1.0
 * @return string The message to display after the process is complete.
 */
function pmprodev_clear_vvl_report( $message ) {
	global $wpdb;
	$wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key = 'pmpro_visits' OR meta_key = 'pmpro_views' OR meta_key = 'pmpro_logins'");
	$wpdb->query("DELETE FROM $wpdb->options WHERE option_name = 'pmpro_visits' OR option_name = 'pmpro_views' OR option_name = 'pmpro_logins'");
	pmprodev_output_message( $message );
}

/**
 * Delete all orders with a sandbox gateway environment and tries to clean up subscriptions at the same time.
 *
 * @param string $message The message to display after the orders are deleted.
 * @return void
 * @since 1.0
 */
function pmprodev_delete_test_orders( $message ) {
	global $wpdb;
	// Delete the test orders now and sandbox subscriptions.

	// Delete the order meta first.
	$wpdb->query( "DELETE FROM {$wpdb->pmpro_membership_ordermeta} WHERE pmpro_membership_order_id IN (SELECT id FROM {$wpdb->pmpro_membership_orders} WHERE gateway_environment = 'sandbox')" );
	$wpdb->query( "DELETE FROM {$wpdb->pmpro_membership_orders} WHERE gateway_environment = 'sandbox'" );

	// Delete subscription meta AND subscriptions.
	$wpdb->query( "DELETE FROM {$wpdb->pmpro_subscriptionmeta} WHERE pmpro_subscription_id IN (SELECT id FROM {$wpdb->pmpro_subscriptions} WHERE gateway_environment = 'sandbox')" );
	$wpdb->query( "DELETE FROM {$wpdb->pmpro_subscriptions} WHERE gateway_environment = 'sandbox'" );
	pmprodev_output_message( $message );
}

/** Clear cached report data
 *
 * @param string $message The message to display after the process is complete.
 * @since 1.0
 * @return void
 */
function pmprodev_clear_cached_report_data( $message ) {
	pmpro_report_memberships_delete_transients();
	pmpro_report_sales_delete_transients();

	pmprodev_output_message( $message );
}

/**
 * Move all users with a specific membership level to another membership level.
 *
 * @param string $message The message to display after the process is complete.
 * @since 1.0
 * @return void
 */
function pmprodev_move_level( $message ) {
	global $wpdb;

	$from_level_id = intval( $_REQUEST['move_level_a'] );
	$to_level_id = intval( $_REQUEST['move_level_b'] );

	//Bail if the level IDs are invalid
	if ( $from_level_id < 1 || $to_level_id < 1 ) {
		pmprodev_output_message( __( 'Please enter a level ID > 0 for each option.', 'pmpro-toolkit' ), 'warning' );
		pmprodev_expand_actions( 'pmprodev_move_level' );
		return;
	}

	$user_ids = $wpdb->get_col( "SELECT user_id FROM $wpdb->pmpro_memberships_users WHERE membership_id = $from_level_id AND status = 'active'" );

	//Bail if no users found
	if ( empty( $user_ids ) ) {
		pmprodev_output_message( sprintf( __( 'Couldn\'t find users with level ID %d.', 'pmpro-toolkit' ), $from_level_id ), 'warning' );
		pmprodev_expand_actions( 'pmprodev_move_level' );
		return;
	}

	$wpdb->query( "UPDATE $wpdb->pmpro_memberships_users SET membership_id = $to_level_id WHERE membership_id = $from_level_id AND status = 'active';" );
	pmprodev_output_message( $message );
	foreach ( $user_ids as $user_id ) {
		do_action( 'pmpro_after_change_membership_level', $to_level_id, $user_id, $from_level_id );
	}

	pmprodev_process_complete();

}

/**
 * Given a membership level ID, start date and end date  assign that level to all users without an active membership.
 *
 * @param string $message The message to display after the process is complete.
 * @since 1.0
 * @return void
 */
function pmprodev_give_level( $message ) {
	global $wpdb;

	$give_level_id = intval( $_REQUEST['give_level_id'] );
	$give_level_startdate = sanitize_text_field( $_REQUEST['give_level_startdate'] );
	$give_level_enddate = sanitize_text_field( $_REQUEST['give_level_enddate'] );

	//bail if the level ID is invalid
	if ( $give_level_id < 1 || empty( $give_level_startdate ) ) {
		pmprodev_output_message( __( 'Please enter a valid level ID and start date.', 'pmpro-toolkit' ), 'warning' );
		pmprodev_expand_actions( 'pmprodev_give_level' );
		return;
	}

	$sqlQuery = $wpdb->prepare(
		"INSERT INTO {$wpdb->pmpro_memberships_users} (user_id, membership_id, status, startdate, enddate)
		SELECT u.ID, %d, 'active', %s, %s
		FROM {$wpdb->users} u 
		LEFT JOIN {$wpdb->pmpro_memberships_users} mu
		ON u.ID = mu.user_id 
		AND mu.status = 'active' 
		WHERE mu.id IS NULL;",
		$give_level_id,
		$give_level_startdate,
		$give_level_enddate
	);

	$wpdb->query( $sqlQuery );

	$message = sprintf( $message, $wpdb->rows_affected, $give_level_id );

	pmprodev_output_message( $message );
}

/**
 * Cancel all users with a specific membership level.
 *
 * @param string $message The message to display after the process is complete.
 * @since 1.0
 * @return void
 */
function pmprodev_cancel_level( $message ) {
	global $wpdb;

	$cancel_level_id = intval( $_REQUEST['cancel_level_id'] );
	$user_ids = $wpdb->get_col( "SELECT user_id FROM $wpdb->pmpro_memberships_users WHERE membership_id = $cancel_level_id AND status = 'active'" );
	// Bail if the level ID is invalid
	if ( $cancel_level_id < 1 ) {
		pmprodev_output_message( __( 'Please enter a valid level ID.', 'pmpro-toolkit' ), 'warning' );
		pmprodev_expand_actions( 'pmprodev_cancel_level' );
		return;
	}

	//Bail if no users found
	if ( empty( $user_ids ) ) {
		pmprodev_output_message( sprintf( __( 'Couldn\'t find users with level ID %d.', 'pmpro-toolkit' ), $cancel_level_id ), 'warning' );
		pmprodev_expand_actions( 'pmprodev_cancel_level' );
	}

	$message = sprintf( $message, count( $user_ids ) );
	pmprodev_output_message( $message );
	foreach ( $user_ids as $user_id ) {
		pmpro_cancelMembershipLevel( $cancel_level_id, $user_id );
	}

	pmprodev_process_complete();
}

/**
 * Copy content restrictions from one membership level to another.
 *
 * @param string $message The message to display after the process is complete.
 * @since 1.0
 * @return void
 */
function pmprodev_copy_memberships_pages( $message ) {
	global $wpdb;

	$from_level_id = intval( $_REQUEST['copy_memberships_pages_from'] );
	$to_level_id = intval( $_REQUEST['copy_memberships_pages_to'] );

	$wpdb->query(
		$wpdb->prepare(
			"INSERT IGNORE INTO {$wpdb->pmpro_memberships_pages} (membership_id, page_id) 
			SELECT %d, page_id FROM {$wpdb->pmpro_memberships_pages} WHERE membership_id = %d",
			$to_level_id,
			$from_level_id
		)
	);

	pmprodev_output_message( $message );
}

/**
 * Delete all orders in token, pending, or review status that are older than a specified number of days.
 *
 * @param string $message The message to display after the process is complete.
 * @since 1.0
 * @return void
 */
function pmprodev_delete_incomplete_orders( $message ) {
	global $wpdb;

	if ( empty( $_REQUEST['delete_incomplete_orders_days']  ) ) {
		pmprodev_output_message( __( 'Please enter a number of days.', 'pmpro-toolkit' ), 'warning' );
		pmprodev_expand_actions( 'pmprodev_delete_incomplete_orders' );
		return;
	}

	$days = intval( $_REQUEST['delete_incomplete_orders_days'] );

	if ( ! is_numeric( $days ) || intval( $days ) < 1 ) {
		pmprodev_output_message( __( 'Please enter a valid number of days.', 'pmpro-toolkit' ), 'warning' );
		pmprodev_expand_actions( 'pmprodev_delete_incomplete_orders' );
		return;
	}

	$deleted = $wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->pmpro_membership_orders} WHERE status IN('token', 'pending', 'review') AND timestamp < %s",
			date( 'Y-m-d', strtotime( "-$days days" ) )
		)
	);

	pmprodev_output_message( sprintf( $message, (int) $deleted ) );
}

/**
 * Output a message to the admin screen.
 *
 * @param string $message The message to display.
 * @param string $type The type of message to display.
 * @since 1.0
 * @return void
 */
function pmprodev_output_message( $message, $type = 'success' ) {
	// If there's no message or we're running in CLI
	if ( empty( $message ) || defined( 'WP_CLI' ) ) {
		return;
	}
	echo '<div class="notice notice-' . esc_attr( $type ) . '"><p>' . esc_html( $message ) . '</p></div>';
}

/**
 * Output a message to the screen when a process is complete.
 *
 * @since 1.0
 * @return void
 */
function pmprodev_process_complete() {
	// We are running in WP-CLI, suppress this script.
	if ( defined( 'WP_CLI' ) ) {
		return;
	}
	echo '<div class="notice notice-success"><p>' . esc_html__( 'Process complete.', 'pmpro-toolkit' ) . '</p></div>';
}

/**
 * Expand the actions after dom is ready.
 *
 * @param string $action The action to expand.
 * @since 1.0
 * @return void
 */
function pmprodev_expand_actions( $action ) {
	// We are running in WP-CLI, suppress this script.
	if ( defined( 'WP_CLI' ) ) {
		return;
	}
	?>
	<script>
		jQuery(document).ready(function($) {
			$('#<?php echo esc_attr( $action ); ?>').prop('checked', true);
			$('#<?php echo esc_attr( $action ); ?>_actions').show();
		});
	</script>
	<?php
}
?>
