<?php
/**
 * nLingual Shared Utilities
 *
 * @package nLingual
 *
 * @since 2.0.0
 */

namespace nLingual;

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
	protected static function _language( &$language ) {
		if ( is_a( $language, __NAMESPACE__ . '\Language' ) ) {
			return true;
		} else {
			$language = Registry::languages()->get( $language );
		}

		return (bool) $language;
	}
}
