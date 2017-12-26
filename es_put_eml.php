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

    // continue;

    $frags = explode("=", $eml_link);
    $filename = end($frags) . ".xml";

    $fpath = "./eml/".$filename;

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

    // if contents updated, (re)index it
    if ($sub_updated) {
        $client->index($params);
        //var_dump($output2);
    }
}

// list and sort all available fields and attributes
if ($updated) {
    sort($dot_path_list);
    file_put_contents("dot_path.list", implode("\n", $dot_path_list));
}

