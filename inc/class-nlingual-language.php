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
	 * @access protected
	 *
	 * @var int
	 */
	protected $id = '';

	/**
	 * The active status of the language.
	 *
	 * @since 2.0.0
	 *
	 * @access protected
	 *
	 * @var bool
	 */
	protected $active = '';

	/**
	 * The slug of the language for URL use.
	 *
	 * @since 2.0.0
	 *
	 * @access protected
	 *
	 * @var string
	 */
	protected $slug = '';

	/**
	 * The name of the language within the admin.
	 *
	 * @since 2.0.0
	 *
	 * @access protected
	 *
	 * @var string
	 */
	protected $system_name = '';

	/**
	 * The native name of the language.
	 *
	 * @since 2.0.0
	 *
	 * @access protected
	 *
	 * @var string
	 */
	protected $native_name = '';

	/**
	 * A shorthand name for the language.
	 *
	 * @since 2.0.0
	 *
	 * @var string
	 */
	protected $short_name = '';

	/**
	 * The local to use for this language (i.e. MO file).
	 *
	 * @since 2.0.0
	 *
	 * @access protected
	 *
	 * @var string
	 */
	protected $locale_name = '';

	/**
	 * The ISO 639-1 code for the language.
	 *
	 * @since 2.0.0
	 *
	 * @access protected
	 *
	 * @var string
	 */
	protected $iso_code = '';

	/**
	 * The desired order of the language.
	 *
	 * @since 2.0.0
	 *
	 * @access protected
	 *
	 * @var string
	 */
	protected $list_order = 0;

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
