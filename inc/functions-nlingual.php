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

/**
 * Check if backwards compatability is needed.
 *
 * @internal
 *
 * @since 2.0.0
 *
 * @uses Registry::get() to get the backwards_compatible option.
 *
 * @return bool Wether or not backwards compatibility is needed.
 */
function backwards_compatible() {
	return Registry::get( 'backwards_compatible' );
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
	$found = Registry::languages()->get( $language );
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
// ! GetText Functions
// =========================

/**
 * The following functions are aliases to the public
 * localization functions (custom ones included), but
 * with the nLingual text domain included automatically,
 * since it's used in 99% of calls within the classes.
 */

/**
 * @see __()
 */
function __( $string ) {
	return \__( $string, 'nlingual' );
}

/**
 * @see _e()
 */
function _e( $string ) {
	return \_e( $string, 'nlingual' );
}

/**
 * @see _n()
 */
function _n( $single, $plural, $number ) {
	return \_n( $single, $plural, $number, 'nlingual' );
}

/**
 * @see _x()
 */
function _x( $string, $context ) {
	return \_x( $string, 'nlingual' );
}

/**
 * @see _ex()
 */
function _ex( $string, $context ) {
	\_ex( $string, 'nlingual' );
}

/**
 * @see _nx()
 */
function _nx( $single, $plural, $number, $context ) {
	return \_nx( $single, $plural, $number, $context, 'nlingual' );
}

/**
 * @see _f()
 */
function _f() {
	$args = func_get_args();
	array_splice( $args, 1, 0, 'nlingual' );
	return call_user_func_array( '\_f', $args );
}

/**
 * @see _ef()
 */
function _ef() {
	$args = func_get_args();
	array_splice( $args, 1, 0, 'nlingual' );
	return call_user_func_array( '\_ef', $args );
}

/**
 * @see _fx()
 */
function _fx() {
	$args = func_get_args();
	array_splice( $args, 2, 0, 'nlingual' );
	return call_user_func_array( '\_fx', $args );
}

/**
 * @see _efx()
 */
function _efx() {
	$args = func_get_args();
	array_splice( $args, 2, 0, 'nlingual' );
	return call_user_func_array( '\_efx', $args );
}

/**
 * @see _a()
 */
function _a( $array ) {
	return \_a( $array, 'nlingual' );
}

/**
 * @see _ax()
 */
function _ax( $array, $context ) {
	return \_a( $array, $context, 'nlingual' );
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
	wp_die( \__( 'Cheatin&#8217; uh?' ), 403 );
}