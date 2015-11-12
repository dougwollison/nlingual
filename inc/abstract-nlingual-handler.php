<?php
/**
 * nLingual Abstract Handler
 *
 * @package nLingual
 * @subpackage Abstracts
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
 * @since 2.0.0
 */

abstract class Handler {
	/**
	 * Initialize common stuff.
	 *
	 * @since 2.0.0
	 */
	public static function init() {
		static::$name = get_called_class();
	}

	/**
	 * Add an internal method to a filter hook.
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
		add_filter( $tag, array( static::$name, $method ), $priority, $accepted_args );
	}

	/**
	 * Alias; add internal method from an action hook.
	 *
	 * @since 2.0.0
	 *
	 * @see Functional::add_filter() for argument details.
	 */
	final public static function add_action() {
		call_user_func_array( 'self::add_filter', func_get_args() );
	}

	/**
	 * Remove an internal method to a filter hook.
	 *
	 * @since 2.0.0
	 *
	 * @see remove_filter() for defails.
	 *
	 * @param string $tag      The name of the filter to hook the $method to.
	 * @param string $method   The name of the called class' method to run when applied.
	 * @param int    $priority Optional. The priority to use for this particular callback.
	 */
	final public static function remove_filter( $tag, $method, $priority = 10 ) {
		remove_filter( $tag, array( static::$name, $method ), $priority );
	}

	/**
	 * Alias; remove internal method from an action hook.
	 *
	 * @since 2.0.0
	 *
	 * @see Functional::remove_filter() for argument details.
	 */
	final public static function remove_action() {
		call_user_func_array( 'self::remove_filter', func_get_args() );
	}

	/**
	 * Add an internal method to a filter hook if it hasn't already been.
	 *
	 * @since 2.0.0
	 *
	 * @see Functional::add_filter() for argument defails.
	 *
	 * @param string $tag    The name of the filter to hook the $method to.
	 * @param string $method The name of the called class' method to add/check for.
	 */
	final public static function maybe_add_filter( $tag, $method ) {
		if ( ! has_filter( $tag, array( static::$name, $method ) ) ) {
			call_user_func_array( 'self::add_filter', func_get_args() );
		}
	}

	/**
	 * Alias; Add an internal method to an action hook if it hasn't already been.
	 *
	 * @since 2.0.0
	 *
	 * @see Functional::maybe_add_filter() for argument details
	 */
	final public static function maybe_add_action() {
		call_user_func_array( 'self::maybe_add_filter', func_get_args() );
	}

	/**
	 * Must-have hook setup method.
	 *
	 * @since 2.0.0
	 */
	abstract public static function register_hooks();
}
