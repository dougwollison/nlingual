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

/**
 * The Liaison System
 *
 * Hooks into select 3rd party systems to provide
 * better compatibility with them, namely other plugins
 * by Doug Wollison.
 *
 * @internal Used by the System.
 *
 * @since 2.0.0
 */

class Liaison extends Handler {
	// =========================
	// ! Hook Registration
	// =========================

	/**
	 * Register hooks.
	 *
	 * @since 2.0.0
	 */
	public static function register_hooks() {
		// QuickStart compatibility
		static::add_action( 'after_setup_theme', 'add_quickstart_helpers', 10, 0 );

		// IndexPages compatibility
		static::add_action( 'after_setup_theme', 'add_indexpages_helpers', 10, 0 );
	}

	// =========================
	// ! QuickStart Helpers
	// =========================

	/**
	 * Check if QuickStart is active, setup necessary helpers.
	 *
	 * @since 2.0.0
	 *
	 * @uses Frontend::current_language_post() on the qs_helper_get_index filter.
	 */
	public static function add_quickstart_helpers() {
		// Abort if QuickStart isn't present
		if ( ! function_exists( 'QuickStart' ) ) {
			return;
		}

		// Custom index page feature adjustments
		if ( current_theme_supports( 'quickstart-index_page' ) ) {
			// Replace the retrieved index page's ID with it's translation counterpart
			Frontend::add_filter( 'qs_helper_get_index', 'current_language_post', 10, 1 );
		}

		// Order manager feature adjustments
		if ( current_theme_supports( 'quickstart-order_manager' ) ) {
			// Set language appropriately
			static::maybe_add_filter( 'nlingual_pre_set_queried_language', 'quickstart_order_manager_language', 10, 2 );
		}
	}

	/**
	 * Set queried language to default (and un-assigned) for certain QuickStart queries.
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::is_post_type_supported() to check for post type support.
	 * @uses Registry::get_rules() to check for menu_order synchronizing.
	 * @uses Registry::default_language() to get the default language slug.
	 *
	 * @param mixed    $pre_value The value to use instead of the determined one.
	 * @param WP_Query $query     The query being modified.
	 *
	 * @return mixed The default language ot use.
	 */
	public static function quickstart_order_manager_language( $pre_value, \WP_Query $query ) {
		// Get the context and the post type
		$context = $query->get( 'qs-context' );
		$post_type = $query->get( 'post_type' );

		// If multiple post types, not the order manager context, or post type isn't supported, abort
		if ( is_array( $post_type ) || $context != 'order-manager' || ! Registry::is_post_type_supported( $post_type ) ) {
			return $pre_value;
		}

		// Get the sync rules for the post type
		$rules = Registry::get_rules( 'sync', 'post_type', $post_type, 'post_fields' );
		// If menu_order isn't set included in the sync rules, abort
		if ( ! in_array( 'menu_order', $rules ) ) {
			return $pre_value;
		}

		// Set the value to the default language (and no language)
		$pre_value = array( Registry::default_language()->slug, '0' );

		return $pre_value;
	}

	// =========================
	// ! IndexPages Helpers
	// =========================

	/**
	 * Check if IndexPages is active, setup necessary helpers.
	 *
	 * @since 2.0.0
	 *
	 * @uses Frontend::current_language_post() on the qs_helper_get_index filter.
	 */
	public static function add_indexpages_helpers() {
		// Abort if IndexPages isn't present
		if ( ! class_exists( 'IndexPages\System' ) ) {
			return;
		}

		// Replace the retrieved index page's ID with it's current language counterpart
		Frontend::add_filter( 'indexpages_get_index_page', 'current_language_post', 10, 1 );

		// Replace the retrieved index page's ID with it's default language counterpart
		Frontend::add_filter( 'indexpages_is_index_page', 'default_language_post', 10, 1 );
	}
}
