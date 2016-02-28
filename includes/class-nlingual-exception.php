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
 * The Exceptional Exception
 *
 * Used in the event of a serious error within
 * the nLingual system.
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
    public function __construct( $message, $code = 0, Exception $previous = null ) {
        parent::__construct( $message, $code, $previous );
    }

    /**
	 * Return the full name of the function from a backtrace step.
	 *
	 * @since 2.0.0
	 *
	 * @param array $step A step in the backtrace.
	 *
	 * @return string The full name of the function.
	 */
	public static function get_step_function( $step ) {
		$function = '';

		if ( isset( $step['class'] ) ) {
			$function .= $step['class'] . $step['type'];
		}

		$function .=  $step['function'];

		return $function;
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
