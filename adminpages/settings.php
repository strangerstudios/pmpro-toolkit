<?php
	// Only admins can access this page.
	if( !function_exists( "current_user_can" ) || ( !current_user_can( "manage_options" ) ) ) {
		die( esc_html__( "You do not have permissions to perform this action.", 'pmpro-toolkit' ) );
	}

	global $msg, $msgt, $pmprodev_options;
	$pmpro_db_version = get_option( 'pmpro_db_version' );


	// Bail if nonce field isn't set.
	if ( !empty( $_REQUEST['savesettings'] ) && ( empty( $_REQUEST[ 'pmpro_toolkit_nonce' ] ) 
		|| !check_admin_referer( 'savesettings', 'pmpro_toolkit_nonce' ) ) ) {
		$msg = -1;
		$msgt = __( "Are you sure you want to do that? Try again.", 'pmpro-toolkit' );
		unset( $_REQUEST[ 'savesettings' ] );
	}

	// Save settings.
	if( !empty( $_REQUEST['savesettings'] ) ) {
		$pmprodev_options['redirect_email'] = sanitize_text_field( $_POST['pmprodev_options']['redirect_email'] );
		$pmprodev_options['ipn_debug'] = sanitize_text_field( $_POST['pmprodev_options']['ipn_debug'] );
		$pmprodev_options['checkout_debug_when'] = sanitize_text_field( $_POST['pmprodev_options']['checkout_debug_when'] );
		$pmprodev_options['checkout_debug_email'] = sanitize_text_field( $_POST['pmprodev_options']['checkout_debug_email'] );
		$pmprodev_options['performance_endpoints'] = sanitize_text_field( $_POST['pmprodev_options']['performance_endpoints'] );

		if( isset( $_POST['pmprodev_options']['expire_memberships'] ) ) {
			$expire_memberships = intval( $_POST['pmprodev_options']['expire_memberships'] );
		} else {
			$expire_memberships = 0;
		}

		$pmprodev_options['expire_memberships'] = $expire_memberships;

		if( isset( $_POST['pmprodev_options']['expiration_warnings'] ) ) {
			$expiration_warnings = intval( $_POST['pmprodev_options']['expiration_warnings'] );
		} else {
			$expiration_warnings = 0;
		}

		$pmprodev_options['expiration_warnings'] = $expiration_warnings;

		if( isset( $_POST['pmprodev_options']['payment_reminders'] ) ) {
			$payment_reminders = intval( $_POST['pmprodev_options']['payment_reminders'] );
		} else {
			$payment_reminders = 0;
		}
		$pmprodev_options['payment_reminders'] = $payment_reminders;

		if( isset( $_POST['pmprodev_options']['credit_card_expiring'] ) ) {
			$credit_card_expiring = intval( $_POST['pmprodev_options']['credit_card_expiring'] );
		} else {
			$credit_card_expiring = 0;
		}

		$pmprodev_options['credit_card_expiring'] = $credit_card_expiring;

		if( isset( $_POST['pmprodev_options']['generate_info'] ) ) {
			$generate_info = intval( $_POST['pmprodev_options']['generate_info'] );
		} else {
			$generate_info = 0;
		}

		$pmprodev_options['generate_info'] = $generate_info;

		if( isset( $_POST['pmprodev_options']['ip_throttling'] ) ) {
			$ip_throttling = sanitize_text_field( $_POST['pmprodev_options']['ip_throttling'] );
		} else {
			$ip_throttling = '';
		}

		$pmprodev_options['ip_throttling'] = $ip_throttling;

		update_option( "pmprodev_options", $pmprodev_options );

		// Assume success.
		$msg = true;
		$msgt = __( "Your developer's toolkit settings have been updated.", 'pmpro-toolkit' );

		// Show the contextual messages on our admin pages.
		if ( ! empty( $msg ) ) { ?>
			<div id="message" class="<?php if($msg > 0) echo "updated fade"; else echo "error"; ?>"><p><?php echo wp_kses_post( $msgt );?></p></div>
			<?php
		}
	}
