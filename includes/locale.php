<?php

class VA_Locale {
	/**
	 * Stores the translated strings for the full weekday names.
	 */
	var $weekday;

	/**
	 * Stores the translated strings for the one character weekday names.
	 *
	 * There is a hack to make sure that Tuesday and Thursday, as well
	 * as Sunday and Saturday, don't conflict. See init() method for more.
	 *
	 * @see VA_Locale::init() for how to handle the hack.
	 */
	var $weekday_initial;

	/**
	 * Stores the translated strings for the abbreviated weekday names.
	 */
	var $weekday_abbrev;

	/**
	 * Stores the translated strings for the two-letter short abbreviated weekday names.
	 */
	var $weekday_short_abbrev;

	/**
	 * Stores the translated strings for the full month names.
	 */
	var $month;

	/**
	 * Stores the translated strings for the abbreviated month names.
	 */
	var $month_abbrev;

	/**
	 * Stores the translated strings for 'am' and 'pm'.
	 *
	 * Also the capitalized versions.
	 */
	var $meridiem;

	/**
	 * Sets up the translated strings and object properties.
	 *
	 * The method creates the translatable strings for various
	 * calendar elements. Which allows for specifying locale
	 * specific calendar names and text direction.
	 */
	function init() {
		// The Weekdays
		$this->weekday[0] = /* translators: weekday */ __('Sunday', APP_TD );
		$this->weekday[1] = /* translators: weekday */ __('Monday', APP_TD );
		$this->weekday[2] = /* translators: weekday */ __('Tuesday', APP_TD );
		$this->weekday[3] = /* translators: weekday */ __('Wednesday', APP_TD );
		$this->weekday[4] = /* translators: weekday */ __('Thursday', APP_TD );
		$this->weekday[5] = /* translators: weekday */ __('Friday', APP_TD );
		$this->weekday[6] = /* translators: weekday */ __('Saturday', APP_TD );

		// The first letter of each day. The _%day%_initial suffix is a hack to make
		// sure the day initials are unique.
		$this->weekday_initial[__('Sunday', APP_TD )]    = /* translators: one-letter abbreviation of the weekday */ __('S_Sunday_initial', APP_TD );
		$this->weekday_initial[__('Monday', APP_TD )]    = /* translators: one-letter abbreviation of the weekday */ __('M_Monday_initial', APP_TD );
		$this->weekday_initial[__('Tuesday', APP_TD )]   = /* translators: one-letter abbreviation of the weekday */ __('T_Tuesday_initial', APP_TD );
		$this->weekday_initial[__('Wednesday', APP_TD )] = /* translators: one-letter abbreviation of the weekday */ __('W_Wednesday_initial', APP_TD );
		$this->weekday_initial[__('Thursday', APP_TD )]  = /* translators: one-letter abbreviation of the weekday */ __('T_Thursday_initial', APP_TD );
		$this->weekday_initial[__('Friday', APP_TD )]    = /* translators: one-letter abbreviation of the weekday */ __('F_Friday_initial', APP_TD );
		$this->weekday_initial[__('Saturday', APP_TD )]  = /* translators: one-letter abbreviation of the weekday */ __('S_Saturday_initial', APP_TD );

		foreach ($this->weekday_initial as $weekday_ => $weekday_initial_) {
			$this->weekday_initial[$weekday_] = preg_replace('/_.+_initial$/', '', $weekday_initial_);
		}

		// Abbreviations for each day.
		$this->weekday_abbrev[__('Sunday', APP_TD )]    = /* translators: three-letter abbreviation of the weekday */ __('Sun', APP_TD );
		$this->weekday_abbrev[__('Monday', APP_TD )]    = /* translators: three-letter abbreviation of the weekday */ __('Mon', APP_TD );
		$this->weekday_abbrev[__('Tuesday', APP_TD )]   = /* translators: three-letter abbreviation of the weekday */ __('Tue', APP_TD );
		$this->weekday_abbrev[__('Wednesday', APP_TD )] = /* translators: three-letter abbreviation of the weekday */ __('Wed', APP_TD );
		$this->weekday_abbrev[__('Thursday', APP_TD )]  = /* translators: three-letter abbreviation of the weekday */ __('Thu', APP_TD );
		$this->weekday_abbrev[__('Friday', APP_TD )]    = /* translators: three-letter abbreviation of the weekday */ __('Fri', APP_TD );
		$this->weekday_abbrev[__('Saturday', APP_TD )]  = /* translators: three-letter abbreviation of the weekday */ __('Sat', APP_TD );

		// Two Letter Abbreviations for each day.
		$this->weekday_short_abbrev[__('Sunday', APP_TD )]    = /* translators: two-letter abbreviation of the weekday */ __('Su', APP_TD );
		$this->weekday_short_abbrev[__('Monday', APP_TD )]    = /* translators: two-letter abbreviation of the weekday */ __('Mo', APP_TD );
		$this->weekday_short_abbrev[__('Tuesday', APP_TD )]   = /* translators: two-letter abbreviation of the weekday */ __('Tu', APP_TD );
		$this->weekday_short_abbrev[__('Wednesday', APP_TD )] = /* translators: two-letter abbreviation of the weekday */ __('We', APP_TD );
		$this->weekday_short_abbrev[__('Thursday', APP_TD )]  = /* translators: two-letter abbreviation of the weekday */ __('Th', APP_TD );
		$this->weekday_short_abbrev[__('Friday', APP_TD )]    = /* translators: two-letter abbreviation of the weekday */ __('Fr', APP_TD );
		$this->weekday_short_abbrev[__('Saturday', APP_TD )]  = /* translators: two-letter abbreviation of the weekday */ __('Sa', APP_TD );

		// The Months
		$this->month['01'] = /* translators: month name */ __('January', APP_TD );
		$this->month['02'] = /* translators: month name */ __('February', APP_TD );
		$this->month['03'] = /* translators: month name */ __('March', APP_TD );
		$this->month['04'] = /* translators: month name */ __('April', APP_TD );
		$this->month['05'] = /* translators: month name */ __('May', APP_TD );
		$this->month['06'] = /* translators: month name */ __('June', APP_TD );
		$this->month['07'] = /* translators: month name */ __('July', APP_TD );
		$this->month['08'] = /* translators: month name */ __('August', APP_TD );
		$this->month['09'] = /* translators: month name */ __('September', APP_TD );
		$this->month['10'] = /* translators: month name */ __('October', APP_TD );
		$this->month['11'] = /* translators: month name */ __('November', APP_TD );
		$this->month['12'] = /* translators: month name */ __('December', APP_TD );

		// Abbreviations for each month. Uses the same hack as above to get around the
		// 'May' duplication.
		$this->month_abbrev[__('January', APP_TD )] = /* translators: three-letter abbreviation of the month */ __('Jan_January_abbreviation', APP_TD );
		$this->month_abbrev[__('February', APP_TD )] = /* translators: three-letter abbreviation of the month */ __('Feb_February_abbreviation', APP_TD );
		$this->month_abbrev[__('March', APP_TD )] = /* translators: three-letter abbreviation of the month */ __('Mar_March_abbreviation', APP_TD );
		$this->month_abbrev[__('April', APP_TD )] = /* translators: three-letter abbreviation of the month */ __('Apr_April_abbreviation', APP_TD );
		$this->month_abbrev[__('May', APP_TD )] = /* translators: three-letter abbreviation of the month */ __('May_May_abbreviation', APP_TD );
		$this->month_abbrev[__('June', APP_TD )] = /* translators: three-letter abbreviation of the month */ __('Jun_June_abbreviation', APP_TD );
		$this->month_abbrev[__('July', APP_TD )] = /* translators: three-letter abbreviation of the month */ __('Jul_July_abbreviation', APP_TD );
		$this->month_abbrev[__('August', APP_TD )] = /* translators: three-letter abbreviation of the month */ __('Aug_August_abbreviation', APP_TD );
		$this->month_abbrev[__('September', APP_TD )] = /* translators: three-letter abbreviation of the month */ __('Sep_September_abbreviation', APP_TD );
		$this->month_abbrev[__('October', APP_TD )] = /* translators: three-letter abbreviation of the month */ __('Oct_October_abbreviation', APP_TD );
		$this->month_abbrev[__('November', APP_TD )] = /* translators: three-letter abbreviation of the month */ __('Nov_November_abbreviation', APP_TD );
		$this->month_abbrev[__('December', APP_TD )] = /* translators: three-letter abbreviation of the month */ __('Dec_December_abbreviation', APP_TD );

		foreach ($this->month_abbrev as $month_ => $month_abbrev_) {
			$this->month_abbrev[$month_] = preg_replace('/_.+_abbreviation$/', '', $month_abbrev_);
		}

		// The Meridiems
		$this->meridiem['am'] = __('am', APP_TD );
		$this->meridiem['pm'] = __('pm', APP_TD );
		$this->meridiem['AM'] = __('AM', APP_TD );
		$this->meridiem['PM'] = __('PM', APP_TD );

	}

