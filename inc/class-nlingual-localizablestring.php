<?php
/**
 * nLingual Localizable String Model
 *
 * @package nLingual
 * @subpackage Structures
 *
 * @since 2.0.0
 */

namespace nLingual;

/**
 * The Localizable String Model
 *
 * Provides a predictable interface for accessing
 * properties of strings that have been registered
 * by the Localizer.
 *
 * @package nLingual
 * @subpackage Structures
 *
 * @api
 *
 * @since 2.0.0
 *
 * @property int    $id       The unique ID of the string.
 * @property string $key      The database key of the string.
 * @property string $type     The type of string, for reference purposes.
 * @property array  $screen   The screen the strings belong to (property/value pair to match).
 * @property string $field    The name of the field the string is for.
 * @property string $field_id The ID of the field the string is for.
 */

class LocalizableString extends Model {
	// =========================
	// ! Properties
	// =========================

	/**
	 * The list of properties for the string (with defaults).
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	protected $properties = array(
		'id'          => '',
		'key'         => null,
		'type'        => 'option',
		'screen'      => array(),
		'field'       => null,
		'field_id'    => null,
	);

	// =========================
	// ! Methods
	// =========================

	/**
	 * Setup the property values.
	 *
	 * @internal Should only be created by the Localizer.
	 *
	 * @since 2.0.0
	 *
	 * @see Language::$properties for a list of allowed values.
	 *
	 * @uses Model::__construct() to setup the values.
	 *
	 * @param int   $id     The ID of the string.
	 * @param array $values The property values.
	 */
	public function __construct( $id, $values ) {
		$values['id'] = $id;

		// Assume key is the same as id if not set
		if ( is_null( $values['key'] ) ) {
			$values['key'] = $id;
		}

		// Assume field is the same as key if not set
		if ( is_null( $values['field'] ) ) {
			$values['field'] = $values['key'];
		}

		// Assume field_id is the same as field if not set
		if ( is_null( $values['field_id'] ) ) {
			$values['field_id'] = $values['field'];
		}

		// Convert the screen value to appropriate format
		$values['screen'] = (array) $values['screen'];
		if ( count( $values['screen'] ) == 1 ) {
			// Assume we're looking for the ID
			$values['screen'] = array( 'id', $values['screen'] );
		}

		parent::__construct( $values );
	}
}
