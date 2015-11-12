<?php
/**
 * nLingual Liaison Functionality
 *
 * @package nLingual
 * @subpackage Handlers
 *
 * @since 2.0.0
 */

namespace nLingual;

class Liaison extends Functional {
	use Shared_Filters;

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
		static::add_action( 'after_setup_theme', 'add_quickstart_helpers', 10 );
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
			static::add_filter( 'qs_helper_get_index', 'current_language_post', 10, 1 );
		}
	}
}
