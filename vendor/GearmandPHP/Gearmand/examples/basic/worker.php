<?php

// Reverse Worker Code
$worker = new GearmanWorker();
$worker->addServer('localhost', 4731);
$worker->addFunction("reverse", function ($job) {
  return strrev($job->workload());
});
while ($worker->work());

