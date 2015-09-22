<?php
namespace nLingual;

/**
 * nLingual URL Rewriter
 *
 * @package nLingual
 *
 * @since 2.0.0
 */

class Rewriter {
	// =========================
	// ! URL Processing Methods
	// =========================

	/**
	 * Process the hostname and extract the language specified.
	 *
	 * @since 2.0.0
	 *
	 * @param string  $host The hostname to process.
	 * @param mixed  &$lang Optional the variable to store the language in.
	 *
	 * @return string The processed hostname with the language removed.
	 */
	public static function process_url_domain( $host, &$lang = false ) {
		// Check if a language slug is present, and if it's an existing language
		if ( preg_match( '#^([a-z]{2})\.#i', $host, $matches ) ) {
			// Update lang with the matched
			$lang = Registry::languages()->get( $matches[1] );

			// Remove the language slug from $host
			$host = substr( $host, 3 );
		}

		return $host;
	}

	/**
	 * Process the path and extract the language.
	 *
	 * @since 2.0.0
	 *
	 * @param string  $path The path to process.
	 * @param mixed  &$lang Optional the variable to store the language in.
	 *
	 * @return string The processed path with the language removed.
	 */
	public static function process_url_path( $path, &$lang = null ) {
		// Get the path of the home URL, with trailing slash
		$home = trailingslashit( parse_url( site_url(), PHP_URL_PATH ) );

		// Subtract the home path from the start of the path provided
		$path = substr( $path, strlen( $home ) );

		// If there's nothing left of $path (e.g. if $path == $home) return $home
		if ( ! $path ) {
			return $home;
		}

		// Check if a language slug is present, and if it's an existing language
		if ( preg_match( '#^([a-z]{2})(/|$)#i', $path, $matches ) ) {
			// Update lang with the matched
			$lang = Registry::languages()->get( $matches[1] );

			// Remove the language slug from $path
			$path = substr( $path, 2 );
		}

		return $path;
	}

	/**
	 * Process a URL and get the language.
	 *
	 * @since 2.0.0
	 *
	 * @param mixed $url_data The URL string or parsed array to proces.
	 *
	 * @return array An array of the resulting language and true hostname/path.
	 */
	public static function process_url( $url_data = null ) {
		$lang = null;

		// Copy to $old_url_data for storage
		$old_url_data = $url_data;

		// If no url was passed, build it from the $_SERVER values
		if ( is_null( $url_data ) ) {
			$url_data = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		}

		// If not already an array, parse it
		if ( ! is_array( $url_data ) ) {
			$url_data = parse_url( $url_data );
		}

		// Ensure default host/path/query values are set
		$url_data = wp_parse_args( $url_data, array(
			'host'  => '',
			'path'  => '/',
			'query' => '',
			'lang'  => ''
		) );

		// Parse the query string into new args entry
		parse_str( $url_data['query'], $url_data['args'] );

		// Check if the lang was already part of the arguments
		if ( isset( $url_data['args']['lang'] ) ) {
			$url_data['lang'] = $url_data['args']['lang'];
			unset( $url_data['args']['lang'] );

			// Return the results now
			return $url_data;
		}

		// Try using the desired method
		switch( Registry::get( 'detection_method' ) ) {
			case 'subdomain':
				$url_data['host'] = static::process_url_domain( $url_data['host'], $url_data['lang'] );
				break;
			case 'subdirectory':
				$url_data['path'] = static::process_url_path( $url_data['path'], $url_data['lang'] );
				break;
		}

		/**
		 * Filter the $url_data array.
		 *
		 * @since 1.1.3
		 *
		 * @param array $url_data     The updated URL data.
		 * @param array $old_url_data The original URL data.
		 *
		 * @return array The filtered $url_data array.
		 */
		$url_data = apply_filters( 'nLingual_process_url', $url_data, $old_url_data );

		return $url_data;
	}
}