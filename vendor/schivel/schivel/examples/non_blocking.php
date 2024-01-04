<?php

require_once(dirname(__DIR__).'/vendor/autoload.php');

use Schivel\Schivel;
use Phreezer\Storage\CouchDB;

$base = new EventBase();
$dns_base = new EventDnsBase($base, true);

$couch = new Schivel(new CouchDB([
	'database'=>'schivel_test',
	'host'=>'datashovel_couchdb',
	'base'=>$base,
	'dns_base'=>$dns_base
]));

$car = new car();
$car->make = 'Ford';
$car->model = 'Explorer';
$car->owner = 'me';

$couch->store($car, function($uuid) use($couch, $car){
	$car2 = new car();
	$car2->make = 'VW';
	$car2->model = 'Jetta';
	$car2->owner = 'me';

	$couch->store($car2, function($uuid2) use($uuid, $couch, $car, $car2) {
		runasyncqueries($couch, $car, $car2, $uuid, $uuid2);
	});
	
});


echo <<<HEADER
ALL EXAMPLES CONCURRENTLY (with few exceptions)
--------------------------------\n
HEADER;

function runasyncqueries($couch,$car,$car2, $uuid, $uuid2){
	global $base, $start;
	$startkey = min($uuid, $uuid2);
	$endkey = max($uuid, $uuid2);

	$start = microtime(true);
	$couch->async()
	 ->fetchDocByKey('car_by_uuid', $uuid)
	 ->fetchDocstateByKey('car_by_uuid', $uuid)
	 ->fetchValueByKey('car_by_uuid', $uuid)
	 ->fetchIdByKey('car_by_uuid', $uuid)
	 ->fetchObjectByKey('car_by_uuid', $uuid)
	 ->fetchJsonByKey('car_by_uuid', $uuid)
	 ->page(1,1)->fetchDocstateByKey('car_by_owner', $car->owner)
	 ->page(2,1)->fetchDocstateByKey('car_by_owner', $car->owner)
	 ->fetchDocByKeys('car_by_uuid', [$uuid])
	 ->fetchDocstateByKeys('car_by_uuid', [$uuid])
	 ->fetchValueByKeys('car_by_uuid', [$uuid])
	 ->fetchIdByKeys('car_by_uuid', [$uuid])
	 ->fetchObjectByKeys('car_by_uuid', [$uuid])
	 ->fetchJsonByKeys('car_by_uuid', [$uuid])
	 ->page(1,1)->fetchDocstateByKeys('car_by_owner', [$car->owner])
	 ->page(2,1)->fetchDocstateByKeys('car_by_owner', [$car->owner])
	 ->fetchDocByRange('car_by_uuid', $startkey, $endkey)
	 ->fetchDocstateByRange('car_by_uuid', $startkey, $endkey)
	 ->fetchValueByRange('car_by_uuid', $startkey, $endkey)
	 ->fetchIdByRange('car_by_uuid', $startkey, $endkey)
	 ->fetchObjectByRange('car_by_uuid', $startkey, $endkey)
	 ->fetchJsonByRange('car_by_uuid', $startkey, $endkey)
	 ->page(1,1)->fetchDocstateByRange('car_by_owner', $car->owner, $car->owner)
	 ->page(2,1)->fetchDocstateByRange('car_by_owner', $car->owner, $car->owner)
	 ->page(1,2)->fetchDocstateByRange('car_by_owner', $car->owner, $car->owner)
	 ->page(1,2)->desc()->fetchDocstateByRange('car_by_owner', $car->owner, $car->owner)
	 ->dispatchViews(function($views) use($couch, $car, $car2, $base, $start){

		var_dump($views);

		$car->_delete = true;
		$car2->_delete = true;

		$couch->store($car, function($uuid) use($couch, $car2, $base, $start){
			echo 'DELETED CAR: '.$uuid.PHP_EOL;

			$couch->store($car2, function($uuid) use($base, $start) {
				echo 'DELETED CAR: '.$uuid.PHP_EOL;
				echo 'FINISHED IN: '.(microtime(true)-$start).' SECONDS'.PHP_EOL;
				$base->exit();
			});
		});
	});

}
/*
  // cant call isDuplicate in async mode since it's not a 'view' query
 ->isDuplicate('car_by_owner', $car->owner)
*/


$base->loop();

class car
{
	public $make;
	public $model;
	public function __construct(){
		
	}
}

function E($val){
	error_log(var_export($val,true));
}

echo 'MEMORY USAGE: '.memory_get_peak_usage(true).PHP_EOL;
