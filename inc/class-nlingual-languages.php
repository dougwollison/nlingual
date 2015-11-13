<?php
/**
 * nLingual Languages Collection
 *
 * @package nLingual
 * @subpackage Structures
 *
 * @since 2.0.0
 */

namespace nLingual;

/**
 * The Languages Collection
 *
 * An array-like system for storing multiple Language objects.
 * Works like an array within `foreach` loops, and includes
 * methods for sorting, filtering, and searching for languages.
 *
 * @package nLingual
 * @subpackage Structures
 *
 * @since 2.0.0
 */

class Languages implements \Iterator {
	// =========================
	// ! Properties
	// =========================

	/**
	 * The current position in the array.
	 *
	 * @since 2.0.0
	 *
	 * @access protected
	 *
	 * @var int
	 */
	protected $position = 0;

	/**
	 * The array of Language objects.
	 *
	 * @since 2.0.0
	 *
	 * @access protected
	 *
	 * @var array
	 */
	protected $items = array();

	// =========================
	// ! Iterator Methods
	// =========================

	/**
	 * Rewind iterator to the first element.
	 *
	 * @since 2.0.0
	 */
	public function rewind() {
		$this->position = 0;
	}

	/**
	 * Return to the current element.
	 *
	 * @since 2.0.0
	 *
	 * @return mixed The current element.
	 */
	public function current() {
		return $this->items[ $this->position ];
	}

	/**
	 * Return to the key of the current element.
	 *
	 * @since 2.0.0
	 *
	 * @return int|string The current key.
	 */
	public function key() {
		return $this->position;
	}

	/**
	 * Advance to the next element.
	 *
	 * @since 2.0.0
	 *
	 * @return mixed The next element.
	 */
	public function next() {
		++$this->position;
	}

	/**
	 * Check if current position is valid.
	 *
	 * @since 2.0.0
	 *
	 * @return bool Wether or not the position is valid.
	 */
	public function valid() {
		return isset( $this->items[ $this->position ] );
	}

	/**
	 * Get the length of the array.
	 *
	 * @since 2.0.0
	 *
	 * @return int The length of the array.
	 */
	public function count() {
		return count( $this->items );
	}

	// =========================
	// ! Methods
	// =========================

	/**
	 * Setup the collection and add any languages passed.
	 *
	 * @since 2.0.0
	 *
	 * @param array $languages Optional. A list of languages to add.
	 */
	public function __construct( $languages = array() ) {
		if ( is_array( $languages ) && ! empty ( $languages ) ) {
			foreach ( $languages as $language ) {
				$this->add( $language, false );
			}
		}

		// Sort the collection
		$this->sort();

		// Reset the position
		$this->position = 0;
	}

	/**
	 * Sort the object index.
	 *
	 * @since 2.0.0
	 *
	 * @param string $field Optional. The field to sort by (defaults to list_order).
	 * @param string $order Optional. Which way to sort (defaults to ascending).
	 *
	 * @return self.
	 */
	public function sort( $field = 'list_order', $order = 'asc' ) {
		usort( $this->items, function( $a, $b ) use ( $field ) {
			if ( $a->$field == $b->$field ) {
				return 0;
			}

			return $a->$field > $b->$field ? 1 : -1;
		});

		// If not in ascending order, reverse the array
		if ( $order != 'asc' ) {
			$this->items = array_reverse( $this->items );
		}

		return $this;
	}

	/**
	 * Return a filtered copy of the collection.
	 *
	 * @since 2.0.0
	 *
	 * @param string $filter Optional. The property to filter by.
	 * @param string $value  Optional. A specific value to filter by.
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
	 * @param string     $field Optional. The field to search in (defaults to id or slug).
	 *
	 * @return bool|Language The language if found (false if not).
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

		// If $field is "@", return the entry in the $items array for that index
		if ( $field == '@' ) {
			return isset( $this->items[ $value ] ) ? $this->items[ $value ] : false;
		}

		// Loop through all languages and return the first match
		foreach ( $this->items as $language ) {
			if ( $language->$field == $value ) {
				return $language;
			}
		}

		return false;
	}

	/**
	 * Alias of get(); retrieves the language at the specific array index.
	 *
	 * @since 2.0.0
	 *
	 * @see Languages::get() for details.
	 *
	 * @param int $index The index to get the item at.
	 *
	 * @return bool|Language The language if found (false if not).
	 */
	public function nth( $index ) {
		return $this->get( $index, '@' );
	}

	/**
	 * Get the index of the first language matching the provided ID/slug.
	 *
	 * @since 2.0.0
	 *
	 * @param mixed $language The language object, ID or slug.
	 *
	 * @return int|bool The index if found, false otherwise.
	 */
	public function find( $language ) {
		// Get the language object
		if ( ! is_a( $language, __NAMESPACE__ . '\Language' ) ) {
			$language = $this->get( $language );
		}

		// If not found, fail
		if ( ! $language ) {
			return false;
		}

		// Loop through all languages and return index of first match
		foreach ( $this->items as $index => $item ) {
			if ( $item->id == $language->id ) {
				return $index;
			}
		}
	}

	/**
	 * Add a language to the index.
	 *
	 * @since 2.0.0
	 *
	 * @param array|Language $language The language to add.
	 * @param bool           $sort     Wether or not to sort after adding.
	 *
	 * @return self.
	 */
	public function add( $language, $sort = true ) {
		// Create new Language object from array if needed
		if ( is_array( $language ) ) {
			$language = new Language( $language );
		}

		// Add to the index if successful
		if ( $language ) {
			$this->items[] = $language;
		}

		if ( $sort ) {
			// Sort the collection
			$this->sort();
		}

		return $this;
	}

	/**
	 * Remove a language from the index.
	 *
	 * @since 2.0.0
	 *
	 * @param int|string $language The ID or slug of the language to remove.
	 *
	 * @return self.
	 */
	public function remove( $language ) {
		// Get the object's index
		if ( $index = $this->find( $language ) ) {
			// Remove it
			unset( $this->items[ $index ] );
		}

		return $this;
	}

	/**
	 * Try to find a language whose locale_name/iso_code matches the language tag specified.
	 *
	 * @since 2.0.0
	 *
	 * @param string $language_tag The language tag to try and find a match for.
	 *
	 * @return bool|Language The language object if found, false otherwise.
	 */
	public function match_tag( $language_tag ) {
		// Sanitize for looser comparison
		$language_tag = sanitize_tag( $language_tag );

		// Loop through all languages and return the first match
		foreach ( $this->items as $language ) {
			// Try the full locale...
			if ( sanitize_tag( $language->locale_name ) == $language_tag ) {
				return $language;
			}
			// Failing that, try the ISO code
			elseif ( sanitize_tag( $language->iso_code ) == $language_tag ) {
				return $language;
			}
		}

		return false;
	}

	/**
	 * Convert to a simple array for storage.
	 *
	 * @since 2.0.0
	 *
	 * @uses Language::export() on each Language object.
	 *
	 * @param string $field Optional. A specific field to get
	 *                      instead of the whole language object.
	 *
	 * @return array A numeric array of the languages.
	 */
	public function export( $field = null ) {
		$data = array();

		foreach ( $this->items as $language ) {
			$data[ $language->id ] = $field ? $language->$field : $language->export();
		}

		return $data;
	}
}
