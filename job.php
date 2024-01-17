<?php
require_once(__DIR__.'/vendor/autoload.php');

$client = new \Kicken\Gearman\Client('127.0.0.1:4730');
$job = $client->submitBackgroundJob('rot13', 'Foobar');
echo $job;