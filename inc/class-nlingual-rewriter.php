<?php
/**
 * nLingual URL Rewriting API
 *
 * @package nLingual
 * @subpackage Tools
 *
 * @since 2.0.0
 */

namespace nLingual;

/**
 * The Rewriter System
 *
 * A toolkit for converting URLs to their localized versions.
 *
 * @api
 *
 * @since 2.0.0
 */

class Rewriter {
	// =========================
	// ! Properties
	// =========================

	/**
	 * Internal flag for wether or not to localize a URL.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	protected static $do_localization = true;

	// =========================
	// ! Property Access/Editing
	// =========================

	/**
	 * Set $do_localization to true.
	 *
	 * @since 2.0.0
	 */
	public static function enable_localization() {
		static::$do_localization = true;
	}

	/**
	 * Set $do_localization to false.
	 *
	 * @since 2.0.0
	 */
	public static function disable_localization() {
		static::$do_localization = false;
	}

	// =========================
	// ! URL Parsing/Building
	// =========================

	/**
	 * Parse a URL, including it's query string, with optional default values.
	 *
	 * An extension of PHP's native parse_url().
	 *
	 * @since 2.0.0
	 *
	 * @param string $url      The url to parse.
	 * @param array  $defaults Optional. The default values for the components list.
	 *
	 * @return array The URL components list.
	 */
	public static function parse_url( $url, $defaults = array() ) {
		// If it's a string, parse into components list
		if ( is_string( $url ) ) {
			$url = parse_url( $url );
		}
		// If not an array, fail
		elseif ( ! is_array( $url ) ) {
			return false;
		}

		// Parse components with defaults
		$url = wp_parse_args( $url, $defaults );

		// If a query is set, parse that into a new args entry
		if ( isset( $url['query'] ) ) {
			parse_str( $url['query'], $url['args'] );
			unset( $url['query'] );
		}

		return $url;
	}

	/**
	 * Build a URL from provided parts.
	 *
	 * Primarily a stand-in for http_build_url since it may
	 * not be available. Can return a relative URL if no host
	 * is specified and also supports a paged parameter for
	 * pagination permalinks.
	 *
	 * @since 2.0.0
	 *
	 * @param array $data The parsed URL parts.
	 *		@option string "scheme"   the URL scheme (http, https, etc.).
	 *		@option string "user"     the username.
	 *		@option string "pass"     the password.
	 *		@option string "host"     the host name.
	 *		@option int    "port"     the port number.
	 *		@option string "path"     the URI path.
	 *		@option int    "paged"    the paged parameter (for WordPress)
	 *		@option string "query"    the query string.
	 *		@option array  "args"     the query args (overrites "query" option).
	 *		@option string "fragment" the document fragment.
	 *
	 * @return string The assembled URL.
	 */
	public static function build_url( $data ) {
		$url = '';

		// Ensure all useable keys are present
		$data = array_merge( array(
			'scheme'   => null,
			'user'     => null,
			'pass'     => null,
			'host'     => null,
			'port'     => null,
			'path'     => null,
			'paged'    => null,
			'query'    => null,
			'args'     => null,
			'fragment' => null,
		), $data );

		// Build the query string if args are present
		if ( is_array( $data['args'] ) ) {
			$data['query'] = http_build_query( $data['args'] );
		}

		// Add the paged parameter to the path if present
		if ( $data['paged'] ) {
			$data['path'] .= sprintf( 'page/%d/', $data['paged'] );
		}

		// Start with the scheme
		if ( $data['scheme'] ) {
			$url .= $data['scheme'] . '://';

			// Next add the host
			if ( $data['host'] ) {
				// First add username/password (unlikely but why not?)
				if ( $data['user'] ) {
					$url .= $data['user'];

					// Add password
					if ( $data['pass'] ) {
						$url .= ':' . $data['pass'];
					}

					// Finish with @ symbol
					if ( $data['user'] ) {
						$url .= '@';
					}
				}

				$url .= $data['host'];

				// Add the port
				if ( $data['port'] ) {
					$url .= ':' . $data['port'];
				}
			}
		}

		// Add the path
		if ( $data['path'] ) {
			$url .= $data['path'];
		}

		// Add the query string
		if ( $data['query'] ) {
			$url .= '?' . $data['query'];
		}

		// Finishe with the fragment
		if ( $data['fragment'] ) {
			$url .= '#' . $data['fragment'];
		}

		return $url;
	}

	// =========================
	// ! URL Processing
	// =========================

