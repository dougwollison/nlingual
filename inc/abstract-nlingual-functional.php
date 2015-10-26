<?php
/**
 * nLingual Abstract Functionality
 *
 * @package nLingual
 *
 * @since 2.0.0
 */

namespace nLingual;

abstract class Functional {
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
	 */
	final public static function add_filter( $tag, $method, $priority = 10, $accepted_args = 1 ) {
		add_filter( $tag, array( static::$name, $method ), $priority, $accepted_args );
	}

	/**
	 * Alias; add internal method from an action hook.
	 *
	 * @since 2.0.0
	 */
	final public static function add_action() {
		call_user_func_array( 'self::add_filter', func_get_args() );
	}

	/**
	 * Remove an internal method to a filter hook.
	 *
	 * @since 2.0.0
	 */
	final public static function remove_filter( $tag, $method, $priority = 10 ) {
		remove_filter( $tag, array( static::$name, $method ), $priority );
	}

	/**
	 * Alias; remove internal method from an action hook.
	 *
	 * @since 2.0.0
	 */
	final public static function remove_action() {
		call_user_func_array( 'self::remove_filter', func_get_args() );
	}

	/**
	 * Must-have hook setup method.
	 *
	 * @since 2.0.0
	 */
	abstract public static function register_hooks();
}