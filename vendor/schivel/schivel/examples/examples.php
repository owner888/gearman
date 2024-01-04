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

$start = microtime(true);

echo <<<HEADER
BY KEY EXAMPLES
--------------------------------\n
HEADER;

var_dump($couch->fetchDocByKey('car_by_uuid', $uuid));
var_dump($couch->fetchDocstateByKey('car_by_uuid', $uuid));
var_dump($couch->fetchValueByKey('car_by_uuid', $uuid));
var_dump($couch->fetchIdByKey('car_by_uuid', $uuid));
var_dump($couch->fetchObjectByKey('car_by_uuid', $uuid));
var_dump($couch->fetchJsonByKey('car_by_uuid', $uuid));
var_dump($couch->isDuplicate('car_by_owner', $car->owner));

var_dump($couch->page(1,1)->fetchDocstateByKey('car_by_owner', $car->owner));
var_dump($couch->page(2,1)->fetchDocstateByKey('car_by_owner', $car->owner));


echo <<<HEADER
BY KEYS EXAMPLES
--------------------------------\n
HEADER;

var_dump($couch->fetchDocByKeys('car_by_uuid', [$uuid]));
var_dump($couch->fetchDocstateByKeys('car_by_uuid', [$uuid]));
var_dump($couch->fetchValueByKeys('car_by_uuid', [$uuid]));
var_dump($couch->fetchIdByKeys('car_by_uuid', [$uuid]));
var_dump($couch->fetchObjectByKeys('car_by_uuid', [$uuid]));
var_dump($couch->fetchJsonByKeys('car_by_uuid', [$uuid]));

var_dump($couch->page(1,1)->fetchDocstateByKeys('car_by_owner', [$car->owner]));
var_dump($couch->page(2,1)->fetchDocstateByKeys('car_by_owner', [$car->owner]));


echo <<<HEADER
BY RANGE EXAMPLES
--------------------------------\n
HEADER;

$startkey = min($uuid, $uuid2);
$endkey = max($uuid, $uuid2);

var_dump($couch->fetchDocByRange('car_by_uuid', $startkey, $endkey));
var_dump($couch->fetchDocstateByRange('car_by_uuid', $startkey, $endkey));
var_dump($couch->fetchValueByRange('car_by_uuid', $startkey, $endkey));
var_dump($couch->fetchIdByRange('car_by_uuid', $startkey, $endkey));
var_dump($couch->fetchObjectByRange('car_by_uuid', $startkey, $endkey));
var_dump($couch->fetchJsonByRange('car_by_uuid', $startkey, $endkey));

var_dump($couch->page(1,1)->fetchDocstateByRange('car_by_owner', $car->owner, $car->owner));
var_dump($couch->page(2,1)->fetchDocstateByRange('car_by_owner', $car->owner, $car->owner));
var_dump($couch->page(1,2)->fetchDocstateByRange('car_by_owner', $car->owner, $car->owner));


var_dump($couch
		->page(1,2)
		->desc()
		->fetchDocstateByRange('car_by_owner', $car->owner, $car->owner));

echo 'FINISHED IN: '.(microtime(true)-$start).' SECONDS'.PHP_EOL;

$couch->getContext();
$couch->delete($car);
$couch->delete($car2);

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
