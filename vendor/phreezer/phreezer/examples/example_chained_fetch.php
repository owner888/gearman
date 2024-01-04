<?php

/**
 *  Chained Fetching of Objects:
 *  ----------------------------
 *  If you 'store' an object that contains another object
 *  Phreezer will store each object separately, but
 *  'hierarchically linked' (ie. container object links to
 *  child object, but not vice-versa).
 *
 *  So when retrieving an object in a non-blocking environment
 *  you don't know you need to retrieve object #2 until you've
 *  already retrieved object #1.
 *
 *  This example demonstrates that Phreezer will handle 'chained'
 *  fetching of objects seamlessly.
 */

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
	'host'      => 'datashovel_couchdb',
	'base'      => $base,
	'dns_base'  => $dns_base
//	'user'      => '{{USERNAME}}',
//	'pass'      => '{{PASSWORD}}'
]);

$driver = new Driver();
$driver->firstname = 'John';
$driver->lastname = 'Doe';

$car = new Car();
$car->driver = $driver;

$couch = $client->getContext();
$couch->store($car, function($uuid) use($couch, $base, $start) {

	echo 'STORED RECORD: '.$uuid.PHP_EOL;
	echo PHP_EOL;

	echo 'FETCHING: '.$uuid.PHP_EOL;
	$couch->fetch($uuid, function($car) use($couch, $uuid, $start, $base) {

		echo 'EXECUTING "drive" method'.PHP_EOL;
		echo $car->drive();
		var_dump($car);

		echo 'DELETING DRIVER: '.$car->driver->getName().PHP_EOL;
		$car->driver->_delete = true;
		echo 'DELETING CAR: '.$uuid.PHP_EOL;
		$car->_delete = true;

		$couch->store($car, function($uuid) use($base, $start){
			echo 'COMPLETED IN: '.(microtime(true)-$start).' SECONDS'.PHP_EOL;
			echo PHP_EOL;
			$base->exit();
		});

	});

});


$base->dispatch();

class Car
{
	public $driver;
	public function drive(){
		return 'vroooom...'.PHP_EOL.PHP_EOL;
	}
}
class Driver
{
	public $firstname;
	public $lastname;
	public function getName(){
		return $this->firstname.' '.$this->lastname;
	}
}

