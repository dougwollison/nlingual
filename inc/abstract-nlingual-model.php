<?php
/**
 * nLingual Abstract Model
 *
 * @package nLingual
 *
 * @since 2.0.0
 */

namespace nLingual;

abstract class SimpleObject {
	/**
	 * Setup the property values.
	 *
	 * @since 2.0.0
	 *
	 * @uses static::$properties
	 *
	 * @param array $values The property values.
	 */
	public function __construct( $values ) {
		$values = wp_parse_args( $values, static::$properties );

		// Set all values provided
		foreach ( static::$properties as $key => $default ) {
			if ( isset( $values[ $key ] ) ) {
				$this->$key = $values[ $key ];
			}
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
		if ( property_exists( $this, $name ) ) {
			return $this->$name;
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
		if ( property_exists( $this, $name ) ) {
			$this->$name = $value;
		}
		return null;
	}

	/**
	 * Convert to a simple array.
	 *
	 * @since 2.0.0
	 *
	 * @uses static::$properties
	 *
	 * @return array An associative array of properites/values.
	 */
	public function export() {
		$data = array();

		foreach ( static::$properties as $key => $default ) {
			$data[ $key ] = $this->$key;
		}

		return $data;
	}
}