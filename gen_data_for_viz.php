<?php

$dbh = new PDO('mysql:host=localhost;dbname=ipt_statistics;charset=utf8', 'taibif', 'ipt@elasticsearch');
$dbh->query("set names 'utf8';");

$sql = "select name, year, month, num_of_occ from dataset_random_records order by name, year, month";

$q = $dbh->query($sql);
while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
	$data[$row['name']][$row['year'].$row['month']] = $row['num_of_occ'];
}


$dataset_names = array_keys($data);

include "func.genYearMonthRange.php";

$ret = genYearMonthRange('2011', '01', '2018', '05');
$dates = $ret['dates'];

$is_header = true;
$str_out = "";
foreach ($dates as $date) {
	if ($is_header) {
		$is_header = false;
		$str_out .=  "year-month\t" . implode("\t", $dataset_names)."\n";
	}
	$str_out .= $date . "\t";
	$vals = [];
	foreach ($dataset_names as $name) {
		$vals[] = empty($data[$name][$date])?0:$data[$name][$date];
	}
	$str_out .= implode("\t", $vals) . "\n";
}
file_put_contents("ipt_statistics.csv", $str_out);

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

echo json_encode($res);
