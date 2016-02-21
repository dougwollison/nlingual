<?php
/**
 * nLingual GetText Utilities
 *
 * Additions to the existing l10n functions.
 *
 * @package nLingual
 * @subpackage i18n
 *
 * @api
 *
 * @since 2.0.0
 */

/*
 * Localize format string.
 *
 * @since 2.0.0 Reworked to accept separate args or and array of them.
 * @since 1.0.0
 *
 * @uses __()
 *
 * @param string $text   The format string.
 * @param string $domain The domain to use.
 * @param mixed  $args   The arguments for vsprintf().
 *                       This can be an array of arguments or separate ones.
 */
function _f( $text, $domain, $args ) {
	// If $args isn't an array, get all addition arguments and use that
	if ( ! is_array( $args ) ) {
		$args = func_get_args();
		$args = array_slice( $args, 2 );
	}

	return vsprintf( __( $text, $domain ), $args );
}

/*
 * Localize format string, with context.
 *
 * @since 2.0.0 Reworked to accept separate args or and array of them.
 * @since 1.0.0
 *
 * @uses _x()
 *
 * @param string $text The format string.
 * @param string $context The context to use.
 * @param string $domain The domain to use.
 * @param mixed  $args   The arguments for vsprintf().
 *                       This can be an array of arguments or separate ones.
 */
function _fx( $text, $context, $domain, $args ) {
	// If $args isn't an array, get all addition arguments and use that
	if ( ! is_array( $args ) ) {
		$args = func_get_args();
		$args = array_slice( $args, 3 );
	}

	return vsprintf( _x( $text, $context, $domain ), $args );
}

/*
 * Echo result of _f().
 *
 * @since 1.0.0
 *
 * @see _f()
 *
 * @param string $text The format string.
 * @param string $domain The domain to use.
 * @params mixed $arg1.. The arguments for vsprintf().
 */
function _ef( $text, $domain ) {
	echo call_user_func_array( '_f', func_get_args() );
}

/*
 * Echo result of _xf().
 *
 * @since 1.0.0
 *
 * @see _fx()
 *
 * @param string $text The format string.
 * @param string $context The context to use.
 * @param string $domain The domain to use.
 * @params mixed $arg1.. The arguments for vsprintf().
 */
function _efx( $text, $context, $domain ) {
	echo call_user_func_array( '_fx', func_get_args() );
}

/*
 * Localize an array of strings.
 *
 * @since 1.0.0
 *
 * @uses __()
 *
 * @param array $array The array to be localized.
 * @param string $domain The domain to use.
 */
function _a( $array, $domain = 'default' ) {
	$_array = array();
	foreach ( $array as $key => $value ) {
		$_array[ $key ] = __( $value, $domain );
	}

	return $_array;
}

/*
 * Localize an array of strings.
 *
 * @since 1.0.0
 *
 * @uses _x()
 *
 * @param array $array The array to be localized.
 * @param string $context The context to use.
 * @param string $domain The domain to use.
 */
function _ax( $array, $context, $domain = 'default' ) {
	$_array = array();
	foreach ( $array as $key => $value ) {
		$_array[ $key ] = _x( $value, $context, $domain );
	}

	return $_array;
}
