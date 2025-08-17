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

		update_option( "pmprodev_options", $pmprodev_options );

		// Assume success.
		$msg = true;
		$msgt = __( "Your developer's toolkit settings have been updated.", 'pmpro-toolkit' );

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

	<!--Scheduled Cron Job Debugging section -->
	<div class="pmpro_section" data-visibility="shown" data-activated="true">
		<div class="pmpro_section_toggle">
			<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
				<span class="dashicons dashicons-arrow-up-alt2"></span>
					<?php if ( class_exists( 'PMPro_Scheduled_Actions' ) ) { ?>
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
								<?php esc_html_e( 'Check to disable the script that checks for expired memberships.', 'pmpro-toolkit' ); ?>
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
								<?php esc_html_e( 'Check to disable the script that sends expiration warnings.', 'pmpro-toolkit' ); ?>
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
								<?php esc_html_e( 'Check to disable the script that sends payment reminders.', 'pmpro-toolkit' ); ?>
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
<p class="submit">
	<input name="savesettings" type="submit" class="button-primary" value="<?php esc_html_e( 'Save Settings', 'pmpro-toolkit' ); ?>">
</p>
</form>
