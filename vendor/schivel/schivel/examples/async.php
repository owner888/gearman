<?php

require_once(dirname(__DIR__).'/vendor/autoload.php');

use Schivel\Schivel;
use Phreezer\Storage\CouchDB;

$couch = new Schivel(new CouchDB([
	'host'=>'datashovel_couchdb',
	'database'=>'schivel_test'
]));

$car = new car();
$car->make = 'Ford';
$car->model = 'Explorer';
$car->owner = 'me';

$uuid = $couch->store($car);

$car2 = new car();
$car2->make = 'VW';
$car2->model = 'Jetta';
$car2->owner = 'me';

$uuid2 = $couch->store($car2);

$startkey = min($uuid, $uuid2);
$endkey = max($uuid, $uuid2);

echo <<<HEADER
ALL EXAMPLES CONCURRENTLY (with few exceptions)
--------------------------------\n
HEADER;

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
 ->fetchViews();


$buffers = $couch->getBuffers();
var_dump($buffers);

echo 'FINISHED IN: '.(microtime(true)-$start).' SECONDS'.PHP_EOL;

/*
  // cant call isDuplicate in async mode since it's not a 'view' query
 ->isDuplicate('car_by_owner', $car->owner)
*/

$couch->delete($car);
$couch->delete($car2);

echo 'car objects have been deleted'.PHP_EOL;
var_dump($car);
var_dump($car2);



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
