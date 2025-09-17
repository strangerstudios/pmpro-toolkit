function pmprodev_generate_checkout_info() {
	jQuery.noConflict().ajax({
		url: 'https://randomuser.me/api/?nat=us',
		dataType: 'json',
		success: function( data ) {
		const results = data['results'][0];

		// Fill required fields.
		jQuery('.pmpro_form_input-checkbox.pmpro_form_input-required').prop('checked', true);
		jQuery('.pmpro_form_input-text.pmpro_form_input-required').val('Sample Text');
		
		// Generate email address
		const username = results.name.first + '.' + results.name.last;
		const base_email = jQuery('#pmprodev-base-email').val();
		const at_index = base_email.indexOf("@");
		const user_email = base_email.substring(0, at_index) + '+' + username + base_email.substring(at_index);
		
		jQuery('#username').val( username );
		jQuery('#password').val( username );
		jQuery('#password2').val( username );
		jQuery('#bemail').val( user_email );
		jQuery('#bconfirmemail').val( user_email );
		jQuery('#first_name').val( results.name.first );
		jQuery('#last_name').val( results.name.last );
		jQuery('#bfirstname').val( results.name.first );
		jQuery('#blastname').val( results.name.last );
		jQuery('#baddress1').val( results.location.street.number  + ' ' +  results.location.street.name );
		jQuery('#bcity').val( results.location.city );
		jQuery('#bstate').val( results.location.state );
		jQuery('#bzipcode').val( results.location.postcode );
		jQuery('#bcountry').val( 'US' );
		jQuery('#bphone').val( results.phone );
		jQuery('#pmpro_sfirstname').val( results.name.first );
		jQuery('#pmpro_slastname').val( results.name.last );
		jQuery('#pmpro_saddress1').val( results.location.street.number  + ' ' +  results.location.street.name );
		jQuery('#pmpro_scity').val( results.location.city );
		jQuery('#pmpro_sstate').val( results.location.state );
		jQuery('#pmpro_szipcode').val( results.location.postcode );
		jQuery('#pmpro_scountry').val( 'US' );
		jQuery('#pmpro_sphone').val( results.phone );
		jQuery('#pmpromd_street_name').val( results.location.street.number  + ' ' +  results.location.street.name );
		jQuery('#pmpromd_city').val( results.location.city );
		jQuery('#pmpromd_state').val( results.location.state );
		jQuery('#pmpromd_zip').val( results.location.postcode );
		jQuery('#pmpromd_country').val( 'US' );
		jQuery('#AccountNumber').val( "4242424242424242" );
		const nextYear = new Date().getFullYear() + 1;
		jQuery('#ExpirationYear').val( nextYear );
		jQuery('#CVV').val( "123" );
		}
	});
}

/**
 * jQuery ready function.
 */
jQuery(document).ready(function ( $ ) {
	$('#pmprodev-generate').click(function () {
		pmprodev_generate_checkout_info();
	});

	$('#pmprodev-generate-submit').click(function () {
		pmprodev_generate_checkout_info();
		$('.pmpro_btn-submit-checkout').click();
	});

	/**

	 * Enable the generate button if the email address is valid.
	 */
	$('#pmprodev-base-email').change(function () {
		const email = $(this).val();
		if ( email.includes('@')) {
			//Enable the generate button
			$('#pmprodev-generate').prop('disabled', false);
			$('#pmprodev-generate-submit').prop('disabled', false);
		} else {
			//Disable the generate button
			$('#pmprodev-generate').prop('disabled', true);
			$('#pmprodev-generate-submit').prop('disabled', true);
		}
	});
});