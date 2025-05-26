<?php
/**
 * API Loader class.
 *
 * @package TK
 */

namespace TK;

use TK\Test_Login_Endpoint;

class API_Loader {
	protected $endpoints = array();

	public function __construct() {
		$this->endpoints = array(
			new Test_Login_Endpoint(),
		);

		// Register the routes of the controller.
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
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
