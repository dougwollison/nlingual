<?php
/**
 * nLingual Theme Locale Patch
 *
 * @package nLingual
 * @subpackage Patches
 */

namespace nLingual;

/**
 * The WP_Locale Replacement
 *
 * Intended to replace the $wp_locale object so that the
 * theme's own text domain is used in localizing these strings.
 *
 * @api
 *
 * @since 2.0.0
 */
final class Locale extends \WP_Locale {
	/**
	 * @see WP_Locale::init()
	 */
	public function init() {
		// Get the theme's text domain
		$domain = wp_get_theme()->get( 'TextDomain' );

		// The Weekdays
		$this->weekday[0] = /* translators: weekday */ __( 'Sunday', $domain );
		$this->weekday[1] = /* translators: weekday */ __( 'Monday', $domain );
		$this->weekday[2] = /* translators: weekday */ __( 'Tuesday', $domain );
		$this->weekday[3] = /* translators: weekday */ __( 'Wednesday', $domain );
		$this->weekday[4] = /* translators: weekday */ __( 'Thursday', $domain );
		$this->weekday[5] = /* translators: weekday */ __( 'Friday', $domain );
		$this->weekday[6] = /* translators: weekday */ __( 'Saturday', $domain );

		// The first letter of each day. The _%day%_initial suffix is a hack to make
		// sure the day initials are unique.
		$this->weekday_initial[ __( 'Sunday', $domain ) ]    = /* translators: one-letter abbreviation of the weekday */ __( 'S_Sunday_initial', $domain );
		$this->weekday_initial[ __( 'Monday', $domain ) ]    = /* translators: one-letter abbreviation of the weekday */ __( 'M_Monday_initial', $domain );
		$this->weekday_initial[ __( 'Tuesday', $domain ) ]   = /* translators: one-letter abbreviation of the weekday */ __( 'T_Tuesday_initial', $domain );
		$this->weekday_initial[ __( 'Wednesday', $domain ) ] = /* translators: one-letter abbreviation of the weekday */ __( 'W_Wednesday_initial', $domain );
		$this->weekday_initial[ __( 'Thursday', $domain ) ]  = /* translators: one-letter abbreviation of the weekday */ __( 'T_Thursday_initial', $domain );
		$this->weekday_initial[ __( 'Friday', $domain ) ]    = /* translators: one-letter abbreviation of the weekday */ __( 'F_Friday_initial', $domain );
		$this->weekday_initial[ __( 'Saturday', $domain ) ]  = /* translators: one-letter abbreviation of the weekday */ __( 'S_Saturday_initial', $domain );

		foreach ($this->weekday_initial as $weekday_ => $weekday_initial_) {
			$this->weekday_initial[ $weekday_ ] = preg_replace( '/_.+_initial$/', '', $weekday_initial_ );
		}

		// Abbreviations for each day.
		$this->weekday_abbrev[ __( 'Sunday', $domain ) ]    = /* translators: three-letter abbreviation of the weekday */ __( 'Sun', $domain );
		$this->weekday_abbrev[ __( 'Monday', $domain ) ]    = /* translators: three-letter abbreviation of the weekday */ __( 'Mon', $domain );
		$this->weekday_abbrev[ __( 'Tuesday', $domain ) ]   = /* translators: three-letter abbreviation of the weekday */ __( 'Tue', $domain );
		$this->weekday_abbrev[ __( 'Wednesday', $domain ) ] = /* translators: three-letter abbreviation of the weekday */ __( 'Wed', $domain );
		$this->weekday_abbrev[ __( 'Thursday', $domain ) ]  = /* translators: three-letter abbreviation of the weekday */ __( 'Thu', $domain );
		$this->weekday_abbrev[ __( 'Friday', $domain ) ]    = /* translators: three-letter abbreviation of the weekday */ __( 'Fri', $domain );
		$this->weekday_abbrev[ __( 'Saturday', $domain ) ]  = /* translators: three-letter abbreviation of the weekday */ __( 'Sat', $domain );

		// The Months
		$this->month['01'] = /* translators: month name */ __( 'January', $domain );
		$this->month['02'] = /* translators: month name */ __( 'February', $domain );
		$this->month['03'] = /* translators: month name */ __( 'March', $domain );
		$this->month['04'] = /* translators: month name */ __( 'April', $domain );
		$this->month['05'] = /* translators: month name */ __( 'May', $domain );
		$this->month['06'] = /* translators: month name */ __( 'June', $domain );
		$this->month['07'] = /* translators: month name */ __( 'July', $domain );
		$this->month['08'] = /* translators: month name */ __( 'August', $domain );
		$this->month['09'] = /* translators: month name */ __( 'September', $domain );
		$this->month['10'] = /* translators: month name */ __( 'October', $domain );
		$this->month['11'] = /* translators: month name */ __( 'November', $domain );
		$this->month['12'] = /* translators: month name */ __( 'December', $domain );

		// Abbreviations for each month. Uses the same hack as above to get around the
		// 'May' duplication.
		$this->month_abbrev[ __( 'January', $domain ) ]   = /* translators: three-letter abbreviation of the month */ __( 'Jan_January_abbreviation', $domain );
		$this->month_abbrev[ __( 'February', $domain ) ]  = /* translators: three-letter abbreviation of the month */ __( 'Feb_February_abbreviation', $domain );
		$this->month_abbrev[ __( 'March', $domain ) ]     = /* translators: three-letter abbreviation of the month */ __( 'Mar_March_abbreviation', $domain );
		$this->month_abbrev[ __( 'April', $domain ) ]     = /* translators: three-letter abbreviation of the month */ __( 'Apr_April_abbreviation', $domain );
		$this->month_abbrev[ __( 'May', $domain ) ]       = /* translators: three-letter abbreviation of the month */ __( 'May_May_abbreviation', $domain );
		$this->month_abbrev[ __( 'June', $domain ) ]      = /* translators: three-letter abbreviation of the month */ __( 'Jun_June_abbreviation', $domain );
		$this->month_abbrev[ __( 'July', $domain ) ]      = /* translators: three-letter abbreviation of the month */ __( 'Jul_July_abbreviation', $domain );
		$this->month_abbrev[ __( 'August', $domain ) ]    = /* translators: three-letter abbreviation of the month */ __( 'Aug_August_abbreviation', $domain );
		$this->month_abbrev[ __( 'September', $domain ) ] = /* translators: three-letter abbreviation of the month */ __( 'Sep_September_abbreviation', $domain );
		$this->month_abbrev[ __( 'October', $domain ) ]   = /* translators: three-letter abbreviation of the month */ __( 'Oct_October_abbreviation', $domain );
		$this->month_abbrev[ __( 'November', $domain ) ]  = /* translators: three-letter abbreviation of the month */ __( 'Nov_November_abbreviation', $domain );
		$this->month_abbrev[ __( 'December', $domain ) ]  = /* translators: three-letter abbreviation of the month */ __( 'Dec_December_abbreviation', $domain );

		foreach ( $this->month_abbrev as $month_ => $month_abbrev_ ) {
			$this->month_abbrev[  $month_  ] = preg_replace( '/_.+_abbreviation$/', '', $month_abbrev_ );
		}

		// The Meridiems
		$this->meridiem[ 'am' ] = __( 'am', $domain );
		$this->meridiem[ 'pm' ] = __( 'pm', $domain );
		$this->meridiem[ 'AM' ] = __( 'AM', $domain );
		$this->meridiem[ 'PM' ] = __( 'PM', $domain );

		// Numbers formatting
		// See http://php.net/number_format

		/* translators: $thousands_sep argument for http://php.net/number_format, default is , */
		$trans = __( 'number_format_thousands_sep', $domain );
		$this->number_format[ 'thousands_sep' ] = ( 'number_format_thousands_sep' == $trans ) ? ',' : $trans;

		/* translators: $dec_point argument for http://php.net/number_format, default is . */
		$trans = __( 'number_format_decimal_point', $domain );
		$this->number_format[ 'decimal_point' ] = ( 'number_format_decimal_point' == $trans ) ? '.' : $trans;

		// Set text direction.
		if ( isset( $GLOBALS[ 'text_direction' ] ) ) {
			$this->text_direction = $GLOBALS[ 'text_direction' ];
		/* translators: 'rtl' or 'ltr'. This sets the text direction for WordPress. */
		} elseif ( 'rtl' == \_x( 'ltr', 'text direction', 'nlingual', $domain ) ) {
			$this->text_direction = 'rtl';
		}
	}
}
