<?php

$opts = getopt("f", ['date:', 'random']);

include __DIR__ . "/db_config.php";

$src = "";
if (isset($opts['random'])) {
	$src = 'random_';
}

$sql = "select name, year, month, num_of_occ from dataset_".$src."records order by name, year, month";

$q = $dbh->query($sql);
while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
	$data[$row['name']][$row['year'].$row['month']] = $row['num_of_occ'];
}


$dataset_names = array_keys($data);

include "func.genYearMonthRange.php";

$start_year = '2018';
$start_month = '05';

$current_year = date('Y');
$current_month = date('m');

if (isset($opts['date'])) {
	preg_match('/^(\d\d\d\d)-(\d\d)-(\d\d)$/', $opts['date'], $date_matched);
	if (@$date_matched[0]) {
		$mon = $date_matched[2];
		$day = $date_matched[3];
		$yr = $date_matched[1];
		if (checkdate($mon, $day, $yr)) {
			$current_year = $yr;
			$current_month = $mon;
		}
	}
}



$ret = genYearMonthRange($start_year, $start_month, $current_year, $current_month);
$dates = $ret['dates'];

$is_header = true;
$str_out = "";
$snapshot = [];
foreach ($dates as $date) {
	if ($is_header) {
		$is_header = false;
		$str_out .=  "year-month\t" . implode("\t", $dataset_names)."\n";
	}
	$str_out .= $date . "\t";
	$vals = [];
	foreach ($dataset_names as $name) {
		$tmp_val = empty($data[$name][$date])?0:$data[$name][$date];
		$vals[] = $tmp_val;
		$snapshot[$name] = $tmp_val;
	}
	$str_out .= implode("\t", $vals) . "\n";
}
file_put_contents(__DIR__."/data/tsv/ipt_statistics_$src".$start_year.$start_month."-".$current_year.$current_month.".tsv", $str_out);

$snap = "";
foreach ($snapshot as $name => $val) {
	$snap .= $name . "\t" . $val . "\n";
}
file_put_contents(__DIR__."/data/tsv/ipt_statistics_snapshot_$src".$current_year.$current_month.".tsv", $snap);

$series_data = [];
foreach ($dataset_names as $name) {
	$d = ['name'=>$name, 'data'=>[]];
	foreach ($dates as $date) {
		$d['data'][] = empty($data[$name][$date])?0:((int)$data[$name][$date]);
	}
	$series_data[] = $d;
}

$res['data'] = $series_data;
$res['dates'] = $dates;

$json_str = json_encode($res);
file_put_contents(__DIR__."/data/json/ipt_statistics_$src".$start_year.$start_month."-".$current_year.$current_month.".json", $json_str);
