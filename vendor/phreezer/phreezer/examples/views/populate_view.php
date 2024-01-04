<?php

/**
 *  Example assumes you will set up your own mock view to query.
 */

require_once(dirname(dirname(__DIR__)).'/vendor/autoload.php');

use Phreezer\Storage\CouchDB;

$lazyProxy = false;
$blacklist = array();
$useAutoload = true;

$start = microtime(true);

$base = new EventBase();
$dns_base = new EventDnsBase($base,true);

$client = new CouchDB([
	'database'  => 'phreezer_tests',
	'host'      => 'datashovel_couchdb',
	'base'      => $base,
	'dns_base'  => $dns_base
//	'user'      => '{{USERNAME}}',
//	'pass'      => '{{PASSWORD}}'
]);

$obj = new ViewTestClass();
$obj->name = 'john';
$obj->microtime = microtime(true);

$obj2 = new ViewTestClass();
$obj2->name = 'jane';
$obj2->microtime = microtime(true);

$couch = $client->getContext();
$couch->store($obj);
$couch->store($obj2, function($uuid) use($base) {
	$base->exit();
});

$base->dispatch();

class ViewTestClass
{
	public $name;
	public $microtime;
}