?>
<h2><?php esc_html_e( 'Toolkit Options', 'pmpro-toolkit' ); ?></h2>
<form action="" method="POST" enctype="multipart/form-data">
	<?php wp_nonce_field( 'savesettings', 'pmpro_toolkit_nonce' );?>
	<!-- Email debugging section -->
	<div class="pmpro_section" data-visibility="shown" data-activated="true">
		<div class="pmpro_section_toggle">
			<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
				<span class="dashicons dashicons-arrow-up-alt2"></span>
				<?php esc_html_e( 'Email Debugging', 'pmpro-toolkit' ); ?>
			</button>
		</div>
		<div class="pmpro_section_inside">
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row" valign="top">
							<label for="pmprodev_options[redirect_email]"> <?php esc_html_e( 'Redirect PMPro Emails', 'pmpro-toolkit' ); ?></label>
						</th>
						<td>
							<input type="email" id="pmprodev_options[redirect_email]" name="pmprodev_options[redirect_email]" value="<?php echo esc_attr( $pmprodev_options['redirect_email'] ); ?>" class="regular-text">
							<p class="description"><?php esc_html_e( 'Redirect all Paid Memberships Pro emails to a specific address.', 'pmpro-toolkit' ); ?></p>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>

	<!--Scheduled Cron Job / Action Scheduler Debugging section -->
	<div class="pmpro_section" data-visibility="shown" data-activated="true">
		<div class="pmpro_section_toggle">
			<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
				<span class="dashicons dashicons-arrow-up-alt2"></span>
					<?php if ( class_exists( 'PMPro_Recurring_Actions' ) ) { ?>
					<?php esc_html_e( 'Scheduled Actions Debugging', 'pmpro-toolkit' ); ?>
				<?php } else { ?>
					<?php esc_html_e( 'Scheduled Cron Job Debugging', 'pmpro-toolkit' ); ?>
				<?php } ?>
			</button>
		</div>
		<div class="pmpro_section_inside">
			<table class="form-table">
				<tbody>
					<!--  Expire Memberships row  -->
					<tr>
						<th scope="row" valign="top">
							<label for="expire_memberships"><?php esc_html_e( 'Expire Memberships', 'pmpro-toolkit' ); ?></label>
						</th>
						<td>
							<input id="expire_memberships" type="checkbox"  name="pmprodev_options[expire_memberships]" value="1" <?php checked( $pmprodev_options['expire_memberships'], 1, true ); ?>>
							<label for="expire_memberships">
								<?php esc_html_e( 'Disable checking for expired memberships.', 'pmpro-toolkit' ); ?>
							</label>
						</td>
					</tr>
					<!-- another row but for Expiration Warning -->
					<tr>
						<th scope="row" valign="top">
							<label for="expiration_warnings"><?php esc_html_e( 'Expiration Warnings', 'pmpro-toolkit' ); ?></label>
						</th>
						<td>
							<input id="expiration_warnings" type="checkbox" name="pmprodev_options[expiration_warnings]" value="1" <?php checked( $pmprodev_options['expiration_warnings'], 1, true ); ?>>
							<label for="expiration_warnings">
								<?php esc_html_e( 'Disable sending expiration warnings.', 'pmpro-toolkit' ); ?>
							</label>
						</td>
					<tr>
					<!-- another row but for Payment Reminders -->
					<tr>
						<th scope="row" valign="top">
							<label for="payment_reminders"><?php esc_html_e( 'Payment Reminders', 'pmpro-toolkit' ); ?></label>
						</th>
						<td>
							<input id="payment_reminders" type="checkbox" name="pmprodev_options[payment_reminders]" value="1" <?php checked( $pmprodev_options['payment_reminders'], 1, true ); ?>>
							<label for="payment_reminders">
								<?php esc_html_e( 'Disable sending payment reminders.', 'pmpro-toolkit' ); ?>
							</label>
						</td>
					<tr>
					<!-- another row but for Credit Card Expiring in older versions -->
					<?php if ( $pmpro_db_version < 3.4 ) { ?>
					<tr>
						<th scope="row" valign="top">
							<label for="credit_card_expiring"><?php esc_html_e( 'Credit Card Expiring', 'pmpro-toolkit' ); ?></label>
						</th>
						<td>
							<input id="credit_card_expiring" type="checkbox" name="pmprodev_options[credit_card_expiring]" value="1" <?php checked( $pmprodev_options['credit_card_expiring'], 1, true ); ?>>
							<label for="credit_card_expiring">
								<?php esc_html_e( 'Check to disable the script that checks for expired credit cards.', 'pmpro-toolkit' ); ?>
							</label>
						</td>
					</tr>
					<?php } ?>
				</tbody>
			</table>
		</div>
	</div>

	<!--Gateway/Checkout Debugging section -->
	<div class="pmpro_section" data-visibility="shown" data-activated="true">
		<div class="pmpro_section_toggle">
			<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
				<span class="dashicons dashicons-arrow-up-alt2"></span>
				<?php esc_html_e( 'Gateway/Checkout Debugging', 'pmpro-toolkit' ); ?>
			</button>
		</div>
		<div class="pmpro_section_inside">
			<table class="form-table">
				<tbody>
					<!--  Expire Memberships row  -->
					<tr>
						<th scope="row" valign="top">
							<label for="ipn_debug"><?php esc_html_e( 'Gateway Callback Debug Email', 'pmpro-toolkit' ); ?></label>
						</th>
						<td>
							<input type="email" id="ipn_debug" name="pmprodev_options[ipn_debug]" value="<?php echo esc_attr( $pmprodev_options['ipn_debug'] ); ?>" class="regular-text">
							<p class="description">
								<?php esc_html_e( 'Enter an email address to receive a debugging email every time the gateway processes data.', 'pmpro-toolkit' ); ?>
							</p>
						</td>
					</tr>
					<!-- another row but for Send Checkout Debug Email	 -->
					<tr>
						<th scope="row" valign="top">
							<label for="checkout_debug_email"><?php esc_html_e( 'Send Checkout Debug Email', 'pmpro-toolkit' ); ?></label>
						</th>
						<td>
							<select name="pmprodev_options[checkout_debug_when]">
								<option value="" <?php selected( $pmprodev_options['checkout_debug_when'], '' ); ?>><?php esc_html_e( 'Never (Off)', 'pmpro-toolkit' ); ?></option>
								<option value="on_checkout" <?php selected( $pmprodev_options['checkout_debug_when'], 'on_checkout' ); ?>><?php esc_html_e( 'Yes, Every Page Load', 'pmpro-toolkit' ); ?></option>
								<option value="on_submit" <?php selected( $pmprodev_options['checkout_debug_when'], 'on_submit' ); ?>><?php esc_html_e( 'Yes, Submitted Forms Only', 'pmpro-toolkit' ); ?></option>
								<option value="on_error" <?php selected( $pmprodev_options['checkout_debug_when'], 'on_error' ); ?>><?php esc_html_e( 'Yes, Errors Only', 'pmpro-toolkit' ); ?></option>
							</select>
							<span><?php esc_html_e( 'to email:', 'pmpro-toolkit' ); ?></span>
							<input type="email" id="checkout_debug_email" name="pmprodev_options[checkout_debug_email]" value="<?php echo esc_attr( $pmprodev_options['checkout_debug_email'] ); ?>">
							<p class="description">
								<?php esc_html_e( 'Send an email every time the Checkout page is hit.', 'pmpro-toolkit' ); ?>
								<?php esc_html_e( 'This email will contain data about the request, user, membership level, order, and other information.', 'pmpro-toolkit' ); ?>
							</p>
						</td>
					</tr>
					<!-- Generate Checkout Info row -->
					<tr>
						<th scope="row" valign="top">
							<label for="pmprodev_options[generate_info]"> <?php esc_html_e( 'Enable Generate Checkout Info Button', 'pmpro-toolkit' ); ?></label>
						</th>
						<td>
							<input type="checkbox" id="pmprodev_options[generate_info]" name="pmprodev_options[generate_info]" value="1" <?php checked( $pmprodev_options['generate_info'], 1, true ); ?>>
							<label for="pmprodev_options[generate_info]" class="description"><?php echo esc_html_e( 'Ability to generate checkout info when testing.', 'pmpro-toolkit' ); ?></label>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
	
	<!-- Endpoints Performance Testing Section -->
	<div class="pmpro_section" data-visibility="shown" data-activated="true">
		<div class="pmpro_section_toggle">
			<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
				<span class="dashicons dashicons-arrow-up-alt2"></span>
				<?php esc_html_e( 'Performance Testing Endpoints', 'pmpro-toolkit' ); ?>
			</button>
		</div>
		<div class="pmpro_section_inside">
			<table class="form-table">
				<tbody>
					<!--  Endpoints Performance Testing  -->
					<tr>
						<th scope="row" valign="top">
							<label for="pmprodev_options[performance_endpoints]"><?php esc_html_e( 'Performance Testing Endpoints', 'pmpro-toolkit' ); ?></label>
						</th>
						<td>
							<select name="pmprodev_options[performance_endpoints]" id="pmprodev_options[performance_endpoints]">
								<option value="no" <?php selected( $pmprodev_options['performance_endpoints'], 'no' ); ?>><?php esc_html_e( 'Disabled', 'pmpro-toolkit' ); ?></option>
								<option value="read_only" <?php selected( $pmprodev_options['performance_endpoints'], 'read_only' ); ?>><?php esc_html_e( 'Read Only', 'pmpro-toolkit' ); ?></option>
								<option value="read_write" <?php selected( $pmprodev_options['performance_endpoints'], 'read_write' ); ?>><?php esc_html_e( 'Read and Write', 'pmpro-toolkit' ); ?></option>
							</select>
							<p class="description">
								<span><?php esc_html_e( 'Enable performance testing REST API endpoint for testing purposes.', 'pmpro-toolkit' ); ?></span>

							</p>
						</td>
					</tr>
					<tr>
						<th></th>
						<td id="pmprodev_options_performance_endpoints">
								<strong><?php esc_html_e( 'Endpoint URLs', 'pmpro-toolkit' ); ?>:</strong><br>
								<code><?php echo esc_url( rest_url( 'toolkit/v1/(endpoint-name)' ) ); ?></code><br>
								<em><?php esc_html_e( 'Use GET for read-only tests, POST for read-write tests (if enabled).', 'pmpro-toolkit' ); ?></em>
								
								<?php
								if ( !defined( 'SAVEQUERIES' ) || SAVEQUERIES === false ) { ?>
									<span class="dashicons dashicons-yes" style="margin-top:-3px;"></span><?php esc_html_e( 'The SAVEQUERIES constant is enabled.', 'pmpro-toolkit' ); ?>
								<?php } else { ?>
									<br><br>
									<strong><?php esc_html_e( 'NOTE:', 'pmpro-toolkit' ); ?></strong>
									<?php printf(
									// translators: 1: define() code snippet, 2: wp-config.php filename
									esc_html__( 'To enable full testing capability, make sure to add %1$s to your %2$s file.', 'pmpro-toolkit' ),
									'<code>define( \'SAVEQUERIES\', true );</code>',
									'<code>wp-config.php</code>'
									); ?>
									<br><br>
								<?php } ?>					
						<p>
							<strong><?php esc_html_e( 'Available Endpoints', 'pmpro-toolkit' ); ?>:</strong><br>
								<ul style="margin: 0 0 0 1.5em; padding: 0; list-style: none;">
									<li><code>/wp-json/toolkit/v1/test-general</code></li>
									<li><code>/wp-json/toolkit/v1/test-login</code></li>
									<li><code>/wp-json/toolkit/v1/test-cancel-level</code></li>
									<li><code>/wp-json/toolkit/v1/test-checkout</code></li>
									<li><code>/wp-json/toolkit/v1/test-account-page</code></li>
									<li><code>/wp-json/toolkit/v1/test-report</code></li>
									<li><code>/wp-json/toolkit/v1/test-member-export</code></li>
									<li><code>/wp-json/toolkit/v1/test-search?query=test</code></li>
									<li><code>/wp-json/toolkit/v1/test-change-level</code></li>
								</ul>
						</p>
						</td>
					</tr>
					<tr>
						<th scope="row" valign="top">
							<label for="pmprodev_options[ip_throttling]"> <?php esc_html_e( 'Enable IP Throttling', 'pmpro-toolkit' ); ?></label>
						</th>
						<td>
							<input type="checkbox" id="pmprodev_options[ip_throttling]" name="pmprodev_options[ip_throttling]" value="1" <?php checked( $pmprodev_options['ip_throttling'], 1, true ); ?>>
							<label for="pmprodev_options[ip_throttling]"> <?php esc_html_e( 'Enable IP-based request throttling on public endpoints.', 'pmpro-toolkit' ); ?></label>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
	<p class="submit">
		<input name="savesettings" type="submit" class="button-primary" value="<?php esc_html_e( 'Save Settings', 'pmpro-toolkit' ); ?>">
	</p>
