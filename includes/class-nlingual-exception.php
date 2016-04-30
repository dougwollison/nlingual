<?php
/**
 * nLingual Exception
 *
 * @package nLingual
 * @subpackage Helpers
 *
 * @since 2.0.0
 */

namespace nLingual;

/**
 * Error code for an invalid action.
 *
 * @since 2.0.0
 *
 * @var string
 */
define( 'NL_ERR_BADREQUEST', 400 );

/**
 * Error code for a forbidden action.
 *
 * @since 2.0.0
 *
 * @var string
 */
define( 'NL_ERR_FORBIDDEN', 403 );

/**
 * Error code for missing thing.
 *
 * @since 2.0.0
 *
 * @var string
 */
define( 'NL_ERR_NOTFOUND', 404 );

/**
 * Error code for an unsupported action.
 *
 * @since 2.0.0
 *
 * @var string
 */
define( 'NL_ERR_UNSUPPORTED', 405 );

/**
 * The Exceptional Exception
 *
 * Used in the event of a serious error within
 * the nLingual system.
 *
 * @api
 *
 * @since 2.0.0
 */
final class Exception extends \Exception {
	/**
	 * The exception constructor, message required.
	 *
	 * @since 2.0.0
	 *
	 * @param string     $message  The error message.
	 * @param int        $code     Optional The error code.
	 * @param \Exception $previous Optional The previous exception in the chain.
	 */
	public function __construct( $message, $code = 0, Exception $previous = null ) {
        parent::__construct( $message, $code, $previous );
    }

	/**
	 * Ouput a string representation of the exception.
	 *
	 * @since 2.0.0
	 *
	 * @return string The string representation.
	 */
	public function __toString() {
	    // Begin the initial message
	    $message = __CLASS__ . ': ' . $this->message;

	    // Append the stack trace
	    $message .= "\nStack trace:\n" . $this->getTraceAsString();

	    return $message;
    }
}
