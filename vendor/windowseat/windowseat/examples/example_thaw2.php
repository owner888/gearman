<?php

require_once(dirname(__DIR__).'/vendor/autoload.php');

use Phreezer\Storage\CouchDB;

$lazyProxy = false;
$blacklist = array();
$useAutoload = true;

$start = microtime(true);

$client = new CouchDB([
    'database'  => 'test_windowseat',
    'host'      => 'windowseat_couchdb',
//  'debug'     => true, /* if uncommented 'debug' will send raw data to php.ini 'error_log' */
//  'user'      => '{{USERNAME}}',
//  'pass'      => '{{PASSWORD}}'
]);

$couch = $client->getContext();


$object = new \Job();
$object->id = 1;

$couch->store($object);



function E($data){
	error_log(var_export($data,true));
}

class Job
{
	public $id;
}
