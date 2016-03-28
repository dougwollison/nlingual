<?php
/**
 * nLingual Field Model
 *
 * @package nLingual
 * @subpackage Structures
 *
 * @since 2.0.0
 */

namespace nLingual;

/**
 * The Localizer Field Model
 *
 * Provides a predictable interface for accessing
 * properties of fields that have been registered
 * by the Localizer.
 *
 * @api
 *
 * @since 2.0.0
 */
final class Localizer_Field extends Model {
	// =========================
	// ! Properties
	// =========================

	/**
	 * The unique ID of the field.
	 *
	 * @since 2.0.0
	 *
	 * @access public
	 *
	 * @var int
	 */
	public $id;

	/**
	 * The database key of the field.
	 *
	 * @since 2.0.0
	 *
	 * @access public
	 *
	 * @var string
	 */
	public $key;

	/**
	 * The type of field, for reference purposes.
	 *
	 * @since 2.0.0
	 *
	 * @access public
	 *
	 * @var string
	 */
	public $type;

	/**
	 * The screen the fields belong to (property/value pair to match).
	 *
	 * @since 2.0.0
	 *
	 * @access public
	 *
	 * @var array
	 */
	public $screen;

	/**
	 * The name of the field the field is for.
	 *
	 * @since 2.0.0
	 *
	 * @access public
	 *
	 * @var string
	 */
	public $field;

	/**
	 * The ID of the field the field is for.
	 *
	 * @since 2.0.0
	 *
	 * @access public
	 *
	 * @var string
	 */
	public $field_id;

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
	 * @uses Model::__construct() to setup the values.
	 *
	 * @param int   $id     The ID of the field.
	 * @param array $values The property values.
	 *		@option string "key"      The database key to store the field under.
	 *		@option string "type"     The type of field.
	 *		@option array  "screen"   The screen id or property/value pair.
	 *		@option string "field"    The name of the field the field is tied to.
	 *		@option string "field_id" The ID of the field the field is tied to.
	 */
	public function __construct( $id, array $values ) {
		$values = wp_parse_args( $values, array(
			'id' => $id,
			'key' => null,
			'field' => null,
			'field_id' => null,
			'screen' => array(),
		) );

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