	/**
	 * Retrieve the full translated weekday word.
	 *
	 * Week starts on translated Sunday and can be fetched
	 * by using 0 (zero). So the week starts with 0 (zero)
	 * and ends on Saturday with is fetched by using 6 (six).
	 *
	 * @param int $weekday_number 0 for Sunday through 6 Saturday
	 * @return string Full translated weekday
	 */
	function get_weekday($weekday_number) {
		return $this->weekday[$weekday_number];
	}

	/**
	 * Retrieve the translated weekday initial.
	 *
	 * The weekday initial is retrieved by the translated
	 * full weekday word. When translating the weekday initial
	 * pay attention to make sure that the starting letter does
	 * not conflict.
	 *
	 * @param string $weekday_name
	 * @return string
	 */
	function get_weekday_initial($weekday_name) {
		return $this->weekday_initial[$weekday_name];
	}

	/**
	 * Retrieve the translated weekday abbreviation.
	 *
	 * The weekday abbreviation is retrieved by the translated
	 * full weekday word.
	 *
	 * @param string $weekday_name Full translated weekday word
	 * @return string Translated weekday abbreviation
	 */
	function get_weekday_abbrev($weekday_name) {
		return $this->weekday_abbrev[$weekday_name];
	}

	/**
	 * Retrieve the translated weekday short abbreviation.
	 *
	 * The short weekday abbreviation is retrieved by the translated
	 * full weekday word.
	 *
	 * @param string $weekday_name Full translated weekday word
	 * @return string Translated weekday short abbreviation
	 */
	function get_weekday_short_abbrev($weekday_name) {
		return $this->weekday_short_abbrev[$weekday_name];
	}

