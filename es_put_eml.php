<?php

require 'vendor/autoload.php';
require 'XMLParser.class.php';

use Elasticsearch\ClientBuilder;

// create an elasticsearch client
$client = ClientBuilder::create()->build();

// get ipt rss, wrap everything in <para/> as CDATA (to ignore html tags)
$ipt_rss = file_get_contents("http://ipt.taibif.tw/rss.do");
$ipt_rss = preg_replace('/<para>(.*?)<\/para>/ims', '<para><![CDATA[$1]]></para>', $ipt_rss);
//$ipt_rss = preg_replace('/\:/ims', '-', $ipt_rss);

// parse rss to array
$rss_parser = new XMLParser($ipt_rss);
// $output = $rss_parser->getFlatOutput();
$output = $rss_parser->getOutput();

$dot_path_list = array();
$updated = false;

$opts = getopt("f", ['date:', 'random']);

var_dump($opts);

$now = date('Y-m-d');
$date = explode("-", $now);
if (isset($opts['date'])) {
    preg_match('/^(\d\d\d\d)-(\d\d)-(\d\d)$/', $opts['date'], $date_matched);
    if (@$date_matched[0]) {
        $mon = $date_matched[2];
        $day = $date_matched[3];
        $yr = $date_matched[1];
        if (checkdate($mon, $day, $yr)) {
            $date = [$yr, $mon, $day];
        }
    }
}

$total = 0;
// get and parse eml, then put into elasticsearch endpoint for index
foreach ($output['rss'][0]['channel'][0]['item'] as $item) {
    $sub_updated = false;
    $eml_link = $item['ipt_eml'][0]['_value'];

    $resource_link = $item['link'][0]['_value'];

    echo $resource_link . "\n";
    $resource_content = file_get_contents($resource_link);

    // no structural data for number of occurence, so parsing from html
    preg_match_all('/[\'][0-9,]*[0-9]+[\']/ims', $resource_content, $match);
    $num_of_occurrence = str_replace(array("'", ","), "", @$match[0][0]);
    if (empty($num_of_occurrence)) $num_of_occurrence = 0;
    $num_of_occurrence = (int) $num_of_occurrence;
    $total += $num_of_occurrence;

    // continue;

    $frags = explode("=", $eml_link);
    $filename = end($frags) . ".xml";

    $fpath = __DIR__ . "/eml/".$filename;

    $pubDate = strtotime($item['pubDate'][0]['_value']);

    // if eml is cached and the caching time is later than the publication time on ipt, use the cache
    if (file_exists($fpath) && filemtime($fpath) >= $pubDate) {
        $eml = file_get_contents($fpath);
    }
    else {
        $eml = file_get_contents($eml_link);
        file_put_contents($fpath, $eml);
        $updated = true;
        $sub_updated = true;
        echo "updated.\n";
    }

    $eml_parser = new XMLParser($eml);

    /* flat json
    $output = $eml_parser->getFlatOutput();
    $dot_path = $eml_parser->getDotPath();
    $dot_path_list += $dot_path;
    $dot_path_list = array_unique($dot_path_list);

    foreach($output as &$o) {
        $o['eml_link'] = $eml_link;

        $params = array(
            'index' => 'taibif_ipt',
            'type' => 'eml_elements',
            'body' => $o,
        );

        $client->index($params);

    }
    //*/

    $output2 = $eml_parser->getOutput();
    $output2['num_of_occurrence'] = $num_of_occurrence;
    $output2['eml_link'] = $eml_link;
    $params = array(
        'id' => md5($eml_link),
        'index' => 'taibif_ipt',
        'type' => 'eml',
        'body' => $output2,
    );

    $dataset_local_name_parts = explode("?r=", $eml_link);
    $dataset_local_name = end($dataset_local_name_parts);

    $num_of_occurrence_ = $num_of_occurrence;
    if (isset($opts['random'])) {
        $num_of_occurrence_ = floor($num_of_occurrence * (1.0 + ((float)rand(-30, 30)/100.0)));
    }

    echo $num_of_occurrence_  . "\n";
    echo $num_of_occurrence . "\n";

    $per_dataset_noo_row[] = [($sub_updated)?'true':'false', $date, $num_of_occurrence_, $dataset_local_name];
    $data_index[] = $date[0] . "-" . $date[1] . ":" . $dataset_local_name;

    // if contents updated, (re)index it
    if ($sub_updated || isset($opts['f'])) {
        $client->index($params);
        //var_dump($output2);
    }
}

// list and sort all available fields and attributes
if ($updated) {
    sort($dot_path_list);
    file_put_contents(__DIR__ . "/dot_path.list", implode("\n", $dot_path_list));
}

//$per_dataset_noo_row = [[($updated)?'true':'false', $date, $total, "ipt.taibif.tw"]] + $per_dataset_noo_row;
//$data_index = ([$date[0] . "-" . $date[1] . ":" . 'ipt.taibif.tw']) + $data_index;

//var_dump(array_combine($data_index, $per_dataset_noo_row));

include __DIR__ . "/db_config.php";

$sql = "replace into dataset_records values";

if (isset($opts['random'])) {
    $sql = "replace into dataset_random_records values";
}
$values = [];
foreach ($per_dataset_noo_row as $row) {
    $name = $row[3];
    $year = $row[1][0];
    $month = $row[1][1];
    $day = $row[1][2];
    $num_of_occ = $row[2];

    $json = [];
    $json[$name][$year."-".$month] = $num_of_occ;
    $json['name'] = $name;
    $json[$name]['Y-m'] = $year . "-" . $month;
    $json_str = json_encode($json);

    $sql_[] = "(?, ?, ?, ?, ?, ?, NULL)";
    $values = array_merge($values, [$name, $num_of_occ, $year, $month, $day, $json_str]);
}

//var_dump($values);

$q = $dbh->prepare($sql . implode(",", $sql_));
$q->execute($values);
