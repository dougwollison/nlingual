<?php
class nLingual_WP_Locale extends WP_locale {
	function __construct() {
		parent::__construct( );
	}

	function init() {
		$theme_domain = wp_get_theme()->get( 'TextDomain' );

		// The Weekdays
		$this->weekday[0] = __( 'Sunday', $theme_domain );
		$this->weekday[1] = __( 'Monday', $theme_domain );
		$this->weekday[2] = __( 'Tuesday', $theme_domain );
		$this->weekday[3] = __( 'Wednesday', $theme_domain );
		$this->weekday[4] = __( 'Thursday', $theme_domain );
		$this->weekday[5] = __( 'Friday', $theme_domain );
		$this->weekday[6] = __( 'Saturday', $theme_domain );

		// The first letter of each day.  The _%day%_initial suffix is a hack to make
		// sure the day initials are unique.
		$this->weekday_initial[__( 'Sunday', $theme_domain ) ]    = __( 'S_Sunday_initial', $theme_domain );
		$this->weekday_initial[__( 'Monday', $theme_domain ) ]    = __( 'M_Monday_initial', $theme_domain );
		$this->weekday_initial[__( 'Tuesday', $theme_domain ) ]   = __( 'T_Tuesday_initial', $theme_domain );
		$this->weekday_initial[__( 'Wednesday', $theme_domain ) ] = __( 'W_Wednesday_initial', $theme_domain );
		$this->weekday_initial[__( 'Thursday', $theme_domain ) ]  = __( 'T_Thursday_initial', $theme_domain );
		$this->weekday_initial[__( 'Friday', $theme_domain ) ]    = __( 'F_Friday_initial', $theme_domain );
		$this->weekday_initial[__( 'Saturday', $theme_domain ) ]  = __( 'S_Saturday_initial', $theme_domain );

		foreach ( $this->weekday_initial as $weekday_ => $weekday_initial_ ) {
			$this->weekday_initial[ $weekday_ ] = preg_replace( '/_.+_initial$/', '', $weekday_initial_ );
		}

		// Abbreviations for each day.
		$this->weekday_abbrev[__( 'Sunday', $theme_domain ) ]    = __( 'Sun', $theme_domain );
		$this->weekday_abbrev[__( 'Monday', $theme_domain ) ]    = __( 'Mon', $theme_domain );
		$this->weekday_abbrev[__( 'Tuesday', $theme_domain ) ]   = __( 'Tue', $theme_domain );
		$this->weekday_abbrev[__( 'Wednesday', $theme_domain ) ] = __( 'Wed', $theme_domain );
		$this->weekday_abbrev[__( 'Thursday', $theme_domain ) ]  = __( 'Thu', $theme_domain );
		$this->weekday_abbrev[__( 'Friday', $theme_domain ) ]    = __( 'Fri', $theme_domain );
		$this->weekday_abbrev[__( 'Saturday', $theme_domain ) ]  = __( 'Sat', $theme_domain );

		// The Months
		$this->month['01'] = __( 'January', $theme_domain );
		$this->month['02'] = __( 'February', $theme_domain );
		$this->month['03'] = __( 'March', $theme_domain );
		$this->month['04'] = __( 'April', $theme_domain );
		$this->month['05'] = __( 'May', $theme_domain );
		$this->month['06'] = __( 'June', $theme_domain );
		$this->month['07'] = __( 'July', $theme_domain );
		$this->month['08'] = __( 'August', $theme_domain );
		$this->month['09'] = __( 'September', $theme_domain );
		$this->month['10'] = __( 'October', $theme_domain );
		$this->month['11'] = __( 'November', $theme_domain );
		$this->month['12'] = __( 'December', $theme_domain );

		// Abbreviations for each month. Uses the same hack as above to get around the
		// 'May' duplication.
		$this->month_abbrev[ __( 'January', $theme_domain ) ]   = __( 'Jan_January_abbreviation', $theme_domain );
		$this->month_abbrev[ __( 'February', $theme_domain ) ]  = __( 'Feb_February_abbreviation', $theme_domain );
		$this->month_abbrev[ __( 'March', $theme_domain ) ]     = __( 'Mar_March_abbreviation', $theme_domain );
		$this->month_abbrev[ __( 'April', $theme_domain ) ]     = __( 'Apr_April_abbreviation', $theme_domain );
		$this->month_abbrev[ __( 'May', $theme_domain ) ]       = __( 'May_May_abbreviation', $theme_domain );
		$this->month_abbrev[ __( 'June', $theme_domain ) ]      = __( 'Jun_June_abbreviation', $theme_domain );
		$this->month_abbrev[ __( 'July', $theme_domain ) ]      = __( 'Jul_July_abbreviation', $theme_domain );
		$this->month_abbrev[ __( 'August', $theme_domain ) ]    = __( 'Aug_August_abbreviation', $theme_domain );
		$this->month_abbrev[ __( 'September', $theme_domain ) ] = __( 'Sep_September_abbreviation', $theme_domain );
		$this->month_abbrev[ __( 'October', $theme_domain ) ]   = __( 'Oct_October_abbreviation', $theme_domain );
		$this->month_abbrev[ __( 'November', $theme_domain ) ]  = __( 'Nov_November_abbreviation', $theme_domain );
		$this->month_abbrev[ __( 'December',$theme_domain ) ]   = __( 'Dec_December_abbreviation', $theme_domain );

		foreach ( $this->month_abbrev as $month_ => $month_abbrev_ ) {
			$this->month_abbrev[ $month_ ] = preg_replace( '/_.+_abbreviation$/', '', $month_abbrev_ );
		}

		// The Meridiems
		$this->meridiem['am'] = __( 'am', $theme_domain );
		$this->meridiem['pm'] = __( 'pm', $theme_domain );
		$this->meridiem['AM'] = __( 'AM', $theme_domain );
		$this->meridiem['PM'] = __( 'PM', $theme_domain );

		// Numbers formatting
		// See http://php.net/number_format

		/* translators: $thousands_sep argument for http://php.net/number_format, default is , */
		$trans = __( 'number_format_thousands_sep', $theme_domain );
		$this->number_format['thousands_sep'] = ( 'number_format_thousands_sep' == $trans ) ? ',' : $trans;

		/* translators: $dec_point argument for http://php.net/number_format, default is . */
		$trans = __( 'number_format_decimal_point', $theme_domain );
		$this->number_format['decimal_point'] = ( 'number_format_decimal_point' == $trans ) ? '.' : $trans;

		// test version // 2.7.1
		global $wp_version;
		if ( version_compare( $wp_version, '3.4', '<' ) ) {
			// Import global locale vars set during inclusion of $locale.php.
			foreach ( (array) $this->locale_vars as $var ) {
				if ( isset( $GLOBALS[ $var ] ) )
					$this->$var = $GLOBALS[ $var ];
			}
		} else {
			// Set text direction.
			if ( isset( $GLOBALS['text_direction'] ) )
				$this->text_direction = $GLOBALS['text_direction'];
			/* translators: 'rtl' or 'ltr'. This sets the text direction for WordPress. */
			elseif ( 'rtl' == _x( 'ltr', 'text direction', $theme_domain ) )
				$this->text_direction = 'rtl';

		}
	}
}
