<?php

require_once(dirname(__DIR__).'/vendor/autoload.php');

use WindowSeat\WindowSeat;
use WindowSeat\CouchConfig;
use WindowSeat\EventHandler;

$config = parse_ini_file(dirname(__DIR__).'/config/config3.ini',true);
$base = new EventBase();

$ws = new WindowSeat(new CouchConfig(
	$base,
	$config['couchdb']
));
$ws->setEventHandler(new EventHandler());
$ws->initialize();

$base->loop();

function E($data){
	error_log(var_export($data,true));
}

class Job{}
