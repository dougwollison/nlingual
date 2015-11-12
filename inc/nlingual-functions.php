<?php
/**
 * nLingual Internal Functions
 *
 * @package nLingual
 * @subpackage Functions
 * @since 2.0.0
 */

namespace nLingual;

/**
 * Check if we're in the backend of the site (excluding frontend AJAX requests)
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
	} else {
		// Check if in the admin or otherwise the login/register page
		return is_admin() || in_array( $pagenow, array( 'wp-login.php', 'wp-register.php' ) );
	}
}

/**
 * Check if backwards compatability is needed.
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

/**
 * Triggers the standard "Cheatin’ uh?" wp_die message.
 *
 * @since 2.0.0
 */
function cheatin() {
	wp_die( \__( 'Cheatin&#8217; uh?' ), 403 );
}

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
	return \__( $string, NL_TXTDMN );
}

/**
 * @see _e()
 */
function _e( $string ) {
	return \_e( $string, NL_TXTDMN );
}

/**
 * @see _n()
 */
function _n( $single, $plural, $number ) {
	return \_n( $single, $plural, $number, NL_TXTDMN );
}

/**
 * @see _x()
 */
function _x( $string, $context ) {
	return \_x( $string, NL_TXTDMN );
}

/**
 * @see _ex()
 */
function _ex( $string, $context ) {
	\_ex( $string, NL_TXTDMN );
}

/**
 * @see _nx()
 */
function _nx( $single, $plural, $number, $context ) {
	return \_nx( $single, $plural, $number, $context, NL_TXTDMN );
}

/**
 * @see _f()
 */
function _f() {
	$args = func_get_args();
	array_splice( $args, 1, 0, NL_TXTDMN );
	return call_user_func_array( '\_f', $args );
}

/**
 * @see _ef()
 */
function _ef() {
	$args = func_get_args();
	array_splice( $args, 1, 0, NL_TXTDMN );
	return call_user_func_array( '\_ef', $args );
}

/**
 * @see _fx()
 */
function _fx() {
	$args = func_get_args();
	array_splice( $args, 2, 0, NL_TXTDMN );
	return call_user_func_array( '\_fx', $args );
}

/**
 * @see _efx()
 */
function _efx() {
	$args = func_get_args();
	array_splice( $args, 2, 0, NL_TXTDMN );
	return call_user_func_array( '\_efx', $args );
}

/**
 * @see _a()
 */
function _a( $array ) {
	return \_a( $array, NL_TXTDMN );
}

/**
 * @see _ax()
 */
function _ax( $array, $context ) {
	return \_a( $array, $context, NL_TXTDMN );
}