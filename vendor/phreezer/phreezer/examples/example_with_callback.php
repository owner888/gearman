<?php

require_once(dirname(__DIR__).'/vendor/autoload.php');

use Phreezer\Storage\CouchDB;

$lazyProxy = false;
$blacklist = array();
$useAutoload = true;

$start = microtime(true);

$base = new EventBase();
$dns_base = new EventDnsBase($base,true);

$client = new CouchDB([
	'database'  => 'phreezer_tests',
	'host'      => 'couchdb',
	'base'      => $base,
	'dns_base'  => $dns_base
//	'user'      => '{{USERNAME}}',
//	'pass'      => '{{PASSWORD}}'
]);


$object = new blah();
$object->a = 3;
$object->b = 2;
$object->c = 1;

$couch = $client->getContext();
$couch->store($object, function($uuid) use($couch, $base, $start) {

	echo 'STORED RECORD: '.$uuid.PHP_EOL;
	echo PHP_EOL;

	echo 'FETCHING: '.$uuid.PHP_EOL;
	$couch->fetch($uuid, function($object) use($couch, $uuid, $start, $base) {

		echo 'EXECUTING "blah" method'.PHP_EOL;
		echo $object->blah();
		var_dump($object);

		echo 'DELETING: '.$uuid.PHP_EOL;
		$object->_delete = true;
		$couch->store($object, function($uuid) use($base, $start){
			echo 'COMPLETED IN: '.(microtime(true)-$start).' SECONDS'.PHP_EOL;
			echo PHP_EOL;
			$base->exit();
		});

	});

});


$base->dispatch();

class blah
{
	public $a;
	public $b;
	public $c;
	public function blah(){
		return '   blahblah'.PHP_EOL.PHP_EOL;
	}
}

