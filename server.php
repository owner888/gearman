#!/usr/bin/php
<?php

require_once(__DIR__.'/vendor/autoload.php');

use GearmandPHP\Gearmand;
use GearmandPHP\Config;

$config = parse_ini_file(__DIR__.'/config.ini',true);

$base = new EventBase();
$config['dns_base'] = new EventDnsBase($base,true);

$gearmand = new Gearmand(new Config(
	$base,
	$config
));

echo 'xxxxx' . PHP_EOL;
$gearmand->run();
echo 'sdjflsfl' . PHP_EOL;
