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
 * @since 2.0.0
 *
 * @global string $pagenow The current page slug.
 */
function is_backend() {
	global $pagenow;

	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		// AJAX request, check if the referrer is from wp-admin
		return strpos( $_SERVER['HTTP_REFERER'], admin_url() ) === 0;
	}

	// Check if in the admin or otherwise the login/register page
	return is_admin() || in_array( $pagenow, array( 'wp-login.php', 'wp-register.php' ) );
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
 * @param mixed &$language        The language to be converted.
 * @param bool   $default_current Optional. Default to the current language if null.
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
 * Triggers the standard "Cheatin’ uh?" wp_die message.
 *
 * @internal
 *
 * @since 2.0.0
 */
function cheatin() {
	wp_die( __( 'Cheatin&#8217; uh?' ), 403 );
}