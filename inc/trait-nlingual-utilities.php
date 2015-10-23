<?php
namespace nLingual;

/**
 * nLingual Translation API
 *
 * @package nLingual
 *
 * @since 2.0.0
 */

trait Utilities {
	/**
	 * Convert $language passed into proper object format.
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::languages() to validate and retrieve the passed language.
	 *
	 * @param mixed &$language The language to be converted.
	 *
	 * @return bool If the language was successfully converted.
	 */
	protected static function _lang( &$language ) {
		if ( is_a( $language, __NAMESPACE__ . '\Language' ) ) {
			return true;
		} else {
			$language = Registry::languages()->get( $language );
		}

		return (bool) $language;
	}
}