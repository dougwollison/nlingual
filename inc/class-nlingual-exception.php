<?php
/**
 * nLingual Exception
 *
 * @package nLingual
 *
 * @since 2.0.0
 */

namespace nLingual;

/**
 * Custom Exception Class
 *
 * Used in the event of a serious error within
 * the nLingual system.
 *
 * @package nLingual
 *
 * @api
 *
 * @since 2.0.0
 */

class Exception extends \Exception {
	/**
	 * The exception constructor, message required.
	 *
	 * @since 2.0.0
	 *
	 * @param string     $message  The error message.
	 * @param int        $code     Optional The error code.
	 * @param \Exception $previous Optional The previous exception in the chain.
	 */
    public function __construct($message, $code = 0, Exception $previous = null) {
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

	    // Append the relevant stack trace info based on error code
	    $trace = $this->getTrace();
	    switch ( $this->code ) {
		    case NL_ERR_UNSUPPORTED_METHOD:
		    	$message .= " in {$trace[1]['file']} on line {$trace[1]['line']}";
		    	break;

		    default:
		    	$message .= "\nStack trace:\n" . $this->getTraceAsString();
	    }

	    return $message;
    }
}
