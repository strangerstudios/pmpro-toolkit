<?php
/**
 * Migration Assistant class.
 */
class PMProDev_Migration_Assistant {
	/**
	 * Exports PMPro data to a file.
	 *
	 * @since 0.7
	 *
	 * @param string|array $export_types The types of information to export.
	 *
	 * @return string|null The error message, if any, or null if no error.
	 */
	public static function export( $export_types ) {
		// Make sure user has permission to export settings.
		if ( ! current_user_can( 'manage_options' ) ) {
			return __( 'You do not have sufficient permissions to export PMPro data.', 'pmpro-toolkit' );
		}
		

		// Make sure settings are an array.
		if ( ! is_array( $export_types ) ) {
			$export_types = array( $export_types );
		}

		// Get the data to export.
		$export_data = array();
		foreach ( $export_types as $export_type ) {
			// Check if this is a valid setting to export.
			if ( method_exists( __CLASS__, 'build_export_data_' . $export_type  ) ) {
				$export_data[ $export_type ] = self::{'build_export_data_' . $export_type}();
			}
		}

		// Output the data as a JSON file.
		header( 'Content-Disposition: attachment; filename=pmpro-settings-' . date( 'Y-m-d' ) . '.json' );
		header( 'Content-Type: application/json' );
		echo json_encode( $export_data );
		exit;
	}

	/**
	 * Imports PMPro data from a file.
	 *
	 * @since 0.7
	 *
	 * @param string $file The location of the file to import from.
	 * @return null|string The error message, if any, or null if no error.
	 */
	public static function import( $file ) {
		// Make sure user has permission to import settings.
		if ( ! current_user_can( 'manage_options' ) ) {
			return __( 'You do not have sufficient permissions to import PMPro data.', 'pmpro-toolkit' );
		}

		// Make sure the file is a valid JSON file.
		$file_data = json_decode( file_get_contents( $file ), true ); // True for associative array.
		if ( ! is_array( $file_data ) ) {
			return __( 'The file you uploaded is not a valid PMPro import file.', 'pmpro-toolkit' );
		}

		// Import the data.
		$error = null; // Track any error messages that may occur.
		foreach ( $file_data as $import_type => $import_data ) {
			// Check if we can process this type of import.
			if ( ! method_exists( __CLASS__, 'import_data_' . $import_type ) ) {
				$error = empty( $error ) ? __( 'Invalid import type: ', 'pmpro-toolkit' ) . $import_type : $error;
				continue;
			}

			// Check that the data being imported is valid.
			if ( ! is_array( $import_data ) ) {
				$error = empty( $error ) ? __( 'Invalid import data for import type: ', 'pmpro-toolkit' ) . $import_type : $error;
				continue;
			}

			// Import the data.
			$returned_error = self::{'import_data_' . $import_type}( $import_data );

			// If there is an error, set it.
			if ( ! empty( $returned_error ) && empty( $error ) ) {
				$error = $returned_error;
			}
		}

		// Return the error message if we have one.
		return $error;
	}

	/**
	 * Export User Field Settings.
	 *
	 * @return array
	 */
	private static function build_export_data_user_fields() {
		$option_name = array( 
			'pmpro_user_fields_settings'
		);	

		// Get the payment settings from the options table.
		return self::helper_get_export_data( $option_name );
	}

	/**
	 * Import User Field Settings.
	 *
	 * @param [type] $user_fields_data
	 * @return string The error message on importing.
	 */
	private static function import_data_user_fields( $user_fields_data ) {
		self::helper_import_to_options( $user_fields_data );
	}

	/**
	 * Get levels export data.
	 *
	 * @since 0.7
	 *
	 * @return array The levels export data.
	 */
	private static function build_export_data_levels() {
		global $wpdb;

		// Get all levels.
		$levels = pmpro_getAllLevels( true ); // True to include hidden levels.

		// Get metadata for all levels.
		$level_metadata = $wpdb->get_results( "SELECT * FROM $wpdb->pmpro_membership_levelmeta" );

		// Add metadata to the levels export data.
		foreach( $level_metadata as $level_meta ) {
			// Make sure that this metadata is for a level that we are exporting.
			if ( ! isset( $levels[ $level_meta->pmpro_membership_level_id ] ) ) {
				continue;
			}

			// Add the metadata to the level.
			if ( ! property_exists( $levels[ $level_meta->pmpro_membership_level_id] , 'metadata' ) ) {
				$levels[ $level_meta->pmpro_membership_level_id ]->metadata = array();
			}
			$levels[ $level_meta->pmpro_membership_level_id ]->metadata[ $level_meta->meta_key ] = $level_meta->meta_value;
		}

		// Get rid of references to current level ID in $level, which will likely change at import.
		foreach ( $levels as $level_id => $level ) {
			unset( $level->id );
		}

		// Get all of the level groups.
		$level_groups = pmpro_get_level_groups();
		foreach( $level_groups as $group_id => $group_data ) {
			// Get rid of the ID in the group data. This will likely change at import.
			unset( $group_data->id );

			// Set up the $level property for the group.
			$group_data->levels = array();

			// Get the level IDs that are a part of this group.
			$group_level_ids = pmpro_get_level_ids_for_group( $group_id );			

			// Add the level objects from $levels to the group.
			foreach( $group_level_ids as $level_id ) {
				$group_data->levels[] = $levels[ $level_id ];
			}
		}

		// Return the levels export data without the group IDs as keys.
		return array_values( $level_groups );
	}

