<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<h2><?php esc_html_e( 'Migration Assistant', 'pmpro-toolkit' ); ?></h2>
<p><?php esc_html_e( 'Use the options below to import or export PMPro data from one membership site to another membership site.', 'pmpro-toolkit' ); ?></p>
<p><?php echo wp_kses( sprintf( __( 'If you need to import or export members, please check out the <a href="%s" target="_blank">Import Members From CSV Add On here</a>.', 'pmpro-toolkit' ), esc_url( 'https://www.paidmembershipspro.com/add-ons/pmpro-import-users-csv/' ) ), array( 'a' => array( 'href' => array(), 'target' => array() ) ) ); ?></p>
<!-- Import File Section -->
<div class="pmpro_section" data-visibility="shown" data-activated="true">
	<div class="pmpro_section_toggle">
		<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
			<span class="dashicons dashicons-arrow-up-alt2"></span>
			<?php esc_html_e( 'Import PMPro Data', 'pmpro-toolkit' ); ?>
		</button>
	</div>
	<div class="pmpro_section_inside">
		<?php
		// Check if the user submitted an import file.
		if (
			isset( $_FILES[ 'pmprodev-import-file' ] )
			&& $_FILES[ 'pmprodev-import-file' ][ 'error' ] === UPLOAD_ERR_OK // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated -- Always set by PHP when the upload array exists; checked for errors.
			&& is_uploaded_file( $_FILES[ 'pmprodev-import-file' ][ 'tmp_name' ] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- PHP-generated temp path, only validated here; the nonce is verified below before importing.
		) {
			// Verify the nonce.
			if ( ! isset( $_POST[ '_wpnonce' ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ '_wpnonce' ] ) ), 'pmprodev-import' ) ) {
				// Verification failed.
				echo '<div class="notice notice-large notice-error inline"><p>' . esc_html__( 'Nonce verification failed.', 'pmpro-toolkit' ) . '</p></div>';
			} else {
				// Verification succeeded. Import the file.
				$error = PMProDev_Migration_Assistant::import( $_FILES[ 'pmprodev-import-file' ][ 'tmp_name' ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- PHP-generated temp path, verified with is_uploaded_file() above.
				if ( is_string( $error ) ) {
					// There was an error during the import.
					echo '<div class="notice notice-large notice-error inline"><p>' . esc_html( $error ) . '</p></div>';
				} else {
					// Import successful.
					echo '<div class="notice notice-large notice-success inline"><p>' . esc_html__( 'Import successful.', 'pmpro-toolkit' ) . '</p></div>';
				}
			}
		} elseif ( isset( $_POST[ '_wpnonce' ] ) ) {
			echo '<div class="notice notice-large notice-error inline"><p>' . esc_html__( 'No import file found. Please try importing again with a valid JSON file.', 'pmpro-toolkit' ) . '</p></div>';
		}
		?>

		<form method="post" enctype="multipart/form-data">
			<label for="pmprodev-import-file"><?php esc_html_e( 'Choose a file to import', 'pmpro-toolkit' ); ?>:</label>
			<input type="file" name="pmprodev-import-file" accept="application/json">
			<?php wp_nonce_field( 'pmprodev-import' ); ?>
			<p class="submit">
				<input type="submit" class="button button-primary" value="<?php esc_html_e( 'Import PMPro Data', 'pmpro-toolkit' ); ?>" onclick="return confirm('<?php esc_html_e( 'This import will permanently overwrite site data. Are you sure that you would like to continue?', 'pmpro-toolkit' ); ?>')">
			</p>
		</form>
	</div>
</div>

<!-- Export Options Section -->
<div class="pmpro_section" data-visibility="shown" data-activated="true">
	<div class="pmpro_section_toggle">
		<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
			<span class="dashicons dashicons-arrow-up-alt2"></span>
			<?php esc_html_e( 'Export PMPro Data', 'pmpro-toolkit' ); ?>
		</button>
	</div>
	<div class="pmpro_section_inside">
		<p><?php esc_html_e( 'Select the data that you would like to export:', 'pmpro-toolkit' ); ?></p>
		<button class="button button-secondary" id="pmprodev-export-select-all"><?php esc_html_e( 'Select All', 'pmpro-toolkit' ); ?></button>

		<?php
		$export_options = [
			'levels'          => __( 'Membership Levels', 'pmpro-toolkit' ),
			'email_templates' => __( 'Email Templates', 'pmpro-toolkit' ),
			'payment'         => __( 'Payment & SSL Settings', 'pmpro-toolkit' ),
			'advanced'        => __( 'Advanced Settings', 'pmpro-toolkit' ),
			'user_fields'     => __( 'User Fields', 'pmpro-toolkit' ),
		];

		foreach ( $export_options as $key => $label ) {
			echo '<p><input type="checkbox" name="pmprodev_export_options[]" value="' . esc_attr( $key ) . '" id="pmprodev_export_options_' . esc_attr( $key ) . '" /> <label for="pmprodev_export_options_' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></p>';
		}
		?>

		<p class="submit">
			<input type="submit" id="pmprodev-export-data" class="button button-primary" onsubmit="return false;" value="<?php esc_html_e( 'Export PMPro Data', 'pmpro-toolkit' ); ?>">
		</p>
	</div>
</div>

<script>
	jQuery( document ).ready( function( $ ) {
		// Handle "Select All" button clicks.
		$( '#pmprodev-export-select-all' ).on('click', function() {
			$buttonText = $( this ).text();
			const $checkboxes = $( 'input[name="pmprodev_export_options[]"]' );
			//toggle checkboxes. If checked, uncheck. If unchecked, check.
			$checkboxes.prop( 'checked', function( i, value ) {
				return $buttonText === '<?php esc_html_e( 'Select All', 'pmpro-toolkit' ); ?>' ? true : false;
			});
			//change button text based on current state. Should be internationalized.
			const selectAllText = '<?php esc_html_e( 'Select All', 'pmpro-toolkit' ); ?>';
			const deselectAllText = '<?php esc_html_e( 'Deselect All', 'pmpro-toolkit' ); ?>';
			const currentText = $( this ).text();
			$( this ).text( currentText === selectAllText ? deselectAllText : selectAllText );

		});

		// Handle export button clicks.
		$( '#pmprodev-export-data' ).on('click', function() {
			// Get all checked export options.
			const export_options = $( 'input[name="pmprodev_export_options[]"]:checked' );
			if ( export_options.length === 0 ) {
				//render a notice to select at least one option, closeable by user.
				$( '#pmprodev-export-data' ).after( 
					'<div class="notice notice-error inline"><p><?php esc_html_e( 'Please select at least one export option.', 'pmpro-toolkit' ); ?> </p></div>' );
				return;
			}

			// Download export file.
			window.open( '<?php echo esc_url( admin_url( '/admin.php?page=pmpro-toolkit' ) ); ?>&section=migration&' + export_options.serialize() );
		});
	});
</script>
