<?php

$client = new GearmanClient();
$client->addServer();
var_dump($client->ping('blah'));
