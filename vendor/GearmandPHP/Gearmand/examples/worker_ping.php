<?php

$worker = new GearmanWorker();
$worker->addServer('localhost',4731);
var_dump($worker->echo('blah'));