	/**
	 * Import levels data.
	 *
	 * This method will always create new levels and not
	 * overwrite existing levels.
	 *
	 * @since 0.7
	 *
	 * @param array $levels_data The levels to import.
	 *
	 * @return string|null The error message, if any, or null if no error.
	 */
	private static function import_data_levels( $levels_data, $group_id = null ) {
		global $wpdb;

		// If $group_id is not passed, we need to create a new group to add levels to.
		if ( empty( $group_id ) ) {
			// Check if the first element is a group object.
			if ( isset( current( $levels_data )['levels'] ) ) {
				// We are importing groups. Import data recursively.
				foreach ( $levels_data as $group ) {
					// Create the group.
					$created_group_id = pmpro_create_level_group( $group['name'], $group['allow_multiple_selections'], $group['displayorder'] );

					// Import the levels.
					self::import_data_levels( $group['levels'], $created_group_id );
				}
			} else {
				// This is a legacy import with only levels. Create a new group for them.
				$created_group_id = pmpro_create_level_group( __( 'Imported Group', 'pmpro-toolkit' ), false );
				self::import_data_levels( $levels_data, $created_group_id );
			}
			return;
		}

		// Import the levels.
		foreach ( $levels_data as $level ) {
			// Add the data to the pmpro_membership_levels table.
			pmpro_insert_or_replace(
				$wpdb->pmpro_membership_levels,
				array(
					'name' => $level['name'],
					'description' => $level['description'],
					'confirmation' => $level['confirmation'],
					'initial_payment' => $level['initial_payment'],
					'billing_amount' => $level['billing_amount'],
					'cycle_number' => $level['cycle_number'],
					'cycle_period' => $level['cycle_period'],
					'billing_limit' => $level['billing_limit'],
					'trial_amount' => $level['trial_amount'],
					'trial_limit' => $level['trial_limit'],
					'expiration_number' => $level['expiration_number'],
					'expiration_period' => $level['expiration_period'],
					'allow_signups' => $level['allow_signups'],
				),
				array(
					'%s',		//name
					'%s',		//description
					'%s',		//confirmation
					'%f',		//initial_payment
					'%f',		//billing_amount
					'%d',		//cycle_number
					'%s',		//cycle_period
					'%d',		//billing_limit
					'%f',		//trial_amount
					'%d',		//trial_limit
					'%d',		//expiration_number
					'%s',		//expiration_period
					'%d',		//allow_signups
				)
			);

			// Get the ID of the level we just imported.
			$level_id = $wpdb->insert_id;

			// Add the level's metadata to the pmpro_membership_levelmeta table.
			if ( isset( $level['metadata'] ) ) {
				foreach ( $level['metadata'] as $meta_key => $meta_value ) {
					update_pmpro_membership_level_meta( $level_id, $meta_key, $meta_value );
				}
			}

			// Add the level to the group.
			pmpro_add_level_to_group( $level_id, $group_id );
		}
	}

	/**
	 * Get email templates export data.
	 *
	 * @since 0.7
	 *
	 * @return array The email templates export data.
	 */
	private static function build_export_data_email_templates() {
		global $wpdb;

		// Get all email template data from options table.
		$email_template_option_data = $wpdb->get_results( "SELECT option_name, option_value FROM $wpdb->options WHERE option_name LIKE 'pmpro_email_%_body'" );

		// Format the email template data.
		$email_template_data = array();
		foreach ( $email_template_option_data as $email_template_option ) {
			$email_template_data[ $email_template_option->option_name ] = $email_template_option->option_value;
		}

		return $email_template_data;
	}

	/**
	 * Import email templates data.
	 *
	 * @since 0.7
	 *
	 * @param array $email_templates_data The email templates to import.
	 *
	 * @return string|null The error message, if any, or null if no error.
	 */
	private static function import_data_email_templates( $email_templates_data ) {
		self::helper_import_to_options( $email_templates_data );
	}

	/**
	 * Get payment settings export data.
	 *
	 * @since 0.7
	 *
	 * @return array The payment settings export data.
	 */
	private static function build_export_data_payment() {
		// Create a list of payment settings option names.
		$payment_settings_names = array(
			'currency',
			'instructions',
			'tax_state',
			'tax_rate',
			'accepted_credit_cards',
			'gateway',
			'gateway_environment',
		);

		// Let gateways add their own settings to the list.
		$payment_settings_names = array_unique( apply_filters( 'pmpro_payment_options', $payment_settings_names ) );

		// Prepend 'pmpro_' to the option names.
		$payment_settings_option_names = self::helper_prepend_pmpro_to_option_names( $payment_settings_names );

		// Get the payment settings from the options table.
		return self::helper_get_export_data( $payment_settings_option_names );
	}

