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
final class Rewriter {
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
	private static $do_localization = true;

	// =========================
	// ! Property Access/Editing
	// =========================

	/**
	 * Get $do_localization.
	 *
	 * @since 2.0.0
	 *
	 * @return bool The value.
	 */
	public static function will_do_localization() {
		return self::$do_localization;
	}

	/**
	 * Set $do_localization.
	 *
	 * @since 2.0.0
	 *
	 * @return bool The old value.
	 */
	public static function toggle_localization( $value ) {
		$old_value = self::$do_localization;
		self::$do_localization = $value;
		return $old_value;
	}

	/**
	 * Set $do_localization to true.
	 *
	 * @since 2.0.0
	 *
	 * @return bool The old value.
	 */
	public static function enable_localization() {
		return self::toggle_localization( true );
	}

	/**
	 * Set $do_localization to false.
	 *
	 * @since 2.0.0
	 *
	 * @return bool The old value.
	 */
	public static function disable_localization() {
		return self::toggle_localization( false );
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
	 * @uses Registry::languages() to get the active registered languages.
	 * @uses Registry::get() to get the query var and redirection method options.
	 *
	 * @param mixed $url_data        Optional. The URL string or parsed array to process.
	 * @param bool  $return_language Optional. Wether or not to return just the language.
	 *
	 * @return URL The parsed and processed URL object.
	 */
	public static function process_url( $url_data = null, $return_language = false ) {
		$language = null;

		// Get the home URL (unlocalized)
		$home = get_home_url( null, '', 'unlocalized' );

		// Get the list of languages
		$languages = Registry::languages();

		// Copy to $old_url_data for storage
		$old_url_data = $url_data;

		// If no url was passed, build it from the $_SERVER values
		if ( is_null( $url_data ) ) {
			$url_data = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		}

		// Parse it
		$the_url = new URL( $url_data, array(
			'path' => '/',
		) );

		// Check if the language was already part of the arguments
		$query_var = Registry::get( 'query_var' );
		if ( isset( $the_url->args[ $query_var ] ) ) {
			// Ensure it is in fact a valid language
			if ( $language = $languages->get( $the_url->args[ $query_var ] ) ) {
				$the_url->meta['language'] = $language;
			}

			return $the_url;
		} else {
			// Try using the desired method
			switch( Registry::get( 'url_rewrite_method' ) ) {
				case 'domain':
					// Get the subdirectory if found, see if it matches a language
					if ( preg_match( '#^([a-z\-]+)\.(.+)#i', $the_url->host, $matches ) ) {
						if ( $language = $languages->get( $matches[1] ) ) {
							// Update language with the matched
							$the_url->meta['language'] = $language;

							// Update the host with the remainder
							$the_url->host = $matches[2];
						}
					}
					break;

				case 'path':
					// Get the path of the home URL, with trailing slash
					$home_path = trailingslashit( parse_url( $home, PHP_URL_PATH ) );

					// Subtract the home path from the start of the path provided
					$path = substr( $the_url->path, strlen( $home_path ) );

					// If there's nothing left of $path (e.g. if $path == $home) abort
					if ( ! $path ) {
						break;
					}

					// Get the subdirectory if found, see if it matches a language
					if ( preg_match( '#^([a-z\-]+)(?:/(.*)|$)#i', $path, $matches ) ) {
						if ( $language = $languages->get( $matches[1] ) ) {
							// Update language with the matched
							$the_url->meta['language'] = $language;

							// Update the path with the remainder
							$the_url->path = '/' . $matches[2];
						}
					}
					break;
			}
		}

		/**
		 * Filter the $the_url object.
		 *
		 * @since 2.0.0
		 *
		 * @param URL   $the_url      The updated URL object.
		 * @param mixed $old_url_data The original URL data.
		 */
		$the_url = apply_filters( 'nlingual_process_url', $the_url, $old_url_data );

		// Return just the language if desired
		if ( $return_language ) {
			return $the_url->meta['language'];
		}

		return $the_url;
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
	 * @uses Registry::is_language_default() to check if the language provided is the default.
	 * @uses Registry::get() to get the skip_default_l10n, url_rewrite_method and query_var options.
	 *
	 * @param string $url        The URL to parse.
	 * @param mixed  $language   Optional. The desired language to localize to.
	 * @param bool   $relocalize Optional. Wether or not to relocalize the url if it already is.
	 *
	 * @throws Exception If the language requested does not exist.
	 *
	 * @return string The new localized URL.
	 */
	public static function localize_url( $url, $language = null, $relocalize = false ) {
		// If localization is disabled, abort
		if ( ! self::$do_localization ) {
			return $url;
		}

		// Ensure $language is a Language, defaulting to current
		if ( ! validate_language( $language, true ) ) {
			// Throw exception if not found
			throw new Exception( 'The language requested does not exist: ' . maybe_serialize( $language ), NL_ERR_NOTFOUND );
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
		$home = get_home_url( null, '', 'unlocalized' );

		// Check if it's just a URI,
		// prefix with domain of home URL if so
		if ( ! parse_url( $url, PHP_URL_HOST ) ) {
			$url = $home . $url;
		}

		// Copy the URL
		$old_url = $url;

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
				$url = self::delocalize_url( $url );
			}

			// Process
			$the_url = new URL( $url );

			// If no language could be gleaned,
			// and provided it's not a wordpress internal URL,
			// AND if we're not in the default language (provided skip_defalt_l10n is on)
			// Go ahead and localize the URL
			if ( ( ! isset( $the_url->meta['language'] ) || is_null( $the_url->meta['language'] ) )
			&& ! preg_match( '#^wp-([\w-]+.php|(admin|content|includes)/)#', $the_url->path )
			&& ( ! Registry::is_language_default( $language ) || ! Registry::get( 'skip_default_l10n' ) ) ) {
				switch ( Registry::get( 'url_rewrite_method' ) ) {
					case 'domain':
						// Prepend hostname with language slug
						$the_url->host = "{$language->slug}.{$the_url->host}";
						break;
					case 'path':
						// Add language slug to path (after any home path)
						$home_path = parse_url( $home, PHP_URL_PATH ) ?: '';
						$request_path = substr( $the_url->path, strlen( $home_path ) );

						// Trim excess slashes
						$home_path = trim( $home_path, '/' );
						$request_path = trim( $request_path, '/' );

						// Build the new path
						$the_url->path = trailingslashit( $home_path . "/{$language->slug}/" . $request_path );
						break;
					default:
						// Add the query argument
						$query_var = Registry::get( 'query_var' );
						$the_url->args[ $query_var ] = $language->slug;
				}

				// Compile to string
				$url = $the_url->build();
			}
		}

		/**
		 * Filter the new localized URL.
		 *
		 * @since 2.0.0
		 *
		 * @param string   $url        The new localized URL.
		 * @param string   $old_url    The original URL passed to this function.
		 * @param Language $language   The language requested.
		 * @param bool     $relocalize Whether or not to forcibly relocalize the URL.
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
	 *
	 * @param string $url The URL to delocalize.
	 *
	 * @return string The delocalized url.
	 */
	public static function delocalize_url( $url ) {
		// Parse and process the url
		$the_url = self::process_url( $url );

		// If a language was extracted, rebuild the $url
		if ( isset( $the_url->meta['language'] ) ) {
			$url = $the_url->build();
		}

		return $url;
	}

	/**
	 * Attempt to localize the current page URL.
	 *
	 * @since 2.2.0 Now uses get_search_link() to create the search URL.
	 * @since 2.0.0
	 *
	 * @uses Registry::get() to check for backwards compatibility.
	 * @uses Registry::current_language() to get the current Language object.
	 * @uses System::switch_language() to switch to the specified language.
	 * @uses Registry::restore_language() to switch back to the previous language.
	 * @uses Rewriter::localize_url() to localize the current URI as-is.
	 * @uses Rewriter::process_url() to localize the original URL of the page.
	 * @uses Translator::get_post_translation() to get the page/post's translation.
	 *
	 * @param mixed $language The language to localize the current page for.
	 *
	 * @throws Exception If the language requested does not exist.
	 *
	 * @return string The localized URL.
	 */
	public static function localize_here( $language = null ) {
		// Ensure $language is a Language, defaulting to current
		if ( ! validate_language( $language, true ) ) {
			// Throw exception if not found
			throw new Exception( 'The language requested does not exist: ' . maybe_serialize( $language ), NL_ERR_NOTFOUND );
		}

		// First, check if it's the queried object is a post
		$queried_object = get_queried_object();
		if ( is_a( $queried_object, 'WP_Post' ) ) {
			// Get the permalink for the translation in the specified language
			$translation = Translator::get_post_translation( $queried_object->ID, $language, true );
			$url = get_permalink( $translation );

			// Relocalize the URL
			$url = self::localize_url( $url, $language, true );
		} else {
			// Switch to the language (redundant for current one but doesn't matter)
			System::switch_language( $language );

			// Now try various other conditional tags...

			// Front page? just use home_url()
			if ( is_front_page() ) {
				$url = home_url( '/' );
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
				$url = get_search_link( get_query_var( 's' ) );
			}
			// Give up and just get the orginally requested URL, relocalized
			else {
				$url = self::localize_url( NL_ORIGINAL_URL, null, true );
			}

			// Switch back to the current language
			System::restore_language();
		}

		// Now parse the URL
		$the_url = new URL( $url );

		// Merge the args with the $_GET variables
		$the_url->args = array_merge( $the_url->args, $_GET );

		// Check if paged and add entry to $url_data
		if ( is_paged() ) {
			$the_url->paged = get_query_var( 'paged' );
		}

		// Build the URL
		$url = $the_url->build();

		/**
		 * Filter the URL.
		 *
		 * @since 2.0.0
		 *
		 * @param string   $url      The new URL.
		 * @param URL      $the_url  The parsed URL object.
		 * @param Language $language The desired language to localize for.
		 */
		$url = apply_filters( 'nlingual_localize_here', $url, $the_url, $language );

		return $url;
	}

	// =========================
	// ! URL Generation
	// =========================

	/**
	 * Get the permalink for a post in the desired language.
	 *
	 * @since 2.0.0
	 *
	 * @uses validate_language() to validate the language and get the Language object.
	 * @uses Translator::get_object_translation() to get the post's translation.
	 *
	 * @param int   $post_id  The ID of the post.
	 * @param mixed $language Optional. The desired language (defaults to current).
	 *
	 * @return string The translation's permalink.
	 */
	public static function get_permalink( $post_id, $language = null ) {
		// Ensure $language is a Language, defaulting to current
		if ( ! validate_language( $language, true ) ) {
			// Doesn't exit; resort to original permalink
			return get_permalink( $post_id );
		}

		// Get the translation counterpart
		$translation_id = self::get_post_translation( $post_id, $language );

		// Return the translations permalink
		return get_permalink( $translation_id );
	}

	/**
	 * Get the translated version of the post based on the path.
	 *
	 * @since 2.0.0
	 *
	 * @uses Translator::get_permalink() to get the permalink for the matched post's translation.
	 *
	 * @param string $path      The path (in /parent/child/ or /page/ form) of the page to find.
	 * @param string $post_type The post type it should be looking for (defaults to page).
	 * @param mixed  $language  The slug of the language requested (defaults to current language).
	 *
	 * @return string The translated permalink.
	 */
	public static function translate_link( $path, $post_type = null, $language = null ) {
		// Default to page for post type
		if ( ! $post_type ) {
			$post_type = 'page';
		}

		// Get the ID based on the path provided
		$post = get_page_by_path( trim( $path, '/' ), OBJECT, $post_type );

		// Abort if not found
		if ( ! $post ) {
			return null;
		}

		// Get the translation's permalink
		return self::get_permalink( $post->ID, $language );
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
