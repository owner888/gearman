<?php
require_once(__DIR__.'/vendor/autoload.php');

$worker = new \Kicken\Gearman\Worker('127.0.0.1:4730');
$worker
    ->registerFunction('rot13', function(\Kicken\Gearman\Job\WorkerJob $job){
        $workload = $job->getWorkload();
        echo "Running rot13 task with workload {$workload}\n";

        return str_rot13($workload);
    })
    ->work()
;
