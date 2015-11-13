<?php
/**
 * nLingual Language Model
 *
 * @package nLingual
 * @subpackage Structures
 *
 * @since 2.0.0
 */

namespace nLingual;

/**
 * The Language Model
 *
 * Provides a predictable interface for accessing
 * properties of Languages stored in the database.
 *
 * @package nLingual
 * @subpackage Structures
 *
 * @api
 *
 * @since 2.0.0
 *
 * @property int    $id          The ID of the language for the database.
 * @property bool   $active      The active status of the language.
 * @property string $slug        The slug of the language for URL use.
 * @property string $system_name The name of the language within the admin.
 * @property string $native_name The native name of the language.
 * @property string $short_name  A shorthand name for the language.
 * @property string $locale_name The local to use for this language (i.e. MO file).
 * @property string $iso_code    The ISO 639-1 code for the language.
 * @property string $list_order  The disired order of the language.
 */

class Language extends Model {
	// =========================
	// ! Properties
	// =========================

	/**
	 * The list of properties for the language (with defaults).
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	protected $properties = array(
		'id'          => 0,
		'slug'        => '',
		'system_name' => '',
		'native_name' => '',
		'short_name'  => '',
		'iso_code'    => '',
		'locale_name' => '',
		'list_order'  => 0,
		'active'      => false,
	);

	// =========================
	// ! Methods
	// =========================

	/**
	 * Setup/sanitize the property values.
	 *
	 * @since 2.0.0
	 *
	 * @see Language::$properties for a list of allowed values.
	 *
	 * @uses Model::__construct() to setup the values.
	 *
	 * @param array $values The property values.
	 */
	public function __construct( $values ) {
		// Setup the object with the provided values
		parent::__construct( $values );

		// If language_id was passed, use that for id
		if ( isset( $values['language_id'] ) ) {
			$this->id = $values['language_id'];
		}

		// Ensure $id is integer
		$this->id = intval( $this->id );

		// Ensure $active is boolean
		$this->active = (bool) intval( $this->active );
	}
}
