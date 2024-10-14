<?php
/**
 * nLingual Internal Functions
 *
 * @package nLingual
 * @subpackage Utilities
 *
 * @internal
 *
 * @since 2.0.0
 */

namespace nLingual;

// =========================
// ! Conditional Tags
// =========================

/**
 * Check if we're in the backend of the site (excluding frontend AJAX requests)
 *
 * @internal
 *
 * @since 2.6.0 Added consideration of WP_INSTALLING.
 * @since 2.0.0
 *
 * @return bool Wether or not this should be considered a "backend" request.
 */
function is_backend() {
	if ( defined( 'WP_INSTALLING' ) && WP_INSTALLING ) {
		// "Install" process, count as backend
		return true;
	}

	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		// AJAX request, check if the referrer is from wp-admin
		return strpos( $_SERVER['HTTP_REFERER'], admin_url() ) === 0;
	}

	// Check if in the admin or otherwise the login/register page
	return is_admin() || in_array( basename( $_SERVER['SCRIPT_NAME'] ), array( 'wp-login.php', 'wp-register.php' ) );
}

/**
 * Check if the patch font option is necessary.
 *
 * @internal
 *
 * @since 2.4.0
 *
 * @return bool Wether or not we're running on 4.6 or earlier.
 */
function is_patch_font_stack_needed() {
	// Only needed prior to 4.6
	return version_compare( $GLOBALS['wp_version'], '4.6', '<' );
}

// =========================
// ! Sanitizing Tools
// =========================

/**
 * Convert $language passed into proper object format.
 *
 * @internal
 *
 * @since 2.0.0
 *
 * @uses Registry::languages() to validate and retrieve the passed language.
 *
 * @param Language &$language        The language to be converted.
 * @param bool     $default_current Optional. Default to the current language if null.
 *
 * @return bool If the language was successfully converted.
 */
function validate_language( &$language, $default_current = false ) {
	// If null, return false unless default_current is desired
	if ( is_null( $language ) ) {
		if ( $default_current ) {
			$language = Registry::current_language();
			return true;
		}
		return false;
	}

	// If it's already an object, return true
	if ( is_a( $language, __NAMESPACE__ . '\Language' ) ) {
		return true;
	}

	// Find the language, replace it if so
	$found = Registry::get_language( $language );
	if ( $found !== false ) {
		$language = $found;
		return true;
	}

	// No match, fail
	return false;
}

/**
 * Sanitize a tag name (lowercase alpha-numeric with optional underscores).
 *
 * @internal
 *
 * @since 2.0.0
 *
 * @param string $tag The tag name to sanitize.
 * @param bool   $_   Allow underscores.
 *
 * @return string The sanitize tag name (default false).
 */
function sanitize_tag( $tag, $_ = false ) {
	$replace = $_ ? '_' : '';

	$tag = strtolower( preg_replace( '/[^A-Za-z0-9]+/', $replace, $tag ) );

	return $tag;
}

// =========================
// ! Misc. Utilities
// =========================

/**
 * Generate a link to create a clone of a post for translation.
 *
 * @since 2.9.2 Add create_posts permissions check.
 * @since 2.6.0
 *
 * @param int $post_id The ID of the post to clone.
 * @param int $language_id The ID of the language to translate to.
 *
 * @return string|null The URL to use.
 */
function get_translate_post_link( $post_id, $language_id ) {
	$post = get_post( $post_id );
	if ( ! $post ) {
		return null;
	}

	$post_type_obj = get_post_type_object( $post->post_type );
	if ( ! current_user_can( $post_type_obj->cap->create_posts ) ) {
		return null;
	}

	$url = admin_url( 'admin-post.php?' ) . http_build_query( array(
		'action' => 'nl_new_translation',
		'post_id' => $post_id,
		'translation_language_id' => $language_id,
	) );

	return $url;
}

/**
 * Test if this plugin is active.
 *
 * @internal
 *
 * @since 2.8.0
 *
 * @return bool Wether or not this plugin is active.
 */
function is_nlingual_active() {
	if ( function_exists( 'is_plugin_active' ) ) {
		return is_plugin_active( NL_PLUGIN_SLUG );
	}

	/**
	 * Front-end polyfill, rather than load all of wp-admin/includes/plugin.php
	 */

	// Get active plugins
	$plugins = get_option( 'active_plugins', array() );
	if ( is_multisite() ) {
		// Add site-wide plugins (why is it in a different format again?)
		$plugins += array_keys( get_site_option( 'active_sitewide_plugins', array() ) );
	}

	return in_array( NL_PLUGIN_SLUG, $plugins );
}

/**
 * Triggers the standard "Cheatin’ uh?" wp_die message.
 *
 * @internal
 *
 * @since 2.0.0
 */
function cheatin() {
	wp_die( 'Cheatin&#8217; uh?', 403 );
}
