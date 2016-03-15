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
 * The Languages Iterator
 *
 * An array-like system for storing multiple Language objects.
 * Works like an array within `foreach` loops, and includes
 * methods for sorting, filtering, and searching for languages.
 *
 * @api
 *
 * @since 2.0.0
 */

final class Languages implements \Iterator {
	// =========================
	// ! Properties
	// =========================

	/**
	 * The current position in the array.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @var int
	 */
	private $position = 0;

	/**
	 * The last auto-increment ID.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @var int
	 */
	private $auto_increment = 0;

	/**
	 * The array of Language objects.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	private $items = array();

	// =========================
	// ! Iterator Methods
	// =========================

	/**
	 * Rewind iterator to the first element.
	 *
	 * @since 2.0.0
	 */
	final public function rewind() {
		$this->position = 0;
	}

	/**
	 * Return to the current element.
	 *
	 * @since 2.0.0
	 *
	 * @return mixed The current element.
	 */
	final public function current() {
		return $this->items[ $this->position ];
	}

	/**
	 * Return to the key of the current element.
	 *
	 * @since 2.0.0
	 *
	 * @return int|string The current key.
	 */
	final public function key() {
		return $this->position;
	}

	/**
	 * Advance to the next element.
	 *
	 * @since 2.0.0
	 *
	 * @return mixed The next element.
	 */
	final public function next() {
		++$this->position;
	}

	/**
	 * Check if current position is valid.
	 *
	 * @since 2.0.0
	 *
	 * @return bool Wether or not the position is valid.
	 */
	final public function valid() {
		return isset( $this->items[ $this->position ] );
	}

	/**
	 * Get the length of the array.
	 *
	 * @since 2.0.0
	 *
	 * @return int The length of the array.
	 */
	final public function count() {
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
	 * @param array $languages      Optional. A list of languages to add.
	 * @param int   $auto_increment Optional. An explicit auto_increment value to use.
	 */
	final public function __construct( $languages = array(), $auto_increment = 1 ) {
		if ( is_array( $languages ) && ! empty ( $languages ) ) {
			foreach ( $languages as $language ) {
				$this->add( $language, false );
			}
		}

		// If $auto_increment was 0 but we have items, use the max ID + 1
		if ( $auto_increment == 1 && $this->count() > 0 ) {
			$auto_increment = $this->sort( 'id', 'desc' )->nth( 0 )->id + 1;
		}

		// Sort the collection
		$this->sort();

		// Reset the position
		$this->position = 0;

		// Set the auto_increment
		$this->auto_increment = $auto_increment;
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
	final public function sort( $field = 'list_order', $order = 'asc' ) {
		usort( $this->items, function( $a, $b ) use ( $field ) {
			if ( $a->$field == $b->$field ) {
				return 0;
			}

			return $a->$field > $b->$field ? 1 : -1;
		} );

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
	 * @param string $value  Optional. A specific value to filter by (defaults to TRUE).
	 *
	 * @return nLingual\Languages A new collection of languages
	 */
	final public function filter( $filter = null, $value = null ) {
		// No filter? Return original
		if ( is_null( $filter ) ) {
			return $this;
		}

		// No value? Assume true
		if ( is_null( $value ) ) {
			$value = true;
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
	final public function get( $value, $field = null ) {
		// Guess $field based on nature of $language if not provided
		if ( is_null( $field ) ) {
			// Slug by default
			$field = 'slug';

			if ( is_numeric( $value ) ) {
				// Language ID if numeric
				$field = 'id';
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
	final public function nth( $index ) {
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
	final public function find( $language ) {
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
	final public function add( $language, $sort = true ) {
		// Create new Language object from array if needed
		if ( is_array( $language ) ) {
			$language = new Language( $language );
		}

		// Add to the index if successful
		if ( $language ) {
			// If language has no ID, assign it one
			if ( $language->id == 0 ) {
				$language->id = $this->auto_increment++;
			}
			// Otherwise, if it's higher than the AI, raise it
			elseif ( $language->id >= $this->auto_increment ) {
				$this->auto_increment = $language->id + 1;
			}

			$this->items[] = $language;

			if ( $sort ) {
				// Sort the collection
				$this->sort();
			}
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
	final public function remove( $language ) {
		// Get the object's index
		$index = $this->find( $language );
		if ( $index !== false ) {
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
	final public function match_tag( $language_tag ) {
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
	 * @uses Language::dump() on each Language object.
	 *
	 * @return array An array of the languages.
	 */
	final public function dump() {
		$data = array();

		foreach ( $this as $language ) {
			$data[] = $language->dump();
		}

		return $data;
	}

	/**
	 * Get an keyed array of a single property for each language.
	 *
	 * @since 2.0.0
	 *
	 * @param string $val_field The field to fetch from each entry.
	 * @param bool   $key_field  Optional. The value to use for the key (defaults to id, false for numeric).
	 *
	 * @return array An array of the the selected language properties.
	 */
	final public function pluck( $val_field, $key_field = 'id' ) {
		$data = array();

		foreach ( $this as $i => $language ) {
			$key = $key_field ? $language->$key_field : $i;
			$data[ $key ] = $language->$val_field;
		}

		return $data;
	}

	/**
	 * Export a class-agnostic representation of the object.
	 *
	 * @since 2.0.0
	 *
	 * @uses Languages::dump() to get the items in array form.
	 * @uses Languages::$auto_increment to store the auto_increment value.
	 *
	 * @return array An array with a dump of $items and the $auto_increment.
	 */
	final public function export() {
		$data = array(
			'entries' => $this->dump(),
			'auto_increment' => $this->auto_increment,
		);

		return $data;
	}
}
