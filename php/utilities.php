<?php
/*
 * ============================
 * Extra Localization utilities
 * ============================
 */

/*
 * Localize format string.
 *
 * @uses __()
 *
 * @param string $text The format string.
 * @param string $domain The domain to use.
 * @params mixed $arg1.. The arguments for vsprintf().
 */
function _f($text, $domain){
	$args = func_get_args();
	$args = array_slice($args, 2);

	return vsprintf(__($text, $domain), $args);
}

/*
 * Localize format string, with context.
 *
 * @uses __()
 *
 * @param string $text The format string.
 * @param string $context The context to use.
 * @param string $domain The domain to use.
 * @params mixed $arg1.. The arguments for vsprintf()
 */
function _fx($text, $context, $domain){
	$args = func_get_args();
	$args = array_slice($args, 3);

	return vsprintf(_x($text, $context, $domain), $args);
}

/*
 * Echo result of _f().
 *
 * @uses _f()
 *
 * @param string $text The format string.
 * @param string $domain The domain to use.
 * @params mixed $arg1.. The arguments for vsprintf().
 */
function _ef($text, $domain){
	echo call_user_func_array('_f', func_get_args());
}

/*
 * Echo result of _xf().
 *
 * @uses _xf()
 *
 * @param string $text The format string.
 * @param string $context The context to use.
 * @param string $domain The domain to use.
 * @params mixed $arg1.. The arguments for vsprintf().
 */
function _efx($text, $context, $domain){
	echo call_user_func_array('_fx', func_get_args());
}

/*
 * Localize an array of strings.
 *
 * @uses __()
 *
 * @param array $array The array to be localized.
 * @param string $domain The domain to use.
 */
function _a($array, $domain = 'default'){
	$_array = array();
	foreach($array as $key => $value){
		$_array[$key] = __($value, $domain);
	}

	return $_array;
}

/*
 * Localize an array of strings.
 *
 * @uses _x()
 *
 * @param array $array The array to be localized.
 * @param string $context The context to use.
 * @param string $domain The domain to use.
 */
function _ax($array, $context, $domain = 'default'){
	$_array = array();
	foreach($array as $key => $value){
		$_array[$key] = _x($value, $context, $domain);
	}

	return $_array;
}