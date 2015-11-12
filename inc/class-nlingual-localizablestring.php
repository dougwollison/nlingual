<?php
/**
 * nLingual Localizable String Model
 *
 * @package nLingual
 *
 * @since 2.0.0
 */

namespace nLingual;

class LocalizableString extends Model {
	// =========================
	// ! Properties
	// =========================

	/**
	 * The ID of the language for the database
	 *
	 * @since 2.0.0
	 *
	 * @access protected
	 *
	 * @var string
	 */
	protected $id;

	/**
	 * The database key of the string.
	 *
	 * @since 2.0.0
	 *
	 * @access protected
	 *
	 * @var string
	 */
	protected $key;

	/**
	 * The type of string, for reference purposes.
	 *
	 * @since 2.0.0
	 *
	 * @access protected
	 *
	 * @var string
	 */
	protected $type;

	/**
	 * The screen the strings belong to (a property/value pair to match).
	 *
	 * @since 2.0.0
	 *
	 * @access protected
	 *
	 * @var array
	 */
	protected $screen;

	/**
	 * The type of input element the string is for.
	 *
	 * @since 2.0.0
	 *
	 * @access protected
	 *
	 * @var string
	 */
	protected $input;

	/**
	 * The name of the field the string is for.
	 *
	 * @since 2.0.0
	 *
	 * @access protected
	 *
	 * @var string
	 */
	protected $field;

	/**
	 * The ID of the field the string is for.
	 *
	 * @since 2.0.0
	 *
	 * @access protected
	 *
	 * @var string
	 */
	protected $field_id;

	/**
	 * A descriptive title for what the string is for.
	 *
	 * @since 2.0.0
	 *
	 * @access protected
	 *
	 * @var string
	 */
	protected $title;

	/**
	 * Further description for what the string is for.
	 *
	 * @since 2.0.0
	 *
	 * @access protected
	 *
	 * @var string
	 */
	protected $description;

	/**
	 * The whitelist of properties and default values they should have.
	 *
	 * @since 2.0.0
	 *
	 * @access protected
	 *
	 * @var array
	 */
	protected static $properties = array(
		'id'          => '',
		'key'         => null,
		'type'        => 'option',
		'screen'      => array(),
		'input'       => 'text',
		'field'       => null,
		'field_id'    => null,
		'title'       => '',
		'description' => '',
	);

	// =========================
	// ! Methods
	// =========================

	/**
	 * Setup the property values.
	 *
	 * @since 2.0.0
	 *
	 * @uses static::$properties
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