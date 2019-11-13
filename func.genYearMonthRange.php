<?php
function genYearMonthRange ($start_year='2017', $start_month='06', $stop_year='2018', $stop_month='05') {

	$months = ['01','02','03','04','05','06','07','08','09','10','11','12']; 

	$year = $start_year;
	$month = $start_month;
	$pos = array_search($start_month, $months);

	// generate date range: year day combination
	$dates = [];
	$dash_dates28 = [];
	while (($year.$month) <= ($stop_year.$stop_month)) {
		$dates[] = $year.$month;
		$dash_dates28[] = $year."-".$month."-28";
		// next year.month
		$pos++;
		$year_offset = floor($pos / 12);
		$year = $start_year + $year_offset;
		$month = $months[($pos) % 12];
	}

	return ['dates'=>$dates, 'dash_dates28'=>$dash_dates28];

}
