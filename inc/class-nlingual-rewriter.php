<?php
/**
 * nLingual URL Rewriter
 *
 * @package nLingual
 *
 * @since 2.0.0
 */

namespace nLingual;

class Rewriter {
	/**
	 * Internal flag for wether or not to localize a URL.
	 *
	 * @since 2.0.0
	 *
	 * @access procted
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
	// ! URL Building
	// =========================

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
	 * @since 2.0.0
	 *
	 * @uses NL_UNLOCALIZED to get the unlocalized home URL.
	 * @uses Registry::get() to get the query var and redirection method options.
	 * @uses Registry::languages() to validate and retrieve the parsed language.
	 *
	 * @param mixed $url_data The URL string or parsed array to proces.
	 *
	 * @return array An array of the resulting language and true hostname/path.
	 */
	public static function process_url( $url_data = null ) {
		$language = null;

		// Get the home URL (unlocalized)
		$home = get_home_url( null, '', NL_UNLOCALIZED );

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
			'language'  => null
		) );

		// Parse the query string into new args entry
		parse_str( $url_data['query'], $url_data['args'] );

		// Check if the language was already part of the arguments
		$query_var = Registry::get( 'query_var' );
		if ( isset( $url_data['args'][ $query_var ] ) ) {
			$url_data['language'] = $url_data['args'][ $query_var ];
			unset( $url_data['args'][ $query_var ] );

			// Return the results now
			return $url_data;
		}

		// Shortcuts to the necessary parts
		$host =& $url_data['host'];
		$path =& $url_data['path'];
		$language =& $url_data['language'];

		// Try using the desired method
		switch( Registry::get( 'redirection_method' ) ) {
			case NL_REDIRECT_USING_DOMAIN:
				// Check if a language slug is present, and if it's an existing language
				if ( preg_match( '#^([a-z]{2})\.#i', $host, $matches ) ) {
					// Update language with the matched
					$language = Registry::languages()->get( $matches[1] );

					// Remove the language slug from $host
					$host = substr( $host, 3 );
				}
				break;

			case NL_REDIRECT_USING_PATH:
				// Get the path of the home URL, with trailing slash
				$home = trailingslashit( parse_url( $home, PHP_URL_PATH ) );

				// Subtract the home path from the start of the path provided
				$path = substr( $path, strlen( $home ) );

				// If there's nothing left of $path (e.g. if $path == $home) just use $home
				if ( ! $path ) {
					$path = $home;
					break;
				}

				// Check if a language slug is present, and if it's an existing language
				if ( preg_match( '#^([a-z]{2})(/|$)#i', $path, $matches ) ) {
					// Update language with the matched
					$language = Registry::languages()->get( $matches[1] );

					// Remove the language slug from $path
					$path = substr( $path, 2 );
				}
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
		$url_data = apply_filters( 'nlingual_process_url', $url_data, $old_url_data );

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
	 * @uses Registry::cache_get() to check if this URL has already been localized.
	 * @uses Rewriter::delocalize_url() to clean the URL for relocalizing if desired.
	 * @uses Rewriter::process_url() to process the URL into it's components.
	 * @uses Registry::default_language() to get the default Language object for comparison.
	 * @uses Registry::get() to get the skip_default_l10n, redirection_method and query_var options.
	 * @uses Rewriter::build_url() to assemble the new URL from the modified components.
	 * @uses Registry::cache_set() to store the result for future reuse.
	 *
	 * @param string   $url        The URL or parsed URL data.
	 * @param Language $language   Optional The desired language to localize to.
	 * @param bool     $relocalize Optional Wether or not to relocalize the url if it already is.
	 *
	 * @return string The new localized URL.
	 */
	public static function localize_url( $url, Language $language = null, $relocalize = false ) {
		// If localization is disabled, abort
		if ( ! static::$do_localization ) {
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
		$id = "[{$language->id}]$url";

		// Check if this URL has been taken care of before,
		// return cached result
		if ( $cached = Registry::cache_get( 'url', $id ) ) {
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
				switch ( Registry::get( 'redirection_method' ) ) {
					case NL_REDIRECT_USING_DOMAIN:
						// Prepend hostname with language slug
						$url_data['host'] = "{$language->slug}.{$url_data['host']}";
						break;
					case NL_REDIRECT_USING_PATH:
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
		Registry::cache_set( 'url', $id, $url );

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
	 * @uses Registry::current_language() to get the current Language object.
	 * @uses Translator::get_permalink() to get the page/post translation's permalink.
	 * @uses Rewriter::process_url() to localize the original URL of the page.
	 * @uses Rewriter::localize_url() to localize the current URI as-is.
	 * @uses Rewriter::build_url() to build the localized URL from it's components.
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

		// Try various conditional tags

		// Front page? just use home_url()
		if ( is_front_page() ) {
			$url = home_url();
		} else
		// Home page? Get the translation permalink
		if ( is_home() ) {
			$page = get_option( 'page_for_posts' );
			$url = Translator::get_permalink( $page, $language );
		} else
		// Singular? Get the translation permalink
		if ( is_singular() ) {
			$post = get_queried_object_id();
			$url = Translator::get_permalink( $post, $language );
		} else
		// Term page? Get the term link
		if ( is_tax() || is_tag() || is_category() ) {
			$url = get_term_link( get_queried_object() );
		} else
		// Post type archive? Get the link
		if ( is_post_type_archive() ) {
			$url = get_post_type_archive_link( get_queried_object()->name );
		} else
		// Author archive? Get the link
		if ( is_author() ) {
			$url = get_author_posts_link( get_queried_object_id() );
		} else
		// Date archive? Get link
		if ( is_day() ) {
			$url = get_day_link( get_query_var( 'year' ), get_query_var( 'month' ), get_query_var( 'day' ) );
		} else
		// Month archive? Get link
		if ( is_month() ) {
			$url = get_month_link( get_query_var( 'year' ), get_query_var( 'month' ) );
		} else
		// Year archive? Get link
		if ( is_year() ) {
			$url = get_year_link( get_query_var( 'year' ) );
		} else
		// Search page? Rebuild the link
		if ( is_search() ) {
			$url = home_url( '/?s=' . get_query_var( 's' ) );
		} else {
			// Give up and just get the orginally requested URL.
			$url = NL_ORIGINAL_URL;
		}

		// Relocalize the URL
		$url = static::localize_url( $url, $language, true );

		// Now parse the URL
		$url_data = parse_url( $url );

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
		 * @param Langauge $language The desired language to localize for.
		 */
		$url_data = apply_filters( 'nLingual_localize_here', $url, $url_data, $language );

		return $url;
	}
}