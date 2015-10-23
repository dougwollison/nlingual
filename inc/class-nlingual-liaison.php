<?php
namespace nLingual;

/**
 * nLingual Liaison Functionality
 *
 * @package nLingual
 *
 * @since 2.0.0
 */

class Liaison extends Functional {
	/**
	 * The name of the class.
	 *
	 * @since 2.0.0
	 *
	 * @access protected (static)
	 *
	 * @var string
	 */
	protected static $name;

	// =========================
	// ! Hook Registration
	// =========================

	/**
	 * Register hooks.
	 *
	 * @since 2.0.0
	 */
	public static function register_hooks() {
		// QuickStart compatability
		static::add_action( 'after_theme_setup', 'add_quickstart_helpers', 10 );
	}

	// =========================
	// ! QuickStart Helpers
	// =========================

	/**
	 * Check if QuickStart is active, setup necessary helpers.
	 *
	 * @since 2.0.0
	 */
	public static function add_quickstart_helpers() {
		// Abort if QuickStart isn't present
		if ( ! function_exists( 'QuickStart' ) ) {
			return;
		}

		// Custom index page feature adjustments
		if ( current_theme_supports( 'quickstart-index_page' ) ) {
			// Replace the retrieved index page's ID with it's translation counterpart
			API::add_filter( 'qs_helper_get_index', 'current_language_version', 10, 1 );
		}
	}
}