<?php
namespace nLingual;

/**
 * nLingual Language Model
 *
 * @package nLingual
 *
 * @since 2.0.0
 */

class Language {
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
	 * @var int
	 */
	protected $language_id = 0;

	/**
	 * The active status of the language
	 *
	 * @since 2.0.0
	 *
	 * @access protected
	 *
	 * @var bool
	 */
	protected $active = true;

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
	 * @access protected
	 *
	 * @var string
	 */
	protected $short_name = '';

	/**
	 * The local to use for this language (i.e. MO file)
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
	 * The disired order of the language.
	 *
	 * @since 2.0.0
	 *
	 * @access protected
	 *
	 * @var int
	 */
	protected $list_order = '';

	/**
	 * The list of properties this object should have.
	 *
	 * @since 2.0.0
	 *
	 * @access protected
	 *
	 * @var array
	 */
	protected static $properties = array( 'language_id', 'slug', 'system_name', 'native_name', 'short_name', 'iso_code', 'locale_name', 'list_order', 'active' );

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
	 * @param array $values The property values.
	 */
	public function __construct( $values ) {
		// Set all values provided
		foreach ( static::$properties as $key ) {
			if ( isset( $values[ $key ] ) ) {
				$this->$key = $values[ $key ];
			}
		}

		// Ensure $language_id is integer
		$this->language_id = intval( $this->language_id );

		// Ensure $active is boolean
		$this->active = (bool) intval( $this->active );
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

		foreach ( static::$properties as $key ) {
			$data[ $key ] = $this->$key;
		}

		return $data;
	}
}