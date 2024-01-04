<?php

require_once(dirname(__DIR__).'/vendor/autoload.php');

use Phreezer\Storage\CouchDB;

$lazyProxy = false;
$blacklist = array();
$useAutoload = true;

$start = microtime(true);

$client = new CouchDB([
	'database'  => 'phreezer_tests',
	'host'      => 'datashovel_couchdb',
//	'debug'     => true, /* if uncommented 'debug' will send raw data to php.ini 'error_log' */
//	'user'      => '{{USERNAME}}',
//	'pass'      => '{{PASSWORD}}'
]);


$couch = $client->getContext();
$ids = [];
for($x=0; $x<2; $x++){
	$obj = new blah();
	$obj->a = 1+$x;
	$obj->b = 2+$x;
	$obj->c = 3+$x;
	echo 'STORING RECORD: ';
	$ids[] = $id = $couch->store($obj);
	echo $id.PHP_EOL;
}
echo PHP_EOL;

foreach($ids as $id){
	echo 'FETCHING: '.$id.PHP_EOL;
	$obj = $couch->fetch($id);

	var_dump($couch->getLastResults());

	echo 'UPDATING: '.$obj->a.' TO "'.$obj->blah().'"'.PHP_EOL;
	$obj->a = $obj->blah();

	echo 'STORING UPDATED VERSION OF: '.$id.PHP_EOL;
	$couch->store($obj);

	echo PHP_EOL;
}
echo PHP_EOL;

// verify hashing function prevents resubmission of duplicate object
foreach($ids as $id){
	echo 'FETCHING: '.$id.PHP_EOL;
	$obj = $couch->fetch($id);

	echo 'STORING SAME VERSION OF: '.$id.PHP_EOL;
	$couch->store($obj);

	echo PHP_EOL;
}
echo PHP_EOL;

foreach($ids as $id){
	echo 'FETCHING: '.$id.PHP_EOL;
	$obj = $couch->fetch($id);

	echo 'DELETING: '.$id.PHP_EOL;
	$obj->_delete = true;
	$couch->store($obj);

	echo PHP_EOL;
}
echo PHP_EOL;

echo 'COMPLETED IN: '.(microtime(true)-$start).' SECONDS'.PHP_EOL;

class blah
{
	public $a;
	public $b;
	public $c;
	public function blah(){
		return 'blahblah';
	}
}

