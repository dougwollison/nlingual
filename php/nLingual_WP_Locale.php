<?php
class nLingual_WP_Locale extends WP_locale {
	function __construct() {
		parent::__construct();
	}

	function init() {
		$theme_domain = wp_get_theme()->get('TextDomain');

		// The Weekdays
		$this->weekday[0] = /* translators: weekday */ __('Sunday', $theme_domain);
		$this->weekday[1] = /* translators: weekday */ __('Monday', $theme_domain);
		$this->weekday[2] = /* translators: weekday */ __('Tuesday', $theme_domain);
		$this->weekday[3] = /* translators: weekday */ __('Wednesday', $theme_domain);
		$this->weekday[4] = /* translators: weekday */ __('Thursday', $theme_domain);
		$this->weekday[5] = /* translators: weekday */ __('Friday', $theme_domain);
		$this->weekday[6] = /* translators: weekday */ __('Saturday', $theme_domain);

		// The first letter of each day.  The _%day%_initial suffix is a hack to make
		// sure the day initials are unique.
		$this->weekday_initial[__('Sunday', $theme_domain)]    = /* translators: one-letter abbreviation of the weekday */ __('S_Sunday_initial', $theme_domain);
		$this->weekday_initial[__('Monday', $theme_domain)]    = /* translators: one-letter abbreviation of the weekday */ __('M_Monday_initial', $theme_domain);
		$this->weekday_initial[__('Tuesday', $theme_domain)]   = /* translators: one-letter abbreviation of the weekday */ __('T_Tuesday_initial', $theme_domain);
		$this->weekday_initial[__('Wednesday', $theme_domain)] = /* translators: one-letter abbreviation of the weekday */ __('W_Wednesday_initial', $theme_domain);
		$this->weekday_initial[__('Thursday', $theme_domain)]  = /* translators: one-letter abbreviation of the weekday */ __('T_Thursday_initial', $theme_domain);
		$this->weekday_initial[__('Friday', $theme_domain)]    = /* translators: one-letter abbreviation of the weekday */ __('F_Friday_initial', $theme_domain);
		$this->weekday_initial[__('Saturday', $theme_domain)]  = /* translators: one-letter abbreviation of the weekday */ __('S_Saturday_initial', $theme_domain);

		foreach ($this->weekday_initial as $weekday_ => $weekday_initial_) {
			$this->weekday_initial[$weekday_] = preg_replace('/_.+_initial$/', '', $weekday_initial_);
		}

		// Abbreviations for each day.
		$this->weekday_abbrev[__('Sunday', $theme_domain)]    = /* translators: three-letter abbreviation of the weekday */ __('Sun', $theme_domain);
		$this->weekday_abbrev[__('Monday', $theme_domain)]    = /* translators: three-letter abbreviation of the weekday */ __('Mon', $theme_domain);
		$this->weekday_abbrev[__('Tuesday', $theme_domain)]   = /* translators: three-letter abbreviation of the weekday */ __('Tue', $theme_domain);
		$this->weekday_abbrev[__('Wednesday', $theme_domain)] = /* translators: three-letter abbreviation of the weekday */ __('Wed', $theme_domain);
		$this->weekday_abbrev[__('Thursday', $theme_domain)]  = /* translators: three-letter abbreviation of the weekday */ __('Thu', $theme_domain);
		$this->weekday_abbrev[__('Friday', $theme_domain)]    = /* translators: three-letter abbreviation of the weekday */ __('Fri', $theme_domain);
		$this->weekday_abbrev[__('Saturday', $theme_domain)]  = /* translators: three-letter abbreviation of the weekday */ __('Sat', $theme_domain);

		// The Months
		$this->month['01'] = /* translators: month name */ __('January', $theme_domain);
		$this->month['02'] = /* translators: month name */ __('February', $theme_domain);
		$this->month['03'] = /* translators: month name */ __('March', $theme_domain);
		$this->month['04'] = /* translators: month name */ __('April', $theme_domain);
		$this->month['05'] = /* translators: month name */ __('May', $theme_domain);
		$this->month['06'] = /* translators: month name */ __('June', $theme_domain);
		$this->month['07'] = /* translators: month name */ __('July', $theme_domain);
		$this->month['08'] = /* translators: month name */ __('August', $theme_domain);
		$this->month['09'] = /* translators: month name */ __('September', $theme_domain);
		$this->month['10'] = /* translators: month name */ __('October', $theme_domain);
		$this->month['11'] = /* translators: month name */ __('November', $theme_domain);
		$this->month['12'] = /* translators: month name */ __('December', $theme_domain );

		// Abbreviations for each month. Uses the same hack as above to get around the
		// 'May' duplication.
		$this->month_abbrev[__('January', $theme_domain)] = /* translators: three-letter abbreviation of the month */ __('Jan_January_abbreviation', $theme_domain);
		$this->month_abbrev[__('February', $theme_domain)] = /* translators: three-letter abbreviation of the month */ __('Feb_February_abbreviation', $theme_domain);
		$this->month_abbrev[__('March', $theme_domain)] = /* translators: three-letter abbreviation of the month */ __('Mar_March_abbreviation', $theme_domain);
		$this->month_abbrev[__('April', $theme_domain)] = /* translators: three-letter abbreviation of the month */ __('Apr_April_abbreviation', $theme_domain);
		$this->month_abbrev[__('May', $theme_domain)] = /* translators: three-letter abbreviation of the month */ __('May_May_abbreviation', $theme_domain);
		$this->month_abbrev[__('June', $theme_domain)] = /* translators: three-letter abbreviation of the month */ __('Jun_June_abbreviation', $theme_domain);
		$this->month_abbrev[__('July', $theme_domain)] = /* translators: three-letter abbreviation of the month */ __('Jul_July_abbreviation', $theme_domain);
		$this->month_abbrev[__('August', $theme_domain)] = /* translators: three-letter abbreviation of the month */ __('Aug_August_abbreviation', $theme_domain);
		$this->month_abbrev[__('September', $theme_domain)] = /* translators: three-letter abbreviation of the month */ __('Sep_September_abbreviation', $theme_domain);
		$this->month_abbrev[__('October', $theme_domain)] = /* translators: three-letter abbreviation of the month */ __('Oct_October_abbreviation', $theme_domain);
		$this->month_abbrev[__('November', $theme_domain)] = /* translators: three-letter abbreviation of the month */ __('Nov_November_abbreviation', $theme_domain);
		$this->month_abbrev[__('December',$theme_domain)] = /* translators: three-letter abbreviation of the month */ __('Dec_December_abbreviation', $theme_domain);

		foreach ($this->month_abbrev as $month_ => $month_abbrev_) {
			$this->month_abbrev[$month_] = preg_replace('/_.+_abbreviation$/', '', $month_abbrev_);
		}

		// The Meridiems
		$this->meridiem['am'] = __('am', $theme_domain);
		$this->meridiem['pm'] = __('pm', $theme_domain);
		$this->meridiem['AM'] = __('AM', $theme_domain);
		$this->meridiem['PM'] = __('PM', $theme_domain);

		// Numbers formatting
		// See http://php.net/number_format

		/* translators: $thousands_sep argument for http://php.net/number_format, default is , */
		$trans = __('number_format_thousands_sep', $theme_domain);
		$this->number_format['thousands_sep'] = ('number_format_thousands_sep' == $trans) ? ',' : $trans;

		/* translators: $dec_point argument for http://php.net/number_format, default is . */
		$trans = __('number_format_decimal_point', $theme_domain);
		$this->number_format['decimal_point'] = ('number_format_decimal_point' == $trans) ? '.' : $trans;

		// test version // 2.7.1
		global $wp_version;
		if ( version_compare($wp_version, '3.4', '<') ) {
			// Import global locale vars set during inclusion of $locale.php.
			foreach ( (array) $this->locale_vars as $var ) {
				if ( isset($GLOBALS[$var]) )
					$this->$var = $GLOBALS[$var];
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