	/**
	 * Import payment settings data.
	 *
	 * @since 0.7
	 *
	 * @param array $payment_settings_data The payment settings to import.
	 *
	 * @return string|null The error message, if any, or null if no error.
	 */
	private static function import_data_payment( $payment_settings_data ) {
		self::helper_import_to_options( $payment_settings_data );
	}

	/**
	 * Get advanced settings export data.
	 *
	 * @since 0.7
	 *
	 * @return array The advanced settings export data.
	 */
	private static function build_export_data_advanced() {
		// Create a list of advanced settings option names.
		$advanced_settings_names = array(
			'hide_toolbar',
			'block_dashboard',
			'nonmembertext',
			'notloggedintext',
			'rsstext',
			'filterqueries',
			'showexcerpts',
			'tospage',
			'spamprotection',
			'recaptcha',
			'recaptcha_version',
			'recaptcha_publickey',
			'recaptcha_privatekey',
			'maxnotificationproiority',
			'activity_email_frequency',
			'hideads',
			'wisdom_opt_out',
			'hideadslevels',
			'redirecttosubscription',
			'uninstall'
		);

		// Prepend 'pmpro_' to the option names.
		$advanced_settings_option_names = self::helper_prepend_pmpro_to_option_names( $advanced_settings_names );

		// Get the advanced settings data.
		return self::helper_get_export_data( $advanced_settings_option_names );
	}

	/**
	 * Import advanced settings data.
	 *
	 * @since 0.7
	 *
	 * @param array $advanced_settings_data The advanced settings to import.
	 */
	private static function import_data_advanced( $advanced_settings_data ) {
		self::helper_import_to_options( $advanced_settings_data );
	}

	/**
	 * Helper function to add import data to wp_options table.
	 *
	 * @since 0.7
	 *
	 * @param array $import_data The import data to add.
	 */
	private static function helper_import_to_options( $import_data ) {
		$allowed_options = self::allowed_options();
		foreach ( $import_data as $option_name => $option_value ) {
			// Make sure that option name begins with 'pmpro_'.
			// If not, we don't want to import it.
			if ( strpos( $option_name, 'pmpro_' ) !== 0 ) {
				continue;
			}
			
			// Make sure the option name exists/used in PMPro, otherwise don't import it.
			if ( ! in_array( $option_name, $allowed_options ) ) {
				continue;
			}

			if ( ! is_array( $option_value ) ) {
				$option_value = wp_kses_post( $option_value ); // Sanitize using wp_kses_post in case there is some JS or something along those lines in the options.
			} else {
				$option_value = array_map( 'sanitize_text_field', $option_value ); // Default to sanitize_text_field for arrays.
			}

			// If the option name is 'pmpro_user_fields_settings', we need to unserialize the value before storing it. Sanitized earlier above this.
			if ( $option_name === 'pmpro_user_fields_settings' ) {
				$option_value = maybe_unserialize( $option_value );
			}
	
			update_option( $option_name, $option_value );
		}
	}

	/**
	 * Helper function to get export data from wp_options table.
	 *
	 * @since 0.7
	 *
	 * @param array $option_names_to_export The names of the options to export.
	 */
	private static function helper_get_export_data( $option_names_to_export ) {
		global $wpdb;

		// Get all advanced settings data from options table.
		$option_data = $wpdb->get_results( "SELECT option_name, option_value FROM $wpdb->options WHERE option_name IN ('" . implode( "','", $option_names_to_export ) . "')" );

		// Format the advanced settings data.
		$export_data = array();
		foreach ( $option_data as $option ) {
			$export_data[ $option->option_name ] = $option->option_value;
		}

		return $export_data;
	}

	/**
	 * Helper function to prepend 'pmpro_' to an array of option names.
	 *
	 * @since 0.7
	 *
	 * @param array $option_names The option names to prepend.
	 * @return array The option names with 'pmpro_' prepended.
	 */
	private static function helper_prepend_pmpro_to_option_names( $option_names ) {
		return array_map( function( $option_name ) {
			return 'pmpro_' . $option_name;
		}, $option_names );
	}

	/**
	 * Function to get a list of allowed options to import.
	 *
	 * @return array $pmpro_options An array of allowed options to be imported.
	 */
	private static function allowed_options() {
		$pmpro_options = array(
			'hide_toolbar',
			'block_dashboard',
			'nonmembertext',
			'notloggedintext',
			'rsstext',
			'filterqueries',
			'showexcerpts',
			'tospage',
			'spamprotection',
			'recaptcha',
			'recaptcha_version',
			'recaptcha_publickey',
			'recaptcha_privatekey',
			'maxnotificationproiority',
			'activity_email_frequency',
			'hideads',
			'wisdom_opt_out',
			'hideadslevels',
			'redirecttosubscription',
			'uninstall',
			'currency',
			'instructions',
			'tax_state',
			'tax_rate',
			'accepted_credit_cards',
			'gateway',
			'gateway_environment',
			'user_fields_settings'
		);

		$pmpro_options = apply_filters( 'pmpro_toolkit_allowed_import_options', $pmpro_options );

		return self::helper_prepend_pmpro_to_option_names( $pmpro_options );
	}
}
