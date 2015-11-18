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
 */

class Language extends Model {
	// =========================
	// ! Properties
	// =========================

	/**
	 * The ID of the language for the database.
	 *
	 * @since 2.0.0
	 *
	 * @access public
	 *
	 * @var int
	 */
	public $id = '';

	/**
	 * The active status of the language.
	 *
	 * @since 2.0.0
	 *
	 * @access public
	 *
	 * @var bool
	 */
	public $active = false;

	/**
	 * The slug of the language for URL use.
	 *
	 * @since 2.0.0
	 *
	 * @access public
	 *
	 * @var string
	 */
	public $slug = '';

	/**
	 * The name of the language within the admin.
	 *
	 * @since 2.0.0
	 *
	 * @access public
	 *
	 * @var string
	 */
	public $system_name = '';

	/**
	 * The native name of the language.
	 *
	 * @since 2.0.0
	 *
	 * @access public
	 *
	 * @var string
	 */
	public $native_name = '';

	/**
	 * A shorthand name for the language.
	 *
	 * @since 2.0.0
	 *
	 * @var string
	 */
	public $short_name = '';

	/**
	 * The local to use for this language (i.e. MO file).
	 *
	 * @since 2.0.0
	 *
	 * @access public
	 *
	 * @var string
	 */
	public $locale_name = '';

	/**
	 * The ISO 639-1 code for the language.
	 *
	 * @since 2.0.0
	 *
	 * @access public
	 *
	 * @var string
	 */
	public $iso_code = '';

	/**
	 * The desired order of the language.
	 *
	 * @since 2.0.0
	 *
	 * @access public
	 *
	 * @var string
	 */
	public $list_order = 0;

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
	 *		@option int    "id"          The ID of the language for the database.
	 *		@option bool   "active"      The active status of the language.
	 *		@option string "slug"        The slug of the language for URL use.
	 *		@option string "system_name" The name of the language within the admin.
	 *		@option string "native_name" The native name of the language.
	 *		@option string "short_name"  A shorthand name for the language.
	 *		@option string "locale_name" The local to use for this language (i.e. MO file).
	 *		@option string "iso_code"    The ISO 639-1 code for the language.
	 *		@option string "list_order"  The disired order of the language.
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
