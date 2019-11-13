<?php

include "func.genYearMonthRange.php";

$ret = genYearMonthRange('2011', '01', '2018', '05');
$dash_dates28 = $ret['dash_dates28'];

foreach($dash_dates28 as $date) {
	$cmd = "php es_put_eml.php --date " . $date . " --random";
	exec($cmd);
}

