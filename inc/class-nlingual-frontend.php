<?php
namespace nLingual;

/**
 * nLingual Frontend Functionality
 *
 * @package nLingual
 *
 * @since 2.0.0
 */

class Frontend extends Functional {
	// =========================
	// ! Properties
	// =========================

	/**
	 * The name of the class.
	 *
	 * @since 2.0.0
	 *
	 * @access protected (static)
	 *
	 * @var string
	 */
	protected static $name;

	// =========================
	// ! Methods
	// =========================

	/**
	 * Register hooks.
	 *
	 * @since 2.0.0
	 */
	public static function register_hooks() {

	}
}

// Initialize
Frontend::init();