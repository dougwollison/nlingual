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
	 * Will not add if it already exists for that hook.
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
		// Only add the filter if it hasn't already been added to the hook
		if ( has_filter( $tag, array( get_called_class(), $method ) ) === false ) {
			add_filter( $tag, array( get_called_class(), $method ), $priority, $accepted_args );
		}
	}

	/**
	 * @see Handler::add_filter()
	 */
	final public static function add_action() {
		call_user_func_array( 'self::add_filter', func_get_args() );
	}

	/**
	 * Remove an internal method from a filter hook.
	 *
	 * @api
	 *
	 * @since 2.0.0
	 *
	 * @see remove_filter() for details.
	 *
	 * @param string $tag    The name of the filter to remove from.
	 * @param string $method The name of the called class' method to remove.
	 *
	 * @return bool|int The priority it originally had (false if wasn't added).
	 */
	final public static function remove_filter( $tag, $method ) {
		// Get old priority, only remove if it had one
		$priority = has_filter( $tag, array( get_called_class(), $method ) );
		if ( $priority !== false ) {
			remove_filter( $tag, array( get_called_class(), $method ), $priority );
			return $priority;
		}
	}

	/**
	 * @see Handler::remove_filter()
	 */
	final public static function remove_action() {
		return call_user_func_array( 'self::remove_filter', func_get_args() );
	}

	/**
	 * Must-have hook setup method.
	 *
	 * @internal Should be called by System::init().
	 *
	 * @since 2.0.0
	 */
	public static function register_hooks() {
		// To be written by extending class
	}
}