	/**
	 * Process a URL and get the language.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @uses NL_UNLOCALIZED to get the unlocalized home URL.
	 * @uses Registry::languages() to get the active registered languages.
	 * @uses Rewriter::parse_url() to parse the URL data.
	 * @uses Registry::get() to get the query var and redirection method options.
	 *
	 * @param mixed  $url_data     The URL string or parsed array to process.
	 * @param string $single_field Optional A specific field to return instead of the full array.
	 *
	 * @return array|string An array of the resulting language and true hostname/path, or the requested field.
	 */
	public static function process_url( $url_data = null, $single_field = false ) {
		$language = null;

		// Get the home URL (unlocalized)
		$home = get_home_url( null, '', NL_UNLOCALIZED );

		// Get the list of active languages
		$active_languages = Registry::languages( 'active' );

		// Copy to $old_url_data for storage
		$old_url_data = $url_data;

		// If no url was passed, build it from the $_SERVER values
		if ( is_null( $url_data ) ) {
			$url_data = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		}

		// Parse it
		$url_data = static::parse_url( $url_data, array(
			'host'      => '',
			'path'      => '/',
			'query'     => '',
			'args'      => array(),
			'language'  => null
		) );

		// Check if the language was already part of the arguments
		$query_var = Registry::get( 'query_var' );
		if ( isset( $url_data['args'][ $query_var ] ) ) {
			$url_data['language'] = $url_data['args'][ $query_var ];
			unset( $url_data['args'][ $query_var ] );

			// Return the results now
			return $url_data;
		}

		$host = $url_data['host'];
		$path = $url_data['path'];

		// Try using the desired method
		switch( Registry::get( 'url_rewrite_method' ) ) {
			case 'domain':
				// Get the subdirectory if found, see if it matches a language
				if ( preg_match( '#^([a-z\-]+)\.(.+)#i', $host, $matches ) ) {
					if ( $language = $active_languages->get( $matches[1] ) ) {
						// Update language with the matched
						$url_data['language'] = $language;

						// Replace $path with the remainder of the URL
						$url_data['host'] = $matches[2];
					}
				}
				break;

			case 'path':
				// Get the path of the home URL, with trailing slash
				$home = trailingslashit( parse_url( $home, PHP_URL_PATH ) );

				// Subtract the home path from the start of the path provided
				$path = substr( $path, strlen( $home ) );

				// If there's nothing left of $path (e.g. if $path == $home) abort
				if ( ! $path ) {
					break;
				}

				// Get the subdirectory if found, see if it matches a language
				if ( preg_match( '#^([a-z\-]+)(?:/(.*)|$)#i', $path, $matches ) ) {
					if ( $language = $active_languages->get( $matches[1] ) ) {
						// Update language with the matched
						$url_data['language'] = $language;

						// Replace $path with the remainder of the URL
						$path = $matches[2];
					}
				}

				$url_data['path'] = $home . $path;
				break;
		}

		/**
		 * Filter the $url_data array.
		 *
		 * @since 2.0.0
		 *
		 * @param array $url_data     The updated URL data.
		 * @param array $old_url_data The original URL data.
		 */
		$url_data = apply_filters( 'nlingual_process_url', $url_data, $old_url_data );

		// Return specified field if desired
		if ( $single_field ) {
			return isset( $url_data[ $single_field ] ) ? $url_data[ $single_field ] : null;
		}

		return $url_data;
	}

	// =========================
	// ! URL Conversion
	// =========================

