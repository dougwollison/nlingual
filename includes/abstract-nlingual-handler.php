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
 * The Handler Hook
 *
 * Simple object representing a hook's settings.
 *
 * @package nLingual
 * @subpackage Utilities
 *
 * @internal
 *
 * @since 2.6.0
 */
final class Hook {
	/**
	 * The tag name.
	 *
	 * @since 2.6.0
	 *
	 * @var string
	 */
	public $tag;

	/**
	 * The method name.
	 *
	 * @since 2.6.0
	 *
	 * @var string
	 */
	public $method;

	/**
	 * The callback priority.
	 *
	 * @since 2.6.0
	 *
	 * @var int
	 */
	public $priority;

	/**
	 * The accepted arguments count.
	 *
	 * @since 2.6.0
	 *
	 * @var int
	 */
	public $accepted_args;

	/**
	 * The disabled status.
	 *
	 * @since 2.6.0
	 *
	 * @var bool
	 */
	public $disabled = false;

	/**
	 * Initialize the hook.
	 *
	 * @since 2.6.0
	 *
	 * @param string $tag           The tag name.
	 * @param string $method        The callback method name.
	 * @param int    $priority      The callback priority.
	 * @param int    $accepted_args The accepted arguments count.
	 */
	public function __construct( $tag, $method, $priority, $accepted_args ) {
		$this->tag = $tag;
		$this->method = $method;
		$this->priority = $priority;
		$this->accepted_args = $accepted_args;
	}
}

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
	 * Retrieve an implemented hook's details.
	 *
	 * @since 2.6.0
	 *
	 * @param string $tag    The name of the filter the hook was applied to.
	 * @param string $method The name of the method that was applied.
	 *
	 * @return Hook|FALSE The hook details, FALSE if not found.
	 */
	final public static function get_hook( $tag, $method ) {
		if ( isset( static::$implemented_hooks[ "$tag/$method" ] ) ) {
			return static::$implemented_hooks[ "$tag/$method" ];
		}

		return false;
	}

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
	final public static function add_hook( $tag, $method, $priority = 10, $accepted_args = 1 ) {
		$class = get_called_class();

		// Only add the filter if it hasn't already been added to the hook
		if ( has_filter( $tag, array( $class, $method ) ) === false ) {
			add_filter( $tag, array( $class, $method ), $priority, $accepted_args );

			static::$implemented_hooks[ "$tag/$method" ] = new Hook( $tag, $method, $priority, $accepted_args );
		}
	}

	/**
	 * @see Handler::add_hook()
	 */
	final public static function add_filter( $tag, $method, $priority = 10, $accepted_args = 1 ) {
		self::add_hook( $tag, $method, $priority, $accepted_args );
	}

	/**
	 * @see Handler::add_hook()
	 */
	final public static function add_action( $tag, $method, $priority = 10, $accepted_args = 1 ) {
		self::add_hook( $tag, $method, $priority, $accepted_args );
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
	 * @param string $tag          The name of the filter to remove from.
	 * @param string $method       The name of the called class' method to remove.
	 * @param bool   $dont_disable Wether or not to skip flagging it at disabled.
	 *
	 * @return bool|int The priority it originally had (false if wasn't added).
	 */
	final public static function remove_hook( $tag, $method, $dont_disable = false ) {
		$class = get_called_class();

		// Retrieve the hook
		if ( $hook = self::get_hook( $tag, $method ) ) {
			// Remove the hook and mark it as disabled unless told not to
			remove_filter( $tag, array( $class, $method ), $hook->priority );
			if ( ! $dont_disable ) {
				$hook->disabled = true;
			}
			return $hook->priority;
		}

		return false;
	}

	/**
	 * @see Handler::remove_hook()
	 */
	final public static function remove_filter( $tag, $method, $dont_disable = false ) {
		return self::remove_hook( $tag, $method, $dont_disable );
	}

	/**
	 * @see Handler::remove_hook()
	 */
	final public static function remove_action( $tag, $method, $dont_disable = false ) {
		return self::remove_hook( $tag, $method, $dont_disable );
	}

	/**
	 * Remove all implemented hooks.
	 *
	 * @api
	 *
	 * @since 2.6.0
	 *
	 * @param bool $force Wether or not to explicitly disable all hooks.
	 */
	public static function remove_all_hooks( $disable = false ) {
		foreach ( static::$implemented_hooks as $hook ) {
			self::remove_hook( $hook->tag, $hook->method, ! $disable );
		}
	}

	/**
	 * Restore a previously disabled hook.
	 *
	 * @api
	 *
	 * @since 2.6.0
	 *
	 * @param string $tag        The name of the filter to remove from.
	 * @param string $method     The name of the class' method to remove.
	 * @param bool   $dont_force Wether or not to ignore a hook's disabled status.
	 */
	final public static function restore_hook( $tag, $method, $dont_force = false ) {
		$class = get_called_class();

		// Retrieve the hook
		if ( $hook = self::get_hook( $tag, $method ) ) {
			// Unless it's disabled (and $dont_force is set), re-add and re-enable it
			if ( ! $dont_force || ! $hook->disabled ) {
				add_filter( $tag, array( $class, $method ), $hook->priority, $hook->accepted_args );
				$hook->disabled = false;
			}
		}
	}

	/**
	 * Restore all implemented hooks.
	 *
	 * @api
	 *
	 * @since 2.6.0
	 *
	 * @param bool $force Wether or not to force-restore all hooks.
	 */
	public static function restore_all_hooks( $force = false ) {
		foreach ( static::$implemented_hooks as $hook ) {
			self::restore_hook( $hook->tag, $hook->method, ! $force );
		}
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