</form>

<script type="text/javascript">
jQuery(document).ready(function($) {
	// Show additional warning when "Read and Write" is selected
	function togglePerformanceWarning() {
		var selectedValue = $('#pmprodev_options\\[performance_endpoints\\]').val();
		var warningDiv = $('#performance-endpoint-warning');
		if (selectedValue === 'read_write') {
			if (warningDiv.length === 0) {
				// Add an ID to the first span if it doesn't exist
				var $span = $('#pmprodev_options\\[performance_endpoints\\]').closest('td').find('span').first();
				if (!$span.attr('id')) {
					$span.attr('id', 'performance-endpoint-span');
				}
				$span.append(
					'<div id="performance-endpoint-warning">' +
					'<strong style="color: #c3524f;"><?php esc_html_e( 'CAUTION:', 'pmpro-toolkit' ); ?></strong> ' +
					'<?php esc_html_e( '"Read and Write" mode will create and delete test data on your site. Only use this on development/testing sites.', 'pmpro-toolkit' ); ?>' +
					'</div>'
				);
			}
		} else {
			warningDiv.remove();
		}
	}

	function toggle_performance_endpoint_info() {
		var selectedValue = $('#pmprodev_options\\[performance_endpoints\\]').val();
		var endpoints_container = $('#pmprodev_options_performance_endpoints').closest('tr');
		var ip_throttling_row = $('#pmprodev_options\\[ip_throttling\\]').closest('tr');
		if (selectedValue === 'no') {
			endpoints_container.hide();
			ip_throttling_row.hide();
		} else {
			endpoints_container.show();
			ip_throttling_row.show();
		}
	}
	
	// Check on page load
	togglePerformanceWarning();
	toggle_performance_endpoint_info();

	// Check when selection changes
	$('#pmprodev_options\\[performance_endpoints\\]').change(function() {
		togglePerformanceWarning();
		toggle_performance_endpoint_info();
	});
});
</script>
