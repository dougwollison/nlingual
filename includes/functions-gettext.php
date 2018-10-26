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
 * @uses __() to perform localizing.
 *
 * @param string $text   The format string.
 * @param string $domain The domain to use.
 * @param mixed  $args   The arguments for vsprintf().
 *                       This can be an array of arguments or separate ones.
 *
 * @return string The localized, completed string.
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
 * @uses _x() to perform localizing.
 *
 * @param string $text    The format string.
 * @param string $context The context to use.
 * @param string $domain  The domain to use.
 * @param mixed  $args    The arguments for vsprintf().
 *                        This can be an array of arguments or separate ones.
 *
 * @return string The localized, completed string.
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
 * @see _f() for arguments.
 */
function _ef( $text, $domain, $args ) {
	echo call_user_func_array( '_f', func_get_args() );
}

/*
 * Echo result of _xf().
 *
 * @since 1.0.0
 *
 * @see _fx() for arguments.
 */
function _efx( $text, $context, $domain, $args ) {
	echo call_user_func_array( '_fx', func_get_args() );
}