	/**
	 * Apply basic localization to a URL.
	 *
	 * This will add the language slug subdomain/subdirecty/query var as needed.
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::current_language() to get the current Language object if not passed.
	 * @uses Rewriter::delocalize_url() to clean the URL for relocalizing if desired.
	 * @uses Rewriter::process_url() to process the URL into it's components.
	 * @uses Registry::default_language() to get the default Language object for comparison.
	 * @uses Registry::get() to get the skip_default_l10n, url_rewrite_method and query_var options.
	 * @uses Rewriter::build_url() to assemble the new URL from the modified components.
	 *
	 * @param string   $url        The URL to parse.
	 * @param Language $language   Optional. The desired language to localize to.
	 * @param bool     $relocalize Optional. Wether or not to relocalize the url if it already is.
	 *
	 * @return string The new localized URL.
	 */
	public static function localize_url( $url, Language $language = null, $relocalize = false ) {
		// If localization is disabled, abort
		if ( ! static::$do_localization ) {
			return $url;
		}

		/**
		 * Filter wether or not to localize the URL.
		 *
		 * @since 2.0.0
		 *
		 * @param bool     $bool       Whether or not to localize the URL. Default true.
		 * @param string   $url        The URL to parse.
		 * @param Language $language   The desired language to localize to.
		 * @param bool     $relocalize Wether or not to relocalize the url if it already is.
		 */
		if ( ! apply_filters( 'nlingual_do_localize_url', true, $url, $language, $relocalize ) ) {
			return $url;
		}

		// If no $url is passed, use current URL
		if ( is_null( $url ) ) {
			$url = NL_ORIGINAL_URL;
		}

		// Get the home URL (unlocalized)
		$home = get_home_url( null, '', NL_UNLOCALIZED );

		// Check if it's just a URI,
		// prefix with domain of home URL if so
		if ( ! parse_url( $url, PHP_URL_HOST ) ) {
			$url = $home . $url;
		}

		// Copy the URL
		$old_url = $url;

		// Default to the current language
		if ( ! $language ) {
			$language = Registry::current_language();
		}

		// Create an identifier for the url for caching
		$cache_id = "$url({$language->id})";

		// Check if this URL has been taken care of before,
		// return cached result
		$cached = wp_cache_get( $cache_id, 'nlingual:url', false, $found );
		if ( $found ) {
			return $cached;
		}

		// Proceed if it's a local url
		if ( strpos( $url, $home ) === 0 ) {
			// If $relocalize, delocalize first
			if ( $relocalize ) {
				$url = static::delocalize_url( $url );
			}

			// Process
			$url_data = static::process_url( $url );

			// If no language could be gleaned,
			// and provided it's not a wordpress internal URL,
			// AND if we're not in the default language (provided skip_defalt_l10n is on)
			// Go ahead and localize the URL
			if ( is_null( $url_data['language'] )
			&& ! preg_match( '#^wp-([\w-]+.php|(admin|content|includes)/)#', $url_data['path'] )
			&& ( $language !== Registry::default_language() || ! Registry::get( 'skip_default_l10n' ) ) ) {
				switch ( Registry::get( 'url_rewrite_method' ) ) {
					case 'domain':
						// Prepend hostname with language slug
						$url_data['host'] = "{$language->slug}.{$url_data['host']}";
						break;
					case 'path':
						// Add language slug to path (after any home path)
						$home_path = parse_url( $home, PHP_URL_PATH ) ?: '';
						$request_path = substr( $url_data['path'], strlen( $home_path ) );

						// Trim excess slashes
						$home_path = trim( $home_path, '/' );
						$request_path = trim( $request_path, '/' );

						// Build the new path
						$url_data['path'] = trailingslashit( $home_path . "/{$language->slug}/" . $request_path );
						break;
					default:
						// Add the query argument
						$query_var = Registry::get( 'query_var' );
						$url_data['args'][ $query_var ] = $language->slug;
				}

				$url = static::build_url( $url_data );
			}
		}

		/**
		 * Filter the new localized URL.
		 *
		 * @since 2.0.0
		 *
		 * @param string  $url        The new localized URL.
		 * @param string  $old_url    The original URL passed to this function.
		 * @param string  $language       The slug of the language requested.
		 * @param bool    $relocalize Whether or not to forcibly relocalize the URL.
		 */
		$url = apply_filters( 'nlingual_localize_url', $url, $old_url, $language, $relocalize );

		// Store the URL in the cache
		wp_cache_set( $cache_id, $url, 'nlingual:url' );

		return $url;
	}

	/**
	 * Delocalize a URL; remove language information.
	 *
	 * @since 2.0.0
	 *
	 * @uses Rewriter::process_url() to extract the language if present.
	 * @uses Rewriter::build_url() to remake the URL sans-language.
	 *
	 * @param string $url The URL to delocalize.
	 *
	 * @return string The delocalized url.
	 */
	public static function delocalize_url( $url ) {
		// Parse and process the url
		$url_data = static::process_url( $url );

		// If a language was extracted, rebuild the $url
		if ( $url_data['language'] ) {
			$url = static::build_url( $url_data );
		}

		return $url;
	}

