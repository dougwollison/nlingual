<?php
/**
 * nLingual Languages Collection
 *
 * @package nLingual
 *
 * @since 2.0.0
 */

class nLingual_Languages {
	/**
	 * The language object index
	 *
	 * @since 2.0.0
	 *
	 * @access protected
	 * @var array
	 */
	protected $languages;

	/**
	 * Setup the collection and add any languages passed.
	 *
	 * @since 2.0.0
	 *
	 * @param array $languages Optional A list of languages to add.
	 */
	public function __construct( $languages = array() ) {
		if ( is_array( $languages ) && ! empty ( $languages ) ) {
			foreach ( $languages as $language ) {
				$this->add( $language );
			}
		}
	}

	/**
	 * Sort the object index.
	 *
	 * @since 2.0.0
	 *
	 * @param string $field Optional The field to sort by (defaults to list_order).
	 * @param string $order Optional Which way to sort (defaults to ascending).
	 */
	public function sort( $field = null, $order = 'asc' ) {
		usort( $this->languages, function( $a, $b ) use ( $field ) {
			if ( $a->$field == $b->$field ) {
				return 0;
			}

			return $a->$field > $b->$field ? 1 : -1;
		});

		// If not in ascending order, reverse the array
		if ( $order != 'asc' ) {
			$this->languages = array_reverse( $this->languages );
		}
	}

	/**
	 * Retrieve a language from the index.
	 *
	 * @since 2.0.0
	 *
	 * @param int|string $value A value to retrieve the langauge by.
	 * @param string     $field Optional The field to search in (defaults to id or slug).
	 *
	 * @return bool|nLingual_Language The language if found (false if not).
	 */
	public function get( $value, $field = null ) {
		// Guess $field based on nature of $language if not provided
		if ( is_null( $field ) ) {
			if ( is_numeric( $language ) ) {
				// Language ID
				$field = 'id';
			} else {
				// Slug
				$field = 'slug';
			}
		}

		// Loop through all languages and return the first match
		foreach ( $this->languages as $language ) {
			if ( $language->$field == $value ) {
				return $language;
			}
		}

		return false;
	}

	/**
	 * Add a language to the index.
	 *
	 * @since 2.0.0
	 *
	 * @param array|nLingual_Language $language The language to add.
	 */
	public function add( $language ) {
		// Create new language object from array if needed
		if ( is_array( $language ) ) {
			$language = new nLingual_Language( $language );
		}

		// Add to the index if successful
		if ( $language ) {
			$this->languages[] = $language;
		}

		// Sort the collection
		$this->sort();
	}

	/**
	 * Remove a language from the index.
	 *
	 * @since 2.0.0
	 *
	 * @param int|string $language The ID or slug of the language to remove.
	 *
	 * @return bool True/False on success/failure.
	 */
	public function remove( $language ) {
		// Get the object's index
		if ( $index = $this->get( $language, 'index' ) ) {
			// Remove it
			unset( $this->languages[ $index ] );
			return true;
		}

		return false;
	}

	/**
	 * Convert to a simple array for storage.
	 *
	 * @since 2.0.0
	 *
	 * @return array A numeric array of the languages.
	 */
	public function export() {
		$data = array();

		foreach ( $this->languages as $language ) {
			$data[] = $language->export();
		}

		return $data;
	}
}