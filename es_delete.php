<?php

require 'vendor/autoload.php';
require 'XMLParser.class.php';

use Elasticsearch\ClientBuilder;

$client = ClientBuilder::create()->build();

$params = array(
    'index' => 'taibif_ipt',
);

// 清空特定的 index
$client->indices()->delete($params);


