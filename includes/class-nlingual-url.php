<?php
/**
 * nLingual URL Model
 *
 * @package nLingual
 * @subpackage Structures
 *
 * @since 2.0.0
 */

namespace nLingual;

/**
 * The URL Model
 *
 * Provides a portable way of parsing/editing URLs.
 *
 * @api
 *
 * @since 2.0.0
 */
final class URL extends Model {
	// =========================
	// ! Properties
	// =========================

	/**
	 * The URL scheme (http, https, etc.)
	 *
	 * @since 2.0.0
	 *
	 * @var string
	 */
	public $scheme = null;

	/**
	 * The username.
	 *
	 * @since 2.0.0
	 *
	 * @var string
	 */
	public $user = null;

	/**
	 * The password.
	 *
	 * @since 2.0.0
	 *
	 * @var string
	 */
	public $password = null;

	/**
	 * The host.
	 *
	 * @since 2.0.0
	 *
	 * @var string
	 */
	public $host = null;

	/**
	 * The port number.
	 *
	 * @since 2.0.0
	 *
	 * @var string
	 */
	public $port = null;

	/**
	 * The URI path.
	 *
	 * @since 2.0.0
	 *
	 * @var string
	 */
	public $path = null;

	/**
	 * The query string.
	 *
	 * @since 2.0.0
	 *
	 * @var string
	 */
	public $query = null;

	/**
	 * The document fragment.
	 *
	 * @since 2.0.0
	 *
	 * @var string
	 */
	public $fragment = null;

	// Special-use properties

	/**
	 * The processed query string.
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	public $args = array();

	/**
	 * The page parameter.
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	public $page = null;

	/**
	 * Miscellaneous meta data.
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	public $meta = array();

	// =========================
	// ! Parse/Build Methods
	// =========================

	/**
	 * Parse the URL (if string) and load it's components.
	 *
	 * @since 2.10.0 Use wp_parse_url(). Drop localizing of error message.
	 * @since 2.0.0
	 *
	 * @param string|array $url_data The URL to process.
	 * @param array        $defaults Optional. The default property values.
	 *
	 * @throws Exception If $url is not an string or array.
	 */
	public function parse( $url_data, $defaults = array() ) {
		// If it's a string, parse it
		if ( is_string( $url_data ) ) {
			$url_data = wp_parse_url( $url_data );
		}
		// If not an array, throw an exception
		elseif ( ! is_array( $url_data ) ) {
			throw new Exception( __CLASS__ . '::parse() expects a string or array, recieved ' . gettype( $url_data ), NL_ERR_UNSUPPORTED );
		}

		// If defaults were provided, parse them
		$url_data = array_merge( $defaults, $url_data );

		// Update the provided properties
		$this->update( $url_data );

		// If there's a query to parse, parse it
		if ( $this->query ) {
			parse_str( $this->query, $this->args );
		}
	}

	/**
	 * Export the URL back into a string.
	 *
	 * @since 2.9.2 Check for permalink struct for $page compiling.
	 * @since 2.9.1 Drop $query use, rewrite $args compiling.
	 * @since 2.9.0 Fixed path being modified during build.
	 * @since 2.8.3 Refixed path + page handling.
	 * @since 2.6.0 Fixed path + page handling.
	 * @since 2.2.0 Ensure / path is included.
	 * @since 2.0.0
	 *
	 * @return string $url The URL in string form.
	 */
	public function build() {
		global $wp_rewrite;

		$url = '';

		// Start with the scheme
		if ( $this->scheme ) {
			$url .= $this->scheme . '://';

			// Next add the host
			if ( $this->host ) {
				// First add username/password (unlikely but why not?)
				if ( $this->user ) {
					$url .= $this->user;

					// Add password
					if ( $this->pass ) {
						$url .= ':' . $this->pass;
					}

					// Finish with @ symbol
					if ( $this->user ) {
						$url .= '@';
					}
				}

				$url .= $this->host;

				// Add the port
				if ( $this->port ) {
					$url .= ':' . $this->port;
				}
			}
		}

		$path = $this->path;

		// If the page property is present, add it to args or path
		if ( $this->page ) {
			if ( ! get_option( 'permalink_structure' ) ) {
				$this->args['page'] = $this->page;
			} else {
				$path .= user_trailingslashit( "{$wp_rewrite->pagination_base}/" . $this->page, 'single_paged' );
			}
		}

		// Add the path
		$url .= '/';
		if ( $path ) {
			// Ensure leading slash
			$url .= ltrim( $path, '/' );
		}

		// Add the query args
		if ( $this->args ) {
			$args = array_combine(
				rawurlencode_deep( array_keys( $this->args ) ),
				rawurlencode_deep( array_values( $this->args ) )
			);

			$url = add_query_arg( $args, $url );
		}

		// Finishe with the fragment
		if ( $this->fragment ) {
			$url .= '#' . $this->fragment;
		}

		return $url;
	}

	// =========================
	// ! Setup/Magic Methods
	// =========================

	/**
	 * Parse a URL string and load it's components.
	 *
	 * @see URL::parse()
	 */
	public function __construct( $url = null, $defaults = array() ) {
		// If a URL is provided, parse it
		if ( ! is_null( $url ) ) {
			$this->parse( $url, $defaults );
		}
	}

	/**
	 * Convert to string; alias of URL::build().
	 *
	 * @see URL::build()
	 */
	public function __toString() {
		return $this->build();
	}
}
