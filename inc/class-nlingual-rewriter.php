<?php
namespace nLingual;

/**
 * nLingual URL Rewriter
 *
 * @package nLingual
 *
 * @since 2.0.0
 */

// Flags
define( 'NL_REDIRECT_USING_DOMAIN', 'NL_REDIRECT_USING_DOMAIN' );
define( 'NL_REDIRECT_USING_PATH', 'NL_REDIRECT_USING_PATH' );

class Rewriter {
	// =========================
	// ! URL Building Methods
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
	// ! URL Processing Methods
	// =========================

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
			'lang'  => null
		) );

		// Parse the query string into new args entry
		parse_str( $url_data['query'], $url_data['args'] );

		// Check if the lang was already part of the arguments
		$query_var = Registry::get( 'query_var' );
		if ( isset( $url_data['args'][ $query_var ] ) ) {
			$url_data['lang'] = $url_data['args'][ $query_var ];
			unset( $url_data['args'][ $query_var ] );

			// Return the results now
			return $url_data;
		}

		// Shortcuts to the necessary parts
		$host =& $url_data['host'];
		$path =& $url_data['path'];
		$lang =& $url_data['lang'];

		// Try using the desired method
		switch( Registry::get( 'detection_method' ) ) {
			case NL_REDIRECT_USING_DOMAIN:
				// Check if a language slug is present, and if it's an existing language
				if ( preg_match( '#^([a-z]{2})\.#i', $host, $matches ) ) {
					// Update lang with the matched
					$lang = Registry::languages()->get( $matches[1] );

					// Remove the language slug from $host
					$host = substr( $host, 3 );
				}
				break;
			case NL_REDIRECT_USING_PATH:
				// Get the path of the home URL, with trailing slash
				$home = trailingslashit( parse_url( home_url(), PHP_URL_PATH ) );

				// Subtract the home path from the start of the path provided
				$path = substr( $path, strlen( $home ) );

				// If there's nothing left of $path (e.g. if $path == $home) just use $home
				if ( ! $path ) {
					$path = $home;
					break;
				}

				// Check if a language slug is present, and if it's an existing language
				if ( preg_match( '#^([a-z]{2})(/|$)#i', $path, $matches ) ) {
					// Update lang with the matched
					$lang = Registry::languages()->get( $matches[1] );

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
	// ! URL Translation Methods
	// =========================

	/**
	 * Apply basic localization to a URL.
	 *
	 * This will add the language slug subdomain/subdirecty/query var as needed.
	 *
	 * @since 2.0.0
	 *
	 * @param string   $url        The URL or parsed URL data.
	 * @param Language $language   Optional The desired language to localize to.
	 * @param bool     $relocalize Optional Wether or not to relocalize the url if it already is.
	 *
	 * @return string The new localized URL.
	 */
	public static function localize_url( $url, Language $language = null, $relocalize = false ) {
		// Check if it's just a URI,
		// prefix with domain of home URL if so
		if ( ! parse_url( $url, PHP_URL_HOST ) ) {
			$url = site_url( $url );
		}

		// Copy the URL
		$old_url = $url;

		// Get the home URL
		$home = home_url();

		// Default to the current language
		if ( ! $language ) {
			$language = Registry::current_lang();
		}

		// Create an identifier for the url for caching
		$id = "[{$language->id}]$url";

		// Check if this URL has been taken care of before,
		// return cached result
		if ( $cached = Registry::cache_get( 'url', $id ) ) {
			return $cached;
		}

		// Proceed if it's a local url and also not just the home url
		if ( strpos( $url, $home ) === 0 && $url !== $home ) {
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
			if ( is_null( $url_data['lang'] )
			&& ! preg_match( '#^wp-([\w-]+.php|(admin|content|includes)/)#', $url_data['path'] )
			&& ( $language !== Registry::default_lang() || ! Registry::get_option( 'skip_default_l10n' ) ) ) {
				switch ( Registry::get( 'redirection_method' ) ) {
					case NL_REDIRECT_USING_DOMAIN:
						// Prepend hostname with language slug
						$url_data['host'] = "{$language->slug}.{$url_data['host']}";
						break;
					case NL_REDIRECT_USING_PATH:
						// Prepend path with language slug (after any home path)
						$home_path = parse_url( home_url(), PHP_URL_PATH ) ?: '';
						$request_path = substr( $url_data['path'], strlen( $home_path ) );
						$url_data['path'] = $home_path . "/{$language->slug}" . $request_path;
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
		 * @param string  $lang       The slug of the language requested.
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
	 * @param string $url The URL to delocalize.
	 *
	 * @return string The delocalized url.
	 */
	public static function delocalize_url( $url ) {
		// Parse and process the url
		$url_data = static::process_url( $url );

		// If a language was extracted, rebuild the $url
		if ( $url_data['lang'] ) {
			$url = static::build_url( $url_data );
		}

		return $url;
	}

	/**
	 * Attempt to localize the current page URL.
	 *
	 * @since 2.0.0
	 *
	 * @param Langauge $language The language to localize the current page for.
	 *
	 * @return string The localized URL.
	 */
	public static function localize_here( Language $language = null ) {
		// Default to the current language
		if ( ! $language ) {
			$language = Registry::current_lang();
		}

		$url = null; // For the end result URL
		$here = null; // For a URL to (re)localize

		// Try various conditional tags

		// Front page? just use home_url()
		if ( is_front_page() ) {
			$here = home_url( '/' );
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
			$here = get_term_link( get_queried_object() );
		} else
		// Post type archive? Get the link
		if ( is_post_type_archive() ) {
			$here = get_post_type_archive_link( get_queried_object()->name );
		} else
		// Author archive? Get the link
		if ( is_author() ) {
			$here = get_author_posts_link( get_queried_object_id() );
		} else
		// Date archive? Get link
		if ( is_day() ) {
			$here = get_day_link( get_query_var( 'year' ), get_query_var( 'month' ), get_query_var( 'day' ) );
		} else
		// Month archive? Get link
		if ( is_month() ) {
			$here = get_month_link( get_query_var( 'year' ), get_query_var( 'month' ) );
		} else
		// Year archive? Get link
		if ( is_year() ) {
			$here = get_year_link( get_query_var( 'year' ) );
		} else
		// Search page? Rebuild the link
		if ( is_search() ) {
			$here = home_url( '/?s=' . get_query_var( 's' ) );
		} else {
			// Give up and just use the requested URI.
			$here = $_SERVER['REQUEST_URI'];
		}

		// If $url hasn't been determined, localize $here
		if ( ! $url && $here ) {
			$url = static::localize_url( $here, $language, true );
		}

		// Now parse the URL
		$url_data = parse_url( $url );

		// Check if paged and add entry to $url_data
		if ( is_paged() ) {
			$url_data['paged'] .= get_query_var( 'paged' );
		}

		/**
		 * Filter the URL data array.
		 *
		 * @since 2.0.0
		 *
		 * @param array $url_data The parsed URL data.
		 */
		$url_data = apply_filters( 'nLingual_localize_here_array', $url_data, $language );

		// Build the URL
		$url = static::build_url( $url_data );

		return $url;
	}
}