<?php
/**
 * API Loader class.
 *
 * @package TK
 */

namespace TK;

use TK\Test_General_Endpoint;
use TK\Test_Login_Endpoint;
use TK\Test_Checkout_Endpoint;
use TK\Test_Change_Level_Endpoint;
use TK\Test_Membership_Account_Endpoint;
use TK\Test_Cancel_Level_Endpoint;
use TK\Test_Search_Endpoint;
use TK\Test_Report_Endpoint;
use TK\Test_Member_Export_Endpoint;

class API_Loader {
	/**
	 * Array of registered API endpoints.
	 *
	 * @var array
	 */
	protected $endpoints = array();

	/**
	 * Constructor.
	 *
	 * Initializes the API endpoints based on the configuration options.
	 * If performance endpoints are enabled, it registers the routes for each endpoint.
	 */
	public function __construct() {
		global $pmprodev_options;

		// Only add performance testing endpoints if explicitly enabled
		if ( ! empty( $pmprodev_options['performance_endpoints'] ) && $pmprodev_options['performance_endpoints'] !== 'no' ) {

			$this->endpoints = array(
				new Test_General_Endpoint(),
				new Test_Login_Endpoint(),
				new Test_Checkout_Endpoint(),
				new Test_Change_Level_Endpoint(),
				new Test_Membership_Account_Endpoint(),
				new Test_Cancel_Level_Endpoint(),
				new Test_Search_Endpoint(),
				new Test_Report_Endpoint(),
				new Test_Member_Export_Endpoint(),
			);

			add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		}
	}

	/**
	 * Kickoff the registration of all API routes used in child classes.
	 *
	 * @return void
	 */
	public function register_routes() {
		foreach ( $this->endpoints as $endpoint ) {
			if ( method_exists( $endpoint, 'register_routes' ) ) {
				$endpoint->register_routes();
			}
		}
	}
}
