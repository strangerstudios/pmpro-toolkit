<?php
/**
 * API Loader class.
 *
 * @package TK
 */

namespace TK;

use TK\Example_Endpoint;
use TK\Performance_Testing_Endpoint;
use TK\Test_Login_Endpoint;
use TK\Test_Checkout_Endpoint;
use TK\Test_Change_Level_Endpoint;


class API_Loader {
	protected $endpoints = array();

	public function __construct() {
		global $pmprodev_options;

		// Only add performance testing endpoint if enabled
		if ( ! empty( $pmprodev_options['performance_endpoints'] ) && $pmprodev_options['performance_endpoints'] !== 'no' ) {

			$this->endpoints = array(
				new Example_Endpoint(),
				new Performance_Testing_Endpoint(),
				new Test_Login_Endpoint(),
				new Test_Checkout_Endpoint(),
				new Test_Change_Level_Endpoint(),
			);

			// Register the routes of the controller.
			add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		}
	}

	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {
		foreach ( $this->endpoints as $endpoint ) {
			if ( method_exists( $endpoint, 'register_routes' ) ) {
				$endpoint->register_routes();
			}
		}
	}
}
