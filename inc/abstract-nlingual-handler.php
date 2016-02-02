<?php
/**
 * nLingual Abstract Handler
 *
 * @package nLingual
 * @subpackage Abstracts
 *
 * @internal
 *
 * @since 2.0.0
 */

namespace nLingual;

/**
 * The Handler Framework
 *
 * The basis for the any classes that need to hook into WordPress.
 * Must be initialized after loading (handled by autoloader),
 * and defines aliases to the WordPress Plugin API, adding the
 * specified method of the current class to the specified hook.
 *
 * @package nLingual
 * @subpackage Abstracts
 *
 * @internal
 *
 * @since 2.0.0
 */

abstract class Handler {
	/**
	 * Add an internal method to a filter hook.
	 *
	 * @api
	 *
	 * @since 2.0.0
	 *
	 * @see add_filter() for details.
	 *
	 * @param string $tag           The name of the filter to hook the $method to.
	 * @param string $method        The name of the called classe's method to run when applied.
	 * @param int    $priority      Optional. The priority to use for this particular callback.
	 * @param int    $accepted_args Optional. The number of arguments the callback accepts.
	 */
	final public static function add_filter( $tag, $method, $priority = 10, $accepted_args = 1 ) {
		add_filter( $tag, array( get_called_class(), $method ), $priority, $accepted_args );
	}

	/**
	 * @see Handler::add_filter()
	 */
	final public static function add_action() {
		call_user_func_array( 'self::add_filter', func_get_args() );
	}

	/**
	 * Remove an internal method to a filter hook.
	 *
	 * @api
	 *
	 * @since 2.0.0
	 *
	 * @see remove_filter() for details.
	 *
	 * @param string $tag      The name of the filter to hook the $method to.
	 * @param string $method   The name of the called class' method to run when applied.
	 * @param int    $priority Optional. The priority to use for this particular callback.
	 */
	final public static function remove_filter( $tag, $method, $priority = 10 ) {
		remove_filter( $tag, array( get_called_class(), $method ), $priority );
	}

	/**
	 * @see Handler::remove_filter()
	 */
	final public static function remove_action() {
		call_user_func_array( 'self::remove_filter', func_get_args() );
	}

	/**
	 * Add an internal method to a filter hook if it hasn't already been.
	 *
	 * @api
	 *
	 * @since 2.0.0
	 *
	 * @see Handler::add_filter() for argument details.
	 *
	 * @param string $tag    The name of the filter to hook the $method to.
	 * @param string $method The name of the called class' method to add/check for.
	 */
	final public static function maybe_add_filter( $tag, $method ) {
		if ( ! has_filter( $tag, array( get_called_class(), $method ) ) ) {
			call_user_func_array( 'self::add_filter', func_get_args() );
		}
	}

	/**
	 * @see Handler::maybe_add_filter()
	 */
	final public static function maybe_add_action() {
		call_user_func_array( 'self::maybe_add_filter', func_get_args() );
	}

	/**
	 * Must-have hook setup method.
	 *
	 * @internal Should be called by System::init().
	 *
	 * @since 2.0.0
	 */
	abstract public static function register_hooks();
}
