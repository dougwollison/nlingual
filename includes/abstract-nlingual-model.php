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
	 * @uses Model::update() to actually set the property values.
	 *
	 * @param array $values The property values.
	 */
	public function __construct( $values ) {
		$this->update( $values );
	}

	/**
	 * Update the model with the provided values.
	 *
	 * @since 2.0.0
	 *
	 * @uses Model::$properties.
	 *
	 * @param array $values The values to update.
	 *
	 * @return static The object.
	 */
	public function update( $values ) {
		// Set all values provided
		foreach ( $values as $key => $value ) {
			if ( property_exists( $this, $key ) ) {
				$this->$key = $value;
			}
		}

		return $this;
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
	public function dump() {
		return get_object_vars( $this );
	}
}
