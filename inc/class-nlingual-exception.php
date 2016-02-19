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

	    // Append the relevant stack trace info based on error code
	    $trace = $this->getTrace();
	    switch ( $this->code ) {
		    case NL_ERR_FORBIDDEN:
		    case NL_ERR_NOTFOUND:
		    case NL_ERR_UNSUPPORTED:
		    	// First, mention the source
		    	$message .= " via " . static::get_step_function( $trace[0] );

		    	// The trigger would be the next step in the trace, unless it's via a magic method
		    	if ( $trace[1]['function'] == 'call_user_func_array' && $trace[2]['function'] == '__callStatic' ) {
			    	// Identify the function that called it and where it was called
			    	$message .= " by " . static::get_step_function( $trace[4] ) . " in {$trace[3]['file']} on line {$trace[3]['line']}";
		    	} else {
			    	// Identify where it was called
			    	$message .= " in {$trace[1]['file']} on line {$trace[1]['line']}";
		    	}
		    	break;

		    default:
		    	// Include everything
		    	$message .= "\nStack trace:\n" . $this->getTraceAsString();
	    }

	    return $message;
    }
}
