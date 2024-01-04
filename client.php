<?php
require_once(__DIR__.'/vendor/autoload.php');

$client = new \Kicken\Gearman\Client('127.0.0.1:4730');
$job = $client->submitJob('rot13', 'Foobar');
$job->onStatus(function(\Kicken\Gearman\Job\ClientJob $job){
    echo $job->getProgressPercentage()."% complete\n";
})->onComplete(function(\Kicken\Gearman\Job\ClientJob $job){
    echo $job->getResult();
});
$client->wait();
