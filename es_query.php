<?php

require 'vendor/autoload.php';

use Elasticsearch\ClientBuilder;

$client = ClientBuilder::create()->build();

$jo = json_decode(file_get_contents('php://input'), true);

if (!empty($jo['q'])) {
    $q = $jo['q'];
}
else if (!empty($argv[1])) {
    // escape * for wildcard search
    $q = str_replace("*", "\\*", $argv[1]);
}
else {
    // if no input, match all
    $query['size'] = 1000;
    $query['query']['match_all'] = new stdClass();
}

if (!isset($query['query'])) {
    $query['size'] = 1000;
    $query['query']['simple_query_string']['query'] = $q;
    // 欄位參考 dot_path.list
    #$query['query']['simple_query_string']['fields'] = array("eml_eml.dataset.title._value", "eml_eml.dataset.creator.individualName.surName._value");
    // match all, title 與 funding 給加權 (加強搜尋標題與贊助者)
    $query['query']['simple_query_string']['fields'] = array("_all", "eml_eml.dataset.title._value^5", "eml_eml.dataset.project.funding.para._value");
    //$query['highlight']['fields']['eml_eml.dataset.title._value']['pre_tags'] = array("<ffss>");
    //$query['highlight']['fields']['eml_eml.dataset.title._value']['post_tags'] = array("</ffss>");

    // 必須設定 field type : nested 才能使用
    #$nested_query['size'] = 1000;
    #$nested_query['query']['nested']['path'] = 'eml_eml.dataset.project';
    #$nested_query['query']['nested']['query']['simple_query_string']['query'] = $q;
}

$params = array(
    'index' => 'taibif_ipt',
    'type' => 'eml',
    //'type' => 'eml_elements',
    'body' => $query,
);

$result = $client->search($params);

header("Content-type: application/json");
// echo json_encode($result, JSON_PRETTY_PRINT);
echo json_encode($result);
//var_dump($result);



