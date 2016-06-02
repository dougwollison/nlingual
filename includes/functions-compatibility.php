<?php
/**
 * nLingual Compatibilty Functions
 *
 * @package nLingual
 * @subpackage Backwards_Compatibilty
 *
 * @since 2.0.0
 */

use nLingual\Registry   as Registry;
use nLingual\Translator as Translator;
use nLingual\Rewriter   as Rewriter;

// =========================
// ! Old Template Functions
// =========================

/**
 * Alias of Registry::get(), with error handling.
 *
 * @since 2.0.0
 *
 * @param string $option The name of the option to retrieve.
 *
 * @return mixed The value of that option (null if not found).
 */
function nL_get_option( $option ) {
	// Attempt to retrieve the desired option.
	try {
		return Registry::get( $option );
	} catch ( Exception $e ) {
		// get_var and post_var have been merged into query_var
		if ( $option == 'get_var' || $option == 'post_var' ) {
			return nl_get_option( 'query_var' );
		}
	}

	// Not found
	return null;
}

/**
 * @see Registry::default_language()
 */
function nL_default_lang() {
	return Registry::default_language( 'slug' );
}

/**
 * @see Registry::current_language()
 */
function nL_current_lang() {
	return Registry::current_language( 'slug' );
}

/**
 * @see Registry::is_current_language()
 */
function nL_is_lang( $language ) {
	return Registry::is_current_language( $language );
}

/**
 * @see Registry::in_default_language()
 */
function nL_is_default() {
	return Registry::in_default_language();
}

/**
 * Get the query var.
 *
 * @see nl_get_option()
 */
function nL_query_var() {
	return nL_get_option( 'query_var' );
}

/**
 * @see Translator::get_post_language()
 */
function nL_get_post_lang( $id ) {
	return Translator::get_post_language( $id );
}

/**
 * @see Translator::get_post_translation()
 */
function nL_get_translation( $id, $language = null, $return_self = true ) {
	// Default to current language
	if ( ! $language ) {
		$language = Registry::current_language();
	}

	$translation_id = Translator::get_post_translation( $id, $language, $return_self );

	return $translation_id;
}

/**
 * @see Translator::get_post_translations()
 */
function nL_associated_posts( $id, $include_self = false ) {
	$translations = Translator::get_post_translations( $id, $include_self );

	return $translations;
}

/**
 * Get a desired language, either a specific field or the whole thing.
 *
 * @since 2.0.0
 *
 * @api
 *
 * @see Registry::get_language() for details.
 *
 * @uses Registry::current_language() to get the current language ID if needed.
 * @uses Languages->get() to get the language specified.
 * @uses Language->dump() to get the language in array form.
 *
 * @param bool|mixed $field    The name of the field, TRUE to get the whole language as an array.
 * @param int|string $language The id or slug of the language to retrieve. NULL for current.
 *
 * @return mixed The desired language array or field.
 */
function nL_get_lang( $field = null, $language = null ) {
	if ( is_null( $language ) ) {
		$language = Registry::current_language( 'id' );
	}

	// Handle fetching of the entire language as an array
	if ( $field === true ) {
		$result = Registry::get_language( $language )->dump();
		// Fill fields for old names
		$result['lang_id'] =& $result['id'];
		$result['iso']     =& $result['iso_code'];
		$result['mo']      =& $result['locale_name'];
		return $result;
	}

	// Handle renamed fields
	$field_aliases = array(
		'lang_id' => 'id',
		'name'    => 'system_name',
		'native'  => 'native_name',
		'order'   => 'list_order',
		'iso'     => 'iso_code',
		'mo'      => 'locale_name',
	);
	if ( isset( $field_aliases[ $field ] ) ) {
		$field = $field_aliases[ $field ];
	}

	// Fetch the language
	return Registry::get_language( $language, $field );
}

/**
 * @see nl_get_lang()
 */
function nL_lang_id( $slug = null ) {
	return nl_get_lang( 'id', $slug );
}

/**
 * @see nl_get_lang()
 */
function nL_lang_slug( $lang_id = null ) {
	return nl_get_lang( 'slug', $lang_id );
}

/**
 * @see Registry::set_language()
 */
function nl_set_lang( $lang, $lock = false ) {
	return Registry::set_language( $lang, $lock );
}

/**
 * @see Rewriter::get_links()
 */
function nL_get_lang_links( $skip_current = false ) {
	return Rewriter::get_links( $skip_current, 'slug' );
}

/**
 * Build an HTML inline list of the language links.
 *
 * @since 2.0.0
 *
 * @api
 *
 * @see Rewriter::get_links() for how the links are retrieved.
 *
 * @param string $prefix Optional. A prefix to place before the links (should end with a space).
 * @param string $sep    Optioanl A separator to use when putting the links together.
 * @param bool   $skip_current Optional. Wether or not to leave out the link for the current language.
 */
function nL_print_lang_links( $prefix = '', $sep = ' ', $skip_current = false ) {
	$links = nl_get_lang_links( $skip_current );

	foreach ( $links as $slug => &$link ) {
		$link = sprintf( '<a href="%s">%s</a>', $link, nl_get_lang( 'native', $slug ) );
	}

	$html = $prefix . implode( $sep, $links );

	echo $html;
}

/**
 * Backwards compatibility support for the old split-language method.
 *
 * @since 2.0.0
 *
 * @api
 *
 * @param string $text      The text to split up.
 * @param mixed  $language  Optional. The language to get the matching version for (defaults to current).
 * @param string $separator Optional. The separator to use when splitting the text (defaults to one defined under nLingual before 2.0.0).
 * @param bool   $force     Optional. Wether or not to force the split to happen instead of only when outside the admin.
 *
 * @return string The part of the text corresponding to the language desired.
 */
function nL_split_langs( $text, $language = null, $separator = null, $force = false ) {
	// Ensure $language is a Language
	if ( ! nLingual\validate_language( $language, true ) ) {
		// Abort if not found
		return $text;
	}

	$index = $language->list_order;

	if ( is_null( $separator ) ) {
		$separator = Registry::get( '_old_separator' );
	}

	if ( ! $separator ) {
		return $text;
	}

	if ( is_admin() && ! $force ) {
		return $text;
	}

	$separator = preg_quote( $separator, '/' );
	$parts = preg_split( "/\s*$separator\s*/", $text );

	$text = $parts[0];
	if ( isset( $parts[ $index ] ) ) {
		$text = $parts[ $index ];
	}

	return $text;
}

// =========================
// ! Old Hooks
// =========================

/**
 * Get the translation of the post in the current language.
 *
 * @since 2.0.0
 *
 * @api
 *
 * @param int $id The post ID.
 *
 * @return int The translation ID if found/applicable.
 */
function nLingual_get_curlang_version( $id ) {
	// Only filter if not in the admin
	if ( ! is_admin() ) {
		$id = nL_get_translation( $id );
	}
	return $id;
}
