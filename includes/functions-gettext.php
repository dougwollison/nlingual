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

// =========================
// ! Text With Placeholders
// =========================

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

// =========================
// ! Text With Markup
// =========================

/*
 * Localize marked up string.
 *
 * @since 2.9.0
 *
 * @see nLingual\markitup() for format options.
 *
 * @uses __() to perform localizing.
 *
 * @param string $text   The format string.
 * @param string $domain The domain to use.
 *
 * @return string The localized, marked up string.
 */
function _m( $text, $domain ) {
	return nLingual\markitup( __( $text, $domain ) );
}

/*
 * Localize marked up string, with context.
 *
 * @since 2.9.0
 *
 * @see nLingual\markitup() for format options.
 *
 * @uses _x() to perform localizing.
 *
 * @param string $text    The format string.
 * @param string $context The context to use.
 * @param string $domain  The domain to use.
 *
 * @return string The localized, marked up string.
 */
function _mx( $text, $context, $domain ) {
	return nLingual\markitup( _x( $text, $context, $domain ) );
}

/*
 * Localize marked up format string.
 *
 * @since 2.9.0
 *
 * @see nLingual\markitup() for format options.
 *
 * @uses _f() to perform localizing and formatting.
 *
 * @param string $text   The format string.
 * @param string $domain The domain to use.
 * @param mixed  $args   The arguments for vsprintf().
 *                       This can be an array of arguments or separate ones.
 *
 * @return string The localized, completed, marked up string.
 */
function _mf( $text, $domain ) {
	return nLingual\markitup( call_user_func_array( '_f', func_get_args() ) );
}

/*
 * Localize marked up format string, with context.
 *
 * @since 2.9.0
 *
 * @see nLingual\markitup() for format options.
 *
 * @uses _fx() to perform localizing and formatting.
 *
 * @param string $text    The format string.
 * @param string $context The context to use.
 * @param string $domain  The domain to use.
 * @param mixed  $args    The arguments for vsprintf().
 *                        This can be an array of arguments or separate ones.
 *
 * @return string The localized, completed, marked up string.
 */
function _mfx( $text, $context, $domain, $args ) {
	return nLingual\markitup( call_user_func_array( '_fx', func_get_args() ) );
}

/*
 * Echo result of _m().
 *
 * @since 2.9.0
 *
 * @see _m() for arguments.
 */
function _em( $text, $domain ) {
	echo _m( $text, $domain );
}

/*
 * Echo result of _mx().
 *
 * @since 2.9.0
 *
 * @see _mx() for arguments.
 */
function _emx( $text, $domain ) {
	echo _mx( $text, $domain );
}

/*
 * Echo result of _mf().
 *
 * @since 2.9.0
 *
 * @see _mf() for arguments.
 */
function _emf( $text, $domain, $args ) {
	echo call_user_func_array( '_mf', func_get_args() );
}

/*
 * Echo result of _mfx().
 *
 * @since 2.9.0
 *
 * @see _mf() for arguments.
 */
function _emfx( $text, $context, $domain, $args ) {
	echo call_user_func_array( '_mfx', func_get_args() );
}