	/**
	 * Attempt to localize the current page URL.
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::get() to check for backwards compatibility.
	 * @uses Registry::current_language() to get the current Language object.
	 * @uses Registry::switch_language() to switch to the specified language.
	 * @uses Registry::restore_language() to switch back to the previous language.
	 * @uses Rewriter::localize_url() to localize the current URI as-is.
	 * @uses Rewriter::process_url() to localize the original URL of the page.
	 * @uses Rewriter::build_url() to build the localized URL from it's components.
	 * @uses Translator::get_post_translation() to get the page/post's translation.
	 *
	 * @param Language $language The language to localize the current page for.
	 *
	 * @return string The localized URL.
	 */
	public static function localize_here( Language $language = null ) {
		// Default to the current language
		if ( ! $language ) {
			$language = Registry::current_language();
		}

		// First, check if it's the queried object is a post
		$queried_object = get_queried_object();
		if ( is_a( $queried_object, 'WP_Post' ) ) {
			// Get the permalink for the translation in the specified language
			$translation = Translator::get_post_translation( $queried_object->ID, $language, true );
			$url = get_permalink( $translation );

			// Relocalize the URL
			$url = static::localize_url( $url, $language, true );
		} else {
			// Switch to the language (redundant for current one but doesn't matter)
			Registry::switch_language( $language );

			// Now try various other conditional tags...

			// Front page? just use home_url()
			if ( is_front_page() ) {
				$url = home_url();
			}
			// Term page? Get the term link
			elseif ( is_tax() || is_tag() || is_category() ) {
				$url = get_term_link( get_queried_object() );
			}
			// Post type archive? Get the link
			elseif ( is_post_type_archive() ) {
				$url = get_post_type_archive_link( get_queried_object()->name );
			}
			// Author archive? Get the link
			elseif ( is_author() ) {
				$url = get_author_posts_link( get_queried_object_id() );
			}
			// Date archive? Get link
			elseif ( is_day() ) {
				$url = get_day_link( get_query_var( 'year' ), get_query_var( 'month' ), get_query_var( 'day' ) );
			}
			// Month archive? Get link
			elseif ( is_month() ) {
				$url = get_month_link( get_query_var( 'year' ), get_query_var( 'month' ) );
			}
			// Year archive? Get link
			elseif ( is_year() ) {
				$url = get_year_link( get_query_var( 'year' ) );
			}
			// Search page? Rebuild the link
			elseif ( is_search() ) {
				$url = home_url( '/?s=' . get_query_var( 's' ) );
			}
			// Give up and just get the orginally requested URL, relocalized
			else {
				$url = static::localize_url( NL_ORIGINAL_URL, null, true );

				// Check if backwards compatibility is needed
				if ( Registry::get( 'backwards_compatible' ) ) {
					/**
					 * Filter the localized original URL.
					 *
					 * @since 2.0.0
					 *
					 * @param string $url      The URL to be filtered.
					 * @param string $language The slug of the language to localize to.
					 */
					$url = apply_filters( 'nLingual_localize_here', $url, $language->slug );
				}
			}

			// Switch back to the current language
			Registry::restore_language();
		}

		// Now parse the URL
		$url_data = static::parse_url( $url, array( 'args' => array() ) );

		// Merge the args with the $_GET variables
		$url_data['args'] = wp_parse_args( $url_data['args'], $_GET );

		// Check if paged and add entry to $url_data
		if ( is_paged() ) {
			$url_data['paged'] .= get_query_var( 'paged' );
		}

		// Build the URL
		$url = static::build_url( $url_data );

		/**
		 * Filter the URL.
		 *
		 * @since 2.0.0
		 *
		 * @param string   $url      The new URL.
		 * @param array    $url_data The parsed URL data.
		 * @param Language $language The desired language to localize for.
		 */
		$url_data = apply_filters( 'nlingual_localize_here', $url, $url_data, $language );

		return $url;
	}

	// =========================
	// ! Utilities
	// =========================

	/**
	 * Get a list of links for each language.
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::current_language() to get the current language.
	 * @uses Registry::languages() to loop through all active registered languages.
	 * @uses Rewriter::localize_here() to get each URL.
	 *
	 * @param bool   $skip_current Wether or not to skip the current language.
	 * @param string $index_by     What language property to use for the array index (typically id or slug).
	 *
	 * @return array A list of URLs for the current page in each language.
	 */
	public static function get_links( $skip_current = false, $index_by = 'id' ) {
		// Get the current language
		$current_language = Registry::current_language( 'id' );

		$links = array();

		// Loop through each language
		foreach ( Registry::languages( 'active' ) as $language ) {
			// Skip if this is the current language and it's not wanted
			if ( $skip_current && $language->id == $current_language ) {
				continue;
			}

			// Get the localized version of the current URL
			$url = Rewriter::localize_here( $language );

			// Add the entry for this language
			$links[ $language->$index_by ] = $url;
		}

		return $links;
	}
}