	/**
	 * Retrieve the full translated month by month number.
	 *
	 * The $month_number parameter has to be a string
	 * because it must have the '0' in front of any number
	 * that is less than 10. Starts from '01' and ends at
	 * '12'.
	 *
	 * You can use an integer instead and it will add the
	 * '0' before the numbers less than 10 for you.
	 *
	 * @param string|int $month_number '01' through '12'
	 * @return string Translated full month name
	 */
	function get_month($month_number) {
		return $this->month[zeroise($month_number, 2)];
	}

	/**
	 * Retrieve translated version of month abbreviation string.
	 *
	 * The $month_name parameter is expected to be the translated or
	 * translatable version of the month.
	 *
	 * @param string $month_name Translated month to get abbreviated version
	 * @return string Translated abbreviated month
	 */
	function get_month_abbrev($month_name) {
		return $this->month_abbrev[$month_name];
	}

	/**
	 * Retrieve translated version of meridiem string.
	 *
	 * The $meridiem parameter is expected to not be translated.
	 *
	 * @param string $meridiem Either 'am', 'pm', 'AM', or 'PM'. Not translated version.
	 * @return string Translated version
	 */
	function get_meridiem($meridiem) {
		return $this->meridiem[$meridiem];
	}

	function date( $date_format, $date_U ) {

		$date_format = str_replace('F', '\V\A\F', $date_format); // F = Full Month name (May, June, July)
		$date_format = str_replace('M', '\V\A\M', $date_format); // M = Abbrev. month name (May, Jun, Jul)
		$date_format = str_replace('l', '\V\A\l', $date_format); // l = Full weekday name (Sunday, Monday, Tuesday)
		$date_format = str_replace('D', '\V\A\D', $date_format); // D = Abbrev. weekday name (Sun, Mon, Tue)
		$date_format = str_replace('k', '\V\A\k', $date_format); // k = Short abbrev. weekday name (Su, Mo, Tu)

		$formatted = date( $date_format, $date_U );

		$formatted = str_replace( 'VAF', $this->get_month( date( 'm', $date_U ) ), $formatted );
		$formatted = str_replace( 'VAM', $this->get_month_abbrev( $this->get_month( date( 'm', $date_U ) ) ), $formatted );
		$formatted = str_replace( 'VAl', $this->get_weekday( date( 'w', $date_U ) ), $formatted );
		$formatted = str_replace( 'VAD', $this->get_weekday_abbrev( $this->get_weekday( date( 'w', $date_U ) ) ), $formatted );
		$formatted = str_replace( 'VAk', $this->get_weekday_short_abbrev( $this->get_weekday( date( 'w', $date_U ) ) ), $formatted );

		return $formatted;
	}

	function __construct() {
		$this->init();
	}
}
