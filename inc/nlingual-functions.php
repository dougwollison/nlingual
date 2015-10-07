<?php
/**
 * nLingual Internal Functions
 *
 * Aliases to the external L10n functions,
 * but with the NL_TXTDMN as the domain, since
 * every internal use of the functions passes it.
 *
 * @package nLingual
 * @subpackage Functions
 * @since 2.0.0
 */

namespace nLingual;

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