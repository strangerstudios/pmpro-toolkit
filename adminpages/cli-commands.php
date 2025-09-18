<?php
	// Only admins can access this page.
	if ( ! function_exists( "current_user_can" ) || ( !current_user_can( "manage_options" ) ) ) {
		die( esc_html__( "You do not have permissions to perform this action.", 'pmpro-toolkit' ) );
	}

	global $msg, $msgt, $pmprodev_options;


	// Bail if nonce field isn't set.
	if ( ! empty( $_REQUEST['savesettings'] ) && ( empty( $_REQUEST[ 'pmpro_toolkit_nonce' ] ) 
		|| ! check_admin_referer( 'savesettings', 'pmpro_toolkit_nonce' ) ) ) {
		$msg = -1;
		$msgt = __( "Are you sure you want to do that? Try again.", 'pmpro-toolkit' );
		unset( $_REQUEST[ 'savesettings' ] );
	}

// Save setting for enabling CLI commands.
if ( ! empty( $_REQUEST['savesettings'] ) ) {
	$pmprodev_options['enable_cli_commands'] = isset( $_POST['pmprodev_options']['enable_cli_commands'] ) ? intval( $_POST['pmprodev_options']['enable_cli_commands'] ) : 0;
	update_option( 'pmprodev_options', $pmprodev_options );
	$msg  = true;
	$msgt = __( 'CLI command settings updated.', 'pmpro-toolkit' );
	echo '<div class="notice notice-success inline"><p>' . esc_html( $msgt ) . '</p></div>';

}

?>
<h2><?php esc_html_e( 'CLI Commands', 'pmpro-toolkit' ); ?></h2>
<form action="" method="POST">
	<?php wp_nonce_field( 'savesettings', 'pmpro_toolkit_nonce' ); ?>
	<div class="pmpro_section" data-visibility="shown" data-activated="true">
		<div class="pmpro_section_toggle">
			<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
				<span class="dashicons dashicons-arrow-up-alt2"></span>
				<?php esc_html_e( 'Enable WP-CLI Commands', 'pmpro-toolkit' ); ?>
			</button>
		</div>
		<div class="pmpro_section_inside">
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row" valign="top"><label for="pmprodev_options[enable_cli_commands]" class="description"><?php esc_html_e( 'Enable Toolkit CLI Commands', 'pmpro-toolkit' ); ?></label></th>
						<td>
							<input type="checkbox" id="pmprodev_options[enable_cli_commands]" name="pmprodev_options[enable_cli_commands]" value="1" <?php checked( ! empty( $pmprodev_options['enable_cli_commands'] ), 1 ); ?> />
							<label for="pmprodev_options[enable_cli_commands]" class="description"><?php esc_html_e( 'Enable Database Scripts as commands in WP-CLI. We recommend enabling on development or staging sites only.', 'pmpro-toolkit' ); ?></label>
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
				<?php esc_html_e( 'Usage', 'pmpro-toolkit' ); ?>
			</button>
		</div>
		<div class="pmpro_section_inside">
			<p><?php esc_html_e( 'Run Toolkit commands from your WordPress install directory using WP-CLI. These commands can modify or delete data. Use caution and always backup first.', 'pmpro-toolkit' ); ?></p>
			<p class="description"><?php esc_html_e( 'Append --dry-run to preview changes without modifying data.', 'pmpro-toolkit' ); ?></p>
			<pre style="background:#f6f7f7;padding:12px;border:1px solid #ccd0d4;overflow:auto;white-space:pre;">usage: wp pmpro-toolkit cancel_level 
   or: wp pmpro-toolkit clean_level_data 
   or: wp pmpro-toolkit clean_member_tables 
   or: wp pmpro-toolkit clean_pmpro_options 
   or: wp pmpro-toolkit clear_cached_report_data 
   or: wp pmpro-toolkit clear_vvl_report 
   or: wp pmpro-toolkit copy_memberships_pages 
   or: wp pmpro-toolkit delete_incomplete_orders 
   or: wp pmpro-toolkit delete_test_orders 
   or: wp pmpro-toolkit delete_users 
   or: wp pmpro-toolkit give_level 
   or: wp pmpro-toolkit move_level 
   or: wp pmpro-toolkit scrub_member_data 

See 'wp help pmpro-toolkit &lt;command&gt;' for more information on a specific command.</pre>
		</div>
	</div>

	<p class="submit">
		<input name="savesettings" type="submit" class="button-primary" value="<?php esc_attr_e( 'Save Settings', 'pmpro-toolkit' ); ?>" />
	</p>
</form>