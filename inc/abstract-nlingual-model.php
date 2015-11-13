<?php
/**
 * nLingual Abstract Model
 *
 * @package nLingual
 * @subpackage Abstracts
 *
 * @since 2.0.0
 */

namespace nLingual;

/**
 * The Model Framework
 *
 * A baseline for any Model objects used by nLingual.
 *
 * @package nLingual
 * @subpackage Abstracts
 *
 * @internal
 *
 * @since 2.0.0
 */

abstract class Model {
	/**
	 * Setup the property values.
	 *
	 * @since 2.0.0
	 *
	 * @uses Model::$properties
	 *
	 * @param array $values The property values.
	 */
	public function __construct( $values ) {
		// Set all values provided
		foreach ( $values as $key => $value ) {
			$this->properties[ $key ] = $value;
		}
	}

	/**
	 * Public access to properties (retrieval).
	 *
	 * @since 2.0.0
	 *
	 * @param string $name The name of the property being accessed.
	 *
	 * @return mixed The value of the property.
	 */
	public function __get( $name ) {
		if ( isset( $this->properties[ $name ] ) ) {
			return $this->properties[ $name ];
		}
		return null;
	}

	/**
	 * Public access to properties (assignment).
	 *
	 * @since 2.0.0
	 *
	 * @param string $name  The name of the property being change.
	 * @param mixed  $value The new value of the property.
	 */
	public function __set( $name, $value ) {
		if ( isset( $this->properties[ $name ] ) ) {
			$this->properties[ $name ] = $value;
		}
		return null;
	}

	/**
	 * Convert to a simple array.
	 *
	 * @api
	 *
	 * @since 2.0.0
	 *
	 * @uses Model::$properties
	 *
	 * @return array An associative array of properites/values.
	 */
	public function export() {
		return $this->properties;
	}
}
