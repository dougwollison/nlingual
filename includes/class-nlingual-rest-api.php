<?php
/**
 * nLingual REST API Functionality
 *
 * @package nLingual
 * @subpackage Handlers
 *
 * @since 2.0.0
 */

namespace nLingual;

/**
 * The REST API Handler
 *
 * Registers REST routes and fields to add
 * language/translation support to the REST API.
 *
 * @internal Used by the System.
 *
 * @since 2.9.0
 */
final class REST_API extends Handler {
	// =========================
	// ! Properties
	// =========================

	/**
	 * Record of added hooks.
	 *
	 * @internal Used by the Handler enable/disable methods.
	 *
	 * @since 2.6.0
	 *
	 * @var array
	 */
	protected static $implemented_hooks = array();

	// =========================
	// ! Hook Registration
	// =========================

	/**
	 * Register hooks.
	 *
	 * @since 2.9.0
	 */
	public static function register_hooks() {
		// Route/Field Registration
		self::add_hook( 'rest_api_init', 'register_routes' );
		self::add_hook( 'rest_api_init', 'register_fields' );
	}

	// =========================
	// ! Route/Field Registration
	// =========================

	/**
	 * Setup the controller routes.
	 *
	 * @since 2.9.0
	 */
	public static function register_routes() {
		$controller = new REST_Languages_Controller;
		$controller->register_routes();

		$controller = new REST_Translations_Controller;
		$controller->register_routes();

		$controller = new REST_Localizations_Controller;
		$controller->register_routes();
	}

	/**
	 * Register additional fields.
	 *
	 * @since 2.9.0
	 */
	public static function register_fields() {
		// do something
	}
}
