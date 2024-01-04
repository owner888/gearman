<?php

class Car
{
	public $make;
	public $model;
}

require_once(dirname(__DIR__).'/vendor/autoload.php');

use Phreezer\Phreezer;
use Phreezer\Storage\CouchDB;
use Phreezer\Models\Container;

$client = new CouchDB([
	'database'  => 'phreezer_tests',
	'host'      => 'datashovel_couchdb'
]);

$container = new Container();

for($x=0;$x<2;$x++){

	$car = new Car();
	$car->make = 'ford';
	$car->model = 'mustang';

	$container->objects[] = $car;
}
$couch = $client->getContext();
$uuid = $couch->store($container);
echo 'STORED CONTAINER: '.$uuid.PHP_EOL;
