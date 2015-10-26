<?php
/**
 * nLingual Languages Collection
 *
 * @package nLingual
 *
 * @since 2.0.0
 */

namespace nLingual;

class Languages implements \Iterator {
	// =========================
	// ! Properties
	// =========================

	/**
	 * The Language object index
	 *
	 * @since 2.0.0
	 *
	 * @access protected
	 *
	 * @var array
	 */
	protected $languages = array();

	// =========================
	// ! Iterator Methods
	// =========================

	/**
	 * Rewind iterator to the first element.
	 *
	 * @since 2.0.0
	 */
	public function rewind() {
		reset( $this->languages );
	}

	/**
	 * Return to the current element.
	 *
	 * @since 2.0.0
	 *
	 * @return mixed The current element.
	 */
	public function current() {
		return current( $this->languages );
	}

	/**
	 * Return to the key of the current element.
	 *
	 * @since 2.0.0
	 *
	 * @return int|string The current key.
	 */
	public function key() {
		return key( $this->languages );
	}

	/**
	 * Advance to the next element.
	 *
	 * @since 2.0.0
	 *
	 * @return mixed The next element.
	 */
	public function next() {
		return next( $this->languages );
	}

	/**
	 * Check if current position is valid.
	 *
	 * @since 2.0.0
	 *
	 * @return bool Wether or not the position is valid.
	 */
	public function valid() {
		$key = key( $this->languages );
		return isset( $this->languages[ $key ] );
	}

	/**
	 * Get the length of the array.
	 *
	 * @since 2.0.0
	 *
	 * @return int The length of the array.
	 */
	public function count() {
		return count( $this->languages );
	}

	// =========================
	// ! Methods
	// =========================

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
				$this->add( $language, false );
			}
		}

		// Sort the collection
		$this->sort();
	}

	/**
	 * Sort the object index.
	 *
	 * @since 2.0.0
	 *
	 * @param string $field Optional The field to sort by (defaults to list_order).
	 * @param string $order Optional Which way to sort (defaults to ascending).
	 */
	public function sort( $field = 'list_order', $order = 'asc' ) {
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
	 * Return a filtered copy of the collection.
	 *
	 * @since 2.0.0
	 *
	 * @param string $filter Optional The property to filter by.
	 * @param string $value  Optional A specific value to filter by.
	 *
	 * @return nLingual\Languages A new collection of languages
	 */
	public function filter( $filter = null, $value = true ) {
		if ( is_null( $filter ) ) {
			return $this;
		}

		$filtered = new static;
		foreach ( $this as $language ) {
			if ( $language->$filter === $value ) {
				$filtered->add( $language, false );
			}
		}

		return $filtered;
	}

	/**
	 * Retrieve a language from the index.
	 *
	 * @since 2.0.0
	 *
	 * @param int|string $value A value to retrieve the language by.
	 * @param string     $field Optional The field to search in (defaults to id or slug).
	 *
	 * @return bool|nLingual_Language The language if found (false if not).
	 */
	public function get( $value, $field = null ) {
		// Guess $field based on nature of $language if not provided
		if ( is_null( $field ) ) {
			if ( is_numeric( $value ) ) {
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
	 * @param bool                    $sort     Wether or not to sort after adding.
	 */
	public function add( $language, $sort = true ) {
		// Create new Language object from array if needed
		if ( is_array( $language ) ) {
			$language = new Language( $language );
		}

		// Add to the index if successful
		if ( $language ) {
			$this->languages[] = $language;
		}

		if ( $sort ) {
			// Sort the collection
			$this->sort();
		}
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
	 * @param string $field Optional A specific field to get
	 *                      instead of the whole language object.
	 *
	 * @return array A numeric array of the languages.
	 */
	public function export( $field = null ) {
		$data = array();

		foreach ( $this->languages as $language ) {
			$data[ $language->id ] = $field ? $language->$field : $language->export();
		}

		return $data;
	}

	/**
	 * Auto-sort once unserialized.
	 *
	 * @since 2.0.0
	 */
	public function __wakeup() {
		$this->sort();
	}
}